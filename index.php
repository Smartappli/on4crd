<?php
declare(strict_types=1);

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
    require_login();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    verify_csrf();
    $_SESSION['theme'] = ($_SESSION['theme'] ?? 'light') === 'light' ? 'dark' : 'light';
    redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
}

$routeModules = [
    'dashboard' => 'dashboard',
    'save_dashboard' => 'dashboard',
    'widget_render' => 'dashboard',
    'profile' => 'members',
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

$publicRoutes = ['home', 'login', 'news', 'news_view', 'articles', 'article', 'wiki', 'wiki_view', 'albums', 'album', 'chatbot', 'directory', 'committee', 'press', 'schools', 'events', 'event_view', 'shop', 'shop_product', 'shop_cart', 'auctions', 'auction_view', 'ad_click', 'sitemap.xml', 'robots.txt', 'newsletter_unsubscribe', 'install.php'];
if (!in_array($route, $publicRoutes, true)) {
    require_login();
}

switch ($route) {
    case 'home': require __DIR__ . '/pages/home.php'; break;
    case 'login': require __DIR__ . '/pages/login.php'; break;
    case 'logout':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }
        verify_csrf();
        logout_member();
        redirect('home');
        break;
    case 'dashboard': require __DIR__ . '/pages/dashboard.php'; break;
    case 'save_dashboard': require __DIR__ . '/pages/save_dashboard.php'; break;
    case 'widget_render': require __DIR__ . '/pages/widget_render.php'; break;
    case 'profile': require __DIR__ . '/pages/profile.php'; break;
    case 'directory': require __DIR__ . '/pages/directory.php'; break;
    case 'committee': require __DIR__ . '/pages/committee.php'; break;
    case 'press': require __DIR__ . '/pages/press.php'; break;
    case 'schools': require __DIR__ . '/pages/schools.php'; break;
    case 'events': require __DIR__ . '/pages/events.php'; break;
    case 'event_view': require __DIR__ . '/pages/event_view.php'; break;
    case 'shop': require __DIR__ . '/pages/shop.php'; break;
    case 'shop_product': require __DIR__ . '/pages/shop_product.php'; break;
    case 'shop_cart': require __DIR__ . '/pages/shop_cart.php'; break;
    case 'shop_checkout': require __DIR__ . '/pages/shop_checkout.php'; break;
    case 'auctions': require __DIR__ . '/pages/auctions.php'; break;
    case 'auction_view': require __DIR__ . '/pages/auction_view.php'; break;
    case 'auction_bid': require __DIR__ . '/pages/auction_bid.php'; break;
    case 'qsl': require __DIR__ . '/pages/qsl.php'; break;
    case 'qsl_preview': require __DIR__ . '/pages/qsl_preview.php'; break;
    case 'qsl_export': require __DIR__ . '/pages/qsl_export.php'; break;
    case 'chatbot': require __DIR__ . '/pages/chatbot.php'; break;
    case 'newsletter': require __DIR__ . '/pages/newsletter.php'; break;
    case 'news': require __DIR__ . '/pages/news.php'; break;
    case 'news_view': require __DIR__ . '/pages/news_view.php'; break;
    case 'articles': require __DIR__ . '/pages/articles.php'; break;
    case 'article': require __DIR__ . '/pages/article.php'; break;
    case 'wiki': require __DIR__ . '/pages/wiki.php'; break;
    case 'wiki_edit': require __DIR__ . '/pages/wiki_edit.php'; break;
    case 'wiki_view': require __DIR__ . '/pages/wiki_view.php'; break;
    case 'albums': require __DIR__ . '/pages/albums.php'; break;
    case 'album': require __DIR__ . '/pages/album.php'; break;
    case 'admin': require __DIR__ . '/pages/admin.php'; break;
    case 'admin_permissions': require __DIR__ . '/pages/admin_permissions.php'; break;
    case 'admin_newsletters': require __DIR__ . '/pages/admin_newsletters.php'; break;
    case 'admin_modules': require __DIR__ . '/pages/admin_modules.php'; break;
    case 'admin_articles': require __DIR__ . '/pages/admin_articles.php'; break;
    case 'admin_committee': require __DIR__ . '/pages/admin_committee.php'; break;
    case 'admin_wiki': require __DIR__ . '/pages/admin_wiki.php'; break;
    case 'admin_albums': require __DIR__ . '/pages/admin_albums.php'; break;
    case 'admin_news': require __DIR__ . '/pages/admin_news.php'; break;
    case 'admin_press': require __DIR__ . '/pages/admin_press.php'; break;
    case 'admin_editorial': require __DIR__ . '/pages/admin_editorial.php'; break;
    case 'admin_translation_reviews': require __DIR__ . '/pages/admin_translation_reviews.php'; break;
    case 'admin_live_feeds': require __DIR__ . '/pages/admin_live_feeds.php'; break;
    case 'admin_events': require __DIR__ . '/pages/admin_events.php'; break;
    case 'admin_shop': require __DIR__ . '/pages/admin_shop.php'; break;
    case 'admin_auctions': require __DIR__ . '/pages/admin_auctions.php'; break;
    case 'ads': require __DIR__ . '/pages/ads.php'; break;
    case 'admin_ads': require __DIR__ . '/pages/admin_ads.php'; break;
    case 'ad_click': require __DIR__ . '/pages/ad_click.php'; break;
    case 'sitemap.xml': require __DIR__ . '/pages/sitemap.php'; break;
    case 'robots.txt': require __DIR__ . '/pages/robots.php'; break;
    case 'newsletter_unsubscribe': require __DIR__ . '/pages/newsletter_unsubscribe.php'; break;
    case 'install.php': require __DIR__ . '/install.php'; break;
    default:
        http_response_code(404);
        echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>404</title><body><h1>404</h1><p>Page introuvable.</p></body></html>';
        break;
}
