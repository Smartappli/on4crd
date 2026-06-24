<?php
declare(strict_types=1);

require_once __DIR__ . '/widget_radio_helpers.php';
require_once __DIR__ . '/widget_i18n.php';

if (!function_exists('ham_agromet_hourly_url')) {
function ham_agromet_hourly_url(?DateTimeImmutable $now = null): string
{
    $baseUrl = rtrim((string) env('AGROMET_API_BASE_URL', 'https://agromet.be/fr/agromet/api/v3/get_pameseb_hourly'), '/');
    $sensors = preg_replace('/[^a-z0-9_,]/i', '', (string) env('AGROMET_SENSORS', 'tsa,plu,hra,vvt'));
    $stations = preg_replace('/[^a-z0-9_,]/i', '', (string) env('AGROMET_STATION_SIDS', '1,26'));
    $sensors = $sensors !== '' ? $sensors : 'tsa,plu,hra,vvt';
    $stations = $stations !== '' ? $stations : '1,26';
    $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('Europe/Brussels'));
    $to = $now->format('Y-m-d');
    $from = $now->sub(new DateInterval('P1D'))->format('Y-m-d');

    return $baseUrl . '/' . $sensors . '/' . $stations . '/' . $from . '/' . $to . '/';
}
}

if (!function_exists('ham_agromet_api_json')) {
function ham_agromet_api_json(string $url, string $token): ?array
{
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        sprintf('Authorization: Token %s', $token),
        'User-Agent: ON4CRD-Agromet/1.0',
    ];

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        if ($curl === false) {
            return null;
        }
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
        ]);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if (!is_string($raw) || trim($raw) === '' || $status >= 400) {
            return null;
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => implode("\r\n", $headers) . "\r\n",
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}
}

if (!function_exists('ham_agromet_row_timestamp')) {
function ham_agromet_row_timestamp(array $row): string
{
    foreach (['timestamp', 'time', 'datetime', 'date_time', 'date', 'dt', 'moment'] as $key) {
        $value = $row[$key] ?? null;
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }

    foreach ($row as $value) {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1) {
            return trim($value);
        }
    }

    return '';
}
}

if (!function_exists('ham_agromet_row_station')) {
function ham_agromet_row_station(array $row): string
{
    foreach (['station_sid', 'station_id', 'sid', 'station', 'point'] as $key) {
        $value = $row[$key] ?? null;
        if (is_scalar($value) && trim((string) $value) !== '') {
            return trim((string) $value);
        }
        if (is_array($value)) {
            foreach (['sid', 'id', 'code', 'name'] as $nestedKey) {
                $nestedValue = $value[$nestedKey] ?? null;
                if (is_scalar($nestedValue) && trim((string) $nestedValue) !== '') {
                    return trim((string) $nestedValue);
                }
            }
        }
    }

    return 'default';
}
}

if (!function_exists('ham_agromet_numeric_value')) {
function ham_agromet_numeric_value(array $row, string $sensor): ?float
{
    $sensor = strtolower($sensor);
    foreach ([$sensor, strtoupper($sensor), $sensor . '_value', 'value_' . $sensor] as $key) {
        $value = $row[$key] ?? null;
        if (is_numeric($value)) {
            return (float) $value;
        }
    }

    foreach (['data', 'values', 'measurements'] as $containerKey) {
        $container = $row[$containerKey] ?? null;
        if (is_array($container)) {
            if (array_is_list($container)) {
                foreach ($container as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $value = ham_agromet_numeric_value($item, $sensor);
                    if ($value !== null) {
                        return $value;
                    }
                }
            } else {
                $value = ham_agromet_numeric_value($container, $sensor);
                if ($value !== null) {
                    return $value;
                }
            }
        }
    }

    $rowSensorRaw = $row['sensor'] ?? $row['code'] ?? $row['parameter'] ?? $row['variable'] ?? '';
    if (is_array($rowSensorRaw)) {
        $rowSensorRaw = $rowSensorRaw['code'] ?? $rowSensorRaw['name'] ?? $rowSensorRaw['id'] ?? '';
    }
    $rowSensor = is_scalar($rowSensorRaw) ? strtolower(trim((string) $rowSensorRaw)) : '';
    if ($rowSensor === $sensor) {
        foreach (['value', 'valeur', 'measurement', 'mesure'] as $valueKey) {
            $value = $row[$valueKey] ?? null;
            if (is_numeric($value)) {
                return (float) $value;
            }
        }
    }

    return null;
}
}

