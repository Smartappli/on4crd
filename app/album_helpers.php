<?php
declare(strict_types=1);

function notify_album_webhooks(array $album): void
{
    if (!table_exists('webhooks')) {
        return;
    }
    $targets = db()->query('SELECT url FROM webhooks WHERE is_active = 1 AND event IN ("album.updated","album.created","*")')->fetchAll() ?: [];
    if ($targets === []) {
        return;
    }
    $payload = json_encode([
        'event' => 'album.updated',
        'album' => [
            'id' => (int) ($album['id'] ?? 0),
            'title' => (string) ($album['title'] ?? ''),
            'slug' => (string) ($album['slug'] ?? ''),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($payload)) {
        return;
    }
    foreach ($targets as $target) {
        $url = trim((string) ($target['url'] ?? ''));
        if ($url === '') {
            continue;
        }
        try {
            $url = validate_remote_feed_url($url) ?? '';
            if ($url === '' || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
                continue;
            }
        } catch (Throwable $throwable) {
            log_structured_event('album_webhook_url_rejected', [
                'message' => $throwable->getMessage(),
            ]);
            continue;
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 3,
                'ignore_errors' => true,
            ],
        ]);
        @file_get_contents($url, false, $ctx);
    }
}

/**
 * @return array{ok:bool,data:array<string,mixed>,error:string}
 */
function album_graph_api_post(string $path, array $params, string $accessToken): array
{
    $version = trim((string) (getenv('META_GRAPH_VERSION') ?: 'v25.0'));
    if (!preg_match('/^v\d+\.\d+$/', $version)) {
        $version = 'v25.0';
    }
    $params['access_token'] = $accessToken;
    $url = 'https://graph.facebook.com/' . $version . '/' . ltrim($path, '/');
    $body = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $raw = false;
    $status = 0;
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        if ($curl !== false) {
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $raw = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        $statusLine = is_array($http_response_header ?? null) ? ($http_response_header[0] ?? '') : '';
        if (preg_match('/\s(\d{3})\s/', (string) $statusLine, $matches) === 1) {
            $status = (int) $matches[1];
        }
    }
    if (!is_string($raw) || $raw === '') {
        return ['ok' => false, 'data' => [], 'error' => 'Meta API request failed.'];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'data' => [], 'error' => 'Meta API returned invalid JSON.'];
    }
    if ($status < 200 || $status >= 300 || isset($decoded['error'])) {
        $message = is_array($decoded['error'] ?? null)
            ? (string) ($decoded['error']['message'] ?? 'Meta API error.')
            : 'Meta API error.';

        return ['ok' => false, 'data' => $decoded, 'error' => $message];
    }

    return ['ok' => true, 'data' => $decoded, 'error' => ''];
}

/**
 * @return list<array{id:int,title:string,caption:string,url:string}>
 */
function album_public_photo_urls(int $albumId, int $limit = 100): array
{
    if ($albumId <= 0 || !table_exists('album_photos')) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    $stmt = db()->prepare('SELECT id, title, caption, file_path FROM album_photos WHERE album_id = ? ORDER BY sort_order ASC, id ASC LIMIT ' . $limit);
    $stmt->execute([$albumId]);
    $rows = $stmt->fetchAll() ?: [];
    $photos = [];
    foreach ($rows as $row) {
        $path = safe_storage_public_path_or_null((string) ($row['file_path'] ?? ''), ['storage/uploads/albums/']);
        if ($path === null) {
            continue;
        }
        $url = base_url($path);
        if (!str_starts_with(strtolower($url), 'https://')) {
            continue;
        }
        $photos[] = [
            'id' => (int) ($row['id'] ?? 0),
            'title' => trim((string) ($row['title'] ?? '')),
            'caption' => trim((string) ($row['caption'] ?? '')),
            'url' => $url,
        ];
    }

    return $photos;
}

/**
 * @return array{facebook:?string,instagram:?string,skipped:list<string>,errors:list<string>}
 */
