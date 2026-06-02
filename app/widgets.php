<?php
declare(strict_types=1);

if (!function_exists('widget_catalog')) {
function widget_catalog(): array
{
    return [
        'propagation' => [
            'title' => 'Propagation',
            'description' => 'Indicateurs géomagnétiques en temps réel pour vos QSO.',
        ],
        'open_meteo' => [
            'title' => 'Météo locale',
            'description' => 'Conditions météo locales en temps réel pour l’activité radio.',
        ],
    ];
}
}


if (!function_exists('enabled_widget_catalog')) {
function enabled_widget_catalog(): array
{
    $catalog = widget_catalog();
    if (!table_exists('dashboard_widget_settings')) {
        return $catalog;
    }

    $rows = db()->query('SELECT widget_key, is_enabled FROM dashboard_widget_settings');
    $settings = $rows !== false ? ($rows->fetchAll() ?: []) : [];
    $enabledMap = [];
    foreach ($settings as $row) {
        $key = (string) ($row['widget_key'] ?? '');
        if ($key === '') {
            continue;
        }
        $enabledMap[$key] = (int) ($row['is_enabled'] ?? 0) === 1;
    }

    $filtered = [];
    foreach ($catalog as $key => $meta) {
        if (($enabledMap[$key] ?? true) === true) {
            $filtered[$key] = $meta;
        }
    }

    return $filtered;
}
}