if (!function_exists('ham_agromet_current_weather')) {
function ham_agromet_current_weather(array $payload): ?array
{
    $rows = $payload['results'] ?? $payload;
    if (!is_array($rows)) {
        return null;
    }

    $buckets = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $timestamp = ham_agromet_row_timestamp($row);
        $bucketKey = $timestamp . '|' . ham_agromet_row_station($row);
        $buckets[$bucketKey] ??= ['timestamp' => $timestamp, 'values' => []];
        foreach (['tsa', 'hra', 'plu', 'vvt'] as $sensor) {
            $value = ham_agromet_numeric_value($row, $sensor);
            if ($value !== null) {
                $buckets[$bucketKey]['values'][$sensor] = $value;
            }
        }
    }

    $bestBucket = null;
    foreach ($buckets as $bucket) {
        if (($bucket['values'] ?? []) === []) {
            continue;
        }
        if ($bestBucket === null) {
            $bestBucket = $bucket;
            continue;
        }
        $bucketTime = strtotime((string) ($bucket['timestamp'] ?? '')) ?: 0;
        $bestTime = strtotime((string) ($bestBucket['timestamp'] ?? '')) ?: 0;
        $bucketCount = count((array) ($bucket['values'] ?? []));
        $bestCount = count((array) ($bestBucket['values'] ?? []));
        if ($bucketTime > $bestTime || ($bucketTime === $bestTime && $bucketCount > $bestCount)) {
            $bestBucket = $bucket;
        }
    }

    if (!is_array($bestBucket)) {
        return null;
    }

    $values = (array) ($bestBucket['values'] ?? []);
    $current = [];
    if (isset($values['tsa'])) {
        $current['temperature_2m'] = (float) $values['tsa'];
    }
    if (isset($values['hra'])) {
        $current['relative_humidity_2m'] = (int) round((float) $values['hra']);
    }
    if (isset($values['plu'])) {
        $current['precipitation'] = (float) $values['plu'];
    }
    if (isset($values['vvt'])) {
        $current['wind_speed_10m'] = (float) $values['vvt'] * 3.6;
    }
    if ((string) ($bestBucket['timestamp'] ?? '') !== '') {
        $current['time'] = (string) $bestBucket['timestamp'];
    }

    return $current !== [] ? $current : null;
}
}