function album_social_publish_if_public(int $albumId): array
{
    $result = ['facebook' => null, 'instagram' => null, 'skipped' => [], 'errors' => []];
    if ($albumId <= 0 || !table_exists('albums') || !table_exists('album_photos')) {
        $result['errors'][] = 'Invalid album.';
        return $result;
    }

    album_ensure_source_proposal_column();
    $albumStmt = db()->prepare('SELECT * FROM albums WHERE id = ? LIMIT 1');
    $albumStmt->execute([$albumId]);
    $album = $albumStmt->fetch() ?: null;
    if (!is_array($album) || (int) ($album['is_public'] ?? 0) !== 1) {
        $result['skipped'][] = 'album_not_public';
        return $result;
    }

    $photos = album_public_photo_urls($albumId, 100);
    if ($photos === []) {
        $result['errors'][] = 'No publicly reachable HTTPS photo URL.';
        db()->prepare('UPDATE albums SET social_publish_error = ? WHERE id = ?')->execute([implode("\n", $result['errors']), $albumId]);
        return $result;
    }

    $title = trim((string) ($album['title'] ?? 'Album'));
    $description = trim((string) ($album['description'] ?? ''));
    $caption = trim($title . ($description !== '' ? "\n\n" . $description : ''));

    $facebookPageId = trim((string) (getenv('FACEBOOK_PAGE_ID') ?: getenv('META_FACEBOOK_PAGE_ID') ?: ''));
    $facebookToken = trim((string) (getenv('FACEBOOK_PAGE_ACCESS_TOKEN') ?: getenv('META_FACEBOOK_PAGE_ACCESS_TOKEN') ?: ''));
    $facebookTargetAlbumId = trim((string) (getenv('FACEBOOK_ALBUM_ID') ?: getenv('META_FACEBOOK_ALBUM_ID') ?: ($album['facebook_album_id'] ?? '')));
    $facebookAlreadyPublished = trim((string) ($album['facebook_post_id'] ?? '')) !== '' || trim((string) ($album['facebook_album_id'] ?? '')) !== '';
    if ($facebookPageId !== '' && $facebookToken !== '' && !$facebookAlreadyPublished) {
        if ($facebookTargetAlbumId !== '') {
            $uploadedPhotoIds = [];
            foreach ($photos as $photo) {
                $upload = album_graph_api_post($facebookTargetAlbumId . '/photos', [
                    'url' => $photo['url'],
                    'caption' => $photo['caption'] !== '' ? $photo['caption'] : ($photo['title'] !== '' ? $photo['title'] : $title),
                ], $facebookToken);
                $photoId = trim((string) ($upload['data']['id'] ?? ''));
                if ($upload['ok'] && $photoId !== '') {
                    $uploadedPhotoIds[] = $photoId;
                } else {
                    $result['errors'][] = 'Facebook: ' . $upload['error'];
                }
            }
            if ($uploadedPhotoIds !== []) {
                $result['facebook'] = $facebookTargetAlbumId;
                db()->prepare('UPDATE albums SET facebook_album_id = ? WHERE id = ?')->execute([$facebookTargetAlbumId, $albumId]);
            }
        } else {
            $facebookPhotos = array_slice($photos, 0, 10);
            if (count($photos) > count($facebookPhotos)) {
                $result['skipped'][] = 'facebook_page_post_limited_to_10_photos';
            }
            if (count($facebookPhotos) === 1) {
                $upload = album_graph_api_post($facebookPageId . '/photos', [
                    'url' => $facebookPhotos[0]['url'],
                    'caption' => $caption,
                ], $facebookToken);
                $facebookPostId = trim((string) ($upload['data']['post_id'] ?? $upload['data']['id'] ?? ''));
                if (!$upload['ok']) {
                    $result['errors'][] = 'Facebook: ' . $upload['error'];
                }
            } else {
                $attachedMedia = [];
                foreach ($facebookPhotos as $index => $photo) {
                    $upload = album_graph_api_post($facebookPageId . '/photos', [
                        'url' => $photo['url'],
                        'caption' => $photo['caption'] !== '' ? $photo['caption'] : ($photo['title'] !== '' ? $photo['title'] : $title),
                        'published' => 'false',
                    ], $facebookToken);
                    $mediaId = trim((string) ($upload['data']['id'] ?? ''));
                    if ($upload['ok'] && $mediaId !== '') {
                        $mediaPayload = json_encode(['media_fbid' => $mediaId], JSON_UNESCAPED_SLASHES);
                        if (is_string($mediaPayload)) {
                            $attachedMedia['attached_media[' . $index . ']'] = $mediaPayload;
                        }
                    } else {
                        $result['errors'][] = 'Facebook: ' . $upload['error'];
                    }
                }
                $facebookPostId = '';
                if ($attachedMedia !== []) {
                    $post = album_graph_api_post($facebookPageId . '/feed', ['message' => $caption] + $attachedMedia, $facebookToken);
                    $facebookPostId = trim((string) ($post['data']['id'] ?? ''));
                    if (!$post['ok']) {
                        $result['errors'][] = 'Facebook: ' . $post['error'];
                    }
                }
            }
            if (($facebookPostId ?? '') !== '') {
                $result['facebook'] = $facebookPostId;
                db()->prepare('UPDATE albums SET facebook_post_id = ? WHERE id = ?')->execute([$facebookPostId, $albumId]);
            }
        }
    } else {
        $result['skipped'][] = 'facebook_not_configured_or_already_published';
    }

    $instagramUserId = trim((string) (getenv('INSTAGRAM_BUSINESS_ACCOUNT_ID') ?: getenv('META_INSTAGRAM_BUSINESS_ACCOUNT_ID') ?: ''));
    $instagramToken = trim((string) (getenv('INSTAGRAM_ACCESS_TOKEN') ?: getenv('META_INSTAGRAM_ACCESS_TOKEN') ?: $facebookToken));
    if ($instagramUserId !== '' && $instagramToken !== '' && trim((string) ($album['instagram_media_id'] ?? '')) === '') {
        $instagramPhotos = array_slice($photos, 0, 10);
        if (count($photos) > count($instagramPhotos)) {
            $result['skipped'][] = 'instagram_carousel_limited_to_10_photos';
        }
        if (count($instagramPhotos) === 1) {
            $container = album_graph_api_post($instagramUserId . '/media', [
                'image_url' => $instagramPhotos[0]['url'],
                'caption' => $caption,
            ], $instagramToken);
            $creationId = trim((string) ($container['data']['id'] ?? ''));
            if (!$container['ok']) {
                $result['errors'][] = 'Instagram: ' . $container['error'];
            }
        } else {
            $children = [];
            foreach ($instagramPhotos as $photo) {
                $child = album_graph_api_post($instagramUserId . '/media', [
                    'image_url' => $photo['url'],
                    'is_carousel_item' => 'true',
                ], $instagramToken);
                $childId = trim((string) ($child['data']['id'] ?? ''));
                if ($child['ok'] && $childId !== '') {
                    $children[] = $childId;
                } else {
                    $result['errors'][] = 'Instagram: ' . $child['error'];
                }
            }
            $creationId = '';
            if (count($children) >= 2) {
                $container = album_graph_api_post($instagramUserId . '/media', [
                    'media_type' => 'CAROUSEL',
                    'children' => implode(',', $children),
                    'caption' => $caption,
                ], $instagramToken);
                $creationId = trim((string) ($container['data']['id'] ?? ''));
                if (!$container['ok']) {
                    $result['errors'][] = 'Instagram: ' . $container['error'];
                }
            }
        }
        if (($creationId ?? '') !== '') {
            $publish = album_graph_api_post($instagramUserId . '/media_publish', ['creation_id' => $creationId], $instagramToken);
            $mediaId = trim((string) ($publish['data']['id'] ?? ''));
            if ($publish['ok'] && $mediaId !== '') {
                $result['instagram'] = $mediaId;
                db()->prepare('UPDATE albums SET instagram_media_id = ? WHERE id = ?')->execute([$mediaId, $albumId]);
            } else {
                $result['errors'][] = 'Instagram: ' . $publish['error'];
            }
        }
    } else {
        $result['skipped'][] = 'instagram_not_configured_or_already_published';
    }

    db()->prepare('UPDATE albums SET social_published_at = IF((facebook_album_id IS NOT NULL AND facebook_album_id <> "") OR (facebook_post_id IS NOT NULL AND facebook_post_id <> "") OR (instagram_media_id IS NOT NULL AND instagram_media_id <> ""), COALESCE(social_published_at, NOW()), social_published_at), social_publish_error = ? WHERE id = ?')
        ->execute([$result['errors'] !== [] ? implode("\n", $result['errors']) : null, $albumId]);

    return $result;
}

