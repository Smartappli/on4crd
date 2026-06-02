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

if (!function_exists('ensure_member_favorites_table')) {
function ensure_member_favorites_table(): bool
{
    if (!table_exists('members')) {
        return false;
    }

    db()->exec('CREATE TABLE IF NOT EXISTS member_favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        target_type VARCHAR(48) NOT NULL,
        target_id INT NOT NULL,
        target_key VARCHAR(190) DEFAULT NULL,
        title VARCHAR(255) NOT NULL DEFAULT "",
        url VARCHAR(255) NOT NULL DEFAULT "",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_member_target (member_id, target_type, target_id),
        KEY idx_member_created (member_id, created_at),
        KEY idx_target (target_type, target_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    return true;
}
}

if (!function_exists('favorite_is_saved')) {
function favorite_is_saved(int $memberId, string $targetType, int $targetId): bool
{
    if ($memberId <= 0 || $targetId <= 0 || !ensure_member_favorites_table()) {
        return false;
    }

    $stmt = db()->prepare('SELECT id FROM member_favorites WHERE member_id = ? AND target_type = ? AND target_id = ? LIMIT 1');
    $stmt->execute([$memberId, $targetType, $targetId]);
    return $stmt->fetchColumn() !== false;
}
}

if (!function_exists('favorite_toggle')) {
function favorite_toggle(int $memberId, string $targetType, int $targetId, string $title = '', string $url = '', ?string $targetKey = null): bool
{
    if ($memberId <= 0 || $targetId <= 0 || !ensure_member_favorites_table()) {
        return false;
    }

    if (favorite_is_saved($memberId, $targetType, $targetId)) {
        $stmt = db()->prepare('DELETE FROM member_favorites WHERE member_id = ? AND target_type = ? AND target_id = ?');
        $stmt->execute([$memberId, $targetType, $targetId]);
        return false;
    }

    $stmt = db()->prepare('INSERT INTO member_favorites (member_id, target_type, target_id, target_key, title, url) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$memberId, $targetType, $targetId, $targetKey, mb_safe_substr(trim($title), 0, 255), mb_safe_substr(trim($url), 0, 255)]);
    return true;
}
}

if (!function_exists('member_favorites_recent')) {
function member_favorites_recent(int $memberId, int $limit = 12): array
{
    if ($memberId <= 0 || !ensure_member_favorites_table()) {
        return [];
    }

    $limit = max(1, min(100, $limit));
    $stmt = db()->prepare('SELECT target_type, target_id, target_key, title, url, created_at FROM member_favorites WHERE member_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
    $stmt->execute([$memberId]);
    return $stmt->fetchAll() ?: [];
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

if (!function_exists('member_personalized_recommendations')) {
function ensure_member_preference_table(): bool
{
    if (!table_exists('members')) {
        return false;
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS member_preferences (
            member_id INT NOT NULL,
            preference_key VARCHAR(80) NOT NULL,
            preference_value VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (member_id, preference_key),
            CONSTRAINT fk_member_preferences_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
        )'
    );

    return table_exists('member_preferences');
}

function member_preference_bool(int $memberId, string $key, bool $default = true): bool
{
    if ($memberId <= 0 || $key === '' || !ensure_member_preference_table()) {
        return $default;
    }

    $stmt = db()->prepare('SELECT preference_value FROM member_preferences WHERE member_id = ? AND preference_key = ? LIMIT 1');
    $stmt->execute([$memberId, $key]);
    $value = $stmt->fetchColumn();
    if ($value === false) {
        return $default;
    }

    return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
}

function set_member_preference_bool(int $memberId, string $key, bool $value): void
{
    if ($memberId <= 0 || $key === '' || !ensure_member_preference_table()) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO member_preferences (member_id, preference_key, preference_value)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$memberId, $key, $value ? '1' : '0']);
}

function member_preference_string(int $memberId, string $key, string $default = ''): string
{
    if ($memberId <= 0 || $key === '' || !ensure_member_preference_table()) {
        return $default;
    }

    $stmt = db()->prepare('SELECT preference_value FROM member_preferences WHERE member_id = ? AND preference_key = ? LIMIT 1');
    $stmt->execute([$memberId, $key]);
    $value = $stmt->fetchColumn();
    if ($value === false) {
        return $default;
    }

    return (string) $value;
}

function set_member_preference_string(int $memberId, string $key, string $value): void
{
    if ($memberId <= 0 || $key === '' || !ensure_member_preference_table()) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO member_preferences (member_id, preference_key, preference_value)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$memberId, $key, mb_safe_substr(trim($value), 0, 255)]);
}

function member_personalized_recommendations(int $memberId, int $limit = 6): array
{
    $limit = max(1, min(24, $limit));
    $signalPrefs = [
        'article' => member_preference_bool($memberId, 'recommendations_signal_article_enabled', true),
        'wiki' => member_preference_bool($memberId, 'recommendations_signal_wiki_enabled', true),
        'classified' => member_preference_bool($memberId, 'recommendations_signal_classified_enabled', true),
        'album' => member_preference_bool($memberId, 'recommendations_signal_album_enabled', true),
        'library' => member_preference_bool($memberId, 'recommendations_signal_library_enabled', true),
    ];
    if (!in_array(true, $signalPrefs, true)) {
        return [];
    }

    $seedTypes = [];
    foreach (member_favorites_recent($memberId, 30) as $favorite) {
        $type = (string) ($favorite['target_type'] ?? '');
        if ($type !== '') {
            $seedTypes[$type] = true;
        }
    }

    $items = [];
    $pushUnique = static function (array $row) use (&$items, $limit): void {
        if (count($items) >= $limit) {
            return;
        }
        $key = (string) ($row['key'] ?? '');
        if ($key === '' || isset($items[$key])) {
            return;
        }
        $items[$key] = $row;
    };

    $wantsArticles = $signalPrefs['article'] && (isset($seedTypes['article']) || $seedTypes === []);
    if ($wantsArticles && table_exists('articles')) {
        $stmt = db()->query('SELECT id, slug, title, updated_at FROM articles WHERE status = "published" ORDER BY updated_at DESC, id DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Article';
            }
            $pushUnique([
                'key' => 'article:' . $id,
                'type' => 'article',
                'title' => $title,
                'url' => route_url('article', ['slug' => (string) ($row['slug'] ?? '')]),
                'meta' => (string) ($row['updated_at'] ?? ''),
                'reason_key' => 'recommendation_reason_article',
            ]);
        }
    }

    $wantsWiki = $signalPrefs['wiki'] && (isset($seedTypes['wiki_page']) || $seedTypes === []);
    if ($wantsWiki && table_exists('wiki_pages')) {
        $stmt = db()->query('SELECT slug, title, updated_at FROM wiki_pages ORDER BY updated_at DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Wiki';
            }
            $pushUnique([
                'key' => 'wiki:' . $slug,
                'type' => 'wiki',
                'title' => $title,
                'url' => route_url('wiki_view', ['slug' => $slug]),
                'meta' => (string) ($row['updated_at'] ?? ''),
                'reason_key' => 'recommendation_reason_wiki',
            ]);
        }
    }

    $wantsClassifieds = $signalPrefs['classified'] && (isset($seedTypes['classified_ad']) || $seedTypes === []);
    if ($wantsClassifieds && table_exists('classified_ads')) {
        $stmt = db()->query('SELECT id, title, created_at FROM classified_ads WHERE status = "active" AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY created_at DESC, id DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Classified';
            }
            $pushUnique([
                'key' => 'classified:' . $id,
                'type' => 'classified',
                'title' => $title,
                'url' => route_url('classifieds', ['q' => $title]),
                'meta' => (string) ($row['created_at'] ?? ''),
                'reason_key' => 'recommendation_reason_classified',
            ]);
        }
    }

    $wantsAlbums = $signalPrefs['album'] && (isset($seedTypes['album']) || $seedTypes === []);
    if ($wantsAlbums && table_exists('albums')) {
        $stmt = db()->query('SELECT id, title, created_at FROM albums WHERE is_public = 1 ORDER BY id DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Album';
            }
            $pushUnique([
                'key' => 'album:' . $id,
                'type' => 'album',
                'title' => $title,
                'url' => route_url('album', ['id' => $id]),
                'meta' => (string) ($row['created_at'] ?? ''),
                'reason_key' => 'recommendation_reason_album',
            ]);
        }
    }

    $wantsLibrary = $signalPrefs['library'] && (isset($seedTypes['library_document']) || $seedTypes === []);
    if ($wantsLibrary && table_exists('member_library_documents')) {
        $stmt = db()->query('SELECT id, title, category, uploaded_at FROM member_library_documents ORDER BY uploaded_at DESC, id DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Library document';
            }
            $pushUnique([
                'key' => 'library:' . $id,
                'type' => 'library',
                'title' => $title,
                'url' => route_url_clean('members_library', ['q' => $title, 'category' => (string) ($row['category'] ?? '')]),
                'meta' => (string) ($row['uploaded_at'] ?? ''),
                'reason_key' => 'recommendation_reason_library',
            ]);
        }
    }

    return array_values($items);
}
}
