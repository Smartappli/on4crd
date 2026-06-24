<?php
declare(strict_types=1);

if (!function_exists('ensure_member_library_table')) {
function ensure_member_library_table(): bool
{
    static $ready = null;
    if (is_bool($ready)) {
        return $ready;
    }
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_library_documents (id INT AUTO_INCREMENT PRIMARY KEY, member_id INT NOT NULL, category VARCHAR(120) NOT NULL DEFAULT "general", subcategory VARCHAR(120) NOT NULL DEFAULT "", tags VARCHAR(255) NOT NULL DEFAULT "", title VARCHAR(255) NOT NULL, description TEXT NULL, file_path VARCHAR(255) NOT NULL, extracted_text LONGTEXT NULL, uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_uploaded (uploaded_at), INDEX idx_member_uploaded (member_id, uploaded_at), INDEX idx_category (category), INDEX idx_subcategory (subcategory), INDEX idx_category_subcategory (category, subcategory), INDEX idx_tags (tags))');
        $ready = table_exists('member_library_documents');
        if ($ready) {
            if (!table_has_column('member_library_documents', 'category')) {
                db()->exec('ALTER TABLE member_library_documents ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT "general" AFTER member_id');
            }
            if (!table_has_column('member_library_documents', 'subcategory')) {
                db()->exec('ALTER TABLE member_library_documents ADD COLUMN subcategory VARCHAR(120) NOT NULL DEFAULT "" AFTER category');
            }
            if (!table_has_column('member_library_documents', 'tags')) {
                db()->exec('ALTER TABLE member_library_documents ADD COLUMN tags VARCHAR(255) NOT NULL DEFAULT "" AFTER subcategory');
            }
            if (!table_has_column('member_library_documents', 'extracted_text')) {
                db()->exec('ALTER TABLE member_library_documents ADD COLUMN extracted_text LONGTEXT NULL AFTER file_path');
            }
            if (!table_has_index('member_library_documents', 'idx_category')) {
                db()->exec('ALTER TABLE member_library_documents ADD INDEX idx_category (category)');
            }
            if (!table_has_index('member_library_documents', 'idx_subcategory')) {
                db()->exec('ALTER TABLE member_library_documents ADD INDEX idx_subcategory (subcategory)');
            }
            if (!table_has_index('member_library_documents', 'idx_category_subcategory')) {
                db()->exec('ALTER TABLE member_library_documents ADD INDEX idx_category_subcategory (category, subcategory)');
            }
            if (!table_has_index('member_library_documents', 'idx_tags')) {
                db()->exec('ALTER TABLE member_library_documents ADD INDEX idx_tags (tags)');
            }
        }
    } catch (Throwable) {
        $ready = false;
    }

    return $ready;
}
}


if (!function_exists('library_controlled_vocabulary_list')) {
function library_controlled_vocabulary_list(): array
{
    return [
        'formation',
        'securite',
        'legal',
        'reglement',
        'technique',
        'antenne',
        'propagation',
        'traffic',
        'numerique',
        'materiel',
        'maintenance',
        'procedure',
        'club',
    ];
}
}

if (!function_exists('member_library_category_slug')) {
function member_library_category_slug(string $value): string
{
    return content_proposal_category_code($value, 120, 'general');
}
}

if (!function_exists('member_library_subcategory_slug')) {
function member_library_subcategory_slug(string $value): string
{
    $slug = slugify($value);
    if ($slug === '' || $slug === 'n-a') {
        return '';
    }
    if (strlen($slug) > 120) {
        $slug = rtrim(substr($slug, 0, 120), '-');
    }

    return $slug;
}
}

if (!function_exists('member_library_subcategory_ref')) {
function member_library_subcategory_ref(string $categoryCode, string $subcategoryCode): string
{
    $categoryCode = member_library_category_slug($categoryCode !== '' ? $categoryCode : 'general');
    $subcategoryCode = member_library_subcategory_slug($subcategoryCode);

    return $subcategoryCode !== '' ? ($categoryCode . ':' . $subcategoryCode) : '';
}
}

if (!function_exists('member_library_subcategory_ref_parts')) {
/**
 * @return array{category:string,subcategory:string}
 */
function member_library_subcategory_ref_parts(string $value): array
{
    $value = trim($value);
    if ($value === '') {
        return ['category' => '', 'subcategory' => ''];
    }

    $parts = explode(':', $value, 2);
    if (count($parts) === 2) {
        return [
            'category' => member_library_category_slug($parts[0] !== '' ? $parts[0] : 'general'),
            'subcategory' => member_library_subcategory_slug($parts[1]),
        ];
    }

    return [
        'category' => '',
        'subcategory' => member_library_subcategory_slug($value),
    ];
}
}

if (!function_exists('member_library_i18n_text')) {
function member_library_i18n_text(string $key): string
{
    static $cache = [];

    $locale = function_exists('current_locale') ? current_locale() : null;
    $cacheKey = $locale ?? '__default__';
    if (!array_key_exists($cacheKey, $cache)) {
        $cache[$cacheKey] = function_exists('i18n_domain_locale') ? i18n_domain_locale('members_library', $locale) : [];
    }

    return (string) ($cache[$cacheKey][$key] ?? $key);
}
}

