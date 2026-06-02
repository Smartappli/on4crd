<?php
declare(strict_types=1);

function config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;
    static $pathCache = [];
    if ($config === null) {
        $configFile = __DIR__ . '/../config/config.php';
        if (!is_file($configFile)) {
            $sampleConfigFile = __DIR__ . '/../config/config.sample.php';
            if (PHP_SAPI !== 'cli' || !is_file($sampleConfigFile)) {
                throw new RuntimeException('Missing config/config.php. Copy config.sample.php first.');
            }
            $configFile = $sampleConfigFile;
        }
        $config = require $configFile;
    }

    if ($key === null) {
        return $config;
    }

    if (array_key_exists($key, $pathCache)) {
        return $pathCache[$key];
    }

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    $pathCache[$key] = $value;
    return $pathCache[$key];
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
        $auth = new \Delight\Auth\Auth($pdo);
    } catch (Throwable $throwable) {
        $auth = null;
        return null;
    }

    return $auth;
}

function table_exists(string $table): bool
{
    static $cache = null;
    static $fallbackCache = [];
    $normalized = strtolower(trim($table));
    if ($normalized === '') {
        return false;
    }
    if (is_array($cache)) {
        return isset($cache[$normalized]);
    }

    try {
        $stmt = db()->query('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()');
        $loadedTables = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $tableName) {
            $loadedTables[strtolower((string) $tableName)] = true;
        }
        $cache = $loadedTables;

        return isset($cache[$normalized]);
    } catch (Throwable) {
        if (array_key_exists($normalized, $fallbackCache)) {
            return $fallbackCache[$normalized];
        }

        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$normalized]);
        $fallbackCache[$normalized] = (int) $stmt->fetchColumn() > 0;

        return $fallbackCache[$normalized];
    }
}

function table_has_column(string $table, string $column): bool
{
    static $cache = [];

    $table = strtolower(trim($table));
    $column = strtolower(trim($column));
    if ($table === '' || $column === '') {
        return false;
    }

    $cacheKey = $table . '.' . $column;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $stmt->execute([$table, $column]);
        $cache[$cacheKey] = (int) $stmt->fetchColumn() > 0;
    } catch (Throwable) {
        $cache[$cacheKey] = false;
    }

    return $cache[$cacheKey];
}

function table_has_index(string $table, string $index): bool
{
    static $cache = [];

    $table = strtolower(trim($table));
    $index = strtolower(trim($index));
    if ($table === '' || $index === '') {
        return false;
    }

    $cacheKey = $table . '.' . $index;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?'
        );
        $stmt->execute([$table, $index]);
        $cache[$cacheKey] = (int) $stmt->fetchColumn() > 0;
    } catch (Throwable) {
        $cache[$cacheKey] = false;
    }

    return $cache[$cacheKey];
}

