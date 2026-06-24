<?php
declare(strict_types=1);

require_once __DIR__ . '/privacy_helpers.php';

/**
 * @return array<string, array{label:string,width:int,height:int}>
 */
function ad_format_catalog(?string $locale = null): array
{
    $t = ad_i18n_messages($locale);

    return [
        'square' => ['label' => ad_i18n_text($t, 'format_square'), 'width' => 1080, 'height' => 1080],
        'landscape' => ['label' => ad_i18n_text($t, 'format_landscape'), 'width' => 1200, 'height' => 628],
        'portrait' => ['label' => ad_i18n_text($t, 'format_portrait'), 'width' => 1080, 'height' => 1350],
    ];
}

/**
 * @return array<string, string>
 */
function ad_i18n_messages(?string $locale = null): array
{
    return i18n_domain_locale('ads', $locale ?? current_locale());
}

/**
 * @param array<string, string> $messages
 */
function ad_i18n_text(array $messages, string $key): string
{
    return (string) $messages[$key];
}

function ad_format_label(string $formatCode, ?string $locale = null): string
{
    $catalog = ad_format_catalog($locale);
    return (string) ($catalog[$formatCode]['label'] ?? $formatCode);
}

function ad_status_label(string $status, ?string $locale = null): string
{
    $messages = ad_i18n_messages($locale);
    $key = 'status_' . $status . '_label';

    return (string) ($messages[$key] ?? ucfirst($status));
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
function available_ad_placements(?string $locale = null): array
{
    if (table_exists('ad_placements')) {
        try {
            $rows = db()->query('SELECT id, code, name, description, sort_order, is_active FROM ad_placements WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll() ?: [];
            if ($rows !== []) {
                return array_map(static function (array $row): array {
                    $name = (string) ($row['name'] ?? '');

                    return [
                        'id' => (int) ($row['id'] ?? 0),
                        'code' => (string) ($row['code'] ?? ''),
                        'name' => $name,
                        'label' => $name,
                        'description' => (string) ($row['description'] ?? ''),
                        'sort_order' => (int) ($row['sort_order'] ?? 0),
                        'is_active' => (int) ($row['is_active'] ?? 1),
                    ];
                }, $rows);
            }
        } catch (Throwable) {
            // Static fallback keeps read-only pages usable when the schema is unavailable.
        }
    }

    $t = ad_i18n_messages($locale);
    $placements = [
        ['code' => 'homepage_top', 'name_key' => 'placement_homepage_top_name', 'description_key' => 'placement_homepage_top_description', 'sort_order' => 10],
        ['code' => 'sidebar', 'name_key' => 'placement_sidebar_name', 'description_key' => 'placement_sidebar_description', 'sort_order' => 20],
        ['code' => 'article_inline', 'name_key' => 'placement_article_inline_name', 'description_key' => 'placement_article_inline_description', 'sort_order' => 30],
    ];

    return array_map(static function (array $placement) use ($t): array {
        $name = ad_i18n_text($t, (string) $placement['name_key']);

        return [
            'id' => 0,
            'code' => (string) $placement['code'],
            'name' => $name,
            'label' => $name,
            'description' => ad_i18n_text($t, (string) $placement['description_key']),
            'sort_order' => (int) $placement['sort_order'],
            'is_active' => 1,
        ];
    }, $placements);
}

/**
 * @return array<int, array{code:string,label:string}>
 */
function ad_placements_for_member(int $memberId): array
{
    return available_ad_placements();
}

/**
 * @return array{impressions:int,clicks:int,ctr:float,unique_viewers:int}
 */
function ad_summary_stats(int $adId): array
{
    if ($adId <= 0 || !table_exists('ad_events')) {
        return ['impressions' => 0, 'clicks' => 0, 'ctr' => 0.0, 'unique_viewers' => 0];
    }

    try {
        $stmt = db()->prepare('SELECT SUM(event_type = "impression") AS impressions, SUM(event_type = "click") AS clicks FROM ad_events WHERE ad_id = ?');
        $stmt->execute([$adId]);
        $row = $stmt->fetch() ?: [];
        $impressions = (int) ($row['impressions'] ?? 0);
        $clicks = (int) ($row['clicks'] ?? 0);
        $uniqueViewers = 0;
        if (table_has_column('ad_events', 'ip_hash')) {
            $uniqueStmt = db()->prepare('SELECT COUNT(DISTINCT ip_hash) FROM ad_events WHERE ad_id = ? AND ip_hash IS NOT NULL AND ip_hash <> ""');
            $uniqueStmt->execute([$adId]);
            $uniqueViewers = (int) ($uniqueStmt->fetchColumn() ?: 0);
        }

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0,
            'unique_viewers' => $uniqueViewers,
        ];
    } catch (Throwable) {
        return ['impressions' => 0, 'clicks' => 0, 'ctr' => 0.0, 'unique_viewers' => 0];
    }
}

/**
 * @return array<int, array<string,mixed>>
 */
function member_ads(int $memberId, bool $ownerOnly = true): array
{
    if (!table_exists('ads')) {
        return [];
    }

    if ($ownerOnly) {
        $stmt = db()->prepare('SELECT a.*, ap.code AS placement_code, ap.name AS placement_name, m.callsign AS owner_callsign FROM ads a INNER JOIN ad_placements ap ON ap.id = a.placement_id LEFT JOIN members m ON m.id = a.owner_member_id WHERE a.owner_member_id = ? ORDER BY a.updated_at DESC, a.id DESC');
        $stmt->execute([$memberId]);
        $rows = $stmt->fetchAll() ?: [];
    } else {
        $rows = db()->query('SELECT a.*, ap.code AS placement_code, ap.name AS placement_name, m.callsign AS owner_callsign FROM ads a INNER JOIN ad_placements ap ON ap.id = a.placement_id LEFT JOIN members m ON m.id = a.owner_member_id ORDER BY a.updated_at DESC, a.id DESC')->fetchAll() ?: [];
    }

    foreach ($rows as &$row) {
        $row['runtime_status'] = ad_runtime_status($row);
        $row['placement_name'] = (string) ($row['placement_name'] ?? $row['placement_code'] ?? '');
        $row['owner_callsign'] = (string) ($row['owner_callsign'] ?? '');
        $row['stats'] = ad_summary_stats((int) ($row['id'] ?? 0));
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
    $stmt = db()->prepare('SELECT a.*, ap.code AS placement_code, ap.name AS placement_name, m.callsign AS owner_callsign FROM ads a INNER JOIN ad_placements ap ON ap.id = a.placement_id LEFT JOIN members m ON m.id = a.owner_member_id WHERE a.id = ? LIMIT 1');
    $stmt->execute([$adId]);
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return null;
    }
    $row['runtime_status'] = ad_runtime_status($row);
    $row['stats'] = ad_summary_stats((int) ($row['id'] ?? 0));
    $row['summary'] = $row['stats'];

    return $row;
}

/**
 * @return array<int, array{event_date:string,placement_code:string,impressions:int,clicks:int,ctr:float}>
 */
function ad_daily_stats(int $adId): array
{
    if ($adId <= 0 || !table_exists('ad_events')) {
        return [];
    }
    $stmt = db()->prepare('SELECT DATE(created_at) AS event_date, placement_code, SUM(event_type = "impression") AS impressions, SUM(event_type = "click") AS clicks FROM ad_events WHERE ad_id = ? GROUP BY DATE(created_at), placement_code ORDER BY event_date DESC LIMIT 30');
    $stmt->execute([$adId]);
    $rows = $stmt->fetchAll() ?: [];
    foreach ($rows as &$row) {
        $impressions = (int) ($row['impressions'] ?? 0);
        $clicks = (int) ($row['clicks'] ?? 0);
        $row['impressions'] = $impressions;
        $row['clicks'] = $clicks;
        $row['ctr'] = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0;
    }
    unset($row);

    return $rows;
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
