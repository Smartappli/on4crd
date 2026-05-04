<?php
declare(strict_types=1);

function config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $configFile = __DIR__ . '/../config/config.php';
        if (!is_file($configFile)) {
            throw new RuntimeException('Missing config/config.php. Copy config.sample.php first.');
        }
        $config = require $configFile;
    }

    if ($key === null) {
        return $config;
    }

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = (string) config('db.dsn', '');
    $user = (string) config('db.user', '');
    $pass = (string) config('db.pass', '');
    if ($dsn === '') {
        throw new RuntimeException('Configuration DB manquante (db.dsn).');
    }

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function auth(): ?\Delight\Auth\Auth
{
    static $auth = false;

    if ($auth instanceof \Delight\Auth\Auth) {
        return $auth;
    }
    if ($auth === null) {
        return null;
    }

    if (!class_exists(\Delight\Auth\Auth::class)) {
        $auth = null;
        return null;
    }

    $pdo = db();
    try {
        if (class_exists(\Delight\Db\PdoDatabase::class)) {
            $auth = new \Delight\Auth\Auth(new \Delight\Db\PdoDatabase($pdo));
        } else {
            $auth = new \Delight\Auth\Auth($pdo);
        }
    } catch (Throwable $throwable) {
        $auth = null;
        return null;
    }

    return $auth;
}

function table_exists(string $table): bool
{
    static $cache = [];
    $normalized = strtolower(trim($table));
    if ($normalized === '') {
        return false;
    }
    if (array_key_exists($normalized, $cache)) {
        return $cache[$normalized];
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
    );
    $stmt->execute([$normalized]);
    $cache[$normalized] = (int) $stmt->fetchColumn() > 0;

    return $cache[$normalized];
}

if (!function_exists('current_locale')) {
function current_locale(): string
{
    static $resolvedLocale = null;
    if (is_string($resolvedLocale)) {
        return $resolvedLocale;
    }

    $locale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
    if (!in_array($locale, ['fr', 'en', 'de', 'nl'], true)) {
        $resolvedLocale = 'fr';
        return $resolvedLocale;
    }

    $resolvedLocale = $locale;
    return $resolvedLocale;
}
}

if (!function_exists('t_page')) {
function t_page(string $domain, string $key, ?string $locale = null): string
{
    $lang = $locale !== null ? strtolower(trim($locale)) : current_locale();
    if (!in_array($lang, ['fr', 'en', 'de', 'nl'], true)) {
        $lang = 'fr';
    }

    static $messages = null;
    if (!is_array($messages)) {
        $messages = [
        'press' => [
            'fr' => ['title' => 'Presse', 'body' => "La section presse sera alimentée via le module d'administration."],
            'en' => ['title' => 'Press', 'body' => 'The press section will be managed from the administration module.'],
            'de' => ['title' => 'Presse', 'body' => 'Der Pressebereich wird über das Administrationsmodul gepflegt.'],
            'nl' => ['title' => 'Pers', 'body' => 'De perssectie wordt beheerd via de beheermodule.'],
        ],
        'sponsoring' => [
            'fr' => ['title' => 'Sponsoring', 'body' => 'Retrouvez sur cette page les informations relatives aux partenaires et opportunités de sponsoring du club.'],
            'en' => ['title' => 'Sponsoring', 'body' => 'Find information here about partners and club sponsorship opportunities.'],
            'de' => ['title' => 'Sponsoring', 'body' => 'Hier finden Sie Informationen zu Partnern und Sponsoring-Möglichkeiten des Clubs.'],
            'nl' => ['title' => 'Sponsoring', 'body' => 'Hier vindt u informatie over partners en sponsoringsmogelijkheden van de club.'],
        ],
        'mentions_legales' => [
            'fr' => ['title' => 'Mentions légales', 'body' => 'Les mentions légales du site ON4CRD sont accessibles ici et seront complétées selon les obligations en vigueur.'],
            'en' => ['title' => 'Legal notice', 'body' => 'The ON4CRD legal notices are available here and will be completed according to applicable obligations.'],
            'de' => ['title' => 'Impressum', 'body' => 'Die rechtlichen Hinweise von ON4CRD sind hier verfügbar und werden gemäß geltenden Verpflichtungen ergänzt.'],
            'nl' => ['title' => 'Juridische vermeldingen', 'body' => 'De juridische vermeldingen van ON4CRD staan hier en worden aangevuld volgens de geldende verplichtingen.'],
        ],
        'conditions_utilisation' => [
            'fr' => ['title' => "Conditions générales d'utilisation", 'body' => "Les conditions générales d'utilisation du site ON4CRD seront publiées et mises à jour sur cette page."],
            'en' => ['title' => 'Terms of use', 'body' => 'The ON4CRD website terms of use will be published and updated on this page.'],
            'de' => ['title' => 'Nutzungsbedingungen', 'body' => 'Die Nutzungsbedingungen der ON4CRD-Website werden auf dieser Seite veröffentlicht und aktualisiert.'],
            'nl' => ['title' => 'Gebruiksvoorwaarden', 'body' => 'De gebruiksvoorwaarden van de ON4CRD-website worden op deze pagina gepubliceerd en bijgewerkt.'],
        ],
        'reglement_interieur' => [
            'fr' => ['title' => "Règlement d'ordre intérieur", 'body' => "Le règlement d'ordre intérieur du club sera présenté sur cette page."],
            'en' => ['title' => 'Internal regulations', 'body' => 'The club internal regulations will be published on this page.'],
            'de' => ['title' => 'Interne Ordnung', 'body' => 'Die interne Ordnung des Clubs wird auf dieser Seite veröffentlicht.'],
            'nl' => ['title' => 'Intern reglement', 'body' => 'Het intern reglement van de club wordt op deze pagina gepubliceerd.'],
        ],
    ];
    }

    if (!isset($messages[$domain])) {
        return $key;
    }

    $catalog = $messages[$domain][$lang] ?? $messages[$domain]['fr'];

    return (string) ($catalog[$key] ?? ($messages[$domain]['fr'][$key] ?? $key));
}
}

function seed_modules(): void
{
    if (!table_exists('modules')) {
        return;
    }

    $modules = [
        ['dashboard', 'Tableau de bord', 'Personnalisation du dashboard', 1, 1, 10],
        ['members', 'Membres', 'Espace membres et profil', 1, 1, 20],
        ['news', 'Actualités', 'Section des actualités du club', 1, 1, 30],
        ['articles', 'Articles', 'Articles techniques', 1, 1, 40],
        ['wiki', 'Wiki', 'Base de connaissances collaborative', 1, 1, 50],
        ['albums', 'Albums', 'Galerie photos', 1, 1, 60],
        ['events', 'Événements', 'Agenda du club', 1, 1, 70],
        ['shop', 'Boutique', 'Produits et commandes', 1, 1, 80],
        ['auctions', 'Enchères', 'Ventes aux enchères', 1, 1, 90],
        ['qsl', 'QSL', 'Gestion des cartes QSL', 1, 1, 100],
        ['chatbot', 'Raymond vous répond', 'Assistant conversationnel intégré au tableau de bord des membres', 1, 1, 110],
        ['advertising', 'Publicités', 'Gestion des annonces/publicités', 1, 1, 120],
        ['press', 'Presse', 'Communiqués et contacts presse', 1, 1, 130],
        ['education', 'Éducation', 'Activités écoles/formation', 1, 1, 140],
        ['committee', 'Comité', 'Informations du comité', 1, 1, 150],
        ['directory', 'Annuaire', 'Annuaire public du club', 1, 1, 160],
        ['admin', 'Administration', 'Administration générale', 1, 1, 1000],
    ];

    $stmt = db()->prepare(
        'INSERT INTO modules (code, label, description, is_core, is_enabled, sort_order)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE label = VALUES(label), description = VALUES(description), sort_order = VALUES(sort_order)'
    );

    foreach ($modules as $module) {
        $stmt->execute($module);
    }
}

function seed_dashboard_widgets(): void
{
    // Hook conservé pour compatibilité installateur.
}

if (!function_exists('widget_catalog')) {
function widget_catalog(): array
{
    return [
        'welcome' => [
            'title' => 'Bienvenue',
            'description' => 'Résumé rapide de votre espace membre.',
        ],
        'club_status' => [
            'title' => 'État du club',
            'description' => 'Statut des modules actifs du site.',
        ],
        'events' => [
            'title' => 'Prochains événements',
            'description' => 'Les prochains rendez-vous du club.',
        ],
        'chatbot' => [
            'title' => 'Raymond vous répond',
            'description' => 'Accès rapide à l’assistant du club.',
        ],
        'quick_links' => [
            'title' => 'Liens rapides',
            'description' => 'Raccourcis vers les modules membres.',
        ],
        'propagation' => [
            'title' => 'Propagation',
            'description' => 'Repères rapides radio du moment.',
        ],
        'open_meteo' => [
            'title' => 'Météo locale',
            'description' => 'Conditions météo en direct via Open‑Meteo.',
        ],
    ];
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
        $lonStep = 5.0 / 60.0;
        $latStep = 2.5 / 60.0;
    }

    return [
        'latitude' => $lat + ($latStep / 2.0),
        'longitude' => $lon + ($lonStep / 2.0),
    ];
}

function extract_latest_kp_measurement(array $payload): ?array
{
    if (count($payload) <= 1) {
        return null;
    }

    for ($index = count($payload) - 1; $index >= 1; $index--) {
        $row = $payload[$index] ?? null;
        if (!is_array($row)) {
            continue;
        }

        $timestamp = trim((string) ($row[0] ?? ''));
        $kpValue = $row[1] ?? null;
        if ($timestamp === '' || !is_numeric($kpValue)) {
            continue;
        }

        return [
            'timestamp' => $timestamp,
            'kp' => (float) $kpValue,
        ];
    }

    return null;
}

function render_widget(string $slug, array $user = []): string
{
    $safeSlug = strtolower(trim($slug));
    $callsign = trim((string) ($user['callsign'] ?? 'OM'));

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
            $locale = current_locale();
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
                    default => 'Données de propagation indisponibles actuellement.',
                };
                return '<p class="help">' . e($unavailableMessage) . '</p>';
            }
            $latestKp = (float) ($measurement['kp'] ?? 0.0);

            $geomagnetic = match (true) {
                $latestKp < 2.0 => 'Très calme',
                $latestKp < 4.0 => 'Calme',
                $latestKp < 5.0 => 'Actif',
                $latestKp < 7.0 => 'Perturbé',
                default => 'Orage géomagnétique',
            };
            return '<ul class="list-clean">'
                . '<li><strong>Kp : ' . e(number_format($latestKp, 1, ',', '')) . '</strong> — ' . e($geomagnetic) . '</li>'
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
                $feedStmt = db()->prepare('SELECT url, cache_ttl, is_enabled FROM live_feeds WHERE code = ? LIMIT 1');
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
            }

            if ($weatherCoordinates !== null) {
                $feedUrl = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
                    'latitude' => number_format($weatherCoordinates['latitude'], 4, '.', ''),
                    'longitude' => number_format($weatherCoordinates['longitude'], 4, '.', ''),
                    'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code',
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
                return '<p class="help">Données météo indisponibles pour le moment.</p>';
            }

            $current = is_array($payload['current'] ?? null) ? $payload['current'] : [];
            $weatherCode = (int) ($current['weather_code'] ?? -1);
            $weatherText = match ($weatherCode) {
                0 => 'Ciel dégagé',
                1, 2, 3 => 'Partiellement nuageux',
                45, 48 => 'Brouillard',
                51, 53, 55, 61, 63, 65, 80, 81, 82 => 'Pluie',
                56, 57, 66, 67 => 'Pluie verglaçante',
                71, 73, 75, 77, 85, 86 => 'Neige',
                95, 96, 99 => 'Orage',
                default => 'Conditions variables',
            };
            return '<ul class="list-clean">'
                . '<li><strong>' . e($weatherText) . '</strong></li>'
                . '</ul>';

        default:
            return '<p class="help">Widget indisponible.</p>';
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
            'local_weather' => 'Météo locale :',
            'geomagnetic' => 'Indice géomagnétique :',
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
            'local_weather' => 'Local weather:',
            'geomagnetic' => 'Geomagnetic index:',
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
            'local_weather' => 'Lokales Wetter:',
            'geomagnetic' => 'Geomagnetischer Index:',
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
            'local_weather' => 'Lokaal weer:',
            'geomagnetic' => 'Geomagnetische index:',
        ],
    ];
    $i18n = $messages[$locale] ?? $messages['fr'];
    $defaultLocator = 'JO20LI';
    $memberLocator = strtoupper(trim((string) ($user['locator'] ?? '')));
    $locator = $memberLocator !== '' ? $memberLocator : $defaultLocator;
    $coordinates = maidenhead_to_coordinates($locator) ?? ['latitude' => 50.3150, 'longitude' => 4.9452];

    $weatherUrl = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
        'latitude' => number_format((float) $coordinates['latitude'], 4, '.', ''),
        'longitude' => number_format((float) $coordinates['longitude'], 4, '.', ''),
        'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code',
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
    if ($localTime !== '') {
        try {
            $hour = (int) (new DateTimeImmutable($localTime))->format('G');
        } catch (Throwable $throwable) {
            $hour = (int) gmdate('G');
        }
    }
    $humidity = is_numeric($currentWeather['relative_humidity_2m'] ?? null) ? (int) $currentWeather['relative_humidity_2m'] : 60;
    $measurement = is_array($kpPayload) ? extract_latest_kp_measurement($kpPayload) : null;
    $kp = is_array($measurement) ? (float) ($measurement['kp'] ?? 3.0) : 3.0;

    $hfScore = 70.0;
    $hfScore += $kp <= 2.0 ? 15.0 : ($kp <= 4.0 ? 5.0 : -20.0);
    $hfScore += ($hour >= 7 && $hour <= 16) ? 10.0 : -5.0;
    $hfScore += ($wind <= 25.0 ? 5.0 : -8.0);
    $hfScore += ($humidity >= 35 && $humidity <= 85) ? 3.0 : -4.0;
    $hfScore += in_array($weatherCode, [95, 96, 99], true) ? -15.0 : 0.0;

    $bands = ['40m', '20m', '15m'];
    if ($hour >= 8 && $hour <= 15 && $kp <= 3.5) {
        $bands = ['20m', '17m', '15m'];
    } elseif ($hour >= 10 && $hour <= 17 && $kp <= 2.5) {
        $bands = ['15m', '12m', '10m'];
    } elseif ($hour >= 18 || $hour <= 6) {
        $bands = ['40m', '80m', '30m'];
    } elseif ($kp >= 5.0) {
        $bands = ['40m', '30m', '20m'];
    }

    $modes = ['SSB', 'CW'];
    if ($kp >= 4.5 || $wind >= 35.0 || in_array($weatherCode, [95, 96, 99], true)) {
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
        . '<li><strong>' . e((string) $i18n['local_weather']) . '</strong> T=' . e(number_format($temperature, 1, ',', '')) . '°C, H=' . e((string) $humidity) . '%, vent ' . e(number_format($wind, 1, ',', '')) . ' km/h</li>'
        . '<li><strong>' . e((string) $i18n['geomagnetic']) . '</strong> Kp=' . e(number_format($kp, 1, ',', '')) . '</li>'
        . '</ul>'
        . '</section>'
        . '</div>';
}
}

function seed_ad_placements(): void
{
    if (!table_exists('ad_placements')) {
        return;
    }

    $placements = [
        ['homepage_top', 'Accueil (haut)', 'Bannière en haut de la page d’accueil', 10],
        ['sidebar', 'Barre latérale', 'Emplacement encart latéral', 20],
        ['article_inline', 'Article (inline)', 'Annonce dans le contenu des articles', 30],
    ];

    $stmt = db()->prepare(
        'INSERT INTO ad_placements (code, name, description, sort_order, is_active)
         VALUES (?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), sort_order = VALUES(sort_order)'
    );
    foreach ($placements as $placement) {
        $stmt->execute($placement);
    }
}

function seed_live_feeds(): void
{
    if (!table_exists('live_feeds')) {
        return;
    }

    $feeds = [
        ['noaa-alerts', 'NOAA Alerts', 'https://services.swpc.noaa.gov/products/alerts.json', 'json', 120, 180, 1, 'Alertes météo spatiale NOAA'],
        ['open-meteo', 'Open-Meteo', 'https://api.open-meteo.com/v1/forecast?latitude=50.3150&longitude=4.9452&current=temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code&timezone=Europe%2FBrussels', 'json', 300, 300, 1, 'Météo locale via Open-Meteo (locator membre, fallback radio-club JO20LI)'],
        ['hamqth-dx', 'HamQTH DX', 'https://www.hamqth.com/dxc_csv.php?limit=12', 'csv', 300, 300, 1, 'Derniers spots DX'],
    ];

    $stmt = db()->prepare(
        'INSERT INTO live_feeds (code, label, url, parser, cache_ttl, refresh_seconds, is_enabled, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE label = VALUES(label), url = VALUES(url), parser = VALUES(parser), cache_ttl = VALUES(cache_ttl), refresh_seconds = VALUES(refresh_seconds), is_enabled = VALUES(is_enabled), notes = VALUES(notes)'
    );
    foreach ($feeds as $feed) {
        $stmt->execute($feed);
    }
}

function ensure_directories(): void
{
    $directories = [
        dirname(__DIR__) . '/storage/cache/data',
        dirname(__DIR__) . '/storage/uploads/albums',
        dirname(__DIR__) . '/storage/uploads/ads',
        dirname(__DIR__) . '/storage/uploads/members',
        dirname(__DIR__) . '/storage/uploads/members/avatars',
        dirname(__DIR__) . '/storage/press',
    ];

    foreach ($directories as $directory) {
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de créer un dossier requis: ' . $directory);
        }
    }
}

