<?php
declare(strict_types=1);

if (!function_exists('maidenhead_to_coordinates')) {
function maidenhead_to_coordinates(string $locator): ?array
{
    $normalized = strtoupper(trim($locator));
    if (preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $normalized) !== 1) {
        return null;
    }

    $lon = -180.0;
    $lat = -90.0;

    $lon += (ord($normalized[0]) - ord('A')) * 20.0;
    $lat += (ord($normalized[1]) - ord('A')) * 10.0;
    $lon += ((int) $normalized[2]) * 2.0;
    $lat += (int) $normalized[3];

    $lonStep = 2.0;
    $latStep = 1.0;

    if (strlen($normalized) >= 6) {
        $lon += (ord($normalized[4]) - ord('A')) * (5.0 / 60.0);
        $lat += (ord($normalized[5]) - ord('A')) * (2.5 / 60.0);
        $lonStep = 0.0;
        $latStep = 2.5 / 60.0;
    }

    return [
        'latitude' => $lat + ($latStep / 2.0),
        'longitude' => $lon + ($lonStep / 2.0),
    ];
}

function coordinates_to_maidenhead(float $latitude, float $longitude, int $length = 6): ?string
{
    if (!is_finite($latitude) || !is_finite($longitude) || $latitude < -90.0 || $latitude > 90.0 || $longitude < -180.0 || $longitude > 180.0) {
        return null;
    }

    $length = $length >= 6 ? 6 : 4;
    $latitude = min(89.999999, max(-90.0, $latitude));
    $longitude = min(179.999999, max(-180.0, $longitude));

    $adjustedLongitude = $longitude + 180.0;
    $adjustedLatitude = $latitude + 90.0;

    $fieldLongitude = (int) floor($adjustedLongitude / 20.0);
    $fieldLatitude = (int) floor($adjustedLatitude / 10.0);
    $adjustedLongitude -= $fieldLongitude * 20.0;
    $adjustedLatitude -= $fieldLatitude * 10.0;

    $squareLongitude = (int) floor($adjustedLongitude / 2.0);
    $squareLatitude = (int) floor($adjustedLatitude);
    $locator = chr(ord('A') + $fieldLongitude)
        . chr(ord('A') + $fieldLatitude)
        . (string) $squareLongitude
        . (string) $squareLatitude;

    if ($length === 4) {
        return $locator;
    }

    $adjustedLongitude -= $squareLongitude * 2.0;
    $adjustedLatitude -= $squareLatitude;

    $subsquareLongitude = (int) floor($adjustedLongitude / (5.0 / 60.0));
    $subsquareLatitude = (int) floor($adjustedLatitude / (2.5 / 60.0));

    return $locator
        . chr(ord('A') + max(0, min(23, $subsquareLongitude)))
        . chr(ord('A') + max(0, min(23, $subsquareLatitude)));
}

function extract_kp_measurement_from_row(array $row): ?array
{
    $timestamp = trim((string) ($row['time_tag'] ?? $row['time'] ?? $row['timestamp'] ?? $row[0] ?? ''));
    $kpValue = $row['Kp'] ?? $row['kp'] ?? $row['kp_index'] ?? $row[1] ?? null;
    if (is_string($kpValue)) {
        $kpValue = str_replace(',', '.', trim($kpValue));
    }
    if ($timestamp === '' || !is_numeric($kpValue)) {
        return null;
    }

    return [
        'timestamp' => $timestamp,
        'kp' => (float) $kpValue,
    ];
}

function extract_latest_kp_measurement(array $payload): ?array
{
    if ($payload === []) {
        return null;
    }

    for ($index = count($payload) - 1; $index >= 0; $index--) {
        $row = $payload[$index] ?? null;
        if (!is_array($row)) {
            continue;
        }

        $measurement = extract_kp_measurement_from_row($row);
        if ($measurement === null) {
            continue;
        }

        return $measurement;
    }

    return null;
}

function extract_kp_trend(array $payload, int $comparisonOffset = 3): ?float
{
    if ($payload === []) {
        return null;
    }

    $comparisonOffset = max(1, $comparisonOffset);
    $measurements = [];
    foreach ($payload as $row) {
        if (!is_array($row)) {
            continue;
        }

        $measurement = extract_kp_measurement_from_row($row);
        if ($measurement !== null) {
            $measurements[] = $measurement;
        }
    }

    if (count($measurements) <= $comparisonOffset) {
        return null;
    }

    $latest = $measurements[count($measurements) - 1];
    $older = $measurements[count($measurements) - 1 - $comparisonOffset];

    return ((float) $latest['kp']) - ((float) $older['kp']);
}

function kp_trend_summary(?float $trend, string $locale): ?string
{
    if (!is_numeric($trend)) {
        return null;
    }

    $labels = match ($locale) {
        'en' => ['trend' => 'Trend:', 'rising' => 'rising', 'falling' => 'falling', 'stable' => 'stable'],
        default => ['trend' => 'Tendance :', 'rising' => 'en hausse', 'falling' => 'en baisse', 'stable' => 'stable'],
    };
    $state = match (true) {
        $trend >= 0.4 => 'rising',
        $trend <= -0.4 => 'falling',
        default => 'stable',
    };
    $delta = ($trend > 0 ? '+' : '') . number_format($trend, 1, ',', '');

    return $labels['trend'] . ' ' . $labels[$state] . ' (Δ ' . $delta . ')';
}
}
