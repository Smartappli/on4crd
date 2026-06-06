<?php
declare(strict_types=1);

require_once __DIR__ . '/hamqsl_widgets.php';
require_once __DIR__ . '/ham_weather_advice.php';
require_once __DIR__ . '/widget_radio_helpers.php';

if (!function_exists('render_widget')) {
function render_widget(string $slug, array $user = []): string
{
    $safeSlug = strtolower(trim($slug));
    $callsign = trim((string) ($user['callsign'] ?? 'OM'));
    $locale = current_locale();

    if (hamqsl_widget_variant($safeSlug) !== null) {
        return render_hamqsl_widget($safeSlug);
    }

    switch ($safeSlug) {
        case 'welcome':
            return '<p>Bonjour <strong>' . e($callsign) . '</strong>, bienvenue dans votre espace membre.</p>'
                . '<p class="help">Personnalisez ce tableau de bord en ajoutant, supprimant et déplaçant vos widgets.</p>';
        case 'chatbot':
            return '<p class="help">Posez vos questions à Raymond sur la radio, le club et les procédures.</p>'
                . '<p><a class="button small" href="' . e(route_url('chatbot')) . '">Ouvrir Raymond</a></p>';
        case 'ham_weather_advice':
            return render_ham_weather_advice($user);

        case 'radio_clocks':
            $todayUtc = gmdate('d/m/Y');
            $todayLocal = (new DateTimeImmutable('now'))->format('d/m/Y');
            $clockLabels = match ($locale) {
                'en' => ['utc' => 'UTC date/time', 'local' => 'Local date/time'],
                default => ['utc' => 'Date/heure UTC', 'local' => 'Date/heure locale'],
            };

            return '<div class="grid-2">'
                . '<article class="inner-card">'
                . '<p class="help"><strong>' . e($clockLabels['utc']) . '</strong></p>'
                . '<p><span data-live-date data-timezone="UTC" aria-live="polite">' . e($todayUtc) . '</span><br>'
                . '<time data-live-clock data-timezone="UTC" aria-live="polite">--:--:--</time></p>'
                . '</article>'
                . '<article class="inner-card">'
                . '<p class="help"><strong>' . e($clockLabels['local']) . '</strong></p>'
                . '<p><span data-live-date data-timezone="local" aria-live="polite">' . e($todayLocal) . '</span><br>'
                . '<time data-live-clock data-timezone="local" aria-live="polite">--:--:--</time></p>'
                . '</article>'
                . '</div>';
        case 'open_meteo':
            $cacheTtl = 300;
            $defaultLocator = 'JO20LI';
            $memberLocator = strtoupper(trim((string) ($user['locator'] ?? '')));
            $locator = $memberLocator !== '' ? $memberLocator : $defaultLocator;
            $weatherCoordinates = maidenhead_to_coordinates($locator);
            $fallbackFeedUrl = 'https://api.open-meteo.com/v1/forecast?latitude=50.3150&longitude=4.9452&current=temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code,cloud_cover,precipitation&timezone=Europe%2FBrussels';
            $fallbackFeedEnabled = true;

            if (table_exists('live_feeds')) {
                try {
                    $feedColumns = ['url'];
                    if (table_has_column('live_feeds', 'cache_ttl')) {
                        $feedColumns[] = 'cache_ttl';
                    }
                    if (table_has_column('live_feeds', 'is_enabled')) {
                        $feedColumns[] = 'is_enabled';
                    }
                    $feedStmt = db()->prepare('SELECT ' . implode(', ', $feedColumns) . ' FROM live_feeds WHERE code = ? LIMIT 1');
                    $feedStmt->execute(['open-meteo']);
                    $feedRow = $feedStmt->fetch();
                    if (is_array($feedRow)) {
                        $fallbackFeedEnabled = (int) ($feedRow['is_enabled'] ?? 1) === 1;
                        $configuredUrl = trim((string) ($feedRow['url'] ?? ''));
                        if ($configuredUrl !== '') {
                            $fallbackFeedUrl = $configuredUrl;
                        }
                        $cacheTtl = max(60, (int) ($feedRow['cache_ttl'] ?? 300));
                    }
                } catch (Throwable) {
                    $fallbackFeedUrl = 'https://api.open-meteo.com/v1/forecast?latitude=50.3150&longitude=4.9452&current=temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code,cloud_cover,precipitation&timezone=Europe%2FBrussels';
                    $fallbackFeedEnabled = true;
                    $cacheTtl = 300;
                }
            }

            $current = null;
            $sourceLabel = 'Agromet';
            $agrometToken = trim((string) env('AGROMET_API_TOKEN', ''));
            if ($agrometToken !== '') {
                $agrometUrl = ham_agromet_hourly_url();
                $current = cache_remember('widget:weather:agromet:' . sha1($agrometUrl), $cacheTtl, static function () use ($agrometUrl, $agrometToken): ?array {
                    $payload = ham_agromet_api_json($agrometUrl, $agrometToken);
                    return is_array($payload) ? ham_agromet_current_weather($payload) : null;
                });
            }

            if (!is_array($current) && $fallbackFeedEnabled) {
                $sourceLabel = 'Open-Meteo';
                if ($weatherCoordinates !== null) {
                    $fallbackFeedUrl = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
                        'latitude' => number_format($weatherCoordinates['latitude'], 4, '.', ''),
                        'longitude' => number_format($weatherCoordinates['longitude'], 4, '.', ''),
                        'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code,cloud_cover,precipitation',
                        'timezone' => 'auto',
                    ]);
                }
                $payload = cache_remember('widget:weather:open-meteo:' . sha1($fallbackFeedUrl . '|' . $locator), $cacheTtl, static function () use ($fallbackFeedUrl): ?array {
                    $context = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'timeout' => 6,
                            'header' => "Accept: application/json\r\nUser-Agent: ON4CRD-Widget/1.0\r\n",
                        ],
                    ]);
                    $raw = @file_get_contents($fallbackFeedUrl, false, $context);
                    if (!is_string($raw) || trim($raw) === '') {
                        return null;
                    }
                    $decoded = json_decode($raw, true);
                    return is_array($decoded) ? $decoded : null;
                });
                $current = is_array($payload) && is_array($payload['current'] ?? null) ? $payload['current'] : null;
            }

            if (!is_array($current)) {
                $weatherUnavailable = match ($locale) {
                    'en' => 'Weather data is currently unavailable.',
                    default => 'Donnees meteo indisponibles pour le moment.',
                };
                return '<p class="help">' . e($weatherUnavailable) . '</p>';
            }

            $temperature = is_numeric($current['temperature_2m'] ?? null) ? (float) $current['temperature_2m'] : null;
            $humidity = is_numeric($current['relative_humidity_2m'] ?? null) ? (int) round((float) $current['relative_humidity_2m']) : null;
            $wind = is_numeric($current['wind_speed_10m'] ?? null) ? (float) $current['wind_speed_10m'] : null;
            $precipitation = is_numeric($current['precipitation'] ?? null) ? (float) $current['precipitation'] : null;
            $weatherCode = is_numeric($current['weather_code'] ?? null) ? (int) $current['weather_code'] : null;

            $labels = match ($locale) {
                'en' => [
                    'weather' => 'Weather',
                    'source' => 'Source',
                    'temperature' => 'Temperature',
                    'humidity' => 'Humidity',
                    'wind' => 'Wind',
                    'rain' => 'Rain',
                    'dry' => 'Dry local conditions',
                    'rainy' => 'Rain observed',
                    'windy' => 'Sustained wind',
                    'humid' => 'Humid air',
                    'variable' => 'Variable conditions',
                    'clear' => 'Clear sky',
                    'cloudy' => 'Cloudy',
                    'fog' => 'Fog',
                    'snow' => 'Snow',
                    'storm' => 'Storm',
                ],
                default => [
                    'weather' => 'Meteo',
                    'source' => 'Source',
                    'temperature' => 'Temperature',
                    'humidity' => 'Humidite',
                    'wind' => 'Vent',
                    'rain' => 'Pluie',
                    'dry' => 'Conditions locales seches',
                    'rainy' => 'Pluie observee',
                    'windy' => 'Vent soutenu',
                    'humid' => 'Air humide',
                    'variable' => 'Conditions variables',
                    'clear' => 'Ciel degage',
                    'cloudy' => 'Nuageux',
                    'fog' => 'Brouillard',
                    'snow' => 'Neige',
                    'storm' => 'Orage',
                ],
            };

            $summary = match ($weatherCode) {
                0 => $labels['clear'],
                1, 2, 3 => $labels['cloudy'],
                45, 48 => $labels['fog'],
                51, 53, 55, 61, 63, 65, 80, 81, 82 => $labels['rainy'],
                71, 73, 75, 77, 85, 86 => $labels['snow'],
                95, 96, 99 => $labels['storm'],
                default => null,
            };
            if ($summary === null) {
                $summary = match (true) {
                    is_numeric($precipitation) && $precipitation >= 0.2 => $labels['rainy'],
                    is_numeric($wind) && $wind >= 30.0 => $labels['windy'],
                    is_numeric($humidity) && $humidity >= 90 => $labels['humid'],
                    default => $labels['dry'],
                };
            }

            $items = [
                '<div class="dashboard-weather-item"><span class="help">' . e((string) $labels['weather']) . '</span><strong>' . e((string) $summary) . '</strong></div>',
                '<div class="dashboard-weather-item"><span class="help">' . e((string) $labels['source']) . '</span><strong>' . e($sourceLabel) . '</strong></div>',
            ];
            if (is_numeric($temperature)) {
                $items[] = '<div class="dashboard-weather-item"><span class="help">' . e((string) $labels['temperature']) . '</span><strong>' . e(number_format((float) $temperature, 1, ',', '')) . '&deg;C</strong></div>';
            }
            if (is_numeric($humidity)) {
                $items[] = '<div class="dashboard-weather-item"><span class="help">' . e((string) $labels['humidity']) . '</span><strong>' . e((string) $humidity) . '%</strong></div>';
            }
            if (is_numeric($wind)) {
                $items[] = '<div class="dashboard-weather-item"><span class="help">' . e((string) $labels['wind']) . '</span><strong>' . e(number_format((float) $wind, 1, ',', '')) . ' km/h</strong></div>';
            }
            if (is_numeric($precipitation)) {
                $items[] = '<div class="dashboard-weather-item"><span class="help">' . e((string) $labels['rain']) . '</span><strong>' . e(number_format((float) $precipitation, 1, ',', '')) . ' mm/h</strong></div>';
            }

            return '<div class="dashboard-weather-grid">' . implode('', $items) . '</div>';
        default:
            $widgetUnavailable = match ($locale) {
                'en' => 'Widget unavailable.',
                'de' => 'Widget nicht verfügbar.',
                'nl' => 'Widget niet beschikbaar.',
                'es' => 'Widget no disponible.',
                'it' => 'Widget non disponibile.',
                'pt' => 'Widget indisponível.',
                'ar' => 'الأداة غير متاحة.',
                'hi' => 'विजेट उपलब्ध नहीं है।',
                'ja' => 'ウィジェットは利用できません。',
                'zh' => '小组件不可用。',
                'bn' => 'উইজেটটি উপলভ্য নয়।',
                'ru' => 'Виджет недоступен.',
                'id' => 'Widget tidak tersedia.',
                default => 'Widget indisponible.',
            };
            return '<p class="help">' . e($widgetUnavailable) . '</p>';
    }
}
}