function album_clear_caches(): void
{
    cache_forget('admin_albums_list_v2');
    cache_forget('admin_albums_photos_total_v2');
    cache_forget('home_public_album_random_photos_v1');
}

function album_ensure_photo_sort_order_column(): bool
{
    if (!table_exists('album_photos')) {
        return false;
    }

    try {
        if (!table_has_column('album_photos', 'album_id')) {
            db()->exec('ALTER TABLE album_photos ADD COLUMN album_id INT NOT NULL DEFAULT 0 AFTER id');
        }
        if (!table_has_column('album_photos', 'title')) {
            db()->exec('ALTER TABLE album_photos ADD COLUMN title VARCHAR(190) NOT NULL DEFAULT "Photo" AFTER album_id');
        }
        if (!table_has_column('album_photos', 'caption')) {
            db()->exec('ALTER TABLE album_photos ADD COLUMN caption TEXT DEFAULT NULL AFTER title');
        }
        if (!table_has_column('album_photos', 'file_path')) {
            db()->exec('ALTER TABLE album_photos ADD COLUMN file_path VARCHAR(255) NOT NULL DEFAULT "" AFTER caption');
        }
        if (!table_has_column('album_photos', 'created_at')) {
            db()->exec('ALTER TABLE album_photos ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER file_path');
        }
        if (!table_has_column('album_photos', 'sort_order')) {
            db()->exec('ALTER TABLE album_photos ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER album_id');
            db()->exec('UPDATE album_photos SET sort_order = id WHERE sort_order = 0');
        }

        return true;
    } catch (Throwable $throwable) {
        log_structured_event('album_photo_sort_order_ensure_failed', [
            'message' => $throwable->getMessage(),
        ]);

        return false;
    }
}