if (!function_exists('member_library_taxonomy_from_input')) {
/**
 * @return array{category:string,subcategory:string}
 */
function member_library_taxonomy_from_input(string $categoryInput, string $subcategoryRef, string $fallbackCategory = 'general'): array
{
    $category = member_library_category_slug($categoryInput !== '' ? $categoryInput : $fallbackCategory);
    if ($category === '') {
        $category = 'general';
    }
    $subcategoryRef = trim($subcategoryRef);
    if ($subcategoryRef === '') {
        return [$category, ''];
    }

    $parts = member_library_subcategory_ref_parts($subcategoryRef);
    if ($parts['subcategory'] === '') {
        return [$category, ''];
    }
    if ($parts['category'] !== '' && $parts['category'] !== $category) {
        throw new RuntimeException(member_library_i18n_text('err_subcategory_category_mismatch'));
    }

    foreach ((array) (member_library_subcategories_by_category()[$category] ?? []) as $knownSubcategory) {
        if (member_library_subcategory_slug((string) ($knownSubcategory['code'] ?? '')) === $parts['subcategory']) {
            return [$category, $parts['subcategory']];
        }
    }

    throw new RuntimeException(member_library_i18n_text('err_subcategory_category_mismatch'));
}
}

if (!function_exists('member_library_document_upload_extensions')) {
/**
 * @return list<string>
 */
function member_library_document_upload_extensions(): array
{
    return ['pdf', 'doc', 'docx', 'txt', 'md', 'html', 'htm'];
}
}

if (!function_exists('member_library_document_upload_mimes')) {
/**
 * @return array<string,list<string>>
 */
function member_library_document_upload_mimes(): array
{
    return [
        'pdf' => ['application/pdf', 'application/x-pdf', 'application/acrobat', 'application/vnd.pdf', 'text/pdf', 'text/x-pdf', 'application/octet-stream'],
        'doc' => ['application/msword', 'application/vnd.ms-word', 'application/x-msword', 'application/vnd.ms-office', 'application/cdfv2', 'application/x-cfb', 'application/x-ole-storage', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'txt' => ['text/plain', 'application/octet-stream'],
        'md' => ['text/plain', 'text/markdown', 'text/x-markdown', 'application/octet-stream'],
        'html' => ['text/html', 'text/plain', 'application/xhtml+xml', 'application/octet-stream'],
        'htm' => ['text/html', 'text/plain', 'application/xhtml+xml', 'application/octet-stream'],
    ];
}
}

if (!function_exists('member_library_upload_max_bytes')) {
function member_library_upload_max_bytes(): int
{
    return 100 * 1024 * 1024;
}
}

if (!function_exists('member_library_store_document_upload')) {
/**
 * @return array{public_path:string,absolute_path:string,extension:string,original_name:string}
 */
function member_library_store_document_upload(?array $file, int $memberId, string $prefix = 'doc'): array
{
    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException(upload_i18n_message('upload_failed'));
    }

    $allowedExtensions = member_library_document_upload_extensions();
    $allowedMimes = member_library_document_upload_mimes();

    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException(upload_i18n_message('extension_not_allowed'));
    }

    $targetDir = dirname(__DIR__) . '/storage/private/library';
    $base = slugify(pathinfo($originalName, PATHINFO_FILENAME));
    if ($base === '') {
        $base = 'document';
    }
    if (strlen($base) > 80) {
        $base = substr($base, 0, 80);
    }
    $prefix = trim((string) preg_replace('/[^a-z0-9_-]+/i', '_', $prefix), '_');
    if ($prefix === '') {
        $prefix = 'doc';
    }

    $filename = secure_move_uploaded_file(
        $file,
        $targetDir,
        $prefix . '_' . $memberId . '-' . $base,
        $allowedExtensions,
        $allowedMimes,
        member_library_upload_max_bytes()
    );

    return [
        'public_path' => 'storage/private/library/' . $filename,
        'absolute_path' => $targetDir . '/' . $filename,
        'extension' => $extension,
        'original_name' => $originalName,
    ];
}
}

if (!function_exists('member_library_store_proposed_document_upload')) {
/**
 * @return array{public_path:string,absolute_path:string,extension:string,original_name:string}
 */
function member_library_store_proposed_document_upload(?array $file, int $memberId): array
{
    return member_library_store_document_upload($file, $memberId, 'proposal_doc');
}
}

if (!function_exists('member_library_delete_document_file')) {
function member_library_delete_document_file(string $publicPath): void
{
    if (!function_exists('safe_storage_public_path_or_null')) {
        return;
    }

    $safePath = safe_storage_document_path_or_null($publicPath, ['storage/private/library/', 'storage/uploads/library/']);
    if ($safePath === null) {
        return;
    }

    $absolute = storage_document_absolute_path($safePath);
    if (is_file($absolute)) {
        @unlink($absolute);
    }
}
}