if (!function_exists('supported_locales')) {
function supported_locales(): array
{
    static $locales = ['fr', 'en', 'de', 'nl', 'it', 'es', 'pt', 'bg', 'hr', 'cs', 'da', 'et', 'fi', 'el', 'hu', 'ga', 'lv', 'lt', 'mt', 'pl', 'ro', 'sk', 'sl', 'sv', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'];
    return $locales;
}
}

if (!function_exists('supported_locales_map')) {
function supported_locales_map(): array
{
    static $map = null;
    if (is_array($map)) {
        return $map;
    }

    $map = array_fill_keys(supported_locales(), true);
    return $map;
}
}

if (!function_exists('is_rtl_locale')) {
function is_rtl_locale(string $locale): bool
{
    return in_array(strtolower(trim($locale)), ['ar'], true);
}
}

if (!function_exists('locale_fallback_chain')) {
function locale_fallback_chain(?string $locale = null): array
{
    $supported = supported_locales_map();
    $requested = strtolower(trim((string) ($locale ?? current_locale())));
    $requested = str_replace('_', '-', $requested);
    $normalized = $requested;
    if (str_contains($requested, '-')) {
        $normalized = (string) explode('-', $requested, 2)[0];
    }

    $chain = [];
    foreach ([$requested, $normalized, 'en', 'fr'] as $candidate) {
        if ($candidate === '' || in_array($candidate, $chain, true) || !isset($supported[$candidate])) {
            continue;
        }
        $chain[] = $candidate;
    }

    return $chain === [] ? ['fr'] : $chain;
}
}

if (!function_exists('i18n_localized_value')) {
function i18n_localized_value(array $localized, ?string $locale = null, string $default = 'fr'): string
{
    foreach (locale_fallback_chain($locale) as $candidateLocale) {
        if (!isset($localized[$candidateLocale])) {
            continue;
        }

        if (is_array($localized[$candidateLocale])) {
            $nestedValue = $localized[$candidateLocale][$default] ?? null;
            if (is_string($nestedValue)) {
                $nestedValue = trim($nestedValue);
                if ($nestedValue !== '') {
                    return $nestedValue;
                }
            }
            continue;
        }

        if (!is_string($localized[$candidateLocale])) {
            continue;
        }
        $value = trim($localized[$candidateLocale]);
        if ($value !== '') {
            return $value;
        }
    }

    if (isset($localized[$default]) && is_string($localized[$default])) {
        $value = trim($localized[$default]);
        if ($value !== '') {
            return $value;
        }
    }

    foreach ($localized as $value) {
        if (!is_string($value)) {
            continue;
        }

        $value = trim($value);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}
}


if (!function_exists('i18n_expand_supported_locales')) {
/**
 * Ensure a page-level i18n dictionary exposes every configured locale.
 *
 * @param array<string, array<string, string>> $messages
 * @param array<int, string>|null $locales
 * @return array<string, array<string, string>>
 */
function i18n_expand_supported_locales(array $messages, ?array $locales = null, string $primaryFallback = 'en', string $secondaryFallback = 'fr'): array
{
    $locales ??= supported_locales();
    $primary = isset($messages[$primaryFallback]) && is_array($messages[$primaryFallback]) ? $messages[$primaryFallback] : [];
    $secondary = isset($messages[$secondaryFallback]) && is_array($messages[$secondaryFallback]) ? $messages[$secondaryFallback] : [];
    $base = array_replace($secondary, $primary);

    foreach ($locales as $locale) {
        if (!is_string($locale) || $locale === '') {
            continue;
        }
        $current = isset($messages[$locale]) && is_array($messages[$locale]) ? $messages[$locale] : [];
        $messages[$locale] = array_replace($base, $current);
    }

    return $messages;
}
}

if (!function_exists('i18n_domain_messages')) {
/**
 * Load a module i18n catalog from app/i18n/<domain>.php and/or app/i18n/<domain>/<locale>.php.
 *
 * @return array<string, array<string, string>>
 */
function i18n_domain_messages(string $domain): array
{
    static $catalogs = [];

    $domain = preg_replace('/[^a-z0-9_]/', '', strtolower($domain)) ?: '';
    if ($domain === '') {
        return [];
    }

    if (array_key_exists($domain, $catalogs)) {
        return $catalogs[$domain];
    }

    $messages = [];
    $path = __DIR__ . '/i18n/' . $domain . '.php';
    if (is_file($path)) {
        $loadedMessages = require $path;
        if (is_array($loadedMessages)) {
            $messages = $loadedMessages;
        }
    }

    $directory = __DIR__ . '/i18n/' . $domain;
    if (is_dir($directory)) {
        foreach (supported_locales() as $localeCode) {
            $localePath = $directory . '/' . $localeCode . '.php';
            if (!is_file($localePath)) {
                continue;
            }
            $localeMessages = require $localePath;
            if (is_array($localeMessages)) {
                $messages[$localeCode] = array_replace(
                    isset($messages[$localeCode]) && is_array($messages[$localeCode]) ? $messages[$localeCode] : [],
                    $localeMessages
                );
            }
        }
    }

    if ($messages === []) {
        $catalogs[$domain] = [];
        return [];
    }

    $catalogs[$domain] = i18n_expand_supported_locales($messages);
    return $catalogs[$domain];
}
}

if (!function_exists('i18n_domain_locale')) {
/**
 * Return the best localized key/value map for a module catalog.
 *
 * @return array<string, string>
 */
function i18n_domain_locale(string $domain, ?string $locale = null, string $default = 'fr'): array
{
    $messages = i18n_domain_messages($domain);
    if ($messages === []) {
        return [];
    }

    foreach (locale_fallback_chain($locale) as $candidateLocale) {
        if (isset($messages[$candidateLocale]) && is_array($messages[$candidateLocale])) {
            return $messages[$candidateLocale];
        }
    }

    return isset($messages[$default]) && is_array($messages[$default]) ? $messages[$default] : [];
}
}

if (!function_exists('i18n_domain_translator')) {
/**
 * Return a compact translation callback for a module catalog.
 */
function i18n_domain_translator(string $domain, ?string $locale = null, string $default = 'fr'): Closure
{
    $messages = i18n_domain_locale($domain, $locale, $default);

    return static fn(string $key): string => (string) ($messages[$key] ?? $key);
}
}

if (!function_exists('current_locale')) {
function current_locale(): string
{
    $supported = supported_locales_map();
    $queryLocale = strtolower(trim((string) ($_GET['lang'] ?? '')));
    $queryLocale = str_replace('_', '-', $queryLocale);
    if (isset($supported[$queryLocale])) {
        return $queryLocale;
    }
    if (str_contains($queryLocale, '-')) {
        $queryLocaleBase = (string) explode('-', $queryLocale, 2)[0];
        if (isset($supported[$queryLocaleBase])) {
            return $queryLocaleBase;
        }
    }

    $locale = strtolower((string) ($_SESSION['locale'] ?? ''));
    if ($locale === '') {
        $acceptLanguage = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        if ($acceptLanguage !== '') {
            $raw = trim((string) explode(',', $acceptLanguage, 2)[0]);
            $locale = str_replace('_', '-', $raw);
        }
    }
    if (!isset($supported[$locale]) && str_contains($locale, '-')) {
        $locale = (string) explode('-', $locale, 2)[0];
    }
    if (!isset($supported[$locale])) {
        return 'fr';
    }

    return $locale;
}
}

if (!function_exists('t_page')) {
function t_page(string $domain, string $key, ?string $locale = null): string
{
    $fallbackChain = locale_fallback_chain($locale);

    $diskMessages = i18n_domain_messages($domain);
    if ($diskMessages !== []) {
        foreach ($fallbackChain as $candidateLocale) {
            if (isset($diskMessages[$candidateLocale][$key])) {
                return (string) $diskMessages[$candidateLocale][$key];
            }
        }
        if (isset($diskMessages['fr'][$key])) {
            return (string) $diskMessages['fr'][$key];
        }
    }

    return $key;
}
}

function seed_modules(): void
{
    if (!table_exists('modules')) {
        return;
    }

    $modules = [
        ['dashboard', 'Tableau de bord', 'Personnalisation du dashboard', 0, 1, 'members', 10],
        ['members', 'Membres', 'Espace membres et profil', 0, 1, 'members', 20],
        ['news', 'Actualités', 'Section des actualités du club', 0, 1, 'public', 30],
        ['articles', 'Articles', 'Articles techniques', 0, 1, 'public', 40],
        ['wiki', 'Wiki', 'Base de connaissances collaborative', 0, 1, 'public', 50],
        ['albums', 'Albums', 'Galerie photos', 0, 1, 'public', 60],
        ['events', 'Événements', 'Agenda du club', 0, 1, 'public', 70],
        ['auctions', 'Enchères', 'Ventes aux enchères', 0, 1, 'public', 90],
        ['qsl', 'QSL', 'Gestion des cartes QSL', 0, 1, 'members', 100],
        ['chatbot', 'Raymond vous répond', 'Assistant conversationnel intégré au tableau de bord des membres', 0, 1, 'public', 110],
        ['advertising', 'Publicités', 'Gestion des annonces/publicités', 0, 1, 'public', 120],
        ['classifieds', 'Petites annonces', 'Module petites annonces', 0, 1, 'public', 121],
        ['press', 'Presse', 'Communiqués et contacts presse', 0, 1, 'public', 130],
        ['education', 'Éducation', 'Activités écoles/formation', 0, 1, 'public', 140],
        ['committee', 'Comité', 'Informations du comité', 0, 1, 'public', 150],
        ['directory', 'Annuaire', 'Annuaire public du club', 0, 1, 'public', 160],
        ['admin', 'Administration', 'Administration générale', 1, 1, 'admin', 1000],
    ];

    $hasVisibility = table_has_column('modules', 'visibility');
    if ($hasVisibility) {
        $stmt = db()->prepare(
            'INSERT INTO modules (code, label, description, is_core, is_enabled, visibility, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE label = VALUES(label), description = VALUES(description), is_core = VALUES(is_core), is_enabled = VALUES(is_enabled), visibility = VALUES(visibility), sort_order = VALUES(sort_order)'
        );
    } else {
        $stmt = db()->prepare(
            'INSERT INTO modules (code, label, description, is_core, is_enabled, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE label = VALUES(label), description = VALUES(description), is_core = VALUES(is_core), is_enabled = VALUES(is_enabled), sort_order = VALUES(sort_order)'
        );
    }

    foreach ($modules as $module) {
        if (!$hasVisibility) {
            unset($module[5]);
            $module = array_values($module);
        }
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
        dirname(__DIR__) . '/storage/auth',
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
        'CREATE TABLE IF NOT EXISTS users_2fa (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            mechanism TINYINT UNSIGNED NOT NULL,
            seed VARCHAR(255) DEFAULT NULL,
            created_at INT UNSIGNED NOT NULL,
            expires_at INT UNSIGNED DEFAULT NULL,
            UNIQUE KEY users_2fa_user_id_mechanism_unique (user_id, mechanism),
            CONSTRAINT users_2fa_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_audit_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED DEFAULT NULL,
            event_at INT UNSIGNED NOT NULL,
            event_type VARCHAR(128) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
            admin_id INT UNSIGNED DEFAULT NULL,
            ip_address VARCHAR(49) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            details_json TEXT DEFAULT NULL,
            KEY users_audit_log_event_at_index (event_at),
            KEY users_audit_log_user_id_event_at_index (user_id, event_at),
            KEY users_audit_log_user_id_event_type_event_at_index (user_id, event_type, event_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_confirmations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            email VARCHAR(249) NOT NULL,
            selector VARCHAR(16) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires INT UNSIGNED NOT NULL,
            UNIQUE KEY users_confirmations_selector_unique (selector),
            KEY users_confirmations_email_expires_index (email, expires),
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
        'CREATE TABLE IF NOT EXISTS users_otps (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            mechanism TINYINT UNSIGNED NOT NULL,
            single_factor TINYINT UNSIGNED NOT NULL DEFAULT 0,
            selector VARCHAR(24) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at INT UNSIGNED DEFAULT NULL,
            KEY users_otps_user_id_mechanism_index (user_id, mechanism),
            KEY users_otps_selector_user_id_index (selector, user_id),
            CONSTRAINT users_otps_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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
            KEY users_resets_user_expires_index (user, expires),
            CONSTRAINT users_resets_user_foreign FOREIGN KEY (user) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_throttling (
            bucket VARCHAR(44) NOT NULL,
            tokens FLOAT UNSIGNED NOT NULL,
            replenished_at INT UNSIGNED NOT NULL,
            expires_at INT UNSIGNED NOT NULL,
            PRIMARY KEY (bucket),
            KEY users_throttling_expires_at_index (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (!table_has_index('users_confirmations', 'users_confirmations_email_expires_index')) {
        db()->exec('ALTER TABLE users_confirmations ADD INDEX users_confirmations_email_expires_index (email, expires)');
    }
    if (!table_has_index('users_resets', 'users_resets_user_expires_index')) {
        db()->exec('ALTER TABLE users_resets ADD INDEX users_resets_user_expires_index (user, expires)');
    }
    if (!table_has_index('users_throttling', 'users_throttling_expires_at_index')) {
        db()->exec('ALTER TABLE users_throttling ADD INDEX users_throttling_expires_at_index (expires_at)');
    }

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

        $columnStmt->execute(['articles', 'scheduled_at']);
        if ((int) $columnStmt->fetchColumn() === 0) {
            db()->exec('ALTER TABLE articles ADD COLUMN scheduled_at DATETIME NULL DEFAULT NULL AFTER status');
        }

        $columnStmt->execute(['articles', 'published_at']);
        if ((int) $columnStmt->fetchColumn() === 0) {
            db()->exec('ALTER TABLE articles ADD COLUMN published_at DATETIME NULL DEFAULT NULL AFTER scheduled_at');
        }

        db()->exec('ALTER TABLE articles MODIFY COLUMN status ENUM("draft","scheduled","published") NOT NULL DEFAULT "draft"');

        db()->exec(
            'CREATE TABLE IF NOT EXISTS article_revisions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                article_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                excerpt TEXT NULL,
                content LONGTEXT NULL,
                status VARCHAR(32) NOT NULL DEFAULT "draft",
                category VARCHAR(120) NOT NULL DEFAULT "autres",
                scheduled_at DATETIME NULL DEFAULT NULL,
                published_at DATETIME NULL DEFAULT NULL,
                author_id INT NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_article_revision_article_created (article_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }



    db()->exec(
        'CREATE TABLE IF NOT EXISTS dashboard_widget_settings (
            widget_key VARCHAR(120) PRIMARY KEY,
            is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

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

    if (table_exists('album_photos')) {
        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnStmt->execute(['album_photos', 'sort_order']);
        if ((int) $columnStmt->fetchColumn() === 0) {
            db()->exec('ALTER TABLE album_photos ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER album_id');
            db()->exec('UPDATE album_photos SET sort_order = id WHERE sort_order = 0');
        }
    }

    if (table_exists('members')) {
        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $requiredColumns = [
            'auth_user_id' => 'ALTER TABLE members ADD COLUMN auth_user_id INT UNSIGNED DEFAULT NULL UNIQUE',
            'country' => 'ALTER TABLE members ADD COLUMN country VARCHAR(190) DEFAULT NULL',
            'is_uba_member' => 'ALTER TABLE members ADD COLUMN is_uba_member TINYINT(1) NOT NULL DEFAULT 0',
            'uba_member_number' => 'ALTER TABLE members ADD COLUMN uba_member_number VARCHAR(64) DEFAULT NULL',
            'visibility_full_name' => 'ALTER TABLE members ADD COLUMN visibility_full_name ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_country' => 'ALTER TABLE members ADD COLUMN visibility_country ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_locator' => 'ALTER TABLE members ADD COLUMN visibility_locator ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_bio' => 'ALTER TABLE members ADD COLUMN visibility_bio ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_licence_class' => 'ALTER TABLE members ADD COLUMN visibility_licence_class ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_qsl' => 'ALTER TABLE members ADD COLUMN visibility_qsl ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_qrz' => 'ALTER TABLE members ADD COLUMN visibility_qrz ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_uba' => 'ALTER TABLE members ADD COLUMN visibility_uba ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_favourite_bands' => 'ALTER TABLE members ADD COLUMN visibility_favourite_bands ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_favourite_modes' => 'ALTER TABLE members ADD COLUMN visibility_favourite_modes ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_antennas' => 'ALTER TABLE members ADD COLUMN visibility_antennas ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_interests' => 'ALTER TABLE members ADD COLUMN visibility_interests ENUM("public","members","private") NOT NULL DEFAULT "members"',
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
        db()->exec("UPDATE modules SET is_enabled = 1, visibility = 'public' WHERE code IN ('news', 'articles', 'wiki', 'albums', 'events', 'auctions', 'chatbot', 'advertising', 'classifieds', 'press', 'education', 'committee', 'directory')");
        db()->exec("UPDATE modules SET is_enabled = 1, visibility = 'members' WHERE code IN ('dashboard', 'members', 'qsl')");
        db()->exec("UPDATE modules SET visibility = 'admin' WHERE code = 'admin'");
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS quotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_fr TEXT DEFAULT NULL,
            quote_en TEXT DEFAULT NULL,
            quote_de TEXT DEFAULT NULL,
            quote_nl TEXT DEFAULT NULL,
            quote_it TEXT DEFAULT NULL,
            quote_es TEXT DEFAULT NULL,
            quote_pt TEXT DEFAULT NULL,
            quote_ar TEXT DEFAULT NULL,
            quote_hi TEXT DEFAULT NULL,
            quote_ja TEXT DEFAULT NULL,
            quote_zh TEXT DEFAULT NULL,
            quote_bn TEXT DEFAULT NULL,
            quote_ru TEXT DEFAULT NULL,
            quote_id TEXT DEFAULT NULL,
            author VARCHAR(190) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    if (table_exists('quotes')) {
        $legacyColumnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $legacyColumnStmt->execute(['quotes', 'quote_text']);
        if ((int) $legacyColumnStmt->fetchColumn() > 0) {
            db()->exec('ALTER TABLE quotes DROP COLUMN quote_text');
        }

        foreach (quote_locale_columns() as $quoteColumn) {
            $columnStmt = db()->prepare(
                'SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
            );
            $columnStmt->execute(['quotes', $quoteColumn]);
            if ((int) $columnStmt->fetchColumn() === 0) {
                db()->exec('ALTER TABLE quotes ADD COLUMN ' . $quoteColumn . ' TEXT DEFAULT NULL');
            }
        }
    }

    $quoteCount = db()->query('SELECT COUNT(*) FROM quotes');
    $hasQuotes = $quoteCount !== false ? (int) $quoteCount->fetchColumn() > 0 : false;
    if (!$hasQuotes) {
        $seedCandidates = [
            __DIR__ . '/../assets/sql/radioamateur_citations_multilingue_3532_mysql.sql',
        ];
        $seedFile = '';
        foreach ($seedCandidates as $candidatePath) {
            if (is_file($candidatePath)) {
                $seedFile = $candidatePath;
                break;
            }
        }
        if ($seedFile !== '') {
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

    ensure_classified_ads_table();
    ensure_wiki_tables();

    ensure_member_favorites_table();
    ensure_member_notifications_table();
}

if (!function_exists('ensure_classified_ads_table')) {
function ensure_classified_ads_table(): bool
{
    try {
        db()->exec(
            'CREATE TABLE IF NOT EXISTS classified_ads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                owner_member_id INT NOT NULL,
                category_code VARCHAR(32) NOT NULL DEFAULT "gear",
                title VARCHAR(190) NOT NULL,
                description TEXT DEFAULT NULL,
                location VARCHAR(120) DEFAULT NULL,
                contact VARCHAR(190) DEFAULT NULL,
                price_cents INT NOT NULL DEFAULT 0,
                status ENUM("draft","active","sold","archived","expired") NOT NULL DEFAULT "draft",
                expires_at DATETIME NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_classified_owner_status (owner_member_id, status),
                INDEX idx_classified_status_created (status, created_at),
                INDEX idx_classified_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );

        $columnStmt->execute(['classified_ads', 'expires_at']);
        if ((int) $columnStmt->fetchColumn() === 0) {
            db()->exec('ALTER TABLE classified_ads ADD COLUMN expires_at DATETIME NULL DEFAULT NULL AFTER status');
        }

        db()->exec('ALTER TABLE classified_ads MODIFY COLUMN status ENUM("draft","active","sold","archived","expired") NOT NULL DEFAULT "draft"');

        return true;
    } catch (Throwable $throwable) {
        log_structured_event('classified_ads_table_ensure_failed', [
            'message' => $throwable->getMessage(),
        ]);

        return false;
    }
}
}

if (!function_exists('ensure_wiki_tables')) {
function ensure_wiki_tables(): bool
{
    try {
        db()->exec(
            'CREATE TABLE IF NOT EXISTS wiki_pages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(190) NOT NULL UNIQUE,
                title VARCHAR(190) NOT NULL,
                content LONGTEXT NOT NULL,
                author_id INT DEFAULT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_wiki_updated (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        db()->exec(
            'CREATE TABLE IF NOT EXISTS wiki_revisions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                wiki_page_id INT NOT NULL,
                member_id INT DEFAULT NULL,
                content LONGTEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_wiki_revision_page (wiki_page_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        return true;
    } catch (Throwable $throwable) {
        log_structured_event('wiki_tables_ensure_failed', [
            'message' => $throwable->getMessage(),
        ]);

        return false;
    }
}
}

if (!function_exists('classifieds_sync_expired')) {
function classifieds_sync_expired(): void
{
    if (!ensure_classified_ads_table()) {
        return;
    }

    db()->exec('UPDATE classified_ads SET status = "expired", updated_at = NOW() WHERE status = "active" AND expires_at IS NOT NULL AND expires_at < NOW()');
}
}

if (!function_exists('articles_sync_scheduled_publications')) {
function articles_sync_scheduled_publications(): void
{
    if (!table_exists('articles')) {
        return;
    }

    db()->exec('UPDATE articles SET status = "published", published_at = COALESCE(published_at, NOW()), updated_at = NOW() WHERE status = "scheduled" AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()');
}
}

function quote_locale_columns(): array
{
    return array_map(static fn(string $locale): string => 'quote_' . $locale, supported_locales());
}

function native_quote_fallback_for_locale(string $locale): array
{
    $quotes = [
        'fr' => ['quote' => 'Chaque contact radio est une passerelle ouverte vers une autre voix.', 'author' => 'ON4CRD'],
        'en' => ['quote' => 'Every radio contact opens a path to another voice.', 'author' => 'ON4CRD'],
        'de' => ['quote' => 'Jeder Funkkontakt öffnet einen Weg zu einer anderen Stimme.', 'author' => 'ON4CRD'],
        'nl' => ['quote' => 'Elk radiocontact opent een pad naar een andere stem.', 'author' => 'ON4CRD'],
        'it' => ['quote' => 'Ogni contatto radio apre un ponte verso un’altra voce.', 'author' => 'ON4CRD'],
        'es' => ['quote' => 'Cada contacto de radio abre un puente hacia otra voz.', 'author' => 'ON4CRD'],
        'pt' => ['quote' => 'Cada contacto de rádio abre uma ponte para outra voz.', 'author' => 'ON4CRD'],
        'ar' => ['quote' => 'كل اتصال لاسلكي يفتح جسراً نحو صوت آخر.', 'author' => 'ON4CRD'],
        'hi' => ['quote' => 'हर रेडियो संपर्क किसी दूसरी आवाज़ तक एक पुल खोलता है।', 'author' => 'ON4CRD'],
        'ja' => ['quote' => 'ひとつの無線交信が、別の声へ続く橋を開く。', 'author' => 'ON4CRD'],
        'zh' => ['quote' => '每一次无线电通联，都是通向另一种声音的桥梁。', 'author' => 'ON4CRD'],
        'bn' => ['quote' => 'প্রতিটি রেডিও যোগাযোগ আরেকটি কণ্ঠের দিকে একটি সেতু খুলে দেয়।', 'author' => 'ON4CRD'],
        'ru' => ['quote' => 'Каждая радиосвязь открывает мост к другому голосу.', 'author' => 'ON4CRD'],
        'id' => ['quote' => 'Setiap kontak radio membuka jembatan menuju suara lain.', 'author' => 'ON4CRD'],
    ];

    return $quotes[$locale] ?? $quotes['fr'];
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

    if (
        preg_match('/INSERT INTO\s+(?:public\.)?(?:`?radioamateur_citations`?)/i', $sql) === 1
    ) {
        seed_quotes_from_radioamateur_dump($sql);
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

function seed_quotes_from_radioamateur_dump(string $sql): void
{
    if (!preg_match_all('/INSERT INTO\s+(?:public\.)?(?:`?radioamateur_citations`?)\s*\([^)]*\)\s*VALUES\s*(.+?);/is', $sql, $matches)) {
        return;
    }

    $insertStmt = db()->prepare('INSERT INTO quotes (quote_fr, quote_en, quote_de, quote_nl, author, is_active) VALUES (?, ?, ?, ?, ?, 1)');
    if ($insertStmt === false) {
        return;
    }

    foreach (($matches[1] ?? []) as $valuesBlock) {
        if (!preg_match_all("/\\(\\s*'((?:''|\\\\'|[^'])*)'\\s*,\\s*'((?:''|\\\\'|[^'])*)'\\s*,\\s*'((?:''|\\\\'|[^'])*)'\\s*,\\s*'((?:''|\\\\'|[^'])*)'\\s*,/u", (string) $valuesBlock, $rows, PREG_SET_ORDER)) {
            continue;
        }

        foreach ($rows as $rowMatch) {
            $quoteFr = trim(str_replace(["''", "\\'"], ["'", "'"], (string) ($rowMatch[1] ?? '')));
            $quoteEn = trim(str_replace(["''", "\\'"], ["'", "'"], (string) ($rowMatch[2] ?? '')));
            $quoteDe = trim(str_replace(["''", "\\'"], ["'", "'"], (string) ($rowMatch[3] ?? '')));
            $quoteNl = trim(str_replace(["''", "\\'"], ["'", "'"], (string) ($rowMatch[4] ?? '')));
            if ($quoteFr === '') {
                continue;
            }

            try {
                $insertStmt->execute([$quoteFr, $quoteEn, $quoteDe, $quoteNl, null]);
            } catch (Throwable) {
                continue;
            }
        }
    }
}

function random_quote_for_layout(): ?array
{
    try {
        if (!table_exists('quotes')) {
            return null;
        }

        $whereActive = table_has_column('quotes', 'is_active') ? ' WHERE is_active = 1' : '';
        $countStmt = db()->query('SELECT COUNT(*) FROM quotes' . $whereActive);
        $activeCount = $countStmt !== false ? (int) $countStmt->fetchColumn() : 0;
        if ($activeCount <= 0) {
            return null;
        }

        $daySeed = date('Y-m-d');
        $offset = (int) (sprintf('%u', crc32($daySeed)) % $activeCount);
        $quoteColumns = array_merge(quote_locale_columns(), ['author']);
        foreach ($quoteColumns as $quoteColumn) {
            if (!table_has_column('quotes', $quoteColumn)) {
                if ($quoteColumn === 'author') {
                    return null;
                }
                continue;
            }
        }

        $selectColumns = array_filter($quoteColumns, static fn(string $quoteColumn): bool => table_has_column('quotes', $quoteColumn));
        $stmt = db()->query('SELECT ' . implode(', ', $selectColumns) . ' FROM quotes' . $whereActive . ' LIMIT 1 OFFSET ' . $offset);
        if ($stmt === false) {
            return null;
        }
    } catch (Throwable) {
        return null;
    }
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return null;
    }

    $locale = current_locale();
    $localizedQuotes = [];
    foreach (supported_locales() as $supportedLocale) {
        $localizedQuotes[$supportedLocale] = trim((string) ($row['quote_' . $supportedLocale] ?? ''));
    }
    $quote = $localizedQuotes[$locale] ?? '';
    if ($quote === '') {
        $nativeFallback = native_quote_fallback_for_locale($locale);
        $quote = (string) $nativeFallback['quote'];
    }
    $author = trim((string) ($row['author'] ?? ''));
    if ($author === '' && isset($nativeFallback)) {
        $author = (string) $nativeFallback['author'];
    }
    if ($quote === '') {
        return null;
    }

    return [
        'quote' => $quote,
        'author' => $author,
    ];
}

function qrz_profile_url_for_callsign(string $callsign): ?string
{
    $callsign = strtoupper(trim($callsign));
    if ($callsign === '' || preg_match('/^[A-Z0-9\/-]{2,32}$/', $callsign) !== 1) {
        return null;
    }

    $url = 'https://www.qrz.com/db/' . rawurlencode($callsign);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: ON4CRD Profile Validator\r\nAccept: text/html\r\n",
            'ignore_errors' => true,
            'timeout' => 4,
        ],
    ]);

    $body = @file_get_contents($url, false, $context, 0, 262144);
    if (!is_string($body) || $body === '') {
        return null;
    }

    $statusLine = (string) ($http_response_header[0] ?? '');
    if (!preg_match('/\s(2\d\d|3\d\d)\s/', $statusLine)) {
        return null;
    }

    $normalizedBody = strtolower($body);
    if (
        str_contains($normalizedBody, 'not found') ||
        str_contains($normalizedBody, 'no such callsign') ||
        str_contains($normalizedBody, 'callsign not found')
    ) {
        return null;
    }

    return $url;
}

if (!function_exists('base_url')) {
function base_url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
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
    static $versionCache = [];

    $normalizedPath = ltrim($path, '/');
    if ($normalizedPath === '') {
        return base_url($path);
    }

    if (!array_key_exists($normalizedPath, $versionCache)) {
        $absolutePath = dirname(__DIR__) . '/' . $normalizedPath;
        $versionCache[$normalizedPath] = is_file($absolutePath) ? (string) filemtime($absolutePath) : '';
    }

    $assetUrl = base_url($normalizedPath);
    $version = $versionCache[$normalizedPath];
    if ($version === '') {
        return $assetUrl;
    }

    $separator = str_contains($assetUrl, '?') ? '&' : '?';
    return $assetUrl . $separator . 'v=' . rawurlencode($version);
}
}

if (!function_exists('route_url')) {
function route_url(string $route, array $query = []): string
{
    static $directPhpRoutes = ['install.php' => true, 'sitemap.xml' => true, 'robots.txt' => true, 'llms.txt' => true];

    $route = trim($route);
    if ($route === '' || $route === 'home') {
        if ($query === []) {
            return base_url('/');
        }

        return base_url('/?' . http_build_query($query));
    }

    if (str_ends_with($route, '.php')) {
        $normalizedRoute = ltrim($route, '/');
        if (isset($directPhpRoutes[$normalizedRoute])) {
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

if (!function_exists('clean_query_params')) {
/**
 * Drop empty query values while preserving meaningful numeric and boolean values.
 *
 * @param array<string, mixed> $query
 * @return array<string, mixed>
 */
function clean_query_params(array $query): array
{
    return array_filter($query, static fn(mixed $value): bool => $value !== '' && $value !== null && $value !== false);
}
}

if (!function_exists('route_url_clean')) {
/**
 * Build a route URL without leaking empty filter values into the query string.
 *
 * @param array<string, mixed> $query
 */
function route_url_clean(string $route, array $query = []): string
{
    return route_url($route, clean_query_params($query));
}
}

if (!function_exists('pagination_state')) {
/**
 * Normalize common pagination values for list pages.
 *
 * @return array{page:int, per_page:int, total_pages:int, offset:int}
 */
function pagination_state(int $totalItems, int $requestedPage, int $perPage): array
{
    $perPage = max(1, $perPage);
    $totalPages = max(1, (int) ceil(max(0, $totalItems) / $perPage));
    $page = min(max(1, $requestedPage), $totalPages);

    return [
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'offset' => ($page - 1) * $perPage,
    ];
}
}

if (!function_exists('env')) {
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }
    return $value;
}
}

if (!function_exists('storage_path')) {
function storage_path(string $path = ''): string
{
    $base = dirname(__DIR__) . '/storage';
    if ($path === '') {
        return $base;
    }
    return $base . '/' . ltrim($path, '/');
}
}

if (!function_exists('llphant_embedding_generator')) {
function llphant_embedding_generator(): ?object
{
    return null;
}
}

if (!function_exists('llphant_embedding_vector')) {
/** @return list<float> */
function llphant_embedding_vector(string $text): array
{
    return [];
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
function mark_authenticated_response_private(): void
{
    if (!headers_sent()) {
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
    }
}

function auth_bypass_member_id(): int
{
    $environment = strtolower(trim((string) config('app.env', 'production')));
    if ($environment !== 'development') {
        return 0;
    }

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
        'classifieds',
        'auction_bid',
        'newsletter',
    ];
    if ($temporaryBypassForMembers && in_array($route, $memberBypassRoutes, true) && table_exists('members')) {
        $stmt = db()->query('SELECT id FROM members WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
        $firstActiveMemberId = $stmt !== false ? (int) $stmt->fetchColumn() : 0;

        return max(0, $firstActiveMemberId);
    }

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
        'classifieds',
        'auction_bid',
        'newsletter',
    ];
    if (!$allowDevelopmentBypass || !table_exists('members') || !in_array($route, $memberBypassRoutes, true)) {
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
    $authUserId = 0;
    $authClient = auth();
    if ($authClient !== null && $authClient->isLoggedIn()) {
        $authUserId = (int) $authClient->getUserId();
        $memberId = $authUserId;
    } elseif ($authClient !== null && $memberId > 0) {
        unset($_SESSION['member_id']);
        $memberId = 0;
    } elseif ($authClient === null && $memberId > 0) {
        unset($_SESSION['member_id']);
        $memberId = 0;
    }

    if ($memberId <= 0) {
        $bypassMemberId = auth_bypass_member_id();
        if ($bypassMemberId > 0) {
            $bypassUser = bypass_member_user($bypassMemberId);
            if (is_array($bypassUser)) {
                $_SESSION['member_id'] = (int) $bypassUser['id'];
                mark_authenticated_response_private();
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

    $memberColumns = ['id'];
    foreach (['callsign', 'full_name', 'email', 'locator', 'is_active', 'is_committee'] as $memberColumn) {
        if (table_has_column('members', $memberColumn)) {
            $memberColumns[] = $memberColumn;
        }
    }
    if ($authUserId > 0 && table_has_column('members', 'auth_user_id')) {
        $where = 'auth_user_id = ?';
        $params = [$authUserId];
    } else {
        $where = 'id = ?';
        $params = [$memberId];
    }

    try {
        $stmt = db()->prepare('SELECT ' . implode(', ', $memberColumns) . ' FROM members WHERE ' . $where . ' LIMIT 1');
        $stmt->execute($params);
        $row = $stmt->fetch();
    } catch (Throwable) {
        $cache = null;
        return null;
    }
    if (!is_array($row) || (int) ($row['is_active'] ?? 0) !== 1) {
        unset($_SESSION['member_id']);
        $cache = null;
        return null;
    }

    $_SESSION['member_id'] = (int) ($row['id'] ?? 0);
    mark_authenticated_response_private();
    $cache = $row;
    return $cache;
}
}

if (!function_exists('require_login')) {
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        $locale = current_locale();
        $message = match ($locale) {
            'en' => 'Please sign in to continue.',
            'de' => 'Bitte melden Sie sich an, um fortzufahren.',
            'nl' => 'Log in om verder te gaan.',
            'es' => 'Inicia sesión para continuar.',
            'it' => 'Accedi per continuare.',
            'pt' => 'Inicie sessão para continuar.',
            'ar' => 'يرجى تسجيل الدخول للمتابعة.',
            'hi' => 'जारी रखने के लिए कृपया लॉग इन करें।',
            'ja' => '続行するにはログインしてください。',
            'zh' => '请先登录以继续。',
            'bn' => 'চালিয়ে যেতে অনুগ্রহ করে লগইন করুন।',
            'ru' => 'Пожалуйста, войдите, чтобы продолжить.',
            'id' => 'Silakan masuk untuk melanjutkan.',
            default => 'Veuillez vous connecter pour continuer.',
        };
        set_flash('error', $message);
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
        try {
            $authClient->logOut();
        } catch (Throwable) {
            // Continue with local cleanup even if the auth library cannot update its tables.
        }
    }

    foreach ([
        'member_id',
        'auth_logged_in',
        'auth_user_id',
        'auth_email',
        'auth_username',
        'auth_status',
        'auth_roles',
        'auth_remembered',
        'auth_last_resync',
        'auth_force_logout',
        'auth_awaiting_2fa_until',
        'auth_awaiting_2fa_user_id',
        'auth_awaiting_2fa_remember_duration',
    ] as $sessionKey) {
        unset($_SESSION[$sessionKey]);
    }

    $rememberCookieNames = ['auth_remember'];
    if (class_exists(\Delight\Auth\Auth::class)) {
        $rememberCookieNames[] = \Delight\Auth\Auth::createRememberCookieName(session_name());
    }
    $cookieParams = session_get_cookie_params();
    foreach (array_unique($rememberCookieNames) as $cookieName) {
        unset($_COOKIE[$cookieName]);
        $cookieOptions = [
            'expires' => time() - 3600,
            'path' => $cookieParams['path'] ?? '/',
            'secure' => (bool) ($cookieParams['secure'] ?? false),
            'httponly' => true,
            'samesite' => (string) ($cookieParams['samesite'] ?? 'Lax'),
        ];
        if (!empty($cookieParams['domain'])) {
            $cookieOptions['domain'] = (string) $cookieParams['domain'];
        }
        setcookie($cookieName, '', $cookieOptions);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}
}

if (!function_exists('module_row')) {
function module_row(string $module): ?array
{
    static $cache = [];

    $module = trim($module);
    if ($module === '' || !table_exists('modules')) {
        return null;
    }

    if (array_key_exists($module, $cache)) {
        return $cache[$module];
    }

    $columns = ['is_enabled'];
    if (table_has_column('modules', 'visibility')) {
        $columns[] = 'visibility';
    }

    try {
        $stmt = db()->prepare('SELECT ' . implode(', ', $columns) . ' FROM modules WHERE code = ? LIMIT 1');
        $stmt->execute([$module]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        $row = false;
    }
    $cache[$module] = is_array($row) ? $row : null;

    return $cache[$module];
}
}

if (!function_exists('module_enabled')) {
function module_enabled(string $module): bool
{
    if ($module === '') {
        return true;
    }

    $row = module_row($module);
    if ($row === null) {
        return true;
    }

    return (int) $row['is_enabled'] === 1;
}
}


if (!function_exists('module_visible_for_current_user')) {
function module_visible_for_current_user(string $module): bool
{
    if ($module === '') {
        return true;
    }

    $row = module_row($module);
    $visibility = (string) ($row['visibility'] ?? 'public');

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
    $locale = current_locale();
    $moduleUnavailable = match ($locale) {
        'en' => 'Module unavailable.',
        'de' => 'Modul nicht verfügbar.',
        'nl' => 'Module niet beschikbaar.',
        'es' => 'Módulo no disponible.',
        'it' => 'Modulo non disponibile.',
        'pt' => 'Módulo indisponível.',
        'ar' => 'الوحدة غير متاحة.',
        'hi' => 'मॉड्यूल उपलब्ध नहीं है।',
        'ja' => 'モジュールは利用できません。',
        'zh' => '模块不可用。',
        'bn' => 'মডিউলটি উপলভ্য নয়।',
        'ru' => 'Модуль недоступен.',
        'id' => 'Modul tidak tersedia.',
        default => 'Module indisponible.',
    };
    echo render_layout('<div class="card"><h1>404</h1><p>' . e($moduleUnavailable) . '</p></div>', '404');
    exit;
}
}

if (!function_exists('has_permission')) {
function has_permission(string $permission): bool
{
    static $permissionCache = [];
    static $schemaReady = null;

    $user = current_user();
    if ($user === null || $permission === '') {
        return false;
    }

    $userId = (int) $user['id'];
    $cacheKey = $userId . '|' . $permission;
    if (array_key_exists($cacheKey, $permissionCache)) {
        return $permissionCache[$cacheKey];
    }

    if ($schemaReady === null) {
        $schemaReady = table_exists('permissions')
            && table_exists('roles')
            && table_exists('member_roles')
            && table_exists('member_permissions')
            && table_exists('role_permissions');
    }
    if (!$schemaReady) {
        $permissionCache[$cacheKey] = false;
        return $permissionCache[$cacheKey];
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
    $stmt->execute([$userId, $userId, $permission]);

    $permissionCache[$cacheKey] = (bool) $stmt->fetchColumn();
    return $permissionCache[$cacheKey];
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
    $i18n = i18n_domain_locale('footer', $locale);

    return '<footer class="site-footer"><div class="footer-inner"><div class="footer-meta"><span>© 2026 Radio Club Durnal (ON4CRD)</span>' . render_footer_social_links() . '<span>' . e((string) $i18n['built_by']) . ' <a href="https://smartappli.eu">Smartappli ®</a></span></div></div></footer>';
}
}

if (!function_exists('render_layout')) {
function route_url_with_locale(string $route, string $locale, array $query = []): string
{
    $query['lang'] = $locale;
    return route_url_clean($route, $query);
}

function seo_public_current_query(): array
{
    $query = (array) $_GET;
    foreach (['route', 'lang', 'locale', '_csrf', 'maintenance_bypass', 'fbclid', 'gclid', 'msclkid', 'mc_cid', 'mc_eid'] as $key) {
        unset($query[$key]);
    }
    foreach (array_keys($query) as $key) {
        if (str_starts_with(strtolower((string) $key), 'utm_')) {
            unset($query[$key]);
        }
    }

    ksort($query);
    return clean_query_params($query);
}

function localized_seo_defaults(string $route, string $locale, array $pageMeta, string $siteName): array
{
    $seo = i18n_domain_locale('seo', $locale);
    $routeKey = preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: 'home';
    $canonicalRoute = in_array($route, ['install.php', 'sitemap.xml', 'robots.txt', 'llms.txt'], true) ? $route : $routeKey;
    $routeSeo = [
        'ad_click' => ['title' => 'Redirection partenaire ON4CRD', 'description' => 'Redirection securisee vers une annonce ou un partenaire du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,nofollow'],
        'admin' => ['title' => 'Administration ON4CRD', 'description' => 'Tableau d administration du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,nofollow'],
        'ads' => ['title' => 'Annonces partenaires ON4CRD', 'description' => 'Annonces, partenaires et communications sponsorisees du Radio Club Durnal ON4CRD.'],
        'album' => ['title' => 'Album photo ON4CRD', 'description' => 'Album photo public des activites, sorties et evenements du Radio Club Durnal ON4CRD.'],
        'albums' => ['title' => 'Galerie photo ON4CRD', 'description' => 'Galerie des albums publics du Radio Club Durnal ON4CRD.'],
        'article' => ['title' => 'Article ON4CRD', 'description' => 'Article radioamateur publie par le Radio Club Durnal ON4CRD.'],
        'articles' => ['title' => 'Articles radioamateurs ON4CRD', 'description' => 'Articles, guides et retours d experience radioamateurs du Radio Club Durnal ON4CRD.'],
        'auction_bid' => ['title' => 'Offre enchere ON4CRD', 'description' => 'Endpoint de soumission d offre pour les encheres ON4CRD.', 'robots' => 'noindex,nofollow'],
        'auction_view' => ['title' => 'Detail enchere ON4CRD', 'description' => 'Detail d un lot ou d une enchere radioamateur proposee par ON4CRD.'],
        'auctions' => ['title' => 'Encheres ON4CRD', 'description' => 'Encheres de materiel radioamateur du Radio Club Durnal ON4CRD.'],
        'bandplan_harec' => ['title' => 'Band plan HAREC', 'description' => 'Plan de bandes radioamateur HAREC et reperes de frequences pour les operateurs ON4CRD.'],
        'bandplan_on2' => ['title' => 'Band plan ON2', 'description' => 'Plan de bandes ON2 pour preparer ses communications radioamateurs en Belgique.'],
        'bandplan_on3' => ['title' => 'Band plan ON3', 'description' => 'Plan de bandes ON3 et ressources pratiques pour les radioamateurs debutants.'],
        'chatbot' => ['title' => 'Assistant ON4CRD', 'description' => 'Assistant pratique du Radio Club Durnal pour retrouver les informations du site et les ressources radioamateurs.'],
        'classifieds' => ['title' => 'Petites annonces ON4CRD', 'description' => 'Petites annonces radioamateurs et materiel entre membres et visiteurs ON4CRD.'],
        'code_cw' => ['title' => 'Code CW et Morse', 'description' => 'Ressources ON4CRD pour apprendre, reviser et pratiquer le code Morse CW.'],
        'code_q' => ['title' => 'Code Q radioamateur', 'description' => 'Liste des codes Q utiles pour le trafic radioamateur et les echanges ON4CRD.'],
        'committee' => ['title' => 'Comite ON4CRD', 'description' => 'Presentation du comite et de l organisation du Radio Club Durnal ON4CRD.'],
        'conditions_utilisation' => ['title' => 'Conditions d utilisation ON4CRD', 'description' => 'Conditions generales d utilisation du site du Radio Club Durnal ON4CRD.'],
        'dashboard' => ['title' => 'Tableau de bord ON4CRD', 'description' => 'Tableau de bord personnel de l espace membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'dashboard_widget_card' => ['title' => 'Widget dashboard ON4CRD', 'description' => 'Fragment technique de widget du tableau de bord membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'directory' => ['title' => 'Annuaire des membres ON4CRD', 'description' => 'Annuaire radioamateur des membres visibles du Radio Club Durnal ON4CRD, indicatifs, licences et QTH.'],
        'event_view' => ['title' => 'Detail evenement ON4CRD', 'description' => 'Detail d un evenement, d une reunion ou d une activite radioamateur du Radio Club Durnal.'],
        'events' => ['title' => 'Agenda ON4CRD', 'description' => 'Agenda des reunions, activites, sorties et evenements radioamateurs du Radio Club Durnal.'],
        'events_feed' => ['title' => 'Flux calendrier ON4CRD', 'description' => 'Flux technique des evenements ON4CRD pour FullCalendar et exports.', 'robots' => 'noindex,nofollow'],
        'footer_contact' => ['title' => 'Contact ON4CRD', 'description' => 'Endpoint de contact du pied de page ON4CRD.', 'robots' => 'noindex,nofollow'],
        'forgot_password' => ['title' => 'Mot de passe oublie ON4CRD', 'description' => 'Procedure de recuperation d acces a l espace membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'gdpr' => ['title' => 'Vie privee ON4CRD', 'description' => 'Gestion des preferences de confidentialite et de visibilite du profil membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'home' => ['title' => 'Radio Club Durnal ON4CRD', 'description' => 'Portail du Radio Club Durnal ON4CRD : actualites, evenements, outils et ressources radioamateurs.'],
        'installphp' => ['title' => 'Installation ON4CRD', 'description' => 'Endpoint d installation technique ON4CRD.', 'robots' => 'noindex,nofollow'],
        'llmstxt' => ['title' => 'LLMS ON4CRD', 'description' => 'Fichier de contexte public pour assistants et moteurs de recherche.', 'robots' => 'noindex,follow'],
        'login' => ['title' => 'Connexion membre ON4CRD', 'description' => 'Connexion securisee a l espace membre du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,nofollow'],
        'logout' => ['title' => 'Deconnexion ON4CRD', 'description' => 'Deconnexion securisee de l espace membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'members_library' => ['title' => 'Bibliotheque membres ON4CRD', 'description' => 'Bibliotheque documentaire reservee aux membres du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,follow'],
        'membership' => ['title' => 'Devenir membre du CRD', 'description' => 'Informations pour devenir membre du Radio Club Durnal, rejoindre les activites et participer a la communaute ON4CRD.'],
        'mentions_legales' => ['title' => 'Mentions legales ON4CRD', 'description' => 'Mentions legales et informations editoriales du site Radio Club Durnal ON4CRD.'],
        'news' => ['title' => 'Actualites ON4CRD', 'description' => 'Dernieres actualites, annonces et informations du Radio Club Durnal ON4CRD.'],
        'news_view' => ['title' => 'Actualite ON4CRD', 'description' => 'Article d actualite du Radio Club Durnal ON4CRD et informations radioamateurs locales.'],
        'newsletter' => ['title' => 'Newsletter membre ON4CRD', 'description' => 'Gestion de l abonnement newsletter pour les membres du Radio Club Durnal.', 'robots' => 'noindex,nofollow'],
        'newsletter_public' => ['title' => 'Newsletter ON4CRD', 'description' => 'Newsletter publique du Radio Club Durnal ON4CRD.'],
        'newsletter_unsubscribe' => ['title' => 'Desinscription newsletter ON4CRD', 'description' => 'Desinscription securisee de la newsletter ON4CRD.', 'robots' => 'noindex,nofollow'],
        'notifications' => ['title' => 'Notifications membre ON4CRD', 'description' => 'Notifications personnelles de l espace membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'press' => ['title' => 'Presse ON4CRD', 'description' => 'Communiques, publications et informations presse du Radio Club Durnal ON4CRD.'],
        'profile' => ['title' => 'Profil membre ON4CRD', 'description' => 'Profil personnel et informations de membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'qsl' => ['title' => 'QSL ON4CRD', 'description' => 'Gestion QSL reservee aux membres ON4CRD.', 'robots' => 'noindex,nofollow'],
        'qsl_export' => ['title' => 'Export QSL ON4CRD', 'description' => 'Export technique des donnees QSL ON4CRD.', 'robots' => 'noindex,nofollow'],
        'qsl_preview' => ['title' => 'Apercu QSL ON4CRD', 'description' => 'Apercu technique QSL ON4CRD.', 'robots' => 'noindex,nofollow'],
        'register' => ['title' => 'Creer un compte ON4CRD', 'description' => 'Creation d un compte membre pour acceder aux services du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,nofollow'],
        'reglement_interieur' => ['title' => 'Reglement interieur ON4CRD', 'description' => 'Reglement interieur et cadre de fonctionnement du Radio Club Durnal.'],
        'relais' => ['title' => 'Relais ON4CRD', 'description' => 'Informations relais et ressources radioamateurs locales du Radio Club Durnal ON4CRD.'],
        'reset_password' => ['title' => 'Reinitialisation du mot de passe ON4CRD', 'description' => 'Reinitialisation securisee du mot de passe membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'robotstxt' => ['title' => 'Robots ON4CRD', 'description' => 'Fichier robots.txt du site ON4CRD.', 'robots' => 'noindex,nofollow'],
        'save_dashboard' => ['title' => 'Sauvegarde tableau de bord ON4CRD', 'description' => 'Endpoint de sauvegarde du tableau de bord membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'schools' => ['title' => 'Ecoles et sensibilisation ON4CRD', 'description' => 'Actions pedagogiques, animations et sensibilisation radioamateur du Radio Club Durnal.'],
        'search' => ['title' => 'Recherche globale ON4CRD', 'description' => 'Recherche dans les contenus, actualites, articles, wiki et ressources ON4CRD.'],
        'settings' => ['title' => 'Preferences ON4CRD', 'description' => 'Preferences d affichage, de langue et de compte pour ON4CRD.', 'robots' => 'noindex,nofollow'],
        'sitemapxml' => ['title' => 'Sitemap ON4CRD', 'description' => 'Plan XML public du site ON4CRD.', 'robots' => 'noindex,follow'],
        'sponsoring' => ['title' => 'Sponsoring Radio Club Durnal', 'description' => 'Possibilites de sponsoring et de partenariat avec le Radio Club Durnal ON4CRD.'],
        'tools' => ['title' => 'Outils radioamateurs ON4CRD', 'description' => 'Outils radioamateurs ON4CRD : calculs, conversions, codes, bandes et ressources pratiques.'],
        'tools_geocode' => ['title' => 'Geocodage outils ON4CRD', 'description' => 'Service de geocodage utilise par les outils radioamateurs ON4CRD.', 'robots' => 'noindex,nofollow'],
        'widget_render' => ['title' => 'Rendu widget ON4CRD', 'description' => 'Endpoint technique de rendu des widgets ON4CRD.', 'robots' => 'noindex,nofollow'],
        'wiki' => ['title' => 'Wiki radioamateur ON4CRD', 'description' => 'Wiki collaboratif du Radio Club Durnal : procedures, techniques, ressources et connaissances radioamateurs.'],
        'wiki_edit' => ['title' => 'Edition wiki ON4CRD', 'description' => 'Interface d edition du wiki ON4CRD.', 'robots' => 'noindex,nofollow'],
        'wiki_view' => ['title' => 'Page wiki ON4CRD', 'description' => 'Page du wiki radioamateur collaboratif ON4CRD.'],
    ];
    foreach ([
        'admin_ads', 'admin_albums', 'admin_articles', 'admin_auctions', 'admin_classifieds', 'admin_committee',
        'admin_dashboard', 'admin_dinner_reservations', 'admin_editorial', 'admin_events', 'admin_events_feed', 'admin_library',
        'admin_live_feeds', 'admin_members', 'admin_modules', 'admin_news', 'admin_newsletters', 'admin_permissions',
        'admin_press', 'admin_translation_reviews', 'admin_wiki',
    ] as $adminRoute) {
        $routeSeo[$adminRoute] ??= [
            'title' => 'Administration ON4CRD',
            'description' => 'Interface d administration du Radio Club Durnal ON4CRD.',
            'robots' => 'noindex,nofollow',
        ];
    }
    $titleKey = $routeKey . '_title';
    $descriptionKey = $routeKey . '_description';
    $title = trim((string) ($pageMeta['title'] ?? ''));
    $description = trim((string) ($pageMeta['description'] ?? ''));

    if ($title === '') {
        $title = trim((string) ($seo[$titleKey] ?? $routeSeo[$routeKey]['title'] ?? $seo['default_title'] ?? $siteName));
    }
    if ($description === '') {
        $description = trim((string) ($seo[$descriptionKey] ?? $routeSeo[$routeKey]['description'] ?? $seo['default_description'] ?? ''));
    }

    $canonicalQuery = seo_public_current_query();
    $alternates = isset($pageMeta['alternates']) && is_array($pageMeta['alternates']) ? $pageMeta['alternates'] : [];
    foreach (supported_locales() as $supportedLocale) {
        $alternates[$supportedLocale] = route_url_with_locale($canonicalRoute, $supportedLocale, $canonicalQuery);
    }
    $alternates['x-default'] = route_url_with_locale($canonicalRoute, 'fr', $canonicalQuery);

    $defaults = array_replace([
        'title' => $title,
        'description' => $description,
        'canonical' => route_url_with_locale($canonicalRoute, $locale, $canonicalQuery),
        'locale' => str_replace('-', '_', locale_open_graph_code($locale)),
        'geo_region' => 'BE-WNA',
        'geo_placename' => (string) ($seo['geo_placename'] ?? 'Durnal, Yvoir, Namur, Belgium'),
        'geo_position' => '50.3150;4.9452',
        'icbm' => '50.3150, 4.9452',
        'latitude' => '50.3150',
        'longitude' => '4.9452',
        'schema_type' => 'WebPage',
        'alternates' => $alternates,
        'robots' => (string) ($routeSeo[$routeKey]['robots'] ?? 'index,follow'),
    ], array_filter($pageMeta, static fn($value): bool => $value !== null && $value !== ''));
    $defaults['alternates'] = $alternates;
    if (!isset($defaults['json_ld'])) {
        $defaults['json_ld'] = [
            '@context' => 'https://schema.org',
            '@type' => (string) ($defaults['schema_type'] ?? 'WebPage'),
            'name' => (string) $defaults['title'],
            'description' => (string) $defaults['description'],
            'url' => (string) $defaults['canonical'],
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => route_url_with_locale('home', $locale),
            ],
            'about' => [
                '@type' => 'Organization',
                'name' => 'Radio Club Durnal ON4CRD',
                'url' => route_url_with_locale('home', $locale),
                'location' => [
                    '@type' => 'Place',
                    'name' => 'Bocq Arena',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => 'Rue des Ecoles',
                        'postalCode' => '5530',
                        'addressLocality' => 'Purnode',
                        'addressRegion' => 'Namur',
                        'addressCountry' => 'BE',
                    ],
                    'geo' => [
                        '@type' => 'GeoCoordinates',
                        'latitude' => 50.3150,
                        'longitude' => 4.9452,
                    ],
                ],
            ],
        ];
    }

    return $defaults;
}

function locale_open_graph_code(string $locale): string
{
    return match ($locale) {
        'fr' => 'fr_BE',
        'en' => 'en_US',
        'de' => 'de_DE',
        'nl' => 'nl_BE',
        'it' => 'it_IT',
        'es' => 'es_ES',
        'pt' => 'pt_PT',
        'ar' => 'ar_AR',
        'hi' => 'hi_IN',
        'ja' => 'ja_JP',
        'zh' => 'zh_CN',
        'bn' => 'bn_BD',
        'ru' => 'ru_RU',
        'id' => 'id_ID',
        default => 'fr_BE',
    };
}

function module_css_assets_for_route(string $route): array
{
    $route = preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: 'home';
    $moduleByRoute = [
        'album' => 'albums',
        'auction_bid' => 'auctions',
        'auction_view' => 'auctions',
        'classifieds_manage' => 'classifieds',
        'event_view' => 'events',
        'news_view' => 'news',
        'wiki_edit' => 'wiki',
        'wiki_view' => 'wiki',
    ];
    $module = $moduleByRoute[$route] ?? $route;
    $assets = [];

    $candidates = [$module];
    if ($route !== $module) {
        $candidates[] = $route;
    }

    foreach (array_unique($candidates) as $candidate) {
        $path = 'assets/css/modules/' . $candidate . '.css';
        if (is_file(dirname(__DIR__) . '/' . $path)) {
            $assets[] = $path;
        }
    }

    return $assets;
}

function module_js_assets_for_route(string $route): array
{
    $route = preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: 'home';
    $moduleByRoute = [
        'event_view' => 'events',
        'save_dashboard' => 'dashboard',
        'widget_render' => 'dashboard',
        'wiki_edit' => 'wiki_edit',
    ];
    $module = $moduleByRoute[$route] ?? $route;
    $assets = [];

    $candidates = [$module];
    if ($route === 'home') {
        $candidates[] = 'tools';
    }
    if (str_starts_with($route, 'admin_') || in_array($route, ['ads', 'classifieds', 'classifieds_manage', 'wiki_edit'], true)) {
        $candidates[] = 'wysiwyg';
    }

    foreach (array_unique($candidates) as $candidate) {
        $path = 'assets/js/modules/' . $candidate . '.js';
        if (is_file(dirname(__DIR__) . '/' . $path)) {
            $assets[] = $path;
        }
    }

    return $assets;
}

function render_layout(string $content, string $title = ''): string
{
    $flashes = consume_flashes();
    $currentRoute = (string) ($_GET['route'] ?? 'home');
    $currentTheme = (string) ($_SESSION['theme'] ?? 'dark');
    if ($currentTheme !== 'dark') {
        $currentTheme = 'light';
    }
    $currentLocale = current_locale();
    if (!in_array($currentLocale, supported_locales(), true)) {
        $currentLocale = 'fr';
    }
    $layoutI18n = i18n_domain_locale('layout', $currentLocale);
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
        ['label' => (string) $layoutI18n['nav_shop'], 'route' => 'classifieds', 'module' => 'classifieds'],
        ['label' => (string) $layoutI18n['nav_events'], 'route' => 'events', 'module' => 'events'],
        ['label' => (string) $layoutI18n['nav_tools'], 'route' => 'tools', 'module' => ''],
        ['label' => (string) $layoutI18n['search_submit'], 'route' => 'search', 'module' => ''],
        ['label' => (string) $layoutI18n['nav_directory'], 'route' => 'directory', 'module' => 'directory'],
    ];
    $navMemberItems = [
        ['label' => (string) $layoutI18n['nav_dashboard'], 'route' => 'dashboard', 'module' => 'dashboard'],
        ['label' => (string) $layoutI18n['nav_wiki'], 'route' => 'wiki', 'module' => 'wiki'],
        ['label' => (string) $layoutI18n['nav_gallery'], 'route' => 'albums', 'module' => 'albums'],
        ['label' => (string) $layoutI18n['nav_articles'], 'route' => 'articles', 'module' => 'articles'],
        ['label' => (string) $layoutI18n['nav_library'], 'route' => 'members_library', 'module' => ''],
        ['label' => 'QSL', 'route' => 'qsl', 'module' => 'qsl'],
        ['label' => (string) $layoutI18n['nav_auctions'], 'route' => 'auctions', 'module' => 'auctions'],
        ['label' => (string) $layoutI18n['nav_assistant'], 'route' => 'chatbot', 'module' => 'chatbot'],
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
        $accountPrivacyLabel = (string) ($layoutI18n['account_privacy'] ?? 'Vie privée');
        $adminMenuLink = '';
        if (has_permission('admin.access')) {
            $adminMenuLink = '<hr class="account-menu-separator">'
                . '<a class="account-menu-link" href="' . e(route_url('admin')) . '">' . e((string) $layoutI18n['account_admin']) . '</a>';
        }

        $authHtml = '<details class="account-menu">'
            . '<summary class="button small account-menu-trigger">' . e($accountLabel) . '</summary>'
            . '<div class="account-menu-panel">'
            . '<a class="account-menu-link" href="' . e(route_url('profile')) . '">' . e((string) $layoutI18n['account_profile']) . '</a>'
            . '<a class="account-menu-link" href="' . e(route_url('gdpr')) . '">' . e($accountPrivacyLabel) . '</a>'
            . '<a class="account-menu-link" href="' . e(route_url('settings')) . '">' . e((string) $layoutI18n['account_settings']) . '</a>'
            . $adminMenuLink
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
    $pageMeta = localized_seo_defaults($currentRoute, $currentLocale, $pageMeta, $siteName);
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
    $metaGeoRegion = trim((string) ($pageMeta['geo_region'] ?? ''));
    $metaGeoPlacename = trim((string) ($pageMeta['geo_placename'] ?? ''));
    $metaGeoPosition = trim((string) ($pageMeta['geo_position'] ?? ''));
    $metaIcbm = trim((string) ($pageMeta['icbm'] ?? ''));
    $metaAlternates = (array) ($pageMeta['alternates'] ?? []);
    $metaImage = trim((string) ($pageMeta['image'] ?? ''));
    $metaImageAlt = trim((string) ($pageMeta['image_alt'] ?? $metaSiteName));
    $metaLatitude = trim((string) ($pageMeta['latitude'] ?? ''));
    $metaLongitude = trim((string) ($pageMeta['longitude'] ?? ''));
    $metaAiSummary = trim((string) ($pageMeta['ai_summary'] ?? $metaDescription));
    $metaCitationAuthor = trim((string) ($pageMeta['citation_author'] ?? 'Radio Club Durnal ON4CRD'));
    $metaKeywords = [];
    foreach (array_merge((array) ($pageMeta['keywords'] ?? []), (array) ($pageMeta['tags'] ?? [])) as $keyword) {
        $keyword = trim((string) $keyword);
        if ($keyword !== '') {
            $metaKeywords[$keyword] = true;
        }
    }
    $jsonLdItems = [];
    if (isset($pageMeta['json_ld'])) {
        $jsonLdItems = is_array($pageMeta['json_ld']) && array_is_list($pageMeta['json_ld'])
            ? $pageMeta['json_ld']
            : [$pageMeta['json_ld']];
    }
    $metaHead = '<meta name="description" content="' . e($metaDescription) . '">'
        . '<meta name="robots" content="' . e($metaRobots) . '">'
        . '<meta name="dcterms.title" content="' . e($pageTitle) . '">'
        . '<meta name="dcterms.description" content="' . e($metaAiSummary) . '">'
        . '<meta name="citation_title" content="' . e($pageTitle) . '">'
        . '<meta name="citation_author" content="' . e($metaCitationAuthor) . '">'
        . '<meta name="citation_abstract" content="' . e($metaAiSummary) . '">'
        . '<meta property="og:title" content="' . e($pageTitle) . '">'
        . '<meta property="og:description" content="' . e($metaDescription) . '">'
        . '<meta property="og:type" content="' . e($metaOgType) . '">'
        . '<meta property="og:locale" content="' . e($metaLocale) . '">'
        . '<meta property="og:site_name" content="' . e($metaSiteName) . '">'
        . '<meta name="twitter:card" content="' . e($metaTwitterCard) . '">'
        . '<meta name="twitter:title" content="' . e($pageTitle) . '">'
        . '<meta name="twitter:description" content="' . e($metaDescription) . '">';
    if ($metaImage !== '') {
        $metaHead .= '<meta property="og:image" content="' . e($metaImage) . '">'
            . '<meta property="og:image:alt" content="' . e($metaImageAlt) . '">'
            . '<meta name="twitter:image" content="' . e($metaImage) . '">'
            . '<meta name="twitter:image:alt" content="' . e($metaImageAlt) . '">';
    }
    if ($metaCanonical !== '') {
        $metaHead .= '<link rel="canonical" href="' . e($metaCanonical) . '">'
            . '<meta property="og:url" content="' . e($metaCanonical) . '">'
            . '<meta name="citation_public_url" content="' . e($metaCanonical) . '">';
    }
    if ($metaKeywords !== []) {
        $keywords = implode(', ', array_keys($metaKeywords));
        $metaHead .= '<meta name="keywords" content="' . e($keywords) . '">'
            . '<meta name="citation_keywords" content="' . e($keywords) . '">';
    }
    foreach ($metaAlternates as $hreflang => $href) {
        $lang = trim((string) $hreflang);
        $url = trim((string) $href);
        if ($lang === '' || $url === '') {
            continue;
        }
        $metaHead .= '<link rel="alternate" hreflang="' . e($lang) . '" href="' . e($url) . '">';
        if ($lang !== 'x-default') {
            $metaHead .= '<meta property="og:locale:alternate" content="' . e(locale_open_graph_code($lang)) . '">';
        }
    }
    if ($metaGeoRegion !== '') {
        $metaHead .= '<meta name="geo.region" content="' . e($metaGeoRegion) . '">';
    }
    if ($metaGeoPlacename !== '') {
        $metaHead .= '<meta name="geo.placename" content="' . e($metaGeoPlacename) . '">';
    }
    if ($metaGeoPosition !== '') {
        $metaHead .= '<meta name="geo.position" content="' . e($metaGeoPosition) . '">';
    }
    if ($metaIcbm !== '') {
        $metaHead .= '<meta name="ICBM" content="' . e($metaIcbm) . '">';
    }
    if ($metaLatitude !== '' && $metaLongitude !== '') {
        $metaHead .= '<meta property="place:location:latitude" content="' . e($metaLatitude) . '">'
            . '<meta property="place:location:longitude" content="' . e($metaLongitude) . '">';
    }
    if (!empty($pageMeta['published_time'])) {
        $publishedTime = trim((string) $pageMeta['published_time']);
        $metaHead .= '<meta property="article:published_time" content="' . e($publishedTime) . '">'
            . '<meta name="citation_publication_date" content="' . e($publishedTime) . '">';
    }
    if (!empty($pageMeta['modified_time'])) {
        $modifiedTime = trim((string) $pageMeta['modified_time']);
        $metaHead .= '<meta property="article:modified_time" content="' . e($modifiedTime) . '">'
            . '<meta property="og:updated_time" content="' . e($modifiedTime) . '">'
            . '<meta name="citation_online_date" content="' . e($modifiedTime) . '">';
    }
    if (!empty($pageMeta['section'])) {
        $metaHead .= '<meta property="article:section" content="' . e((string) $pageMeta['section']) . '">';
    }
    foreach ((array) ($pageMeta['tags'] ?? []) as $tag) {
        $tag = trim((string) $tag);
        if ($tag !== '') {
            $metaHead .= '<meta property="article:tag" content="' . e($tag) . '">';
        }
    }
    foreach ($jsonLdItems as $jsonLdItem) {
        if (!is_array($jsonLdItem) || $jsonLdItem === []) {
            continue;
        }
        try {
            $encodedJsonLd = json_encode($jsonLdItem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $metaHead .= '<script nonce="' . e(csp_nonce()) . '" type="application/ld+json">' . $encodedJsonLd . '</script>';
        } catch (Throwable) {
            continue;
        }
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
        'es' => ['icon' => '🇪🇸', 'label' => 'Español'],
        'it' => ['icon' => '🇮🇹', 'label' => 'Italiano'],
        'pt' => ['icon' => '🇵🇹', 'label' => 'Português'],
        'bg' => ['icon' => '🇧🇬', 'label' => 'Български'],
        'hr' => ['icon' => '🇭🇷', 'label' => 'Hrvatski'],
        'cs' => ['icon' => '🇨🇿', 'label' => 'Čeština'],
        'da' => ['icon' => '🇩🇰', 'label' => 'Dansk'],
        'et' => ['icon' => '🇪🇪', 'label' => 'Eesti'],
        'fi' => ['icon' => '🇫🇮', 'label' => 'Suomi'],
        'el' => ['icon' => '🇬🇷', 'label' => 'Ελληνικά'],
        'hu' => ['icon' => '🇭🇺', 'label' => 'Magyar'],
        'ga' => ['icon' => '🇮🇪', 'label' => 'Gaeilge'],
        'lv' => ['icon' => '🇱🇻', 'label' => 'Latviešu'],
        'lt' => ['icon' => '🇱🇹', 'label' => 'Lietuvių'],
        'mt' => ['icon' => '🇲🇹', 'label' => 'Malti'],
        'pl' => ['icon' => '🇵🇱', 'label' => 'Polski'],
        'ro' => ['icon' => '🇷🇴', 'label' => 'Română'],
        'sk' => ['icon' => '🇸🇰', 'label' => 'Slovenčina'],
        'sl' => ['icon' => '🇸🇮', 'label' => 'Slovenščina'],
        'sv' => ['icon' => '🇸🇪', 'label' => 'Svenska'],
        'ar' => ['icon' => '🇸🇦', 'label' => 'العربية'],
        'hi' => ['icon' => '🇮🇳', 'label' => 'हिन्दी'],
        'ja' => ['icon' => '🇯🇵', 'label' => '日本語'],
        'zh' => ['icon' => '🇨🇳', 'label' => '中文'],
        'bn' => ['icon' => '🇧🇩', 'label' => 'বাংলা'],
        'ru' => ['icon' => '🇷🇺', 'label' => 'Русский'],
        'id' => ['icon' => '🇮🇩', 'label' => 'Bahasa Indonesia'],
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
    $htmlDir = is_rtl_locale($currentLocale) ? 'rtl' : 'ltr';
    $moduleCssHtml = '';
    foreach (module_css_assets_for_route($currentRoute) as $moduleCssPath) {
        $moduleCssHtml .= '<link rel="stylesheet" href="' . e(asset_url($moduleCssPath)) . '">';
    }
    $moduleJsHtml = '';
    foreach (module_js_assets_for_route($currentRoute) as $moduleJsPath) {
        $moduleJsHtml .= '<script nonce="' . e($nonce) . '" src="' . e(asset_url($moduleJsPath)) . '" defer></script>';
    }

    return '<!doctype html><html lang="' . e($currentLocale) . '" dir="' . e($htmlDir) . '" class="notranslate" translate="no" data-theme="' . e($currentTheme) . '" style="--accent: ' . e($accentColor) . '; --accent-strong: ' . e($accentStrongColor) . ';"><head><meta charset="utf-8"><meta name="google" content="notranslate"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
        . e($pageTitle)
        . '</title>' . $metaHead
        . '<meta name="theme-color" content="#2f6fed">'
        . '<link rel="manifest" href="' . e(asset_url('manifest.webmanifest')) . '">'
        . '<link rel="alternate" type="text/plain" title="LLM context" href="' . e(base_url('llms.txt')) . '">'
        . '<link rel="alternate" type="application/json" title="AI content index" href="' . e(base_url('ai-index.json')) . '">'
        . '<link rel="alternate" type="application/ld+json" title="ON4CRD knowledge graph" href="' . e(base_url('knowledge-graph.jsonld')) . '">'
        . '<link rel="icon" href="' . e(asset_url('assets/icons/icon.svg')) . '" type="image/svg+xml">'
        . '<link rel="apple-touch-icon" href="' . e(asset_url('assets/icons/apple-touch-icon.png')) . '">'
        . '<link rel="stylesheet" href="' . e(asset_url('assets/css/app.css')) . '">'
        . $moduleCssHtml
        . '<script nonce="' . e($nonce) . '" src="https://cdn.tailwindcss.com"></script>'
        . '<script nonce="' . e($nonce) . '">tailwind.config={theme:{extend:{colors:{club:{900:"#0f172a",700:"#1d4ed8",500:"#3b82f6",100:"#dbeafe"}}}}};</script>'
        . '</head><body data-route="' . e($currentRoute) . '" data-sw-url="' . e(base_url('sw.js')) . '">'
        . '<a class="skip-link" href="#main-content">' . e((string) ($layoutI18n['skip_to_content'] ?? 'Skip to content')) . '</a>'
        . '<header class="topbar"><div class="brand-wrap"><div class="brand-mark"><img class="brand-mark-img" src="' . e(asset_url('assets/logo/LOGO-CRD-HALO-2020.png')) . '" alt="Logo ON4CRD"></div><a class="brand" href="' . e(route_url('home')) . '">'
        . '<span class="brand-title">ON4CRD.be</span><span class="brand-subtitle">Club Radio Durnal</span></a></div>'
        . '<button class="menu-toggle button secondary" type="button" aria-controls="main-nav" aria-expanded="false"><span aria-hidden="true">☰</span><span class="menu-label">Menu</span></button>'
        . '<button class="nav-backdrop" type="button" aria-label="' . e((string) ($layoutI18n['close_menu'] ?? 'Close menu')) . '" hidden></button>'
        . '<nav id="main-nav" class="nav" aria-label="' . e((string) ($layoutI18n['main_navigation'] ?? 'Main navigation')) . '">' . $navHtml . '<div class="nav-mobile-tools">' . $menuToolsHtml . '</div></nav>'
        . '<div class="toolbar">' . $menuToolsHtml . '</div></header>'
        . '<main id="main-content" class="layout container py-6">' . $flashHtml . $content . '</main>'
        . render_site_footer($currentRoute)
        . '<script nonce="' . e($nonce) . '" src="' . e(asset_url('assets/js/app.js')) . '" defer></script>'
        . $moduleJsHtml
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

if (!function_exists('article_import_text_to_html')) {
function article_import_text_to_html(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
    if ($text === '') {
        return '';
    }

    $lines = explode("\n", $text);
    $html = [];
    $paragraph = [];
    $listType = null;

    $flushParagraph = static function () use (&$html, &$paragraph): void {
        $content = trim(implode(' ', $paragraph));
        $paragraph = [];
        if ($content !== '') {
            $html[] = '<p>' . e($content) . '</p>';
        }
    };
    $closeList = static function () use (&$html, &$listType): void {
        if ($listType !== null) {
            $html[] = '</' . $listType . '>';
            $listType = null;
        }
    };

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            $flushParagraph();
            $closeList();
            continue;
        }

        if (preg_match('/^#{1,6}\s+(.+)$/u', $line, $matches)) {
            $flushParagraph();
            $closeList();
            $level = min(4, max(2, strspn($line, '#') + 1));
            $html[] = '<h' . $level . '>' . e(trim($matches[1])) . '</h' . $level . '>';
            continue;
        }

        if (preg_match('/^(?:[-*•])\s+(.+)$/u', $line, $matches)) {
            $flushParagraph();
            if ($listType !== 'ul') {
                $closeList();
                $listType = 'ul';
                $html[] = '<ul>';
            }
            $html[] = '<li>' . e(trim($matches[1])) . '</li>';
            continue;
        }

        if (preg_match('/^\d+[\.)]\s+(.+)$/u', $line, $matches)) {
            $flushParagraph();
            if ($listType !== 'ol') {
                $closeList();
                $listType = 'ol';
                $html[] = '<ol>';
            }
            $html[] = '<li>' . e(trim($matches[1])) . '</li>';
            continue;
        }

        $closeList();
        $paragraph[] = $line;
    }

    $flushParagraph();
    $closeList();

    return sanitize_rich_html(implode("\n", $html));
}
}

if (!function_exists('article_extract_docx_html')) {
function article_extract_docx_html(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    $xml = '';
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($path) === true) {
            $documentXml = $zip->getFromName('word/document.xml');
            $zip->close();
            if (is_string($documentXml)) {
                $xml = $documentXml;
            }
        }
    } else {
        $unzip = article_find_binary('unzip');
        if ($unzip !== '') {
            $output = @shell_exec(escapeshellarg($unzip) . ' -p ' . escapeshellarg($path) . ' word/document.xml');
            if (is_string($output)) {
                $xml = $output;
            }
        }
    }

    if ($xml === '') {
        return '';
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);
    if (!$loaded) {
        return article_import_text_to_html(strip_tags($xml));
    }

    $xpath = new DOMXPath($dom);
    $paragraphs = $xpath->query('//*[local-name()="p"]');
    if (!$paragraphs instanceof DOMNodeList) {
        return '';
    }

    $html = [];
    $listOpen = false;
    foreach ($paragraphs as $paragraph) {
        if (!$paragraph instanceof DOMElement) {
            continue;
        }

        $text = '';
        $runs = $xpath->query('.//*[local-name()="t" or local-name()="tab" or local-name()="br"]', $paragraph);
        if ($runs instanceof DOMNodeList) {
            foreach ($runs as $run) {
                if (!$run instanceof DOMNode) {
                    continue;
                }
                $localName = $run->localName;
                if ($localName === 'tab') {
                    $text .= ' ';
                } elseif ($localName === 'br') {
                    $text .= "\n";
                } else {
                    $text .= $run->textContent;
                }
            }
        }

        $text = trim((string) preg_replace('/[ \t]+/u', ' ', $text));
        if ($text === '') {
            if ($listOpen) {
                $html[] = '</ul>';
                $listOpen = false;
            }
            continue;
        }

        $style = '';
        $styleNodes = $xpath->query('.//*[local-name()="pStyle"]', $paragraph);
        $styleNode = $styleNodes instanceof DOMNodeList ? $styleNodes->item(0) : null;
        if ($styleNode instanceof DOMElement) {
            $style = strtolower($styleNode->getAttribute('w:val') ?: $styleNode->getAttribute('val'));
        }
        $numNodes = $xpath->query('.//*[local-name()="numPr"]', $paragraph);
        $isList = $numNodes instanceof DOMNodeList && $numNodes->length > 0;

        if ($isList) {
            if (!$listOpen) {
                $html[] = '<ul>';
                $listOpen = true;
            }
            $html[] = '<li>' . e($text) . '</li>';
            continue;
        }

        if ($listOpen) {
            $html[] = '</ul>';
            $listOpen = false;
        }

        if (str_contains($style, 'heading') || str_contains($style, 'titre')) {
            $level = preg_match('/([1-6])/', $style, $matches) ? (int) $matches[1] + 1 : 2;
            $level = min(4, max(2, $level));
            $html[] = '<h' . $level . '>' . e($text) . '</h' . $level . '>';
        } else {
            $html[] = '<p>' . nl2br(e($text)) . '</p>';
        }
    }

    if ($listOpen) {
        $html[] = '</ul>';
    }

    return sanitize_rich_html(implode("\n", $html));
}
}

if (!function_exists('article_find_binary')) {
function article_find_binary(string $binary): string
{
    $binary = trim($binary);
    if ($binary === '' || preg_match('/[^a-z0-9_.-]/i', $binary)) {
        return '';
    }

    $command = PHP_OS_FAMILY === 'Windows'
        ? 'where ' . escapeshellarg($binary) . ' 2>NUL'
        : 'command -v ' . escapeshellarg($binary) . ' 2>/dev/null';
    $output = @shell_exec($command);
    if (!is_string($output) || trim($output) === '') {
        return '';
    }

    $lines = preg_split('/\R/u', trim($output)) ?: [];
    foreach ($lines as $line) {
        $candidate = trim($line);
        if ($candidate !== '' && (is_file($candidate) || PHP_OS_FAMILY !== 'Windows')) {
            return $candidate;
        }
    }

    return '';
}
}

if (!function_exists('article_extract_pdf_text')) {
function article_extract_pdf_text(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    $binary = article_find_binary('pdftotext');
    if ($binary === '') {
        return '';
    }

    $command = escapeshellarg($binary) . ' -layout -enc UTF-8 ' . escapeshellarg($path) . ' -';
    $output = @shell_exec($command);
    if (!is_string($output) || trim($output) === '') {
        return '';
    }

    return trim((string) preg_replace('/[ \t]+/u', ' ', $output));
}
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
    $trimmed = trim($value);
    if (preg_match('/^(\d{1,2})\D+(\d{1,2})(?:\D+\d{1,2})?$/', $trimmed, $matches) === 1) {
        $hours = max(0, min(23, (int) $matches[1]));
        $minutes = max(0, min(59, (int) $matches[2]));
        return str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT);
    }

    $digits = preg_replace('/[^0-9]/', '', $trimmed) ?? '';
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
     * @return list<string>
     */
    function rag_tokens(string $text): array
    {
        $normalized = mb_safe_strtolower(trim($text));
        if ($normalized === '') {
            return [];
        }
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $normalized) ?: [];
        $stopwords = [
            'fr' => ['le','la','les','de','des','du','un','une','et','ou','pour','avec','dans','sur','est','sont','au','aux','ce','cette','ces'],
            'en' => ['the','a','an','and','or','for','with','in','on','is','are','to','of','from','that','this','these'],
            'de' => ['der','die','das','ein','eine','und','oder','mit','im','in','auf','ist','sind','zu','von','für','den','dem'],
            'nl' => ['de','het','een','en','of','met','in','op','is','zijn','voor','van','naar','dat','dit','deze'],
        ];
        $localeStops = $stopwords[current_locale()] ?? $stopwords['fr'];
        $globalStops = ['de'];
        $tokens = [];
        foreach ($parts as $part) {
            $token = trim((string) $part);
            if ($token === '' || mb_strlen($token) < 2) {
                continue;
            }
            if (in_array($token, $localeStops, true) || in_array($token, $globalStops, true)) {
                continue;
            }
            $tokens[$token] = true;
        }
        return array_keys($tokens);
    }

    /**
     * @param list<string> $queryTokens
     */
    function rag_overlap_score(array $queryTokens, string $text): float
    {
        if ($queryTokens === []) {
            return 0.0;
        }
        $haystack = ' ' . mb_safe_strtolower($text) . ' ';
        $score = 0.0;
        foreach ($queryTokens as $token) {
            if (str_contains($haystack, ' ' . $token . ' ')) {
                $score += 1.0;
            } elseif (str_contains($haystack, $token)) {
                $score += 0.5;
            }
        }
        return $score;
    }

    /**
     * @param list<string> $queryTokens
     */


    /**
     * @param list<string> $queryTokens
     */
    function rag_query_coverage(array $queryTokens, string $text): float
    {
        if ($queryTokens === []) {
            return 0.0;
        }
        $normalizedText = ' ' . mb_safe_strtolower($text) . ' ';
        $matched = 0;
        foreach ($queryTokens as $token) {
            if (str_contains($normalizedText, ' ' . $token . ' ') || str_contains($normalizedText, $token)) {
                $matched++;
            }
        }
        return $matched / max(1, count($queryTokens));
    }

    function rag_weighted_score(array $queryTokens, string $text): float
    {
        if ($queryTokens === []) {
            return 0.0;
        }

        $normalizedText = mb_safe_strtolower($text);
        if (trim($normalizedText) === '') {
            return 0.0;
        }

        $score = 0.0;
        foreach ($queryTokens as $token) {
            $quoted = preg_quote($token, '/');
            $wholeWordMatches = preg_match_all('/(?<![\p{L}\p{N}])' . $quoted . '(?![\p{L}\p{N}])/u', $normalizedText);
            if (is_int($wholeWordMatches) && $wholeWordMatches > 0) {
                $score += 1.5 + min(1.5, ($wholeWordMatches - 1) * 0.3);
                continue;
            }

            if (str_contains($normalizedText, $token)) {
                $score += 0.4;
            }
        }

        if (count($queryTokens) >= 2) {
            $phrase = implode(' ', $queryTokens);
            if ($phrase !== '' && str_contains($normalizedText, $phrase)) {
                $score += 2.0;
            }
        }

        return $score;
    }



    function ensure_rag_chunks_table(): bool
    {
        try {
            db()->exec('CREATE TABLE IF NOT EXISTS rag_chunks (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                source_type VARCHAR(32) NOT NULL,
                source_key VARCHAR(191) NOT NULL,
                title VARCHAR(255) NOT NULL,
                body MEDIUMTEXT NOT NULL,
                url VARCHAR(255) DEFAULT NULL,
                embedding_json MEDIUMTEXT NOT NULL,
                embedding_provider VARCHAR(48) NOT NULL DEFAULT "llphant",
                embedding_model VARCHAR(128) NOT NULL DEFAULT "",
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_source (source_type, source_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            try { db()->exec('ALTER TABLE rag_chunks ADD COLUMN embedding_provider VARCHAR(48) NOT NULL DEFAULT "llphant"'); } catch (Throwable) { /* column may already exist */ }
            try { db()->exec('ALTER TABLE rag_chunks ADD COLUMN embedding_model VARCHAR(128) NOT NULL DEFAULT ""'); } catch (Throwable) { /* column may already exist */ }
            try { db()->exec('CREATE INDEX idx_rag_chunks_updated_at ON rag_chunks (updated_at)'); } catch (Throwable) { /* index may already exist */ }
            try { db()->exec('CREATE INDEX idx_rag_chunks_source_type_updated_at ON rag_chunks (source_type, updated_at)'); } catch (Throwable) { /* index may already exist */ }
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return list<string> */
    function rag_chunks_from_text(string $text, int $maxChars = 600, int $overlap = 120): array
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
        if ($plain === '') { return []; }
        $sentences = preg_split('/(?<=[\.\!\?])\s+/u', $plain) ?: [];
        if ($sentences === []) {
            $sentences = [$plain];
        }
        $chunks = [];
        $seen = [];
        $buffer = '';
        foreach ($sentences as $sentence) {
            $sentence = trim((string) $sentence);
            if ($sentence === '') {
                continue;
            }
            $candidate = trim($buffer . ' ' . $sentence);
            if (mb_strlen($candidate) <= $maxChars) {
                $buffer = $candidate;
                continue;
            }
            if ($buffer !== '') {
                $normalized = mb_safe_strtolower($buffer);
                if (!isset($seen[$normalized])) {
                    $seen[$normalized] = true;
                    $chunks[] = $buffer;
                }
                $tail = mb_substr($buffer, max(0, mb_strlen($buffer) - $overlap));
                $buffer = trim($tail . ' ' . $sentence);
            } else {
                $buffer = mb_substr($sentence, 0, $maxChars);
            }
            if (count($chunks) >= 12) { break; }
        }
        if ($buffer !== '' && count($chunks) < 12) {
            $normalized = mb_safe_strtolower($buffer);
            if (!isset($seen[$normalized])) {
                $chunks[] = $buffer;
            }
        }
        if (count($chunks) > 1) {
            $chunks = array_values(array_filter($chunks, static fn (string $chunk): bool => mb_strlen(trim($chunk)) >= 40));
        }
        return $chunks;
    }

    function rag_library_document_body(array $doc): string
    {
        $description = trim((string) ($doc['description'] ?? ''));
        $extracted = trim((string) ($doc['extracted_text'] ?? ''));
        $parts = [];
        if ($description !== '') {
            $parts[] = $description;
        }
        if ($extracted !== '') {
            $parts[] = $extracted;
        }

        $filePath = trim((string) ($doc['file_path'] ?? ''));
        if ($extracted === '' && $filePath !== '') {
            $absPath = storage_path($filePath);
            $ext = mb_safe_strtolower((string) pathinfo($absPath, PATHINFO_EXTENSION));
            $allowed = ['txt', 'md', 'csv', 'json', 'log', 'xml', 'html', 'htm', 'docx', 'pdf'];
            if (is_file($absPath) && in_array($ext, $allowed, true)) {
                $raw = '';
                if ($ext === 'docx') {
                    $raw = rag_extract_docx_text($absPath);
                } elseif ($ext === 'pdf') {
                    $raw = rag_extract_pdf_text($absPath);
                } else {
                    $fileRaw = @file_get_contents($absPath);
                    if (is_string($fileRaw)) {
                        $raw = $fileRaw;
                    }
                }
                if ($raw !== '') {
                    if ($ext === 'html' || $ext === 'htm') {
                        $raw = strip_tags($raw);
                    }
                    $parts[] = trim((string) preg_replace('/\s+/u', ' ', $raw));
                }
            }
        }

        return trim(implode("\n", array_filter($parts, static fn ($v): bool => is_string($v) && $v !== '')));
    }

    function rag_extract_docx_text(string $path): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (!is_string($xml) || $xml === '') {
            return '';
        }
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($xml)));
    }

    function rag_extract_pdf_text(string $path): string
    {
        $binary = trim((string) @shell_exec('command -v pdftotext 2>/dev/null'));
        if ($binary === '') {
            return '';
        }
        $cmd = $binary . ' -layout ' . escapeshellarg($path) . ' - 2>/dev/null';
        $output = @shell_exec($cmd);
        if (!is_string($output) || trim($output) === '') {
            return '';
        }
        return trim((string) preg_replace('/\s+/u', ' ', $output));
    }

    /** @return list<float> */
    function rag_embedding_with_llphant(string $text): array
    {
        if (!class_exists('\\LLPhant\\Embeddings\\EmbeddingGenerator\\EmbeddingGeneratorInterface')) {
            return [];
        }

        try {
            // Preferred integration: app-level LLPhant adapter returning an embedding generator instance.
            if (function_exists('llphant_embedding_generator')) {
                $generator = llphant_embedding_generator();
                if (is_object($generator) && method_exists($generator, 'embedQuery')) {
                    $embedding = $generator->embedQuery($text);
                    if (is_array($embedding)) {
                        return array_values(array_map('floatval', array_filter($embedding, 'is_numeric')));
                    }
                }
            }

            // Backward-compatible integration hook kept for legacy projects.
            if (function_exists('llphant_embedding_vector')) {
                $vector = llphant_embedding_vector($text);
                if (is_array($vector)) {
                    return array_values(array_map('floatval', array_filter($vector, 'is_numeric')));
                }
            }
        } catch (Throwable) {
            return [];
        }

        return [];
    }

    function rag_llphant_is_ready(): bool
    {
        if (!class_exists('\\LLPhant\\Embeddings\\EmbeddingGenerator\\EmbeddingGeneratorInterface')) {
            return false;
        }
        try {
            if (function_exists('llphant_embedding_generator')) {
                $generator = llphant_embedding_generator();
                if (is_object($generator) && method_exists($generator, 'embedQuery')) {
                    return true;
                }
            }
            if (function_exists('llphant_embedding_vector')) {
                $probe = llphant_embedding_vector('ping');
                if (is_array($probe) && $probe !== []) {
                    return true;
                }
            }
        } catch (Throwable) {
            return false;
        }
        return false;
    }

    function rag_llphant_model_name(): string
    {
        $model = trim((string) env('RAG_LLPHANT_EMBEDDING_MODEL', ''));
        return $model;
    }

    /** @return list<float> */
    function rag_embedding_vector(string $text, int $dim = 96): array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return array_fill(0, $dim, 0.0);
        }

        // RAG embeddings are now fully LLPhant-based.
        $providerVector = rag_embedding_with_llphant($trimmed);
        if ($providerVector !== []) {
            return $providerVector;
        }

        return [];
    }

    /** @param list<float> $a @param list<float> $b */
    function rag_cosine_similarity(array $a, array $b): float
    {
        $size = min(count($a), count($b));
        if ($size === 0) { return 0.0; }
        $dot = 0.0;
        for ($i = 0; $i < $size; $i++) { $dot += ((float) $a[$i]) * ((float) $b[$i]); }
        return $dot;
    }

    /** @return list<float> */
    function rag_decode_embedding(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) { return []; }
        $vector = [];
        foreach ($decoded as $value) {
            if (is_numeric($value)) {
                $vector[] = (float) $value;
            }
        }
        return $vector;
    }

    /** @return list<string> */
    function rag_query_variants(string $normalized, array $queryTokens): array
    {
        $variants = [];
        $base = trim($normalized);
        if ($base !== '') {
            $variants[] = $base;
        }
        $tokenOnly = trim(implode(' ', array_slice($queryTokens, 0, 8)));
        if ($tokenOnly !== '' && !in_array($tokenOnly, $variants, true)) {
            $variants[] = $tokenOnly;
        }
        if (count($queryTokens) >= 3) {
            $focus = trim(implode(' ', array_slice($queryTokens, 0, 3)));
            if ($focus !== '' && !in_array($focus, $variants, true)) {
                $variants[] = $focus;
            }
        }
        return array_slice($variants, 0, 3);
    }

    /** @return list<string> */
    function rag_infer_source_types(string $normalized): array
    {
        $q = mb_safe_strtolower($normalized);
        $types = [];
        if (preg_match('/\b(article|blog|news|actualité|actu)\b/u', $q)) {
            $types[] = 'article';
        }
        if (preg_match('/\b(document|pdf|library|bibliothèque|doc)\b/u', $q)) {
            $types[] = 'library';
        }
        if (preg_match('/\b(knowledge|base|faq|guide|tutoriel)\b/u', $q)) {
            $types[] = 'knowledge';
        }
        return array_values(array_unique($types));
    }



    /** @return list<array{variant:string,source_types:list<string>,token_hints:list<string>,limit:int}> */
    function rag_agentic_plan(string $normalized, array $queryTokens, array $variants, array $preferredSourceTypes): array
    {
        $plan = [];
        foreach ($variants as $idx => $variant) {
            $variantTokens = rag_tokens($variant);
            $tokenHints = array_slice($variantTokens, 0, $idx === 0 ? 3 : 2);
            if ($tokenHints === []) { continue; }
            $plan[] = [
                'variant' => $variant,
                'source_types' => $preferredSourceTypes,
                'token_hints' => $tokenHints,
                'limit' => $idx === 0 ? 80 : 60,
            ];
        }
        if ($plan === []) {
            $tokenHints = array_slice($queryTokens, 0, 2);
            if ($tokenHints !== []) {
                $plan[] = [
                    'variant' => $normalized,
                    'source_types' => $preferredSourceTypes,
                    'token_hints' => $tokenHints,
                    'limit' => 70,
                ];
            }
        }
        if ($preferredSourceTypes !== []) {
            $plan[] = [
                'variant' => $normalized,
                'source_types' => [],
                'token_hints' => array_slice($queryTokens, 0, 2),
                'limit' => 50,
            ];
        }
        return $plan;
    }

    function rag_agentic_confidence(float $score, float $coverage, float $margin): float
    {
        $scorePart = max(0.0, min(1.0, ($score - 1.2) / 2.2));
        $coveragePart = max(0.0, min(1.0, $coverage));
        $marginPart = max(0.0, min(1.0, $margin / 0.35));
        return ($scorePart * 0.5) + ($coveragePart * 0.35) + ($marginPart * 0.15);
    }


    function rag_source_group_key(string $sourceType, string $sourceKey, string $title): string
    {
        if ($sourceKey !== '') {
            $base = preg_replace('/_\d+$/', '', $sourceKey);
            if (is_string($base) && $base !== '') {
                return $sourceType . '|' . $base;
            }
            return $sourceType . '|' . $sourceKey;
        }
        return $sourceType . '|' . trim(mb_safe_strtolower($title));
    }



    /** @param array<int,array<string,mixed>> $rows @return array<string,float> */
    function rag_agentic_source_vote(array $rows, array $queryTokens): array
    {
        $votes = [];
        foreach ($rows as $row) {
            if (!is_array($row)) { continue; }
            $sourceType = (string) ($row['source_type'] ?? '');
            if ($sourceType === '') { continue; }
            $title = (string) ($row['title'] ?? '');
            $body = (string) ($row['body'] ?? '');
            $combined = $title . ' ' . $body;
            $coverage = rag_query_coverage($queryTokens, $combined);
            $lexical = rag_weighted_score($queryTokens, $title) + (rag_weighted_score($queryTokens, $body) * 0.6);
            $votes[$sourceType] = ($votes[$sourceType] ?? 0.0) + ($coverage * 1.5) + $lexical;
        }
        return $votes;
    }



    function rag_agentic_answer_is_valid(string $normalizedQuestion, string $summary, float $coverage, float $confidence): bool
    {
        $summaryNorm = mb_safe_strtolower(trim($summary));
        $questionNorm = mb_safe_strtolower(trim($normalizedQuestion));
        if ($summaryNorm === '') { return false; }
        if (mb_strlen($summaryNorm) < 48) { return false; }
        if ($confidence < 0.5 || $coverage < 0.2) { return false; }
        if ($questionNorm !== '' && ($summaryNorm === $questionNorm || mb_strpos($summaryNorm, $questionNorm) !== false) && mb_strlen($summaryNorm) < 220) {
            return false;
        }
        return true;
    }

    function rag_chunks_are_stale(int $maxAgeSeconds = 86400): bool
    {
        try {
            $stmt = db()->query('SELECT UNIX_TIMESTAMP(MAX(updated_at)) FROM rag_chunks');
            $ts = (int) ($stmt ? $stmt->fetchColumn() : 0);
            if ($ts <= 0) {
                return true;
            }
            return (time() - $ts) > $maxAgeSeconds;
        } catch (Throwable) {
            return true;
        }
    }

    function rag_chunks_need_embedding_refresh(string $provider, string $model): bool
    {
        try {
            $stmt = db()->prepare('SELECT COUNT(*) FROM rag_chunks WHERE embedding_provider = ? AND embedding_model = ?');
            $stmt->execute([$provider, $model]);
            $matching = (int) ($stmt->fetchColumn() ?: 0);
            $totalStmt = db()->query('SELECT COUNT(*) FROM rag_chunks');
            $total = (int) ($totalStmt ? $totalStmt->fetchColumn() : 0);
            if ($total <= 0) {
                return true;
            }
            return $matching !== $total;
        } catch (Throwable) {
            return false;
        }
    }

    function rag_reindex_lock_file(): string
    {
        return cache_dir_path() . '/rag-reindex.lock';
    }

    function rag_can_reindex_now(int $cooldownSeconds = 900): bool
    {
        $file = rag_reindex_lock_file();
        $now = time();
        if (!is_file($file)) {
            @file_put_contents($file, (string) $now);
            return true;
        }
        $last = (int) @file_get_contents($file);
        if (($now - $last) < $cooldownSeconds) {
            return false;
        }
        @file_put_contents($file, (string) $now);
        return true;
    }

    /**
     * @param list<string> $preferredSourceTypes
     * @return array{answer:string,source:string,sources?:array<int,array{title:string,url:string,type:string}>,confidence?:float,freshness_hours?:float|null}
     */
function answer_question_from_knowledge(string $question, array $preferredSourceTypes = []): array
{
        $locale = current_locale();
        $chatbotI18n = [
            'fr' => [
                'empty_question' => 'Je n’ai pas reçu de question exploitable.',
                'no_precise_yet' => 'Je n’ai pas de réponse précise pour le moment.',
                'article_found' => 'J’ai trouvé un article pertinent : ',
                'summary' => 'Résumé : ',
                'link' => 'Lien : ',
                'articles_source' => 'Articles ON4CRD',
                'knowledge_source' => 'Base de connaissances ON4CRD',
                'article_label' => 'Article',
                'no_answer' => 'Je n’ai pas de réponse précise pour cette question. Essayez de mentionner un mot-clé (QSL, antenne, propagation, licence) ou consultez le module Articles.',
                'assistant_source' => 'Assistant Raymond',
            ],
            'en' => [
                'empty_question' => 'I did not receive a usable question.',
                'no_precise_yet' => 'I do not have a precise answer right now.',
                'article_found' => 'I found a relevant article: ',
                'summary' => 'Summary: ',
                'link' => 'Link: ',
                'articles_source' => 'ON4CRD articles',
                'knowledge_source' => 'ON4CRD knowledge base',
                'article_label' => 'Article',
                'no_answer' => 'I do not have a precise answer for this question. Try adding a keyword (QSL, antenna, propagation, license) or browse the Articles module.',
                'assistant_source' => 'Raymond assistant',
            ],
            'de' => [
                'empty_question' => 'Ich habe keine verwertbare Frage erhalten.',
                'no_precise_yet' => 'Ich habe im Moment keine genaue Antwort.',
                'article_found' => 'Ich habe einen relevanten Artikel gefunden: ',
                'summary' => 'Zusammenfassung: ',
                'link' => 'Link: ',
                'articles_source' => 'ON4CRD-Artikel',
                'knowledge_source' => 'ON4CRD-Wissensdatenbank',
                'article_label' => 'Artikel',
                'no_answer' => 'Ich habe keine genaue Antwort auf diese Frage. Versuchen Sie ein Schlüsselwort (QSL, Antenne, Ausbreitung, Lizenz) oder nutzen Sie das Artikel-Modul.',
                'assistant_source' => 'Assistent Raymond',
            ],
            'nl' => [
                'empty_question' => 'Ik heb geen bruikbare vraag ontvangen.',
                'no_precise_yet' => 'Ik heb momenteel geen exact antwoord.',
                'article_found' => 'Ik heb een relevant artikel gevonden: ',
                'summary' => 'Samenvatting: ',
                'link' => 'Link: ',
                'articles_source' => 'ON4CRD-artikels',
                'knowledge_source' => 'ON4CRD-kennisbank',
                'article_label' => 'Artikel',
                'no_answer' => 'Ik heb geen exact antwoord op deze vraag. Probeer een trefwoord (QSL, antenne, propagatie, licentie) of bekijk de Artikels-module.',
                'assistant_source' => 'Raymond-assistent',
            ],
        ];
        $chatbotT = $chatbotI18n[$locale] ?? $chatbotI18n['fr'];
        $normalized = mb_safe_strtolower(trim($question));
        if ($normalized === '') {
            return ['answer' => (string) $chatbotT['empty_question'], 'source' => (string) $chatbotT['assistant_source']];
        }

        $queryTokens = rag_tokens($normalized);
        if ($queryTokens === [] && mb_strlen($normalized) < 3) {
            return [
                'answer' => (string) $chatbotT['no_answer'],
                'source' => (string) $chatbotT['assistant_source'],
            ];
        }

        if (ensure_rag_chunks_table()) {
            try {
                $countStmt = db()->query('SELECT COUNT(*) FROM rag_chunks');
                $chunkCount = (int) ($countStmt ? $countStmt->fetchColumn() : 0);
                $embeddingProvider = 'llphant';
                $embeddingModel = rag_llphant_model_name();
                $mustReindex = $chunkCount === 0
                    || rag_chunks_are_stale(43200)
                    || rag_chunks_need_embedding_refresh($embeddingProvider, $embeddingModel);
                $llphantReady = rag_llphant_is_ready();
                if ($llphantReady && $mustReindex && rag_can_reindex_now(900)) {
                    db()->beginTransaction();
                    try {
                        db()->exec('DELETE FROM rag_chunks');
                        $insert = db()->prepare('INSERT INTO rag_chunks (source_type, source_key, title, body, url, embedding_json, embedding_provider, embedding_model) VALUES (?,?,?,?,?,?,?,?)');
                        $knowledgePath = __DIR__ . '/knowledge.php';
                        $knowledgeBase = is_file($knowledgePath) ? (require $knowledgePath) : [];
                        if (is_array($knowledgeBase)) {
                            foreach ($knowledgeBase as $idx => $item) {
                                if (!is_array($item)) { continue; }
                                $title = trim((string) ($item['title'] ?? 'Knowledge'));
                                $body = trim((string) ($item['body'] ?? ''));
                                foreach (rag_chunks_from_text($body) as $chunkIndex => $chunk) {
                                    $key = 'kb_' . (string) $idx . '_' . (string) $chunkIndex;
                                    $vec = rag_embedding_vector($title . ' ' . $chunk);
                                    $insert->execute(['knowledge', $key, $title, $chunk, (string) ($item['url'] ?? ''), json_encode($vec), $embeddingProvider, $embeddingModel]);
                                }
                            }
                        }
                        if (table_exists('articles')) {
                            $rows = db()->query('SELECT slug,title,excerpt,content FROM articles WHERE status = "published" ORDER BY updated_at DESC LIMIT 120')->fetchAll() ?: [];
                            foreach ($rows as $row) {
                                if (!is_array($row)) { continue; }
                                $slug = trim((string) ($row['slug'] ?? ''));
                                if ($slug === '') { continue; }
                                $title = trim((string) ($row['title'] ?? 'Article'));
                                $body = trim((string) (($row['excerpt'] ?? '') . "\n" . ($row['content'] ?? '')));
                                foreach (rag_chunks_from_text($body) as $chunkIndex => $chunk) {
                                    $key = 'article_' . $slug . '_' . (string) $chunkIndex;
                                    $vec = rag_embedding_vector($title . ' ' . $chunk);
                                    $insert->execute(['article', $key, $title, $chunk, route_url('article', ['slug' => $slug]), json_encode($vec), $embeddingProvider, $embeddingModel]);
                                }
                            }
                        }

                        if (ensure_member_library_table()) {
                            $docs = db()->query('SELECT id,title,description,extracted_text,file_path FROM member_library_documents ORDER BY uploaded_at DESC LIMIT 120')->fetchAll() ?: [];
                            foreach ($docs as $doc) {
                                if (!is_array($doc)) { continue; }
                                $docId = (int) ($doc['id'] ?? 0);
                                if ($docId <= 0) { continue; }
                                $title = trim((string) ($doc['title'] ?? 'Document'));
                                $body = rag_library_document_body($doc);
                                if ($body === '') { continue; }
                                $safePath = safe_storage_public_path_or_null((string) ($doc['file_path'] ?? ''), ['storage/uploads/library/']) ?? '';
                                $url = $safePath !== '' ? base_url($safePath) : '';
                                foreach (rag_chunks_from_text($body) as $chunkIndex => $chunk) {
                                    $key = 'doc_' . (string) $docId . '_' . (string) $chunkIndex;
                                    $vec = rag_embedding_vector($title . ' ' . $chunk);
                                    $insert->execute(['library', $key, $title, $chunk, $url, json_encode($vec), $embeddingProvider, $embeddingModel]);
                                }
                            }
                        }
                        db()->commit();
                    } catch (Throwable $e) {
                        if (db()->inTransaction()) {
                            db()->rollBack();
                        }
                        throw $e;
                    }
                }

                $rows = [];
                $variants = rag_query_variants($normalized, $queryTokens);
                $inferredSourceTypes = rag_infer_source_types($normalized);
                $preferredSourceTypes = array_values(array_unique(array_filter(array_merge($inferredSourceTypes, $preferredSourceTypes), static fn($v): bool => is_string($v) && $v !== '')));
                $planSteps = rag_agentic_plan($normalized, $queryTokens, $variants, $preferredSourceTypes);
                $planTrace = [];
                foreach ($planSteps as $step) {
                    if (!is_array($step)) { continue; }
                    $tokenHints = isset($step['token_hints']) && is_array($step['token_hints']) ? $step['token_hints'] : [];
                    if ($tokenHints === []) { continue; }
                    $whereParts = [];
                    $params = [];
                    foreach ($tokenHints as $hint) {
                        $whereParts[] = '(title LIKE ? OR body LIKE ?)';
                        $like = '%' . $hint . '%';
                        $params[] = $like;
                        $params[] = $like;
                    }
                    $sql = 'SELECT source_type, source_key, title, body, url, embedding_json, updated_at FROM rag_chunks WHERE ' . implode(' OR ', $whereParts);
                    $stepSourceTypes = isset($step['source_types']) && is_array($step['source_types']) ? $step['source_types'] : [];
                    if ($stepSourceTypes !== []) {
                        $typePlaceholders = implode(',', array_fill(0, count($stepSourceTypes), '?'));
                        $sql .= ' AND source_type IN (' . $typePlaceholders . ')';
                        foreach ($stepSourceTypes as $type) { $params[] = $type; }
                    }
                    $stepLimit = max(20, min(120, (int) ($step['limit'] ?? 80)));
                    $sql .= ' ORDER BY updated_at DESC LIMIT ' . $stepLimit;
                    $stmt = db()->prepare($sql);
                    $stmt->execute($params);
                    $fetched = $stmt->fetchAll() ?: [];
                    $planTrace[] = [
                        'variant' => (string) ($step['variant'] ?? ''),
                        'hits' => count($fetched),
                        'typed' => $stepSourceTypes !== [] ? '1' : '0',
                    ];
                    foreach ($fetched as $row) {
                        if (!is_array($row)) { continue; }
                        $uniq = (string) ($row['source_type'] ?? '') . '|' . (string) ($row['source_key'] ?? '');
                        $rows[$uniq] = $row;
                    }
                    if (count($rows) >= 170) {
                        break;
                    }
                }
                if ($rows === []) {
                    $stmt = db()->query('SELECT source_type, source_key, title, body, url, embedding_json, updated_at FROM rag_chunks ORDER BY updated_at DESC LIMIT 220');
                    $rows = $stmt ? ($stmt->fetchAll() ?: []) : [];
                } else {
                    $rows = array_values($rows);
                }
                $sourceVotes = rag_agentic_source_vote($rows, $queryTokens);
                $best = null;
                $bestScore = -1.0;
                $bestCoverage = 0.0;
                $secondBestScore = -1.0;
                $bestVariantUsed = '';
                $rankedCandidates = [];
                $queryComplexity = max(1, count($queryTokens));
                $sourceSeen = [];
                $qVecMap = [];
                $llphantReady = rag_llphant_is_ready();
                if (!$llphantReady) {
                    throw new RuntimeException('llphant_unavailable');
                }
                foreach ($rows as $row) {
                    if (!is_array($row)) { continue; }
                    $vec = rag_decode_embedding((string) ($row['embedding_json'] ?? '[]'));
                    if ($vec === []) { continue; }
                    $sim = 0.0;
                    $variantUsed = '';
                    foreach ($variants as $variant) {
                        if (!isset($qVecMap[$variant])) {
                            $qVecMap[$variant] = rag_embedding_vector($variant);
                        }
                        $variantSim = rag_cosine_similarity($qVecMap[$variant], $vec);
                        if ($variantSim > $sim) {
                            $sim = $variantSim;
                            $variantUsed = $variant;
                        }
                    }
                    if ($sim <= 0.03) { continue; }
                    $title = (string) ($row['title'] ?? '');
                    $body = (string) ($row['body'] ?? '');
                    $combined = $title . ' ' . $body;
                    $coverage = rag_query_coverage($queryTokens, $combined);
                    $lexical = rag_weighted_score($queryTokens, $title) * 1.4
                        + rag_weighted_score($queryTokens, $body) * 0.9;
                    $phraseBoost = 0.0;
                    if (mb_strlen($normalized) >= 4) {
                        $lowerTitle = mb_safe_strtolower($title);
                        $lowerBody = mb_safe_strtolower($body);
                        if (mb_strpos($lowerTitle, $normalized) !== false) {
                            $phraseBoost += 0.38;
                        } elseif (mb_strpos($lowerBody, $normalized) !== false) {
                            $phraseBoost += 0.2;
                        }
                    }
                    $sourceType = (string) ($row['source_type'] ?? '');
                    $sourceBoost = match ($sourceType) {
                        'knowledge' => 0.45,
                        'article' => 0.28,
                        'library' => 0.18,
                        default => 0.0,
                    };
                    $totalVotes = array_sum($sourceVotes);
                    if ($totalVotes > 0.0 && isset($sourceVotes[$sourceType])) {
                        $voteShare = max(0.0, min(1.0, ((float) $sourceVotes[$sourceType]) / $totalVotes));
                        $sourceBoost += $voteShare * 0.22;
                    }
                    $recencyBoost = 0.0;
                    $updatedAt = trim((string) ($row['updated_at'] ?? ''));
                    if ($updatedAt !== '') {
                        try {
                            $ageHours = max(0.0, (time() - (new DateTimeImmutable($updatedAt))->getTimestamp()) / 3600.0);
                            $recencyBoost = max(0.0, 0.2 - min(0.2, $ageHours / 1200.0));
                        } catch (Throwable) {
                            $recencyBoost = 0.0;
                        }
                    }
                    $sourceKey = (string) ($row['source_key'] ?? '');
                    $sourceGroupKey = rag_source_group_key($sourceType, $sourceKey, $title);
                    $duplicatePenalty = 0.0;
                    $seenCount = (int) ($sourceSeen[$sourceGroupKey] ?? 0);
                    if ($seenCount > 0) {
                        $duplicatePenalty = min(0.32, $seenCount * 0.11);
                    }
                    $sourceSeen[$sourceGroupKey] = $seenCount + 1;
                    $score = $sim * 5.2 + $coverage * 2.8 + $lexical * 0.7 + $sourceBoost + $recencyBoost + $phraseBoost - $duplicatePenalty;
                    $rankedCandidates[] = [
                        'score' => $score,
                        'title' => trim((string) ($row['title'] ?? '')),
                        'url' => trim((string) ($row['url'] ?? '')),
                        'type' => $sourceType !== '' ? $sourceType : 'source',
                    ];
                    if ($score > $bestScore) {
                        $secondBestScore = $bestScore;
                        $bestScore = $score;
                        $best = $row;
                        $bestCoverage = $coverage;
                        $bestVariantUsed = $variantUsed;
                    } elseif ($score > $secondBestScore) {
                        $secondBestScore = $score;
                    }
                }
                $isAmbiguous = ($bestScore - $secondBestScore) < 0.08 && $bestCoverage < 0.35;
                $minCoverage = $queryComplexity >= 6 ? 0.28 : 0.2;
                $minScore = $queryComplexity >= 6 ? 2.0 : 1.8;
                $confidence = rag_agentic_confidence($bestScore, $bestCoverage, $bestScore - $secondBestScore);
                $answerAccepted = false;
                if (is_array($best) && !$isAmbiguous && $bestScore >= $minScore && $bestCoverage >= $minCoverage && $confidence >= 0.48) {
                    $summary = trim(mb_substr((string) ($best['body'] ?? ''), 0, 480));
                    $link = trim((string) ($best['url'] ?? ''));
                    $freshnessHours = null;
                    $bestUpdatedAt = trim((string) ($best['updated_at'] ?? ''));
                    if ($bestUpdatedAt !== '') {
                        try {
                            $freshnessHours = max(0.0, (time() - (new DateTimeImmutable($bestUpdatedAt))->getTimestamp()) / 3600.0);
                        } catch (Throwable) {
                            $freshnessHours = null;
                        }
                    }
                    $isStaleCandidate = $freshnessHours !== null && $freshnessHours > 24.0 * 180.0;
                    if ($isStaleCandidate && $confidence < 0.65) {
                        $answerAccepted = false;
                        $best = null;
                    }
                    $answerAccepted = rag_agentic_answer_is_valid($normalized, $summary, $bestCoverage, $confidence);
                    if (!$answerAccepted) {
                        $best = null;
                    }
                    if ($answerAccepted && is_array($best)) {
                        usort($rankedCandidates, static fn(array $a, array $b): int => ((float) ($b['score'] ?? 0.0)) <=> ((float) ($a['score'] ?? 0.0)));
                        $sources = [];
                        $seen = [];
                        foreach ($rankedCandidates as $candidate) {
                            $sourceTitle = trim((string) ($candidate['title'] ?? ''));
                            $sourceUrl = trim((string) ($candidate['url'] ?? ''));
                            $sourceType = trim((string) ($candidate['type'] ?? 'source'));
                            $uniq = $sourceType . '|' . $sourceTitle . '|' . $sourceUrl;
                            if ($sourceTitle === '' || isset($seen[$uniq])) {
                                continue;
                            }
                            $seen[$uniq] = true;
                            $sources[] = ['title' => $sourceTitle, 'url' => $sourceUrl, 'type' => $sourceType];
                            if (count($sources) >= 3) {
                                break;
                            }
                        }
                        $answer = $summary;
                        if ($link !== '') { $answer .= "\n\n" . (string) $chatbotT['link'] . $link; }
                        $sourceType = trim((string) ($best['source_type'] ?? 'source'));
                        $sourceTitle = trim((string) ($best['title'] ?? ''));
                        $source = 'RAG v2 agentic (LLPhant) · ' . $sourceType . ($sourceTitle !== '' ? (' · ' . $sourceTitle) : '');
                        if ($bestVariantUsed !== '') {
                            $source .= ' · variant:' . mb_substr($bestVariantUsed, 0, 48);
                        }
                        $source .= ' · conf:' . (string) round($confidence, 2);
                        if ($sourceVotes !== []) {
                            arsort($sourceVotes);
                            $topVotedType = (string) array_key_first($sourceVotes);
                            if ($topVotedType !== '') {
                                $source .= ' · voted:' . $topVotedType;
                            }
                        }
                        if ($planTrace !== []) {
                            $last = $planTrace[min(count($planTrace) - 1, 2)] ?? null;
                            if (is_array($last)) {
                                $source .= ' · plan:' . (string) ($last['hits'] ?? '0') . 'h';
                            }
                        }
                        return [
                            'answer' => $answer,
                            'source' => $source,
                            'sources' => $sources,
                            'confidence' => (float) round($confidence, 2),
                            'freshness_hours' => $freshnessHours,
                        ];
                    }
                }
            } catch (Throwable) {
                // fallback below
            }
        }

        $knowledgePath = __DIR__ . '/knowledge.php';
        $knowledgeBase = [];
        if (is_file($knowledgePath)) {
            $loaded = require $knowledgePath;
            if (is_array($loaded)) {
                $knowledgeBase = $loaded;
            }
        }

        $bestScore = -1.0;
        $bestItem = null;
        foreach ($knowledgeBase as $item) {
            if (!is_array($item)) {
                continue;
            }
            $score = 0.0;
            $keywords = isset($item['keywords']) && is_array($item['keywords']) ? $item['keywords'] : [];
            foreach ($keywords as $keyword) {
                $needle = mb_safe_strtolower(trim((string) $keyword));
                if ($needle !== '' && str_contains($normalized, $needle)) {
                    $score += 3.0;
                }
            }
            $title = (string) ($item['title'] ?? '');
            $body = (string) ($item['body'] ?? '');
            $score += rag_weighted_score($queryTokens, $title) * 2.0;
            $score += rag_weighted_score($queryTokens, $body);
            $score += rag_query_coverage($queryTokens, $title . ' ' . $body) * 3.5;
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestItem = $item;
            }
        }

        if ($bestItem !== null && $bestScore > 0) {
            return [
                'answer' => trim((string) ($bestItem['body'] ?? (string) $chatbotT['no_precise_yet'])),
                'source' => trim((string) ($bestItem['source'] ?? (string) $chatbotT['knowledge_source'])),
            ];
        }

        $ragLikeTerms = array_slice($queryTokens, 0, 5);
        if ($normalized !== '') {
            array_unshift($ragLikeTerms, $normalized);
        }
        $ragLikeTerms = array_values(array_unique(array_filter(array_map(
            static fn(string $term): string => trim($term),
            $ragLikeTerms
        ), static fn(string $term): bool => $term !== '')));

        if (table_exists('articles')) {
            try {
                $whereParts = [];
                $params = [];
                foreach ($ragLikeTerms as $term) {
                    $like = '%' . $term . '%';
                    $whereParts[] = '(title LIKE ? OR excerpt LIKE ? OR content LIKE ?)';
                    array_push($params, $like, $like, $like);
                }
                if ($whereParts === []) {
                    $whereParts[] = '(title LIKE ? OR excerpt LIKE ? OR content LIKE ?)';
                    array_push($params, '%'.$question.'%', '%'.$question.'%', '%'.$question.'%');
                }
                $sql = 'SELECT title, excerpt, content, slug FROM articles WHERE status = "published" AND (' . implode(' OR ', $whereParts) . ') ORDER BY updated_at DESC LIMIT 25';
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                $articles = $stmt->fetchAll();
                $article = null;
                $articleScore = -1.0;
                foreach ($articles as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $score = rag_weighted_score($queryTokens, (string) ($row['title'] ?? '')) * 2.0
                        + rag_weighted_score($queryTokens, (string) ($row['excerpt'] ?? ''))
                        + rag_weighted_score($queryTokens, (string) ($row['content'] ?? ''));
                    $score += rag_query_coverage($queryTokens, (string) (($row['title'] ?? '') . ' ' . ($row['excerpt'] ?? '') . ' ' . ($row['content'] ?? ''))) * 3.0;
                    if ($score > $articleScore) {
                        $articleScore = $score;
                        $article = $row;
                    }
                }
                if (is_array($article) && $articleScore >= 2.0) {
                    $title = trim((string) ($article['title'] ?? (string) $chatbotT['article_label']));
                    $excerpt = trim((string) ($article['excerpt'] ?? ''));
                    $slug = trim((string) ($article['slug'] ?? ''));
                    $url = $slug !== '' ? route_url('article', ['slug' => $slug]) : '';
                    $answer = (string) $chatbotT['article_found'] . $title . '.';
                    if ($excerpt !== '') {
                        $answer .= "\n\n" . (string) $chatbotT['summary'] . $excerpt;
                    }
                    if ($url !== '') {
                        $answer .= "\n\n" . (string) $chatbotT['link'] . $url;
                    }
                    return ['answer' => $answer, 'source' => (string) $chatbotT['articles_source']];
                }
            } catch (Throwable) {
                // fallback below
            }
        }

        if (ensure_member_library_table()) {
            try {
                $whereParts = [];
                $params = [];
                foreach ($ragLikeTerms as $term) {
                    $like = '%' . $term . '%';
                    $whereParts[] = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ?)';
                    array_push($params, $like, $like, $like);
                }
                if ($whereParts === []) {
                    $whereParts[] = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ?)';
                    array_push($params, '%'.$question.'%', '%'.$question.'%', '%'.$question.'%');
                }
                $sql = 'SELECT title, description, extracted_text, file_path FROM member_library_documents WHERE (' . implode(' OR ', $whereParts) . ') ORDER BY uploaded_at DESC LIMIT 25';
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                $docs = $stmt->fetchAll();
                $doc = null;
                $docScore = -1.0;
                foreach ($docs as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $score = rag_weighted_score($queryTokens, (string) ($row['title'] ?? '')) * 2.0
                        + rag_weighted_score($queryTokens, (string) ($row['description'] ?? ''))
                        + rag_weighted_score($queryTokens, (string) ($row['extracted_text'] ?? ''));
                    $score += rag_query_coverage($queryTokens, (string) (($row['title'] ?? '') . ' ' . ($row['description'] ?? '') . ' ' . ($row['extracted_text'] ?? ''))) * 3.0;
                    if ($score > $docScore) {
                        $docScore = $score;
                        $doc = $row;
                    }
                }
                if (is_array($doc) && $docScore >= 2.0) {
                    $locale = current_locale();
                    $chatbotDocI18n = [
                        'fr' => ['doc_fallback' => 'Document PDF', 'prefix' => 'J’ai trouvé un document dans la bibliothèque membres : ', 'summary' => 'Résumé : ', 'open' => 'Consulter : ', 'source' => 'Bibliothèque membres'],
                        'en' => ['doc_fallback' => 'PDF document', 'prefix' => 'I found a document in the members library: ', 'summary' => 'Summary: ', 'open' => 'Open: ', 'source' => 'Members library'],
                        'de' => ['doc_fallback' => 'PDF-Dokument', 'prefix' => 'Ich habe ein Dokument in der Mitgliederbibliothek gefunden: ', 'summary' => 'Zusammenfassung: ', 'open' => 'Öffnen: ', 'source' => 'Mitgliederbibliothek'],
                        'nl' => ['doc_fallback' => 'PDF-document', 'prefix' => 'Ik heb een document gevonden in de ledenbibliotheek: ', 'summary' => 'Samenvatting: ', 'open' => 'Openen: ', 'source' => 'Ledenbibliotheek'],
                    ];
                    $chatbotDocT = $chatbotDocI18n[$locale] ?? $chatbotDocI18n['fr'];
                    $docTitle = trim((string) ($doc['title'] ?? (string) $chatbotDocT['doc_fallback']));
                    $docDescription = trim((string) ($doc['description'] ?? ''));
                    $docUrl = trim((string) ($doc['file_path'] ?? ''));
                    $safeDocUrl = safe_storage_public_path_or_null($docUrl, ['storage/uploads/library/']);
                    $answer = (string) $chatbotDocT['prefix'] . $docTitle . '.';
                    if ($docDescription !== '') {
                        $answer .= "\n\n" . (string) $chatbotDocT['summary'] . $docDescription;
                    }
                    if (is_string($safeDocUrl) && $safeDocUrl !== '') {
                        $answer .= "\n\n" . (string) $chatbotDocT['open'] . base_url($safeDocUrl);
                    }
                    return ['answer' => $answer, 'source' => (string) $chatbotDocT['source']];
                }
            } catch (Throwable) {
                // fallback below
            }
        }

        return [
            'answer' => (string) $chatbotT['no_answer'],
            'source' => (string) $chatbotT['assistant_source'],
        ];
    }
}

if (!function_exists('ensure_member_library_table')) {
function ensure_member_library_table(): bool
{
    static $ready = null;
    if (is_bool($ready)) {
        return $ready;
    }
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS member_library_documents (id INT AUTO_INCREMENT PRIMARY KEY, member_id INT NOT NULL, category VARCHAR(120) NOT NULL DEFAULT "general", tags VARCHAR(255) NOT NULL DEFAULT "", title VARCHAR(255) NOT NULL, description TEXT NULL, file_path VARCHAR(255) NOT NULL, extracted_text LONGTEXT NULL, uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_uploaded (uploaded_at), INDEX idx_member_uploaded (member_id, uploaded_at), INDEX idx_category (category), INDEX idx_tags (tags))');
        $ready = table_exists('member_library_documents');
        if ($ready) {
            $hasCategory = false;
            try {
                $col = db()->query("SHOW COLUMNS FROM member_library_documents LIKE 'category'");
                $hasCategory = (bool) ($col && $col->fetch());
            } catch (Throwable) {
                $hasCategory = false;
            }
            if (!$hasCategory) {
                db()->exec('ALTER TABLE member_library_documents ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT "general" AFTER member_id');
            }
            $hasTags = false;
            try {
                $tagsCol = db()->query("SHOW COLUMNS FROM member_library_documents LIKE 'tags'");
                $hasTags = (bool) ($tagsCol && $tagsCol->fetch());
            } catch (Throwable) {
                $hasTags = false;
            }
            if (!$hasTags) {
                db()->exec('ALTER TABLE member_library_documents ADD COLUMN tags VARCHAR(255) NOT NULL DEFAULT "" AFTER category');
            }
            try {
                db()->exec('ALTER TABLE member_library_documents ADD INDEX idx_category (category)');
            } catch (Throwable) {
                // Index may already exist.
            }
            try {
                db()->exec('ALTER TABLE member_library_documents ADD INDEX idx_tags (tags)');
            } catch (Throwable) {
                // Index may already exist.
            }
        }
    } catch (Throwable) {
        $ready = false;
    }

    return $ready;
}
}

if (!function_exists('ensure_member_favorites_table')) {
function ensure_member_favorites_table(): bool
{
    if (!table_exists('members')) {
        return false;
    }

    db()->exec('CREATE TABLE IF NOT EXISTS member_favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        target_type VARCHAR(48) NOT NULL,
        target_id INT NOT NULL,
        target_key VARCHAR(190) DEFAULT NULL,
        title VARCHAR(255) NOT NULL DEFAULT "",
        url VARCHAR(255) NOT NULL DEFAULT "",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_member_target (member_id, target_type, target_id),
        KEY idx_member_created (member_id, created_at),
        KEY idx_target (target_type, target_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    return true;
}
}

if (!function_exists('favorite_is_saved')) {
function favorite_is_saved(int $memberId, string $targetType, int $targetId): bool
{
    if ($memberId <= 0 || $targetId <= 0 || !ensure_member_favorites_table()) {
        return false;
    }

    $stmt = db()->prepare('SELECT id FROM member_favorites WHERE member_id = ? AND target_type = ? AND target_id = ? LIMIT 1');
    $stmt->execute([$memberId, $targetType, $targetId]);
    return $stmt->fetchColumn() !== false;
}
}

if (!function_exists('favorite_toggle')) {
function favorite_toggle(int $memberId, string $targetType, int $targetId, string $title = '', string $url = '', ?string $targetKey = null): bool
{
    if ($memberId <= 0 || $targetId <= 0 || !ensure_member_favorites_table()) {
        return false;
    }

    if (favorite_is_saved($memberId, $targetType, $targetId)) {
        $stmt = db()->prepare('DELETE FROM member_favorites WHERE member_id = ? AND target_type = ? AND target_id = ?');
        $stmt->execute([$memberId, $targetType, $targetId]);
        return false;
    }

    $stmt = db()->prepare('INSERT INTO member_favorites (member_id, target_type, target_id, target_key, title, url) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$memberId, $targetType, $targetId, $targetKey, mb_safe_substr(trim($title), 0, 255), mb_safe_substr(trim($url), 0, 255)]);
    return true;
}
}

if (!function_exists('member_favorites_recent')) {
function member_favorites_recent(int $memberId, int $limit = 12): array
{
    if ($memberId <= 0 || !ensure_member_favorites_table()) {
        return [];
    }

    $limit = max(1, min(100, $limit));
    $stmt = db()->prepare('SELECT target_type, target_id, target_key, title, url, created_at FROM member_favorites WHERE member_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
    $stmt->execute([$memberId]);
    return $stmt->fetchAll() ?: [];
}
}

if (!function_exists('library_controlled_vocabulary_list')) {
function library_controlled_vocabulary_list(): array
{
    return [
        'formation',
        'securite',
        'legal',
        'reglement',
        'technique',
        'antenne',
        'propagation',
        'traffic',
        'numerique',
        'materiel',
        'maintenance',
        'procedure',
        'club',
    ];
}
}

if (!function_exists('library_ingestion_templates_map')) {
function library_ingestion_templates_map(): array
{
    return [
        'training' => ['category' => 'formation', 'tags' => ['formation', 'procedure', 'club']],
        'safety' => ['category' => 'general', 'tags' => ['securite', 'procedure', 'reglement']],
        'technical' => ['category' => 'general', 'tags' => ['technique', 'antenne', 'propagation', 'materiel']],
        'legal' => ['category' => 'general', 'tags' => ['legal', 'reglement', 'club']],
    ];
}
}

if (!function_exists('library_filter_controlled_tags')) {
function library_filter_controlled_tags(array $tags): array
{
    $allowed = array_fill_keys(library_controlled_vocabulary_list(), true);
    $out = [];
    foreach ($tags as $tag) {
        $raw = trim((string) $tag);
        if ($raw === '') {
            continue;
        }
        $norm = mb_strtolower($raw, 'UTF-8');
        $norm = preg_replace('/\s+/u', ' ', $norm) ?? $norm;
        $norm = trim($norm);
        if ($norm === '' || !isset($allowed[$norm])) {
            continue;
        }
        $out[] = $raw;
    }
    return $out;
}
}

if (!function_exists('editorial_blocked_reasons_from_article')) {
/**
 * @param array<string,mixed> $article
 * @return list<string>
 */
function editorial_blocked_reasons_from_article(array $article): array
{
    $reasons = [];
    $title = trim((string) ($article['title'] ?? ''));
    $content = trim(strip_tags((string) ($article['content'] ?? '')));
    $status = (string) ($article['status'] ?? 'draft');
    $scheduledAt = trim((string) ($article['scheduled_at'] ?? ''));

    if ($title === '') {
        $reasons[] = 'missing_title';
    }
    if ($content === '') {
        $reasons[] = 'missing_content';
    }
    if ($status === 'scheduled') {
        if ($scheduledAt === '') {
            $reasons[] = 'missing_schedule_date';
        } else {
            $ts = strtotime($scheduledAt);
            if ($ts === false) {
                $reasons[] = 'invalid_schedule_date';
            } elseif ($ts <= time()) {
                $reasons[] = 'stuck_in_past_schedule';
            }
        }
    }
    return $reasons;
}
}

if (!function_exists('member_personalized_recommendations')) {
function ensure_member_preference_table(): bool
{
    if (!table_exists('members')) {
        return false;
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS member_preferences (
            member_id INT NOT NULL,
            preference_key VARCHAR(80) NOT NULL,
            preference_value VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (member_id, preference_key),
            CONSTRAINT fk_member_preferences_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
        )'
    );

    return table_exists('member_preferences');
}

function member_preference_bool(int $memberId, string $key, bool $default = true): bool
{
    if ($memberId <= 0 || $key === '' || !ensure_member_preference_table()) {
        return $default;
    }

    $stmt = db()->prepare('SELECT preference_value FROM member_preferences WHERE member_id = ? AND preference_key = ? LIMIT 1');
    $stmt->execute([$memberId, $key]);
    $value = $stmt->fetchColumn();
    if ($value === false) {
        return $default;
    }

    return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
}

function set_member_preference_bool(int $memberId, string $key, bool $value): void
{
    if ($memberId <= 0 || $key === '' || !ensure_member_preference_table()) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO member_preferences (member_id, preference_key, preference_value)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$memberId, $key, $value ? '1' : '0']);
}

function member_preference_string(int $memberId, string $key, string $default = ''): string
{
    if ($memberId <= 0 || $key === '' || !ensure_member_preference_table()) {
        return $default;
    }

    $stmt = db()->prepare('SELECT preference_value FROM member_preferences WHERE member_id = ? AND preference_key = ? LIMIT 1');
    $stmt->execute([$memberId, $key]);
    $value = $stmt->fetchColumn();
    if ($value === false) {
        return $default;
    }

    return (string) $value;
}

function set_member_preference_string(int $memberId, string $key, string $value): void
{
    if ($memberId <= 0 || $key === '' || !ensure_member_preference_table()) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO member_preferences (member_id, preference_key, preference_value)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$memberId, $key, mb_safe_substr(trim($value), 0, 255)]);
}

function member_personalized_recommendations(int $memberId, int $limit = 6): array
{
    $limit = max(1, min(24, $limit));
    $signalPrefs = [
        'article' => member_preference_bool($memberId, 'recommendations_signal_article_enabled', true),
        'wiki' => member_preference_bool($memberId, 'recommendations_signal_wiki_enabled', true),
        'classified' => member_preference_bool($memberId, 'recommendations_signal_classified_enabled', true),
        'album' => member_preference_bool($memberId, 'recommendations_signal_album_enabled', true),
        'library' => member_preference_bool($memberId, 'recommendations_signal_library_enabled', true),
    ];
    if (!in_array(true, $signalPrefs, true)) {
        return [];
    }

    $seedTypes = [];
    foreach (member_favorites_recent($memberId, 30) as $favorite) {
        $type = (string) ($favorite['target_type'] ?? '');
        if ($type !== '') {
            $seedTypes[$type] = true;
        }
    }

    $items = [];
    $pushUnique = static function (array $row) use (&$items, $limit): void {
        if (count($items) >= $limit) {
            return;
        }
        $key = (string) ($row['key'] ?? '');
        if ($key === '' || isset($items[$key])) {
            return;
        }
        $items[$key] = $row;
    };

    $wantsArticles = $signalPrefs['article'] && (isset($seedTypes['article']) || $seedTypes === []);
    if ($wantsArticles && table_exists('articles')) {
        $stmt = db()->query('SELECT id, slug, title, updated_at FROM articles WHERE status = "published" ORDER BY updated_at DESC, id DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Article';
            }
            $pushUnique([
                'key' => 'article:' . $id,
                'type' => 'article',
                'title' => $title,
                'url' => route_url('article', ['slug' => (string) ($row['slug'] ?? '')]),
                'meta' => (string) ($row['updated_at'] ?? ''),
                'reason_key' => 'recommendation_reason_article',
            ]);
        }
    }

    $wantsWiki = $signalPrefs['wiki'] && (isset($seedTypes['wiki_page']) || $seedTypes === []);
    if ($wantsWiki && table_exists('wiki_pages')) {
        $stmt = db()->query('SELECT slug, title, updated_at FROM wiki_pages ORDER BY updated_at DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Wiki';
            }
            $pushUnique([
                'key' => 'wiki:' . $slug,
                'type' => 'wiki',
                'title' => $title,
                'url' => route_url('wiki_view', ['slug' => $slug]),
                'meta' => (string) ($row['updated_at'] ?? ''),
                'reason_key' => 'recommendation_reason_wiki',
            ]);
        }
    }

    $wantsClassifieds = $signalPrefs['classified'] && (isset($seedTypes['classified_ad']) || $seedTypes === []);
    if ($wantsClassifieds && table_exists('classified_ads')) {
        $stmt = db()->query('SELECT id, title, created_at FROM classified_ads WHERE status = "active" AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY created_at DESC, id DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Classified';
            }
            $pushUnique([
                'key' => 'classified:' . $id,
                'type' => 'classified',
                'title' => $title,
                'url' => route_url('classifieds', ['q' => $title]),
                'meta' => (string) ($row['created_at'] ?? ''),
                'reason_key' => 'recommendation_reason_classified',
            ]);
        }
    }

    $wantsAlbums = $signalPrefs['album'] && (isset($seedTypes['album']) || $seedTypes === []);
    if ($wantsAlbums && table_exists('albums')) {
        $stmt = db()->query('SELECT id, title, created_at FROM albums WHERE is_public = 1 ORDER BY id DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Album';
            }
            $pushUnique([
                'key' => 'album:' . $id,
                'type' => 'album',
                'title' => $title,
                'url' => route_url('album', ['id' => $id]),
                'meta' => (string) ($row['created_at'] ?? ''),
                'reason_key' => 'recommendation_reason_album',
            ]);
        }
    }

    $wantsLibrary = $signalPrefs['library'] && (isset($seedTypes['library_document']) || $seedTypes === []);
    if ($wantsLibrary && table_exists('member_library_documents')) {
        $stmt = db()->query('SELECT id, title, category, uploaded_at FROM member_library_documents ORDER BY uploaded_at DESC, id DESC LIMIT 12');
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Library document';
            }
            $pushUnique([
                'key' => 'library:' . $id,
                'type' => 'library',
                'title' => $title,
                'url' => route_url_clean('members_library', ['q' => $title, 'category' => (string) ($row['category'] ?? '')]),
                'meta' => (string) ($row['uploaded_at'] ?? ''),
                'reason_key' => 'recommendation_reason_library',
            ]);
        }
    }

    return array_values($items);
}
}

if (!function_exists('ensure_member_notifications_table')) {
function ensure_member_notifications_table(): bool
{
    if (!table_exists('members')) {
        return false;
    }

    db()->exec('CREATE TABLE IF NOT EXISTS member_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        type VARCHAR(64) NOT NULL DEFAULT "info",
        title VARCHAR(255) NOT NULL,
        body TEXT DEFAULT NULL,
        url VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL DEFAULT NULL,
        KEY idx_member_unread (member_id, is_read, created_at),
        KEY idx_member_created (member_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    return true;
}
}

if (!function_exists('notify_member')) {
function notify_member(int $memberId, string $type, string $title, ?string $body = null, ?string $url = null): void
{
    if ($memberId <= 0 || !ensure_member_notifications_table()) {
        return;
    }

    $stmt = db()->prepare('INSERT INTO member_notifications (member_id, type, title, body, url) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $memberId,
        mb_safe_substr(trim($type), 0, 64) ?: 'info',
        mb_safe_substr(trim($title), 0, 255),
        $body !== null ? trim($body) : null,
        $url !== null ? mb_safe_substr(trim($url), 0, 255) : null,
    ]);
}
}

if (!function_exists('member_notifications_recent')) {
function member_notifications_recent(int $memberId, int $limit = 10): array
{
    if ($memberId <= 0 || !ensure_member_notifications_table()) {
        return [];
    }

    $limit = max(1, min(100, $limit));
    $stmt = db()->prepare('SELECT id, type, title, body, url, is_read, created_at FROM member_notifications WHERE member_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
    $stmt->execute([$memberId]);
    return $stmt->fetchAll() ?: [];
}
}

if (!function_exists('member_notifications_unread_count')) {
function member_notifications_unread_count(int $memberId): int
{
    if ($memberId <= 0 || !ensure_member_notifications_table()) {
        return 0;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM member_notifications WHERE member_id = ? AND is_read = 0');
    $stmt->execute([$memberId]);
    return (int) ($stmt->fetchColumn() ?: 0);
}
}

if (!function_exists('member_notifications_mark_all_read')) {
function member_notifications_mark_all_read(int $memberId): void
{
    if ($memberId <= 0 || !ensure_member_notifications_table()) {
        return;
    }

    $stmt = db()->prepare('UPDATE member_notifications SET is_read = 1, read_at = NOW() WHERE member_id = ? AND is_read = 0');
    $stmt->execute([$memberId]);
}
}

if (!function_exists('member_notification_mark_read')) {
function member_notification_mark_read(int $memberId, int $notificationId): void
{
    if ($memberId <= 0 || $notificationId <= 0 || !ensure_member_notifications_table()) {
        return;
    }

    $stmt = db()->prepare('UPDATE member_notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND member_id = ? AND is_read = 0');
    $stmt->execute([$notificationId, $memberId]);
}
}

/**
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function localized_article_row(array $row): array
{
    $locale = current_locale();
    if ($locale !== 'fr') {
        $articleId = (int) ($row['id'] ?? 0);
        if ($articleId > 0 && table_exists('article_translations')) {
            try {
                $stmt = db()->prepare('SELECT title, excerpt, content FROM article_translations WHERE article_id = ? AND locale = ? ORDER BY CASE status WHEN "reviewed" THEN 0 WHEN "auto" THEN 1 ELSE 2 END, updated_at DESC LIMIT 1');
                $stmt->execute([$articleId, $locale]);
                $translation = $stmt->fetch();
                if (is_array($translation)) {
                    foreach (['title', 'excerpt', 'content'] as $field) {
                        $value = trim((string) ($translation[$field] ?? ''));
                        if ($value !== '') {
                            $row[$field] = $value;
                        }
                    }
                }
            } catch (Throwable) {
                // Keep the source article when translations are unavailable.
            }
        }
    }

    $row['title_localized'] = (string) ($row['title'] ?? '');
    $row['excerpt_localized'] = (string) ($row['excerpt'] ?? '');
    $row['content_localized'] = (string) ($row['content'] ?? '');

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
    $locale = current_locale();
    $qslUnavailableLabel = match ($locale) {
        'en' => 'Secure QSL unavailable',
        'de' => 'Sichere QSL nicht verfügbar',
        'nl' => 'Beveiligde QSL niet beschikbaar',
        'es' => 'QSL segura no disponible',
        'it' => 'QSL sicura non disponibile',
        'pt' => 'QSL segura indisponível',
        'ar' => 'QSL الآمنة غير متاحة',
        'hi' => 'सुरक्षित QSL उपलब्ध नहीं है',
        'ja' => '安全なQSLは利用できません',
        'zh' => '安全QSL不可用',
        'bn' => 'নিরাপদ QSL উপলভ্য নয়',
        'ru' => 'Безопасная QSL недоступна',
        'id' => 'QSL aman tidak tersedia',
        default => 'QSL sécurisée indisponible',
    };
    $safeFallbackSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="500" viewBox="0 0 900 500"><rect width="900" height="500" fill="#0f172a"/><text x="450" y="250" text-anchor="middle" fill="#f8fafc" font-family="Arial, sans-serif" font-size="28">' . e($qslUnavailableLabel) . '</text></svg>';

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
            return $safeFallbackSvg;
        }
    }

    if (str_contains($normalized, 'javascript:')) {
        return $safeFallbackSvg;
    }

    if (preg_match_all('/(?:href|xlink:href)\s*=\s*["\']([^"\']+)["\']/i', $svg, $matches) > 0 && isset($matches[1])) {
        foreach ($matches[1] as $href) {
            $candidate = strtolower(trim((string) $href));
            if (str_starts_with($candidate, 'data:image/')) {
                if (preg_match('/^data:image\/(?:png|jpe?g|webp);base64,[a-z0-9+\/=]+$/i', $candidate) !== 1) {
                    return $safeFallbackSvg;
                }
            } elseif (str_starts_with($candidate, 'data:')) {
                return $safeFallbackSvg;
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
        throw new RuntimeException(upload_i18n_message('qsl_bg_upload_failed'));
    }

    $tmpPath = (string) ($upload['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException(upload_i18n_message('qsl_bg_invalid'));
    }

    $mime = detect_uploaded_mime_type($tmpPath);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException(upload_i18n_message('qsl_bg_not_supported'));
    }
    $size = (int) ($upload['size'] ?? 0);
    if ($size <= 0 || $size > 6 * 1024 * 1024) {
        throw new RuntimeException(upload_i18n_message('qsl_bg_too_large'));
    }

    $extension = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg',
    };
    assert_upload_file_is_valid_signature($tmpPath, [$extension]);
    $sanitizedTmpPath = sanitize_uploaded_image_file($tmpPath, $extension);
    $raw = @file_get_contents($sanitizedTmpPath);
    if ($sanitizedTmpPath !== $tmpPath) {
        @unlink($sanitizedTmpPath);
    }
    if ($raw === false) {
        throw new RuntimeException(upload_i18n_message('qsl_bg_unreadable'));
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

function preferred_locale_from_accept_language(string $header, ?array $supportedLocales = null): string
{
    $supportedLocales ??= supported_locales();
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

function preferred_locale_from_host(string $host, ?array $supportedLocales = null): string
{
    $supportedLocales ??= supported_locales();
    $normalizedHost = strtolower(trim($host));
    if ($normalizedHost === '') {
        return '';
    }

    $hostname = explode(':', $normalizedHost)[0] ?? $normalizedHost;
    $firstLabel = explode('.', $hostname)[0] ?? '';
    if ($firstLabel !== '' && in_array($firstLabel, $supportedLocales, true)) {
        return $firstLabel;
    }

    return '';
}

function initialize_user_preferences(): void
{
    $supportedLocales = supported_locales();
    $supportedThemes = ['light', 'dark'];
    $supportedAccents = ['blue', 'emerald', 'violet', 'red', 'amber', 'orange'];

    $cookieLocale = strtolower((string) ($_COOKIE['on4crd_locale'] ?? ''));
    $cookieTheme = strtolower((string) ($_COOKIE['on4crd_theme'] ?? ''));
    $cookieAccent = strtolower((string) ($_COOKIE['on4crd_accent'] ?? ''));

    if (!isset($_SESSION['locale'])) {
        $hostLocale = preferred_locale_from_host((string) ($_SERVER['HTTP_HOST'] ?? ''), $supportedLocales);
        if ($hostLocale !== '') {
            $_SESSION['locale'] = $hostLocale;
        } elseif (in_array($cookieLocale, $supportedLocales, true)) {
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
        throw new RuntimeException(upload_i18n_message('invalid_csrf_token'));
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

function upload_i18n_message(string $key): string
{
    $locale = current_locale();
    $messages = [
        'uploaded_unreadable' => ['fr' => 'Fichier téléversé illisible.', 'en' => 'Uploaded file is unreadable.', 'de' => 'Hochgeladene Datei ist unlesbar.', 'nl' => 'Geüpload bestand is onleesbaar.', 'es' => 'El archivo subido no se puede leer.', 'it' => 'Il file caricato non è leggibile.', 'pt' => 'O ficheiro carregado está ilegível.', 'ar' => 'الملف المرفوع غير قابل للقراءة.', 'hi' => 'अपलोड की गई फ़ाइल पढ़ी नहीं जा सकती।', 'ja' => 'アップロードされたファイルを読み取れません。', 'zh' => '上传的文件无法读取。', 'bn' => 'আপলোড করা ফাইলটি পড়া যাচ্ছে না।', 'ru' => 'Загруженный файл не читается.', 'id' => 'File yang diunggah tidak dapat dibaca.'],
        'invalid_signature' => ['fr' => 'Signature de fichier invalide pour le type attendu.', 'en' => 'Invalid file signature for the expected type.', 'de' => 'Ungültige Dateisignatur für den erwarteten Typ.', 'nl' => 'Ongeldige bestandssignatuur voor het verwachte type.', 'es' => 'Firma de archivo no válida para el tipo esperado.', 'it' => 'Firma file non valida per il tipo previsto.', 'pt' => 'Assinatura de ficheiro inválida para o tipo esperado.', 'ar' => 'توقيع الملف غير صالح للنوع المتوقع.', 'hi' => 'अपेक्षित प्रकार के लिए फ़ाइल हस्ताक्षर अमान्य है।', 'ja' => '想定された形式に対してファイル署名が無効です。', 'zh' => '文件签名与预期类型不匹配。', 'bn' => 'প্রত্যাশিত ধরনের জন্য ফাইল স্বাক্ষর অবৈধ।', 'ru' => 'Недопустимая сигнатура файла для ожидаемого типа.', 'id' => 'Tanda tangan file tidak valid untuk tipe yang diharapkan.'],
        'upload_failed' => ['fr' => 'Échec du téléversement.', 'en' => 'Upload failed.', 'de' => 'Upload fehlgeschlagen.', 'nl' => 'Upload mislukt.', 'es' => 'Error al subir el archivo.', 'it' => 'Caricamento non riuscito.', 'pt' => 'Falha no carregamento.', 'ar' => 'فشل رفع الملف.', 'hi' => 'अपलोड विफल हुआ।', 'ja' => 'アップロードに失敗しました。', 'zh' => '上传失败。', 'bn' => 'আপলোড ব্যর্থ হয়েছে।', 'ru' => 'Ошибка загрузки файла.', 'id' => 'Unggahan gagal.'],
        'upload_invalid' => ['fr' => 'Fichier téléversé invalide.', 'en' => 'Invalid uploaded file.', 'de' => 'Ungültig hochgeladene Datei.', 'nl' => 'Ongeldig geüpload bestand.', 'es' => 'Archivo subido no válido.', 'it' => 'File caricato non valido.', 'pt' => 'Ficheiro carregado inválido.', 'ar' => 'الملف المرفوع غير صالح.', 'hi' => 'अपलोड की गई फ़ाइल अमान्य है।', 'ja' => '無効なアップロードファイルです。', 'zh' => '上传的文件无效。', 'bn' => 'আপলোড করা ফাইলটি অবৈধ।', 'ru' => 'Недопустимый загруженный файл.', 'id' => 'File yang diunggah tidak valid.'],
        'file_too_large_or_empty' => ['fr' => 'Fichier trop volumineux ou vide.', 'en' => 'File is too large or empty.', 'de' => 'Datei ist zu groß oder leer.', 'nl' => 'Bestand is te groot of leeg.', 'es' => 'El archivo es demasiado grande o está vacío.', 'it' => 'Il file è troppo grande o vuoto.', 'pt' => 'O ficheiro é demasiado grande ou está vazio.', 'ar' => 'الملف كبير جدًا أو فارغ.', 'hi' => 'फ़ाइल बहुत बड़ी है या खाली है।', 'ja' => 'ファイルが大きすぎるか空です。', 'zh' => '文件过大或为空。', 'bn' => 'ফাইলটি খুব বড় বা খালি।', 'ru' => 'Файл слишком большой или пустой.', 'id' => 'File terlalu besar atau kosong.'],
        'extension_not_allowed' => ['fr' => 'Extension de fichier non autorisée.', 'en' => 'File extension is not allowed.', 'de' => 'Dateierweiterung ist nicht erlaubt.', 'nl' => 'Bestandsextensie is niet toegestaan.', 'es' => 'La extensión de archivo no está permitida.', 'it' => 'Estensione file non consentita.', 'pt' => 'Extensão de ficheiro não permitida.', 'ar' => 'امتداد الملف غير مسموح به.', 'hi' => 'फ़ाइल एक्सटेंशन की अनुमति नहीं है।', 'ja' => '許可されていないファイル拡張子です。', 'zh' => '文件扩展名不被允许。', 'bn' => 'ফাইল এক্সটেনশন অনুমোদিত নয়।', 'ru' => 'Расширение файла не разрешено.', 'id' => 'Ekstensi file tidak diizinkan.'],
        'mime_not_allowed' => ['fr' => 'Type MIME de fichier non autorisé.', 'en' => 'File MIME type is not allowed.', 'de' => 'MIME-Typ der Datei ist nicht erlaubt.', 'nl' => 'MIME-type van bestand is niet toegestaan.', 'es' => 'El tipo MIME del archivo no está permitido.', 'it' => 'Il tipo MIME del file non è consentito.', 'pt' => 'O tipo MIME do ficheiro não é permitido.', 'ar' => 'نوع MIME للملف غير مسموح به.', 'hi' => 'फ़ाइल का MIME प्रकार अनुमत नहीं है।', 'ja' => '許可されていない MIME タイプです。', 'zh' => '文件 MIME 类型不被允许。', 'bn' => 'ফাইলের MIME ধরন অনুমোদিত নয়।', 'ru' => 'MIME-тип файла не разрешён.', 'id' => 'Tipe MIME file tidak diizinkan.'],
        'cannot_create_destination_dir' => ['fr' => 'Impossible de créer le dossier de destination.', 'en' => 'Unable to create destination folder.', 'de' => 'Zielordner konnte nicht erstellt werden.', 'nl' => 'Kan doelmap niet maken.', 'es' => 'No se puede crear la carpeta de destino.', 'it' => 'Impossibile creare la cartella di destinazione.', 'pt' => 'Não foi possível criar a pasta de destino.', 'ar' => 'تعذر إنشاء مجلد الوجهة.', 'hi' => 'गंतव्य फ़ोल्डर बनाया नहीं जा सका।', 'ja' => '保存先フォルダーを作成できません。', 'zh' => '无法创建目标文件夹。', 'bn' => 'গন্তব্য ফোল্ডার তৈরি করা যায়নি।', 'ru' => 'Не удалось создать целевую папку.', 'id' => 'Tidak dapat membuat folder tujuan.'],
        'cannot_move_uploaded_file' => ['fr' => 'Impossible de déplacer le fichier téléversé.', 'en' => 'Unable to move uploaded file.', 'de' => 'Hochgeladene Datei konnte nicht verschoben werden.', 'nl' => 'Kan geüpload bestand niet verplaatsen.', 'es' => 'No se puede mover el archivo subido.', 'it' => 'Impossibile spostare il file caricato.', 'pt' => 'Não foi possível mover o ficheiro carregado.', 'ar' => 'تعذر نقل الملف المرفوع.', 'hi' => 'अपलोड की गई फ़ाइल को स्थानांतरित नहीं किया जा सका।', 'ja' => 'アップロードファイルを移動できません。', 'zh' => '无法移动上传的文件。', 'bn' => 'আপলোড করা ফাইল সরানো যায়নি।', 'ru' => 'Не удалось переместить загруженный файл.', 'id' => 'Tidak dapat memindahkan file yang diunggah.'],
        'uploaded_image_unreadable' => ['fr' => 'Image téléversée illisible.', 'en' => 'Uploaded image is unreadable.', 'de' => 'Hochgeladenes Bild ist unlesbar.', 'nl' => 'Geüploade afbeelding is onleesbaar.', 'es' => 'La imagen subida no se puede leer.', 'it' => 'L’immagine caricata non è leggibile.', 'pt' => 'A imagem carregada está ilegível.', 'ar' => 'الصورة المرفوعة غير قابلة للقراءة.', 'hi' => 'अपलोड की गई छवि पढ़ी नहीं जा सकती।', 'ja' => 'アップロードされた画像を読み取れません。', 'zh' => '上传的图片无法读取。', 'bn' => 'আপলোড করা ছবিটি পড়া যাচ্ছে না।', 'ru' => 'Загруженное изображение не читается.', 'id' => 'Gambar yang diunggah tidak dapat dibaca.'],
        'uploaded_image_invalid' => ['fr' => 'Image téléversée invalide.', 'en' => 'Uploaded image is invalid.', 'de' => 'Hochgeladenes Bild ist ungültig.', 'nl' => 'Geüploade afbeelding is ongeldig.', 'es' => 'La imagen subida no es válida.', 'it' => 'L’immagine caricata non è valida.', 'pt' => 'A imagem carregada é inválida.', 'ar' => 'الصورة المرفوعة غير صالحة.', 'hi' => 'अपलोड की गई छवि अमान्य है।', 'ja' => 'アップロードされた画像が無効です。', 'zh' => '上传的图片无效。', 'bn' => 'আপলোড করা ছবিটি অবৈধ।', 'ru' => 'Загруженное изображение недопустимо.', 'id' => 'Gambar yang diunggah tidak valid.'],
        'cannot_create_temp_file' => ['fr' => 'Impossible de créer un fichier temporaire.', 'en' => 'Unable to create a temporary file.', 'de' => 'Temporäre Datei konnte nicht erstellt werden.', 'nl' => 'Kan geen tijdelijk bestand maken.', 'es' => 'No se puede crear un archivo temporal.', 'it' => 'Impossibile creare un file temporaneo.', 'pt' => 'Não foi possível criar um ficheiro temporário.', 'ar' => 'تعذر إنشاء ملف مؤقت.', 'hi' => 'अस्थायी फ़ाइल बनाई नहीं जा सकी।', 'ja' => '一時ファイルを作成できません。', 'zh' => '无法创建临时文件。', 'bn' => 'অস্থায়ী ফাইল তৈরি করা যায়নি।', 'ru' => 'Не удалось создать временный файл.', 'id' => 'Tidak dapat membuat file sementara.'],
        'image_metadata_cleanup_failed' => ['fr' => 'Échec du nettoyage des métadonnées image.', 'en' => 'Failed to clean image metadata.', 'de' => 'Bereinigung der Bildmetadaten fehlgeschlagen.', 'nl' => 'Opschonen van afbeeldingsmetadata mislukt.', 'es' => 'Error al limpiar los metadatos de la imagen.', 'it' => 'Pulizia dei metadati immagine non riuscita.', 'pt' => 'Falha ao limpar os metadados da imagem.', 'ar' => 'فشل تنظيف البيانات الوصفية للصورة.', 'hi' => 'छवि मेटाडेटा साफ़ करने में विफल।', 'ja' => '画像メタデータのクリーンアップに失敗しました。', 'zh' => '清理图片元数据失败。', 'bn' => 'ছবির মেটাডেটা পরিষ্কার করা যায়নি।', 'ru' => 'Не удалось очистить метаданные изображения.', 'id' => 'Gagal membersihkan metadata gambar.'],
        'missing_image' => ['fr' => 'Image manquante.', 'en' => 'Missing image.', 'de' => 'Bild fehlt.', 'nl' => 'Afbeelding ontbreekt.', 'es' => 'Falta la imagen.', 'it' => 'Immagine mancante.', 'pt' => 'Imagem em falta.', 'ar' => 'الصورة مفقودة.', 'hi' => 'छवि अनुपलब्ध है।', 'ja' => '画像がありません。', 'zh' => '缺少图片。', 'bn' => 'ছবি অনুপস্থিত।', 'ru' => 'Изображение отсутствует.', 'id' => 'Gambar tidak ada.'],
        'qsl_bg_upload_failed' => ['fr' => 'Le téléversement de l’image de fond QSL a échoué.', 'en' => 'QSL background image upload failed.', 'de' => 'Das Hochladen des QSL-Hintergrundbilds ist fehlgeschlagen.', 'nl' => 'Upload van QSL-achtergrondafbeelding mislukt.', 'es' => 'Error al subir la imagen de fondo QSL.', 'it' => 'Caricamento dell’immagine di sfondo QSL non riuscito.', 'pt' => 'Falha no carregamento da imagem de fundo QSL.', 'ar' => 'فشل رفع صورة خلفية QSL.', 'hi' => 'QSL पृष्ठभूमि छवि अपलोड विफल हुआ।', 'ja' => 'QSL 背景画像のアップロードに失敗しました。', 'zh' => 'QSL 背景图片上传失败。', 'bn' => 'QSL ব্যাকগ্রাউন্ড ছবি আপলোড ব্যর্থ হয়েছে।', 'ru' => 'Не удалось загрузить фоновое изображение QSL.', 'id' => 'Unggahan gambar latar QSL gagal.'],
        'qsl_bg_invalid' => ['fr' => 'Image de fond QSL invalide.', 'en' => 'Invalid QSL background image.', 'de' => 'Ungültiges QSL-Hintergrundbild.', 'nl' => 'Ongeldige QSL-achtergrondafbeelding.', 'es' => 'Imagen de fondo QSL no válida.', 'it' => 'Immagine di sfondo QSL non valida.', 'pt' => 'Imagem de fundo QSL inválida.', 'ar' => 'صورة خلفية QSL غير صالحة.', 'hi' => 'अमान्य QSL पृष्ठभूमि छवि।', 'ja' => '無効な QSL 背景画像です。', 'zh' => '无效的 QSL 背景图片。', 'bn' => 'অবৈধ QSL ব্যাকগ্রাউন্ড ছবি।', 'ru' => 'Недопустимое фоновое изображение QSL.', 'id' => 'Gambar latar QSL tidak valid.'],
        'qsl_bg_not_supported' => ['fr' => 'Image de fond non supportée (JPG, PNG ou WEBP).', 'en' => 'Unsupported background image (JPG, PNG or WEBP).', 'de' => 'Nicht unterstütztes Hintergrundbild (JPG, PNG oder WEBP).', 'nl' => 'Niet-ondersteunde achtergrondafbeelding (JPG, PNG of WEBP).', 'es' => 'Imagen de fondo no compatible (JPG, PNG o WEBP).', 'it' => 'Immagine di sfondo non supportata (JPG, PNG o WEBP).', 'pt' => 'Imagem de fundo não suportada (JPG, PNG ou WEBP).', 'ar' => 'صورة الخلفية غير مدعومة (JPG أو PNG أو WEBP).', 'hi' => 'असमर्थित पृष्ठभूमि छवि (JPG, PNG या WEBP)।', 'ja' => '未対応の背景画像です（JPG、PNG、WEBP）。', 'zh' => '不支持的背景图片（JPG、PNG 或 WEBP）。', 'bn' => 'অসমর্থিত ব্যাকগ্রাউন্ড ছবি (JPG, PNG বা WEBP)।', 'ru' => 'Неподдерживаемое фоновое изображение (JPG, PNG или WEBP).', 'id' => 'Gambar latar tidak didukung (JPG, PNG, atau WEBP).'],
        'qsl_bg_too_large' => ['fr' => 'Image de fond trop volumineuse (max 6 Mo).', 'en' => 'Background image is too large (max 6 MB).', 'de' => 'Hintergrundbild ist zu groß (max. 6 MB).', 'nl' => 'Achtergrondafbeelding is te groot (max. 6 MB).', 'es' => 'La imagen de fondo es demasiado grande (máx. 6 MB).', 'it' => 'L’immagine di sfondo è troppo grande (max 6 MB).', 'pt' => 'A imagem de fundo é demasiado grande (máx. 6 MB).', 'ar' => 'صورة الخلفية كبيرة جدًا (الحد الأقصى 6 ميغابايت).', 'hi' => 'पृष्ठभूमि छवि बहुत बड़ी है (अधिकतम 6 MB)।', 'ja' => '背景画像が大きすぎます（最大6MB）。', 'zh' => '背景图片过大（最大 6MB）。', 'bn' => 'ব্যাকগ্রাউন্ড ছবি খুব বড় (সর্বোচ্চ 6 MB)।', 'ru' => 'Фоновое изображение слишком большое (макс. 6 МБ).', 'id' => 'Gambar latar terlalu besar (maks 6 MB).'],
        'qsl_bg_unreadable' => ['fr' => 'Image de fond QSL illisible.', 'en' => 'QSL background image is unreadable.', 'de' => 'QSL-Hintergrundbild ist unlesbar.', 'nl' => 'QSL-achtergrondafbeelding is onleesbaar.', 'es' => 'La imagen de fondo QSL no se puede leer.', 'it' => 'L’immagine di sfondo QSL non è leggibile.', 'pt' => 'A imagem de fundo QSL está ilegível.', 'ar' => 'صورة خلفية QSL غير قابلة للقراءة.', 'hi' => 'QSL पृष्ठभूमि छवि पढ़ी नहीं जा सकती।', 'ja' => 'QSL 背景画像を読み取れません。', 'zh' => 'QSL 背景图片无法读取。', 'bn' => 'QSL ব্যাকগ্রাউন্ড ছবি পড়া যাচ্ছে না।', 'ru' => 'Фоновое изображение QSL не читается.', 'id' => 'Gambar latar QSL tidak dapat dibaca.'],
        'invalid_csrf_token' => ['fr' => 'Jeton CSRF invalide.', 'en' => 'Invalid CSRF token.', 'de' => 'Ungültiges CSRF-Token.', 'nl' => 'Ongeldig CSRF-token.', 'es' => 'Token CSRF no válido.', 'it' => 'Token CSRF non valido.', 'pt' => 'Token CSRF inválido.', 'ar' => 'رمز CSRF غير صالح.', 'hi' => 'अमान्य CSRF टोकन।', 'ja' => '無効な CSRF トークンです。', 'zh' => '无效的 CSRF 令牌。', 'bn' => 'অবৈধ CSRF টোকেন।', 'ru' => 'Недействительный CSRF-токен.', 'id' => 'Token CSRF tidak valid.'],
        'too_many_login_attempts' => ['fr' => 'Trop de tentatives de connexion. Réessayez plus tard.', 'en' => 'Too many login attempts. Please try again later.', 'de' => 'Zu viele Anmeldeversuche. Bitte später erneut versuchen.', 'nl' => 'Te veel inlogpogingen. Probeer het later opnieuw.', 'es' => 'Demasiados intentos de inicio de sesión. Inténtelo más tarde.', 'it' => 'Troppi tentativi di accesso. Riprova più tardi.', 'pt' => 'Muitas tentativas de início de sessão. Tente novamente mais tarde.', 'ar' => 'محاولات تسجيل دخول كثيرة جدًا. يرجى المحاولة لاحقًا.', 'hi' => 'लॉगिन के बहुत अधिक प्रयास हुए। कृपया बाद में पुनः प्रयास करें।', 'ja' => 'ログイン試行回数が多すぎます。しばらくしてから再試行してください。', 'zh' => '登录尝试次数过多，请稍后再试。', 'bn' => 'লগইনের চেষ্টা খুব বেশি হয়েছে। অনুগ্রহ করে পরে আবার চেষ্টা করুন।', 'ru' => 'Слишком много попыток входа. Повторите позже.', 'id' => 'Terlalu banyak percobaan masuk. Silakan coba lagi nanti.'],
        'invalid_url' => ['fr' => 'URL invalide.', 'en' => 'Invalid URL.', 'de' => 'Ungültige URL.', 'nl' => 'Ongeldige URL.', 'es' => 'URL no válida.', 'it' => 'URL non valido.', 'pt' => 'URL inválido.', 'ar' => 'رابط غير صالح.', 'hi' => 'अमान्य URL।', 'ja' => '無効なURLです。', 'zh' => '无效的 URL。', 'bn' => 'অবৈধ URL।', 'ru' => 'Недопустимый URL.', 'id' => 'URL tidak valid.'],
        'invalid_relative_url' => ['fr' => 'URL relative invalide.', 'en' => 'Invalid relative URL.', 'de' => 'Ungültige relative URL.', 'nl' => 'Ongeldige relatieve URL.', 'es' => 'URL relativa no válida.', 'it' => 'URL relativo non valido.', 'pt' => 'URL relativo inválido.', 'ar' => 'رابط نسبي غير صالح.', 'hi' => 'अमान्य सापेक्ष URL।', 'ja' => '無効な相対URLです。', 'zh' => '无效的相对 URL。', 'bn' => 'অবৈধ রিলেটিভ URL।', 'ru' => 'Недопустимый относительный URL.', 'id' => 'URL relatif tidak valid.'],
        'only_http_https_allowed' => ['fr' => 'Seules les URL HTTP et HTTPS sont autorisées.', 'en' => 'Only HTTP and HTTPS URLs are allowed.', 'de' => 'Nur HTTP- und HTTPS-URLs sind erlaubt.', 'nl' => 'Alleen HTTP- en HTTPS-URL’s zijn toegestaan.', 'es' => 'Solo se permiten URL HTTP y HTTPS.', 'it' => 'Sono consentiti solo URL HTTP e HTTPS.', 'pt' => 'Apenas URLs HTTP e HTTPS são permitidos.', 'ar' => 'يُسمح فقط بروابط HTTP وHTTPS.', 'hi' => 'केवल HTTP और HTTPS URL की अनुमति है।', 'ja' => 'HTTP および HTTPS のURLのみ許可されています。', 'zh' => '仅允许 HTTP 和 HTTPS URL。', 'bn' => 'শুধুমাত্র HTTP এবং HTTPS URL অনুমোদিত।', 'ru' => 'Разрешены только URL HTTP и HTTPS.', 'id' => 'Hanya URL HTTP dan HTTPS yang diizinkan.'],
        'invalid_product' => ['fr' => 'Produit invalide.', 'en' => 'Invalid product.', 'de' => 'Ungültiges Produkt.', 'nl' => 'Ongeldig product.', 'es' => 'Producto no válido.', 'it' => 'Prodotto non valido.', 'pt' => 'Produto inválido.', 'ar' => 'منتج غير صالح.', 'hi' => 'अमान्य उत्पाद।', 'ja' => '無効な商品です。', 'zh' => '无效商品。', 'bn' => 'অবৈধ পণ্য।', 'ru' => 'Недопустимый товар.', 'id' => 'Produk tidak valid.'],
        'product_unavailable' => ['fr' => 'Produit indisponible.', 'en' => 'Product unavailable.', 'de' => 'Produkt nicht verfügbar.', 'nl' => 'Product niet beschikbaar.', 'es' => 'Producto no disponible.', 'it' => 'Prodotto non disponibile.', 'pt' => 'Produto indisponível.', 'ar' => 'المنتج غير متاح.', 'hi' => 'उत्पाद उपलब्ध नहीं है।', 'ja' => '商品は利用できません。', 'zh' => '商品不可用。', 'bn' => 'পণ্য উপলভ্য নয়।', 'ru' => 'Товар недоступен.', 'id' => 'Produk tidak tersedia.'],
        'invalid_product_in_cart' => ['fr' => 'Produit invalide dans le panier.', 'en' => 'Invalid product in cart.', 'de' => 'Ungültiges Produkt im Warenkorb.', 'nl' => 'Ongeldig product in de winkelwagen.', 'es' => 'Producto no válido en el carrito.', 'it' => 'Prodotto non valido nel carrello.', 'pt' => 'Produto inválido no carrinho.', 'ar' => 'منتج غير صالح في السلة.', 'hi' => 'कार्ट में अमान्य उत्पाद।', 'ja' => 'カート内に無効な商品があります。', 'zh' => '购物车中有无效商品。', 'bn' => 'কার্টে অবৈধ পণ্য।', 'ru' => 'Недопустимый товар в корзине.', 'id' => 'Produk tidak valid di keranjang.'],
        'cart_empty' => ['fr' => 'Le panier est vide.', 'en' => 'Cart is empty.', 'de' => 'Der Warenkorb ist leer.', 'nl' => 'Winkelwagen is leeg.', 'es' => 'El carrito está vacío.', 'it' => 'Il carrello è vuoto.', 'pt' => 'O carrinho está vazio.', 'ar' => 'سلة التسوق فارغة.', 'hi' => 'कार्ट खाली है।', 'ja' => 'カートは空です。', 'zh' => '购物车为空。', 'bn' => 'কার্ট খালি।', 'ru' => 'Корзина пуста.', 'id' => 'Keranjang kosong.'],
        'insufficient_stock_for' => ['fr' => 'Stock insuffisant pour ', 'en' => 'Insufficient stock for ', 'de' => 'Unzureichender Lagerbestand für ', 'nl' => 'Onvoldoende voorraad voor ', 'es' => 'Stock insuficiente para ', 'it' => 'Scorte insufficienti per ', 'pt' => 'Stock insuficiente para ', 'ar' => 'المخزون غير كافٍ لـ ', 'hi' => 'के लिए स्टॉक अपर्याप्त है: ', 'ja' => '在庫不足: ', 'zh' => '库存不足：', 'bn' => 'এর জন্য পর্যাপ্ত স্টক নেই: ', 'ru' => 'Недостаточно товара для ', 'id' => 'Stok tidak mencukupi untuk '],
    ];
    return $messages[$key][$locale] ?? $messages[$key]['fr'] ?? '';
}

function assert_upload_file_is_valid_signature(string $tmpPath, array $allowedExtensions): void
{
    $signature = @file_get_contents($tmpPath, false, null, 0, 16);
    if ($signature === false) {
        throw new RuntimeException(upload_i18n_message('uploaded_unreadable'));
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

    throw new RuntimeException(upload_i18n_message('invalid_signature'));
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
        throw new RuntimeException(upload_i18n_message('upload_failed'));
    }

    $tmpPath = (string) ($upload['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException(upload_i18n_message('upload_invalid'));
    }

    $size = (int) ($upload['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        throw new RuntimeException(upload_i18n_message('file_too_large_or_empty'));
    }

    $originalName = (string) ($upload['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException(upload_i18n_message('extension_not_allowed'));
    }

    $mime = detect_uploaded_mime_type($tmpPath);
    if (!in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException(upload_i18n_message('mime_not_allowed'));
    }
    assert_upload_file_is_valid_signature($tmpPath, $allowedExtensions);

    $sanitizedTmpPath = $tmpPath;
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $sanitizedTmpPath = sanitize_uploaded_image_file($tmpPath, $extension);
    }

    if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0755, true) && !is_dir($destinationDirectory)) {
        throw new RuntimeException(upload_i18n_message('cannot_create_destination_dir'));
    }

    $filename = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destinationPath = rtrim($destinationDirectory, '/') . '/' . $filename;
    $moved = $sanitizedTmpPath === $tmpPath
        ? move_uploaded_file($tmpPath, $destinationPath)
        : rename($sanitizedTmpPath, $destinationPath);
    if (!$moved) {
        throw new RuntimeException(upload_i18n_message('cannot_move_uploaded_file'));
    }

    @chmod($destinationPath, 0644);
    return $filename;
}

function sanitize_uploaded_image_file(string $tmpPath, string $extension): string
{
    $raw = @file_get_contents($tmpPath);
    if ($raw === false) {
        throw new RuntimeException(upload_i18n_message('uploaded_image_unreadable'));
    }

    if (!function_exists('imagecreatefromstring')) {
        return $tmpPath;
    }

    $image = @imagecreatefromstring($raw);
    if ($image === false) {
        throw new RuntimeException(upload_i18n_message('uploaded_image_invalid'));
    }

    $outputPath = tempnam(sys_get_temp_dir(), 'on4crd-img-');
    if ($outputPath === false) {
        imagedestroy($image);
        throw new RuntimeException(upload_i18n_message('cannot_create_temp_file'));
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
        throw new RuntimeException(upload_i18n_message('image_metadata_cleanup_failed'));
    }

    return $outputPath;
}

function handle_album_upload(?array $upload, string $callsign): string
{
    if (!is_array($upload)) {
        throw new RuntimeException(upload_i18n_message('missing_image'));
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
        throw new RuntimeException(upload_i18n_message('too_many_login_attempts'));
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
        throw new RuntimeException(upload_i18n_message('invalid_url'));
    }

    if ($allowRelative && str_starts_with($trimmed, '//')) {
        throw new RuntimeException(upload_i18n_message('invalid_relative_url'));
    }

    if ($allowRelative && preg_match('~^(?:/|\./|\../|\?|#)~', $trimmed) === 1) {
        return $trimmed;
    }

    if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
        throw new RuntimeException(upload_i18n_message('invalid_url'));
    }

    $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new RuntimeException(upload_i18n_message('only_http_https_allowed'));
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
    $locale = current_locale();
    $tr = static function (string $key) use ($locale): string {
        $messages = [
            'lot_not_found' => [
                'fr' => 'Lot introuvable.',
                'en' => 'Lot not found.',
                'de' => 'Los nicht gefunden.',
                'nl' => 'Kavel niet gevonden.',
                'es' => 'Lote no encontrado.',
                'it' => 'Lotto non trovato.',
                'pt' => 'Lote não encontrado.',
                'ar' => 'لم يتم العثور على الدفعة.',
                'hi' => 'लॉट नहीं मिला।',
                'ja' => 'ロットが見つかりません。',
                'zh' => '未找到拍卖批次。',
                'bn' => 'লট পাওয়া যায়নি।',
                'ru' => 'Лот не найден.',
                'id' => 'Lot tidak ditemukan.',
            ],
            'auction_not_active' => [
                'fr' => 'Cette enchère n’est pas active.',
                'en' => 'This auction is not active.',
                'de' => 'Diese Auktion ist nicht aktiv.',
                'nl' => 'Deze veiling is niet actief.',
                'es' => 'Esta subasta no está activa.',
                'it' => 'Questa asta non è attiva.',
                'pt' => 'Este leilão não está ativo.',
                'ar' => 'هذا المزاد غير نشط.',
                'hi' => 'यह नीलामी सक्रिय नहीं है।',
                'ja' => 'このオークションは現在アクティブではありません。',
                'zh' => '此拍卖当前未激活。',
                'bn' => 'এই নিলামটি সক্রিয় নয়।',
                'ru' => 'Этот аукцион не активен.',
                'id' => 'Lelang ini tidak aktif.',
            ],
            'min_bid_prefix' => [
                'fr' => 'Le montant minimum pour enchérir est ',
                'en' => 'The minimum bid amount is ',
                'de' => 'Der Mindestgebotsbetrag ist ',
                'nl' => 'Het minimumbedrag om te bieden is ',
                'es' => 'El importe mínimo para pujar es ',
                'it' => 'L’importo minimo per fare un’offerta è ',
                'pt' => 'O valor mínimo para licitar é ',
                'ar' => 'الحد الأدنى للمزايدة هو ',
                'hi' => 'बोली लगाने की न्यूनतम राशि है ',
                'ja' => '入札の最低金額は ',
                'zh' => '最低出价金额为 ',
                'bn' => 'বিড করার সর্বনিম্ন পরিমাণ হলো ',
                'ru' => 'Минимальная ставка составляет ',
                'id' => 'Jumlah tawaran minimum adalah ',
            ],
            'concurrency_conflict' => [
                'fr' => 'Conflit de concurrence sur l’enchère. Veuillez réessayer.',
                'en' => 'Concurrent bid conflict. Please try again.',
                'de' => 'Konflikt bei gleichzeitigen Geboten. Bitte erneut versuchen.',
                'nl' => 'Conflict door gelijktijdige biedingen. Probeer opnieuw.',
                'es' => 'Conflicto por pujas simultáneas. Inténtelo de nuevo.',
                'it' => 'Conflitto di offerte simultanee. Riprova.',
                'pt' => 'Conflito de licitações simultâneas. Tente novamente.',
                'ar' => 'تعارض بسبب مزايدات متزامنة. يرجى المحاولة مرة أخرى.',
                'hi' => 'समकालिक बोलियों के कारण टकराव। कृपया पुनः प्रयास करें।',
                'ja' => '同時入札の競合が発生しました。再試行してください。',
                'zh' => '并发出价冲突，请重试。',
                'bn' => 'একই সময়ে বিডের দ্বন্দ্ব হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।',
                'ru' => 'Конфликт параллельных ставок. Попробуйте снова.',
                'id' => 'Terjadi konflik tawaran bersamaan. Silakan coba lagi.',
            ],
        ];
        return $messages[$key][$locale] ?? $messages[$key]['fr'];
    };

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
            throw new RuntimeException($tr('lot_not_found'));
        }

        $status = auction_runtime_status($lot);
        if ($status !== 'active') {
            throw new RuntimeException($tr('auction_not_active'));
        }

        $minimum = auction_minimum_bid_cents($lot);
        if ($amountCents < $minimum) {
            throw new RuntimeException($tr('min_bid_prefix') . format_price_eur($minimum) . '.');
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
            throw new RuntimeException($tr('concurrency_conflict'));
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
        'es' => ['layout' => 'Administración', 'title' => 'Administración centralizada', 'lead' => 'Todos los módulos y herramientas de administración se agrupan en este panel único.', 'search_label' => 'Búsqueda rápida', 'search_placeholder' => 'Módulo, herramienta, descripción…', 'search_cta' => 'Filtrar', 'search_reset' => 'Restablecer', 'empty' => 'Ningún módulo coincide con su búsqueda.'],
        'it' => ['layout' => 'Amministrazione', 'title' => 'Amministrazione centralizzata', 'lead' => 'Tutti i moduli e gli strumenti di amministrazione sono raccolti in questa dashboard unica.', 'search_label' => 'Ricerca rapida', 'search_placeholder' => 'Modulo, strumento, descrizione…', 'search_cta' => 'Filtra', 'search_reset' => 'Reimposta', 'empty' => 'Nessun modulo corrisponde alla ricerca.'],
        'pt' => ['layout' => 'Administração', 'title' => 'Administração centralizada', 'lead' => 'Todos os módulos e ferramentas de administração estão agrupados neste painel único.', 'search_label' => 'Pesquisa rápida', 'search_placeholder' => 'Módulo, ferramenta, descrição…', 'search_cta' => 'Filtrar', 'search_reset' => 'Repor', 'empty' => 'Nenhum módulo corresponde à sua pesquisa.'],
    ];

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

    return $resolved;
}

/**
 * @return array<int, array{
 *   route:string,
 *   title:array<string,string>,
 *   desc:array<string,string>,
 *   module?:string,
 *   permission?:string,
 *   audience?:array<string,string>,
 *   icon?:array<string,string>|string
 * }>
 */
function admin_module_cards_catalog(): array
{
    $catalog = [
        ['route' => 'admin_modules', 'title' => ['fr' => 'Modules', 'en' => 'Modules', 'de' => 'Module', 'nl' => 'Modules', 'es' => 'Módulos', 'it' => 'Moduli', 'pt' => 'Módulos', 'ar' => 'الوحدات', 'bn' => 'মডিউল', 'hi' => 'मॉड्यूल', 'id' => 'Modul', 'ja' => 'モジュール', 'ru' => 'Модули', 'zh' => '模块'], 'desc' => ['fr' => 'Activation, désactivation et pilotage global des modules.', 'en' => 'Enable, disable and globally manage modules.', 'de' => 'Module aktivieren, deaktivieren und zentral steuern.', 'nl' => 'Modules activeren, deactiveren en centraal beheren.', 'es' => 'Activar, desactivar y gestionar globalmente los módulos.', 'it' => 'Attiva, disattiva e gestisci globalmente i moduli.', 'pt' => 'Ative, desative e gerencie globalmente os módulos.', 'ar' => 'تفعيل وتعطيل وإدارة الوحدات بشكل عام.', 'bn' => 'মডিউল সক্রিয়, নিষ্ক্রিয় ও কেন্দ্রীয়ভাবে পরিচালনা করুন।', 'hi' => 'मॉड्यूल सक्षम/अक्षम करें और वैश्विक रूप से प्रबंधित करें।', 'id' => 'Aktifkan, nonaktifkan, dan kelola modul secara global.', 'ja' => 'モジュールの有効化・無効化と全体管理。', 'ru' => 'Включение, отключение и общее управление модулями.', 'zh' => '启用、禁用并统一管理模块。'], 'permission' => 'modules.manage'],
        ['route' => 'admin_members', 'title' => ['fr' => 'Gestion des membres', 'en' => 'Member management', 'de' => 'Mitgliederverwaltung', 'nl' => 'Ledenbeheer', 'es' => 'Gestión de socios', 'it' => 'Gestione membri', 'pt' => 'Gestão de membros', 'ar' => 'إدارة الأعضاء', 'bn' => 'সদস্য ব্যবস্থাপনা', 'hi' => 'सदस्य प्रबंधन', 'id' => 'Manajemen anggota', 'ja' => 'メンバー管理', 'ru' => 'Управление участниками', 'zh' => '成员管理'], 'desc' => ['fr' => 'Profils membres, statut actif et comité.', 'en' => 'Member profiles, active status and committee.', 'de' => 'Mitgliederprofile, Aktivstatus und Komitee.', 'nl' => 'Ledenprofielen, actieve status en comité.', 'es' => 'Perfiles de socios, estado activo y comité.', 'it' => 'Profili membri, stato attivo e comitato.', 'pt' => 'Perfis de membros, estado ativo e comité.', 'ar' => 'ملفات الأعضاء والحالة النشطة واللجنة.', 'bn' => 'সদস্য প্রোফাইল, সক্রিয় অবস্থা ও কমিটি।', 'hi' => 'सदस्य प्रोफ़ाइल, सक्रिय स्थिति और समिति।', 'id' => 'Profil anggota, status aktif, dan komite.', 'ja' => 'メンバープロファイル、アクティブ状態、委員会。', 'ru' => 'Профили участников, активный статус и комитет.', 'zh' => '成员档案、活跃状态和委员会。']],
        ['route' => 'admin_permissions', 'title' => ['fr' => 'Rôles & permissions', 'en' => 'Roles & permissions', 'de' => 'Rollen & Berechtigungen', 'nl' => 'Rollen & rechten', 'es' => 'Roles y permisos', 'it' => 'Ruoli e permessi', 'pt' => 'Funções e permissões', 'ar' => 'الأدوار والصلاحيات', 'bn' => 'ভূমিকা ও অনুমতি', 'hi' => 'भूमिकाएँ और अनुमतियाँ', 'id' => 'Peran & izin', 'ja' => 'ロールと権限', 'ru' => 'Роли и разрешения', 'zh' => '角色与权限'], 'desc' => ['fr' => 'Attribution des rôles et matrice des permissions.', 'en' => 'Role assignment and permissions matrix.', 'de' => 'Rollenzuweisung und Berechtigungsmatrix.', 'nl' => 'Roltoewijzing en rechtenmatrix.', 'es' => 'Asignación de roles y matriz de permisos.', 'it' => 'Assegnazione dei ruoli e matrice dei permessi.', 'pt' => 'Atribuição de funções e matriz de permissões.', 'ar' => 'تعيين الأدوار ومصفوفة الصلاحيات.', 'bn' => 'ভূমিকা নির্ধারণ ও অনুমতি ম্যাট্রিক্স।', 'hi' => 'भूमिका असाइनमेंट और अनुमति मैट्रिक्स।', 'id' => 'Penetapan peran dan matriks izin.', 'ja' => 'ロール割り当てと権限マトリクス。', 'ru' => 'Назначение ролей и матрица разрешений.', 'zh' => '角色分配与权限矩阵。']],
        ['route' => 'admin_news', 'title' => ['fr' => 'Actualités', 'en' => 'News', 'de' => 'Neuigkeiten', 'nl' => 'Nieuws', 'es' => 'Noticias', 'it' => 'Notizie', 'pt' => 'Notícias', 'ar' => 'الأخبار', 'bn' => 'সংবাদ', 'hi' => 'समाचार', 'id' => 'Berita', 'ja' => 'ニュース', 'ru' => 'Новости', 'zh' => '新闻'], 'desc' => ['fr' => 'Sections, rédaction et modération.', 'en' => 'Sections, writing and moderation.', 'de' => 'Bereiche, Redaktion und Moderation.', 'nl' => 'Secties, redactie en moderatie.', 'es' => 'Secciones, redacción y moderación.', 'it' => 'Sezioni, redazione e moderazione.', 'pt' => 'Secções, redação e moderação.', 'ar' => 'الأقسام والتحرير والإشراف.', 'bn' => 'সেকশন, লেখা ও মডারেশন।', 'hi' => 'सेक्शन, लेखन और मॉडरेशन।', 'id' => 'Bagian, penulisan, dan moderasi.', 'ja' => 'セクション、執筆、モデレーション。', 'ru' => 'Разделы, редактура и модерация.', 'zh' => '栏目、撰写与审核。'], 'module' => 'news'],
        ['route' => 'admin_articles', 'title' => ['fr' => 'Articles', 'en' => 'Articles', 'de' => 'Artikel', 'nl' => 'Artikels', 'es' => 'Artículos', 'it' => 'Articoli', 'pt' => 'Artigos', 'ar' => 'المقالات', 'bn' => 'প্রবন্ধ', 'hi' => 'लेख', 'id' => 'Artikel', 'ja' => '記事', 'ru' => 'Статьи', 'zh' => '文章'], 'desc' => ['fr' => 'Articles techniques publics.', 'en' => 'Public technical articles.', 'de' => 'Öffentliche technische Artikel.', 'nl' => 'Publieke technische artikels.', 'es' => 'Artículos técnicos públicos.', 'it' => 'Articoli tecnici pubblici.', 'pt' => 'Artigos técnicos públicos.', 'ar' => 'مقالات تقنية عامة.', 'bn' => 'সর্বসাধারণের জন্য প্রযুক্তিগত প্রবন্ধ।', 'hi' => 'सार्वजनिक तकनीकी लेख।', 'id' => 'Artikel teknis publik.', 'ja' => '公開技術記事。', 'ru' => 'Публичные технические статьи.', 'zh' => '公开技术文章。'], 'module' => 'articles'],
        ['route' => 'admin_committee', 'title' => ['fr' => 'Comité', 'en' => 'Committee', 'de' => 'Komitee', 'nl' => 'Comité', 'es' => 'Comité', 'it' => 'Comitato', 'pt' => 'Comité', 'ar' => 'اللجنة', 'bn' => 'কমিটি', 'hi' => 'समिति', 'id' => 'Komite', 'ja' => '委員会', 'ru' => 'Комитет', 'zh' => '委员会'], 'desc' => ['fr' => 'Membres du comité, rôle, ordre et biographie.', 'en' => 'Committee members, role, order and biography.', 'de' => 'Komiteemitglieder, Rolle, Reihenfolge und Biografie.', 'nl' => 'Comitéleden, rol, volgorde en biografie.', 'es' => 'Miembros del comité, rol, orden y biografía.', 'it' => 'Membri del comitato, ruolo, ordine e biografia.', 'pt' => 'Membros do comité, função, ordem e biografia.', 'ar' => 'أعضاء اللجنة والأدوار والترتيب والسيرة.', 'bn' => 'কমিটির সদস্য, ভূমিকা, ক্রম ও জীবনী।', 'hi' => 'समिति सदस्य, भूमिका, क्रम और जीवनी।', 'id' => 'Anggota komite, peran, urutan, dan biografi.', 'ja' => '委員会メンバー、役割、順序、経歴。', 'ru' => 'Члены комитета, роль, порядок и биография.', 'zh' => '委员会成员、角色、顺序和简介。'], 'module' => 'committee'],
        ['route' => 'admin_press', 'title' => ['fr' => 'Presse', 'en' => 'Press', 'de' => 'Presse', 'nl' => 'Pers', 'es' => 'Prensa', 'it' => 'Stampa', 'pt' => 'Imprensa', 'ar' => 'الصحافة', 'bn' => 'প্রেস', 'hi' => 'प्रेस', 'id' => 'Pers', 'ja' => 'プレス', 'ru' => 'Пресса', 'zh' => '媒体'], 'desc' => ['fr' => 'Contacts presse, communiqués datés et documents téléchargeables.', 'en' => 'Press contacts, dated releases and downloadable documents.', 'de' => 'Pressekontakte, datierte Mitteilungen und Downloads.', 'nl' => 'Perscontacten, gedateerde berichten en downloads.', 'es' => 'Contactos de prensa, comunicados fechados y documentos descargables.', 'it' => 'Contatti stampa, comunicati datati e documenti scaricabili.', 'pt' => 'Contactos de imprensa, comunicados datados e documentos descarregáveis.', 'ar' => 'جهات اتصال الصحافة وبيانات مؤرخة ووثائق قابلة للتنزيل.', 'bn' => 'প্রেস যোগাযোগ, তারিখযুক্ত বিজ্ঞপ্তি ও ডাউনলোডযোগ্য নথি।', 'hi' => 'प्रेस संपर्क, दिनांकित विज्ञप्तियाँ और डाउनलोड करने योग्य दस्तावेज़।', 'id' => 'Kontak pers, rilis bertanggal, dan dokumen yang dapat diunduh.', 'ja' => '報道連絡先、日付付きリリース、ダウンロード可能な資料。', 'ru' => 'Контакты для прессы, датированные релизы и загружаемые документы.', 'zh' => '媒体联系人、带日期的公告和可下载文档。'], 'module' => 'press'],
        ['route' => 'admin_events', 'title' => ['fr' => 'Agenda', 'en' => 'Agenda', 'de' => 'Agenda', 'nl' => 'Agenda', 'es' => 'Agenda', 'it' => 'Agenda', 'pt' => 'Agenda', 'ar' => 'الجدول', 'bn' => 'সূচি', 'hi' => 'कार्यसूची', 'id' => 'Kalender kegiatan', 'ja' => '予定', 'ru' => 'Повестка', 'zh' => '日程'], 'desc' => ['fr' => 'Événements du club et contests locaux affichés dans les widgets live.', 'en' => 'Club events and local contests shown in live widgets.', 'de' => 'Clubveranstaltungen und lokale Contests in Live-Widgets.', 'nl' => 'Clubevenementen en lokale contests in live widgets.', 'es' => 'Eventos del club y concursos locales mostrados en widgets en vivo.', 'it' => 'Eventi del club e contest locali mostrati nei widget live.', 'pt' => 'Eventos do clube e concursos locais exibidos em widgets ao vivo.', 'ar' => 'فعاليات النادي والمسابقات المحلية المعروضة في الأدوات الحية.', 'bn' => 'লাইভ উইজেটে প্রদর্শিত ক্লাব ইভেন্ট ও স্থানীয় কনটেস্ট।', 'hi' => 'लाइव विजेट्स में दिखाए जाने वाले क्लब इवेंट और स्थानीय प्रतियोगिताएँ।', 'id' => 'Acara klub dan kontes lokal yang ditampilkan di widget langsung.', 'ja' => 'ライブウィジェットに表示されるクラブイベントと地域コンテスト。', 'ru' => 'События клуба и местные контесты в live-виджетах.', 'zh' => '在实时小组件中显示的俱乐部活动和本地竞赛。'], 'module' => 'events'],
        ['route' => 'admin_dinner_reservations', 'title' => ['fr' => 'Dîner annuel', 'en' => 'Annual dinner', 'de' => 'Jahresessen', 'nl' => 'Jaarlijks diner', 'es' => 'Cena anual', 'it' => 'Cena annuale', 'pt' => 'Jantar anual', 'ar' => 'العشاء السنوي', 'bn' => 'বার্ষিক ডিনার', 'hi' => 'वार्षिक रात्रिभोज', 'id' => 'Makan malam tahunan', 'ja' => '年次ディナー', 'ru' => 'Ежегодный ужин', 'zh' => '年度晚宴'], 'desc' => ['fr' => 'Réservations, lignes repas/dessert, quantités et total automatique.', 'en' => 'Reservations, meal/dessert lines, quantities and auto total.', 'de' => 'Reservierungen, Menüzeilen, Mengen und automatische Summe.', 'nl' => 'Reservaties, maaltijdregels, aantallen en automatisch totaal.', 'es' => 'Reservas, líneas de comida/postre, cantidades y total automático.', 'it' => 'Prenotazioni, voci pasto/dessert, quantità e totale automatico.', 'pt' => 'Reservas, linhas de refeição/sobremesa, quantidades e total automático.', 'ar' => 'الحجوزات وبنود الوجبات/الحلوى والكميات والإجمالي التلقائي.', 'bn' => 'রিজার্ভেশন, খাবার/ডেজার্ট লাইন, পরিমাণ ও স্বয়ংক্রিয় মোট।', 'hi' => 'आरक्षण, भोजन/डेज़र्ट पंक्तियाँ, मात्राएँ और स्वचालित कुल।', 'id' => 'Reservasi, baris menu/hidangan penutup, jumlah, dan total otomatis.', 'ja' => '予約、食事/デザート項目、数量、自動合計。', 'ru' => 'Бронирования, позиции меню/десерта, количества и автосумма.', 'zh' => '预订、餐食/甜点条目、数量和自动总计。'], 'module' => 'events', 'permission' => 'events.manage'],
        ['route' => 'admin_auctions', 'title' => ['fr' => 'Enchères', 'en' => 'Auctions', 'de' => 'Auktionen', 'nl' => 'Veilingen', 'es' => 'Subastas', 'it' => 'Aste', 'pt' => 'Leilões', 'ar' => 'المزادات', 'bn' => 'নিলাম', 'hi' => 'नीलामी', 'id' => 'Lelang', 'ja' => 'オークション', 'ru' => 'Аукционы', 'zh' => '拍卖'], 'desc' => ['fr' => 'Lots, planification, offres et clôture.', 'en' => 'Lots, scheduling, bids and closing.', 'de' => 'Lose, Planung, Gebote und Abschluss.', 'nl' => 'Kavels, planning, biedingen en afsluiting.', 'es' => 'Lotes, planificación, pujas y cierre.', 'it' => 'Lotti, pianificazione, offerte e chiusura.', 'pt' => 'Lotes, planeamento, licitações e fecho.', 'ar' => 'الدفعات والجدولة والعروض والإغلاق.', 'bn' => 'লট, পরিকল্পনা, বিড ও সমাপ্তি।', 'hi' => 'लॉट, शेड्यूलिंग, बोलियाँ और समापन।', 'id' => 'Lot, penjadwalan, penawaran, dan penutupan.', 'ja' => 'ロット、スケジュール、入札、終了。', 'ru' => 'Лоты, планирование, ставки и закрытие.', 'zh' => '拍品、排期、出价与结束。'], 'module' => 'auctions', 'permission' => 'auctions.manage'],
        ['route' => 'admin_editorial', 'title' => ['fr' => 'Éditorial multilingue', 'en' => 'Multilingual editorial', 'de' => 'Mehrsprachige Redaktion', 'nl' => 'Meertalige redactie', 'es' => 'Editorial multilingüe', 'it' => 'Editoriale multilingue', 'pt' => 'Editorial multilíngue', 'ar' => 'تحرير متعدد اللغات', 'bn' => 'বহুভাষিক সম্পাদকীয়', 'hi' => 'बहुभाषी संपादकीय', 'id' => 'Editorial multibahasa', 'ja' => '多言語エディトリアル', 'ru' => 'Многоязычная редакция', 'zh' => '多语言编辑'], 'desc' => ['fr' => 'Français source, traduction auto EN/DE/NL et relecture manuelle.', 'en' => 'French source, EN/DE/NL auto translation and manual review.', 'de' => 'Französische Quelle, automatische Übersetzung und Review.', 'nl' => 'Franse bron, automatische vertaling en manuele review.', 'es' => 'Fuente en francés, traducción automática EN/DE/NL y revisión manual.', 'it' => 'Fonte francese, traduzione automatica EN/DE/NL e revisione manuale.', 'pt' => 'Fonte em francês, tradução automática EN/DE/NL e revisão manual.', 'ar' => 'مصدر بالفرنسية مع ترجمة آلية EN/DE/NL ومراجعة يدوية.', 'bn' => 'ফরাসি সোর্স, EN/DE/NL স্বয়ংক্রিয় অনুবাদ ও ম্যানুয়াল রিভিউ।', 'hi' => 'फ़्रेंच स्रोत, EN/DE/NL ऑटो अनुवाद और मैनुअल समीक्षा।', 'id' => 'Sumber bahasa Prancis, terjemahan otomatis EN/DE/NL, dan tinjauan manual.', 'ja' => 'フランス語原文、EN/DE/NL自動翻訳、手動レビュー。', 'ru' => 'Французский источник, авто-перевод EN/DE/NL и ручная проверка.', 'zh' => '法语源文，EN/DE/NL 自动翻译与人工校对。']],
        ['route' => 'admin_translation_reviews', 'title' => ['fr' => 'Relecture linguistique', 'en' => 'Translation reviews', 'de' => 'Sprachliche Prüfung', 'nl' => 'Taalreview', 'es' => 'Revisión de traducciones', 'it' => 'Revisione traduzioni', 'pt' => 'Revisão de traduções', 'ar' => 'مراجعة الترجمات', 'bn' => 'অনুবাদ পর্যালোচনা', 'hi' => 'अनुवाद समीक्षा', 'id' => 'Tinjauan terjemahan', 'ja' => '翻訳レビュー', 'ru' => 'Проверка переводов', 'zh' => '翻译审校'], 'desc' => ['fr' => 'Workflow de validation des traductions des actualités et articles.', 'en' => 'Validation workflow for news/article translations.', 'de' => 'Freigabe-Workflow für News-/Artikelübersetzungen.', 'nl' => 'Validatieworkflow voor vertalingen van nieuws/artikels.', 'es' => 'Flujo de validación para traducciones de noticias y artículos.', 'it' => 'Flusso di validazione per traduzioni di notizie e articoli.', 'pt' => 'Fluxo de validação para traduções de notícias e artigos.', 'ar' => 'سير عمل اعتماد ترجمات الأخبار والمقالات.', 'bn' => 'সংবাদ/প্রবন্ধ অনুবাদের যাচাইকরণ ওয়ার্কফ্লো।', 'hi' => 'समाचार/लेख अनुवाद के लिए सत्यापन कार्यप्रवाह।', 'id' => 'Alur validasi untuk terjemahan berita/artikel.', 'ja' => 'ニュース/記事翻訳の承認ワークフロー。', 'ru' => 'Процесс валидации переводов новостей и статей.', 'zh' => '新闻/文章翻译的校验工作流。']],
        ['route' => 'admin_dashboard', 'title' => ['fr' => 'Widgets dashboard', 'en' => 'Dashboard widgets', 'de' => 'Dashboard-Widgets', 'nl' => 'Dashboard-widgets', 'es' => 'Widgets del panel', 'it' => 'Widget dashboard', 'pt' => 'Widgets do painel', 'ar' => 'عناصر لوحة التحكم', 'bn' => 'ড্যাশবোর্ড উইজেট', 'hi' => 'डैशबोर्ड विजेट्स', 'id' => 'Widget dasbor', 'ja' => 'ダッシュボードウィジェット', 'ru' => 'Виджеты панели', 'zh' => '仪表板小组件'], 'desc' => ['fr' => 'Activation des widgets disponibles pour les membres.', 'en' => 'Enable widgets available to members.', 'de' => 'Aktivierung verfügbarer Widgets für Mitglieder.', 'nl' => 'Activeer widgets beschikbaar voor leden.', 'es' => 'Activar widgets disponibles para socios.', 'it' => 'Attiva i widget disponibili per i membri.', 'pt' => 'Ativar widgets disponíveis para membros.', 'ar' => 'تفعيل الأدوات المتاحة للأعضاء.', 'bn' => 'সদস্যদের জন্য উপলভ্য উইজেট সক্রিয় করুন।', 'hi' => 'सदस्यों के लिए उपलब्ध विजेट सक्रिय करें।', 'id' => 'Aktifkan widget yang tersedia untuk anggota.', 'ja' => 'メンバー向けウィジェットを有効化。', 'ru' => 'Включение доступных участникам виджетов.', 'zh' => '启用会员可用的小组件。'], 'module' => 'dashboard', 'permission' => 'admin.access'],
        ['route' => 'admin_live_feeds', 'title' => ['fr' => 'Flux live', 'en' => 'Live feeds', 'de' => 'Live-Feeds', 'nl' => 'Live feeds', 'es' => 'Feeds en vivo', 'it' => 'Feed live', 'pt' => 'Feeds ao vivo', 'ar' => 'التدفقات المباشرة', 'bn' => 'লাইভ ফিড', 'hi' => 'लाइव फ़ीड', 'id' => 'Feed langsung', 'ja' => 'ライブフィード', 'ru' => 'Живые ленты', 'zh' => '实时流'], 'desc' => ['fr' => 'Pilotage fin des flux radioamateur, TTL, URLs et activation.', 'en' => 'Fine control of radio feeds, TTL, URLs and activation.', 'de' => 'Feinsteuerung von Funk-Feeds, TTL, URLs und Aktivierung.', 'nl' => 'Fijn beheer van radiofeeds, TTL, URL’s en activatie.', 'es' => 'Control preciso de feeds de radio, TTL, URLs y activación.', 'it' => 'Controllo avanzato dei feed radio, TTL, URL e attivazione.', 'pt' => 'Controlo avançado de feeds de rádio, TTL, URLs e ativação.', 'ar' => 'تحكم دقيق في تدفقات الراديو وTTL وعناوين URL والتفعيل.', 'bn' => 'রেডিও ফিড, TTL, URL ও সক্রিয়করণের সূক্ষ্ম নিয়ন্ত্রণ।', 'hi' => 'रेडियो फ़ीड, TTL, URL और सक्रियण का सूक्ष्म नियंत्रण।', 'id' => 'Kontrol rinci feed radio, TTL, URL, dan aktivasi.', 'ja' => '無線フィード、TTL、URL、有効化の詳細管理。', 'ru' => 'Тонкая настройка радио-лент, TTL, URL и активации.', 'zh' => '精细控制无线电流、TTL、URL 与启用。']],
        ['route' => 'admin_newsletters', 'title' => ['fr' => 'Newsletter', 'en' => 'Newsletter', 'de' => 'Newsletter', 'nl' => 'Nieuwsbrief', 'es' => 'Boletín', 'it' => 'Newsletter', 'pt' => 'Newsletter', 'ar' => 'النشرة البريدية', 'bn' => 'নিউজলেটার', 'hi' => 'न्यूज़लेटर', 'id' => 'Buletin', 'ja' => 'ニュースレター', 'ru' => 'Рассылка', 'zh' => '通讯'], 'desc' => ['fr' => 'Abonnés, import CSV et campagnes email.', 'en' => 'Subscribers, CSV import and email campaigns.', 'de' => 'Abonnenten, CSV-Import und E-Mail-Kampagnen.', 'nl' => 'Abonnees, CSV-import en e-mailcampagnes.', 'es' => 'Suscriptores, importación CSV y campañas de correo.', 'it' => 'Iscritti, import CSV e campagne email.', 'pt' => 'Subscritores, importação CSV e campanhas de email.', 'ar' => 'المشتركون واستيراد CSV وحملات البريد الإلكتروني.', 'bn' => 'সাবস্ক্রাইবার, CSV ইমপোর্ট ও ইমেইল ক্যাম্পেইন।', 'hi' => 'सदस्य, CSV आयात और ईमेल अभियान।', 'id' => 'Pelanggan, impor CSV, dan kampanye email.', 'ja' => '購読者、CSVインポート、メール配信キャンペーン。', 'ru' => 'Подписчики, импорт CSV и email-кампании.', 'zh' => '订阅者、CSV 导入和邮件活动。']],
        ['route' => 'admin_wiki', 'title' => ['fr' => 'Wiki', 'en' => 'Wiki', 'de' => 'Wiki', 'nl' => 'Wiki', 'es' => 'Wiki', 'it' => 'Wiki', 'pt' => 'Wiki', 'ar' => 'الويكي', 'bn' => 'উইকি', 'hi' => 'विकी', 'id' => 'Wiki kolaboratif', 'ja' => '共同Wiki', 'ru' => 'Вики', 'zh' => '维基'], 'desc' => ['fr' => 'Pages collaboratives et révisions.', 'en' => 'Collaborative pages and revisions.', 'de' => 'Kollaborative Seiten und Revisionen.', 'nl' => 'Samenwerkingspagina’s en revisies.', 'es' => 'Páginas colaborativas y revisiones.', 'it' => 'Pagine collaborative e revisioni.', 'pt' => 'Páginas colaborativas e revisões.', 'ar' => 'صفحات تعاونية ومراجعات.', 'bn' => 'সহযোগী পৃষ্ঠা ও রিভিশন।', 'hi' => 'सहयोगी पृष्ठ और संशोधन।', 'id' => 'Halaman kolaboratif dan revisi.', 'ja' => '共同編集ページと改訂履歴。', 'ru' => 'Совместные страницы и ревизии.', 'zh' => '协作页面与修订。'], 'module' => 'wiki'],
        ['route' => 'admin_albums', 'title' => ['fr' => 'Albums', 'en' => 'Albums', 'de' => 'Alben', 'nl' => 'Albums', 'es' => 'Álbumes', 'it' => 'Album', 'pt' => 'Álbuns', 'ar' => 'الألبومات', 'bn' => 'অ্যালবাম', 'hi' => 'एल्बम', 'id' => 'Album', 'ja' => 'アルバム', 'ru' => 'Альбомы', 'zh' => '相册'], 'desc' => ['fr' => 'Galerie publique et synchro sociale.', 'en' => 'Public gallery and social sync.', 'de' => 'Öffentliche Galerie und Social-Sync.', 'nl' => 'Publieke galerij en sociale sync.', 'es' => 'Galería pública y sincronización social.', 'it' => 'Galleria pubblica e sincronizzazione social.', 'pt' => 'Galeria pública e sincronização social.', 'ar' => 'معرض عام ومزامنة اجتماعية.', 'bn' => 'পাবলিক গ্যালারি ও সোশ্যাল সিঙ্ক।', 'hi' => 'सार्वजनिक गैलरी और सोशल सिंक।', 'id' => 'Galeri publik dan sinkronisasi sosial.', 'ja' => '公開ギャラリーとソーシャル同期。', 'ru' => 'Публичная галерея и социальная синхронизация.', 'zh' => '公开图库与社交同步。'], 'module' => 'albums'],
        ['route' => 'admin_library', 'title' => ['fr' => 'Bibliothèque', 'en' => 'Library', 'de' => 'Bibliothek', 'nl' => 'Bibliotheek', 'es' => 'Biblioteca', 'it' => 'Biblioteca', 'pt' => 'Biblioteca', 'ar' => 'المكتبة', 'bn' => 'লাইব্রেরি', 'hi' => 'पुस्तकालय', 'id' => 'Perpustakaan', 'ja' => 'ライブラリ', 'ru' => 'Библиотека', 'zh' => '资料库'], 'desc' => ['fr' => 'Gestion des documents PDF de la bibliothèque membres.', 'en' => 'Manage PDF documents from the members library.', 'de' => 'Verwaltung der PDF-Dokumente der Mitgliederbibliothek.', 'nl' => 'Beheer van PDF-documenten uit de ledenbibliotheek.', 'es' => 'Gestión de documentos PDF de la biblioteca de socios.', 'it' => 'Gestione dei documenti PDF della biblioteca membri.', 'pt' => 'Gestão de documentos PDF da biblioteca de membros.', 'ar' => 'إدارة مستندات PDF لمكتبة الأعضاء.', 'bn' => 'সদস্য লাইব্রেরির PDF নথি ব্যবস্থাপনা।', 'hi' => 'सदस्य पुस्तकालय के PDF दस्तावेज़ प्रबंधन।', 'id' => 'Kelola dokumen PDF dari perpustakaan anggota.', 'ja' => '会員ライブラリのPDF文書を管理。', 'ru' => 'Управление PDF-документами библиотеки участников.', 'zh' => '管理会员资料库中的 PDF 文档。'], 'permission' => 'admin.access'],
        ['route' => 'admin_ads', 'title' => ['fr' => 'Publicités', 'en' => 'Ads', 'de' => 'Werbung', 'nl' => 'Advertenties', 'es' => 'Publicidad', 'it' => 'Pubblicità', 'pt' => 'Publicidade', 'ar' => 'الإعلانات', 'bn' => 'বিজ্ঞাপন', 'hi' => 'विज्ञापन', 'id' => 'Iklan', 'ja' => '広告', 'ru' => 'Реклама', 'zh' => '广告'], 'desc' => ['fr' => 'Régie publicitaire, placements et statistiques.', 'en' => 'Ad inventory, placements and statistics.', 'de' => 'Werbeverwaltung, Platzierungen und Statistiken.', 'nl' => 'Advertentiebeheer, plaatsingen en statistieken.', 'es' => 'Inventario publicitario, ubicaciones y estadísticas.', 'it' => 'Inventario pubblicitario, posizionamenti e statistiche.', 'pt' => 'Inventário publicitário, posicionamentos e estatísticas.', 'ar' => 'إدارة المخزون الإعلاني والمواضع والإحصاءات.', 'bn' => 'বিজ্ঞাপন ইনভেন্টরি, প্লেসমেন্ট ও পরিসংখ্যান।', 'hi' => 'विज्ञापन इन्वेंटरी, प्लेसमेंट और आँकड़े।', 'id' => 'Inventaris iklan, penempatan, dan statistik.', 'ja' => '広告在庫、配置、統計。', 'ru' => 'Рекламный инвентарь, размещения и статистика.', 'zh' => '广告库存、投放位与统计。'], 'module' => 'advertising'],
    ];

    $catalog[] = [
        'route' => 'admin_classifieds',
        'title' => ['fr' => 'Petites annonces', 'en' => 'Classifieds'],
        'desc' => ['fr' => 'Modération des annonces membres.', 'en' => 'Moderate member classifieds.'],
        'module' => 'classifieds',
        'permission' => 'ads.moderate',
    ];

    $allSupportedLocales = supported_locales();
    foreach ($catalog as &$card) {
        foreach (['title', 'desc', 'audience', 'icon'] as $field) {
            if (!isset($card[$field]) || !is_array($card[$field])) {
                continue;
            }
            $localized = (array) $card[$field];
            foreach ($allSupportedLocales as $localeCode) {
                if (!isset($localized[$localeCode]) || trim((string) $localized[$localeCode]) === '') {
                    $localized[$localeCode] = (string) ($localized['en'] ?? $localized['fr'] ?? '');
                }
            }
            $card[$field] = $localized;
        }
    }
    unset($card);

    return $catalog;
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
            $title = i18n_localized_value($card['title'], $locale, 'fr');
            $desc = i18n_localized_value($card['desc'], $locale, 'fr');
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

