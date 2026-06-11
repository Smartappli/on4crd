<?php
declare(strict_types=1);

if (!is_file(__DIR__ . '/config/config.php')) {
    require __DIR__ . '/install.php';
    exit;
}

require_once __DIR__ . '/app/bootstrap.php';

$route = (string) ($_GET['route'] ?? '');
if ($route === '') {
    $requestUriPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $path = is_string($requestUriPath) ? trim($requestUriPath, '/') : '';
    if ($path !== '' && $path !== 'index.php') {
        $directDiscoveryRoutes = ['sitemap.xml', 'robots.txt', 'llms.txt', 'ai-index.json', 'knowledge-graph.jsonld'];
        $pathBasename = strtolower(basename($path));
        $route = in_array($pathBasename, $directDiscoveryRoutes, true)
            ? $pathBasename
            : strtolower(pathinfo($path, PATHINFO_FILENAME));
    }
}

$normalizedRoute = ltrim($route, '/');
if (str_ends_with($normalizedRoute, '.php')) {
    if (in_array($normalizedRoute, ['install.php'], true)) {
        $route = 'install.php';
    } else {
        $route = strtolower(pathinfo($normalizedRoute, PATHINFO_FILENAME));
    }
}

if ($route === '') {
    $route = 'home';
}
$requestStart = microtime(true);