if (!function_exists('member_library_extract_text')) {
function member_library_extract_text(string $path, string $extension): string
{
    $extension = strtolower($extension);
    if ($extension === 'pdf' && function_exists('article_extract_pdf_text')) {
        return article_extract_pdf_text($path);
    }
    if ($extension === 'docx' && function_exists('article_extract_docx_html')) {
        return trim(strip_tags(article_extract_docx_html($path)));
    }
    if (!is_file($path)) {
        return '';
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw)) {
        return '';
    }
    if (in_array($extension, ['html', 'htm'], true)) {
        $raw = strip_tags($raw);
    }

    return trim((string) preg_replace('/\s+/u', ' ', $raw));
}
}

if (!function_exists('member_library_split_tags')) {
/**
 * @return list<string>
 */
function member_library_split_tags(string $value): array
{
    $tags = [];
    foreach (preg_split('/[,;#]+/u', $value) ?: [] as $part) {
        $tag = content_proposal_clean_single_line((string) $part, 80);
        if ($tag !== '') {
            $tags[] = $tag;
        }
    }

    return $tags;
}
}

if (!function_exists('member_library_clean_tags')) {
function member_library_clean_tags(string $value): string
{
    $seen = [];
    $tags = [];
    foreach (library_filter_controlled_tags(member_library_split_tags($value)) as $tag) {
        $key = mb_strtolower(trim((string) $tag), 'UTF-8');
        if ($key === '' || isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $tags[] = trim((string) $tag);
    }

    return mb_safe_substr(implode(',', $tags), 0, 255);
}
}

if (!function_exists('member_library_proposal_labels')) {
/**
 * @return list<string>
 */
function member_library_proposal_labels(array $messages, string $field): array
{
    $labels = match ($field) {
        'category' => ['Category', 'Catégorie', 'Categorie', 'Topic', 'Theme', 'Thématique', 'Thematique'],
        'subcategory' => ['Subcategory', 'Sub-topic', 'Sub topic', 'Sous-thématique', 'Sous-thematique', 'Sous thématique', 'Sous thematique', 'Sous-thème', 'Sous-theme'],
        'tags' => ['Tags', 'Keywords', 'Mots clés', 'Mots cles'],
        'description' => ['Description'],
        default => [],
    };
    $messageKey = match ($field) {
        'category' => 'propose_document_category',
        'subcategory' => 'propose_document_subcategory',
        'tags' => 'tags',
        'description' => 'propose_document_description',
        default => '',
    };
    if ($messageKey !== '' && isset($messages[$messageKey])) {
        array_unshift($labels, (string) $messages[$messageKey]);
    }

    return array_values(array_unique(array_filter($labels, static fn(string $label): bool => trim($label) !== '')));
}
}

if (!function_exists('member_library_proposal_value_at')) {
function member_library_proposal_value_at(string $summary, int $index): string
{
    $rows = content_proposal_summary_rows($summary);

    return trim((string) ($rows[$index]['value'] ?? ''));
}
}

if (!function_exists('member_library_proposal_category_from_summary')) {
function member_library_proposal_category_from_summary(string $summary, array $messages = []): string
{
    $category = content_proposal_detail_from_summary($summary, member_library_proposal_labels($messages, 'category'));
    if ($category === '') {
        $category = member_library_proposal_value_at($summary, 0);
    }

    return member_library_category_slug($category !== '' ? $category : 'general');
}
}

if (!function_exists('member_library_proposal_subcategory_from_summary')) {
function member_library_proposal_subcategory_from_summary(string $summary, array $messages = []): string
{
    return member_library_subcategory_slug(content_proposal_detail_from_summary($summary, member_library_proposal_labels($messages, 'subcategory')));
}
}

if (!function_exists('member_library_proposal_tags_from_summary')) {
function member_library_proposal_tags_from_summary(string $summary, array $messages = []): string
{
    return member_library_clean_tags(content_proposal_detail_from_summary($summary, member_library_proposal_labels($messages, 'tags')));
}
}

if (!function_exists('member_library_proposal_description_from_summary')) {
function member_library_proposal_description_from_summary(string $summary, array $messages = []): string
{
    return content_proposal_clean_multiline(
        content_proposal_detail_from_summary($summary, member_library_proposal_labels($messages, 'description')),
        5000
    );
}
}

if (!function_exists('member_library_proposal_source_path')) {
function member_library_proposal_source_path(string $sourceRef): string
{
    $sourceRef = rawurldecode(trim($sourceRef));
    if ($sourceRef === '') {
        return '';
    }
    if (function_exists('safe_storage_document_path_or_null')) {
        $safePath = safe_storage_document_path_or_null($sourceRef, ['storage/private/library/', 'storage/uploads/library/']);
        if ($safePath !== null) {
            return $safePath;
        }
    }
    if (preg_match('~(storage/(?:private|uploads)/library/[^\s?#]+)~i', str_replace('\\', '/', $sourceRef), $matches) === 1) {
        $candidate = ltrim((string) $matches[1], '/');
        if (function_exists('safe_storage_document_path_or_null')) {
            return safe_storage_document_path_or_null($candidate, ['storage/private/library/', 'storage/uploads/library/']) ?? '';
        }
        if (!str_contains($candidate, '..') && (
            str_starts_with($candidate, 'storage/private/library/')
            || str_starts_with($candidate, 'storage/uploads/library/')
        )) {
            return $candidate;
        }
    }

    return '';
}
}

if (!function_exists('member_library_document_proposal_action')) {
function member_library_document_proposal_action(string $summary): string
{
    $action = content_proposal_clean_single_line(
        content_proposal_detail_from_summary($summary, ['Action']),
        32
    );

    return in_array($action, ['update_document', 'delete_document'], true) ? $action : '';
}
}

if (!function_exists('member_library_document_proposal_document_id')) {
function member_library_document_proposal_document_id(string $summary): int
{
    return max(0, (int) content_proposal_detail_from_summary($summary, ['Document ID']));
}
}

if (!function_exists('member_library_create_document_record')) {
function member_library_create_document_record(
    int $memberId,
    string $title,
    string $category,
    string $tags,
    string $description,
    string $publicPath,
    string $subcategory = ''
): int {
    if (!ensure_member_library_table()) {
        throw new RuntimeException('storage_unavailable');
    }

    $title = content_proposal_clean_single_line($title, 190);
    if ($title === '') {
        throw new RuntimeException('err_required');
    }

    $sourcePath = member_library_proposal_source_path($publicPath);
    if ($sourcePath === '') {
        throw new RuntimeException('err_invalid');
    }
    $absolutePath = storage_document_absolute_path($sourcePath);
    if (!is_file($absolutePath)) {
        throw new RuntimeException('err_invalid');
    }

    $existingStmt = db()->prepare('SELECT id FROM member_library_documents WHERE file_path = ? LIMIT 1');
    $existingStmt->execute([$sourcePath]);
    $existingId = (int) ($existingStmt->fetchColumn() ?: 0);
    if ($existingId > 0) {
        return $existingId;
    }

    $category = member_library_category_slug($category !== '' ? $category : 'general');
    $subcategory = member_library_subcategory_slug($subcategory);
    $tags = member_library_clean_tags($tags);
    $description = content_proposal_clean_multiline($description, 5000);
    $extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
    $extractedText = member_library_extract_text($absolutePath, $extension);

    db()->prepare('INSERT INTO member_library_documents (member_id, category, subcategory, tags, title, description, file_path, extracted_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            max(0, $memberId),
            $category,
            $subcategory,
            $tags,
            $title,
            $description !== '' ? $description : null,
            $sourcePath,
            $extractedText !== '' ? $extractedText : null,
        ]);

    return (int) db()->lastInsertId();
}
}

