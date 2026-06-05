<?php
declare(strict_types=1);

/**
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function localized_article_row(array $row): array
{
    $locale = current_locale();
    if ($locale !== 'fr') {
        $articleId = (int) ($row['id'] ?? 0);
        if ($articleId > 0 && table_exists('article_translations')) {
            try {
                $stmt = db()->prepare('SELECT title, excerpt, content FROM article_translations WHERE article_id = ? AND locale = ? ORDER BY CASE status WHEN "reviewed" THEN 0 WHEN "auto" THEN 1 ELSE 2 END, updated_at DESC LIMIT 1');
                $stmt->execute([$articleId, $locale]);
                $translation = $stmt->fetch();
                if (is_array($translation)) {
                    foreach (['title', 'excerpt', 'content'] as $field) {
                        $value = trim((string) ($translation[$field] ?? ''));
                        if ($value !== '') {
                            $row[$field] = $value;
                        }
                    }
                }
            } catch (Throwable) {
                // Keep the source article when translations are unavailable.
            }
        }
    }

    $row['title_localized'] = (string) ($row['title'] ?? '');
    $row['excerpt_localized'] = (string) ($row['excerpt'] ?? '');
    $row['content_localized'] = (string) ($row['content'] ?? '');

    return $row;
}

function article_translation_source_hash(string $title, string $excerpt, string $content): string
{
    return sha1(trim($title) . "\n---excerpt---\n" . trim($excerpt) . "\n---content---\n" . trim($content));
}

/**
 * @return list<string>
 */
function article_translation_target_locales(): array
{
    return array_values(array_filter(
        supported_locales(),
        static fn(string $locale): bool => $locale !== 'fr'
    ));
}

