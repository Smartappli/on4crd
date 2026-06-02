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
        db()->exec('CREATE TABLE IF NOT EXISTS member_library_documents (id INT AUTO_INCREMENT PRIMARY KEY, member_id INT NOT NULL, category VARCHAR(120) NOT NULL DEFAULT "general", tags VARCHAR(255) NOT NULL DEFAULT "", title VARCHAR(255) NOT NULL, description TEXT NULL, file_path VARCHAR(255) NOT NULL, extracted_text LONGTEXT NULL, uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_uploaded (uploaded_at), INDEX idx_member_uploaded (member_id, uploaded_at), INDEX idx_category (category), INDEX idx_tags (tags))');
        $ready = table_exists('member_library_documents');
        if ($ready) {
            $hasCategory = false;
            try {
                $col = db()->query("SHOW COLUMNS FROM member_library_documents LIKE 'category'");
                $hasCategory = (bool) ($col && $col->fetch());
            } catch (Throwable) {
                $hasCategory = false;
            }
            if (!$hasCategory) {
                db()->exec('ALTER TABLE member_library_documents ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT "general" AFTER member_id');
            }
            $hasTags = false;
            try {
                $tagsCol = db()->query("SHOW COLUMNS FROM member_library_documents LIKE 'tags'");
                $hasTags = (bool) ($tagsCol && $tagsCol->fetch());
            } catch (Throwable) {
                $hasTags = false;
            }
            if (!$hasTags) {
                db()->exec('ALTER TABLE member_library_documents ADD COLUMN tags VARCHAR(255) NOT NULL DEFAULT "" AFTER category');
            }
            try {
                db()->exec('ALTER TABLE member_library_documents ADD INDEX idx_category (category)');
            } catch (Throwable) {
                // Index may already exist.
            }
            try {
                db()->exec('ALTER TABLE member_library_documents ADD INDEX idx_tags (tags)');
            } catch (Throwable) {
                // Index may already exist.
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