if (!function_exists('render_ham_weather_advice')) {
function render_ham_weather_advice(array $user = []): string
{
    $locale = current_locale();
    $i18n = dashboard_widget_i18n_messages($locale);
    $propagationLabel = match ($locale) {
        'en' => 'HF propagation:',
        'de' => 'HF-Ausbreitung:',
        'nl' => 'HF-propagatie:',
        'es' => 'Propagacion HF:',
        'it' => 'Propagazione HF:',
        'pt' => 'Propagacao HF:',
        default => 'Propagation HF :',
    };
    $propagationStates = match ($locale) {
        'en' => ['quiet' => 'quiet', 'usable' => 'usable', 'disturbed' => 'disturbed', 'stormy' => 'stormy', 'unavailable' => 'unavailable'],
        'de' => ['quiet' => 'ruhig', 'usable' => 'nutzbar', 'disturbed' => 'gestoert', 'stormy' => 'stuermisch', 'unavailable' => 'nicht verfuegbar'],
        'nl' => ['quiet' => 'rustig', 'usable' => 'bruikbaar', 'disturbed' => 'verstoord', 'stormy' => 'stormachtig', 'unavailable' => 'niet beschikbaar'],
        'es' => ['quiet' => 'tranquila', 'usable' => 'utilizable', 'disturbed' => 'perturbada', 'stormy' => 'tormentosa', 'unavailable' => 'no disponible'],
        'it' => ['quiet' => 'calma', 'usable' => 'utilizzabile', 'disturbed' => 'disturbata', 'stormy' => 'tempestosa', 'unavailable' => 'non disponibile'],
        'pt' => ['quiet' => 'calma', 'usable' => 'utilizavel', 'disturbed' => 'perturbada', 'stormy' => 'tempestuosa', 'unavailable' => 'indisponivel'],
        default => ['quiet' => 'calme', 'usable' => 'exploitable', 'disturbed' => 'perturbee', 'stormy' => 'orage magnetique', 'unavailable' => 'indisponible'],
    };
    $defaultLocator = 'JO20LI';
    $memberLocator = strtoupper(trim((string) ($user['locator'] ?? '')));
    if ($memberLocator === '' && isset($user['id']) && is_numeric($user['id']) && table_exists('members')) {
        try {
            $stmt = db()->prepare('SELECT locator, qth FROM members WHERE id = ? LIMIT 1');
            $stmt->execute([(int) $user['id']]);
            $row = $stmt->fetch();
            if (is_array($row)) {
                $candidateLocator = strtoupper(trim((string) ($row['locator'] ?? '')));
                if ($candidateLocator === '') {
                    $candidateLocator = strtoupper(trim((string) ($row['qth'] ?? '')));
                }
                if ($candidateLocator !== '' && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $candidateLocator) === 1) {
                    $memberLocator = $candidateLocator;
                }
            }
        } catch (Throwable) {
            // Keep fallback behavior when member profile location cannot be read.
        }
    }
    $locator = $memberLocator !== '' ? $memberLocator : $defaultLocator;
    $coordinates = maidenhead_to_coordinates($locator) ?? ['latitude' => 50.3150, 'longitude' => 4.9452];

    $currentWeather = null;
    $agrometToken = trim((string) env('AGROMET_API_TOKEN', ''));
    if ($agrometToken !== '') {
        $agrometUrl = ham_agromet_hourly_url();
        $agrometPayload = cache_remember('ham:advice:weather:agromet:' . hash('sha256', $agrometUrl), 300, static function () use ($agrometUrl, $agrometToken): ?array {
            return ham_agromet_api_json($agrometUrl, $agrometToken);
        });
        $currentWeather = is_array($agrometPayload) ? ham_agromet_current_weather($agrometPayload) : null;
    }

    if (!is_array($currentWeather)) {
        $weatherUrl = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
            'latitude' => number_format((float) $coordinates['latitude'], 4, '.', ''),
            'longitude' => number_format((float) $coordinates['longitude'], 4, '.', ''),
            'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code,cloud_cover,precipitation',
            'timezone' => 'auto',
        ]);
        $weatherPayload = cache_remember('ham:advice:weather:' . hash('sha256', $weatherUrl), 300, static function () use ($weatherUrl): ?array {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 6,
                    'header' => "Accept: application/json\r\nUser-Agent: ON4CRD-Propagation/1.0\r\n",
                ],
            ]);
            $raw = @file_get_contents($weatherUrl, false, $context);
            if (!is_string($raw) || trim($raw) === '') {
                return null;
            }
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : null;
        });
        $currentWeather = is_array($weatherPayload) && is_array($weatherPayload['current'] ?? null) ? $weatherPayload['current'] : [];
    }

    $kpPayload = cache_remember('ham:advice:kp', 300, static function (): ?array {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 6,
                'header' => "Accept: application/json\r\nUser-Agent: ON4CRD-Propagation/1.0\r\n",
            ],
        ]);
        $raw = @file_get_contents('https://services.swpc.noaa.gov/products/noaa-planetary-k-index.json', false, $context);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    });

    $temperature = is_numeric($currentWeather['temperature_2m'] ?? null) ? (float) $currentWeather['temperature_2m'] : 15.0;
    $wind = is_numeric($currentWeather['wind_speed_10m'] ?? null) ? (float) $currentWeather['wind_speed_10m'] : 10.0;
    $weatherCode = (int) ($currentWeather['weather_code'] ?? -1);
    $localTime = trim((string) ($currentWeather['time'] ?? ''));
    $hour = (int) gmdate('G');
    if ($localTime !== '') {
        try {
            $dtLocal = new DateTimeImmutable($localTime);
            $hour = (int) $dtLocal->format('G');
        } catch (Throwable $throwable) {
            $hour = (int) gmdate('G');
        }
    }

    $humidity = is_numeric($currentWeather['relative_humidity_2m'] ?? null) ? (int) $currentWeather['relative_humidity_2m'] : 60;
    $cloudCover = is_numeric($currentWeather['cloud_cover'] ?? null) ? (int) $currentWeather['cloud_cover'] : 45;
    $precipitation = is_numeric($currentWeather['precipitation'] ?? null) ? (float) $currentWeather['precipitation'] : 0.0;
    $measurement = is_array($kpPayload) ? extract_latest_kp_measurement($kpPayload) : null;
    $kp = is_array($measurement) ? (float) ($measurement['kp'] ?? 3.0) : null;
    $kpTrend = is_array($kpPayload) ? extract_kp_trend($kpPayload) : null;
    $kpTrendForScoring = is_numeric($kpTrend) ? (float) $kpTrend : 0.0;
    $kpForScoring = is_numeric($kp) ? (float) $kp : 3.0;
    $propagationStateKey = match (true) {
        !is_numeric($kp) => 'unavailable',
        $kpForScoring <= 2.0 && $kpTrendForScoring < 0.8 => 'quiet',
        $kpForScoring <= 4.0 => 'usable',
        $kpForScoring <= 5.0 => 'disturbed',
        default => 'stormy',
    };
    $propagationSummary = (string) ($propagationStates[$propagationStateKey] ?? $propagationStates['unavailable']);
    if (is_numeric($kp)) {
        $propagationSummary .= ' (Kp ' . number_format((float) $kp, 1, ',', '') . ')';
    }

    $month = (int) gmdate('n');
    if ($localTime !== '') {
        try {
            $month = (int) (new DateTimeImmutable($localTime))->format('n');
        } catch (Throwable $throwable) {
            $month = (int) gmdate('n');
        }
    }
    $isSummer = $month >= 4 && $month <= 9;
    $isDaytime = $hour >= 7 && $hour <= 16;
    $isLateEvening = $hour >= 20 || $hour <= 5;

    $hfScore = 65.0;
    $hfScore += $kpForScoring <= 1.5 ? 20.0 : ($kpForScoring <= 3.0 ? 10.0 : ($kpForScoring <= 4.5 ? 1.0 : -20.0));
    $hfScore += $kpTrendForScoring <= -0.8 ? 6.0 : ($kpTrendForScoring >= 0.8 ? -8.0 : 0.0);
    $hfScore += $isDaytime ? 10.0 : -4.0;
    $hfScore += ($wind <= 18.0 ? 8.0 : ($wind <= 30.0 ? 2.0 : -10.0));
    $hfScore += ($humidity >= 35 && $humidity <= 85) ? 3.0 : -5.0;
    $hfScore += ($cloudCover <= 45 ? 2.0 : ($cloudCover >= 90 ? -4.0 : 0.0));
    $hfScore += ($precipitation <= 0.1 ? 2.0 : ($precipitation >= 2.5 ? -8.0 : -3.0));
    $hfScore += in_array($weatherCode, [95, 96, 99], true) ? -16.0 : 0.0;
    $hfScore += $isSummer && $isDaytime ? 4.0 : 0.0;
    $hfScore += !$isSummer && $isLateEvening ? 4.0 : 0.0;

    $bands = ['40m', '20m', '15m'];
    if ($hour >= 8 && $hour <= 15 && $kpForScoring <= 3.5 && $isSummer) {
        $bands = ['20m', '17m', '15m'];
    } elseif ($hour >= 10 && $hour <= 17 && $kpForScoring <= 2.5 && $isSummer) {
        $bands = ['15m', '12m', '10m'];
    } elseif ($hour >= 18 || $hour <= 6) {
        $bands = ['40m', '80m', '30m'];
        if (!$isSummer && $kpForScoring <= 4.0) {
            $bands = ['80m', '40m', '30m'];
        }
    } elseif ($kpForScoring >= 5.0) {
        $bands = ['40m', '30m', '20m'];
    }

    $modes = ['SSB', 'CW'];
    if ($kpForScoring >= 4.5 || $wind >= 35.0 || $precipitation >= 2.0 || in_array($weatherCode, [95, 96, 99], true)) {
        $modes = ['FT8', 'CW', 'RTTY'];
    } elseif ($temperature < 5.0 || $humidity > 90) {
        $modes = ['FT8', 'SSB', 'CW'];
    }

    $text = static fn (string $key): string => (string) ($i18n['ham_weather_' . $key] ?? $key);
    $scoreLabel = $hfScore >= 80 ? $text('score_excellent') : ($hfScore >= 60 ? $text('score_good') : ($hfScore >= 45 ? $text('score_variable') : $text('score_difficult')));
    $timeWindow = $hour >= 8 && $hour <= 15 ? $text('window_day') : ($hour >= 16 && $hour <= 21 ? $text('window_evening') : $text('window_night'));

    return '<div class="grid gap-4">'
        . '<section>'
        . '<ul class="mt-2 list-clean">'
        . '<li><strong>' . e($scoreLabel) . '</strong> ' . e($text('for_qso')) . ' (score ' . e((string) max(0, min(100, (int) round($hfScore)))) . '/100)</li>'
        . '<li><strong>' . e($text('bands')) . '</strong> ' . e(implode(' • ', $bands)) . '</li>'
        . '<li><strong>' . e($text('modes')) . '</strong> ' . e(implode(' • ', $modes)) . '</li>'
        . '<li><strong>' . e($text('window')) . '</strong> ' . e($timeWindow) . '</li>'
        . '<li><strong>' . e($propagationLabel) . '</strong> ' . e($propagationSummary) . '</li>'
        . '</ul>'
        . '</section>'
        . '</div>';
}
}