function apply_runtime_schema_updates(): void
{
    if (!table_exists('users')) {
        db()->exec(
            'CREATE TABLE users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(249) NOT NULL,
                password VARCHAR(255) NOT NULL,
                username VARCHAR(100) DEFAULT NULL,
                status TINYINT UNSIGNED NOT NULL DEFAULT 0,
                verified TINYINT UNSIGNED NOT NULL DEFAULT 0,
                resettable TINYINT UNSIGNED NOT NULL DEFAULT 1,
                roles_mask INT UNSIGNED NOT NULL DEFAULT 0,
                registered INT UNSIGNED NOT NULL,
                last_login INT UNSIGNED DEFAULT NULL,
                force_logout MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
                UNIQUE KEY users_email_unique (email),
                UNIQUE KEY users_username_unique (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_confirmations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            email VARCHAR(249) NOT NULL,
            selector VARCHAR(16) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires INT UNSIGNED NOT NULL,
            UNIQUE KEY users_confirmations_selector_unique (selector),
            KEY users_confirmations_user_id_index (user_id),
            CONSTRAINT users_confirmations_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_remembered (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user INT UNSIGNED NOT NULL,
            selector VARCHAR(24) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires INT UNSIGNED NOT NULL,
            UNIQUE KEY users_remembered_selector_unique (selector),
            KEY users_remembered_user_index (user),
            CONSTRAINT users_remembered_user_foreign FOREIGN KEY (user) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_resets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user INT UNSIGNED NOT NULL,
            selector VARCHAR(20) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires INT UNSIGNED NOT NULL,
            UNIQUE KEY users_resets_selector_unique (selector),
            KEY users_resets_user_index (user),
            CONSTRAINT users_resets_user_foreign FOREIGN KEY (user) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_throttling (
            bucket VARCHAR(44) NOT NULL,
            tokens FLOAT UNSIGNED NOT NULL,
            replenished_at INT UNSIGNED NOT NULL,
            expires_at INT UNSIGNED NOT NULL,
            PRIMARY KEY (bucket)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (table_exists('articles')) {
        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnStmt->execute(['articles', 'category']);
        $hasCategory = (int) $columnStmt->fetchColumn() > 0;
        if (!$hasCategory) {
            db()->exec('ALTER TABLE articles ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT "autres" AFTER status');
        }
    }


    if (table_exists('dashboard_widgets')) {
        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnStmt->execute(['dashboard_widgets', 'config_json']);
        $hasConfigJson = (int) $columnStmt->fetchColumn() > 0;
        if (!$hasConfigJson) {
            db()->exec('ALTER TABLE dashboard_widgets ADD COLUMN config_json LONGTEXT DEFAULT NULL AFTER widget_key');
        }
    }

    if (table_exists('members')) {
        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $requiredColumns = [
            'auth_user_id' => 'ALTER TABLE members ADD COLUMN auth_user_id INT UNSIGNED DEFAULT NULL UNIQUE',
            'visibility_full_name' => 'ALTER TABLE members ADD COLUMN visibility_full_name ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_licence_class' => 'ALTER TABLE members ADD COLUMN visibility_licence_class ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_favourite_bands' => 'ALTER TABLE members ADD COLUMN visibility_favourite_bands ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_photo' => 'ALTER TABLE members ADD COLUMN visibility_photo ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'avatar_path' => 'ALTER TABLE members ADD COLUMN avatar_path VARCHAR(255) DEFAULT NULL',
        ];

        foreach ($requiredColumns as $columnName => $statement) {
            $columnStmt->execute(['members', $columnName]);
            $hasColumn = (int) $columnStmt->fetchColumn() > 0;
            if (!$hasColumn) {
                db()->exec($statement);
            }
        }
    }


    if (table_exists('modules')) {
        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnStmt->execute(['modules', 'visibility']);
        $hasVisibility = (int) $columnStmt->fetchColumn() > 0;
        if (!$hasVisibility) {
            db()->exec('ALTER TABLE modules ADD COLUMN visibility ENUM("public","members","admin") NOT NULL DEFAULT "members" AFTER is_enabled');
        }
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS quotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_text TEXT NOT NULL,
            author VARCHAR(190) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $quoteCount = db()->query('SELECT COUNT(*) FROM quotes');
    $hasQuotes = $quoteCount !== false ? (int) $quoteCount->fetchColumn() > 0 : false;
    if (!$hasQuotes) {
        $seedFile = __DIR__ . '/../assets/sql/radioamateur_citations_multilingue_3532.sql';
        if (is_file($seedFile)) {
            try {
                seed_quotes_from_sql_file($seedFile);
            } catch (Throwable $throwable) {
                log_structured_event('quotes_seed_failed', [
                    'message' => $throwable->getMessage(),
                    'file' => $seedFile,
                ]);
            }
        }
    }
}

function seed_quotes_from_sql_file(string $filePath): void
{
    if (!is_file($filePath)) {
        return;
    }

    $sql = (string) file_get_contents($filePath);
    if (trim($sql) === '') {
        return;
    }

    $statements = preg_split('/;\s*(?:\R|$)/', $sql) ?: [];
    foreach ($statements as $statement) {
        $trimmed = trim($statement);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '/*')) {
            continue;
        }
        try {
            db()->exec($trimmed);
        } catch (Throwable $throwable) {
            log_structured_event('quotes_seed_statement_skipped', [
                'message' => $throwable->getMessage(),
            ]);
        }
    }
}

function random_quote_for_layout(): ?array
{
    if (!table_exists('quotes')) {
        return null;
    }

    static $cachedQuote = null;
    if (is_array($cachedQuote)) {
        return $cachedQuote;
    }

    $countStmt = db()->query('SELECT COUNT(*) FROM quotes WHERE is_active = 1');
    $activeCount = $countStmt !== false ? (int) $countStmt->fetchColumn() : 0;
    if ($activeCount <= 0) {
        return null;
    }

    try {
        $offset = random_int(0, max(0, $activeCount - 1));
    } catch (Throwable $throwable) {
        $offset = 0;
    }

    $stmt = db()->query('SELECT quote_text, author FROM quotes WHERE is_active = 1 LIMIT 1 OFFSET ' . $offset);
    if ($stmt === false) {
        return null;
    }
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return null;
    }

    $quote = trim((string) ($row['quote_text'] ?? ''));
    $author = trim((string) ($row['author'] ?? ''));
    if ($quote === '') {
        return null;
    }

    $cachedQuote = [
        'quote' => $quote,
        'author' => $author,
    ];

    return $cachedQuote;
}

if (!function_exists('base_url')) {
function base_url(string $path = ''): string
{
    $configured = rtrim((string) config('app.base_url', ''), '/');
    if ($configured !== '') {
        $base = $configured;
    } else {
        $scheme = is_https_request() ? 'https' : 'http';
        $forwardedHostHeader = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
        $hostHeader = $forwardedHostHeader !== ''
            ? trim(explode(',', $forwardedHostHeader)[0])
            : (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $host = strtolower(trim($hostHeader));
        if ($host === '' || preg_match('/[^a-z0-9\\-\\.:\\[\\]]/i', $host) !== 0) {
            $host = 'localhost';
        }

        $forwardedPortHeader = trim((string) ($_SERVER['HTTP_X_FORWARDED_PORT'] ?? ''));
        $forwardedPort = $forwardedPortHeader !== ''
            ? trim(explode(',', $forwardedPortHeader)[0])
            : '';
        if ($forwardedPort !== '' && ctype_digit($forwardedPort)) {
            $port = (int) $forwardedPort;
            if (($scheme === 'https' && $port !== 443) || ($scheme === 'http' && $port !== 80)) {
                $hostWithoutPort = preg_replace('/:\\d+$/', '', $host);
                $host = ($hostWithoutPort ?: $host) . ':' . $port;
            }
        }

        $base = $scheme . '://' . $host;
    }

    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}
}

if (!function_exists('asset_url')) {
function asset_url(string $path): string
{
    return base_url($path);
}
}

if (!function_exists('route_url')) {
function route_url(string $route, array $query = []): string
{
    $route = trim($route);
    if ($route === '' || $route === 'home') {
        if ($query === []) {
            return base_url('/');
        }

        return base_url('/?' . http_build_query($query));
    }

    if (str_ends_with($route, '.php')) {
        $directPhpRoutes = ['install.php', 'sitemap.xml', 'robots.txt'];
        $normalizedRoute = ltrim($route, '/');
        if (in_array($normalizedRoute, $directPhpRoutes, true)) {
            $suffix = $query === [] ? '' : ('?' . http_build_query($query));
            return base_url('/' . $normalizedRoute . $suffix);
        }

        $route = pathinfo($normalizedRoute, PATHINFO_FILENAME);
    }

    $extra = [];
    if (str_contains($route, '&')) {
        [$route, $tail] = explode('&', $route, 2);
        parse_str($tail, $extra);
    }

    $params = array_merge(['route' => $route], $extra, $query);
    return base_url('/index.php?' . http_build_query($params));
}
}

if (!function_exists('redirect_url')) {
function redirect_url(string $url): void
{
    header('Location: ' . $url, true, 302);
    exit;
}
}

if (!function_exists('redirect')) {
function redirect(string $route): void
{
    redirect_url(route_url($route));
}
}

if (!function_exists('set_flash')) {
function set_flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}
}

if (!function_exists('consume_flashes')) {
function consume_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    if (!is_array($flashes)) {
        $flashes = [];
    }
    unset($_SESSION['_flash']);

    return array_values(array_filter($flashes, static fn ($item): bool => is_array($item)));
}
}

if (!function_exists('current_user')) {
function auth_bypass_member_id(): int
{
    $configuredBypassId = max(0, (int) config('app.auth_bypass_member_id', 0));
    if ($configuredBypassId > 0) {
        return $configuredBypassId;
    }

    $route = (string) ($_GET['route'] ?? 'home');
    $temporaryBypassForMembers = (bool) config('app.bypass_member_modules_auth', false);
    $memberBypassRoutes = [
        'dashboard',
        'save_dashboard',
        'widget_render',
        'profile',
        'qsl',
        'qsl_preview',
        'qsl_export',
        'shop_checkout',
        'auction_bid',
        'newsletter',
    ];
    if ($temporaryBypassForMembers && in_array($route, $memberBypassRoutes, true) && table_exists('members')) {
        $stmt = db()->query('SELECT id FROM members WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
        $firstActiveMemberId = $stmt !== false ? (int) $stmt->fetchColumn() : 0;

        return max(0, $firstActiveMemberId);
    }

    $environment = strtolower(trim((string) config('app.env', 'production')));
    $allowDevelopmentBypass = (bool) config('app.disable_login_in_development', false);
    $route = (string) ($_GET['route'] ?? 'home');
    $memberBypassRoutes = [
        'dashboard',
        'save_dashboard',
        'widget_render',
        'profile',
        'qsl',
        'qsl_preview',
        'qsl_export',
        'shop_checkout',
        'auction_bid',
        'newsletter',
    ];
    if (!$allowDevelopmentBypass || $environment !== 'development' || !table_exists('members') || !in_array($route, $memberBypassRoutes, true)) {
        return 0;
    }

    $stmt = db()->query('SELECT id FROM members WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
    $firstActiveMemberId = $stmt !== false ? (int) $stmt->fetchColumn() : 0;

    return max(0, $firstActiveMemberId);
}

function bypass_member_user(int $memberId): ?array
{
    if ($memberId <= 0 || !table_exists('members')) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, callsign, full_name, email, locator, is_active, is_committee FROM members WHERE id = ? LIMIT 1');
    $stmt->execute([$memberId]);
    $row = $stmt->fetch();
    if (!is_array($row) || (int) ($row['is_active'] ?? 0) !== 1) {
        return null;
    }

    return $row;
}

function current_user(): ?array
{
    static $cache = null;
    static $loaded = false;

    if ($loaded) {
        return $cache;
    }
    $loaded = true;

    $memberId = (int) ($_SESSION['member_id'] ?? 0);
    $authClient = auth();
    if ($authClient !== null && $authClient->isLoggedIn()) {
        $memberId = (int) $authClient->getUserId();
    }

    if ($memberId <= 0) {
        $bypassMemberId = auth_bypass_member_id();
        if ($bypassMemberId > 0) {
            $bypassUser = bypass_member_user($bypassMemberId);
            if (is_array($bypassUser)) {
                $_SESSION['member_id'] = (int) $bypassUser['id'];
                $cache = $bypassUser;
                return $cache;
            }
        }

        $cache = null;
        return null;
    }

    if (!table_exists('members')) {
        $cache = null;
        return null;
    }

    $stmt = db()->prepare('SELECT id, callsign, full_name, email, locator, is_active, is_committee FROM members WHERE id = ? OR auth_user_id = ? LIMIT 1');
    $stmt->execute([$memberId, $memberId]);
    $row = $stmt->fetch();
    if (!is_array($row) || (int) ($row['is_active'] ?? 0) !== 1) {
        unset($_SESSION['member_id']);
        $cache = null;
        return null;
    }

    $cache = $row;
    return $cache;
}
}

if (!function_exists('require_login')) {
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        set_flash('error', 'Veuillez vous connecter pour continuer.');
        redirect('login');
    }

    return $user;
}
}

if (!function_exists('logout_member')) {
function logout_member(): void
{
    $authClient = auth();
    if ($authClient !== null && $authClient->isLoggedIn()) {
        $authClient->logOut();
    }
    unset($_SESSION['member_id']);
}
}

if (!function_exists('module_enabled')) {
function module_enabled(string $module): bool
{
    if ($module === '' || !table_exists('modules')) {
        return true;
    }

    $stmt = db()->prepare('SELECT is_enabled FROM modules WHERE code = ? LIMIT 1');
    $stmt->execute([$module]);
    $value = $stmt->fetchColumn();
    if ($value === false) {
        return true;
    }

    return (int) $value === 1;
}
}


if (!function_exists('module_visible_for_current_user')) {
function module_visible_for_current_user(string $module): bool
{
    if ($module === '' || !table_exists('modules')) {
        return true;
    }

    $stmt = db()->prepare('SELECT visibility FROM modules WHERE code = ? LIMIT 1');
    $stmt->execute([$module]);
    $visibility = (string) ($stmt->fetchColumn() ?: 'public');

    if ($visibility === 'public') {
        return true;
    }

    $user = current_user();
    if ($user === null) {
        return false;
    }

    if ($visibility === 'members') {
        return true;
    }

    if ($visibility === 'admin') {
        return has_permission('admin.access') || has_permission('modules.manage');
    }

    return false;
}
}

if (!function_exists('require_module_enabled')) {
function require_module_enabled(string $module): void
{
    if (module_enabled($module) && module_visible_for_current_user($module)) {
        return;
    }

    http_response_code(404);
    echo render_layout('<div class="card"><h1>404</h1><p>Module indisponible.</p></div>', '404');
    exit;
}
}

if (!function_exists('has_permission')) {
function has_permission(string $permission): bool
{
    $user = current_user();
    if ($user === null || $permission === '') {
        return false;
    }
    if (!table_exists('permissions') || !table_exists('roles') || !table_exists('member_roles') || !table_exists('role_permissions')) {
        return false;
    }

    $stmt = db()->prepare(
        'SELECT 1
         FROM permissions p
         LEFT JOIN member_permissions mp ON mp.permission_id = p.id AND mp.member_id = ?
         LEFT JOIN role_permissions rp ON rp.permission_id = p.id
         LEFT JOIN member_roles mr ON mr.role_id = rp.role_id AND mr.member_id = ?
         WHERE p.code = ?
           AND (mp.member_id IS NOT NULL OR mr.member_id IS NOT NULL)
         LIMIT 1'
    );
    $stmt->execute([(int) $user['id'], (int) $user['id'], $permission]);

    return (bool) $stmt->fetchColumn();
}
}

if (!function_exists('require_permission')) {
function require_permission(string $permission): void
{
    require_login();
    if (has_permission($permission)) {
        return;
    }

    http_response_code(403);
    echo render_layout('<div class="card"><h1>403</h1><p>Accès refusé.</p></div>', 'Accès refusé');
    exit;
}
}

if (!function_exists('set_page_meta')) {
function set_page_meta(string|array $title = '', string $description = ''): void
{
    if (is_array($title)) {
        $_SESSION['_page_meta'] = $title;
        return;
    }
    $_SESSION['_page_meta'] = ['title' => $title, 'description' => $description];
}
}

if (!function_exists('render_footer_social_links')) {
function render_footer_social_links(): string
{
    $socialLinks = [
        [
            'name' => 'Facebook',
            'href' => 'https://www.facebook.com/groups/clubradiodurnal/',
            'path' => 'M22 12a10 10 0 1 0-11.56 9.87v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.23.19 2.23.19v2.45h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.88h-2.34v6.99A10 10 0 0 0 22 12z',
        ],
        [
            'name' => 'LinkedIn',
            'href' => 'https://www.linkedin.com/',
            'path' => 'M4.98 3.5a2.49 2.49 0 1 0 0 4.98 2.49 2.49 0 0 0 0-4.98zM3 8.98h3.96V21H3zM9.34 8.98h3.8v1.64h.05c.53-1 1.82-2.05 3.75-2.05C20.95 8.57 22 11.2 22 14.62V21h-3.96v-5.66c0-1.35-.02-3.09-1.88-3.09-1.88 0-2.17 1.47-2.17 2.99V21H10.03z',
        ],
        [
            'name' => 'X',
            'href' => 'https://x.com/',
            'path' => 'M18.9 2H22l-6.77 7.74L23 22h-6.2l-4.85-6.33L6.41 22H3.3l7.24-8.28L1 2h6.36l4.38 5.78zM17.82 20h1.72L6.45 3.9H4.6z',
        ],
        [
            'name' => 'Instagram',
            'href' => 'https://www.instagram.com/',
            'path' => 'M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm11.25 1.5a1.25 1.25 0 1 1-1.25 1.25 1.25 1.25 0 0 1 1.25-1.25zM12 7a5 5 0 1 1-5 5 5 5 0 0 1 5-5zm0 2a3 3 0 1 0 3 3 3 3 0 0 0-3-3z',
        ],
    ];

    $html = '<span style="display:inline-flex;align-items:center;gap:.6rem;">';
    foreach ($socialLinks as $social) {
        $name = (string) ($social['name'] ?? '');
        $href = (string) ($social['href'] ?? '#');
        $path = (string) ($social['path'] ?? '');
        $html .= '<a href="' . e($href) . '" target="_blank" rel="noopener noreferrer" aria-label="' . e($name . ' - Club Radio Durnal') . '" title="' . e($name . ' - Club Radio Durnal') . '">'
            . '<svg aria-hidden="true" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="' . e($path) . '"></path></svg>'
            . '<span class="sr-only">' . e($name) . '</span>'
            . '</a>';
    }
    $html .= '</span>';

    return $html;
}
}

if (!function_exists('render_site_footer')) {
function render_site_footer(string $currentRoute): string
{
    $locale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
    $footerMessages = [
        'fr' => ['built_by' => 'Site réalisé par'],
        'en' => ['built_by' => 'Website built by'],
        'de' => ['built_by' => 'Website erstellt von'],
        'nl' => ['built_by' => 'Website gemaakt door'],
    ];
    $i18n = $footerMessages[$locale] ?? $footerMessages['fr'];

    return '<footer class="site-footer"><div class="footer-inner"><div class="footer-meta"><span>© 2026 Radio Club Durnal (ON4CRD)</span>' . render_footer_social_links() . '<span>' . e((string) $i18n['built_by']) . ' <a href="https://smartappli.eu">Smartappli ®</a></span></div></div></footer>';
}
}

if (!function_exists('render_layout')) {
function render_layout(string $content, string $title = ''): string
{
    $flashes = consume_flashes();
    $currentRoute = (string) ($_GET['route'] ?? 'home');
    $currentTheme = (string) ($_SESSION['theme'] ?? 'dark');
    if ($currentTheme !== 'dark') {
        $currentTheme = 'light';
    }
    $currentLocale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
    if (!in_array($currentLocale, ['fr', 'en', 'de', 'nl'], true)) {
        $currentLocale = 'fr';
    }
    $layoutMessages = [
        'fr' => [
            'nav_home' => 'Accueil', 'nav_news' => 'Actualités', 'nav_shop' => 'Boutique', 'nav_events' => 'Événements', 'nav_tools' => 'Outils', 'nav_directory' => 'Annuaire',
            'nav_dashboard' => 'Tableau de bord', 'nav_wiki' => 'Wiki', 'nav_gallery' => 'Galerie', 'nav_articles' => 'Articles', 'nav_auctions' => 'Enchères',
            'account_space' => 'Mon espace', 'account_profile' => 'Profil', 'account_settings' => 'Paramètres', 'logout' => 'Déconnexion', 'login' => 'Connexion',
            'theme_light' => 'Clair', 'theme_dark' => 'Sombre',
            'accent_blue' => 'Bleu', 'accent_emerald' => 'Émeraude', 'accent_violet' => 'Violet', 'accent_red' => 'Rouge', 'accent_amber' => 'Ambre', 'accent_orange' => 'Orange',
            'language_choice' => 'Choix de la langue', 'language_help' => 'Sélecteur de langue du site. Le changement est appliqué automatiquement.',
            'theme_choice' => 'Choix du mode clair ou sombre', 'theme_help' => 'Sélecteur de thème. Le changement est appliqué automatiquement.',
            'accent_choice' => 'Choix de la couleur', 'accent_help' => 'Sélecteur de couleur d’accent. Le changement est appliqué automatiquement.',
            'install_app' => 'Installer l’app',
        ],
        'en' => [
            'nav_home' => 'Home', 'nav_news' => 'News', 'nav_shop' => 'Shop', 'nav_events' => 'Events', 'nav_tools' => 'Tools', 'nav_directory' => 'Directory',
            'nav_dashboard' => 'Dashboard', 'nav_wiki' => 'Wiki', 'nav_gallery' => 'Gallery', 'nav_articles' => 'Articles', 'nav_auctions' => 'Auctions',
            'account_space' => 'My account', 'account_profile' => 'Profile', 'account_settings' => 'Settings', 'logout' => 'Log out', 'login' => 'Log in',
            'theme_light' => 'Light', 'theme_dark' => 'Dark',
            'accent_blue' => 'Blue', 'accent_emerald' => 'Emerald', 'accent_violet' => 'Violet', 'accent_red' => 'Red', 'accent_amber' => 'Amber', 'accent_orange' => 'Orange',
            'language_choice' => 'Language selection', 'language_help' => 'Site language selector. Changes are applied automatically.',
            'theme_choice' => 'Light or dark mode selection', 'theme_help' => 'Theme selector. Changes are applied automatically.',
            'accent_choice' => 'Accent color selection', 'accent_help' => 'Accent color selector. Changes are applied automatically.',
            'install_app' => 'Install app',
        ],
        'de' => [
            'nav_home' => 'Startseite', 'nav_news' => 'Neuigkeiten', 'nav_shop' => 'Shop', 'nav_events' => 'Veranstaltungen', 'nav_tools' => 'Werkzeuge', 'nav_directory' => 'Verzeichnis',
            'nav_dashboard' => 'Dashboard', 'nav_wiki' => 'Wiki', 'nav_gallery' => 'Galerie', 'nav_articles' => 'Artikel', 'nav_auctions' => 'Auktionen',
            'account_space' => 'Mein Bereich', 'account_profile' => 'Profil', 'account_settings' => 'Einstellungen', 'logout' => 'Abmelden', 'login' => 'Anmelden',
            'theme_light' => 'Hell', 'theme_dark' => 'Dunkel',
            'accent_blue' => 'Blau', 'accent_emerald' => 'Smaragd', 'accent_violet' => 'Violett', 'accent_red' => 'Rot', 'accent_amber' => 'Bernstein', 'accent_orange' => 'Orange',
            'language_choice' => 'Sprachauswahl', 'language_help' => 'Sprachauswahl der Website. Änderungen werden automatisch angewendet.',
            'theme_choice' => 'Hell- oder Dunkelmodus auswählen', 'theme_help' => 'Designauswahl. Änderungen werden automatisch angewendet.',
            'accent_choice' => 'Akzentfarbe auswählen', 'accent_help' => 'Akzentfarbauswahl. Änderungen werden automatisch angewendet.',
            'install_app' => 'App installieren',
        ],
        'nl' => [
            'nav_home' => 'Startpagina', 'nav_news' => 'Nieuws', 'nav_shop' => 'Winkel', 'nav_events' => 'Evenementen', 'nav_tools' => 'Tools', 'nav_directory' => 'Gids',
            'nav_dashboard' => 'Dashboard', 'nav_wiki' => 'Wiki', 'nav_gallery' => 'Galerij', 'nav_articles' => 'Artikels', 'nav_auctions' => 'Veilingen',
            'account_space' => 'Mijn ruimte', 'account_profile' => 'Profiel', 'account_settings' => 'Instellingen', 'logout' => 'Afmelden', 'login' => 'Inloggen',
            'theme_light' => 'Licht', 'theme_dark' => 'Donker',
            'accent_blue' => 'Blauw', 'accent_emerald' => 'Smaragd', 'accent_violet' => 'Violet', 'accent_red' => 'Rood', 'accent_amber' => 'Amber', 'accent_orange' => 'Oranje',
            'language_choice' => 'Taalselectie', 'language_help' => 'Taalkiezer van de site. Wijzigingen worden automatisch toegepast.',
            'theme_choice' => 'Lichte of donkere modus kiezen', 'theme_help' => 'Themaselector. Wijzigingen worden automatisch toegepast.',
            'accent_choice' => 'Accentkleur kiezen', 'accent_help' => 'Accentkleurselector. Wijzigingen worden automatisch toegepast.',
            'install_app' => 'App installeren',
        ],
    ];
    $layoutI18n = $layoutMessages[$currentLocale] ?? $layoutMessages['fr'];
    $currentAccent = strtolower((string) ($_SESSION['accent'] ?? 'blue'));
    $accentPalette = [
        'blue' => ['color' => '#2f6fed', 'strong' => '#1f59cf', 'label' => 'Bleu'],
        'emerald' => ['color' => '#059669', 'strong' => '#047857', 'label' => 'Émeraude'],
        'violet' => ['color' => '#7c3aed', 'strong' => '#6d28d9', 'label' => 'Violet'],
        'red' => ['color' => '#dc2626', 'strong' => '#b91c1c', 'label' => 'Rouge'],
        'amber' => ['color' => '#d97706', 'strong' => '#b45309', 'label' => 'Ambre'],
        'orange' => ['color' => '#ea580c', 'strong' => '#c2410c', 'label' => 'Orange'],
    ];
    if ($currentAccent === 'rose') {
        $currentAccent = 'red';
    }
    if (!array_key_exists($currentAccent, $accentPalette)) {
        $currentAccent = 'blue';
    }
    $accentColor = (string) $accentPalette[$currentAccent]['color'];
    $accentStrongColor = (string) $accentPalette[$currentAccent]['strong'];
    $user = current_user();
    $flashHtml = '';
    foreach ($flashes as $flash) {
        $type = (string) ($flash['type'] ?? 'info');
        $message = e((string) ($flash['message'] ?? ''));
        $flashHtml .= '<div class="flash flash-' . e($type) . '">' . $message . '</div>';
    }

    $navPrimaryItems = [
        ['label' => (string) $layoutI18n['nav_home'], 'route' => 'home', 'module' => ''],
        ['label' => (string) $layoutI18n['nav_news'], 'route' => 'news', 'module' => 'news'],
        ['label' => (string) $layoutI18n['nav_shop'], 'route' => 'shop', 'module' => 'shop'],
        ['label' => (string) $layoutI18n['nav_events'], 'route' => 'events', 'module' => 'events'],
        ['label' => (string) $layoutI18n['nav_tools'], 'route' => 'tools', 'module' => ''],
        ['label' => (string) $layoutI18n['nav_directory'], 'route' => 'directory', 'module' => 'directory'],
    ];
    $navMemberItems = [
        ['label' => (string) $layoutI18n['nav_dashboard'], 'route' => 'dashboard', 'module' => 'dashboard'],
        ['label' => (string) $layoutI18n['nav_wiki'], 'route' => 'wiki', 'module' => 'wiki'],
        ['label' => (string) $layoutI18n['nav_gallery'], 'route' => 'albums', 'module' => 'albums'],
        ['label' => (string) $layoutI18n['nav_articles'], 'route' => 'articles', 'module' => 'articles'],
        ['label' => 'QSL', 'route' => 'qsl', 'module' => 'qsl'],
        ['label' => (string) $layoutI18n['nav_auctions'], 'route' => 'auctions', 'module' => 'auctions'],
    ];

    $buildNavLinks = static function (array $items, string $currentRoute): string {
        $links = '';
        foreach ($items as $item) {
            $module = (string) ($item['module'] ?? '');
            if ($module !== '' && !module_enabled($module)) {
                continue;
            }

            $route = (string) $item['route'];
            $isCurrent = $currentRoute === $route || ($currentRoute === '' && $route === 'home');
            $links .= '<a class="transition-colors duration-200" href="' . e(route_url($route)) . '"' . ($isCurrent ? ' aria-current="page"' : '') . '>'
                . e((string) $item['label']) . '</a>';
        }

        return $links;
    };
    $navHtml = '<div class="nav-row nav-row-primary">' . $buildNavLinks($navPrimaryItems, $currentRoute) . '</div>';
    if ($user !== null) {
        $memberLinks = $buildNavLinks($navMemberItems, $currentRoute);
        if ($memberLinks !== '') {
            $navHtml .= '<div class="nav-row nav-row-member">' . $memberLinks . '</div>';
        }
    }

    $authHtml = '';
    if ($user !== null) {
        $accountLabel = trim((string) ($user['callsign'] ?? '')) !== '' ? (string) $user['callsign'] : (string) $layoutI18n['account_space'];
        $authHtml = '<details class="account-menu">'
            . '<summary class="button small account-menu-trigger">' . e($accountLabel) . '</summary>'
            . '<div class="account-menu-panel">'
            . '<a class="account-menu-link" href="' . e(route_url('profile')) . '">' . e((string) $layoutI18n['account_profile']) . '</a>'
            . '<a class="account-menu-link" href="' . e(route_url('profile')) . '">' . e((string) $layoutI18n['account_settings']) . '</a>'
            . '<hr class="account-menu-separator">'
            . '<form class="nav-form account-menu-form" method="post" action="' . e(route_url('logout')) . '">'
            . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
            . '<button type="submit" class="button small account-menu-logout">' . e((string) $layoutI18n['logout']) . '</button>'
            . '</form>'
            . '</div>'
            . '</details>';
    } else {
        $authHtml = '<a class="button toolbar-login-button" href="' . e(route_url('login')) . '">' . e((string) $layoutI18n['login']) . '</a>';
    }

    $siteName = (string) config('app.site_name', 'ON4CRD');
    $pageMeta = (array) ($_SESSION['_page_meta'] ?? []);
    unset($_SESSION['_page_meta']);
    $metaTitle = trim((string) ($pageMeta['title'] ?? ''));
    $pageTitle = $title !== '' ? $title : ($metaTitle !== '' ? $metaTitle : $siteName);
    $metaDescription = trim((string) ($pageMeta['description'] ?? ''));
    if ($metaDescription === '') {
        $metaDescription = 'Radio Club Durnal ON4CRD : actualités, événements, formation, ressources et vie du club radioamateur.';
    }
    $metaCanonical = trim((string) ($pageMeta['canonical'] ?? ''));
    $metaRobots = trim((string) ($pageMeta['robots'] ?? 'index,follow'));
    $metaOgType = trim((string) ($pageMeta['og_type'] ?? 'website'));
    $metaTwitterCard = trim((string) ($pageMeta['twitter_card'] ?? 'summary_large_image'));
    $metaLocale = trim((string) ($pageMeta['locale'] ?? 'fr_BE'));
    $metaSiteName = trim((string) ($pageMeta['site_name'] ?? $siteName));
    $metaHead = '<meta name="description" content="' . e($metaDescription) . '">'
        . '<meta name="robots" content="' . e($metaRobots) . '">'
        . '<meta property="og:title" content="' . e($pageTitle) . '">'
        . '<meta property="og:description" content="' . e($metaDescription) . '">'
        . '<meta property="og:type" content="' . e($metaOgType) . '">'
        . '<meta property="og:locale" content="' . e($metaLocale) . '">'
        . '<meta property="og:site_name" content="' . e($metaSiteName) . '">'
        . '<meta name="twitter:card" content="' . e($metaTwitterCard) . '">'
        . '<meta name="twitter:title" content="' . e($pageTitle) . '">'
        . '<meta name="twitter:description" content="' . e($metaDescription) . '">';
    if ($metaCanonical !== '') {
        $metaHead .= '<link rel="canonical" href="' . e($metaCanonical) . '">'
            . '<meta property="og:url" content="' . e($metaCanonical) . '">';
    }
    $year = gmdate('Y');
    $themeOptions = [
        'light' => ['icon' => '☀️', 'label' => (string) $layoutI18n['theme_light']],
        'dark' => ['icon' => '🌙', 'label' => (string) $layoutI18n['theme_dark']],
    ];
    $languageOptions = [
        'fr' => ['icon' => '🇫🇷', 'label' => 'Français'],
        'en' => ['icon' => '🇬🇧', 'label' => 'English'],
        'de' => ['icon' => '🇩🇪', 'label' => 'Deutsch'],
        'nl' => ['icon' => '🇳🇱', 'label' => 'Nederlands'],
    ];
    $accentIcons = [
        'blue' => '🔵',
        'emerald' => '🟢',
        'violet' => '🟣',
        'red' => '🔴',
        'amber' => '🟡',
        'orange' => '🟠',
    ];
    $languageOptionHtml = '';
    foreach ($languageOptions as $localeCode => $localeConfig) {
        $isActive = $localeCode === $currentLocale;
        $localeLabel = (string) ($localeConfig['label'] ?? strtoupper($localeCode));
        $localeIcon = (string) ($localeConfig['icon'] ?? '');
        $languageOptionHtml .= '<option value="' . e($localeCode) . '"' . ($isActive ? ' selected' : '') . '>'
            . e(trim($localeIcon . ' ' . $localeLabel))
            . '</option>';
    }
    $themeOptionHtml = '';
    foreach ($themeOptions as $themeCode => $themeConfig) {
        $isActive = $themeCode === $currentTheme;
        $themeIcon = (string) ($themeConfig['icon'] ?? '');
        $themeLabel = (string) ($themeConfig['label'] ?? $themeCode);
        $themeOptionHtml .= '<option value="' . e($themeCode) . '"' . ($isActive ? ' selected' : '') . '>'
            . e(trim($themeIcon . ' ' . $themeLabel))
            . '</option>';
    }
    $accentOptionHtml = '';
    foreach ($accentPalette as $accentCode => $accentConfig) {
        $isActive = $accentCode === $currentAccent;
        $accentIcon = (string) ($accentIcons[$accentCode] ?? '🎨');
        $accentLabel = (string) ($layoutI18n['accent_' . $accentCode] ?? ($accentConfig['label'] ?? ucfirst($accentCode)));
        $accentDotColor = (string) ($accentConfig['color'] ?? '#2f6fed');
        $accentOptionHtml .= '<option value="' . e($accentCode) . '"' . ($isActive ? ' selected' : '') . ' style="color:' . e($accentDotColor) . ';">'
            . e(trim($accentIcon . ' ' . $accentLabel))
            . '</option>';
    }
    $languageFormHtml = '<form class="toolbar-form inline-form" method="post" action="' . e(route_url('set_language')) . '">'
        . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="return_route" value="' . e($currentRoute) . '">'
        . '<label class="sr-only" for="language-selector">' . e((string) $layoutI18n['language_choice']) . '</label>'
        . '<select id="language-selector" class="preference-select js-auto-submit" name="locale" aria-label="' . e((string) $layoutI18n['language_choice']) . '" aria-describedby="language-help">' . $languageOptionHtml . '</select>'
        . '<span class="sr-only" id="language-help">' . e((string) $layoutI18n['language_help']) . '</span>'
        . '</form>';
    $themeFormHtml = '<form class="toolbar-form" method="post" action="' . e(route_url('set_theme')) . '">'
        . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="return_route" value="' . e($currentRoute) . '">'
        . '<label class="sr-only" for="theme-selector">' . e((string) $layoutI18n['theme_choice']) . '</label>'
        . '<select id="theme-selector" class="preference-select js-auto-submit" name="theme" aria-label="' . e((string) $layoutI18n['theme_choice']) . '" aria-describedby="theme-help">' . $themeOptionHtml . '</select>'
        . '<span class="sr-only" id="theme-help">' . e((string) $layoutI18n['theme_help']) . '</span>'
        . '</form>';
    $accentFormHtml = '<form class="toolbar-form inline-form" method="post" action="' . e(route_url('set_accent')) . '">'
        . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="return_route" value="' . e($currentRoute) . '">'
        . '<label class="sr-only" for="accent-selector">' . e((string) $layoutI18n['accent_choice']) . '</label>'
        . '<select id="accent-selector" class="preference-select js-auto-submit" name="accent" aria-label="' . e((string) $layoutI18n['accent_choice']) . '" aria-describedby="accent-help">' . $accentOptionHtml . '</select>'
        . '<span class="sr-only" id="accent-help">' . e((string) $layoutI18n['accent_help']) . '</span>'
        . '</form>';
    $installButtonHtml = '<button type="button" class="button secondary" data-pwa-install hidden disabled aria-label="' . e((string) $layoutI18n['install_app']) . '">' . e((string) $layoutI18n['install_app']) . '</button>';
    $menuToolsHtml = '<div class="toolbar-preferences">'
        . '<div class="toolbar-preferences-row">' . $languageFormHtml . $themeFormHtml . '</div>'
        . '<div class="toolbar-preferences-row">' . $accentFormHtml . '<div class="toolbar-auth">' . $installButtonHtml . $authHtml . '</div></div>'
        . '</div>';
    $nonce = csp_nonce();
    return '<!doctype html><html lang="' . e($currentLocale) . '" data-theme="' . e($currentTheme) . '" style="--accent: ' . e($accentColor) . '; --accent-strong: ' . e($accentStrongColor) . ';"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
        . e($pageTitle)
        . '</title>' . $metaHead
        . '<meta name="theme-color" content="#2f6fed">'
        . '<link rel="manifest" href="' . e(asset_url('manifest.webmanifest')) . '">'
        . '<link rel="icon" href="' . e(asset_url('assets/icons/icon.svg')) . '" type="image/svg+xml">'
        . '<link rel="apple-touch-icon" href="' . e(asset_url('assets/icons/apple-touch-icon.png')) . '">'
        . '<link rel="stylesheet" href="' . e(asset_url('assets/css/app.css')) . '">'
        . '<script nonce="' . e($nonce) . '" src="https://cdn.tailwindcss.com"></script>'
        . '<script nonce="' . e($nonce) . '">tailwind.config={theme:{extend:{colors:{club:{900:"#0f172a",700:"#1d4ed8",500:"#3b82f6",100:"#dbeafe"}}}}};</script>'
        . '</head><body data-sw-url="' . e(base_url('sw.js')) . '">'
        . '<a class="skip-link" href="#main-content">Aller au contenu</a>'
        . '<header class="topbar"><div class="brand-wrap"><div class="brand-mark"><img class="brand-mark-img" src="' . e(asset_url('assets/logo/LOGO-CRD-HALO-2020.png')) . '" alt="Logo ON4CRD"></div><a class="brand" href="' . e(route_url('home')) . '">'
        . '<span class="brand-title">ON4CRD.be</span><span class="brand-subtitle">Club Radio Durnal</span></a></div>'
        . '<button class="menu-toggle button secondary" type="button" aria-controls="main-nav" aria-expanded="false"><span aria-hidden="true">☰</span><span class="menu-label">Menu</span></button>'
        . '<button class="nav-backdrop" type="button" aria-label="Fermer le menu" hidden></button>'
        . '<nav id="main-nav" class="nav" aria-label="Navigation principale">' . $navHtml . '<div class="nav-mobile-tools">' . $menuToolsHtml . '</div></nav>'
        . '<div class="toolbar">' . $menuToolsHtml . '</div></header>'
        . '<main id="main-content" class="layout container py-6">' . $flashHtml . $content . '</main>'
        . render_site_footer($currentRoute)
        . '<script nonce="' . e($nonce) . '" src="' . e(asset_url('assets/js/app.js')) . '" defer></script>'
        . '</body></html>';
}
}

function is_https_request(): bool
{
    $forwardedProtoHeader = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    $forwardedProto = $forwardedProtoHeader !== '' ? trim(explode(',', $forwardedProtoHeader)[0]) : '';
    $serverPort = (string) ($_SERVER['SERVER_PORT'] ?? '');

    return (
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || ($serverPort === '443')
        || ($forwardedProto === 'https')
    );
}

function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    return $nonce;
}


function mb_safe_substr(string $value, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null ? mb_substr($value, $start) : mb_substr($value, $start, $length);
    }

    return $length === null ? substr($value, $start) : substr($value, $start, $length);
}