if (!function_exists('member_library_update_document_record')) {
function member_library_update_document_record(
    int $documentId,
    string $title,
    string $category,
    string $tags,
    string $description,
    string $replacementPublicPath = '',
    string $subcategory = ''
): void {
    if (!ensure_member_library_table()) {
        throw new RuntimeException('storage_unavailable');
    }

    $documentId = max(0, $documentId);
    $title = content_proposal_clean_single_line($title, 190);
    if ($documentId <= 0 || $title === '') {
        throw new RuntimeException('err_required');
    }

    $stmt = db()->prepare('SELECT file_path FROM member_library_documents WHERE id = ? LIMIT 1');
    $stmt->execute([$documentId]);
    $currentPath = (string) ($stmt->fetchColumn() ?: '');
    if ($currentPath === '') {
        throw new RuntimeException('err_invalid');
    }

    $category = member_library_category_slug($category !== '' ? $category : 'general');
    $subcategory = member_library_subcategory_slug($subcategory);
    $tags = member_library_clean_tags($tags);
    $description = content_proposal_clean_multiline($description, 5000);
    $replacementPublicPath = member_library_proposal_source_path($replacementPublicPath);

    if ($replacementPublicPath !== '') {
        $absolutePath = storage_document_absolute_path($replacementPublicPath);
        if (!is_file($absolutePath)) {
            throw new RuntimeException('err_invalid');
        }
        $extension = strtolower((string) pathinfo($replacementPublicPath, PATHINFO_EXTENSION));
        $extractedText = member_library_extract_text($absolutePath, $extension);
        db()->prepare('UPDATE member_library_documents SET category = ?, subcategory = ?, tags = ?, title = ?, description = ?, file_path = ?, extracted_text = ? WHERE id = ?')
            ->execute([
                $category,
                $subcategory,
                $tags,
                $title,
                $description !== '' ? $description : null,
                $replacementPublicPath,
                $extractedText !== '' ? $extractedText : null,
                $documentId,
            ]);
        if ($currentPath !== $replacementPublicPath) {
            member_library_delete_document_file($currentPath);
        }
    } else {
        db()->prepare('UPDATE member_library_documents SET category = ?, subcategory = ?, tags = ?, title = ?, description = ? WHERE id = ?')
            ->execute([$category, $subcategory, $tags, $title, $description !== '' ? $description : null, $documentId]);
    }

    if (table_exists('member_favorites')) {
        $favoriteUrl = route_url_clean('members_library', ['q' => $title, 'category' => $category, 'subcategory' => $subcategory, 'tag' => $tags]);
        db()->prepare('UPDATE member_favorites SET title = ?, url = ? WHERE target_type = ? AND target_id = ?')
            ->execute([$title, $favoriteUrl, 'library_document', $documentId]);
    }
}
}

