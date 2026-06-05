<?php
declare(strict_types=1);

require_once __DIR__ . '/hamqsl_widgets.php';
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

        case 'club_status':
            $moduleCount = 0;
            if (table_exists('modules')) {
                $stmt = db()->query('SELECT COUNT(*) FROM modules WHERE is_enabled = 1');
                $moduleCount = $stmt !== false ? (int) $stmt->fetchColumn() : 0;
            }

            return '<p><strong>' . $moduleCount . '</strong> modules actifs.</p>'
                . '<p class="help">Configuration dynamique via l’administration.</p>';

        case 'events':
            if (!table_exists('events')) {
                return '<p class="help">Module événements indisponible.</p>';
            }

            $dateColumn = table_has_column('events', 'start_at')
                ? 'start_at'
                : (table_has_column('events', 'starts_at') ? 'starts_at' : '');
            if ($dateColumn === '') {
                return '<p class="help">Agenda indisponible.</p>';
            }

            $rows = db()->query(
                'SELECT title, ' . $dateColumn . ' AS event_start_at FROM events WHERE '
                . $dateColumn . ' IS NOT NULL AND ' . $dateColumn . ' >= NOW() ORDER BY ' . $dateColumn . ' ASC LIMIT 3'
            );
            $events = $rows !== false ? ($rows->fetchAll() ?: []) : [];
            if ($events === []) {
                return '<p class="help">Aucun événement à venir.</p>';
            }

            $html = '<ul class="list-clean">';
            foreach ($events as $event) {
                $title = e((string) ($event['title'] ?? 'Événement'));
                $startsAt = e((string) ($event['event_start_at'] ?? ''));
                $html .= '<li><strong>' . $title . '</strong><br><span class="help">' . $startsAt . '</span></li>';
            }
            $html .= '</ul>';

            return $html;

        case 'chatbot':
            return '<p class="help">Posez vos questions à Raymond sur la radio, le club et les procédures.</p>'
                . '<p><a class="button small" href="' . e(route_url('chatbot')) . '">Ouvrir Raymond</a></p>';

        case 'quick_links':
            return '<ul class="list-clean">'
                . '<li><a href="' . e(route_url('profile')) . '">Mon profil</a></li>'
                . '<li><a href="' . e(route_url('qsl')) . '">QSL</a></li>'
                . '<li><a href="' . e(route_url('newsletter')) . '">Newsletter</a></li>'
                . '</ul>';

        case 'propagation':
            $kpFeedUrl = 'https://services.swpc.noaa.gov/products/noaa-planetary-k-index.json';
            $cacheKey = 'widget:propagation:kp-index';
            $payload = cache_remember($cacheKey, 300, static function () use ($kpFeedUrl): ?array {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'timeout' => 6,
                        'header' => "Accept: application/json\r\nUser-Agent: ON4CRD-Widget/1.0\r\n",
                    ],
                ]);
                $raw = @file_get_contents($kpFeedUrl, false, $context);
                if (!is_string($raw) || trim($raw) === '') {
                    return null;
                }
                $decoded = json_decode($raw, true);
                return is_array($decoded) ? $decoded : null;
            });

            $measurement = is_array($payload) ? extract_latest_kp_measurement($payload) : null;
            if (!is_array($measurement)) {
                $unavailableMessage = match ($locale) {
                    'en' => 'Propagation data is currently unavailable.',
                    'de' => 'Ausbreitungsdaten sind derzeit nicht verfügbar.',
                    'nl' => 'Propagatiegegevens zijn momenteel niet beschikbaar.',
                    'es' => 'Los datos de propagación no están disponibles actualmente.',
                    'it' => 'I dati di propagazione non sono attualmente disponibili.',
                    'pt' => 'Os dados de propagação estão indisponíveis no momento.',
                    'ar' => 'بيانات الانتشار غير متاحة حالياً.',
                    'hi' => 'प्रसार डेटा इस समय उपलब्ध नहीं है।',
                    'ja' => '現在、伝搬データを利用できません。',
                    'zh' => '当前无法获取传播数据。',
                    default => 'Les données de propagation sont actuellement indisponibles.',
                };
                return '<p class="help">' . e($unavailableMessage) . '</p>';
            }
            $latestKp = (float) ($measurement['kp'] ?? 0.0);
            $kpTrend = is_array($payload) ? extract_kp_trend($payload) : null;
            $kpTrendSummary = kp_trend_summary($kpTrend, $locale);

            $geomagneticFr = match (true) {
                $latestKp < 2.0 => 'Très calme',
                $latestKp < 4.0 => 'Calme',
                $latestKp < 5.0 => 'Actif',
                $latestKp < 7.0 => 'Perturbé',
                default => 'Orage géomagnétique',
            };
            $geomagnetic = match ($locale) {
                'en' => match (true) {
                    $latestKp < 2.0 => 'Very quiet',
                    $latestKp < 4.0 => 'Quiet',
                    $latestKp < 5.0 => 'Active',
                    $latestKp < 7.0 => 'Disturbed',
                    default => 'Geomagnetic storm',
                },
                default => $geomagneticFr,
            };
            return '<ul class="list-clean">'
                . '<li><strong>Kp : ' . e(number_format($latestKp, 1, ',', '')) . '</strong> — ' . e($geomagnetic) . '</li>'
                . ($kpTrendSummary !== null ? '<li><strong>' . e($kpTrendSummary) . '</strong></li>' : '')
                . '</ul>';

        case 'open_meteo':
            $defaultUrl = 'https://api.open-meteo.com/v1/forecast?latitude=50.3150&longitude=4.9452&current=temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code&timezone=Europe%2FBrussels';
            $feedUrl = $defaultUrl;
            $cacheTtl = 300;
            $defaultLocator = 'JO20LI';
            $memberLocator = strtoupper(trim((string) ($user['locator'] ?? '')));
            $locator = $memberLocator !== '' ? $memberLocator : $defaultLocator;
            $usingClubDefaultLocator = $memberLocator === '';
            $weatherCoordinates = maidenhead_to_coordinates($locator);

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
                    if ((int) ($feedRow['is_enabled'] ?? 1) !== 1) {
                        return '<p class="help">Flux Open‑Meteo désactivé dans l’administration.</p>';
                    }
                    $configuredUrl = trim((string) ($feedRow['url'] ?? ''));
                    if ($configuredUrl !== '') {
                        $feedUrl = $configuredUrl;
                    }
                    $cacheTtl = max(60, (int) ($feedRow['cache_ttl'] ?? 300));
                }
                } catch (Throwable) {
                    $feedUrl = $defaultUrl;
                    $cacheTtl = 300;
                }
            }

            if ($weatherCoordinates !== null) {
                $feedUrl = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
                    'latitude' => number_format($weatherCoordinates['latitude'], 4, '.', ''),
                    'longitude' => number_format($weatherCoordinates['longitude'], 4, '.', ''),
                    'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code,cloud_cover,precipitation',
                    'timezone' => 'auto',
                ]);
            }

            $cacheKey = 'widget:open-meteo:' . sha1($feedUrl . '|' . $locator);
            $payload = cache_remember($cacheKey, $cacheTtl, static function () use ($feedUrl): ?array {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'timeout' => 6,
                        'header' => "Accept: application/json\r\nUser-Agent: ON4CRD-Widget/1.0\r\n",
                    ],
                ]);
                $raw = @file_get_contents($feedUrl, false, $context);
                if (!is_string($raw) || trim($raw) === '') {
                    return null;
                }
                $decoded = json_decode($raw, true);
                return is_array($decoded) ? $decoded : null;
            });

            if (!is_array($payload)) {
                $weatherUnavailable = match ($locale) {
                    'en' => 'Weather data is currently unavailable.',
                    'de' => 'Wetterdaten sind derzeit nicht verfügbar.',
                    'nl' => 'Weergegevens zijn momenteel niet beschikbaar.',
                    'es' => 'Los datos meteorológicos no están disponibles por el momento.',
                    'it' => 'I dati meteo non sono disponibili al momento.',
                    'pt' => 'Os dados meteorológicos não estão disponíveis no momento.',
                    'ar' => 'بيانات الطقس غير متاحة حالياً.',
                    'hi' => 'मौसम डेटा फिलहाल उपलब्ध नहीं है।',
                    'ja' => '現在、天気データは利用できません。',
                    'zh' => '当前天气数据不可用。',
                    'bn' => 'এই মুহূর্তে আবহাওয়ার তথ্য পাওয়া যাচ্ছে না।',
                    'ru' => 'Метеоданные сейчас недоступны.',
                    'id' => 'Data cuaca saat ini tidak tersedia.',
                    default => 'Données météo indisponibles pour le moment.',
                };
                return '<p class="help">' . e($weatherUnavailable) . '</p>';
            }

            $current = is_array($payload['current'] ?? null) ? $payload['current'] : [];
            $weatherCode = (int) ($current['weather_code'] ?? -1);
            $weatherLabels = match ($locale) {
                'en' => ['clear', 'cloudy', 'fog', 'rain', 'freezing_rain', 'snow', 'storm', 'variable'],
                'de' => ['Klarer Himmel', 'Bewölkt', 'Nebel', 'Regen', 'Gefrierender Regen', 'Schnee', 'Gewitter', 'Wechselhafte Bedingungen'],
                'nl' => ['Heldere hemel', 'Bewolkt', 'Mist', 'Regen', 'IJzel', 'Sneeuw', 'Onweer', 'Wisselende omstandigheden'],
                'es' => ['Cielo despejado', 'Nublado', 'Niebla', 'Lluvia', 'Lluvia helada', 'Nieve', 'Tormenta', 'Condiciones variables'],
                'it' => ['Cielo sereno', 'Nuvoloso', 'Nebbia', 'Pioggia', 'Pioggia gelata', 'Neve', 'Temporale', 'Condizioni variabili'],
                'pt' => ['Céu limpo', 'Nublado', 'Nevoeiro', 'Chuva', 'Chuva gelada', 'Neve', 'Trovoada', 'Condições variáveis'],
                'ar' => ['سماء صافية', 'غائم', 'ضباب', 'مطر', 'مطر متجمد', 'ثلج', 'عاصفة رعدية', 'ظروف متغيرة'],
                'hi' => ['आसमान साफ़', 'बादल', 'कोहरा', 'बारिश', 'जमी हुई बारिश', 'बर्फ़', 'आंधी-तूफ़ान', 'परिवर्ती परिस्थितियाँ'],
                'ja' => ['快晴', '曇り', '霧', '雨', '凍雨', '雪', '雷雨', '変わりやすい状況'],
                'zh' => ['晴朗', '多云', '有雾', '降雨', '冻雨', '降雪', '雷暴', '天气多变'],
                'bn' => ['আকাশ পরিষ্কার', 'মেঘলা', 'কুয়াশা', 'বৃষ্টি', 'বরফমিশ্রিত বৃষ্টি', 'তুষার', 'বজ্রঝড়', 'পরিবর্তনশীল অবস্থা'],
                'ru' => ['Ясно', 'Облачно', 'Туман', 'Дождь', 'Ледяной дождь', 'Снег', 'Гроза', 'Переменные условия'],
                'id' => ['Langit cerah', 'Berawan', 'Berkabut', 'Hujan', 'Hujan beku', 'Salju', 'Badai petir', 'Kondisi berubah-ubah'],
                default => ['Ciel dégagé', 'Nuageux', 'Brouillard', 'Pluie', 'Pluie verglaçante', 'Neige', 'Orage', 'Conditions variables'],
            };
            $weatherText = match ($weatherCode) {
                0 => $weatherLabels[0],
                1, 2, 3 => $weatherLabels[1],
                45, 48 => $weatherLabels[2],
                51, 53, 55, 61, 63, 65, 80, 81, 82 => $weatherLabels[3],
                56, 57, 66, 67 => $weatherLabels[4],
                71, 73, 75, 77, 85, 86 => $weatherLabels[5],
                95, 96, 99 => $weatherLabels[6],
                default => $weatherLabels[7],
            };
            $weatherPrefix = match ($locale) {
                'en' => 'Weather:',
                'de' => 'Wetter:',
                'nl' => 'Weer:',
                'es' => 'Tiempo:',
                'it' => 'Meteo:',
                'pt' => 'Tempo:',
                'ar' => 'الطقس:',
                'hi' => 'मौसम:',
                'ja' => '天気:',
                'zh' => '天气：',
                'bn' => 'আবহাওয়া:',
                'ru' => 'Погода:',
                'id' => 'Cuaca:',
                default => 'Météo:',
            };
            return '<ul class="list-clean">'
                . '<li><strong>' . e($weatherPrefix) . ' ' . e($weatherText) . '</strong></li>'
                . '</ul>';

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
