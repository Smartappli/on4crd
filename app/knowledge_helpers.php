<?php
declare(strict_types=1);

require_once __DIR__ . '/article_helpers.php';

if (!function_exists('answer_question_from_knowledge')) {
    /**
     * @return list<string>
     */
    function rag_tokens(string $text): array
    {
        $normalized = mb_safe_strtolower(trim($text));
        if ($normalized === '') {
            return [];
        }
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $normalized) ?: [];
        $stopwords = [
            'fr' => ['le','la','les','de','des','du','un','une','et','ou','pour','avec','dans','sur','est','sont','au','aux','ce','cette','ces'],
            'en' => ['the','a','an','and','or','for','with','in','on','is','are','to','of','from','that','this','these'],
            'de' => ['der','die','das','ein','eine','und','oder','mit','im','in','auf','ist','sind','zu','von','für','den','dem'],
            'nl' => ['de','het','een','en','of','met','in','op','is','zijn','voor','van','naar','dat','dit','deze'],
        ];
        $localeStops = $stopwords[current_locale()] ?? $stopwords['fr'];
        $globalStops = ['de'];
        $tokens = [];
        foreach ($parts as $part) {
            $token = trim((string) $part);
            if ($token === '' || mb_strlen($token) < 2) {
                continue;
            }
            if (in_array($token, $localeStops, true) || in_array($token, $globalStops, true)) {
                continue;
            }
            $tokens[$token] = true;
        }
        return array_keys($tokens);
    }

    /**
     * @param list<string> $queryTokens
     */
    function rag_overlap_score(array $queryTokens, string $text): float
    {
        if ($queryTokens === []) {
            return 0.0;
        }
        $haystack = ' ' . mb_safe_strtolower($text) . ' ';
        $score = 0.0;
        foreach ($queryTokens as $token) {
            if (str_contains($haystack, ' ' . $token . ' ')) {
                $score += 1.0;
            } elseif (str_contains($haystack, $token)) {
                $score += 0.5;
            }
        }
        return $score;
    }

    /**
     * @param list<string> $queryTokens
     */


    /**
     * @param list<string> $queryTokens
     */
    function rag_query_coverage(array $queryTokens, string $text): float
    {
        if ($queryTokens === []) {
            return 0.0;
        }
        $normalizedText = ' ' . mb_safe_strtolower($text) . ' ';
        $matched = 0;
        foreach ($queryTokens as $token) {
            if (str_contains($normalizedText, ' ' . $token . ' ') || str_contains($normalizedText, $token)) {
                $matched++;
            }
        }
        return $matched / max(1, count($queryTokens));
    }

    function rag_weighted_score(array $queryTokens, string $text): float
    {
        if ($queryTokens === []) {
            return 0.0;
        }

        $normalizedText = mb_safe_strtolower($text);
        if (trim($normalizedText) === '') {
            return 0.0;
        }

        $score = 0.0;
        foreach ($queryTokens as $token) {
            $quoted = preg_quote($token, '/');
            $wholeWordMatches = preg_match_all('/(?<![\p{L}\p{N}])' . $quoted . '(?![\p{L}\p{N}])/u', $normalizedText);
            if (is_int($wholeWordMatches) && $wholeWordMatches > 0) {
                $score += 1.5 + min(1.5, ($wholeWordMatches - 1) * 0.3);
                continue;
            }

            if (str_contains($normalizedText, $token)) {
                $score += 0.4;
            }
        }

        if (count($queryTokens) >= 2) {
            $phrase = implode(' ', $queryTokens);
            if ($phrase !== '' && str_contains($normalizedText, $phrase)) {
                $score += 2.0;
            }
        }

        return $score;
    }



    function ensure_rag_chunks_table(): bool
    {
        try {
            db()->exec('CREATE TABLE IF NOT EXISTS rag_chunks (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                source_type VARCHAR(32) NOT NULL,
                source_key VARCHAR(191) NOT NULL,
                title VARCHAR(255) NOT NULL,
                body MEDIUMTEXT NOT NULL,
                url VARCHAR(255) DEFAULT NULL,
                embedding_json MEDIUMTEXT NOT NULL,
                embedding_provider VARCHAR(48) NOT NULL DEFAULT "llphant",
                embedding_model VARCHAR(128) NOT NULL DEFAULT "",
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_source (source_type, source_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            try { db()->exec('ALTER TABLE rag_chunks ADD COLUMN embedding_provider VARCHAR(48) NOT NULL DEFAULT "llphant"'); } catch (Throwable) { /* column may already exist */ }
            try { db()->exec('ALTER TABLE rag_chunks ADD COLUMN embedding_model VARCHAR(128) NOT NULL DEFAULT ""'); } catch (Throwable) { /* column may already exist */ }
            try { db()->exec('CREATE INDEX idx_rag_chunks_updated_at ON rag_chunks (updated_at)'); } catch (Throwable) { /* index may already exist */ }
            try { db()->exec('CREATE INDEX idx_rag_chunks_source_type_updated_at ON rag_chunks (source_type, updated_at)'); } catch (Throwable) { /* index may already exist */ }
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return list<string> */
    function rag_chunks_from_text(string $text, int $maxChars = 600, int $overlap = 120): array
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
        if ($plain === '') { return []; }
        $sentences = preg_split('/(?<=[\.\!\?])\s+/u', $plain) ?: [];
        if ($sentences === []) {
            $sentences = [$plain];
        }
        $chunks = [];
        $seen = [];
        $buffer = '';
        foreach ($sentences as $sentence) {
            $sentence = trim((string) $sentence);
            if ($sentence === '') {
                continue;
            }
            $candidate = trim($buffer . ' ' . $sentence);
            if (mb_strlen($candidate) <= $maxChars) {
                $buffer = $candidate;
                continue;
            }
            if ($buffer !== '') {
                $normalized = mb_safe_strtolower($buffer);
                if (!isset($seen[$normalized])) {
                    $seen[$normalized] = true;
                    $chunks[] = $buffer;
                }
                $tail = mb_substr($buffer, max(0, mb_strlen($buffer) - $overlap));
                $buffer = trim($tail . ' ' . $sentence);
            } else {
                $buffer = mb_substr($sentence, 0, $maxChars);
            }
            if (count($chunks) >= 12) { break; }
        }
        if ($buffer !== '' && count($chunks) < 12) {
            $normalized = mb_safe_strtolower($buffer);
            if (!isset($seen[$normalized])) {
                $chunks[] = $buffer;
            }
        }
        if (count($chunks) > 1) {
            $chunks = array_values(array_filter($chunks, static fn (string $chunk): bool => mb_strlen(trim($chunk)) >= 40));
        }
        return $chunks;
    }

    function rag_library_document_body(array $doc): string
    {
        $description = trim((string) ($doc['description'] ?? ''));
        $extracted = trim((string) ($doc['extracted_text'] ?? ''));
        $parts = [];
        if ($description !== '') {
            $parts[] = $description;
        }
        if ($extracted !== '') {
            $parts[] = $extracted;
        }

        $filePath = trim((string) ($doc['file_path'] ?? ''));
        if ($extracted === '' && $filePath !== '') {
            $absPath = storage_path($filePath);
            $ext = mb_safe_strtolower((string) pathinfo($absPath, PATHINFO_EXTENSION));
            $allowed = ['txt', 'md', 'csv', 'json', 'log', 'xml', 'html', 'htm', 'docx', 'pdf'];
            if (is_file($absPath) && in_array($ext, $allowed, true)) {
                $raw = '';
                if ($ext === 'docx') {
                    $raw = rag_extract_docx_text($absPath);
                } elseif ($ext === 'pdf') {
                    $raw = rag_extract_pdf_text($absPath);
                } else {
                    $fileRaw = @file_get_contents($absPath);
                    if (is_string($fileRaw)) {
                        $raw = $fileRaw;
                    }
                }
                if ($raw !== '') {
                    if ($ext === 'html' || $ext === 'htm') {
                        $raw = strip_tags($raw);
                    }
                    $parts[] = trim((string) preg_replace('/\s+/u', ' ', $raw));
                }
            }
        }

        return trim(implode("\n", array_filter($parts, static fn ($v): bool => is_string($v) && $v !== '')));
    }

    function rag_extract_docx_text(string $path): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (!is_string($xml) || $xml === '') {
            return '';
        }
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($xml)));
    }

    function rag_extract_pdf_text(string $path): string
    {
        $binary = trim((string) @shell_exec('command -v pdftotext 2>/dev/null'));
        if ($binary === '') {
            return '';
        }
        $cmd = $binary . ' -layout ' . escapeshellarg($path) . ' - 2>/dev/null';
        $output = @shell_exec($cmd);
        if (!is_string($output) || trim($output) === '') {
            return '';
        }
        return trim((string) preg_replace('/\s+/u', ' ', $output));
    }

    /** @return list<float> */
    function rag_embedding_with_llphant(string $text): array
    {
        if (!class_exists('\\LLPhant\\Embeddings\\EmbeddingGenerator\\EmbeddingGeneratorInterface')) {
            return [];
        }

        try {
            // Preferred integration: app-level LLPhant adapter returning an embedding generator instance.
            if (function_exists('llphant_embedding_generator')) {
                $generator = llphant_embedding_generator();
                if (is_object($generator) && method_exists($generator, 'embedQuery')) {
                    $embedding = $generator->embedQuery($text);
                    if (is_array($embedding)) {
                        return array_values(array_map('floatval', array_filter($embedding, 'is_numeric')));
                    }
                }
            }

            // Backward-compatible integration hook kept for legacy projects.
            if (function_exists('llphant_embedding_vector')) {
                $vector = llphant_embedding_vector($text);
                if (is_array($vector)) {
                    return array_values(array_map('floatval', array_filter($vector, 'is_numeric')));
                }
            }
        } catch (Throwable) {
            return [];
        }

        return [];
    }

    function rag_llphant_is_ready(): bool
    {
        if (!class_exists('\\LLPhant\\Embeddings\\EmbeddingGenerator\\EmbeddingGeneratorInterface')) {
            return false;
        }
        try {
            if (function_exists('llphant_embedding_generator')) {
                $generator = llphant_embedding_generator();
                if (is_object($generator) && method_exists($generator, 'embedQuery')) {
                    return true;
                }
            }
            if (function_exists('llphant_embedding_vector')) {
                $probe = llphant_embedding_vector('ping');
                if (is_array($probe) && $probe !== []) {
                    return true;
                }
            }
        } catch (Throwable) {
            return false;
        }
        return false;
    }

    function rag_llphant_model_name(): string
    {
        $model = trim((string) env('RAG_LLPHANT_EMBEDDING_MODEL', ''));
        return $model;
    }

    /** @return list<float> */
    function rag_embedding_vector(string $text, int $dim = 96): array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return array_fill(0, $dim, 0.0);
        }

        // RAG embeddings are now fully LLPhant-based.
        $providerVector = rag_embedding_with_llphant($trimmed);
        if ($providerVector !== []) {
            return $providerVector;
        }

        return [];
    }

    /** @param list<float> $a @param list<float> $b */
    function rag_cosine_similarity(array $a, array $b): float
    {
        $size = min(count($a), count($b));
        if ($size === 0) { return 0.0; }
        $dot = 0.0;
        for ($i = 0; $i < $size; $i++) { $dot += ((float) $a[$i]) * ((float) $b[$i]); }
        return $dot;
    }

    /** @return list<float> */
    function rag_decode_embedding(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) { return []; }
        $vector = [];
        foreach ($decoded as $value) {
            if (is_numeric($value)) {
                $vector[] = (float) $value;
            }
        }
        return $vector;
    }

    /** @return list<string> */
    function rag_query_variants(string $normalized, array $queryTokens): array
    {
        $variants = [];
        $base = trim($normalized);
        if ($base !== '') {
            $variants[] = $base;
        }
        $tokenOnly = trim(implode(' ', array_slice($queryTokens, 0, 8)));
        if ($tokenOnly !== '' && !in_array($tokenOnly, $variants, true)) {
            $variants[] = $tokenOnly;
        }
        if (count($queryTokens) >= 3) {
            $focus = trim(implode(' ', array_slice($queryTokens, 0, 3)));
            if ($focus !== '' && !in_array($focus, $variants, true)) {
                $variants[] = $focus;
            }
        }
        return array_slice($variants, 0, 3);
    }

    /** @return list<string> */
    function rag_infer_source_types(string $normalized): array
    {
        $q = mb_safe_strtolower($normalized);
        $types = [];
        if (preg_match('/\b(article|blog|news|actualité|actu)\b/u', $q)) {
            $types[] = 'article';
        }
        if (preg_match('/\b(document|pdf|library|bibliothèque|doc)\b/u', $q)) {
            $types[] = 'library';
        }
        if (preg_match('/\b(knowledge|base|faq|guide|tutoriel)\b/u', $q)) {
            $types[] = 'knowledge';
        }
        return array_values(array_unique($types));
    }



    /** @return list<array{variant:string,source_types:list<string>,token_hints:list<string>,limit:int}> */
    function rag_agentic_plan(string $normalized, array $queryTokens, array $variants, array $preferredSourceTypes): array
    {
        $plan = [];
        foreach ($variants as $idx => $variant) {
            $variantTokens = rag_tokens($variant);
            $tokenHints = array_slice($variantTokens, 0, $idx === 0 ? 3 : 2);
            if ($tokenHints === []) { continue; }
            $plan[] = [
                'variant' => $variant,
                'source_types' => $preferredSourceTypes,
                'token_hints' => $tokenHints,
                'limit' => $idx === 0 ? 80 : 60,
            ];
        }
        if ($plan === []) {
            $tokenHints = array_slice($queryTokens, 0, 2);
            if ($tokenHints !== []) {
                $plan[] = [
                    'variant' => $normalized,
                    'source_types' => $preferredSourceTypes,
                    'token_hints' => $tokenHints,
                    'limit' => 70,
                ];
            }
        }
        if ($preferredSourceTypes !== []) {
            $plan[] = [
                'variant' => $normalized,
                'source_types' => [],
                'token_hints' => array_slice($queryTokens, 0, 2),
                'limit' => 50,
            ];
        }
        return $plan;
    }

    function rag_agentic_confidence(float $score, float $coverage, float $margin): float
    {
        $scorePart = max(0.0, min(1.0, ($score - 1.2) / 2.2));
        $coveragePart = max(0.0, min(1.0, $coverage));
        $marginPart = max(0.0, min(1.0, $margin / 0.35));
        return ($scorePart * 0.5) + ($coveragePart * 0.35) + ($marginPart * 0.15);
    }


    function rag_source_group_key(string $sourceType, string $sourceKey, string $title): string
    {
        if ($sourceKey !== '') {
            $base = preg_replace('/_\d+$/', '', $sourceKey);
            if (is_string($base) && $base !== '') {
                return $sourceType . '|' . $base;
            }
            return $sourceType . '|' . $sourceKey;
        }
        return $sourceType . '|' . trim(mb_safe_strtolower($title));
    }



    /** @param array<int,array<string,mixed>> $rows @return array<string,float> */
    function rag_agentic_source_vote(array $rows, array $queryTokens): array
    {
        $votes = [];
        foreach ($rows as $row) {
            if (!is_array($row)) { continue; }
            $sourceType = (string) ($row['source_type'] ?? '');
            if ($sourceType === '') { continue; }
            $title = (string) ($row['title'] ?? '');
            $body = (string) ($row['body'] ?? '');
            $combined = $title . ' ' . $body;
            $coverage = rag_query_coverage($queryTokens, $combined);
            $lexical = rag_weighted_score($queryTokens, $title) + (rag_weighted_score($queryTokens, $body) * 0.6);
            $votes[$sourceType] = ($votes[$sourceType] ?? 0.0) + ($coverage * 1.5) + $lexical;
        }
        return $votes;
    }



    function rag_agentic_answer_is_valid(string $normalizedQuestion, string $summary, float $coverage, float $confidence): bool
    {
        $summaryNorm = mb_safe_strtolower(trim($summary));
        $questionNorm = mb_safe_strtolower(trim($normalizedQuestion));
        if ($summaryNorm === '') { return false; }
        if (mb_strlen($summaryNorm) < 48) { return false; }
        if ($confidence < 0.5 || $coverage < 0.2) { return false; }
        if ($questionNorm !== '' && ($summaryNorm === $questionNorm || mb_strpos($summaryNorm, $questionNorm) !== false) && mb_strlen($summaryNorm) < 220) {
            return false;
        }
        return true;
    }

    function rag_chunks_are_stale(int $maxAgeSeconds = 86400): bool
    {
        try {
            $stmt = db()->query('SELECT UNIX_TIMESTAMP(MAX(updated_at)) FROM rag_chunks');
            $ts = (int) ($stmt ? $stmt->fetchColumn() : 0);
            if ($ts <= 0) {
                return true;
            }
            return (time() - $ts) > $maxAgeSeconds;
        } catch (Throwable) {
            return true;
        }
    }

    function rag_chunks_need_embedding_refresh(string $provider, string $model): bool
    {
        try {
            $stmt = db()->prepare('SELECT COUNT(*) FROM rag_chunks WHERE embedding_provider = ? AND embedding_model = ?');
            $stmt->execute([$provider, $model]);
            $matching = (int) ($stmt->fetchColumn() ?: 0);
            $totalStmt = db()->query('SELECT COUNT(*) FROM rag_chunks');
            $total = (int) ($totalStmt ? $totalStmt->fetchColumn() : 0);
            if ($total <= 0) {
                return true;
            }
            return $matching !== $total;
        } catch (Throwable) {
            return false;
        }
    }

    function rag_reindex_lock_file(): string
    {
        return cache_dir_path() . '/rag-reindex.lock';
    }

    function rag_can_reindex_now(int $cooldownSeconds = 900): bool
    {
        $file = rag_reindex_lock_file();
        $now = time();
        if (!is_file($file)) {
            @file_put_contents($file, (string) $now);
            return true;
        }
        $last = (int) @file_get_contents($file);
        if (($now - $last) < $cooldownSeconds) {
            return false;
        }
        @file_put_contents($file, (string) $now);
        return true;
    }

    /**
     * @param list<string> $preferredSourceTypes
     * @return array{answer:string,source:string,sources?:array<int,array{title:string,url:string,type:string}>,confidence?:float,freshness_hours?:float|null}
     */