function album_ensure_source_proposal_column(): bool
{
    if (!table_exists('albums')) {
        return false;
    }

    try {
        if (!table_has_column('albums', 'member_id')) {
            db()->exec('ALTER TABLE albums ADD COLUMN member_id INT DEFAULT NULL AFTER id');
        }
        if (!table_has_column('albums', 'category')) {
            db()->exec('ALTER TABLE albums ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT "general" AFTER member_id');
        }
        if (!table_has_column('albums', 'subcategory')) {
            db()->exec('ALTER TABLE albums ADD COLUMN subcategory VARCHAR(120) NOT NULL DEFAULT "" AFTER category');
        }
        if (!table_has_column('albums', 'title')) {
            db()->exec('ALTER TABLE albums ADD COLUMN title VARCHAR(190) NOT NULL DEFAULT "Album" AFTER subcategory');
        }
        if (!table_has_column('albums', 'description')) {
            db()->exec('ALTER TABLE albums ADD COLUMN description TEXT DEFAULT NULL AFTER title');
        }
        if (!table_has_column('albums', 'is_public')) {
            db()->exec('ALTER TABLE albums ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER description');
        }
        if (!table_has_index('albums', 'idx_albums_member')) {
            db()->exec('ALTER TABLE albums ADD INDEX idx_albums_member (member_id)');
        }
        if (!table_has_index('albums', 'idx_albums_category')) {
            db()->exec('ALTER TABLE albums ADD INDEX idx_albums_category (category)');
        }
        if (!table_has_index('albums', 'idx_albums_subcategory')) {
            db()->exec('ALTER TABLE albums ADD INDEX idx_albums_subcategory (category, subcategory)');
        }
        if (!table_has_column('albums', 'source_proposal_id')) {
            db()->exec('ALTER TABLE albums ADD COLUMN source_proposal_id INT NULL AFTER is_public');
        }
        if (!table_has_column('albums', 'publish_requested')) {
            db()->exec('ALTER TABLE albums ADD COLUMN publish_requested TINYINT(1) NOT NULL DEFAULT 0 AFTER source_proposal_id');
        }
        if (!table_has_column('albums', 'facebook_album_id')) {
            db()->exec('ALTER TABLE albums ADD COLUMN facebook_album_id VARCHAR(80) DEFAULT NULL AFTER publish_requested');
        }
        if (!table_has_column('albums', 'facebook_post_id')) {
            db()->exec('ALTER TABLE albums ADD COLUMN facebook_post_id VARCHAR(80) DEFAULT NULL AFTER facebook_album_id');
        }
        if (!table_has_column('albums', 'instagram_media_id')) {
            db()->exec('ALTER TABLE albums ADD COLUMN instagram_media_id VARCHAR(80) DEFAULT NULL AFTER facebook_post_id');
        }
        if (!table_has_column('albums', 'social_published_at')) {
            db()->exec('ALTER TABLE albums ADD COLUMN social_published_at DATETIME DEFAULT NULL AFTER instagram_media_id');
        }
        if (!table_has_column('albums', 'social_publish_error')) {
            db()->exec('ALTER TABLE albums ADD COLUMN social_publish_error TEXT DEFAULT NULL AFTER social_published_at');
        }
        if (!table_has_column('albums', 'created_at')) {
            db()->exec('ALTER TABLE albums ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER social_publish_error');
        }
        if (!table_has_index('albums', 'idx_albums_source_proposal')) {
            db()->exec('ALTER TABLE albums ADD INDEX idx_albums_source_proposal (source_proposal_id)');
        }
        db()->exec('UPDATE albums SET category = "general" WHERE category IS NULL OR category = ""');
        db()->exec('UPDATE albums SET subcategory = "" WHERE subcategory IS NULL');
        album_ensure_categories_table();
        album_ensure_subcategories_table();

        return true;
    } catch (Throwable $throwable) {
        log_structured_event('album_source_proposal_column_ensure_failed', [
            'message' => $throwable->getMessage(),
        ]);

        return false;
    }
}

function album_category_code(string $value): string
{
    return content_proposal_category_code($value, 120, 'general');
}

function album_subcategory_code(string $value): string
{
    return content_taxonomy_code($value, 120, '', true);
}

function album_subcategory_ref(string $categoryCode, string $subcategoryCode): string
{
    $categoryCode = album_category_code($categoryCode !== '' ? $categoryCode : 'general');
    $subcategoryCode = album_subcategory_code($subcategoryCode);

    return $subcategoryCode !== '' ? ($categoryCode . ':' . $subcategoryCode) : '';
}

/**
 * @return array{category:string,subcategory:string}
 */
function album_subcategory_ref_parts(string $value): array
{
    $value = trim($value);
    if ($value === '') {
        return ['category' => '', 'subcategory' => ''];
    }
    $parts = explode(':', $value, 2);
    if (count($parts) === 2) {
        return [
            'category' => album_category_code($parts[0] !== '' ? $parts[0] : 'general'),
            'subcategory' => album_subcategory_code($parts[1]),
        ];
    }

    return ['category' => '', 'subcategory' => album_subcategory_code($value)];
}

function album_category_label_from_code(string $code): string
{
    $label = trim(str_replace('-', ' ', album_category_code($code)));
    return $label !== '' ? mb_convert_case($label, MB_CASE_TITLE, 'UTF-8') : 'General';
}

/**
 * @return array<string, string>
 */