function mb_safe_strtolower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function mb_safe_strtoupper(string $value): string
{
    return function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
}

function mb_safe_strimwidth(string $value, int $start, int $width, string $trimMarker = ''): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, $start, $width, $trimMarker);
    }

    $slice = substr($value, $start, $width);

    if (strlen($value) > ($start + $width) && $trimMarker !== '') {
        return rtrim($slice) . $trimMarker;
    }

    return $slice;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'n-a';
    }

    if (function_exists('iconv')) {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) {
            $value = $transliterated;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'n-a';
}

function sanitize_href_attribute(string $url): ?string
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return null;
    }

    if (preg_match('/^(?:javascript|data|vbscript):/i', $trimmed) === 1) {
        return null;
    }

    try {
        return normalize_http_url($trimmed, true);
    } catch (Throwable) {
        return null;
    }
}

function sanitize_image_src_attribute(string $url): ?string
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return null;
    }
    if (preg_match('/^data:image\\/(?:png|jpe?g|gif|webp);base64,[a-z0-9+\\/=]+$/i', $trimmed) === 1) {
        return $trimmed;
    }

    return sanitize_href_attribute($trimmed);
}

function sanitize_rich_html(string $html): string
{
    if ($html === '') {
        return '';
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $wrapped = '<!doctype html><html><body>' . $html . '</body></html>';
    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    $removeTags = ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'base'];
    foreach ($removeTags as $tag) {
        while (($nodes = $dom->getElementsByTagName($tag))->length > 0) {
            $node = $nodes->item(0);
            if ($node !== null && $node->parentNode !== null) {
                $node->parentNode->removeChild($node);
            } else {
                break;
            }
        }
    }

    $allNodes = $dom->getElementsByTagName('*');
    for ($i = $allNodes->length - 1; $i >= 0; $i--) {
        $node = $allNodes->item($i);
        if (!$node instanceof DOMElement || !$node->hasAttributes()) {
            continue;
        }
        $toRemove = [];
        foreach ($node->attributes as $attribute) {
            $name = strtolower($attribute->name);
            if (str_starts_with($name, 'on')) {
                $toRemove[] = $attribute->name;
                continue;
            }
            if ($name === 'href') {
                $safe = sanitize_href_attribute($attribute->value);
                if ($safe === null) {
                    $toRemove[] = $attribute->name;
                } else {
                    $node->setAttribute('href', $safe);
                }
            }
            if ($name === 'src') {
                $safe = sanitize_image_src_attribute($attribute->value);
                if ($safe === null) {
                    $toRemove[] = $attribute->name;
                } else {
                    $node->setAttribute('src', $safe);
                }
            }
        }
        foreach ($toRemove as $attrName) {
            $node->removeAttribute($attrName);
        }
        if (strtolower($node->tagName) === 'img' && !$node->hasAttribute('loading')) {
            $node->setAttribute('loading', 'lazy');
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) {
        return '';
    }

    $result = '';
    foreach ($body->childNodes as $child) {
        $result .= $dom->saveHTML($child);
    }

    return $result;
}

function safe_storage_public_path(string $path, array $allowedPrefixes = ['storage/press/']): string
{
    $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
    if ($normalized === '' || str_contains($normalized, '..')) {
        throw new RuntimeException('Chemin de stockage invalide.');
    }

    foreach ($allowedPrefixes as $prefix) {
        $prefix = ltrim(str_replace('\\', '/', trim($prefix)), '/');
        if ($prefix !== '' && str_starts_with($normalized, $prefix)) {
            return $normalized;
        }
    }

    throw new RuntimeException('Chemin de stockage non autorisé.');
}

function safe_storage_public_path_or_null(string $path, array $allowedPrefixes = ['storage/press/']): ?string
{
    try {
        return safe_storage_public_path($path, $allowedPrefixes);
    } catch (Throwable) {
        return null;
    }
}

function qsl_normalize_callsign(string $value): string
{
    $upper = mb_safe_strtoupper(trim($value));
    $upper = preg_replace('/\s*\/\s*/', '/', $upper) ?? '';
    $upper = preg_replace('/[^A-Z0-9\/]/', '', $upper) ?? '';

    return trim($upper, '/');
}

function qsl_normalize_date(string $value): string
{
    $digits = preg_replace('/[^0-9]/', '', trim($value)) ?? '';
    if (strlen($digits) >= 8) {
        return substr($digits, 0, 8);
    }

    return '';
}

function qsl_normalize_time(string $value): string
{
    $digits = preg_replace('/[^0-9]/', '', trim($value)) ?? '';
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) <= 2) {
        return str_pad($digits, 2, '0', STR_PAD_LEFT) . '00';
    }

    return str_pad(substr($digits, 0, 4), 4, '0', STR_PAD_LEFT);
}