if (!function_exists('member_library_delete_document_record')) {
function member_library_delete_document_record(int $documentId): void
{
    if (!ensure_member_library_table()) {
        throw new RuntimeException('storage_unavailable');
    }

    $documentId = max(0, $documentId);
    if ($documentId <= 0) {
        throw new RuntimeException('err_required');
    }

    $stmt = db()->prepare('SELECT file_path FROM member_library_documents WHERE id = ? LIMIT 1');
    $stmt->execute([$documentId]);
    $path = (string) ($stmt->fetchColumn() ?: '');
    if ($path === '') {
        throw new RuntimeException('err_invalid');
    }

    member_library_delete_document_file($path);
    db()->prepare('DELETE FROM member_library_documents WHERE id = ? LIMIT 1')->execute([$documentId]);
    if (table_exists('member_favorites')) {
        db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id = ?')->execute(['library_document', $documentId]);
    }
}
}

if (!function_exists('member_library_apply_accepted_proposal')) {
function member_library_apply_accepted_proposal(array $proposal, array $messages = []): ?int
{
    $proposalType = (string) ($proposal['proposal_type'] ?? '');

    if ($proposalType === 'category') {
        if (!member_library_ensure_categories_table()) {
            throw new RuntimeException('storage_unavailable');
        }
        $label = content_proposal_clean_single_line((string) ($proposal['title'] ?? ''), 160);
        if ($label === '') {
            throw new RuntimeException('err_required');
        }
        $code = member_library_category_slug($label);
        db()->prepare('INSERT INTO member_library_categories (code, label) VALUES (?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
            ->execute([$code, $label]);

        return null;
    }

    if ($proposalType === 'subcategory') {
        if (!member_library_ensure_subcategories_table()) {
            throw new RuntimeException('storage_unavailable');
        }
        $label = content_proposal_clean_single_line((string) ($proposal['title'] ?? ''), 160);
        $code = member_library_subcategory_slug($label);
        $parentCategory = member_library_proposal_category_from_summary((string) ($proposal['summary'] ?? ''), $messages);
        if ($parentCategory === '') {
            $parentCategory = 'general';
        }
        if ($label === '' || $code === '') {
            throw new RuntimeException('err_required');
        }
        db()->prepare('INSERT INTO member_library_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
            ->execute([$parentCategory, $code, $label]);

        return null;
    }

    if ($proposalType !== 'content') {
        return null;
    }

    if (!ensure_member_library_table()) {
        throw new RuntimeException('storage_unavailable');
    }

    $summary = (string) ($proposal['summary'] ?? '');
    $documentAction = member_library_document_proposal_action($summary);
    if ($documentAction !== '') {
        $documentId = member_library_document_proposal_document_id($summary);
        if ($documentAction === 'delete_document') {
            member_library_delete_document_record($documentId);

            return $documentId;
        }

        $title = content_proposal_clean_single_line((string) ($proposal['title'] ?? ''), 190);
        if ($title === '') {
            throw new RuntimeException('err_required');
        }
        member_library_update_document_record(
            $documentId,
            $title,
            member_library_proposal_category_from_summary($summary, $messages),
            member_library_proposal_tags_from_summary($summary, $messages),
            member_library_proposal_description_from_summary($summary, $messages),
            member_library_proposal_source_path((string) ($proposal['source_ref'] ?? '')),
            member_library_proposal_subcategory_from_summary($summary, $messages)
        );

        return $documentId;
    }

    return member_library_create_document_record(
        max(0, (int) ($proposal['member_id'] ?? 0)),
        (string) ($proposal['title'] ?? ''),
        member_library_proposal_category_from_summary($summary, $messages),
        member_library_proposal_tags_from_summary($summary, $messages),
        member_library_proposal_description_from_summary($summary, $messages),
        (string) ($proposal['source_ref'] ?? ''),
        member_library_proposal_subcategory_from_summary($summary, $messages)
    );
}
}

if (!function_exists('member_library_sync_accepted_proposals')) {
/**
 * @param array<string, string> $messages
 * @return array{checked:int,applied:int,skipped:int,failed:int}
 */
function member_library_sync_accepted_proposals(array $messages = [], int $limit = 100): array
{
    static $alreadyRan = false;

    $result = ['checked' => 0, 'applied' => 0, 'skipped' => 0, 'failed' => 0];
    if ($alreadyRan) {
        return $result;
    }
    $alreadyRan = true;

    if (!ensure_content_proposals_table() || !ensure_member_library_table()) {
        return $result;
    }

    require_once __DIR__ . '/article_import_helpers.php';

    $limit = max(1, min(500, $limit));
    try {
        $stmt = db()->prepare(
            'SELECT id, member_id, proposal_type, title, summary, source_ref
             FROM content_proposals
             WHERE area = "members_library"
               AND status = "accepted"
               AND proposal_type IN ("category", "subcategory", "content")
             ORDER BY updated_at ASC, id ASC
             LIMIT ' . $limit
        );
        $stmt->execute();
        $proposals = $stmt->fetchAll() ?: [];
    } catch (Throwable $throwable) {
        if (function_exists('log_structured_event')) {
            log_structured_event('member_library_accepted_proposals_sync_load_failed', [
                'message' => $throwable->getMessage(),
            ]);
        }

        return $result;
    }

    foreach ($proposals as $proposal) {
        $result['checked']++;
        try {
            if ((string) ($proposal['proposal_type'] ?? '') === 'content') {
                if (member_library_document_proposal_action((string) ($proposal['summary'] ?? '')) !== '') {
                    $result['skipped']++;
                    continue;
                }
                $sourcePath = member_library_proposal_source_path((string) ($proposal['source_ref'] ?? ''));
                if ($sourcePath === '') {
                    throw new RuntimeException('err_invalid');
                }

                $existingStmt = db()->prepare('SELECT id FROM member_library_documents WHERE file_path = ? LIMIT 1');
                $existingStmt->execute([$sourcePath]);
                if ((int) ($existingStmt->fetchColumn() ?: 0) > 0) {
                    $result['skipped']++;
                    continue;
                }
            }

            member_library_apply_accepted_proposal($proposal, $messages);
            $result['applied']++;
        } catch (Throwable $throwable) {
            $result['failed']++;
            if (function_exists('log_structured_event')) {
                log_structured_event('member_library_accepted_proposal_sync_failed', [
                    'proposal_id' => (int) ($proposal['id'] ?? 0),
                    'message' => $throwable->getMessage(),
                ]);
            }
        }
    }

    return $result;
}
}

if (!function_exists('member_library_default_subcategories')) {
function member_library_default_subcategories(): array
{
    return [
        ['category_code' => 'general', 'code' => 'references', 'label' => member_library_i18n_text('subcategory_references'), 'sort_order' => 10],
        ['category_code' => 'general', 'code' => 'club', 'label' => member_library_i18n_text('subcategory_club'), 'sort_order' => 20],
        ['category_code' => 'formation', 'code' => 'cours', 'label' => member_library_i18n_text('subcategory_cours'), 'sort_order' => 10],
        ['category_code' => 'formation', 'code' => 'examens', 'label' => member_library_i18n_text('subcategory_examens'), 'sort_order' => 20],
        ['category_code' => 'technique', 'code' => 'montages', 'label' => member_library_i18n_text('subcategory_montages'), 'sort_order' => 10],
        ['category_code' => 'technique', 'code' => 'mesures', 'label' => member_library_i18n_text('subcategory_mesures'), 'sort_order' => 20],
        ['category_code' => 'antennes', 'code' => 'construction', 'label' => member_library_i18n_text('subcategory_construction'), 'sort_order' => 10],
        ['category_code' => 'antennes', 'code' => 'reglages', 'label' => member_library_i18n_text('subcategory_reglages'), 'sort_order' => 20],
        ['category_code' => 'propagation', 'code' => 'bulletins', 'label' => member_library_i18n_text('subcategory_bulletins'), 'sort_order' => 10],
        ['category_code' => 'propagation', 'code' => 'previsions', 'label' => member_library_i18n_text('subcategory_previsions'), 'sort_order' => 20],
        ['category_code' => 'modes-numeriques', 'code' => 'logiciels', 'label' => member_library_i18n_text('subcategory_logiciels'), 'sort_order' => 10],
        ['category_code' => 'modes-numeriques', 'code' => 'protocoles', 'label' => member_library_i18n_text('subcategory_protocoles'), 'sort_order' => 20],
        ['category_code' => 'reglementation', 'code' => 'licences', 'label' => member_library_i18n_text('subcategory_licences'), 'sort_order' => 10],
        ['category_code' => 'reglementation', 'code' => 'procedures', 'label' => member_library_i18n_text('subcategory_procedures'), 'sort_order' => 20],
        ['category_code' => 'procedures', 'code' => 'station', 'label' => member_library_i18n_text('subcategory_station'), 'sort_order' => 10],
        ['category_code' => 'procedures', 'code' => 'securite', 'label' => member_library_i18n_text('subcategory_securite'), 'sort_order' => 20],
        ['category_code' => 'club', 'code' => 'reunions', 'label' => member_library_i18n_text('subcategory_reunions'), 'sort_order' => 10],
        ['category_code' => 'club', 'code' => 'archives', 'label' => member_library_i18n_text('subcategory_archives'), 'sort_order' => 20],
    ];
}
}

if (!function_exists('member_library_default_categories')) {
function member_library_default_categories(): array
{
    return [
        ['code' => 'general', 'label' => member_library_i18n_text('category_general'), 'sort_order' => 1],
        ['code' => 'formation', 'label' => member_library_i18n_text('category_formation'), 'sort_order' => 10],
        ['code' => 'technique', 'label' => member_library_i18n_text('category_technique'), 'sort_order' => 20],
        ['code' => 'antennes', 'label' => member_library_i18n_text('category_antennes'), 'sort_order' => 30],
        ['code' => 'propagation', 'label' => member_library_i18n_text('category_propagation'), 'sort_order' => 40],
        ['code' => 'modes-numeriques', 'label' => member_library_i18n_text('category_modes_numeriques'), 'sort_order' => 50],
        ['code' => 'reglementation', 'label' => member_library_i18n_text('category_reglementation'), 'sort_order' => 60],
        ['code' => 'procedures', 'label' => member_library_i18n_text('category_procedures'), 'sort_order' => 70],
        ['code' => 'club', 'label' => member_library_i18n_text('category_club'), 'sort_order' => 80],
    ];
}
}

if (!function_exists('member_library_ensure_categories_table')) {
function member_library_ensure_categories_table(): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_library_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(120) NOT NULL UNIQUE,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
        $categoryInsert = db()->prepare('INSERT IGNORE INTO member_library_categories (code, label, sort_order) VALUES (?, ?, ?)');
        foreach (member_library_default_categories() as $category) {
            $categoryInsert->execute([
                (string) $category['code'],
                (string) $category['label'],
                (int) $category['sort_order'],
            ]);
        }
        try {
            db()->exec("UPDATE member_library_documents SET category = 'videos' WHERE category = 'medias'");
        } catch (Throwable) {
        }
        try {
            $legacyCategoryStmt = db()->query("SELECT COUNT(*) FROM member_library_categories WHERE code = 'medias'");
            if ($legacyCategoryStmt !== false && (int) $legacyCategoryStmt->fetchColumn() > 0) {
                $videosCategoryStmt = db()->query("SELECT COUNT(*) FROM member_library_categories WHERE code = 'videos'");
                if ($videosCategoryStmt !== false && (int) $videosCategoryStmt->fetchColumn() > 0) {
                    db()->exec("DELETE FROM member_library_categories WHERE code = 'medias'");
                } else {
                    db()->exec("UPDATE member_library_categories SET code = 'videos', label = 'Videos', sort_order = 20 WHERE code = 'medias'");
                }
            }
        } catch (Throwable) {
        }

        return table_exists('member_library_categories');
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('member_library_ensure_subcategories_table')) {
function member_library_ensure_subcategories_table(): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_library_subcategories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_code VARCHAR(120) NOT NULL,
            code VARCHAR(120) NOT NULL,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_member_library_subcategory (category_code, code),
            INDEX idx_member_library_subcategory_category (category_code)
        )');
        $subcategoryInsert = db()->prepare('INSERT IGNORE INTO member_library_subcategories (category_code, code, label, sort_order) VALUES (?, ?, ?, ?)');
        foreach (member_library_default_subcategories() as $subcategory) {
            $categoryCode = member_library_category_slug((string) ($subcategory['category_code'] ?? 'general'));
            $code = member_library_subcategory_slug((string) ($subcategory['code'] ?? ''));
            $label = content_proposal_clean_single_line((string) ($subcategory['label'] ?? $code), 160);
            if ($categoryCode === '' || $code === '' || $label === '') {
                continue;
            }
            $subcategoryInsert->execute([
                $categoryCode,
                $code,
                $label,
                (int) ($subcategory['sort_order'] ?? 100),
            ]);
        }

        return table_exists('member_library_subcategories');
    } catch (Throwable) {
        return false;
    }
}
}

