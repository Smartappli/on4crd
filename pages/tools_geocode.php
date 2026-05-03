<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$locale = current_locale();
$i18n = [
    'fr' => ['missing_q' => $t('missing_q'), 'service_down' => $t('service_down'), 'address_not_found' => $t('address_not_found'), 'invalid_coords' => $t('invalid_coords')],
    'en' => ['missing_q' => 'Missing q parameter.', 'service_down' => 'Geocoding service unavailable.', 'address_not_found' => 'Address not found.', 'invalid_coords' => 'Invalid coordinates received.'],
    'de' => ['missing_q' => 'Fehlender q-Parameter.', 'service_down' => 'Geokodierungsdienst nicht verfügbar.', 'address_not_found' => 'Adresse nicht gefunden.', 'invalid_coords' => 'Ungültige Koordinaten empfangen.'],
    'nl' => ['missing_q' => 'Ontbrekende q-parameter.', 'service_down' => 'Geocoderingsservice niet beschikbaar.', 'address_not_found' => 'Adres niet gevonden.', 'invalid_coords' => 'Ongeldige coördinaten ontvangen.'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
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

echo json_encode([
    'ok' => true,
    'display_name' => (string) ($row['display_name'] ?? $query),
    'lat' => $lat,
    'lon' => $lon,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

