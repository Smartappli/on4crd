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
        ['chatbot', 'Assistant', 'Assistant conversationnel', 1, 1, 110],
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
    // Intentionally kept as a no-op fallback.
    // The bootstrap always invokes this hook so deployments with mixed versions
    // do not fail when no runtime migration is required.
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
        $suffix = $query === [] ? '' : ('?' . http_build_query($query));
        return base_url('/' . ltrim($route, '/') . $suffix);
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
function current_user(): ?array
{
    static $cache = null;
    static $loaded = false;

    if ($loaded) {
        return $cache;
    }
    $loaded = true;

    $memberId = (int) ($_SESSION['member_id'] ?? 0);
    if ($memberId <= 0 || !table_exists('members')) {
        $cache = null;
        return null;
    }

    $stmt = db()->prepare('SELECT id, callsign, full_name, email, is_active FROM members WHERE id = ? LIMIT 1');
    $stmt->execute([$memberId]);
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

if (!function_exists('require_module_enabled')) {
function require_module_enabled(string $module): void
{
    if (module_enabled($module)) {
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

if (!function_exists('render_layout')) {
function render_layout(string $content, string $title = ''): string
{
    $flashes = consume_flashes();
    $currentRoute = (string) ($_GET['route'] ?? 'home');
    $currentTheme = (string) ($_SESSION['theme'] ?? 'light');
    if ($currentTheme !== 'dark') {
        $currentTheme = 'light';
    }
    $currentLocale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
    if (!in_array($currentLocale, ['fr', 'en', 'de', 'nl'], true)) {
        $currentLocale = 'fr';
    }
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
        ['label' => 'Accueil', 'route' => 'home', 'module' => ''],
        ['label' => 'Actualités', 'route' => 'news', 'module' => 'news'],
        ['label' => 'Boutique', 'route' => 'shop', 'module' => 'shop'],
        ['label' => 'Événements', 'route' => 'events', 'module' => 'events'],
        ['label' => 'Annuaire', 'route' => 'directory', 'module' => 'directory'],
    ];
    $navMemberItems = [
        ['label' => 'Wiki', 'route' => 'wiki', 'module' => 'wiki'],
        ['label' => 'Galerie', 'route' => 'albums', 'module' => 'albums'],
        ['label' => 'Articles', 'route' => 'articles', 'module' => 'articles'],
        ['label' => 'QSL', 'route' => 'qsl', 'module' => 'qsl'],
        ['label' => 'Enchères', 'route' => 'auctions', 'module' => 'auctions'],
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
        $authHtml = '<a class="button secondary small" href="' . e(route_url('profile')) . '">' . e((string) ($user['callsign'] ?? 'Mon profil')) . '</a>'
            . '<form class="nav-form" method="post" action="' . e(route_url('logout')) . '">'
            . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
            . '<button type="submit" class="button secondary small">Déconnexion</button>'
            . '</form>';
    } else {
        $authHtml = '<a class="button toolbar-login-button" href="' . e(route_url('login')) . '">Connexion</a>';
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
        'light' => ['icon' => '☀️', 'label' => 'Clair'],
        'dark' => ['icon' => '🌙', 'label' => 'Sombre'],
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
        $accentLabel = (string) ($accentConfig['label'] ?? ucfirst($accentCode));
        $accentDotColor = (string) ($accentConfig['color'] ?? '#2f6fed');
        $accentOptionHtml .= '<option value="' . e($accentCode) . '"' . ($isActive ? ' selected' : '') . ' style="color:' . e($accentDotColor) . ';">'
            . e(trim($accentIcon . ' ' . $accentLabel))
            . '</option>';
    }
    $languageFormHtml = '<form class="toolbar-form inline-form" method="post" action="' . e(route_url('set_language')) . '">'
        . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="return_route" value="' . e($currentRoute) . '">'
        . '<label class="sr-only" for="language-selector">Choix de la langue</label>'
        . '<select id="language-selector" class="preference-select js-auto-submit" name="locale" aria-label="Choix de la langue" aria-describedby="language-help">' . $languageOptionHtml . '</select>'
        . '<span class="sr-only" id="language-help">Sélecteur de langue du site. Le changement est appliqué automatiquement.</span>'
        . '</form>';
    $themeFormHtml = '<form class="toolbar-form" method="post" action="' . e(route_url('set_theme')) . '">'
        . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="return_route" value="' . e($currentRoute) . '">'
        . '<label class="sr-only" for="theme-selector">Choix du mode</label>'
        . '<select id="theme-selector" class="preference-select js-auto-submit" name="theme" aria-label="Choix du mode clair ou sombre" aria-describedby="theme-help">' . $themeOptionHtml . '</select>'
        . '<span class="sr-only" id="theme-help">Sélecteur de thème. Le changement est appliqué automatiquement.</span>'
        . '</form>';
    $accentFormHtml = '<form class="toolbar-form inline-form" method="post" action="' . e(route_url('set_accent')) . '">'
        . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="return_route" value="' . e($currentRoute) . '">'
        . '<label class="sr-only" for="accent-selector">Choix de la couleur</label>'
        . '<select id="accent-selector" class="preference-select js-auto-submit" name="accent" aria-label="Choix de la couleur" aria-describedby="accent-help">' . $accentOptionHtml . '</select>'
        . '<span class="sr-only" id="accent-help">Sélecteur de couleur d’accent. Le changement est appliqué automatiquement.</span>'
        . '</form>';
    $menuToolsHtml = '<div class="toolbar-preferences">'
        . '<div class="toolbar-preferences-row">' . $languageFormHtml . $themeFormHtml . '</div>'
        . '<div class="toolbar-preferences-row">' . $accentFormHtml . '<div class="toolbar-auth">' . $authHtml . '</div></div>'
        . '</div>';
    $nonce = csp_nonce();

    return '<!doctype html><html lang="' . e($currentLocale) . '" data-theme="' . e($currentTheme) . '" style="--accent: ' . e($accentColor) . '; --accent-strong: ' . e($accentStrongColor) . ';"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
        . e($pageTitle)
        . '</title>' . $metaHead
        . '<link rel="stylesheet" href="' . e(asset_url('assets/css/app.css')) . '">'
        . '<script nonce="' . e($nonce) . '" src="https://cdn.tailwindcss.com"></script>'
        . '<script nonce="' . e($nonce) . '">tailwind.config={theme:{extend:{colors:{club:{900:"#0f172a",700:"#1d4ed8",500:"#3b82f6",100:"#dbeafe"}}}}};</script>'
        . '</head><body>'
        . '<a class="skip-link" href="#main-content">Aller au contenu</a>'
        . '<header class="topbar"><div class="brand-wrap"><div class="brand-mark">ON</div><a class="brand" href="' . e(route_url('home')) . '">'
        . '<span class="brand-title">ON4CRD.be</span><span class="brand-subtitle">Club Radio Durnal</span></a></div>'
        . '<nav class="nav" aria-label="Navigation principale">' . $navHtml . '</nav>'
        . '<div class="toolbar">' . $menuToolsHtml . '</div></header>'
        . '<main id="main-content" class="layout container py-6">' . $flashHtml . $content . '</main>'
        . '<footer class="site-footer"><div class="footer-inner"><div class="footer-grid">'
        . '<section><h3 class="footer-title">Radio Club Durnal</h3><p class="footer-copy">Bocq Arena, Rue des Écoles, 5530 Purnode</p><p class="footer-spacer" aria-hidden="true">&nbsp;</p><p class="footer-copy">Éditeurs responsables: ON4BEN : +32 496 260 865 &amp; ON4DG : +32 478 789 193.</p><p class="footer-spacer" aria-hidden="true">&nbsp;</p><form class="footer-newsletter-form" method="get" action="' . e(route_url('newsletter')) . '"><label for="footer-newsletter-email" class="sr-only">Email newsletter</label><input id="footer-newsletter-email" type="email" name="email" placeholder="Votre email" required><button type="submit" class="button">S\'inscrire à la newsletter</button></form></section>'
        . '<section><h3 class="footer-title">Contact</h3><form class="footer-contact-form" method="post" action="' . e(route_url('footer_contact')) . '"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="return_route" value="' . e($currentRoute) . '"><label for="footer-contact-name" class="sr-only">Nom</label><input id="footer-contact-name" type="text" name="name" placeholder="Votre nom" required><label for="footer-contact-email" class="sr-only">Email</label><input id="footer-contact-email" type="email" name="email" placeholder="Votre email" required><label for="footer-contact-message" class="sr-only">Message</label><textarea id="footer-contact-message" name="message" placeholder=" Votre message" rows="3" required></textarea><button type="submit" class="button">Envoyer</button></form></section>'
        . '<section><h3 class="footer-title">Informations importantes</h3><ul class="footer-nav"><li><a href="' . e(route_url('conditions_utilisation')) . '">Conditions générales d\'utilisation</a></li><li><a href="' . e(route_url('mentions_legales')) . '">Mentions légales</a></li><li><a href="' . e(route_url('reglement_interieur')) . '">Règlement d\'ordre intérieur</a></li><li><a href="' . e(route_url('membership')) . '">Faire un don</a></li><li><a href="' . e(route_url('sponsoring')) . '">Sponsoring</a></li></ul></section>'
        . '</div><div class="footer-meta"><span>© 2026 Radio Club Durnal (ON4CRD)</span><span style="display:inline-flex;align-items:center;gap:.6rem;"><a href="https://www.facebook.com/groups/clubradiodurnal/" target="_blank" rel="noopener noreferrer" aria-label="Facebook - Club Radio Durnal" title="Facebook - Club Radio Durnal"><svg aria-hidden="true" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.56 9.87v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.23.19 2.23.19v2.45h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.88h-2.34v6.99A10 10 0 0 0 22 12z"></path></svg><span class="sr-only">Facebook</span></a><a href="https://www.linkedin.com/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn - Club Radio Durnal" title="LinkedIn - Club Radio Durnal"><svg aria-hidden="true" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M4.98 3.5a2.49 2.49 0 1 0 0 4.98 2.49 2.49 0 0 0 0-4.98zM3 8.98h3.96V21H3zM9.34 8.98h3.8v1.64h.05c.53-1 1.82-2.05 3.75-2.05C20.95 8.57 22 11.2 22 14.62V21h-3.96v-5.66c0-1.35-.02-3.09-1.88-3.09-1.88 0-2.17 1.47-2.17 2.99V21H10.03z"></path></svg><span class="sr-only">LinkedIn</span></a><a href="https://x.com/" target="_blank" rel="noopener noreferrer" aria-label="X - Club Radio Durnal" title="X - Club Radio Durnal"><svg aria-hidden="true" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M18.9 2H22l-6.77 7.74L23 22h-6.2l-4.85-6.33L6.41 22H3.3l7.24-8.28L1 2h6.36l4.38 5.78zM17.82 20h1.72L6.45 3.9H4.6z"></path></svg><span class="sr-only">X</span></a><a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Instagram - Club Radio Durnal" title="Instagram - Club Radio Durnal"><svg aria-hidden="true" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm11.25 1.5a1.25 1.25 0 1 1-1.25 1.25 1.25 1.25 0 0 1 1.25-1.25zM12 7a5 5 0 1 1-5 5 5 5 0 0 1 5-5zm0 2a3 3 0 1 0 3 3 3 3 0 0 0-3-3z"></path></svg><span class="sr-only">Instagram</span></a></span><span>Site réalisé par <a href="https://smartappli.eu">Smartappli ®</a></span></div></div></footer>'
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

function sanitize_svg_document(string $svg): string
{
    $normalized = strtolower($svg);
    if (
        str_contains($normalized, '<script')
        || str_contains($normalized, 'javascript:')
        || str_contains($normalized, 'onload=')
        || str_contains($normalized, 'onerror=')
        || str_contains($normalized, '<iframe')
        || str_contains($normalized, '<object')
    ) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="500" viewBox="0 0 900 500"><rect width="900" height="500" fill="#0f172a"/><text x="450" y="250" text-anchor="middle" fill="#f8fafc" font-family="Arial, sans-serif" font-size="28">QSL sécurisée indisponible</text></svg>';
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

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="500" viewBox="0 0 900 500">'
        . '<rect width="900" height="500" fill="#0b1f3a"/>'
        . '<text x="40" y="70" fill="#e2e8f0" font-size="42" font-family="Arial, sans-serif" font-weight="700">' . $title . '</text>'
        . '<text x="40" y="130" fill="#f8fafc" font-size="30" font-family="Arial, sans-serif">DE: ' . $ownCall . '</text>'
        . '<text x="40" y="170" fill="#cbd5e1" font-size="22" font-family="Arial, sans-serif">' . $ownName . ' • ' . $ownQth . '</text>'
        . '<text x="40" y="250" fill="#f8fafc" font-size="34" font-family="Arial, sans-serif">TO: ' . $qsoCall . '</text>'
        . '<text x="40" y="305" fill="#cbd5e1" font-size="22" font-family="Arial, sans-serif">DATE ' . $date . '  UTC ' . $time . '  BAND ' . $band . '  MODE ' . $mode . '</text>'
        . '<text x="40" y="345" fill="#cbd5e1" font-size="22" font-family="Arial, sans-serif">RST S/R: ' . $rstSent . ' / ' . $rstRecv . '</text>'
        . '<text x="40" y="395" fill="#f8fafc" font-size="20" font-family="Arial, sans-serif">' . $comment . '</text>'
        . '</svg>';

    return sanitize_svg_document($svg);
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || strlen($_SESSION['_csrf']) !== 64) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function verify_csrf(): void
{
    $sessionToken = (string) ($_SESSION['_csrf'] ?? '');
    $submittedToken = (string) ($_POST['_csrf'] ?? '');
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

    return 'storage/uploads/albums/' . $saved;
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

function client_ip_address(): string
{
    return trim((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')) ?: '0.0.0.0';
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

function auction_minimum_bid_cents(array $lot): int
{
    $current = max((int) ($lot['current_price_cents'] ?? 0), (int) ($lot['starting_price_cents'] ?? 0));
    $hasBids = ((int) ($lot['current_price_cents'] ?? 0)) > 0;
    if (!$hasBids) {
        return max(0, (int) ($lot['starting_price_cents'] ?? 0));
    }

    return $current + max(1, (int) ($lot['min_increment_cents'] ?? 100));
}

function auction_sync_expired_lots(): void
{
    if (!table_exists('auction_lots')) {
        return;
    }

    $rows = db()->query('SELECT id FROM auction_lots WHERE status IN ("scheduled","active") AND ends_at <= NOW()')->fetchAll();
    if ($rows === []) {
        return;
    }

    $update = db()->prepare('UPDATE auction_lots SET status = "closed", winner_member_id = ?, current_price_cents = ? WHERE id = ?');
    foreach ($rows as $row) {
        $lotId = (int) $row['id'];
        $highestBid = auction_highest_bid($lotId);
        $winnerId = $highestBid ? (int) $highestBid['member_id'] : null;
        $currentPrice = $highestBid ? (int) $highestBid['amount_cents'] : 0;
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