function album_default_categories(): array
{
    return [
        'general' => 'General',
        'activites' => 'Activites club',
        'contests' => 'Contests',
        'formations' => 'Formations',
        'sorties' => 'Sorties',
        'radio' => 'Radioamateur',
    ];
}

function album_ensure_categories_table(): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS album_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(120) NOT NULL UNIQUE,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
        if (!table_has_column('album_categories', 'deleted_at')) {
            db()->exec('ALTER TABLE album_categories ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER sort_order');
        }
        if (!table_has_index('album_categories', 'idx_album_category_deleted')) {
            db()->exec('ALTER TABLE album_categories ADD INDEX idx_album_category_deleted (deleted_at)');
        }
        $insert = db()->prepare('INSERT IGNORE INTO album_categories (code, label, sort_order) VALUES (?, ?, ?)');
        $order = 1;
        foreach (album_default_categories() as $code => $label) {
            $insert->execute([(string) $code, (string) $label, $order++ * 10]);
        }

        return table_exists('album_categories');
    } catch (Throwable) {
        return false;
    }
}

function album_ensure_subcategories_table(): bool
{
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS album_subcategories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_code VARCHAR(120) NOT NULL,
            code VARCHAR(120) NOT NULL,
            label VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_album_subcategory (category_code, code),
            INDEX idx_album_subcategory_category (category_code)
        )');

        return table_exists('album_subcategories');
    } catch (Throwable) {
        return false;
    }
}

/**
 * @return array<string, string>
 */
function album_categories(): array
{
    $categories = [];
    $deletedCategories = [];
    $categoryTableAvailable = album_ensure_categories_table();
    if ($categoryTableAvailable) {
        try {
            foreach (db()->query('SELECT code FROM album_categories WHERE deleted_at IS NOT NULL')->fetchAll() ?: [] as $row) {
                $code = album_category_code((string) ($row['code'] ?? ''));
                if ($code !== '') {
                    $deletedCategories[$code] = true;
                }
            }
        } catch (Throwable) {
            $deletedCategories = [];
        }
    }

    foreach (album_default_categories() as $code => $label) {
        $categoryCode = album_category_code((string) $code);
        if ($categoryCode !== '' && !isset($deletedCategories[$categoryCode])) {
            $categories[$categoryCode] = (string) $label;
        }
    }

    if ($categoryTableAvailable) {
        try {
            foreach (db()->query('SELECT code, label FROM album_categories WHERE deleted_at IS NULL ORDER BY sort_order ASC, label ASC')->fetchAll() ?: [] as $row) {
                $code = album_category_code((string) ($row['code'] ?? ''));
                $label = content_proposal_clean_single_line((string) ($row['label'] ?? $code), 160);
                if ($code !== '' && $label !== '') {
                    $categories[$code] = $label;
                }
            }
        } catch (Throwable) {
        }
    }
    try {
        if (table_exists('albums') && table_has_column('albums', 'category')) {
            foreach (db()->query('SELECT category FROM albums WHERE category IS NOT NULL AND category <> "" GROUP BY category ORDER BY category ASC')->fetchAll() ?: [] as $row) {
                $code = album_category_code((string) ($row['category'] ?? ''));
                if ($code !== '' && !isset($deletedCategories[$code]) && !isset($categories[$code])) {
                    $categories[$code] = album_category_label_from_code($code);
                }
            }
        }
    } catch (Throwable) {
    }

    return $categories;
}

/**
 * @param array<string, string> $categories
 */
function album_category_from_input(string $value, array $categories): string
{
    $code = album_category_code($value);
    if ($code === '') {
        $code = 'general';
    }
    if (!isset($categories[$code])) {
        throw new RuntimeException('Invalid album category.');
    }

    return $code;
}

/**
 * @return list<array{category_code:string,code:string,label:string}>
 */
function album_subcategory_options(): array
{
    if (!album_ensure_subcategories_table()) {
        return [];
    }

    try {
        $rows = db()->query('SELECT category_code, code, label FROM album_subcategories ORDER BY category_code ASC, sort_order ASC, label ASC')->fetchAll() ?: [];
    } catch (Throwable) {
        $rows = [];
    }

    $options = [];
    foreach ($rows as $row) {
        $categoryCode = album_category_code((string) ($row['category_code'] ?? 'general'));
        $code = album_subcategory_code((string) ($row['code'] ?? ''));
        $label = content_proposal_clean_single_line((string) ($row['label'] ?? $code), 160);
        if ($categoryCode !== '' && $code !== '' && $label !== '') {
            $options[] = ['category_code' => $categoryCode, 'code' => $code, 'label' => $label];
        }
    }

    return $options;
}

/**
 * @return array<string, list<array{category_code:string,code:string,label:string}>>
 */
function album_subcategories_by_category(): array
{
    $byCategory = [];
    foreach (album_subcategory_options() as $subcategory) {
        $byCategory[$subcategory['category_code']][] = $subcategory;
    }

    return $byCategory;
}

/**
 * @param array<string, string> $categories
 * @param array<string, int> $countsByCategory
 * @return array<string, string>
 */
