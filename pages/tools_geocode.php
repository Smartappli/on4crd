<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$query = trim((string) ($_GET['q'] ?? ''));
if ($query === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Paramètre q manquant.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
    echo json_encode(['ok' => false, 'error' => 'Service de géocodage indisponible.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

$rows = json_decode($response, true);
if (!is_array($rows) || $rows === []) {
    echo json_encode(['ok' => false, 'error' => 'Adresse introuvable.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

$row = $rows[0] ?? [];
$lat = isset($row['lat']) ? (float) $row['lat'] : null;
$lon = isset($row['lon']) ? (float) $row['lon'] : null;
if (!is_float($lat) || !is_float($lon)) {
    echo json_encode(['ok' => false, 'error' => 'Coordonnées invalides reçues.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

echo json_encode([
    'ok' => true,
    'display_name' => (string) ($row['display_name'] ?? $query),
    'lat' => $lat,
    'lon' => $lon,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

