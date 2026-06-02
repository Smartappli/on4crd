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