function article_sanitize_content(string $html): string
{
    $html = trim(sanitize_rich_html($html));
    if ($html === '') {
        return '';
    }

    $allowedTags = array_fill_keys([
        'p',
        'br',
        'strong',
        'b',
        'em',
        'i',
        'u',
        'ul',
        'ol',
        'li',
        'h2',
        'h3',
        'h4',
        'blockquote',
        'pre',
        'code',
        'a',
        'img',
        'figure',
        'figcaption',
        'table',
        'thead',
        'tbody',
        'tr',
        'th',
        'td',
        'hr',
    ], true);
    $allowedAttributes = [
        'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true],
        'img' => ['src' => true, 'alt' => true, 'title' => true, 'width' => true, 'height' => true, 'loading' => true],
        'th' => ['colspan' => true, 'rowspan' => true],
        'td' => ['colspan' => true, 'rowspan' => true],
    ];

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $dom->loadHTML('<!doctype html><html><body>' . $html . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    $allNodes = $dom->getElementsByTagName('*');
    for ($i = $allNodes->length - 1; $i >= 0; $i--) {
        $node = $allNodes->item($i);
        if (!$node instanceof DOMElement) {
            continue;
        }

        $tag = strtolower($node->tagName);
        if ($tag === 'html' || $tag === 'body') {
            continue;
        }

        if (!isset($allowedTags[$tag])) {
            $parent = $node->parentNode;
            if ($parent === null) {
                continue;
            }
            while ($node->firstChild !== null) {
                $parent->insertBefore($node->firstChild, $node);
            }
            $parent->removeChild($node);
            continue;
        }

        $allowedForTag = $allowedAttributes[$tag] ?? [];
        if ($node->hasAttributes()) {
            $toRemove = [];
            foreach ($node->attributes as $attribute) {
                $name = strtolower($attribute->name);
                if (!isset($allowedForTag[$name])) {
                    $toRemove[] = $attribute->name;
                }
            }
            foreach ($toRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        if ($tag === 'a') {
            if ($node->hasAttribute('href')) {
                $safeHref = sanitize_href_attribute($node->getAttribute('href'));
                if ($safeHref === null) {
                    $node->removeAttribute('href');
                } else {
                    $node->setAttribute('href', $safeHref);
                }
            }
            $target = strtolower(trim($node->getAttribute('target')));
            if ($target !== '' && !in_array($target, ['_blank', '_self'], true)) {
                $node->removeAttribute('target');
                $target = '';
            }
            if ($target === '_blank') {
                $node->setAttribute('rel', 'noopener noreferrer');
            } elseif ($node->hasAttribute('rel')) {
                $rel = trim((string) preg_replace('/[^a-z0-9 _-]+/i', '', $node->getAttribute('rel')));
                if ($rel === '') {
                    $node->removeAttribute('rel');
                } else {
                    $node->setAttribute('rel', $rel);
                }
            }
        }

        if ($tag === 'img') {
            $safeSrc = $node->hasAttribute('src') ? sanitize_image_src_attribute($node->getAttribute('src')) : null;
            if ($safeSrc === null) {
                if ($node->parentNode !== null) {
                    $node->parentNode->removeChild($node);
                }
                continue;
            }
            $node->setAttribute('src', $safeSrc);
            $node->setAttribute('loading', 'lazy');
            foreach (['width', 'height'] as $dimension) {
                if (!$node->hasAttribute($dimension)) {
                    continue;
                }
                $value = (int) $node->getAttribute($dimension);
                if ($value <= 0 || $value > 2000 || !preg_match('/^\d+$/', $node->getAttribute($dimension))) {
                    $node->removeAttribute($dimension);
                } else {
                    $node->setAttribute($dimension, (string) $value);
                }
            }
        }

        if ($tag === 'td' || $tag === 'th') {
            foreach (['colspan', 'rowspan'] as $span) {
                if (!$node->hasAttribute($span)) {
                    continue;
                }
                $value = (int) $node->getAttribute($span);
                if ($value <= 0 || $value > 20 || !preg_match('/^\d+$/', $node->getAttribute($span))) {
                    $node->removeAttribute($span);
                } else {
                    $node->setAttribute($span, (string) $value);
                }
            }
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) {
        return '';
    }

    $result = '';
    foreach ($body->childNodes as $child) {
        $result .= $dom->saveHTML($child);
    }

    return trim($result);
}

function article_translation_deepl_target(string $locale): ?string
{
    $locale = strtolower(trim($locale));
    $targets = [
        'en' => 'EN',
        'de' => 'DE',
        'nl' => 'NL',
        'it' => 'IT',
        'es' => 'ES',
        'pt' => 'PT-PT',
        'bg' => 'BG',
        'cs' => 'CS',
        'da' => 'DA',
        'et' => 'ET',
        'fi' => 'FI',
        'el' => 'EL',
        'hu' => 'HU',
        'lv' => 'LV',
        'lt' => 'LT',
        'pl' => 'PL',
        'ro' => 'RO',
        'sk' => 'SK',
        'sl' => 'SL',
        'sv' => 'SV',
        'ar' => 'AR',
        'ja' => 'JA',
        'zh' => 'ZH-HANS',
        'ru' => 'RU',
        'id' => 'ID',
    ];

    return $targets[$locale] ?? null;
}

/**
 * @param list<string> $texts
 * @return list<string>|null
 */
function article_translation_deepl_translate(array $texts, string $locale): ?array
{
    static $deeplUnavailable = false;

    if ($deeplUnavailable) {
        return null;
    }

    $texts = array_values(array_map(static fn(string $text): string => trim($text), $texts));
    if ($texts === [] || implode('', $texts) === '') {
        return null;
    }

    if (strtolower((string) config('translation.provider', 'none')) !== 'deepl') {
        return null;
    }

    $apiKey = trim((string) config('translation.deepl_api_key', ''));
    $targetLang = article_translation_deepl_target($locale);
    if ($apiKey === '' || $targetLang === null) {
        return null;
    }

    $configuredEndpoint = trim((string) config('translation.deepl_api_url', ''));
    $endpoint = $configuredEndpoint !== ''
        ? $configuredEndpoint
        : (str_ends_with($apiKey, ':fx') ? 'https://api-free.deepl.com/v2/translate' : 'https://api.deepl.com/v2/translate');

    $body = http_build_query([
        'auth_key' => $apiKey,
        'source_lang' => 'FR',
        'target_lang' => $targetLang,
        'tag_handling' => 'html',
        'preserve_formatting' => '1',
    ], '', '&', PHP_QUERY_RFC3986);
    foreach ($texts as $text) {
        $body .= '&text=' . rawurlencode($text);
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'timeout' => 6,
            'ignore_errors' => true,
        ],
    ]);

    try {
        $response = @file_get_contents($endpoint, false, $context);
    } catch (Throwable) {
        $deeplUnavailable = true;
        return null;
    }
    if (!is_string($response) || trim($response) === '') {
        $deeplUnavailable = true;
        return null;
    }

    $payload = json_decode($response, true);
    if (!is_array($payload) || !isset($payload['translations']) || !is_array($payload['translations'])) {
        $deeplUnavailable = true;
        return null;
    }

    $translated = [];
    foreach ($payload['translations'] as $translation) {
        if (!is_array($translation)) {
            $deeplUnavailable = true;
            return null;
        }
        $translated[] = (string) ($translation['text'] ?? '');
    }

    if (count($translated) !== count($texts)) {
        $deeplUnavailable = true;
        return null;
    }

    return $translated;
}

/**
 * @param array{title:string,excerpt:string,content:string} $source
 * @return array{title:string,excerpt:string,content:string,status:string}
 */
function article_translation_auto_fields(array $source, string $locale): array
{
    $sourceTitle = (string) ($source['title'] ?? '');
    $sourceExcerpt = (string) ($source['excerpt'] ?? '');
    $sourceContent = (string) ($source['content'] ?? '');
    $translated = article_translation_deepl_translate([$sourceTitle, $sourceExcerpt, $sourceContent], $locale);
    if (is_array($translated)) {
        return [
            'title' => trim($translated[0]) !== '' ? trim($translated[0]) : $sourceTitle,
            'excerpt' => trim($translated[1]) !== '' ? trim($translated[1]) : $sourceExcerpt,
            'content' => trim($translated[2]) !== '' ? article_sanitize_content($translated[2]) : $sourceContent,
            'status' => 'auto',
        ];
    }

    return [
        'title' => $sourceTitle,
        'excerpt' => $sourceExcerpt,
        'content' => $sourceContent,
        'status' => 'needs_review',
    ];
}

function article_translation_upsert(int $articleId, string $locale, ?string $title = null, ?string $summary = null, ?string $content = null): void
{
    if ($articleId <= 0 || $locale === 'fr' || !table_exists('article_translations')) {
        return;
    }

    $sourceStmt = db()->prepare('SELECT title, excerpt, content FROM articles WHERE id = ? LIMIT 1');
    $sourceStmt->execute([$articleId]);
    $source = $sourceStmt->fetch();
    if (!is_array($source)) {
        return;
    }

    $sourceFields = [
        'title' => (string) ($source['title'] ?? ''),
        'excerpt' => (string) ($source['excerpt'] ?? ''),
        'content' => (string) ($source['content'] ?? ''),
    ];
    $sourceHash = article_translation_source_hash($sourceFields['title'], $sourceFields['excerpt'], $sourceFields['content']);

    $existingStmt = db()->prepare('SELECT status, source_hash FROM article_translations WHERE article_id = ? AND locale = ? LIMIT 1');
    $existingStmt->execute([$articleId, $locale]);
    $existing = $existingStmt->fetch() ?: null;
    if ($title === null && $summary === null && $content === null && is_array($existing) && (string) ($existing['status'] ?? '') === 'reviewed' && (string) ($existing['source_hash'] ?? '') === $sourceHash) {
        return;
    }

    if ($title === null && $summary === null && $content === null) {
        $fields = article_translation_auto_fields($sourceFields, $locale);
    } else {
        $fields = [
            'title' => trim((string) ($title ?? '')) !== '' ? trim((string) $title) : $sourceFields['title'],
            'excerpt' => trim((string) ($summary ?? '')) !== '' ? trim((string) $summary) : $sourceFields['excerpt'],
            'content' => trim((string) ($content ?? '')) !== '' ? article_sanitize_content((string) $content) : $sourceFields['content'],
            'status' => 'needs_review',
        ];
    }

    $update = db()->prepare('UPDATE article_translations SET source_hash = ?, title = ?, excerpt = ?, content = ?, status = ?, reviewed_by = NULL, reviewed_at = NULL, updated_at = NOW() WHERE article_id = ? AND locale = ?');
    $update->execute([$sourceHash, $fields['title'], $fields['excerpt'], $fields['content'], $fields['status'], $articleId, $locale]);
    if ($update->rowCount() > 0) {
        return;
    }

    db()->prepare('INSERT INTO article_translations (article_id, locale, source_hash, title, excerpt, content, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$articleId, $locale, $sourceHash, $fields['title'], $fields['excerpt'], $fields['content'], $fields['status']]);
}

function article_translations_sync_all(int $articleId): int
{
    $count = 0;
    foreach (article_translation_target_locales() as $locale) {
        article_translation_upsert($articleId, $locale);
        $count++;
    }

    return $count;
}