function qsl_normalize_comment(string $value): string
{
    $clean = trim(preg_replace('/\s+/', ' ', $value) ?? '');

    return mb_safe_substr($clean, 0, 180);
}

function qsl_normalize_qsl_status(string $value): string
{
    $normalized = mb_safe_strtoupper(trim($value));
    if ($normalized === '') {
        return '';
    }

    $allowed = ['Y', 'N', 'R', 'Q', 'I', 'V'];
    $status = mb_safe_substr($normalized, 0, 1);
    return in_array($status, $allowed, true) ? $status : '';
}

function parse_adif(string $content): array
{
    $rows = [];
    if (trim($content) === '') {
        return $rows;
    }

    preg_match_all('/<([A-Z0-9_]+):(\d+)[^>]*>(.*?)((?=<[A-Z0-9_]+:\d+)|<EOR>|$)/is', $content, $matches, PREG_SET_ORDER);

    $record = [];
    foreach ($matches as $match) {
        $field = strtolower((string) $match[1]);
        $length = (int) $match[2];
        $raw = (string) $match[3];
        $value = substr($raw, 0, $length);
        $value = trim($value);

        if ($field === 'call') {
            $record['call'] = qsl_normalize_callsign($value);
        } elseif ($field === 'qso_date') {
            $record['qso_date'] = qsl_normalize_date($value);
        } elseif ($field === 'time_on') {
            $record['time_on'] = qsl_normalize_time($value);
        } elseif ($field === 'band') {
            $record['band'] = mb_safe_strtoupper($value);
        } elseif ($field === 'mode') {
            $record['mode'] = mb_safe_strtoupper($value);
        } elseif ($field === 'rst_sent') {
            $record['rst_sent'] = mb_safe_substr(trim($value), 0, 16);
        } elseif ($field === 'rst_rcvd') {
            $record['rst_recv'] = mb_safe_substr(trim($value), 0, 16);
        } elseif ($field === 'comment') {
            $record['comment'] = qsl_normalize_comment($value);
        } elseif ($field === 'eqsl_qsl_sent') {
            $record['eqsl_qsl_sent'] = qsl_normalize_qsl_status($value);
        } elseif ($field === 'eqsl_qsl_rcvd') {
            $record['eqsl_qsl_rcvd'] = qsl_normalize_qsl_status($value);
        } elseif ($field === 'qsl_sent') {
            $record['qsl_sent'] = qsl_normalize_qsl_status($value);
        } elseif ($field === 'qsl_rcvd') {
            $record['qsl_rcvd'] = qsl_normalize_qsl_status($value);
        } elseif ($field === 'lotw_qsl_sent') {
            $record['lotw_qsl_sent'] = qsl_normalize_qsl_status($value);
        } elseif ($field === 'lotw_qsl_rcvd') {
            $record['lotw_qsl_rcvd'] = qsl_normalize_qsl_status($value);
        }

        if (stripos((string) $match[4], '<EOR>') !== false) {
            if (($record['call'] ?? '') !== '') {
                $rows[] = $record;
            }
            $record = [];
        }
    }

    if ($record !== [] && ($record['call'] ?? '') !== '') {
        $rows[] = $record;
    }

    return $rows;
}

function qsl_format_display_date(string $value): string
{
    $normalized = qsl_normalize_date($value);
    if ($normalized === '' || strlen($normalized) !== 8) {
        return trim($value);
    }

    return substr($normalized, 6, 2) . '/' . substr($normalized, 4, 2) . '/' . substr($normalized, 0, 4);
}

function qsl_format_display_time(string $value): string
{
    $normalized = qsl_normalize_time($value);
    if ($normalized === '' || strlen($normalized) !== 4) {
        return trim($value);
    }

    return substr($normalized, 0, 2) . ':' . substr($normalized, 2, 2);
}

function build_qsl_svg_payload(array $user, array $data, string $comment = ''): array
{
    $ownCall = qsl_normalize_callsign((string) ($data['own_call'] ?? ($user['callsign'] ?? '')));
    $ownName = trim((string) ($data['own_name'] ?? ($user['full_name'] ?? '')));
    $ownQth = trim((string) ($data['own_qth'] ?? ($user['qth'] ?? '')));
    $qsoCall = qsl_normalize_callsign((string) ($data['qso_call'] ?? ($data['call'] ?? '')));
    $qsoDate = qsl_normalize_date((string) ($data['qso_date'] ?? ''));
    $timeOn = qsl_normalize_time((string) ($data['time_on'] ?? ''));
    $band = mb_safe_strtoupper(trim((string) ($data['band'] ?? '')));
    $mode = mb_safe_strtoupper(trim((string) ($data['mode'] ?? '')));
    $rstSent = mb_safe_substr(trim((string) ($data['rst_sent'] ?? '')), 0, 16);
    $rstRecv = mb_safe_substr(trim((string) ($data['rst_recv'] ?? '')), 0, 16);
    $payloadComment = qsl_normalize_comment($comment !== '' ? $comment : (string) ($data['comment'] ?? 'TNX QSO 73'));
    $templateName = trim((string) ($data['template_name'] ?? 'classic'));
    if ($templateName === '') {
        $templateName = 'classic';
    }
    $backgroundImage = trim((string) ($data['background_image_data_uri'] ?? ''));
    $backgroundPrimary = trim((string) ($data['background_primary'] ?? '#0b1f3a'));
    $backgroundSecondary = trim((string) ($data['background_secondary'] ?? '#1d4ed8'));

    return [
        'title' => (string) ($data['title'] ?? ''),
        'own_call' => $ownCall,
        'own_name' => $ownName,
        'own_qth' => $ownQth,
        'qso_call' => $qsoCall,
        'qso_date' => $qsoDate,
        'time_on' => $timeOn,
        'band' => $band,
        'mode' => $mode,
        'rst_sent' => $rstSent,
        'rst_recv' => $rstRecv,
        'comment' => $payloadComment,
        'template_name' => $templateName,
        'background_image_data_uri' => $backgroundImage,
        'background_primary' => preg_match('/^#[a-f0-9]{6}$/i', $backgroundPrimary) === 1 ? strtoupper($backgroundPrimary) : '#0B1F3A',
        'background_secondary' => preg_match('/^#[a-f0-9]{6}$/i', $backgroundSecondary) === 1 ? strtoupper($backgroundSecondary) : '#1D4ED8',
    ];
}

function qsl_card_title(array $payload): string
{
    $call = qsl_normalize_callsign((string) ($payload['qso_call'] ?? ''));
    $date = qsl_normalize_date((string) ($payload['qso_date'] ?? ''));
    $band = mb_safe_strtoupper(trim((string) ($payload['band'] ?? '')));
    $mode = mb_safe_strtoupper(trim((string) ($payload['mode'] ?? '')));

    $chunks = ['QSL'];
    if ($call !== '') {
        $chunks[] = $call;
    }
    if ($date !== '') {
        $chunks[] = qsl_format_display_date($date);
    }
    if ($band !== '') {
        $chunks[] = $band;
    }
    if ($mode !== '') {
        $chunks[] = $mode;
    }

    return mb_safe_substr(implode(' • ', $chunks), 0, 190);
}

