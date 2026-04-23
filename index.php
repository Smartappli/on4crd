<?php
declare(strict_types=1);

if (!is_file(__DIR__ . '/config/config.php')) {
    require __DIR__ . '/install.php';
    exit;
}

require_once __DIR__ . '/app/bootstrap.php';

$route = (string) ($_GET['route'] ?? 'home');
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

seo_apply_defaults($route);

if (str_contains($route, '.') && !in_array($route, ['sitemap.xml', 'robots.txt', 'install.php'], true)) {
    http_response_code(404);
    exit('Not found');
}

if ($route === 'toggle_theme') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    verify_csrf();
    $_SESSION['theme'] = ($_SESSION['theme'] ?? 'light') === 'light' ? 'dark' : 'light';
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
    $supportedLocales = ['fr', 'en', 'de', 'nl'];
    if (!in_array($locale, $supportedLocales, true)) {
        $locale = 'fr';
    }
    $_SESSION['locale'] = $locale;
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
    $returnRoute = (string) ($_POST['return_route'] ?? 'home');
    redirect($returnRoute !== '' ? $returnRoute : 'home');
}

if ($route === 'set_theme') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    verify_csrf();
    $theme = strtolower((string) ($_POST['theme'] ?? 'light'));
    $supportedThemes = ['light', 'dark'];
    if (!in_array($theme, $supportedThemes, true)) {
        $theme = 'light';
    }
    $_SESSION['theme'] = $theme;
    $returnRoute = (string) ($_POST['return_route'] ?? 'home');
    redirect($returnRoute !== '' ? $returnRoute : 'home');
}

$routeModules = [
    'dashboard' => 'dashboard',
    'save_dashboard' => 'dashboard',
    'widget_render' => 'dashboard',
    'profile' => 'members',
    'membership' => 'members',
    'directory' => 'directory',
    'committee' => 'committee',
    'press' => 'press',
    'schools' => 'education',
    'events' => 'events',
    'event_view' => 'events',
    'shop' => 'shop',
    'shop_product' => 'shop',
    'shop_cart' => 'shop',
    'shop_checkout' => 'shop',
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
    'wiki' => 'wiki',
    'wiki_edit' => 'wiki',
    'wiki_view' => 'wiki',
    'albums' => 'albums',
    'album' => 'albums',
    'ads' => 'advertising',
    'admin_ads' => 'advertising',
    'ad_click' => 'advertising',
    'admin' => 'admin',
    'admin_permissions' => 'admin',
    'admin_newsletters' => 'admin',
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
    'admin_shop' => 'admin',
    'admin_auctions' => 'admin',
];

if (isset($routeModules[$route])) {
    require_module_enabled($routeModules[$route]);
}

$publicRoutes = ['home', 'login', 'membership', 'conditions_utilisation', 'mentions_legales', 'reglement_interieur', 'sponsoring', 'news', 'news_view', 'articles', 'article', 'wiki', 'wiki_view', 'albums', 'album', 'chatbot', 'directory', 'committee', 'press', 'schools', 'events', 'event_view', 'shop', 'shop_product', 'shop_cart', 'auctions', 'auction_view', 'ad_click', 'relais', 'sitemap.xml', 'robots.txt', 'newsletter_unsubscribe', 'footer_contact', 'install.php'];
if (!in_array($route, $publicRoutes, true)) {
    require_login();
}


$dispatchPage = static function (string $relativePath): void {
    $path = __DIR__ . '/' . ltrim($relativePath, '/');
    if (!is_file($path)) {
        http_response_code(404);
        echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>404</title><body><h1>404</h1><p>Page introuvable.</p></body></html>';
        return;
    }

    require $path;
};