if (!function_exists('member_library_subcategory_options')) {
/**
 * @return list<array{category_code:string,code:string,label:string}>
 */
function member_library_subcategory_options(): array
{
    if (!member_library_ensure_subcategories_table()) {
        return array_map(
            static fn(array $subcategory): array => [
                'category_code' => (string) ($subcategory['category_code'] ?? 'general'),
                'code' => (string) ($subcategory['code'] ?? ''),
                'label' => (string) ($subcategory['label'] ?? ''),
            ],
            member_library_default_subcategories()
        );
    }

    try {
        $rows = db()->query('SELECT category_code, code, label FROM member_library_subcategories ORDER BY category_code ASC, sort_order ASC, label ASC')->fetchAll() ?: [];
    } catch (Throwable) {
        $rows = [];
    }

    $options = [];
    foreach ($rows as $row) {
        $categoryCode = member_library_category_slug((string) ($row['category_code'] ?? 'general'));
        $code = member_library_subcategory_slug((string) ($row['code'] ?? ''));
        $label = content_proposal_clean_single_line((string) ($row['label'] ?? $code), 160);
        if ($categoryCode === '' || $code === '' || $label === '') {
            continue;
        }
        $options[] = [
            'category_code' => $categoryCode,
            'code' => $code,
            'label' => $label,
        ];
    }

    return $options;
}
}