function import_adif_records(int $memberId, array $records): int
{
    if ($memberId <= 0 || $records === [] || !table_exists('qso_logs')) {
        return 0;
    }

    $existingStmt = db()->prepare(
        'SELECT id FROM qso_logs
         WHERE member_id = ? AND qso_call = ? AND COALESCE(qso_date, \'\') = ? AND COALESCE(time_on, \'\') = ?
         LIMIT 1'
    );
    $insertStmt = db()->prepare(
        'INSERT INTO qso_logs (member_id, qso_call, qso_date, time_on, band, mode, rst_sent, rst_recv, raw_payload)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $created = 0;
    foreach ($records as $row) {
        if (!is_array($row)) {
            continue;
        }

        $payload = build_qsl_svg_payload([], [
            'qso_call' => (string) ($row['call'] ?? ''),
            'qso_date' => (string) ($row['qso_date'] ?? ''),
            'time_on' => (string) ($row['time_on'] ?? ''),
            'band' => (string) ($row['band'] ?? ''),
            'mode' => (string) ($row['mode'] ?? ''),
            'rst_sent' => (string) ($row['rst_sent'] ?? ''),
            'rst_recv' => (string) ($row['rst_recv'] ?? ''),
            'comment' => (string) ($row['comment'] ?? ''),
        ]);

        if ($payload['qso_call'] === '') {
            continue;
        }

        $existingStmt->execute([$memberId, $payload['qso_call'], $payload['qso_date'], $payload['time_on']]);
        if ($existingStmt->fetchColumn()) {
            continue;
        }

        $insertStmt->execute([
            $memberId,
            $payload['qso_call'],
            $payload['qso_date'] !== '' ? $payload['qso_date'] : null,
            $payload['time_on'] !== '' ? $payload['time_on'] : null,
            $payload['band'] !== '' ? $payload['band'] : null,
            $payload['mode'] !== '' ? $payload['mode'] : null,
            $payload['rst_sent'] !== '' ? $payload['rst_sent'] : null,
            $payload['rst_recv'] !== '' ? $payload['rst_recv'] : null,
            json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $created++;
    }

    return $created;
}

if (!function_exists('answer_question_from_knowledge')) {
    /**
     * @return array{answer:string,source:string}
     */
function answer_question_from_knowledge(string $question): array
{
        $normalized = mb_safe_strtolower(trim($question));
        if ($normalized === '') {
            return ['answer' => 'Je n’ai pas reçu de question exploitable.', 'source' => 'Assistant Raymond'];
        }

        $knowledgePath = __DIR__ . '/knowledge.php';
        $knowledgeBase = [];
        if (is_file($knowledgePath)) {
            $loaded = require $knowledgePath;
            if (is_array($loaded)) {
                $knowledgeBase = $loaded;
            }
        }

        $bestScore = -1;
        $bestItem = null;
        foreach ($knowledgeBase as $item) {
            if (!is_array($item)) {
                continue;
            }
            $score = 0;
            $keywords = isset($item['keywords']) && is_array($item['keywords']) ? $item['keywords'] : [];
            foreach ($keywords as $keyword) {
                $needle = mb_safe_strtolower(trim((string) $keyword));
                if ($needle !== '' && str_contains($normalized, $needle)) {
                    $score += 3;
                }
            }
            $title = mb_safe_strtolower((string) ($item['title'] ?? ''));
            if ($title !== '' && str_contains($normalized, $title)) {
                $score += 2;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestItem = $item;
            }
        }

        if ($bestItem !== null && $bestScore > 0) {
            return [
                'answer' => trim((string) ($bestItem['body'] ?? 'Je n’ai pas de réponse précise pour le moment.')),
                'source' => trim((string) ($bestItem['source'] ?? 'Base de connaissances ON4CRD')),
            ];
        }

        if (table_exists('articles')) {
            try {
                $like = '%' . $question . '%';
                $stmt = db()->prepare('SELECT title, excerpt, slug FROM articles WHERE status = "published" AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?) ORDER BY updated_at DESC LIMIT 1');
                $stmt->execute([$like, $like, $like]);
                $article = $stmt->fetch();
                if (is_array($article)) {
                    $title = trim((string) ($article['title'] ?? 'Article'));
                    $excerpt = trim((string) ($article['excerpt'] ?? ''));
                    $slug = trim((string) ($article['slug'] ?? ''));
                    $url = $slug !== '' ? base_url('index.php?route=article&slug=' . urlencode($slug)) : '';
                    $answer = 'J’ai trouvé un article pertinent : ' . $title . '.';
                    if ($excerpt !== '') {
                        $answer .= "\n\nRésumé : " . $excerpt;
                    }
                    if ($url !== '') {
                        $answer .= "\n\nLien : " . $url;
                    }
                    return ['answer' => $answer, 'source' => 'Articles ON4CRD'];
                }
            } catch (Throwable) {
                // fallback below
            }
        }

        return [
            'answer' => 'Je n’ai pas de réponse précise pour cette question. Essayez de mentionner un mot-clé (QSL, antenne, propagation, licence) ou consultez le module Articles.',
            'source' => 'Assistant Raymond',
        ];
    }
}

/**
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function localized_article_row(array $row): array
{
    $locale = current_locale();
    if ($locale === 'fr') {
        return $row;
    }

    $articleId = (int) ($row['id'] ?? 0);
    if ($articleId <= 0 || !table_exists('article_translations')) {
        return $row;
    }

    try {
        $stmt = db()->prepare('SELECT title, excerpt, content FROM article_translations WHERE article_id = ? AND locale = ? ORDER BY CASE status WHEN "reviewed" THEN 0 WHEN "auto" THEN 1 ELSE 2 END, updated_at DESC LIMIT 1');
        $stmt->execute([$articleId, $locale]);
        $translation = $stmt->fetch();
        if (!is_array($translation)) {
            return $row;
        }

        foreach (['title', 'excerpt', 'content'] as $field) {
            $value = trim((string) ($translation[$field] ?? ''));
            if ($value !== '') {
                $row[$field] = $value;
            }
        }
    } catch (Throwable) {
        return $row;
    }

    return $row;
}

function article_translation_upsert(int $articleId, string $locale, ?string $title = null, ?string $summary = null, ?string $content = null): void
{
    if ($articleId <= 0 || $locale === 'fr' || !table_exists('article_translations')) {
        return;
    }

    $sourceStmt = db()->prepare('SELECT title, excerpt, content FROM articles WHERE id = ? LIMIT 1');
    $sourceStmt->execute([$articleId]);
    $source = $sourceStmt->fetch();
    if (!is_array($source)) {
        return;
    }

    $finalTitle = trim((string) ($title ?? ''));
    $finalExcerpt = trim((string) ($summary ?? ''));
    $finalContent = trim((string) ($content ?? ''));
    if ($finalTitle === '') {
        $finalTitle = (string) ($source['title'] ?? '');
    }
    if ($finalExcerpt === '') {
        $finalExcerpt = (string) ($source['excerpt'] ?? '');
    }
    if ($finalContent === '') {
        $finalContent = (string) ($source['content'] ?? '');
    }

    $status = ($title === null && $summary === null && $content === null) ? 'auto' : 'needs_review';

    $update = db()->prepare('UPDATE article_translations SET title = ?, excerpt = ?, content = ?, status = ?, updated_at = NOW() WHERE article_id = ? AND locale = ?');
    $update->execute([$finalTitle, $finalExcerpt, $finalContent, $status, $articleId, $locale]);
    if ($update->rowCount() > 0) {
        return;
    }

    db()->prepare('INSERT INTO article_translations (article_id, locale, title, excerpt, content, status) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$articleId, $locale, $finalTitle, $finalExcerpt, $finalContent, $status]);
}

function parse_price_to_cents(string $price): int
{
    $normalized = str_replace([' ', "\xc2\xa0"], '', trim($price));
    $normalized = str_replace(',', '.', $normalized);
    $normalized = preg_replace('/[^0-9.\-]/', '', $normalized) ?? '0';
    if ($normalized === '' || $normalized === '-' || $normalized === '.') {
        return 0;
    }

    return (int) max(0, round(((float) $normalized) * 100));
}

function format_price_eur(int $cents): string
{
    $amount = max(0, $cents) / 100;
    return number_format($amount, 2, ',', ' ') . ' €';
}

function format_integer_or_unlimited(?int $value): string
{
    return $value === null ? '∞' : (string) max(0, $value);
}

/**
 * @return array<string, array{label:string,width:int,height:int}>
 */
function ad_format_catalog(): array
{
    return [
        'square' => ['label' => 'Carré (1080×1080)', 'width' => 1080, 'height' => 1080],
        'landscape' => ['label' => 'Paysage (1200×628)', 'width' => 1200, 'height' => 628],
        'portrait' => ['label' => 'Portrait (1080×1350)', 'width' => 1080, 'height' => 1350],
    ];
}

function ad_format_label(string $formatCode): string
{
    $catalog = ad_format_catalog();
    return (string) ($catalog[$formatCode]['label'] ?? $formatCode);
}

function ad_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'En attente',
        'active' => 'Active',
        'paused' => 'En pause',
        'expired' => 'Expirée',
        'rejected' => 'Refusée',
        default => ucfirst($status),
    };
}

/**
 * @param array<string,mixed> $ad
 */
function ad_runtime_status(array $ad): string
{
    $status = (string) ($ad['status'] ?? 'pending');
    if ($status !== 'active') {
        return $status;
    }
    $endsAt = (string) ($ad['ends_at'] ?? '');
    if ($endsAt !== '' && strtotime($endsAt) !== false && strtotime($endsAt) < time()) {
        return 'expired';
    }
    return 'active';
}

/**
 * @return array<int, array{code:string,label:string}>
 */
function available_ad_placements(): array
{
    return [
        ['code' => 'home_hero', 'label' => 'Accueil (hero)'],
        ['code' => 'home_sidebar', 'label' => 'Accueil (latéral)'],
        ['code' => 'news_inline', 'label' => 'Actualités (inline)'],
    ];
}

/**
 * @return array<int, array{code:string,label:string}>
 */
function ad_placements_for_member(int $memberId): array
{
    return available_ad_placements();
}

/**
 * @return array<int, array<string,mixed>>
 */
function member_ads(int $memberId, bool $ownerOnly = true): array
{
    if (!table_exists('ads')) {
        return [];
    }

    $placementMap = [];
    foreach (available_ad_placements() as $placement) {
        $placementMap[(string) ($placement['code'] ?? '')] = (string) ($placement['label'] ?? '');
    }

    if ($ownerOnly) {
        $stmt = db()->prepare('SELECT a.* FROM ads a WHERE a.owner_member_id = ? ORDER BY a.updated_at DESC, a.id DESC');
        $stmt->execute([$memberId]);
        $rows = $stmt->fetchAll() ?: [];
    } else {
        $rows = db()->query('SELECT a.* FROM ads a ORDER BY a.updated_at DESC, a.id DESC')->fetchAll() ?: [];
    }

    foreach ($rows as &$row) {
        $row['runtime_status'] = ad_runtime_status($row);
        $code = (string) ($row['placement_code'] ?? '');
        $row['placement_name'] = $placementMap[$code] ?? $code;
        $row['owner_callsign'] = (string) ($row['owner_callsign'] ?? '');
    }
    unset($row);

    return $rows;
}

/**
 * @return array<int, array<string,mixed>>
 */
function committee_members(): array
{
    if (!table_exists('committee_members')) {
        return [];
    }
    return db()->query('SELECT * FROM committee_members WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll() ?: [];
}

function placeholder_avatar(string $seed = '', int $size = 256): string
{
    return 'assets/icons/icon-192.png';
}

function editorial_text(string $slot, string $fallback = ''): string
{
    if (table_exists('editorial_contents')) {
        $locale = current_locale();
        $stmt = db()->prepare('SELECT fr, en, de, nl FROM editorial_contents WHERE slot = ? LIMIT 1');
        $stmt->execute([$slot]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            $candidate = trim((string) ($row[$locale] ?? ''));
            if ($candidate === '') {
                $candidate = trim((string) ($row['fr'] ?? ''));
            }
            if ($candidate !== '') {
                return $candidate;
            }
        }
    }

    $defaults = [
        'committee.title' => 'Comité',
        'committee.intro' => 'Présentation du comité du radio club.',
        'committee.mission' => 'Transparence',
        'committee.onboarding' => 'Accueil des membres',
        'committee.contact_title' => 'Contact',
        'committee.contact_text' => 'Le comité est disponible pour vos questions.',
    ];

    return (string) ($defaults[$slot] ?? $fallback);
}

/**
 * @return array<string,mixed>|null
 */
function editorial_content_row(string $slot): ?array
{
    if ($slot === '' || !table_exists('editorial_contents')) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM editorial_contents WHERE slot = ? LIMIT 1');
    $stmt->execute([$slot]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function save_editorial_content(string $slot, string $fr = '', string $en = '', string $de = '', string $nl = ''): void
{
    if ($slot === '' || !table_exists('editorial_contents')) {
        return;
    }
    $update = db()->prepare('UPDATE editorial_contents SET fr = ?, en = ?, de = ?, nl = ?, updated_at = NOW() WHERE slot = ?');
    $update->execute([$fr, $en, $de, $nl, $slot]);
    if ($update->rowCount() > 0) {
        return;
    }
    db()->prepare('INSERT INTO editorial_contents (slot, fr, en, de, nl) VALUES (?, ?, ?, ?, ?)')
        ->execute([$slot, $fr, $en, $de, $nl]);
}

function news_status_label(string $status): string
{
    return match ($status) {
        'draft' => 'Brouillon',
        'pending' => 'En attente',
        'published' => 'Publié',
        'archived' => 'Archivé',
        default => ucfirst($status),
    };
}

/**
 * @return int[]
 */
function managed_section_ids_for_member(int $memberId): array
{
    if ($memberId <= 0 || !table_exists('news_section_managers')) {
        return [];
    }
    $stmt = db()->prepare('SELECT section_id FROM news_section_managers WHERE member_id = ?');
    $stmt->execute([$memberId]);
    return array_values(array_unique(array_map('intval', array_column($stmt->fetchAll() ?: [], 'section_id'))));
}

function can_submit_news_in_section(int|array $user, int $sectionId): bool
{
    if ($sectionId <= 0) {
        return false;
    }
    if (is_array($user) && ((int) ($user['is_admin'] ?? 0) === 1)) {
        return true;
    }
    $memberId = is_array($user) ? (int) ($user['id'] ?? 0) : (int) $user;
    if ($memberId <= 0) {
        return false;
    }
    return in_array($sectionId, managed_section_ids_for_member($memberId), true);
}

function news_translation_upsert(int $newsId, string $locale, ?string $title = null, ?string $summary = null, ?string $content = null): void
{
    if ($newsId <= 0 || $locale === 'fr' || !table_exists('news_translations')) {
        return;
    }
    $src = db()->prepare('SELECT title, excerpt AS summary, content FROM news_posts WHERE id = ? LIMIT 1');
    $src->execute([$newsId]);
    $row = $src->fetch();
    if (!is_array($row)) {
        return;
    }
    $finalTitle = trim((string) ($title ?? '')) ?: (string) ($row['title'] ?? '');
    $finalSummary = trim((string) ($summary ?? '')) ?: (string) ($row['summary'] ?? '');
    $finalContent = trim((string) ($content ?? '')) ?: (string) ($row['content'] ?? '');
    $status = ($title === null && $summary === null && $content === null) ? 'auto' : 'needs_review';

    $update = db()->prepare('UPDATE news_translations SET title = ?, summary = ?, content = ?, status = ?, updated_at = NOW() WHERE news_id = ? AND locale = ?');
    $update->execute([$finalTitle, $finalSummary, $finalContent, $status, $newsId, $locale]);
    if ($update->rowCount() > 0) {
        return;
    }
    db()->prepare('INSERT INTO news_translations (news_id, locale, title, summary, content, status) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$newsId, $locale, $finalTitle, $finalSummary, $finalContent, $status]);
}

/**
 * @return array<int,array<string,mixed>>
 */
function press_contacts(): array
{
    if (!table_exists('press_contacts')) {
        return [];
    }
    return db()->query('SELECT * FROM press_contacts ORDER BY sort_order ASC, id ASC')->fetchAll() ?: [];
}

/**
 * @return array<int,array<string,mixed>>
 */
function latest_press_releases(int $limit = 20): array
{
    if (!table_exists('press_releases')) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    return db()->query('SELECT * FROM press_releases ORDER BY release_date DESC, id DESC LIMIT ' . (int) $limit)->fetchAll() ?: [];
}

function notify_album_webhooks(array $album): void
{
    if (!table_exists('webhooks')) {
        return;
    }
    $targets = db()->query('SELECT url FROM webhooks WHERE is_active = 1 AND event IN ("album.updated","album.created","*")')->fetchAll() ?: [];
    if ($targets === []) {
        return;
    }
    $payload = json_encode([
        'event' => 'album.updated',
        'album' => [
            'id' => (int) ($album['id'] ?? 0),
            'title' => (string) ($album['title'] ?? ''),
            'slug' => (string) ($album['slug'] ?? ''),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($payload)) {
        return;
    }
    foreach ($targets as $target) {
        $url = trim((string) ($target['url'] ?? ''));
        if ($url === '') {
            continue;
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 3,
                'ignore_errors' => true,
            ],
        ]);
        @file_get_contents($url, false, $ctx);
    }
}

/**
 * @return array<string,mixed>|null
 */
function ad_fetch_by_id(int $adId): ?array
{
    if ($adId <= 0 || !table_exists('ads')) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM ads WHERE id = ? LIMIT 1');
    $stmt->execute([$adId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

/**
 * @return array<int, array{day:string,impressions:int,clicks:int}>
 */
function ad_daily_stats(int $adId): array
{
    if ($adId <= 0 || !table_exists('ad_events')) {
        return [];
    }
    $stmt = db()->prepare('SELECT DATE(created_at) AS day, SUM(event_type = "view") AS impressions, SUM(event_type = "click") AS clicks FROM ad_events WHERE ad_id = ? GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 30');
    $stmt->execute([$adId]);
    return $stmt->fetchAll() ?: [];
}

function log_ad_event(int $adId, string $eventType, string $placementCode = ''): void
{
    if ($adId <= 0 || !table_exists('ad_events')) {
        return;
    }
    db()->prepare('INSERT INTO ad_events (ad_id, event_type, placement_code, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)')
        ->execute([
            $adId,
            $eventType,
            $placementCode,
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
}

function create_qsl_cards_from_qsos(int $memberId, array $qsoIds, string $templateName = 'classic'): int
{
    if ($memberId <= 0 || $qsoIds === [] || !table_exists('qso_logs') || !table_exists('qsl_cards')) {
        return 0;
    }
    $normalizedTemplate = strtolower(trim($templateName));
    if (!in_array($normalizedTemplate, ['classic', 'classic_duplex'], true)) {
        $normalizedTemplate = 'classic';
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $qsoIds), static fn (int $id): bool => $id > 0)));
    if ($ids === []) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$memberId], $ids);
    $qsoStmt = db()->prepare(
        "SELECT id, qso_call, qso_date, time_on, band, mode, rst_sent, rst_recv
         FROM qso_logs
         WHERE member_id = ? AND id IN ($placeholders)
         ORDER BY id DESC"
    );
    $qsoStmt->execute($params);
    $rows = $qsoStmt->fetchAll();
    if (!is_array($rows) || $rows === []) {
        return 0;
    }

    $memberStmt = db()->prepare('SELECT id, username, email, callsign, full_name, qth FROM users WHERE id = ? LIMIT 1');
    $memberStmt->execute([$memberId]);
    $member = $memberStmt->fetch();
    if (!is_array($member)) {
        $member = ['id' => $memberId, 'callsign' => '', 'full_name' => '', 'qth' => ''];
    }

    $existsStmt = db()->prepare(
        'SELECT id FROM qsl_cards
         WHERE member_id = ? AND qso_call = ? AND COALESCE(qso_date, \'\') = ? AND COALESCE(time_on, \'\') = ?
         LIMIT 1'
    );
    $insertStmt = db()->prepare(
        'INSERT INTO qsl_cards (member_id, title, qso_call, qso_date, time_on, band, mode, rst_sent, rst_recv, template_name, svg_content)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $created = 0;
    foreach ($rows as $row) {
        $payload = build_qsl_svg_payload($member, [
            'qso_call' => (string) ($row['qso_call'] ?? ''),
            'qso_date' => (string) ($row['qso_date'] ?? ''),
            'time_on' => (string) ($row['time_on'] ?? ''),
            'band' => (string) ($row['band'] ?? ''),
            'mode' => (string) ($row['mode'] ?? ''),
            'rst_sent' => (string) ($row['rst_sent'] ?? ''),
            'rst_recv' => (string) ($row['rst_recv'] ?? ''),
            'comment' => 'TNX QSO 73',
        ]);
        if ($payload['qso_call'] === '') {
            continue;
        }

        $existsStmt->execute([$memberId, $payload['qso_call'], $payload['qso_date'], $payload['time_on']]);
        if ($existsStmt->fetchColumn()) {
            continue;
        }

        $svg = generate_qsl_svg($payload);
        $insertStmt->execute([
            $memberId,
            qsl_card_title($payload),
            $payload['qso_call'],
            $payload['qso_date'] !== '' ? $payload['qso_date'] : null,
            $payload['time_on'] !== '' ? $payload['time_on'] : null,
            $payload['band'] !== '' ? $payload['band'] : null,
            $payload['mode'] !== '' ? $payload['mode'] : null,
            $payload['rst_sent'] !== '' ? $payload['rst_sent'] : null,
            $payload['rst_recv'] !== '' ? $payload['rst_recv'] : null,
            $normalizedTemplate,
            $svg,
        ]);
        $created++;
    }

    return $created;
}

function qsl_template_supports_back(string $templateName): bool
{
    return strtolower(trim($templateName)) === 'classic_duplex';
}

function sanitize_svg_document(string $svg): string
{
    $normalized = strtolower($svg);
    $dangerousPatterns = [
        '/<\s*script\b/i',
        '/<\s*(iframe|object|embed|foreignobject)\b/i',
        '/\s+on[a-z0-9:_-]+\s*=/i',
        '/(?:href|xlink:href)\s*=\s*["\']?\s*javascript:/i',
        '/style\s*=\s*["\'][^"\']*url\s*\(/i',
    ];

    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $svg) === 1) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="500" viewBox="0 0 900 500"><rect width="900" height="500" fill="#0f172a"/><text x="450" y="250" text-anchor="middle" fill="#f8fafc" font-family="Arial, sans-serif" font-size="28">QSL sécurisée indisponible</text></svg>';
        }
    }

    if (str_contains($normalized, 'javascript:')) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="500" viewBox="0 0 900 500"><rect width="900" height="500" fill="#0f172a"/><text x="450" y="250" text-anchor="middle" fill="#f8fafc" font-family="Arial, sans-serif" font-size="28">QSL sécurisée indisponible</text></svg>';
    }

    if (preg_match_all('/(?:href|xlink:href)\s*=\s*["\']([^"\']+)["\']/i', $svg, $matches) > 0 && isset($matches[1])) {
        foreach ($matches[1] as $href) {
            $candidate = strtolower(trim((string) $href));
            if (str_starts_with($candidate, 'data:image/')) {
                if (preg_match('/^data:image\/(?:png|jpe?g|webp);base64,[a-z0-9+\/=]+$/i', $candidate) !== 1) {
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="500" viewBox="0 0 900 500"><rect width="900" height="500" fill="#0f172a"/><text x="450" y="250" text-anchor="middle" fill="#f8fafc" font-family="Arial, sans-serif" font-size="28">QSL sécurisée indisponible</text></svg>';
                }
            } elseif (str_starts_with($candidate, 'data:')) {
                return '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="500" viewBox="0 0 900 500"><rect width="900" height="500" fill="#0f172a"/><text x="450" y="250" text-anchor="middle" fill="#f8fafc" font-family="Arial, sans-serif" font-size="28">QSL sécurisée indisponible</text></svg>';
            }
        }
    }

    return $svg;
}

function generate_qsl_svg(array $payload): string
{
    $ownCall = e(qsl_normalize_callsign((string) ($payload['own_call'] ?? '')));
    $qsoCall = e(qsl_normalize_callsign((string) ($payload['qso_call'] ?? '')));
    $ownName = e(trim((string) ($payload['own_name'] ?? '')));
    $ownQth = e(trim((string) ($payload['own_qth'] ?? '')));
    $date = e(qsl_normalize_date((string) ($payload['qso_date'] ?? '')));
    $time = e(qsl_normalize_time((string) ($payload['time_on'] ?? '')));
    $band = e(mb_safe_strtoupper(trim((string) ($payload['band'] ?? ''))));
    $mode = e(mb_safe_strtoupper(trim((string) ($payload['mode'] ?? ''))));
    $rstSent = e(trim((string) ($payload['rst_sent'] ?? '')));
    $rstRecv = e(trim((string) ($payload['rst_recv'] ?? '')));
    $comment = e(qsl_normalize_comment((string) ($payload['comment'] ?? 'TNX QSO 73')));
    $title = e(trim((string) ($payload['title'] ?? 'QSL Card')));
    $backgroundPrimary = e(trim((string) ($payload['background_primary'] ?? '#0B1F3A')));
    $backgroundSecondary = e(trim((string) ($payload['background_secondary'] ?? '#1D4ED8')));
    $backgroundImage = trim((string) ($payload['background_image_data_uri'] ?? ''));
    $templateName = trim((string) ($payload['template_name'] ?? 'classic'));
    $isDuplex = qsl_template_supports_back($templateName);
    $backgroundLayer = '<rect width="900" height="500" fill="url(#qsl-bg-gradient)"/>';
    if ($backgroundImage !== '') {
        $safeBackground = e($backgroundImage);
        $backgroundLayer = '<image href="' . $safeBackground . '" x="0" y="0" width="900" height="500" preserveAspectRatio="xMidYMid slice"/>'
            . '<rect width="900" height="500" fill="rgba(8, 15, 32, .38)"/>';
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="500" viewBox="0 0 900 500">'
        . '<defs><linearGradient id="qsl-bg-gradient" x1="0%" y1="0%" x2="100%" y2="100%">'
        . '<stop offset="0%" stop-color="' . $backgroundPrimary . '"/>'
        . '<stop offset="100%" stop-color="' . $backgroundSecondary . '"/>'
        . '</linearGradient></defs>'
        . $backgroundLayer
        . '<text x="40" y="70" fill="#e2e8f0" font-size="42" font-family="Arial, sans-serif" font-weight="700">' . $title . '</text>'
        . '<text x="40" y="130" fill="#f8fafc" font-size="30" font-family="Arial, sans-serif">DE: ' . $ownCall . '</text>'
        . '<text x="40" y="170" fill="#cbd5e1" font-size="22" font-family="Arial, sans-serif">' . $ownName . ' • ' . $ownQth . '</text>'
        . '<text x="40" y="250" fill="#f8fafc" font-size="34" font-family="Arial, sans-serif">TO: ' . $qsoCall . '</text>';

    if ($isDuplex) {
        $svg .= '<text x="40" y="395" fill="#e2e8f0" font-size="20" font-family="Arial, sans-serif">QSL recto — détails au verso</text>';
    } else {
        $svg .= '<text x="40" y="305" fill="#cbd5e1" font-size="22" font-family="Arial, sans-serif">DATE ' . $date . '  UTC ' . $time . '  BAND ' . $band . '  MODE ' . $mode . '</text>'
            . '<text x="40" y="345" fill="#cbd5e1" font-size="22" font-family="Arial, sans-serif">RST S/R: ' . $rstSent . ' / ' . $rstRecv . '</text>'
            . '<text x="40" y="395" fill="#f8fafc" font-size="20" font-family="Arial, sans-serif">' . $comment . '</text>';
    }

    $svg .= '</svg>';

    return sanitize_svg_document($svg);
}

function generate_qsl_back_svg(array $payload): string
{
    $ownCall = e(qsl_normalize_callsign((string) ($payload['own_call'] ?? '')));
    $qsoCall = e(qsl_normalize_callsign((string) ($payload['qso_call'] ?? '')));
    $ownName = e(trim((string) ($payload['own_name'] ?? '')));
    $ownQth = e(trim((string) ($payload['own_qth'] ?? '')));
    $date = e(qsl_normalize_date((string) ($payload['qso_date'] ?? '')));
    $time = e(qsl_normalize_time((string) ($payload['time_on'] ?? '')));
    $band = e(mb_safe_strtoupper(trim((string) ($payload['band'] ?? ''))));
    $mode = e(mb_safe_strtoupper(trim((string) ($payload['mode'] ?? ''))));
    $rstSent = e(trim((string) ($payload['rst_sent'] ?? '')));
    $rstRecv = e(trim((string) ($payload['rst_recv'] ?? '')));
    $comment = e(qsl_normalize_comment((string) ($payload['comment'] ?? 'TNX QSO 73')));

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="500" viewBox="0 0 900 500">'
        . '<rect width="900" height="500" fill="#f8fafc"/>'
        . '<rect x="18" y="18" width="864" height="464" fill="none" stroke="#1f2937" stroke-width="3"/>'
        . '<text x="40" y="70" fill="#0f172a" font-size="40" font-family="Arial, sans-serif" font-weight="700">QSL Confirmation (Verso)</text>'
        . '<text x="40" y="115" fill="#334155" font-size="20" font-family="Arial, sans-serif">DE: ' . $ownCall . ' • TO: ' . $qsoCall . '</text>'
        . '<text x="40" y="165" fill="#0f172a" font-size="22" font-family="Arial, sans-serif">Operator: ' . $ownName . '</text>'
        . '<text x="40" y="200" fill="#0f172a" font-size="22" font-family="Arial, sans-serif">QTH: ' . $ownQth . '</text>'
        . '<text x="40" y="250" fill="#0f172a" font-size="22" font-family="Arial, sans-serif">Date: ' . $date . '    UTC: ' . $time . '</text>'
        . '<text x="40" y="285" fill="#0f172a" font-size="22" font-family="Arial, sans-serif">Band: ' . $band . '    Mode: ' . $mode . '</text>'
        . '<text x="40" y="320" fill="#0f172a" font-size="22" font-family="Arial, sans-serif">RST S/R: ' . $rstSent . ' / ' . $rstRecv . '</text>'
        . '<text x="40" y="370" fill="#334155" font-size="20" font-family="Arial, sans-serif">' . $comment . '</text>'
        . '<text x="40" y="440" fill="#475569" font-size="18" font-family="Arial, sans-serif">Merci pour le contact — 73 !</text>'
        . '</svg>';

    return sanitize_svg_document($svg);
}

function qsl_background_upload_to_data_uri(?array $upload): string
{
    if (!is_array($upload)) {
        return '';
    }
    $errorCode = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Le téléversement de l’image de fond QSL a échoué.');
    }

    $tmpPath = (string) ($upload['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Image de fond QSL invalide.');
    }

    $mime = detect_uploaded_mime_type($tmpPath);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException('Image de fond non supportée (JPG, PNG ou WEBP).');
    }
    $size = (int) ($upload['size'] ?? 0);
    if ($size <= 0 || $size > 6 * 1024 * 1024) {
        throw new RuntimeException('Image de fond trop volumineuse (max 6 Mo).');
    }

    $extension = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => '',
    };
    assert_upload_file_is_valid_signature($tmpPath, [$extension]);
    $sanitizedTmpPath = sanitize_uploaded_image_file($tmpPath, $extension);
    $raw = @file_get_contents($sanitizedTmpPath);
    if ($sanitizedTmpPath !== $tmpPath) {
        @unlink($sanitizedTmpPath);
    }
    if ($raw === false) {
        throw new RuntimeException('Image de fond QSL illisible.');
    }

    return 'data:' . $mime . ';base64,' . base64_encode($raw);
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || strlen($_SESSION['_csrf']) !== 64) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function preferred_locale_from_accept_language(string $header, array $supportedLocales = ['fr', 'en', 'de', 'nl']): string
{
    $normalized = strtolower(trim($header));
    if ($normalized === '') {
        return 'en';
    }
    $chunks = preg_split('/\s*,\s*/', $normalized) ?: [];
    foreach ($chunks as $chunk) {
        if ($chunk === '') {
            continue;
        }
        $localePart = explode(';', $chunk)[0] ?? '';
        $localePart = trim($localePart);
        if ($localePart === '') {
            continue;
        }
        $base = explode('-', $localePart)[0] ?? $localePart;
        if (in_array($base, $supportedLocales, true)) {
            return $base;
        }
    }

    return 'en';
}

function initialize_user_preferences(): void
{
    $supportedLocales = ['fr', 'en', 'de', 'nl'];
    $supportedThemes = ['light', 'dark'];
    $supportedAccents = ['blue', 'emerald', 'violet', 'red', 'amber', 'orange'];

    $cookieLocale = strtolower((string) ($_COOKIE['on4crd_locale'] ?? ''));
    $cookieTheme = strtolower((string) ($_COOKIE['on4crd_theme'] ?? ''));
    $cookieAccent = strtolower((string) ($_COOKIE['on4crd_accent'] ?? ''));

    if (!isset($_SESSION['locale'])) {
        if (in_array($cookieLocale, $supportedLocales, true)) {
            $_SESSION['locale'] = $cookieLocale;
        } else {
            $_SESSION['locale'] = preferred_locale_from_accept_language((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''), $supportedLocales);
        }
    }
    if (!isset($_SESSION['theme'])) {
        $_SESSION['theme'] = in_array($cookieTheme, $supportedThemes, true) ? $cookieTheme : 'dark';
    }
    if (!isset($_SESSION['accent'])) {
        $_SESSION['accent'] = in_array($cookieAccent, $supportedAccents, true) ? $cookieAccent : 'blue';
    }
}

function verify_csrf(): void
{
    $sessionToken = (string) ($_SESSION['_csrf'] ?? '');
    $postToken = (string) ($_POST['_csrf'] ?? '');
    $headerToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $submittedToken = $postToken !== '' ? $postToken : $headerToken;
    if ($sessionToken === '' || $submittedToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        throw new RuntimeException('Jeton CSRF invalide.');
    }
}

function matomo_origin(): ?string
{
    $matomoUrl = trim((string) config('tracking.matomo_url', ''));
    if ($matomoUrl === '') {
        return null;
    }

    $parts = parse_url($matomoUrl);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return null;
    }

    $origin = $parts['scheme'] . '://' . $parts['host'];
    if (!empty($parts['port'])) {
        $origin .= ':' . $parts['port'];
    }

    return $origin;
}