switch ($route) {
    case 'home': $dispatchPage('pages/home.php'); break;
    case 'login': $dispatchPage('pages/login.php'); break;
    case 'membership': $dispatchPage('pages/membership.php'); break;
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
    case 'save_dashboard': $dispatchPage('pages/save_dashboard.php'); break;
    case 'widget_render': $dispatchPage('pages/widget_render.php'); break;
    case 'profile': $dispatchPage('pages/profile.php'); break;
    case 'directory': $dispatchPage('pages/directory.php'); break;
    case 'committee': $dispatchPage('pages/committee.php'); break;
    case 'press': $dispatchPage('pages/press.php'); break;
    case 'schools': $dispatchPage('pages/schools.php'); break;
    case 'relais': $dispatchPage('pages/relais.php'); break;
    case 'events': $dispatchPage('pages/events.php'); break;
    case 'event_view': $dispatchPage('pages/event_view.php'); break;
    case 'shop': $dispatchPage('pages/shop.php'); break;
    case 'shop_product': $dispatchPage('pages/shop_product.php'); break;
    case 'shop_cart': $dispatchPage('pages/shop_cart.php'); break;
    case 'shop_checkout': $dispatchPage('pages/shop_checkout.php'); break;
    case 'auctions': $dispatchPage('pages/auctions.php'); break;
    case 'auction_view': $dispatchPage('pages/auction_view.php'); break;
    case 'auction_bid': $dispatchPage('pages/auction_bid.php'); break;
    case 'qsl': $dispatchPage('pages/qsl.php'); break;
    case 'qsl_preview': $dispatchPage('pages/qsl_preview.php'); break;
    case 'qsl_export': $dispatchPage('pages/qsl_export.php'); break;
    case 'chatbot': $dispatchPage('pages/chatbot.php'); break;
    case 'newsletter': $dispatchPage('pages/newsletter.php'); break;
    case 'news': $dispatchPage('pages/news.php'); break;
    case 'news_view': $dispatchPage('pages/news_view.php'); break;
    case 'articles': $dispatchPage('pages/articles.php'); break;
    case 'article': $dispatchPage('pages/article.php'); break;
    case 'wiki': $dispatchPage('pages/wiki.php'); break;
    case 'wiki_edit': $dispatchPage('pages/wiki_edit.php'); break;
    case 'wiki_view': $dispatchPage('pages/wiki_view.php'); break;
    case 'albums': $dispatchPage('pages/albums.php'); break;
    case 'album': $dispatchPage('pages/album.php'); break;
    case 'admin': $dispatchPage('pages/admin.php'); break;
    case 'admin_permissions': $dispatchPage('pages/admin_permissions.php'); break;
    case 'admin_newsletters': $dispatchPage('pages/admin_newsletters.php'); break;
    case 'admin_modules': $dispatchPage('pages/admin_modules.php'); break;
    case 'admin_articles': $dispatchPage('pages/admin_articles.php'); break;
    case 'admin_committee': $dispatchPage('pages/admin_committee.php'); break;
    case 'admin_wiki': $dispatchPage('pages/admin_wiki.php'); break;
    case 'admin_albums': $dispatchPage('pages/admin_albums.php'); break;
    case 'admin_news': $dispatchPage('pages/admin_news.php'); break;
    case 'admin_press': $dispatchPage('pages/admin_press.php'); break;
    case 'admin_editorial': $dispatchPage('pages/admin_editorial.php'); break;
    case 'admin_translation_reviews': $dispatchPage('pages/admin_translation_reviews.php'); break;
    case 'admin_live_feeds': $dispatchPage('pages/admin_live_feeds.php'); break;
    case 'admin_events': $dispatchPage('pages/admin_events.php'); break;
    case 'admin_shop': $dispatchPage('pages/admin_shop.php'); break;
    case 'admin_auctions': $dispatchPage('pages/admin_auctions.php'); break;
    case 'ads': $dispatchPage('pages/ads.php'); break;
    case 'admin_ads': $dispatchPage('pages/admin_ads.php'); break;
    case 'ad_click': $dispatchPage('pages/ad_click.php'); break;
    case 'sitemap.xml': $dispatchPage('pages/sitemap.php'); break;
    case 'robots.txt': $dispatchPage('pages/robots.php'); break;
    case 'newsletter_unsubscribe': $dispatchPage('pages/newsletter_unsubscribe.php'); break;
    case 'footer_contact': $dispatchPage('pages/footer_contact.php'); break;
    case 'install.php': $dispatchPage('install.php'); break;
    default:
        http_response_code(404);
        echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>404</title><body><h1>404</h1><p>Page introuvable.</p></body></html>';
        break;
}