register_shutdown_function(static function () use ($requestStart, $route): void {
    $status = http_response_code();
    $durationMs = (int) round((microtime(true) - $requestStart) * 1000);
    log_structured_event('http_request', [
        'status_code' => $status,
        'duration_ms' => $durationMs,
        'route_name' => $route,
        'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    ]);
});

if (maintenance_should_block_route($route)) {
    maintenance_render_and_exit();
}

$localeFromQuery = strtolower(trim((string) ($_GET['locale'] ?? '')));
if ($localeFromQuery !== '') {
    if (in_array($localeFromQuery, supported_locales(), true)) {
        $_SESSION['locale'] = $localeFromQuery;
        setcookie('on4crd_locale', $localeFromQuery, [
            'expires' => time() + (86400 * 365),
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off'),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }
}

seo_apply_defaults($route);


function render_localized_not_found(): void
{
    $locale = function_exists('current_locale') ? current_locale() : 'fr';
    $messages = i18n_domain_locale('errors', $locale);
    $message = (string) ($messages['page_not_found'] ?? 'Page introuvable.');
    $errorPage = __DIR__ . '/pages/errors.php';
    if (is_file($errorPage)) {
        $previousRoute = $_GET['route'] ?? null;
        $previousNotFoundRoute = $_GET['_not_found_route'] ?? null;
        $_GET['_not_found_route'] = is_scalar($previousRoute) ? (string) $previousRoute : '';
        $_GET['route'] = 'errors';
        require $errorPage;
        if ($previousRoute === null) {
            unset($_GET['route']);
        } else {
            $_GET['route'] = $previousRoute;
        }
        if ($previousNotFoundRoute === null) {
            unset($_GET['_not_found_route']);
        } else {
            $_GET['_not_found_route'] = $previousNotFoundRoute;
        }
        return;
    }

    $htmlLang = in_array($locale, supported_locales(), true) ? $locale : substr($locale, 0, 2);
    $htmlDir = is_rtl_locale($htmlLang) ? 'rtl' : 'ltr';
    echo '<!doctype html><html lang="' . htmlspecialchars($htmlLang, ENT_QUOTES, 'UTF-8') . '" dir="' . htmlspecialchars($htmlDir, ENT_QUOTES, 'UTF-8') . '"><meta charset="utf-8"><title>404</title><body><h1>404</h1><p>'
        . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        . '</p></body></html>';
}


if (str_contains($route, '.') && !in_array($route, ['sitemap.xml', 'robots.txt', 'llms.txt', 'ai-index.json', 'knowledge-graph.jsonld', 'install.php'], true)) {
    http_response_code(404);
    render_localized_not_found();
    exit;
}

if ($route === 'toggle_theme') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    verify_csrf();
    $_SESSION['theme'] = ($_SESSION['theme'] ?? 'dark') === 'dark' ? 'light' : 'dark';
    $returnRoute = (string) ($_POST['return_route'] ?? 'home');
    redirect($returnRoute !== '' ? $returnRoute : 'home');
}

if ($route === 'set_language') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    verify_csrf();
    $locale = strtolower((string) ($_POST['locale'] ?? 'fr'));
    if (!in_array($locale, supported_locales(), true)) {
        $locale = 'fr';
    }
    $_SESSION['locale'] = $locale;
    setcookie('on4crd_locale', $locale, [
        'expires' => time() + (86400 * 365),
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off'),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $returnRoute = (string) ($_POST['return_route'] ?? 'home');
    redirect($returnRoute !== '' ? $returnRoute : 'home');
}

if ($route === 'set_accent') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    verify_csrf();
    $accent = strtolower((string) ($_POST['accent'] ?? 'blue'));
    if ($accent === 'rose') {
        $accent = 'red';
    }
    $supportedAccents = ['blue', 'emerald', 'violet', 'red', 'amber', 'orange'];
    if (!in_array($accent, $supportedAccents, true)) {
        $accent = 'blue';
    }
    $_SESSION['accent'] = $accent;
    setcookie('on4crd_accent', $accent, [
        'expires' => time() + (86400 * 365),
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off'),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $returnRoute = (string) ($_POST['return_route'] ?? 'home');
    redirect($returnRoute !== '' ? $returnRoute : 'home');
}

if ($route === 'set_theme') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    verify_csrf();
    $theme = strtolower((string) ($_POST['theme'] ?? 'dark'));
    $supportedThemes = ['light', 'dark'];
    if (!in_array($theme, $supportedThemes, true)) {
        $theme = 'dark';
    }
    $_SESSION['theme'] = $theme;
    setcookie('on4crd_theme', $theme, [
        'expires' => time() + (86400 * 365),
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off'),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $returnRoute = (string) ($_POST['return_route'] ?? 'home');
    redirect($returnRoute !== '' ? $returnRoute : 'home');
}

$routeModules = [
    'dashboard' => 'dashboard',
    'notifications' => 'dashboard',
    'save_dashboard' => 'dashboard',
    'widget_render' => 'dashboard',
    'dashboard_widget_card' => 'dashboard',
    'profile' => 'members',
    'change_password' => 'members',
    'my_requests' => 'members',
    'members_library' => 'members',
    'webotheque' => 'webotheque',
    'presentations' => 'presentations',
    'videos' => 'videos',
    'pv' => 'pv',
    'fichiers' => 'fichiers',
    'telechargements' => 'fichiers',
    'directory' => 'directory',
    'tools' => 'tools',
    'tools_geocode' => 'tools',
    'committee' => 'committee',
    'press' => 'press',
    'schools' => 'education',
    'relais' => 'education',
    'events' => 'events',
    'events_feed' => 'events',
    'event_view' => 'events',
    'auctions' => 'auctions',
    'auction_view' => 'auctions',
    'auction_bid' => 'auctions',
    'qsl' => 'qsl',
    'qsl_preview' => 'qsl',
    'qsl_export' => 'qsl',
    'chatbot' => 'chatbot',
    'newsletter' => 'members',
    'news' => 'news',
    'news_view' => 'news',
    'articles' => 'articles',
    'article' => 'articles',
    'article_propose' => 'articles',
    'wiki' => 'wiki',
    'wiki_edit' => 'wiki',
    'wiki_propose' => 'wiki',
    'wiki_view' => 'wiki',
    'albums' => 'albums',
    'album' => 'albums',
    'classifieds' => 'classifieds',
    'classifieds_manage' => 'classifieds',
    'ads' => 'advertising',
    'admin_classifieds' => 'classifieds',
    'admin_ads' => 'advertising',
    'ad_click' => 'advertising',
    'admin' => 'admin',
    'admin_permissions' => 'admin',
    'admin_members' => 'admin',
    'admin_newsletters' => 'admin',
    'admin_privacy' => 'admin',
    'admin_modules' => 'admin',
    'admin_articles' => 'admin',
    'admin_committee' => 'admin',
    'admin_wiki' => 'admin',
    'admin_albums' => 'admin',
    'admin_news' => 'admin',
    'admin_press' => 'admin',
    'admin_editorial' => 'admin',
    'admin_translation_reviews' => 'admin',
    'admin_live_feeds' => 'admin',
    'admin_events' => 'admin',
    'admin_events_feed' => 'admin',
    'admin_dinner_reservations' => 'admin',
    'admin_library' => 'admin',
    'admin_webotheque' => 'admin',
    'admin_presentations' => 'admin',
    'admin_videos' => 'admin',
    'admin_pv' => 'admin',
    'admin_fichiers' => 'admin',
    'admin_telechargements' => 'admin',
    'admin_dashboard' => 'admin',
    'admin_auctions' => 'admin',
    'settings' => 'members',
    'code_q' => 'education',
    'code_cw' => 'education',
    'bandplan_on3' => 'education',
    'bandplan_on2' => 'education',
    'bandplan_harec' => 'education',
];

if (isset($routeModules[$route])) {
    require_module_enabled($routeModules[$route], $route);
}

$publicRoutes = ['home', 'login', 'logout', 'register', 'forgot_password', 'reset_password', 'membership', 'donation', 'conditions_utilisation', 'mentions_legales', 'reglement_interieur', 'sponsoring', 'gdpr', 'search', 'news', 'news_view', 'articles', 'article', 'wiki', 'wiki_view', 'albums', 'album', 'classifieds', 'chatbot', 'directory', 'tools', 'tools_geocode', 'committee', 'press', 'schools', 'events', 'events_feed', 'event_view', 'auctions', 'auction_view', 'ad_click', 'relais', 'code_q', 'code_cw', 'bandplan_on3', 'bandplan_on2', 'bandplan_harec', 'errors', 'sitemap.xml', 'robots.txt', 'newsletter_unsubscribe', 'newsletter_public', 'footer_contact', 'llms.txt', 'ai-index.json', 'knowledge-graph.jsonld', 'install.php'];
if (!isset($routeModules[$route]) && !in_array($route, $publicRoutes, true)) {
    http_response_code(404);
    render_localized_not_found();
    exit;
}

if (!in_array($route, $publicRoutes, true)) {
    require_login(login_next_url_for_route($route, $_GET));
}

$passwordChangeExemptRoutes = ['change_password', 'logout'];
$passwordChangeUser = current_user();
if (
    $passwordChangeUser !== null
    && member_password_change_required($passwordChangeUser)
    && !in_array($route, $passwordChangeExemptRoutes, true)
) {
    set_flash('error', t_page('change_password', 'forced_notice'));
    redirect('change_password');
}

app_load_route_helpers($route);

$dispatchPage = static function (string $relativePath): void {
    $path = __DIR__ . '/' . ltrim($relativePath, '/');
    if (!is_file($path)) {
        http_response_code(404);
        render_localized_not_found();
        return;
    }

    require $path;
};

switch ($route) {
    case 'home': $dispatchPage('pages/home.php'); break;
    case 'login': $dispatchPage('pages/login.php'); break;
    case 'register': $dispatchPage('pages/register.php'); break;
    case 'forgot_password': $dispatchPage('pages/forgot_password.php'); break;
    case 'reset_password': $dispatchPage('pages/reset_password.php'); break;
    case 'membership': $dispatchPage('pages/membership.php'); break;
    case 'donation': $dispatchPage('pages/donation.php'); break;
    case 'conditions_utilisation': $dispatchPage('pages/conditions_utilisation.php'); break;
    case 'mentions_legales': $dispatchPage('pages/mentions_legales.php'); break;
    case 'reglement_interieur': $dispatchPage('pages/reglement_interieur.php'); break;
    case 'sponsoring': $dispatchPage('pages/sponsoring.php'); break;
    case 'logout':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }
        verify_csrf();
        logout_member();
        redirect('home');
        break;
    case 'dashboard': $dispatchPage('pages/dashboard.php'); break;
    case 'notifications': $dispatchPage('pages/notifications.php'); break;
    case 'save_dashboard': $dispatchPage('pages/save_dashboard.php'); break;
    case 'widget_render': $dispatchPage('pages/widget_render.php'); break;
    case 'dashboard_widget_card': $dispatchPage('pages/dashboard_widget_card.php'); break;
    case 'profile': $dispatchPage('pages/profile.php'); break;
    case 'change_password': $dispatchPage('pages/change_password.php'); break;
    case 'gdpr': $dispatchPage('pages/gdpr.php'); break;
    case 'my_requests': $dispatchPage('pages/my_requests.php'); break;
    case 'directory': $dispatchPage('pages/directory.php'); break;
    case 'tools': $dispatchPage('pages/tools.php'); break;
    case 'tools_geocode': $dispatchPage('pages/tools_geocode.php'); break;
    case 'committee': $dispatchPage('pages/committee.php'); break;
    case 'press': $dispatchPage('pages/press.php'); break;
    case 'schools': $dispatchPage('pages/schools.php'); break;
    case 'relais': $dispatchPage('pages/relais.php'); break;
    case 'events': $dispatchPage('pages/events.php'); break;
    case 'events_feed': $dispatchPage('pages/events_feed.php'); break;
    case 'event_view': $dispatchPage('pages/event_view.php'); break;
    case 'auctions': $dispatchPage('pages/auctions.php'); break;
    case 'classifieds': $dispatchPage('pages/classifieds.php'); break;
    case 'classifieds_manage': $dispatchPage('pages/classifieds_manage.php'); break;
    case 'auction_view': $dispatchPage('pages/auction_view.php'); break;
    case 'auction_bid': $dispatchPage('pages/auction_bid.php'); break;
    case 'qsl': $dispatchPage('pages/qsl.php'); break;
    case 'qsl_preview': $dispatchPage('pages/qsl_preview.php'); break;
    case 'qsl_export': $dispatchPage('pages/qsl_export.php'); break;
    case 'chatbot': $dispatchPage('pages/chatbot.php'); break;
    case 'members_library': $dispatchPage('pages/members_library.php'); break;
    case 'webotheque': $dispatchPage('pages/webotheque.php'); break;
    case 'presentations': $dispatchPage('pages/presentations.php'); break;
    case 'videos': $dispatchPage('pages/videos.php'); break;
    case 'pv': $dispatchPage('pages/pv.php'); break;
    case 'fichiers': $dispatchPage('pages/fichiers.php'); break;
    case 'telechargements': $dispatchPage('pages/telechargements.php'); break;
    case 'search': $dispatchPage('pages/search.php'); break;
    case 'newsletter': $dispatchPage('pages/newsletter.php'); break;
    case 'newsletter_public': $dispatchPage('pages/newsletter_public.php'); break;
    case 'news': $dispatchPage('pages/news.php'); break;
    case 'news_view': $dispatchPage('pages/news_view.php'); break;
    case 'articles': $dispatchPage('pages/articles.php'); break;
    case 'article': $dispatchPage('pages/article.php'); break;
    case 'article_propose': $dispatchPage('pages/article_propose.php'); break;
    case 'wiki': $dispatchPage('pages/wiki.php'); break;
    case 'wiki_edit': $dispatchPage('pages/wiki_edit.php'); break;
    case 'wiki_propose': $dispatchPage('pages/wiki_propose.php'); break;
    case 'wiki_view': $dispatchPage('pages/wiki_view.php'); break;
    case 'albums': $dispatchPage('pages/albums.php'); break;
    case 'album': $dispatchPage('pages/album.php'); break;
    case 'admin': $dispatchPage('pages/admin.php'); break;
    case 'admin_permissions': $dispatchPage('pages/admin_permissions.php'); break;
    case 'admin_members': $dispatchPage('pages/admin_members.php'); break;
    case 'admin_newsletters': $dispatchPage('pages/admin_newsletters.php'); break;
    case 'admin_privacy': $dispatchPage('pages/admin_privacy.php'); break;
    case 'admin_modules': $dispatchPage('pages/admin_modules.php'); break;
    case 'admin_articles': $dispatchPage('pages/admin_articles.php'); break;
    case 'admin_committee': $dispatchPage('pages/admin_committee.php'); break;
    case 'admin_wiki': $dispatchPage('pages/admin_wiki.php'); break;
    case 'admin_albums': $dispatchPage('pages/admin_albums.php'); break;
    case 'admin_library': $dispatchPage('pages/admin_library.php'); break;
    case 'admin_webotheque': $dispatchPage('pages/admin_webotheque.php'); break;
    case 'admin_presentations': $dispatchPage('pages/admin_presentations.php'); break;
    case 'admin_videos': $dispatchPage('pages/admin_videos.php'); break;
    case 'admin_pv': $dispatchPage('pages/admin_pv.php'); break;
    case 'admin_fichiers': $dispatchPage('pages/admin_fichiers.php'); break;
    case 'admin_telechargements': $dispatchPage('pages/admin_telechargements.php'); break;
    case 'admin_news': $dispatchPage('pages/admin_news.php'); break;
    case 'admin_press': $dispatchPage('pages/admin_press.php'); break;
    case 'admin_editorial': $dispatchPage('pages/admin_editorial.php'); break;
    case 'admin_translation_reviews': $dispatchPage('pages/admin_translation_reviews.php'); break;
    case 'admin_live_feeds': $dispatchPage('pages/admin_live_feeds.php'); break;
    case 'admin_events': $dispatchPage('pages/admin_events.php'); break;
    case 'admin_events_feed': $dispatchPage('pages/admin_events_feed.php'); break;
    case 'admin_dinner_reservations': $dispatchPage('pages/admin_dinner_reservations.php'); break;
    case 'admin_dashboard': $dispatchPage('pages/admin_dashboard.php'); break;
    case 'admin_auctions': $dispatchPage('pages/admin_auctions.php'); break;
    case 'admin_classifieds': $dispatchPage('pages/admin_classifieds.php'); break;
    case 'settings': $dispatchPage('pages/settings.php'); break;
    case 'code_q': $dispatchPage('pages/code_q.php'); break;
    case 'code_cw': $dispatchPage('pages/code_cw.php'); break;
    case 'bandplan_on3': $dispatchPage('pages/bandplan_on3.php'); break;
    case 'bandplan_on2': $dispatchPage('pages/bandplan_on2.php'); break;
    case 'bandplan_harec': $dispatchPage('pages/bandplan_harec.php'); break;
    case 'errors':
        http_response_code(404);
        $dispatchPage('pages/errors.php');
        break;
    case 'ads': $dispatchPage('pages/ads.php'); break;
    case 'admin_ads': $dispatchPage('pages/admin_ads.php'); break;
    case 'ad_click': $dispatchPage('pages/ad_click.php'); break;
    case 'sitemap.xml': $dispatchPage('pages/sitemap.php'); break;
    case 'robots.txt': $dispatchPage('pages/robots.php'); break;
    case 'llms.txt': $dispatchPage('pages/llms.php'); break;
    case 'ai-index.json': $dispatchPage('pages/ai_index.php'); break;
    case 'knowledge-graph.jsonld': $dispatchPage('pages/knowledge_graph.php'); break;
    case 'newsletter_unsubscribe': $dispatchPage('pages/newsletter_unsubscribe.php'); break;
    case 'footer_contact': $dispatchPage('pages/footer_contact.php'); break;
    case 'install.php': $dispatchPage('install.php'); break;
    default:
        http_response_code(404);
        render_localized_not_found();
        break;
}