function apply_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    $nonce = csp_nonce();
    $scriptSrc = ["'self'", "'nonce-" . $nonce . "'", 'https://cdn.tailwindcss.com', 'https://cdn.jsdelivr.net'];
    $imgSrc = ["'self'", 'data:', 'https:'];
    $styleSrc = ["'self'", "'unsafe-inline'", 'https://cdn.jsdelivr.net'];
    $connectSrc = ["'self'"];
    $frameSrc = ["'self'", 'https://www.google.com', 'https://maps.google.com'];

    $matomoOrigin = matomo_origin();
    if ($matomoOrigin !== null) {
        $scriptSrc[] = $matomoOrigin;
        $imgSrc[] = $matomoOrigin;
        $connectSrc[] = $matomoOrigin;
    }

    $csp = [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
        "object-src 'none'",
        "manifest-src 'self'",
        "worker-src 'self'",
        'frame-src ' . implode(' ', array_unique($frameSrc)),
        "font-src 'self' data:",
        'script-src ' . implode(' ', array_unique($scriptSrc)),
        'style-src ' . implode(' ', array_unique($styleSrc)),
        'img-src ' . implode(' ', array_unique($imgSrc)),
        'connect-src ' . implode(' ', array_unique($connectSrc)),
    ];

    if (is_https_request()) {
        $csp[] = 'upgrade-insecure-requests';
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    header('Content-Security-Policy: ' . implode('; ', $csp));
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Permissions-Policy: camera=(), geolocation=(), microphone=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');

    if (!empty($_SESSION['member_id'])) {
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
    }
}

function ensure_storage_htaccess(string $directory, string $rules): void
{
    $file = rtrim($directory, '/') . '/.htaccess';
    if (!is_file($file)) {
        file_put_contents($file, $rules);
    }
}

function detect_uploaded_mime_type(string $tmpPath): string
{
    if (!is_file($tmpPath)) {
        return '';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return '';
    }
    $mime = (string) finfo_file($finfo, $tmpPath);
    finfo_close($finfo);

    return strtolower(trim($mime));
}

function assert_upload_file_is_valid_signature(string $tmpPath, array $allowedExtensions): void
{
    $signature = @file_get_contents($tmpPath, false, null, 0, 16);
    if ($signature === false) {
        throw new RuntimeException('Fichier téléversé illisible.');
    }

    $known = [
        'pdf' => '%PDF-',
        'jpg' => "\xFF\xD8\xFF",
        'jpeg' => "\xFF\xD8\xFF",
        'png' => "\x89PNG\r\n\x1A\n",
        'webp' => 'RIFF',
    ];

    foreach ($allowedExtensions as $extension) {
        $extension = strtolower((string) $extension);
        if (!isset($known[$extension])) {
            continue;
        }
        if (str_starts_with($signature, $known[$extension])) {
            if ($extension !== 'webp' || str_contains(substr($signature, 8), 'WEBP')) {
                return;
            }
        }
    }

    throw new RuntimeException('Signature de fichier invalide pour le type attendu.');
}

function secure_move_uploaded_file(
    array $upload,
    string $destinationDirectory,
    string $prefix,
    array $allowedExtensions,
    array $allowedMimes,
    int $maxBytes
): string {
    $errorCode = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Échec du téléversement.');
    }

    $tmpPath = (string) ($upload['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Fichier téléversé invalide.');
    }

    $size = (int) ($upload['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        throw new RuntimeException('Fichier trop volumineux ou vide.');
    }

    $originalName = (string) ($upload['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Extension de fichier non autorisée.');
    }

    $mime = detect_uploaded_mime_type($tmpPath);
    if (!in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException('Type MIME de fichier non autorisé.');
    }
    assert_upload_file_is_valid_signature($tmpPath, $allowedExtensions);

    $sanitizedTmpPath = $tmpPath;
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $sanitizedTmpPath = sanitize_uploaded_image_file($tmpPath, $extension);
    }

    if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0755, true) && !is_dir($destinationDirectory)) {
        throw new RuntimeException('Impossible de créer le dossier de destination.');
    }

    $filename = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destinationPath = rtrim($destinationDirectory, '/') . '/' . $filename;
    $moved = $sanitizedTmpPath === $tmpPath
        ? move_uploaded_file($tmpPath, $destinationPath)
        : rename($sanitizedTmpPath, $destinationPath);
    if (!$moved) {
        throw new RuntimeException('Impossible de déplacer le fichier téléversé.');
    }

    @chmod($destinationPath, 0644);
    return $filename;
}

function sanitize_uploaded_image_file(string $tmpPath, string $extension): string
{
    $raw = @file_get_contents($tmpPath);
    if ($raw === false) {
        throw new RuntimeException('Image téléversée illisible.');
    }

    if (!function_exists('imagecreatefromstring')) {
        return $tmpPath;
    }

    $image = @imagecreatefromstring($raw);
    if ($image === false) {
        throw new RuntimeException('Image téléversée invalide.');
    }

    $outputPath = tempnam(sys_get_temp_dir(), 'on4crd-img-');
    if ($outputPath === false) {
        imagedestroy($image);
        throw new RuntimeException('Impossible de créer un fichier temporaire.');
    }

    $writeOk = match ($extension) {
        'jpg', 'jpeg' => imagejpeg($image, $outputPath, 90),
        'png' => imagepng($image, $outputPath, 6),
        'webp' => function_exists('imagewebp') ? imagewebp($image, $outputPath, 85) : false,
        default => false,
    };
    imagedestroy($image);

    if (!$writeOk) {
        @unlink($outputPath);
        throw new RuntimeException('Échec du nettoyage des métadonnées image.');
    }

    return $outputPath;
}

function handle_album_upload(?array $upload, string $callsign): string
{
    if (!is_array($upload)) {
        throw new RuntimeException('Image manquante.');
    }
    $baseDir = dirname(__DIR__) . '/storage/uploads/albums';
    $saved = secure_move_uploaded_file(
        $upload,
        $baseDir,
        slugify($callsign !== '' ? $callsign : 'member'),
        ['jpg', 'jpeg', 'png', 'webp'],
        ['image/jpeg', 'image/png', 'image/webp'],
        8 * 1024 * 1024
    );

    $publicPath = 'storage/uploads/albums/' . $saved;
    create_album_thumbnail($publicPath, 640, 640);

    return $publicPath;
}

