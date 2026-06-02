<?php
declare(strict_types=1);

function app_load_helper_file(string $filename): void
{
    require_once __DIR__ . '/' . ltrim($filename, '/');
}

function app_load_route_helpers(string $route): void
{
    $helperRoutes = [
        'module_catalog.php' => ['home', 'admin', 'admin_dashboard'],
        'widgets.php' => ['home', 'dashboard', 'save_dashboard', 'widget_render', 'dashboard_widget_card', 'admin_dashboard'],
        'qsl_helpers.php' => ['qsl', 'qsl_preview', 'qsl_export'],
        'knowledge_helpers.php' => ['chatbot'],
        'member_content.php' => ['dashboard', 'settings', 'members_library', 'articles', 'article', 'albums', 'album', 'classifieds', 'classifieds_manage', 'admin_library'],
        'notifications.php' => ['dashboard', 'notifications', 'articles', 'article', 'albums', 'album', 'classifieds', 'members_library', 'admin_articles', 'admin_albums', 'admin_classifieds', 'admin_library', 'admin_news'],
        'article_import_helpers.php' => ['admin_articles', 'admin_library'],
        'article_helpers.php' => ['home', 'articles', 'article', 'admin_articles'],
        'ads_helpers.php' => ['ads', 'admin_ads', 'ad_click'],
        'committee_helpers.php' => ['committee', 'admin_editorial'],
        'news_helpers.php' => ['admin_news'],
        'press_helpers.php' => ['press', 'admin_press'],
        'album_helpers.php' => ['home', 'admin_albums'],
        'member_media.php' => ['directory', 'gdpr', 'profile'],
        'auction_helpers.php' => ['auctions', 'auction_view', 'auction_bid', 'admin_auctions'],
        'admin_helpers.php' => ['admin'],
        'newsletter.php' => ['newsletter', 'newsletter_public', 'newsletter_unsubscribe', 'settings', 'admin_newsletters'],
    ];

    if ($route === '__all') {
        foreach (array_keys($helperRoutes) as $filename) {
            app_load_helper_file($filename);
        }
        return;
    }

    foreach ($helperRoutes as $filename => $routes) {
        if (in_array($route, $routes, true)) {
            app_load_helper_file($filename);
        }
    }
}
