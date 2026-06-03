<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/tools_geocode.php';
$i18n = i18n_expand_supported_locales($i18n);
$resolved = [];
foreach (array_keys($i18n['fr']) as $key) {
    $pool = [];
    foreach ($i18n as $lang => $translations) {
        if (isset($translations[$key]) && is_string($translations[$key])) {
            $pool[$lang] = $translations[$key];
        }
    }
    $resolved[$key] = i18n_localized_value($pool, $locale, 'fr');
}
$t = static function (string $key) use ($resolved): string {
    return (string) ($resolved[$key] ?? $key);
};

$query = trim((string) ($_GET['q'] ?? ''));
if ($query === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $t('missing_q')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

$url = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=1&q=' . rawurlencode($query);
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 12,
        'header' => "Accept: application/json\r\nUser-Agent: ON4CRD-Tools/1.0\r\n",
    ],
]);

$response = @file_get_contents($url, false, $context);
if ($response === false) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => $t('service_down')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

$rows = json_decode($response, true);
if (!is_array($rows) || $rows === []) {
    echo json_encode(['ok' => false, 'error' => $t('address_not_found')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

$row = $rows[0] ?? [];
$lat = isset($row['lat']) ? (float) $row['lat'] : null;
$lon = isset($row['lon']) ? (float) $row['lon'] : null;
if (!is_float($lat) || !is_float($lon)) {
    echo json_encode(['ok' => false, 'error' => $t('invalid_coords')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

$locator = coordinates_to_maidenhead($lat, $lon, 6);
$zones = member_profile_radio_zones_for_coordinates($lat, $lon);

echo json_encode([
    'ok' => true,
    'display_name' => (string) ($row['display_name'] ?? $query),
    'lat' => $lat,
    'lon' => $lon,
    'locator' => $locator,
    'cq_zone' => $zones['cq_zone'] ?? null,
    'itu_zone' => $zones['itu_zone'] ?? null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
