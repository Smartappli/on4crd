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

function album_clear_caches(): void
{
    cache_forget('admin_albums_list_v2');
    cache_forget('admin_albums_photos_total_v2');
    cache_forget('home_public_album_random_photos_v1');
}

function album_ensure_source_proposal_column(): bool
{
    if (!table_exists('albums')) {
        return false;
    }

    try {
        if (!table_has_column('albums', 'source_proposal_id')) {
            db()->exec('ALTER TABLE albums ADD COLUMN source_proposal_id INT NULL AFTER is_public');
        }
        if (!table_has_index('albums', 'idx_albums_source_proposal')) {
            db()->exec('ALTER TABLE albums ADD INDEX idx_albums_source_proposal (source_proposal_id)');
        }

        return true;
    } catch (Throwable $throwable) {
        log_structured_event('album_source_proposal_column_ensure_failed', [
            'message' => $throwable->getMessage(),
        ]);

        return false;
    }
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

    $existingStmt = db()->prepare('SELECT id FROM albums WHERE source_proposal_id = ? LIMIT 1');
    $existingStmt->execute([$proposalId]);
    $existingId = (int) ($existingStmt->fetchColumn() ?: 0);
    if ($existingId > 0) {
        return $existingId;
    }

    $description = album_proposal_description_from_summary((string) ($proposal['summary'] ?? ''));
    db()->prepare('INSERT INTO albums (title, description, is_public, source_proposal_id) VALUES (?, ?, 1, ?)')
        ->execute([$title, $description, $proposalId]);
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
        8 * 1024 * 1024
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
