<?php
declare(strict_types=1);

require_once __DIR__ . '/privacy_helpers.php';

/**
 * @return array<string, array{label:string,width:int,height:int}>
 */
function ad_format_catalog(): array
{
    return [
        'square' => ['label' => 'Carré (1080×1080)', 'width' => 1080, 'height' => 1080],
        'landscape' => ['label' => 'Paysage (1200×628)', 'width' => 1200, 'height' => 628],
        'portrait' => ['label' => 'Portrait (1080×1350)', 'width' => 1080, 'height' => 1350],
    ];
}

function ad_format_label(string $formatCode): string
{
    $catalog = ad_format_catalog();
    return (string) ($catalog[$formatCode]['label'] ?? $formatCode);
}

function ad_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'En attente',
        'active' => 'Active',
        'paused' => 'En pause',
        'expired' => 'Expirée',
        'rejected' => 'Refusée',
        default => ucfirst($status),
    };
}

/**
 * @param array<string,mixed> $ad
 */
function ad_runtime_status(array $ad): string
{
    $status = (string) ($ad['status'] ?? 'pending');
    if ($status !== 'active') {
        return $status;
    }
    $endsAt = (string) ($ad['ends_at'] ?? '');
    if ($endsAt !== '' && strtotime($endsAt) !== false && strtotime($endsAt) < time()) {
        return 'expired';
    }
    return 'active';
}

/**
 * @return array<int, array{code:string,label:string}>
 */
function available_ad_placements(): array
{
    return [
        ['code' => 'home_hero', 'label' => 'Accueil (hero)'],
        ['code' => 'home_sidebar', 'label' => 'Accueil (latéral)'],
        ['code' => 'news_inline', 'label' => 'Actualités (inline)'],
    ];
}

/**
 * @return array<int, array{code:string,label:string}>
 */
function ad_placements_for_member(int $memberId): array
{
    return available_ad_placements();
}

/**
 * @return array<int, array<string,mixed>>
 */
function member_ads(int $memberId, bool $ownerOnly = true): array
{
    if (!table_exists('ads')) {
        return [];
    }

    $placementMap = [];
    foreach (available_ad_placements() as $placement) {
        $placementMap[(string) ($placement['code'] ?? '')] = (string) ($placement['label'] ?? '');
    }

    if ($ownerOnly) {
        $stmt = db()->prepare('SELECT a.* FROM ads a WHERE a.owner_member_id = ? ORDER BY a.updated_at DESC, a.id DESC');
        $stmt->execute([$memberId]);
        $rows = $stmt->fetchAll() ?: [];
    } else {
        $rows = db()->query('SELECT a.* FROM ads a ORDER BY a.updated_at DESC, a.id DESC')->fetchAll() ?: [];
    }

    foreach ($rows as &$row) {
        $row['runtime_status'] = ad_runtime_status($row);
        $code = (string) ($row['placement_code'] ?? '');
        $row['placement_name'] = $placementMap[$code] ?? $code;
        $row['owner_callsign'] = (string) ($row['owner_callsign'] ?? '');
    }
    unset($row);

    return $rows;
}

/**
 * @return array<string,mixed>|null
 */
function ad_fetch_by_id(int $adId): ?array
{
    if ($adId <= 0 || !table_exists('ads')) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM ads WHERE id = ? LIMIT 1');
    $stmt->execute([$adId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

/**
 * @return array<int, array{day:string,impressions:int,clicks:int}>
 */
function ad_daily_stats(int $adId): array
{
    if ($adId <= 0 || !table_exists('ad_events')) {
        return [];
    }
    $stmt = db()->prepare('SELECT DATE(created_at) AS day, SUM(event_type = "impression") AS impressions, SUM(event_type = "click") AS clicks FROM ad_events WHERE ad_id = ? GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 30');
    $stmt->execute([$adId]);
    return $stmt->fetchAll() ?: [];
}

function log_ad_event(int $adId, string $eventType, string $placementCode = ''): void
{
    if ($adId <= 0 || !table_exists('ad_events')) {
        return;
    }

    if ($eventType === 'view') {
        $eventType = 'impression';
    }
    if (!in_array($eventType, ['impression', 'click'], true)) {
        return;
    }

    $columns = ['ad_id', 'event_type', 'placement_code'];
    $values = [$adId, $eventType, $placementCode];

    if (table_has_column('ad_events', 'ip_hash')) {
        $columns[] = 'ip_hash';
        $values[] = privacy_request_ip_hash() ?: null;
    }
    if (table_has_column('ad_events', 'user_agent_hash')) {
        $columns[] = 'user_agent_hash';
        $values[] = privacy_request_user_agent_hash() ?: null;
    }

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $columnSql = implode(', ', array_map(static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`', $columns));
    db()->prepare('INSERT INTO ad_events (' . $columnSql . ') VALUES (' . $placeholders . ')')
        ->execute($values);
}

function handle_ad_image_upload(?array $upload, string $callsign, string $existingPath = ''): ?string
{
    if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingPath !== '' ? $existingPath : null;
    }

    $baseDir = dirname(__DIR__) . '/storage/uploads/ads';
    $saved = secure_move_uploaded_file(
        $upload,
        $baseDir,
        slugify($callsign !== '' ? $callsign : 'ad'),
        ['jpg', 'jpeg', 'png', 'webp'],
        ['image/jpeg', 'image/png', 'image/webp'],
        6 * 1024 * 1024
    );

    return 'storage/uploads/ads/' . $saved;
}