function create_album_thumbnail(string $publicPath, int $maxWidth = 640, int $maxHeight = 640): ?string
{
    if (!extension_loaded('gd')) {
        return null;
    }
    $sourcePath = dirname(__DIR__) . '/' . ltrim($publicPath, '/');
    if (!is_file($sourcePath)) {
        return null;
    }
    $info = @getimagesize($sourcePath);
    if (!is_array($info)) {
        return null;
    }
    [$width, $height] = $info;
    if ($width <= 0 || $height <= 0) {
        return null;
    }
    $mime = (string) ($info['mime'] ?? '');
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($sourcePath),
        'image/png' => @imagecreatefrompng($sourcePath),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
        default => false,
    };
    if (!$src) {
        return null;
    }
    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
    $newW = max(1, (int) floor($width * $ratio));
    $newH = max(1, (int) floor($height * $ratio));
    $dst = imagecreatetruecolor($newW, $newH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);

    $dir = dirname($sourcePath) . '/thumbs';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        imagedestroy($src);
        imagedestroy($dst);
        return null;
    }
    $name = pathinfo($sourcePath, PATHINFO_FILENAME) . '.jpg';
    $thumbAbs = $dir . '/' . $name;
    $ok = imagejpeg($dst, $thumbAbs, 84);
    imagedestroy($src);
    imagedestroy($dst);
    if (!$ok) {
        return null;
    }
    return 'storage/uploads/albums/thumbs/' . $name;
}

function album_thumbnail_public_path(string $photoPath): string
{
    $base = pathinfo($photoPath, PATHINFO_FILENAME);
    return 'storage/uploads/albums/thumbs/' . $base . '.jpg';
}

function handle_ad_image_upload(?array $upload, string $callsign, string $existingPath = ''): ?string
{
    if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingPath !== '' ? $existingPath : null;
    }

    $baseDir = dirname(__DIR__) . '/storage/uploads/ads';
    $saved = secure_move_uploaded_file(
        $upload,
        $baseDir,
        slugify($callsign !== '' ? $callsign : 'ad'),
        ['jpg', 'jpeg', 'png', 'webp'],
        ['image/jpeg', 'image/png', 'image/webp'],
        6 * 1024 * 1024
    );

    return 'storage/uploads/ads/' . $saved;
}

function generate_member_avatar_from_photo(string $photoPublicPath, int $memberId): ?string
{
    $sourcePath = dirname(__DIR__) . '/' . ltrim($photoPublicPath, '/');
    if (!is_file($sourcePath) || !extension_loaded('gd')) {
        return null;
    }

    $info = @getimagesize($sourcePath);
    $mime = (string) ($info['mime'] ?? '');
    $sourceImage = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($sourcePath),
        'image/png' => @imagecreatefrompng($sourcePath),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
        default => false,
    };
    if (!$sourceImage) {
        return null;
    }

    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);
    $side = min($sourceWidth, $sourceHeight);
    $srcX = (int) floor(($sourceWidth - $side) / 2);
    $srcY = (int) floor(($sourceHeight - $side) / 2);

    $avatar = imagecreatetruecolor(256, 256);
    imagealphablending($avatar, false);
    imagesavealpha($avatar, true);
    $transparent = imagecolorallocatealpha($avatar, 0, 0, 0, 127);
    imagefill($avatar, 0, 0, $transparent);
    imagecopyresampled($avatar, $sourceImage, 0, 0, $srcX, $srcY, 256, 256, $side, $side);
    imagedestroy($sourceImage);

    $targetDir = dirname(__DIR__) . '/storage/uploads/members/avatars';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        imagedestroy($avatar);
        return null;
    }
    $fileName = 'avatar_' . $memberId . '_' . date('YmdHis') . '.png';
    $targetPath = $targetDir . '/' . $fileName;
    $saved = imagepng($avatar, $targetPath, 8);
    imagedestroy($avatar);
    if (!$saved) {
        return null;
    }

    return 'storage/uploads/members/avatars/' . $fileName;
}


function member_default_avatar_data_uri(string $label = ''): string
{
    $trimmed = trim($label);
    $initial = strtoupper(function_exists('mb_substr') ? (string) mb_substr($trimmed, 0, 1, 'UTF-8') : substr($trimmed, 0, 1));
    if ($initial === '' || !preg_match('/[A-Z0-9]/', $initial)) {
        $initial = 'R';
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 256 256" role="img" aria-label="Avatar">'
        . '<defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#1d4ed8"/><stop offset="100%" stop-color="#0f172a"/></linearGradient></defs>'
        . '<rect width="256" height="256" rx="128" fill="url(#bg)"/>'
        . '<text x="50%" y="56%" text-anchor="middle" dominant-baseline="middle" font-family="Arial, Helvetica, sans-serif" font-size="112" font-weight="700" fill="#f8fafc">'
        . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8')
        . '</text></svg>';

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

function member_avatar_src(array $member): string
{
    $avatarPath = trim((string) ($member['avatar_path'] ?? ''));
    if ($avatarPath !== '') {
        return asset_url($avatarPath);
    }

    $photoPath = trim((string) ($member['photo_path'] ?? ''));
    if ($photoPath !== '') {
        return asset_url($photoPath);
    }

    $label = (string) ($member['callsign'] ?? ($member['full_name'] ?? ''));

    return member_default_avatar_data_uri($label);
}
function client_ip_address(): string
{
    return trim((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')) ?: '0.0.0.0';
}

function cache_dir_path(): string
{
    $dir = __DIR__ . '/../storage/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return $dir;
}

function login_throttle_file(): string
{
    return cache_dir_path() . '/login-' . hash('sha256', client_ip_address()) . '.json';
}

function login_throttle_state(): array
{
    $file = login_throttle_file();
    if (!is_file($file)) {
        return ['attempts' => 0, 'first_attempt_at' => 0, 'locked_until' => 0];
    }

    $decoded = json_decode((string) file_get_contents($file), true);
    return is_array($decoded)
        ? array_merge(['attempts' => 0, 'first_attempt_at' => 0, 'locked_until' => 0], $decoded)
        : ['attempts' => 0, 'first_attempt_at' => 0, 'locked_until' => 0];
}

function write_login_throttle_state(array $state): void
{
    file_put_contents(login_throttle_file(), json_encode($state, JSON_THROW_ON_ERROR));
}

function enforce_login_throttle(): void
{
    $state = login_throttle_state();
    if ((int) ($state['locked_until'] ?? 0) > time()) {
        throw new RuntimeException('Trop de tentatives de connexion. Réessayez plus tard.');
    }
}

function record_login_failure(): void
{
    $state = login_throttle_state();
    $now = time();
    $window = 900;

    if (($now - (int) ($state['first_attempt_at'] ?? 0)) > $window) {
        $state = ['attempts' => 0, 'first_attempt_at' => $now, 'locked_until' => 0];
    }

    $state['first_attempt_at'] = (int) ($state['first_attempt_at'] ?: $now);
    $state['attempts'] = (int) ($state['attempts'] ?? 0) + 1;
    if ($state['attempts'] >= 5) {
        $state['locked_until'] = $now + 900;
    }

    write_login_throttle_state($state);
}

function clear_login_failures(): void
{
    $file = login_throttle_file();
    if (is_file($file)) {
        unlink($file);
    }
}

function normalize_http_url(string $url, bool $allowRelative = false): ?string
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return null;
    }

    if (preg_match('/[\r\n]/', $trimmed) === 1) {
        throw new RuntimeException('URL invalide.');
    }

    if ($allowRelative && str_starts_with($trimmed, '//')) {
        throw new RuntimeException('URL relative invalide.');
    }

    if ($allowRelative && preg_match('~^(?:/|\./|\../|\?|#)~', $trimmed) === 1) {
        return $trimmed;
    }

    if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
        throw new RuntimeException('URL invalide.');
    }

    $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new RuntimeException('Seules les URL HTTP et HTTPS sont autorisées.');
    }

    return $trimmed;
}

function is_private_or_reserved_ip(string $ip): bool
{
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
        return false;
    }

    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

function host_resolves_to_private_network(string $host): bool
{
    $normalizedHost = strtolower(rtrim(trim($host), '.'));
    if ($normalizedHost === '') {
        return true;
    }

    if (in_array($normalizedHost, ['localhost'], true) || str_ends_with($normalizedHost, '.local') || str_ends_with($normalizedHost, '.internal')) {
        return true;
    }

    if (filter_var($normalizedHost, FILTER_VALIDATE_IP) !== false) {
        return is_private_or_reserved_ip($normalizedHost);
    }

    if (function_exists('gethostbynamel')) {
        $ips = @gethostbynamel($normalizedHost);
        if (is_array($ips) && $ips !== []) {
            foreach ($ips as $ip) {
                if (is_private_or_reserved_ip($ip)) {
                    return true;
                }
            }
        }
    }

    return false;
}

function validate_outbound_url(string $url): ?string
{
    return normalize_http_url($url);
}

function validate_public_profile_url(string $url): ?string
{
    return normalize_http_url($url);
}

function validate_remote_feed_url(string $url): ?string
{
    $normalized = normalize_http_url($url);
    if ($normalized === null) {
        return null;
    }

    $host = strtolower((string) parse_url($normalized, PHP_URL_HOST));
    if ($host === '' || host_resolves_to_private_network($host)) {
        throw new RuntimeException("L'URL distante pointe vers un réseau privé ou réservé.");
    }

    $dnsRecords = @dns_get_record($host, DNS_A + DNS_AAAA);
    if (is_array($dnsRecords)) {
        foreach ($dnsRecords as $record) {
            $ip = (string) ($record['ip'] ?? $record['ipv6'] ?? '');
            if ($ip !== '' && is_private_or_reserved_ip($ip)) {
                throw new RuntimeException("L'URL distante résout vers une IP privée/réservée.");
            }
        }
    }

    return $normalized;
}

function shop_status_label(string $status): string
{
    return match (trim($status)) {
        'draft' => 'Brouillon',
        'published' => 'Publié',
        'archived' => 'Archivé',
        default => 'Inconnu',
    };
}

function shop_order_status_label(string $status): string
{
    return match (trim($status)) {
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'ready' => 'Prête',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
        default => 'Inconnu',
    };
}

function shop_categories(): array
{
    if (!table_exists('shop_categories')) {
        return [];
    }

    $stmt = db()->query('SELECT id, slug, name, description, sort_order, is_active FROM shop_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC, id ASC');
    return $stmt->fetchAll();
}