function album_visible_categories(array $categories, array $countsByCategory): array
{
    $visible = [];
    foreach ($categories as $code => $label) {
        if ((int) ($countsByCategory[(string) $code] ?? 0) <= 0) {
            continue;
        }
        $visible[(string) $code] = (string) $label;
    }

    return $visible;
}

/**
 * @param array<string, list<array<string, mixed>>> $subcategoriesByCategory
 * @param array<string, int> $countsBySubcategory
 * @return array<string, list<array<string, mixed>>>
 */
function album_visible_subcategories_by_category(array $subcategoriesByCategory, array $countsBySubcategory): array
{
    $visible = [];
    foreach ($subcategoriesByCategory as $categoryCode => $subcategories) {
        foreach ($subcategories as $subcategory) {
            $code = album_subcategory_code((string) ($subcategory['code'] ?? ''));
            $count = (int) ($countsBySubcategory[(string) $categoryCode . ':' . $code] ?? 0);
            if ($code === '' || $count <= 0) {
                continue;
            }
            $subcategory['total'] = $count;
            $visible[(string) $categoryCode][] = $subcategory;
        }
    }

    return $visible;
}

/**
 * @return list<int>
 */
function album_favorite_album_ids(int $memberId): array
{
    if (
        $memberId <= 0
        || !function_exists('ensure_member_favorites_table')
        || !ensure_member_favorites_table()
        || !table_exists('albums')
    ) {
        return [];
    }

    try {
        $stmt = db()->prepare('SELECT a.id FROM member_favorites f INNER JOIN albums a ON a.id = f.target_id WHERE f.member_id = ? AND f.target_type = ? AND a.is_public = 1 ORDER BY f.created_at DESC, f.id DESC');
        $stmt->execute([$memberId, 'album']);
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

/**
 * @param array<string, string> $categories
 * @param array<string, string> $labels
 */
function render_album_taxonomy_fields(array $categories, array $labels = [], string $selectedCategory = 'general', string $selectedSubcategory = ''): string
{
    $selectedCategory = album_category_code($selectedCategory !== '' ? $selectedCategory : 'general');
    $selectedSubcategory = album_subcategory_code($selectedSubcategory);
    $subcategoriesByCategory = album_subcategories_by_category();
    $categoryLabel = (string) ($labels['category_field'] ?? 'Thématique');
    $subcategoryLabel = (string) ($labels['subcategory_field'] ?? 'Sous-thématique');
    $noSubcategory = (string) ($labels['no_subcategory'] ?? 'Sans sous-thématique');

    $html = '<label><span>' . e($categoryLabel) . '</span><select name="category">';
    foreach ($categories as $code => $label) {
        $html .= '<option value="' . e((string) $code) . '"' . ($selectedCategory === (string) $code ? ' selected' : '') . '>' . e((string) $label) . '</option>';
    }
    $html .= '</select></label>'
        . '<label><span>' . e($subcategoryLabel) . '</span><select name="subcategory_ref">'
        . '<option value="">' . e($noSubcategory) . '</option>';
    foreach ($subcategoriesByCategory as $parentCode => $subcategories) {
        $html .= '<optgroup label="' . e((string) ($categories[(string) $parentCode] ?? album_category_label_from_code((string) $parentCode))) . '">';
        foreach ($subcategories as $subcategory) {
            $code = album_subcategory_code((string) ($subcategory['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $html .= '<option value="' . e(album_subcategory_ref((string) $parentCode, $code)) . '"'
                . ($selectedCategory === (string) $parentCode && $selectedSubcategory === $code ? ' selected' : '')
                . '>' . e((string) ($subcategory['label'] ?? $code)) . '</option>';
        }
        $html .= '</optgroup>';
    }

    return $html . '</select></label>';
}

function album_proposal_description_from_summary(string $summary): ?string
{
    $description = content_proposal_detail_from_summary($summary, ['Description', 'Resume', 'Summary']);
    $theme = content_proposal_detail_from_summary($summary, ['Thematique', 'Theme', 'Topic']);
    $keywords = content_proposal_detail_from_summary($summary, ['Mots cles', 'Keywords', 'Tags']);
    $metadata = content_proposal_details_text([
        'Thematique' => $theme,
        'Mots cles' => $keywords,
    ]);
    $albumDescription = trim($description . ($metadata !== '' ? "\n\n" . $metadata : ''));

    return $albumDescription !== '' ? $albumDescription : null;
}

function album_proposal_action(string $summary): string
{
    $action = content_proposal_clean_single_line(
        content_proposal_detail_from_summary($summary, ['Action']),
        32
    );

    return in_array($action, ['update_album', 'delete_album'], true) ? $action : '';
}

function album_proposal_album_id(string $summary): int
{
    return max(0, (int) content_proposal_detail_from_summary($summary, ['Album ID']));
}

function album_delete_photo_file(string $publicPath): void
{
    $safePath = safe_storage_public_path_or_null($publicPath, ['storage/uploads/albums/']);
    if ($safePath === null) {
        return;
    }

    $absolute = dirname(__DIR__) . '/' . $safePath;
    if (is_file($absolute)) {
        @unlink($absolute);
    }
    $thumbPublic = album_thumbnail_public_path($safePath);
    $thumbAbsolute = dirname(__DIR__) . '/' . $thumbPublic;
    if (is_file($thumbAbsolute)) {
        @unlink($thumbAbsolute);
    }
}

function album_update_record(int $albumId, string $title, string $description, ?int $isPublic = null, string $category = 'general', string $subcategory = ''): void
{
    if (!album_ensure_source_proposal_column()) {
        throw new RuntimeException('Albums storage unavailable.');
    }

    $title = content_proposal_clean_single_line($title, 190);
    $description = content_proposal_clean_multiline($description, 10000);
    $category = album_category_code($category !== '' ? $category : 'general');
    $subcategory = album_subcategory_code($subcategory);
    if ($albumId <= 0 || $title === '') {
        throw new RuntimeException('Invalid album proposal.');
    }

    $stmt = db()->prepare('SELECT id, is_public FROM albums WHERE id = ? LIMIT 1');
    $stmt->execute([$albumId]);
    $album = $stmt->fetch();
    if (!is_array($album)) {
        throw new RuntimeException('Invalid album proposal.');
    }

    $visibility = $isPublic;
    if ($visibility === null) {
        $visibility = (int) ($album['is_public'] ?? 1);
    }

    db()->prepare('UPDATE albums SET category = ?, subcategory = ?, title = ?, description = ?, is_public = ? WHERE id = ?')
        ->execute([$category, $subcategory, $title, $description !== '' ? $description : null, $visibility ? 1 : 0, $albumId]);
    if (table_exists('member_favorites')) {
        db()->prepare('UPDATE member_favorites SET title = ?, url = ? WHERE target_type = ? AND target_id = ?')
            ->execute([$title, route_url('album', ['id' => $albumId]), 'album', $albumId]);
    }
    album_clear_caches();
}

function album_delete_record(int $albumId): void
{
    if (!album_ensure_source_proposal_column()) {
        throw new RuntimeException('Albums storage unavailable.');
    }
    if ($albumId <= 0) {
        throw new RuntimeException('Invalid album proposal.');
    }

    $albumStmt = db()->prepare('SELECT id FROM albums WHERE id = ? LIMIT 1');
    $albumStmt->execute([$albumId]);
    if (!$albumStmt->fetchColumn()) {
        throw new RuntimeException('Invalid album proposal.');
    }

    $photoStmt = db()->prepare('SELECT file_path FROM album_photos WHERE album_id = ?');
    $photoStmt->execute([$albumId]);
    $photoRows = $photoStmt->fetchAll() ?: [];

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM album_photos WHERE album_id = ?')->execute([$albumId]);
        if (table_exists('member_favorites')) {
            $pdo->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id = ?')->execute(['album', $albumId]);
        }
        $pdo->prepare('DELETE FROM albums WHERE id = ?')->execute([$albumId]);
        $pdo->commit();
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $throwable;
    }

    foreach ($photoRows as $photoRow) {
        album_delete_photo_file((string) ($photoRow['file_path'] ?? ''));
    }
    album_clear_caches();
}

function album_apply_accepted_proposal(array $proposal): ?int
{
    if ((string) ($proposal['proposal_type'] ?? '') !== 'content') {
        return null;
    }
    if (!album_ensure_source_proposal_column()) {
        throw new RuntimeException('Albums storage unavailable.');
    }

    $proposalId = (int) ($proposal['id'] ?? 0);
    $title = content_proposal_clean_single_line((string) ($proposal['title'] ?? ''), 190);
    if ($proposalId <= 0 || $title === '') {
        throw new RuntimeException('Invalid album proposal.');
    }

    $summary = (string) ($proposal['summary'] ?? '');
    $action = album_proposal_action($summary);
    if ($action !== '') {
        $albumId = album_proposal_album_id($summary);
        if ($action === 'delete_album') {
            album_delete_record($albumId);

            return $albumId;
        }

        album_update_record(
            $albumId,
            $title,
            (string) (content_proposal_detail_from_summary($summary, ['Description', 'Resume', 'Summary']) ?: ''),
            null,
            (string) (content_proposal_detail_from_summary($summary, ['Thematique', 'Thématique', 'Theme', 'Topic']) ?: 'general'),
            (string) (content_proposal_detail_from_summary($summary, ['Sous-thematique', 'Sous-thématique', 'Subtopic']) ?: '')
        );

        return $albumId;
    }

    $existingStmt = db()->prepare('SELECT id FROM albums WHERE source_proposal_id = ? LIMIT 1');
    $existingStmt->execute([$proposalId]);
    $existingId = (int) ($existingStmt->fetchColumn() ?: 0);
    if ($existingId > 0) {
        return $existingId;
    }

    $description = album_proposal_description_from_summary($summary);
    $category = album_category_code((string) (content_proposal_detail_from_summary($summary, ['Thematique', 'Thématique', 'Theme', 'Topic']) ?: 'general'));
    $subcategory = album_subcategory_code((string) (content_proposal_detail_from_summary($summary, ['Sous-thematique', 'Sous-thématique', 'Subtopic']) ?: ''));
    db()->prepare('INSERT INTO albums (member_id, category, subcategory, title, description, is_public, source_proposal_id) VALUES (?, ?, ?, ?, ?, 1, ?)')
        ->execute([max(0, (int) ($proposal['member_id'] ?? 0)), $category, $subcategory, $title, $description, $proposalId]);
    $albumId = (int) db()->lastInsertId();
    album_clear_caches();

    return $albumId;
}

/**
 * @return array{checked:int,applied:int,skipped:int,failed:int}
 */
function album_sync_accepted_proposals(int $limit = 100): array
{
    static $alreadyRan = false;

    $result = ['checked' => 0, 'applied' => 0, 'skipped' => 0, 'failed' => 0];
    if ($alreadyRan) {
        return $result;
    }
    $alreadyRan = true;

    if (!ensure_content_proposals_table() || !album_ensure_source_proposal_column()) {
        return $result;
    }

    $limit = max(1, min(500, $limit));
    try {
        $stmt = db()->prepare(
            'SELECT id, member_id, proposal_type, title, summary, source_ref
             FROM content_proposals
             WHERE area = "albums"
               AND status = "accepted"
               AND proposal_type = "content"
             ORDER BY updated_at ASC, id ASC
             LIMIT ' . $limit
        );
        $stmt->execute();
        $proposals = $stmt->fetchAll() ?: [];
    } catch (Throwable $throwable) {
        log_structured_event('album_accepted_proposals_sync_load_failed', [
            'message' => $throwable->getMessage(),
        ]);

        return $result;
    }

    foreach ($proposals as $proposal) {
        $result['checked']++;
        try {
            if (album_proposal_action((string) ($proposal['summary'] ?? '')) !== '') {
                $result['skipped']++;
                continue;
            }

            $proposalId = (int) ($proposal['id'] ?? 0);
            $existingStmt = db()->prepare('SELECT id FROM albums WHERE source_proposal_id = ? LIMIT 1');
            $existingStmt->execute([$proposalId]);
            if ((int) ($existingStmt->fetchColumn() ?: 0) > 0) {
                $result['skipped']++;
                continue;
            }

            album_apply_accepted_proposal($proposal);
            $result['applied']++;
        } catch (Throwable $throwable) {
            $result['failed']++;
            log_structured_event('album_accepted_proposal_sync_failed', [
                'proposal_id' => (int) ($proposal['id'] ?? 0),
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    return $result;
}

function handle_album_upload(?array $upload, string $callsign): string
{
    if (!is_array($upload)) {
        throw new RuntimeException(upload_i18n_message('missing_image'));
    }
    $baseDir = dirname(__DIR__) . '/storage/uploads/albums';
    $saved = secure_move_uploaded_file(
        $upload,
        $baseDir,
        slugify($callsign !== '' ? $callsign : 'member'),
        ['jpg', 'jpeg', 'png', 'webp'],
        ['image/jpeg', 'image/png', 'image/webp'],
        8 * 1024 * 1024,
        true
    );

    $publicPath = 'storage/uploads/albums/' . $saved;
    create_album_thumbnail($publicPath, 640, 640);

    return $publicPath;
}

function create_album_thumbnail(string $publicPath, int $maxWidth = 640, int $maxHeight = 640): ?string
{
    if (!extension_loaded('gd')) {
        return null;
    }
    $sourcePath = dirname(__DIR__) . '/' . ltrim($publicPath, '/');
    if (!is_file($sourcePath)) {
        return null;
    }
    $info = @getimagesize($sourcePath);
    if (!is_array($info)) {
        return null;
    }
    [$width, $height] = $info;
    if ($width <= 0 || $height <= 0) {
        return null;
    }
    $mime = (string) $info['mime'];
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($sourcePath),
        'image/png' => @imagecreatefrompng($sourcePath),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
        default => false,
    };
    if (!$src) {
        return null;
    }
    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
    $newW = max(1, (int) floor($width * $ratio));
    $newH = max(1, (int) floor($height * $ratio));
    $dst = imagecreatetruecolor($newW, $newH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);

    $dir = dirname($sourcePath) . '/thumbs';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        imagedestroy($src);
        imagedestroy($dst);
        return null;
    }
    $name = pathinfo($sourcePath, PATHINFO_FILENAME) . '.jpg';
    $thumbAbs = $dir . '/' . $name;
    $ok = imagejpeg($dst, $thumbAbs, 84);
    imagedestroy($src);
    imagedestroy($dst);
    if (!$ok) {
        return null;
    }
    return 'storage/uploads/albums/thumbs/' . $name;
}

function album_thumbnail_public_path(string $photoPath): string
{
    $base = pathinfo($photoPath, PATHINFO_FILENAME);
    return 'storage/uploads/albums/thumbs/' . $base . '.jpg';
}