function answer_question_from_knowledge(string $question, array $preferredSourceTypes = []): array
{
        $locale = current_locale();
        $chatbotI18n = [
            'fr' => [
                'empty_question' => 'Je n’ai pas reçu de question exploitable.',
                'no_precise_yet' => 'Je n’ai pas de réponse précise pour le moment.',
                'article_found' => 'J’ai trouvé un article pertinent : ',
                'summary' => 'Résumé : ',
                'link' => 'Lien : ',
                'articles_source' => 'Articles ON4CRD',
                'knowledge_source' => 'Base de connaissances ON4CRD',
                'article_label' => 'Article',
                'no_answer' => 'Je n’ai pas de réponse précise pour cette question. Essayez de mentionner un mot-clé (QSL, antenne, propagation, licence) ou consultez le module Articles.',
                'assistant_source' => 'Assistant Raymond',
            ],
            'en' => [
                'empty_question' => 'I did not receive a usable question.',
                'no_precise_yet' => 'I do not have a precise answer right now.',
                'article_found' => 'I found a relevant article: ',
                'summary' => 'Summary: ',
                'link' => 'Link: ',
                'articles_source' => 'ON4CRD articles',
                'knowledge_source' => 'ON4CRD knowledge base',
                'article_label' => 'Article',
                'no_answer' => 'I do not have a precise answer for this question. Try adding a keyword (QSL, antenna, propagation, license) or browse the Articles module.',
                'assistant_source' => 'Raymond assistant',
            ],
            'de' => [
                'empty_question' => 'Ich habe keine verwertbare Frage erhalten.',
                'no_precise_yet' => 'Ich habe im Moment keine genaue Antwort.',
                'article_found' => 'Ich habe einen relevanten Artikel gefunden: ',
                'summary' => 'Zusammenfassung: ',
                'link' => 'Link: ',
                'articles_source' => 'ON4CRD-Artikel',
                'knowledge_source' => 'ON4CRD-Wissensdatenbank',
                'article_label' => 'Artikel',
                'no_answer' => 'Ich habe keine genaue Antwort auf diese Frage. Versuchen Sie ein Schlüsselwort (QSL, Antenne, Ausbreitung, Lizenz) oder nutzen Sie das Artikel-Modul.',
                'assistant_source' => 'Assistent Raymond',
            ],
            'nl' => [
                'empty_question' => 'Ik heb geen bruikbare vraag ontvangen.',
                'no_precise_yet' => 'Ik heb momenteel geen exact antwoord.',
                'article_found' => 'Ik heb een relevant artikel gevonden: ',
                'summary' => 'Samenvatting: ',
                'link' => 'Link: ',
                'articles_source' => 'ON4CRD-artikels',
                'knowledge_source' => 'ON4CRD-kennisbank',
                'article_label' => 'Artikel',
                'no_answer' => 'Ik heb geen exact antwoord op deze vraag. Probeer een trefwoord (QSL, antenne, propagatie, licentie) of bekijk de Artikels-module.',
                'assistant_source' => 'Raymond-assistent',
            ],
        ];
        $chatbotT = $chatbotI18n[$locale] ?? $chatbotI18n['fr'];
        $normalized = mb_safe_strtolower(trim($question));
        if ($normalized === '') {
            return ['answer' => (string) $chatbotT['empty_question'], 'source' => (string) $chatbotT['assistant_source']];
        }

        $queryTokens = rag_tokens($normalized);
        if ($queryTokens === [] && mb_strlen($normalized) < 3) {
            return [
                'answer' => (string) $chatbotT['no_answer'],
                'source' => (string) $chatbotT['assistant_source'],
            ];
        }

        if (ensure_rag_chunks_table()) {
            try {
                $countStmt = db()->query('SELECT COUNT(*) FROM rag_chunks');
                $chunkCount = (int) ($countStmt ? $countStmt->fetchColumn() : 0);
                $embeddingProvider = 'llphant';
                $embeddingModel = rag_llphant_model_name();
                $mustReindex = $chunkCount === 0
                    || rag_chunks_are_stale(43200)
                    || rag_chunks_need_embedding_refresh($embeddingProvider, $embeddingModel);
                $llphantReady = rag_llphant_is_ready();
                if ($llphantReady && $mustReindex && rag_can_reindex_now(900)) {
                    db()->beginTransaction();
                    try {
                        db()->exec('DELETE FROM rag_chunks');
                        $insert = db()->prepare('INSERT INTO rag_chunks (source_type, source_key, title, body, url, embedding_json, embedding_provider, embedding_model) VALUES (?,?,?,?,?,?,?,?)');
                        $knowledgePath = __DIR__ . '/knowledge.php';
                        $knowledgeBase = i18n_load_array_file_once($knowledgePath);
                        if (is_array($knowledgeBase)) {
                            foreach ($knowledgeBase as $idx => $item) {
                                if (!is_array($item)) { continue; }
                                $title = trim((string) ($item['title'] ?? 'Knowledge'));
                                $body = trim((string) ($item['body'] ?? ''));
                                foreach (rag_chunks_from_text($body) as $chunkIndex => $chunk) {
                                    $key = 'kb_' . (string) $idx . '_' . (string) $chunkIndex;
                                    $vec = rag_embedding_vector($title . ' ' . $chunk);
                                    $insert->execute(['knowledge', $key, $title, $chunk, (string) ($item['url'] ?? ''), json_encode($vec), $embeddingProvider, $embeddingModel]);
                                }
                            }
                        }
                        if (table_exists('articles')) {
                            $rows = db()->query('SELECT id, slug, title, excerpt, content, published_at, created_at, updated_at FROM articles WHERE status = "published" ORDER BY ' . article_publication_sort_expression() . ' DESC, id DESC LIMIT 120')->fetchAll() ?: [];
                            foreach ($rows as $row) {
                                if (!is_array($row)) { continue; }
                                $slug = trim((string) ($row['slug'] ?? ''));
                                if ($slug === '') { continue; }
                                $title = trim((string) ($row['title'] ?? 'Article'));
                                $body = trim((string) (($row['excerpt'] ?? '') . "\n" . ($row['content'] ?? '')));
                                foreach (rag_chunks_from_text($body) as $chunkIndex => $chunk) {
                                    $key = 'article_' . $slug . '_' . (string) $chunkIndex;
                                    $vec = rag_embedding_vector($title . ' ' . $chunk);
                                    $insert->execute(['article', $key, $title, $chunk, route_url('article', ['slug' => $slug]), json_encode($vec), $embeddingProvider, $embeddingModel]);
                                }
                            }
                        }

                        if (ensure_member_library_table()) {
                            $docs = db()->query('SELECT id,title,description,extracted_text,file_path FROM member_library_documents ORDER BY uploaded_at DESC LIMIT 120')->fetchAll() ?: [];
                            foreach ($docs as $doc) {
                                if (!is_array($doc)) { continue; }
                                $docId = (int) ($doc['id'] ?? 0);
                                if ($docId <= 0) { continue; }
                                $title = trim((string) ($doc['title'] ?? 'Document'));
                                $body = rag_library_document_body($doc);
                                if ($body === '') { continue; }
                                $safePath = safe_storage_public_path_or_null((string) ($doc['file_path'] ?? ''), ['storage/uploads/library/']) ?? '';
                                $url = $safePath !== '' ? base_url($safePath) : '';
                                foreach (rag_chunks_from_text($body) as $chunkIndex => $chunk) {
                                    $key = 'doc_' . (string) $docId . '_' . (string) $chunkIndex;
                                    $vec = rag_embedding_vector($title . ' ' . $chunk);
                                    $insert->execute(['library', $key, $title, $chunk, $url, json_encode($vec), $embeddingProvider, $embeddingModel]);
                                }
                            }
                        }
                        db()->commit();
                    } catch (Throwable $e) {
                        if (db()->inTransaction()) {
                            db()->rollBack();
                        }
                        throw $e;
                    }
                }

                $rows = [];
                $variants = rag_query_variants($normalized, $queryTokens);
                $inferredSourceTypes = rag_infer_source_types($normalized);
                $preferredSourceTypes = array_values(array_unique(array_filter(array_merge($inferredSourceTypes, $preferredSourceTypes), static fn($v): bool => is_string($v) && $v !== '')));
                $planSteps = rag_agentic_plan($normalized, $queryTokens, $variants, $preferredSourceTypes);
                $planTrace = [];
                foreach ($planSteps as $step) {
                    if (!is_array($step)) { continue; }
                    $tokenHints = isset($step['token_hints']) && is_array($step['token_hints']) ? $step['token_hints'] : [];
                    if ($tokenHints === []) { continue; }
                    $whereParts = [];
                    $params = [];
                    foreach ($tokenHints as $hint) {
                        $whereParts[] = '(title LIKE ? OR body LIKE ?)';
                        $like = '%' . $hint . '%';
                        $params[] = $like;
                        $params[] = $like;
                    }
                    $sql = 'SELECT source_type, source_key, title, body, url, embedding_json, updated_at FROM rag_chunks WHERE ' . implode(' OR ', $whereParts);
                    $stepSourceTypes = isset($step['source_types']) && is_array($step['source_types']) ? $step['source_types'] : [];
                    if ($stepSourceTypes !== []) {
                        $typePlaceholders = implode(',', array_fill(0, count($stepSourceTypes), '?'));
                        $sql .= ' AND source_type IN (' . $typePlaceholders . ')';
                        foreach ($stepSourceTypes as $type) { $params[] = $type; }
                    }
                    $stepLimit = max(20, min(120, (int) ($step['limit'] ?? 80)));
                    $sql .= ' ORDER BY updated_at DESC LIMIT ' . $stepLimit;
                    $stmt = db()->prepare($sql);
                    $stmt->execute($params);
                    $fetched = $stmt->fetchAll() ?: [];
                    $planTrace[] = [
                        'variant' => (string) ($step['variant'] ?? ''),
                        'hits' => count($fetched),
                        'typed' => $stepSourceTypes !== [] ? '1' : '0',
                    ];
                    foreach ($fetched as $row) {
                        if (!is_array($row)) { continue; }
                        $uniq = (string) ($row['source_type'] ?? '') . '|' . (string) ($row['source_key'] ?? '');
                        $rows[$uniq] = $row;
                    }
                    if (count($rows) >= 170) {
                        break;
                    }
                }
                if ($rows === []) {
                    $stmt = db()->query('SELECT source_type, source_key, title, body, url, embedding_json, updated_at FROM rag_chunks ORDER BY updated_at DESC LIMIT 220');
                    $rows = $stmt ? ($stmt->fetchAll() ?: []) : [];
                } else {
                    $rows = array_values($rows);
                }
                $sourceVotes = rag_agentic_source_vote($rows, $queryTokens);
                $best = null;
                $bestScore = -1.0;
                $bestCoverage = 0.0;
                $secondBestScore = -1.0;
                $bestVariantUsed = '';
                $rankedCandidates = [];
                $queryComplexity = max(1, count($queryTokens));
                $sourceSeen = [];
                $qVecMap = [];
                $llphantReady = rag_llphant_is_ready();
                if (!$llphantReady) {
                    throw new RuntimeException('llphant_unavailable');
                }
                foreach ($rows as $row) {
                    if (!is_array($row)) { continue; }
                    $vec = rag_decode_embedding((string) ($row['embedding_json'] ?? '[]'));
                    if ($vec === []) { continue; }
                    $sim = 0.0;
                    $variantUsed = '';
                    foreach ($variants as $variant) {
                        if (!isset($qVecMap[$variant])) {
                            $qVecMap[$variant] = rag_embedding_vector($variant);
                        }
                        $variantSim = rag_cosine_similarity($qVecMap[$variant], $vec);
                        if ($variantSim > $sim) {
                            $sim = $variantSim;
                            $variantUsed = $variant;
                        }
                    }
                    if ($sim <= 0.03) { continue; }
                    $title = (string) ($row['title'] ?? '');
                    $body = (string) ($row['body'] ?? '');
                    $combined = $title . ' ' . $body;
                    $coverage = rag_query_coverage($queryTokens, $combined);
                    $lexical = rag_weighted_score($queryTokens, $title) * 1.4
                        + rag_weighted_score($queryTokens, $body) * 0.9;
                    $phraseBoost = 0.0;
                    if (mb_strlen($normalized) >= 4) {
                        $lowerTitle = mb_safe_strtolower($title);
                        $lowerBody = mb_safe_strtolower($body);
                        if (mb_strpos($lowerTitle, $normalized) !== false) {
                            $phraseBoost += 0.38;
                        } elseif (mb_strpos($lowerBody, $normalized) !== false) {
                            $phraseBoost += 0.2;
                        }
                    }
                    $sourceType = (string) ($row['source_type'] ?? '');
                    $sourceBoost = match ($sourceType) {
                        'knowledge' => 0.45,
                        'article' => 0.28,
                        'library' => 0.18,
                        default => 0.0,
                    };
                    $totalVotes = array_sum($sourceVotes);
                    if ($totalVotes > 0.0 && isset($sourceVotes[$sourceType])) {
                        $voteShare = max(0.0, min(1.0, ((float) $sourceVotes[$sourceType]) / $totalVotes));
                        $sourceBoost += $voteShare * 0.22;
                    }
                    $recencyBoost = 0.0;
                    $updatedAt = trim((string) ($row['updated_at'] ?? ''));
                    if ($updatedAt !== '') {
                        try {
                            $ageHours = max(0.0, (time() - (new DateTimeImmutable($updatedAt))->getTimestamp()) / 3600.0);
                            $recencyBoost = max(0.0, 0.2 - min(0.2, $ageHours / 1200.0));
                        } catch (Throwable) {
                            $recencyBoost = 0.0;
                        }
                    }
                    $sourceKey = (string) ($row['source_key'] ?? '');
                    $sourceGroupKey = rag_source_group_key($sourceType, $sourceKey, $title);
                    $duplicatePenalty = 0.0;
                    $seenCount = (int) ($sourceSeen[$sourceGroupKey] ?? 0);
                    if ($seenCount > 0) {
                        $duplicatePenalty = min(0.32, $seenCount * 0.11);
                    }
                    $sourceSeen[$sourceGroupKey] = $seenCount + 1;
                    $score = $sim * 5.2 + $coverage * 2.8 + $lexical * 0.7 + $sourceBoost + $recencyBoost + $phraseBoost - $duplicatePenalty;
                    $rankedCandidates[] = [
                        'score' => $score,
                        'title' => trim((string) ($row['title'] ?? '')),
                        'url' => trim((string) ($row['url'] ?? '')),
                        'type' => $sourceType !== '' ? $sourceType : 'source',
                    ];
                    if ($score > $bestScore) {
                        $secondBestScore = $bestScore;
                        $bestScore = $score;
                        $best = $row;
                        $bestCoverage = $coverage;
                        $bestVariantUsed = $variantUsed;
                    } elseif ($score > $secondBestScore) {
                        $secondBestScore = $score;
                    }
                }
                $isAmbiguous = ($bestScore - $secondBestScore) < 0.08 && $bestCoverage < 0.35;
                $minCoverage = $queryComplexity >= 6 ? 0.28 : 0.2;
                $minScore = $queryComplexity >= 6 ? 2.0 : 1.8;
                $confidence = rag_agentic_confidence($bestScore, $bestCoverage, $bestScore - $secondBestScore);
                $answerAccepted = false;
                if (is_array($best) && !$isAmbiguous && $bestScore >= $minScore && $bestCoverage >= $minCoverage && $confidence >= 0.48) {
                    $summary = trim(mb_substr((string) ($best['body'] ?? ''), 0, 480));
                    $link = trim((string) ($best['url'] ?? ''));
                    $freshnessHours = null;
                    $bestUpdatedAt = trim((string) ($best['updated_at'] ?? ''));
                    if ($bestUpdatedAt !== '') {
                        try {
                            $freshnessHours = max(0.0, (time() - (new DateTimeImmutable($bestUpdatedAt))->getTimestamp()) / 3600.0);
                        } catch (Throwable) {
                            $freshnessHours = null;
                        }
                    }
                    $isStaleCandidate = $freshnessHours !== null && $freshnessHours > 24.0 * 180.0;
                    if ($isStaleCandidate && $confidence < 0.65) {
                        $answerAccepted = false;
                        $best = null;
                    }
                    $answerAccepted = rag_agentic_answer_is_valid($normalized, $summary, $bestCoverage, $confidence);
                    if (!$answerAccepted) {
                        $best = null;
                    }
                    if ($answerAccepted && is_array($best)) {
                        usort($rankedCandidates, static fn(array $a, array $b): int => ((float) ($b['score'] ?? 0.0)) <=> ((float) ($a['score'] ?? 0.0)));
                        $sources = [];
                        $seen = [];
                        foreach ($rankedCandidates as $candidate) {
                            $sourceTitle = trim((string) ($candidate['title'] ?? ''));
                            $sourceUrl = trim((string) ($candidate['url'] ?? ''));
                            $sourceType = trim((string) ($candidate['type'] ?? 'source'));
                            $uniq = $sourceType . '|' . $sourceTitle . '|' . $sourceUrl;
                            if ($sourceTitle === '' || isset($seen[$uniq])) {
                                continue;
                            }
                            $seen[$uniq] = true;
                            $sources[] = ['title' => $sourceTitle, 'url' => $sourceUrl, 'type' => $sourceType];
                            if (count($sources) >= 3) {
                                break;
                            }
                        }
                        $answer = $summary;
                        if ($link !== '') { $answer .= "\n\n" . (string) $chatbotT['link'] . $link; }
                        $sourceType = trim((string) ($best['source_type'] ?? 'source'));
                        $sourceTitle = trim((string) ($best['title'] ?? ''));
                        $source = 'RAG v2 agentic (LLPhant) · ' . $sourceType . ($sourceTitle !== '' ? (' · ' . $sourceTitle) : '');
                        if ($bestVariantUsed !== '') {
                            $source .= ' · variant:' . mb_substr($bestVariantUsed, 0, 48);
                        }
                        $source .= ' · conf:' . (string) round($confidence, 2);
                        if ($sourceVotes !== []) {
                            arsort($sourceVotes);
                            $topVotedType = (string) array_key_first($sourceVotes);
                            if ($topVotedType !== '') {
                                $source .= ' · voted:' . $topVotedType;
                            }
                        }
                        if ($planTrace !== []) {
                            $last = $planTrace[min(count($planTrace) - 1, 2)] ?? null;
                            if (is_array($last)) {
                                $source .= ' · plan:' . (string) ($last['hits'] ?? '0') . 'h';
                            }
                        }
                        return [
                            'answer' => $answer,
                            'source' => $source,
                            'sources' => $sources,
                            'confidence' => (float) round($confidence, 2),
                            'freshness_hours' => $freshnessHours,
                        ];
                    }
                }
            } catch (Throwable) {
                // fallback below
            }
        }

        $knowledgePath = __DIR__ . '/knowledge.php';
        $knowledgeBase = [];
        if (is_file($knowledgePath)) {
            $loaded = i18n_load_array_file_once($knowledgePath);
            if (is_array($loaded)) {
                $knowledgeBase = $loaded;
            }
        }

        $bestScore = -1.0;
        $bestItem = null;
        foreach ($knowledgeBase as $item) {
            if (!is_array($item)) {
                continue;
            }
            $score = 0.0;
            $keywords = isset($item['keywords']) && is_array($item['keywords']) ? $item['keywords'] : [];
            foreach ($keywords as $keyword) {
                $needle = mb_safe_strtolower(trim((string) $keyword));
                if ($needle !== '' && str_contains($normalized, $needle)) {
                    $score += 3.0;
                }
            }
            $title = (string) ($item['title'] ?? '');
            $body = (string) ($item['body'] ?? '');
            $score += rag_weighted_score($queryTokens, $title) * 2.0;
            $score += rag_weighted_score($queryTokens, $body);
            $score += rag_query_coverage($queryTokens, $title . ' ' . $body) * 3.5;
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestItem = $item;
            }
        }

        if ($bestItem !== null && $bestScore > 0) {
            return [
                'answer' => trim((string) ($bestItem['body'] ?? (string) $chatbotT['no_precise_yet'])),
                'source' => trim((string) ($bestItem['source'] ?? (string) $chatbotT['knowledge_source'])),
            ];
        }

        $ragLikeTerms = array_slice($queryTokens, 0, 5);
        if ($normalized !== '') {
            array_unshift($ragLikeTerms, $normalized);
        }
        $ragLikeTerms = array_values(array_unique(array_filter(array_map(
            static fn(string $term): string => trim($term),
            $ragLikeTerms
        ), static fn(string $term): bool => $term !== '')));

        if (table_exists('articles')) {
            try {
                $whereParts = [];
                $translationJoin = '';
                $translationWhere = '';
                $params = [];
                if (current_locale() !== 'fr' && table_exists('article_translations')) {
                    $publicStatuses = article_translation_public_statuses();
                    $statusPlaceholders = implode(',', array_fill(0, count($publicStatuses), '?'));
                    $translationJoin = ' LEFT JOIN article_translations tr ON tr.article_id = a.id AND tr.locale = ? AND tr.status IN (' . $statusPlaceholders . ')';
                    array_push($params, current_locale(), ...$publicStatuses);
                    $translationWhere = ' OR tr.title LIKE ? OR tr.excerpt LIKE ? OR tr.content LIKE ?';
                }
                foreach ($ragLikeTerms as $term) {
                    $like = '%' . $term . '%';
                    $whereParts[] = '(a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ?' . $translationWhere . ')';
                    array_push($params, $like, $like, $like);
                    if ($translationWhere !== '') {
                        array_push($params, $like, $like, $like);
                    }
                }
                if ($whereParts === []) {
                    $whereParts[] = '(a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ?' . $translationWhere . ')';
                    array_push($params, '%'.$question.'%', '%'.$question.'%', '%'.$question.'%');
                    if ($translationWhere !== '') {
                        array_push($params, '%'.$question.'%', '%'.$question.'%', '%'.$question.'%');
                    }
                }
                $sql = 'SELECT a.id, a.title, a.excerpt, a.content, a.slug, a.published_at, a.created_at, a.updated_at
                    FROM articles a' . $translationJoin . '
                    WHERE a.status = "published" AND (' . implode(' OR ', $whereParts) . ')
                    ORDER BY ' . article_publication_sort_expression_for_alias('a') . ' DESC, a.id DESC LIMIT 25';
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                $articles = $stmt->fetchAll();
                $article = null;
                $articleScore = -1.0;
                foreach ($articles as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $row = localized_article_row($row);
                    $titleText = (string) ($row['title_localized'] ?? $row['title'] ?? '');
                    $excerptText = (string) ($row['excerpt_localized'] ?? $row['excerpt'] ?? '');
                    $contentText = (string) ($row['content_localized'] ?? $row['content'] ?? '');
                    $score = rag_weighted_score($queryTokens, $titleText) * 2.0
                        + rag_weighted_score($queryTokens, $excerptText)
                        + rag_weighted_score($queryTokens, $contentText);
                    $score += rag_query_coverage($queryTokens, $titleText . ' ' . $excerptText . ' ' . $contentText) * 3.0;
                    if ($score > $articleScore) {
                        $articleScore = $score;
                        $article = $row;
                    }
                }
                if (is_array($article) && $articleScore >= 2.0) {
                    $title = trim((string) ($article['title'] ?? (string) $chatbotT['article_label']));
                    $excerpt = trim((string) ($article['excerpt'] ?? ''));
                    $slug = trim((string) ($article['slug'] ?? ''));
                    $url = $slug !== '' ? route_url('article', ['slug' => $slug]) : '';
                    $answer = (string) $chatbotT['article_found'] . $title . '.';
                    if ($excerpt !== '') {
                        $answer .= "\n\n" . (string) $chatbotT['summary'] . $excerpt;
                    }
                    if ($url !== '') {
                        $answer .= "\n\n" . (string) $chatbotT['link'] . $url;
                    }
                    return ['answer' => $answer, 'source' => (string) $chatbotT['articles_source']];
                }
            } catch (Throwable) {
                // fallback below
            }
        }

        if (ensure_member_library_table()) {
            try {
                $whereParts = [];
                $params = [];
                foreach ($ragLikeTerms as $term) {
                    $like = '%' . $term . '%';
                    $whereParts[] = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ?)';
                    array_push($params, $like, $like, $like);
                }
                if ($whereParts === []) {
                    $whereParts[] = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ?)';
                    array_push($params, '%'.$question.'%', '%'.$question.'%', '%'.$question.'%');
                }
                $sql = 'SELECT title, description, extracted_text, file_path FROM member_library_documents WHERE (' . implode(' OR ', $whereParts) . ') ORDER BY uploaded_at DESC LIMIT 25';
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                $docs = $stmt->fetchAll();
                $doc = null;
                $docScore = -1.0;
                foreach ($docs as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $score = rag_weighted_score($queryTokens, (string) ($row['title'] ?? '')) * 2.0
                        + rag_weighted_score($queryTokens, (string) ($row['description'] ?? ''))
                        + rag_weighted_score($queryTokens, (string) ($row['extracted_text'] ?? ''));
                    $score += rag_query_coverage($queryTokens, (string) (($row['title'] ?? '') . ' ' . ($row['description'] ?? '') . ' ' . ($row['extracted_text'] ?? ''))) * 3.0;
                    if ($score > $docScore) {
                        $docScore = $score;
                        $doc = $row;
                    }
                }
                if (is_array($doc) && $docScore >= 2.0) {
                    $locale = current_locale();
                    $chatbotDocI18n = [
                        'fr' => ['doc_fallback' => 'Document PDF', 'prefix' => 'J’ai trouvé un document dans la bibliothèque membres : ', 'summary' => 'Résumé : ', 'open' => 'Consulter : ', 'source' => 'Bibliothèque membres'],
                        'en' => ['doc_fallback' => 'PDF document', 'prefix' => 'I found a document in the members library: ', 'summary' => 'Summary: ', 'open' => 'Open: ', 'source' => 'Members library'],
                        'de' => ['doc_fallback' => 'PDF-Dokument', 'prefix' => 'Ich habe ein Dokument in der Mitgliederbibliothek gefunden: ', 'summary' => 'Zusammenfassung: ', 'open' => 'Öffnen: ', 'source' => 'Mitgliederbibliothek'],
                        'nl' => ['doc_fallback' => 'PDF-document', 'prefix' => 'Ik heb een document gevonden in de ledenbibliotheek: ', 'summary' => 'Samenvatting: ', 'open' => 'Openen: ', 'source' => 'Ledenbibliotheek'],
                    ];
                    $chatbotDocT = $chatbotDocI18n[$locale] ?? $chatbotDocI18n['fr'];
                    $docTitle = trim((string) ($doc['title'] ?? (string) $chatbotDocT['doc_fallback']));
                    $docDescription = trim((string) ($doc['description'] ?? ''));
                    $docUrl = trim((string) ($doc['file_path'] ?? ''));
                    $safeDocUrl = safe_storage_public_path_or_null($docUrl, ['storage/uploads/library/']);
                    $answer = (string) $chatbotDocT['prefix'] . $docTitle . '.';
                    if ($docDescription !== '') {
                        $answer .= "\n\n" . (string) $chatbotDocT['summary'] . $docDescription;
                    }
                    if (is_string($safeDocUrl) && $safeDocUrl !== '') {
                        $answer .= "\n\n" . (string) $chatbotDocT['open'] . base_url($safeDocUrl);
                    }
                    return ['answer' => $answer, 'source' => (string) $chatbotDocT['source']];
                }
            } catch (Throwable) {
                // fallback below
            }
        }

        return [
            'answer' => (string) $chatbotT['no_answer'],
            'source' => (string) $chatbotT['assistant_source'],
        ];
    }
}