if (!function_exists('render_widget')) {
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

function render_widget(string $slug, array $user = []): string
{
    $safeSlug = strtolower(trim($slug));
    $callsign = trim((string) ($user['callsign'] ?? 'OM'));
    $locale = current_locale();

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

            $rows = db()->query('SELECT title, starts_at FROM events WHERE starts_at IS NOT NULL ORDER BY starts_at ASC LIMIT 3');
            $events = $rows !== false ? ($rows->fetchAll() ?: []) : [];
            if ($events === []) {
                return '<p class="help">Aucun événement à venir.</p>';
            }

            $html = '<ul class="list-clean">';
            foreach ($events as $event) {
                $title = e((string) ($event['title'] ?? 'Événement'));
                $startsAt = e((string) ($event['starts_at'] ?? ''));
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

function render_ham_weather_advice(array $user = []): string
{
    $locale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
    $messages = [
        'fr' => [
            'score_excellent' => 'Excellentes conditions',
            'score_good' => 'Bonnes conditions',
            'score_variable' => 'Conditions variables',
            'score_difficult' => 'Conditions difficiles',
            'window_day' => '08h–15h',
            'window_evening' => '16h–21h',
            'window_night' => 'soirée / nuit',
            'radio_info' => 'Informations radioamateur',
            'for_qso' => 'pour les QSO',
            'bands' => 'Bandes conseillées :',
            'modes' => 'Modes conseillés :',
            'window' => 'Créneau recommandé :',
            'input_info' => 'Informations utilisées pour le calcul',
            'location' => 'Localisation :',
            'local_hour' => 'Heure locale :',
            'updated_at' => 'Dernière mise à jour :',
            'local_weather' => 'Météo locale :',
            'geomagnetic' => 'Indice géomagnétique :',
            'kp_unavailable' => 'indisponible',
        ],
        'en' => [
            'score_excellent' => 'Excellent conditions',
            'score_good' => 'Good conditions',
            'score_variable' => 'Variable conditions',
            'score_difficult' => 'Difficult conditions',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'evening / night',
            'radio_info' => 'Ham radio information',
            'for_qso' => 'for QSOs',
            'bands' => 'Recommended bands:',
            'modes' => 'Recommended modes:',
            'window' => 'Recommended time window:',
            'input_info' => 'Data used for calculation',
            'location' => 'Location:',
            'local_hour' => 'Local time:',
            'updated_at' => 'Last update:',
            'local_weather' => 'Local weather:',
            'geomagnetic' => 'Geomagnetic index:',
            'kp_unavailable' => 'unavailable',
        ],
        'de' => [
            'score_excellent' => 'Ausgezeichnete Bedingungen',
            'score_good' => 'Gute Bedingungen',
            'score_variable' => 'Wechselhafte Bedingungen',
            'score_difficult' => 'Schwierige Bedingungen',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'Abend / Nacht',
            'radio_info' => 'Funkinformationen',
            'for_qso' => 'für QSOs',
            'bands' => 'Empfohlene Bänder:',
            'modes' => 'Empfohlene Betriebsarten:',
            'window' => 'Empfohlenes Zeitfenster:',
            'input_info' => 'Für die Berechnung verwendete Daten',
            'location' => 'Standort:',
            'local_hour' => 'Ortszeit:',
            'updated_at' => 'Letzte Aktualisierung:',
            'local_weather' => 'Lokales Wetter:',
            'geomagnetic' => 'Geomagnetischer Index:',
            'kp_unavailable' => 'nicht verfügbar',
        ],
        'nl' => [
            'score_excellent' => 'Uitstekende condities',
            'score_good' => 'Goede condities',
            'score_variable' => 'Wisselende condities',
            'score_difficult' => 'Moeilijke condities',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'avond / nacht',
            'radio_info' => 'Radioamateurinformatie',
            'for_qso' => 'voor QSO’s',
            'bands' => 'Aanbevolen banden:',
            'modes' => 'Aanbevolen modes:',
            'window' => 'Aanbevolen tijdsvenster:',
            'input_info' => 'Gegevens gebruikt voor de berekening',
            'location' => 'Locatie:',
            'local_hour' => 'Lokale tijd:',
            'updated_at' => 'Laatste update:',
            'local_weather' => 'Lokaal weer:',
            'geomagnetic' => 'Geomagnetische index:',
            'kp_unavailable' => 'niet beschikbaar',
        ],
        'es' => [
            'score_excellent' => 'Condiciones excelentes',
            'score_good' => 'Buenas condiciones',
            'score_variable' => 'Condiciones variables',
            'score_difficult' => 'Condiciones difíciles',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'tarde / noche',
            'radio_info' => 'Información de radioafición',
            'for_qso' => 'para QSOs',
            'bands' => 'Bandas recomendadas:',
            'modes' => 'Modos recomendados:',
            'window' => 'Franja horaria recomendada:',
            'input_info' => 'Datos usados para el cálculo',
            'location' => 'Ubicación:',
            'local_hour' => 'Hora local:',
            'updated_at' => 'Última actualización:',
            'local_weather' => 'Tiempo local:',
            'geomagnetic' => 'Índice geomagnético:',
            'kp_unavailable' => 'no disponible',
        ],
        'it' => [
            'score_excellent' => 'Condizioni eccellenti',
            'score_good' => 'Buone condizioni',
            'score_variable' => 'Condizioni variabili',
            'score_difficult' => 'Condizioni difficili',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'sera / notte',
            'radio_info' => 'Informazioni radioamatoriali',
            'for_qso' => 'per i QSO',
            'bands' => 'Bande consigliate:',
            'modes' => 'Modi consigliati:',
            'window' => 'Fascia oraria consigliata:',
            'input_info' => 'Dati usati per il calcolo',
            'location' => 'Posizione:',
            'local_hour' => 'Ora locale:',
            'updated_at' => 'Ultimo aggiornamento:',
            'local_weather' => 'Meteo locale:',
            'geomagnetic' => 'Indice geomagnetico:',
            'kp_unavailable' => 'non disponibile',
        ],
        'pt' => [
            'score_excellent' => 'Condições excelentes',
            'score_good' => 'Boas condições',
            'score_variable' => 'Condições variáveis',
            'score_difficult' => 'Condições difíceis',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'fim de tarde / noite',
            'radio_info' => 'Informações de radioamador',
            'for_qso' => 'para QSOs',
            'bands' => 'Bandas recomendadas:',
            'modes' => 'Modos recomendados:',
            'window' => 'Janela horária recomendada:',
            'input_info' => 'Dados usados no cálculo',
            'location' => 'Localização:',
            'local_hour' => 'Hora local:',
            'updated_at' => 'Última atualização:',
            'local_weather' => 'Tempo local:',
            'geomagnetic' => 'Índice geomagnético:',
            'kp_unavailable' => 'indisponível',
        ],
        'ar' => [
            'score_excellent' => 'ظروف ممتازة',
            'score_good' => 'ظروف جيدة',
            'score_variable' => 'ظروف متغيرة',
            'score_difficult' => 'ظروف صعبة',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'المساء / الليل',
            'radio_info' => 'معلومات هواة الراديو',
            'for_qso' => 'لاتصالات QSO',
            'bands' => 'النطاقات الموصى بها:',
            'modes' => 'الأنماط الموصى بها:',
            'window' => 'الفترة الزمنية الموصى بها:',
            'input_info' => 'البيانات المستخدمة للحساب',
            'location' => 'الموقع:',
            'local_hour' => 'الوقت المحلي:',
            'updated_at' => 'آخر تحديث:',
            'local_weather' => 'الطقس المحلي:',
            'geomagnetic' => 'المؤشر الجيومغناطيسي:',
            'kp_unavailable' => 'غير متوفر',
        ],
        'hi' => [
            'score_excellent' => 'उत्कृष्ट परिस्थितियाँ',
            'score_good' => 'अच्छी परिस्थितियाँ',
            'score_variable' => 'परिवर्ती परिस्थितियाँ',
            'score_difficult' => 'कठिन परिस्थितियाँ',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'शाम / रात',
            'radio_info' => 'हैम रेडियो जानकारी',
            'for_qso' => 'QSO के लिए',
            'bands' => 'अनुशंसित बैंड:',
            'modes' => 'अनुशंसित मोड:',
            'window' => 'अनुशंसित समय खिड़की:',
            'input_info' => 'गणना के लिए उपयोग किया गया डेटा',
            'location' => 'स्थान:',
            'local_hour' => 'स्थानीय समय:',
            'updated_at' => 'अंतिम अपडेट:',
            'local_weather' => 'स्थानीय मौसम:',
            'geomagnetic' => 'भू-चुंबकीय सूचकांक:',
            'kp_unavailable' => 'उपलब्ध नहीं',
        ],
        'ja' => [
            'score_excellent' => '非常に良好なコンディション',
            'score_good' => '良好なコンディション',
            'score_variable' => '変わりやすいコンディション',
            'score_difficult' => '難しいコンディション',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => '夕方 / 夜間',
            'radio_info' => 'アマチュア無線情報',
            'for_qso' => 'QSO向け',
            'bands' => '推奨バンド:',
            'modes' => '推奨モード:',
            'window' => '推奨時間帯:',
            'input_info' => '計算に使用したデータ',
            'location' => '場所:',
            'local_hour' => '現地時刻:',
            'updated_at' => '最終更新:',
            'local_weather' => '現地の天気:',
            'geomagnetic' => '地磁気指数:',
            'kp_unavailable' => '利用不可',
        ],
        'zh' => [
            'score_excellent' => '条件极佳',
            'score_good' => '条件良好',
            'score_variable' => '条件多变',
            'score_difficult' => '条件较差',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => '傍晚 / 夜间',
            'radio_info' => '业余无线电信息',
            'for_qso' => '适用于 QSO',
            'bands' => '推荐频段：',
            'modes' => '推荐模式：',
            'window' => '推荐时段：',
            'input_info' => '用于计算的数据',
            'location' => '位置：',
            'local_hour' => '当地时间：',
            'updated_at' => '最后更新：',
            'local_weather' => '当地天气：',
            'geomagnetic' => '地磁指数：',
            'kp_unavailable' => '不可用',
        ],
        'bn' => [
            'score_excellent' => 'চমৎকার অবস্থা',
            'score_good' => 'ভাল অবস্থা',
            'score_variable' => 'পরিবর্তনশীল অবস্থা',
            'score_difficult' => 'কঠিন অবস্থা',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'সন্ধ্যা / রাত',
            'radio_info' => 'হ্যাম রেডিও তথ্য',
            'for_qso' => 'QSO-এর জন্য',
            'bands' => 'প্রস্তাবিত ব্যান্ড:',
            'modes' => 'প্রস্তাবিত মোড:',
            'window' => 'প্রস্তাবিত সময়সীমা:',
            'input_info' => 'গণনার জন্য ব্যবহৃত তথ্য',
            'location' => 'অবস্থান:',
            'local_hour' => 'স্থানীয় সময়:',
            'updated_at' => 'সর্বশেষ আপডেট:',
            'local_weather' => 'স্থানীয় আবহাওয়া:',
            'geomagnetic' => 'ভূচৌম্বক সূচক:',
            'kp_unavailable' => 'উপলব্ধ নয়',
        ],
        'ru' => [
            'score_excellent' => 'Отличные условия',
            'score_good' => 'Хорошие условия',
            'score_variable' => 'Переменные условия',
            'score_difficult' => 'Сложные условия',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'вечер / ночь',
            'radio_info' => 'Информация для радиолюбителей',
            'for_qso' => 'для QSO',
            'bands' => 'Рекомендуемые диапазоны:',
            'modes' => 'Рекомендуемые режимы:',
            'window' => 'Рекомендуемое время:',
            'input_info' => 'Данные, использованные для расчёта',
            'location' => 'Местоположение:',
            'local_hour' => 'Местное время:',
            'updated_at' => 'Последнее обновление:',
            'local_weather' => 'Местная погода:',
            'geomagnetic' => 'Геомагнитный индекс:',
            'kp_unavailable' => 'недоступно',
        ],
        'id' => [
            'score_excellent' => 'Kondisi sangat baik',
            'score_good' => 'Kondisi baik',
            'score_variable' => 'Kondisi berubah-ubah',
            'score_difficult' => 'Kondisi sulit',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'sore / malam',
            'radio_info' => 'Informasi radio amatir',
            'for_qso' => 'untuk QSO',
            'bands' => 'Band yang direkomendasikan:',
            'modes' => 'Mode yang direkomendasikan:',
            'window' => 'Rentang waktu yang direkomendasikan:',
            'input_info' => 'Data yang digunakan untuk perhitungan',
            'location' => 'Lokasi:',
            'local_hour' => 'Waktu lokal:',
            'updated_at' => 'Pembaruan terakhir:',
            'local_weather' => 'Cuaca lokal:',
            'geomagnetic' => 'Indeks geomagnetik:',
            'kp_unavailable' => 'tidak tersedia',
        ],
    ];
    $i18n = $messages[$locale] ?? $messages['fr'];
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

    $weatherUrl = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
        'latitude' => number_format((float) $coordinates['latitude'], 4, '.', ''),
        'longitude' => number_format((float) $coordinates['longitude'], 4, '.', ''),
        'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code,cloud_cover,precipitation',
        'timezone' => 'auto',
    ]);
    $weatherPayload = cache_remember('ham:advice:weather:' . sha1($weatherUrl), 300, static function () use ($weatherUrl): ?array {
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

    $currentWeather = is_array($weatherPayload) && is_array($weatherPayload['current'] ?? null) ? $weatherPayload['current'] : [];
    $temperature = is_numeric($currentWeather['temperature_2m'] ?? null) ? (float) $currentWeather['temperature_2m'] : 15.0;
    $wind = is_numeric($currentWeather['wind_speed_10m'] ?? null) ? (float) $currentWeather['wind_speed_10m'] : 10.0;
    $weatherCode = (int) ($currentWeather['weather_code'] ?? -1);
    $localTime = trim((string) ($currentWeather['time'] ?? ''));
    $hour = (int) gmdate('G');
    $updatedLabel = '';
    if ($localTime !== '') {
        try {
            $dtLocal = new DateTimeImmutable($localTime);
            $hour = (int) $dtLocal->format('G');
            $updatedLabel = $dtLocal->format('d-m-Y H:i');
        } catch (Throwable $throwable) {
            $hour = (int) gmdate('G');
            $updatedLabel = gmdate('d-m-Y H:i');
        }
    } else {
        $updatedLabel = gmdate('d-m-Y H:i');
    }

    $humidity = is_numeric($currentWeather['relative_humidity_2m'] ?? null) ? (int) $currentWeather['relative_humidity_2m'] : 60;
    $cloudCover = is_numeric($currentWeather['cloud_cover'] ?? null) ? (int) $currentWeather['cloud_cover'] : 45;
    $precipitation = is_numeric($currentWeather['precipitation'] ?? null) ? (float) $currentWeather['precipitation'] : 0.0;
    $measurement = is_array($kpPayload) ? extract_latest_kp_measurement($kpPayload) : null;
    $kp = is_array($measurement) ? (float) ($measurement['kp'] ?? 3.0) : null;
    $kpTrend = is_array($kpPayload) ? extract_kp_trend($kpPayload) : null;
    $kpTrendForScoring = is_numeric($kpTrend) ? (float) $kpTrend : 0.0;
    $kpTrendSummary = kp_trend_summary($kpTrend, $locale);
    $kpForScoring = is_numeric($kp) ? (float) $kp : 3.0;

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

    $scoreLabel = $hfScore >= 80 ? (string) $i18n['score_excellent'] : ($hfScore >= 60 ? (string) $i18n['score_good'] : ($hfScore >= 45 ? (string) $i18n['score_variable'] : (string) $i18n['score_difficult']));
    $timeWindow = $hour >= 8 && $hour <= 15 ? (string) $i18n['window_day'] : ($hour >= 16 && $hour <= 21 ? (string) $i18n['window_evening'] : (string) $i18n['window_night']);

    return '<div class="grid gap-4">'
        . '<section>'
        . '<h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">' . e((string) $i18n['radio_info']) . '</h3>'
        . '<ul class="mt-2 list-clean">'
        . '<li><strong>' . e($scoreLabel) . '</strong> ' . e((string) $i18n['for_qso']) . ' (score ' . e((string) max(0, min(100, (int) round($hfScore)))) . '/100)</li>'
        . '<li><strong>' . e((string) $i18n['bands']) . '</strong> ' . e(implode(' • ', $bands)) . '</li>'
        . '<li><strong>' . e((string) $i18n['modes']) . '</strong> ' . e(implode(' • ', $modes)) . '</li>'
        . '<li><strong>' . e((string) $i18n['window']) . '</strong> ' . e($timeWindow) . '</li>'
        . '</ul>'
        . '</section>'
        . '<section>'
        . '<h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">' . e((string) $i18n['input_info']) . '</h3>'
        . '<ul class="mt-2 list-clean">'
        . '<li><strong>' . e((string) $i18n['location']) . '</strong> ' . e($locator) . '</li>'
        . '<li><strong>' . e((string) $i18n['local_hour']) . '</strong> ' . e(str_pad((string) $hour, 2, '0', STR_PAD_LEFT)) . 'h</li>'
        . '<li><strong>' . e((string) $i18n['local_weather']) . '</strong> T=' . e(number_format($temperature, 1, ',', '')) . '°C, H=' . e((string) $humidity) . '%, vent ' . e(number_format($wind, 1, ',', '')) . ' km/h, nuages ' . e((string) $cloudCover) . '%, pluie ' . e(number_format($precipitation, 1, ',', '')) . ' mm/h</li>'
        . '<li><strong>' . e((string) $i18n['geomagnetic']) . '</strong> '
        . (is_numeric($kp)
            ? 'Kp=' . e(number_format((float) $kp, 1, ',', '')) . ($kpTrendSummary !== null ? '; ' . e($kpTrendSummary) : '')
            : e((string) $i18n['kp_unavailable']))
        . '</li>'
        . '<li><strong>' . e((string) $i18n['updated_at']) . '</strong> ' . e($updatedLabel) . '</li>'
        . '</ul>'
        . '</section>'
        . '</div>';
}
}