if (!function_exists('member_library_subcategories_by_category')) {
/**
 * @return array<string, list<array{category_code:string,code:string,label:string}>>
 */
function member_library_subcategories_by_category(): array
{
    $byCategory = [];
    foreach (member_library_subcategory_options() as $subcategory) {
        $byCategory[$subcategory['category_code']][] = $subcategory;
    }

    return $byCategory;
}
}

if (!function_exists('member_library_visible_categories')) {
/**
 * @param list<array<string, mixed>> $categories
 * @return list<array<string, mixed>>
 */
function member_library_visible_categories(array $categories): array
{
    $visible = [];
    foreach ($categories as $category) {
        if ((int) ($category['total'] ?? 0) <= 0) {
            continue;
        }
        $visible[] = $category;
    }

    return $visible;
}
}

if (!function_exists('member_library_visible_subcategories_by_category')) {
/**
 * @param array<string, list<array<string, mixed>>> $subcategoriesByCategory
 * @return array<string, list<array<string, mixed>>>
 */
function member_library_visible_subcategories_by_category(array $subcategoriesByCategory): array
{
    $visible = [];
    foreach ($subcategoriesByCategory as $categoryCode => $subcategories) {
        foreach ($subcategories as $subcategory) {
            if ((int) ($subcategory['total'] ?? 0) <= 0) {
                continue;
            }
            $visible[(string) $categoryCode][] = $subcategory;
        }
    }

    return $visible;
}
}