function shop_public_products(?string $category = null): array
{
    if (!table_exists('shop_products')) {
        return [];
    }

    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM shop_products p
            LEFT JOIN shop_categories c ON c.id = p.category_id
            WHERE p.status = "published"';
    $params = [];

    $normalizedCategory = trim((string) $category);
    if ($normalizedCategory !== '') {
        $sql .= ' AND c.slug = ? AND c.is_active = 1';
        $params[] = $normalizedCategory;
    }

    $sql .= ' ORDER BY p.is_featured DESC, p.updated_at DESC, p.id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function shop_product_by_slug(string $slug): ?array
{
    if (!table_exists('shop_products')) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM shop_products p
         LEFT JOIN shop_categories c ON c.id = p.category_id
         WHERE p.slug = ?
         LIMIT 1'
    );
    $stmt->execute([trim($slug)]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function shop_cart_raw(): array
{
    $cart = $_SESSION['shop_cart'] ?? [];
    if (!is_array($cart)) {
        return [];
    }

    $normalized = [];
    foreach ($cart as $productId => $quantity) {
        $id = (int) $productId;
        $qty = (int) $quantity;
        if ($id > 0 && $qty > 0) {
            $normalized[$id] = $qty;
        }
    }

    return $normalized;
}

function shop_cart_save(array $cart): void
{
    if ($cart === []) {
        unset($_SESSION['shop_cart']);
        return;
    }

    $_SESSION['shop_cart'] = $cart;
}

function shop_cart_state(): array
{
    $raw = shop_cart_raw();
    if ($raw === [] || !table_exists('shop_products')) {
        if ($raw === []) {
            return ['items' => [], 'total_cents' => 0];
        }
        shop_cart_clear();

        return ['items' => [], 'total_cents' => 0];
    }

    $ids = array_keys($raw);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare(
        'SELECT id, slug, title, summary, price_cents, stock_qty, status
         FROM shop_products
         WHERE id IN (' . $placeholders . ')'
    );
    $stmt->execute($ids);

    $productsById = [];
    foreach ($stmt->fetchAll() as $row) {
        $productsById[(int) $row['id']] = $row;
    }

    $items = [];
    $total = 0;
    $updatedCart = [];

    foreach ($raw as $productId => $quantity) {
        $product = $productsById[$productId] ?? null;
        if (!is_array($product) || (string) ($product['status'] ?? '') !== 'published') {
            continue;
        }

        $maxQty = $product['stock_qty'] !== null ? (int) $product['stock_qty'] : null;
        $finalQty = $maxQty !== null ? min($quantity, max(0, $maxQty)) : $quantity;
        if ($finalQty <= 0) {
            continue;
        }

        $lineTotal = $finalQty * (int) $product['price_cents'];
        $items[] = [
            'product' => $product,
            'quantity' => $finalQty,
            'line_total_cents' => $lineTotal,
        ];
        $total += $lineTotal;
        $updatedCart[$productId] = $finalQty;
    }

    if ($updatedCart !== $raw) {
        shop_cart_save($updatedCart);
    }

    return [
        'items' => $items,
        'total_cents' => $total,
    ];
}

function shop_cart_add(int $productId, int $quantity = 1): void
{
    if ($productId <= 0) {
        throw new RuntimeException('Produit invalide.');
    }

    $cart = shop_cart_raw();
    $cart[$productId] = max(1, (int) ($cart[$productId] ?? 0) + max(1, $quantity));
    shop_cart_save($cart);
    shop_cart_state();
}

function shop_cart_update(int $productId, int $quantity): void
{
    if ($productId <= 0) {
        throw new RuntimeException('Produit invalide.');
    }

    $cart = shop_cart_raw();
    if ($quantity <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $quantity;
    }

    shop_cart_save($cart);
    shop_cart_state();
}

function shop_cart_remove(int $productId): void
{
    if ($productId <= 0) {
        return;
    }

    $cart = shop_cart_raw();
    unset($cart[$productId]);
    shop_cart_save($cart);
}

function shop_cart_clear(): void
{
    unset($_SESSION['shop_cart']);
}

function place_shop_order(int $memberId, string $paymentMethod, string $notes = ''): string
{
    if (!table_exists('shop_orders') || !table_exists('shop_order_items')) {
        throw new RuntimeException("Le module boutique n'est pas initialisé.");
    }

    $cart = shop_cart_state();
    if (($cart['items'] ?? []) === []) {
        throw new RuntimeException('Le panier est vide.');
    }

    $allowedPayments = ['on_site', 'bank_transfer'];
    $payment = in_array($paymentMethod, $allowedPayments, true) ? $paymentMethod : 'on_site';
    $cleanNotes = trim($notes);
    if (function_exists('mb_substr')) {
        $cleanNotes = mb_substr($cleanNotes, 0, 1000);
    } else {
        $cleanNotes = substr($cleanNotes, 0, 1000);
    }

    $orderReference = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $pdo = db();

    $insertOrder = $pdo->prepare(
        'INSERT INTO shop_orders (reference_code, member_id, status, total_cents, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertItem = $pdo->prepare(
        'INSERT INTO shop_order_items (order_id, product_id, product_title, quantity, unit_price_cents, line_total_cents) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $fetchProduct = $pdo->prepare(
        'SELECT id, title, price_cents, stock_qty, status FROM shop_products WHERE id = ? AND status = "published" LIMIT 1 FOR UPDATE'
    );
    $updateStock = $pdo->prepare('UPDATE shop_products SET stock_qty = stock_qty - ? WHERE id = ? AND stock_qty >= ?');

    $pdo->beginTransaction();
    try {
        $insertOrder->execute([
            $orderReference,
            $memberId,
            'pending',
            (int) ($cart['total_cents'] ?? 0),
            $payment,
            $cleanNotes,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        foreach ((array) ($cart['items'] ?? []) as $item) {
            $product = $item['product'] ?? null;
            $qty = max(1, (int) ($item['quantity'] ?? 0));
            $productId = (int) ($product['id'] ?? 0);
            if ($productId <= 0) {
                throw new RuntimeException('Produit invalide dans le panier.');
            }

            $fetchProduct->execute([$productId]);
            $dbProduct = $fetchProduct->fetch();
            if (!$dbProduct) {
                throw new RuntimeException('Produit indisponible.');
            }

            if ($dbProduct['stock_qty'] !== null) {
                $updateStock->execute([$qty, (int) $dbProduct['id'], $qty]);
                if ($updateStock->rowCount() === 0) {
                    throw new RuntimeException('Stock insuffisant pour ' . (string) $dbProduct['title'] . '.');
                }
            }

            $insertItem->execute([
                $orderId,
                (int) $dbProduct['id'],
                (string) $dbProduct['title'],
                $qty,
                (int) $dbProduct['price_cents'],
                $qty * (int) $dbProduct['price_cents'],
            ]);
        }

        $pdo->commit();
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $throwable;
    }

    shop_cart_clear();
    return $orderReference;
}

function shop_recent_orders(?int $memberId = null, int $limit = 50): array
{
    if (!table_exists('shop_orders')) {
        return [];
    }

    $sql = 'SELECT o.*, m.callsign
            FROM shop_orders o
            LEFT JOIN members m ON m.id = o.member_id';
    $params = [];
    if ($memberId !== null && $memberId > 0) {
        $sql .= ' WHERE o.member_id = ?';
        $params[] = $memberId;
    }
    $sql .= ' ORDER BY o.created_at DESC, o.id DESC LIMIT ' . max(1, $limit);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function shop_order_items(int $orderId): array
{
    if (!table_exists('shop_order_items')) {
        return [];
    }
    $stmt = db()->prepare('SELECT * FROM shop_order_items WHERE order_id = ? ORDER BY id ASC');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function auction_public_lots(int $limit = 24): array
{
    if (!table_exists('auction_lots')) {
        return [];
    }

    auction_sync_expired_lots();
    $stmt = db()->prepare(
        'SELECT l.*, m.callsign AS winner_callsign
         FROM auction_lots l
         LEFT JOIN members m ON m.id = l.winner_member_id
         WHERE l.status IN ("scheduled","active","closed")
         ORDER BY
            CASE l.status WHEN "active" THEN 1 WHEN "scheduled" THEN 2 ELSE 3 END,
            l.ends_at ASC,
            l.id DESC
         LIMIT ' . max(1, $limit)
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function auction_lot_by_slug(string $slug): ?array
{
    if (!table_exists('auction_lots')) {
        return null;
    }
    auction_sync_expired_lots();
    $stmt = db()->prepare('SELECT l.*, m.callsign AS winner_callsign FROM auction_lots l LEFT JOIN members m ON m.id = l.winner_member_id WHERE l.slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function auction_lot_by_id(int $lotId): ?array
{
    if (!table_exists('auction_lots')) {
        return null;
    }
    auction_sync_expired_lots();
    $stmt = db()->prepare('SELECT l.*, m.callsign AS winner_callsign FROM auction_lots l LEFT JOIN members m ON m.id = l.winner_member_id WHERE l.id = ? LIMIT 1');
    $stmt->execute([$lotId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function auction_bids_for_lot(int $lotId, int $limit = 20): array
{
    if (!table_exists('auction_bids')) {
        return [];
    }
    $stmt = db()->prepare(
        'SELECT b.*, m.callsign, m.full_name
         FROM auction_bids b
         INNER JOIN members m ON m.id = b.member_id
         WHERE b.lot_id = ?
         ORDER BY b.amount_cents DESC, b.created_at ASC
         LIMIT ' . max(1, $limit)
    );
    $stmt->execute([$lotId]);
    return $stmt->fetchAll();
}

function auction_highest_bid(int $lotId): ?array
{
    if (!table_exists('auction_bids')) {
        return null;
    }
    $stmt = db()->prepare(
        'SELECT b.*, m.callsign
         FROM auction_bids b
         INNER JOIN members m ON m.id = b.member_id
         WHERE b.lot_id = ?
         ORDER BY b.amount_cents DESC, b.created_at ASC
         LIMIT 1'
    );
    $stmt->execute([$lotId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function auction_runtime_status(array $lot): string
{
    $status = (string) ($lot['status'] ?? 'draft');
    if (in_array($status, ['cancelled', 'draft'], true)) {
        return $status;
    }

    $now = new DateTimeImmutable('now');
    $startsAt = new DateTimeImmutable((string) $lot['starts_at']);
    $endsAt = new DateTimeImmutable((string) $lot['ends_at']);
    if ($status !== 'closed' && $now >= $endsAt) {
        return 'closed';
    }
    if ($now < $startsAt) {
        return 'scheduled';
    }

    return $status === 'closed' ? 'closed' : 'active';
}

function auction_status_label(string $status): string
{
    return match ($status) {
        'draft' => 'Brouillon',
        'scheduled' => 'Planifiée',
        'active' => 'En cours',
        'closed' => 'Terminée',
        'cancelled' => 'Annulée',
        default => ucfirst($status),
    };
}

function auction_minimum_bid_cents(array $lot): int
{
    $current = max((int) ($lot['current_price_cents'] ?? 0), (int) ($lot['starting_price_cents'] ?? 0));
    $hasBids = ((int) ($lot['current_price_cents'] ?? 0)) > 0;
    if (!$hasBids) {
        return max(0, (int) ($lot['starting_price_cents'] ?? 0));
    }

    return $current + max(1, (int) ($lot['min_increment_cents'] ?? 100));
}


function auction_reserve_met(array $lot, int $highestBidCents): bool
{
    $reserve = (int) ($lot['reserve_price_cents'] ?? 0);
    if ($reserve <= 0) {
        return true;
    }

    return $highestBidCents >= $reserve;
}

function auction_sync_expired_lots(): void
{
    if (!table_exists('auction_lots')) {
        return;
    }

    $rows = db()->query('SELECT id, reserve_price_cents FROM auction_lots WHERE status IN ("scheduled","active") AND ends_at <= NOW()')->fetchAll();
    if ($rows === []) {
        return;
    }

    $update = db()->prepare('UPDATE auction_lots SET status = "closed", winner_member_id = ?, current_price_cents = ? WHERE id = ?');
    foreach ($rows as $row) {
        $lotId = (int) $row['id'];
        $highestBid = auction_highest_bid($lotId);
        $currentPrice = $highestBid ? (int) $highestBid['amount_cents'] : 0;
        $reserveMet = auction_reserve_met($row, $currentPrice);
        $winnerId = ($highestBid && $reserveMet) ? (int) $highestBid['member_id'] : null;
        $update->execute([$winnerId, $currentPrice, $lotId]);
    }
}

function place_auction_bid(int $lotId, int $memberId, int $amountCents): void
{
    $pdo = db();
    $insertBid = $pdo->prepare('INSERT INTO auction_bids (lot_id, member_id, amount_cents) VALUES (?, ?, ?)');
    $lockLot = $pdo->prepare('SELECT * FROM auction_lots WHERE id = ? LIMIT 1 FOR UPDATE');
    $updateLot = $pdo->prepare(
        'UPDATE auction_lots SET current_price_cents = ?, status = "active", winner_member_id = NULL, extended_until = ?, ends_at = ? WHERE id = ? AND current_price_cents <= ?'
    );

    $pdo->beginTransaction();
    try {
        $lockLot->execute([$lotId]);
        $lot = $lockLot->fetch();
        if (!$lot) {
            throw new RuntimeException('Lot introuvable.');
        }

        $status = auction_runtime_status($lot);
        if ($status !== 'active') {
            throw new RuntimeException('Cette enchère n’est pas active.');
        }

        $minimum = auction_minimum_bid_cents($lot);
        if ($amountCents < $minimum) {
            throw new RuntimeException('Le montant minimum pour enchérir est ' . format_price_eur($minimum) . '.');
        }

        $now = new DateTimeImmutable('now');
        $endsAt = new DateTimeImmutable((string) $lot['ends_at']);
        $extension = null;
        if ($endsAt->getTimestamp() - $now->getTimestamp() <= 300) {
            $extension = $endsAt->modify('+5 minutes')->format('Y-m-d H:i:s');
        }

        $insertBid->execute([$lotId, $memberId, $amountCents]);
        $newEnd = $extension ?? (string) $lot['ends_at'];
        $updateLot->execute([$amountCents, $extension, $newEnd, $lotId, (int) $lot['current_price_cents']]);
        if ($updateLot->rowCount() === 0) {
            throw new RuntimeException('Conflit de concurrence sur l’enchère. Veuillez réessayer.');
        }

        if (!empty($lot['buy_now_price_cents']) && $amountCents >= (int) $lot['buy_now_price_cents']) {
            $pdo->prepare('UPDATE auction_lots SET status = "closed", winner_member_id = ?, current_price_cents = ? WHERE id = ?')
                ->execute([$memberId, $amountCents, $lotId]);
        }

        $pdo->commit();
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $throwable;
    }
}


/**
 * @return array{layout:string,title:string,lead:string,search_label:string,search_placeholder:string,search_cta:string,search_reset:string,empty:string}
 */
function admin_dashboard_translations(string $locale): array
{
    $i18n = [
        'fr' => ['layout' => 'Administration', 'title' => 'Administration centralisée', 'lead' => 'Tous les modules et outils d’administration sont regroupés dans ce tableau de bord unique.', 'search_label' => 'Recherche rapide', 'search_placeholder' => 'Module, outil, description…', 'search_cta' => 'Filtrer', 'search_reset' => 'Réinitialiser', 'empty' => 'Aucun module ne correspond à la recherche.'],
        'en' => ['layout' => 'Administration', 'title' => 'Centralized administration', 'lead' => 'All admin modules and tools are grouped in this single dashboard.', 'search_label' => 'Quick search', 'search_placeholder' => 'Module, tool, description…', 'search_cta' => 'Filter', 'search_reset' => 'Reset', 'empty' => 'No module matches your search.'],
        'de' => ['layout' => 'Verwaltung', 'title' => 'Zentralisierte Verwaltung', 'lead' => 'Alle Verwaltungs-Module und Werkzeuge sind in diesem einzigen Dashboard gebündelt.', 'search_label' => 'Schnellsuche', 'search_placeholder' => 'Modul, Werkzeug, Beschreibung…', 'search_cta' => 'Filtern', 'search_reset' => 'Zurücksetzen', 'empty' => 'Kein Modul entspricht Ihrer Suche.'],
        'nl' => ['layout' => 'Beheer', 'title' => 'Gecentraliseerd beheer', 'lead' => 'Alle beheermodules en tools zijn gegroepeerd in dit ene dashboard.', 'search_label' => 'Snel zoeken', 'search_placeholder' => 'Module, tool, beschrijving…', 'search_cta' => 'Filteren', 'search_reset' => 'Reset', 'empty' => 'Geen module komt overeen met je zoekopdracht.'],
    ];

    return $i18n[$locale] ?? $i18n['fr'];
}

/**
 * @return array<int, array{route:string,title:array{fr:string,en:string,de:string,nl:string},desc:array{fr:string,en:string,de:string,nl:string},module?:string,permission?:string}>
 */
function admin_module_cards_catalog(): array
{
    return [
        ['route' => 'admin_modules', 'title' => ['fr' => 'Modules', 'en' => 'Modules', 'de' => 'Module', 'nl' => 'Modules'], 'desc' => ['fr' => 'Activation, désactivation et pilotage global des modules.', 'en' => 'Enable, disable and globally manage modules.', 'de' => 'Module aktivieren, deaktivieren und zentral steuern.', 'nl' => 'Modules activeren, deactiveren en centraal beheren.'], 'permission' => 'modules.manage'],
        ['route' => 'admin_permissions', 'title' => ['fr' => 'Permissions', 'en' => 'Permissions', 'de' => 'Berechtigungen', 'nl' => 'Rechten'], 'desc' => ['fr' => 'Rôles, droits et affectations.', 'en' => 'Roles, rights and assignments.', 'de' => 'Rollen, Rechte und Zuweisungen.', 'nl' => 'Rollen, rechten en toewijzingen.']],
        ['route' => 'admin_news', 'title' => ['fr' => 'Actualités', 'en' => 'News', 'de' => 'Neuigkeiten', 'nl' => 'Nieuws'], 'desc' => ['fr' => 'Sections, rédaction et modération.', 'en' => 'Sections, writing and moderation.', 'de' => 'Bereiche, Redaktion und Moderation.', 'nl' => 'Secties, redactie en moderatie.'], 'module' => 'news'],
        ['route' => 'admin_articles', 'title' => ['fr' => 'Articles', 'en' => 'Articles', 'de' => 'Artikel', 'nl' => 'Artikels'], 'desc' => ['fr' => 'Articles techniques publics.', 'en' => 'Public technical articles.', 'de' => 'Öffentliche technische Artikel.', 'nl' => 'Publieke technische artikels.'], 'module' => 'articles'],
        ['route' => 'admin_committee', 'title' => ['fr' => 'Comité', 'en' => 'Committee', 'de' => 'Komitee', 'nl' => 'Comité'], 'desc' => ['fr' => 'Membres du comité, rôle, ordre et biographie.', 'en' => 'Committee members, role, order and biography.', 'de' => 'Komiteemitglieder, Rolle, Reihenfolge und Biografie.', 'nl' => 'Comitéleden, rol, volgorde en biografie.'], 'module' => 'committee'],
        ['route' => 'admin_press', 'title' => ['fr' => 'Presse', 'en' => 'Press', 'de' => 'Presse', 'nl' => 'Pers'], 'desc' => ['fr' => 'Contacts presse, communiqués datés et documents téléchargeables.', 'en' => 'Press contacts, dated releases and downloadable documents.', 'de' => 'Pressekontakte, datierte Mitteilungen und Downloads.', 'nl' => 'Perscontacten, gedateerde berichten en downloads.'], 'module' => 'press'],
        ['route' => 'admin_events', 'title' => ['fr' => 'Agenda', 'en' => 'Agenda', 'de' => 'Agenda', 'nl' => 'Agenda'], 'desc' => ['fr' => 'Événements du club et contests locaux affichés dans les widgets live.', 'en' => 'Club events and local contests shown in live widgets.', 'de' => 'Clubveranstaltungen und lokale Contests in Live-Widgets.', 'nl' => 'Clubevenementen en lokale contests in live widgets.'], 'module' => 'events'],
        ['route' => 'admin_dinner_reservations', 'title' => ['fr' => 'Dîner annuel', 'en' => 'Annual dinner', 'de' => 'Jahresessen', 'nl' => 'Jaarlijks diner'], 'desc' => ['fr' => 'Réservations, lignes repas/dessert, quantités et total automatique.', 'en' => 'Reservations, meal/dessert lines, quantities and auto total.', 'de' => 'Reservierungen, Menüzeilen, Mengen und automatische Summe.', 'nl' => 'Reservaties, maaltijdregels, aantallen en automatisch totaal.'], 'module' => 'events', 'permission' => 'events.manage'],
        ['route' => 'admin_shop', 'title' => ['fr' => 'Boutique', 'en' => 'Shop', 'de' => 'Shop', 'nl' => 'Winkel'], 'desc' => ['fr' => 'Catalogue produits, catégories et commandes club.', 'en' => 'Product catalog, categories and club orders.', 'de' => 'Produktkatalog, Kategorien und Clubbestellungen.', 'nl' => 'Productcatalogus, categorieën en clubbestellingen.'], 'module' => 'shop', 'permission' => 'shop.manage'],
        ['route' => 'admin_auctions', 'title' => ['fr' => 'Enchères', 'en' => 'Auctions', 'de' => 'Auktionen', 'nl' => 'Veilingen'], 'desc' => ['fr' => 'Lots, planification, offres et clôture.', 'en' => 'Lots, scheduling, bids and closing.', 'de' => 'Lose, Planung, Gebote und Abschluss.', 'nl' => 'Kavels, planning, biedingen en afsluiting.'], 'module' => 'auctions', 'permission' => 'auctions.manage'],
        ['route' => 'admin_editorial', 'title' => ['fr' => 'Éditorial multilingue', 'en' => 'Multilingual editorial', 'de' => 'Mehrsprachige Redaktion', 'nl' => 'Meertalige redactie'], 'desc' => ['fr' => 'Français source, traduction auto EN/DE/NL et relecture manuelle.', 'en' => 'French source, EN/DE/NL auto translation and manual review.', 'de' => 'Französische Quelle, automatische Übersetzung und Review.', 'nl' => 'Franse bron, automatische vertaling en manuele review.']],
        ['route' => 'admin_translation_reviews', 'title' => ['fr' => 'Relecture linguistique', 'en' => 'Translation reviews', 'de' => 'Sprachliche Prüfung', 'nl' => 'Taalreview'], 'desc' => ['fr' => 'Workflow de validation des traductions des actualités et articles.', 'en' => 'Validation workflow for news/article translations.', 'de' => 'Freigabe-Workflow für News-/Artikelübersetzungen.', 'nl' => 'Validatieworkflow voor vertalingen van nieuws/artikels.']],
        ['route' => 'admin_live_feeds', 'title' => ['fr' => 'Flux live', 'en' => 'Live feeds', 'de' => 'Live-Feeds', 'nl' => 'Live feeds'], 'desc' => ['fr' => 'Pilotage fin des flux radioamateur, TTL, URLs et activation.', 'en' => 'Fine control of radio feeds, TTL, URLs and activation.', 'de' => 'Feinsteuerung von Funk-Feeds, TTL, URLs und Aktivierung.', 'nl' => 'Fijn beheer van radiofeeds, TTL, URL’s en activatie.']],
        ['route' => 'admin_newsletters', 'title' => ['fr' => 'Newsletter', 'en' => 'Newsletter', 'de' => 'Newsletter', 'nl' => 'Nieuwsbrief'], 'desc' => ['fr' => 'Abonnés, import CSV et campagnes email.', 'en' => 'Subscribers, CSV import and email campaigns.', 'de' => 'Abonnenten, CSV-Import und E-Mail-Kampagnen.', 'nl' => 'Abonnees, CSV-import en e-mailcampagnes.']],
        ['route' => 'admin_wiki', 'title' => ['fr' => 'Wiki', 'en' => 'Wiki', 'de' => 'Wiki', 'nl' => 'Wiki'], 'desc' => ['fr' => 'Pages collaboratives et révisions.', 'en' => 'Collaborative pages and revisions.', 'de' => 'Kollaborative Seiten und Revisionen.', 'nl' => 'Samenwerkingspagina’s en revisies.'], 'module' => 'wiki'],
        ['route' => 'admin_albums', 'title' => ['fr' => 'Albums', 'en' => 'Albums', 'de' => 'Alben', 'nl' => 'Albums'], 'desc' => ['fr' => 'Galerie publique et synchro sociale.', 'en' => 'Public gallery and social sync.', 'de' => 'Öffentliche Galerie und Social-Sync.', 'nl' => 'Publieke galerij en sociale sync.'], 'module' => 'albums'],
        ['route' => 'admin_ads', 'title' => ['fr' => 'Publicités', 'en' => 'Ads', 'de' => 'Werbung', 'nl' => 'Advertenties'], 'desc' => ['fr' => 'Régie publicitaire, placements et statistiques.', 'en' => 'Ad inventory, placements and statistics.', 'de' => 'Werbeverwaltung, Platzierungen und Statistiken.', 'nl' => 'Advertentiebeheer, plaatsingen en statistieken.'], 'module' => 'advertising'],
    ];
}


/**
 * @return array<int, array{route:string,title:string,desc:string}>
 */
function admin_dashboard_cards(string $locale, int $userId, string $search = ''): array
{
    $needle = trim($search);
    $needle = $needle !== '' ? mb_safe_strtolower($needle) : '';

    return admin_cards_for_dashboard($locale, $userId, $needle);
}

/**
 * @return array<int, array{route:string,title:string,desc:string}>
 */
function admin_cards_for_dashboard(string $locale, int $userId, string $searchNeedle = ''): array
{
    return cache_remember('admin_cards_' . $locale . '_' . $userId . '_' . md5($searchNeedle), 30, static function () use ($locale, $searchNeedle): array {
        $cards = [];
        foreach (admin_module_cards_catalog() as $card) {
            $module = (string) ($card['module'] ?? '');
            $permission = (string) ($card['permission'] ?? '');
            if ($module !== '' && !module_enabled($module)) {
                continue;
            }
            if ($permission !== '' && !has_permission($permission)) {
                continue;
            }
            $title = (string) ($card['title'][$locale] ?? $card['title']['fr']);
            $desc = (string) ($card['desc'][$locale] ?? $card['desc']['fr']);
            if ($searchNeedle !== '') {
                $haystack = mb_safe_strtolower($title . ' ' . $desc);
                if (!str_contains($haystack, $searchNeedle)) {
                    continue;
                }
            }
            $cards[] = ['route' => (string) $card['route'], 'title' => $title, 'desc' => $desc];
        }
        return $cards;
    });
}
