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

$sendError = static function (int $status, string $key) use ($t): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $t($key)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    $sendError(405, 'method_not_allowed');
    return;
}

$query = trim((string) ($_GET['q'] ?? ''));
if ($query === '') {
    $sendError(400, 'missing_q');
    return;
}

$query = preg_replace('/\s+/u', ' ', $query) ?? $query;
if (strlen($query) > 160 || preg_match('/[\x00-\x1F\x7F]/', $query)) {
    $sendError(400, 'invalid_q');
    return;
}

$cacheKey = 'tools_geocode_' . sha1(strtolower($query));
$cacheMiss = new stdClass();
$cachedPayload = function_exists('cache_get') ? cache_get($cacheKey, $cacheMiss) : $cacheMiss;
if (is_array($cachedPayload)) {
    echo json_encode($cachedPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

$rateLimitWindow = 60;
$rateLimitMaxRequests = 12;
$rateLimitDirectory = function_exists('cache_dir_path') ? cache_dir_path() : dirname(__DIR__) . '/storage/cache';
if (!is_dir($rateLimitDirectory)) {
    @mkdir($rateLimitDirectory, 0775, true);
}
$clientIp = function_exists('client_ip_address') ? client_ip_address() : (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$rateLimitFile = rtrim($rateLimitDirectory, '/\\') . DIRECTORY_SEPARATOR . 'geocode-' . hash('sha256', $clientIp) . '.json';
$now = time();
$rateLimitState = ['count' => 0, 'window_start' => $now];
if (is_file($rateLimitFile)) {
    $decoded = json_decode((string) @file_get_contents($rateLimitFile), true);
    if (is_array($decoded)) {
        $rateLimitState = array_merge($rateLimitState, $decoded);
    }
}
if (($now - (int) ($rateLimitState['window_start'] ?? 0)) >= $rateLimitWindow) {
    $rateLimitState = ['count' => 0, 'window_start' => $now];
}
$rateLimitState['count'] = (int) ($rateLimitState['count'] ?? 0) + 1;
@file_put_contents($rateLimitFile, json_encode($rateLimitState, JSON_UNESCAPED_SLASHES), LOCK_EX);
if ((int) $rateLimitState['count'] > $rateLimitMaxRequests) {
    header('Retry-After: ' . max(1, $rateLimitWindow - ($now - (int) ($rateLimitState['window_start'] ?? $now))));
    $sendError(429, 'rate_limited');
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
    $sendError(502, 'service_down');
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
$addressDetails = isset($row['address']) && is_array($row['address']) ? $row['address'] : [];
$countryCode = strtoupper(trim((string) ($addressDetails['country_code'] ?? '')));
$country = $countryCode !== '' ? $countryCode : (string) ($addressDetails['country'] ?? '');
$zones = member_profile_radio_zones_for_coordinates($lat, $lon, $country);

$payload = [
    'ok' => true,
    'display_name' => (string) ($row['display_name'] ?? $query),
    'lat' => $lat,
    'lon' => $lon,
    'locator' => $locator,
    'cq_zone' => $zones['cq_zone'] ?? null,
    'itu_zone' => $zones['itu_zone'] ?? null,
];
if (function_exists('cache_set')) {
    cache_set($cacheKey, $payload, 7 * 24 * 60 * 60);
}

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