if (!function_exists('member_library_favorites_label')) {
/**
 * @param array<string, mixed> $messages
 */
function member_library_favorites_label(array $messages, string $locale = ''): string
{
    $label = trim((string) ($messages['favorites'] ?? ''));
    if ($label !== '') {
        return $label;
    }
    if ($locale === 'fr') {
        return 'Favoris';
    }
    if ($locale === 'en') {
        return 'Favorites';
    }

    $favorite = trim((string) ($messages['favorite'] ?? ''));
    return $favorite !== '' ? $favorite : 'Favorites';
}
}

if (!function_exists('member_library_favorite_document_ids')) {
/**
 * @return list<int>
 */
function member_library_favorite_document_ids(int $memberId): array
{
    if (
        $memberId <= 0
        || !function_exists('ensure_member_favorites_table')
        || !ensure_member_favorites_table()
        || !ensure_member_library_table()
    ) {
        return [];
    }

    try {
        $stmt = db()->prepare('SELECT d.id FROM member_favorites f INNER JOIN member_library_documents d ON d.id = f.target_id WHERE f.member_id = ? AND f.target_type = ? ORDER BY f.created_at DESC, f.id DESC');
        $stmt->execute([$memberId, 'library_document']);
        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    } catch (Throwable) {
        return [];
    }
}
}

if (!function_exists('library_ingestion_templates_map')) {
function library_ingestion_templates_map(): array
{
    return [
        'training' => ['category' => 'formation', 'tags' => ['formation', 'procedure', 'club']],
        'safety' => ['category' => 'general', 'tags' => ['securite', 'procedure', 'reglement']],
        'technical' => ['category' => 'general', 'tags' => ['technique', 'antenne', 'propagation', 'materiel']],
        'legal' => ['category' => 'general', 'tags' => ['legal', 'reglement', 'club']],
    ];
}
}

if (!function_exists('library_filter_controlled_tags')) {
function library_filter_controlled_tags(array $tags): array
{
    $allowed = array_fill_keys(library_controlled_vocabulary_list(), true);
    $out = [];
    foreach ($tags as $tag) {
        $raw = trim((string) $tag);
        if ($raw === '') {
            continue;
        }
        $norm = mb_strtolower($raw, 'UTF-8');
        $norm = preg_replace('/\s+/u', ' ', $norm) ?? $norm;
        $norm = trim($norm);
        if ($norm === '' || !isset($allowed[$norm])) {
            continue;
        }
        $out[] = $raw;
    }
    return $out;
}
}

if (!function_exists('editorial_blocked_reasons_from_article')) {
/**
 * @param array<string,mixed> $article
 * @return list<string>
 */
function editorial_blocked_reasons_from_article(array $article): array
{
    $reasons = [];
    $title = trim((string) ($article['title'] ?? ''));
    $content = trim(strip_tags((string) ($article['content'] ?? '')));
    $status = (string) ($article['status'] ?? 'draft');
    $scheduledAt = trim((string) ($article['scheduled_at'] ?? ''));

    if ($title === '') {
        $reasons[] = 'missing_title';
    }
    if ($content === '') {
        $reasons[] = 'missing_content';
    }
    if ($status === 'scheduled') {
        if ($scheduledAt === '') {
            $reasons[] = 'missing_schedule_date';
        } else {
            $ts = strtotime($scheduledAt);
            if ($ts === false) {
                $reasons[] = 'invalid_schedule_date';
            } elseif ($ts <= time()) {
                $reasons[] = 'stuck_in_past_schedule';
            }
        }
    }
    return $reasons;
}
}
