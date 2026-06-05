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
        'widget_catalog.php' => ['dashboard', 'save_dashboard', 'admin_dashboard'],
        'widget_renderer.php' => ['home', 'dashboard', 'widget_render'],
        'ham_weather_advice.php' => ['home'],
        'qsl_helpers.php' => ['qsl', 'qsl_preview', 'qsl_export'],
        'member_library_helpers.php' => ['members_library', 'admin_library', 'admin_articles', 'chatbot'],
        'knowledge_helpers.php' => ['chatbot'],
        'member_favorites.php' => ['dashboard', 'members_library', 'articles', 'article', 'albums', 'album', 'classifieds'],
        'member_preferences.php' => ['dashboard', 'settings'],
        'member_recommendations.php' => ['settings'],
        'notifications.php' => ['dashboard', 'notifications', 'articles', 'article', 'albums', 'album', 'classifieds', 'members_library', 'admin_articles', 'admin_albums', 'admin_classifieds', 'admin_library', 'admin_news'],
        'article_import_helpers.php' => ['admin_articles', 'admin_library'],
        'article_helpers.php' => ['home', 'articles', 'article', 'article_propose', 'admin_articles', 'admin_translation_reviews'],
        'ads_helpers.php' => ['ads', 'admin_ads', 'ad_click'],
        'committee_helpers.php' => ['committee', 'admin_editorial'],
        'news_helpers.php' => ['admin_news'],
        'press_helpers.php' => ['press', 'admin_press'],
        'album_helpers.php' => ['home', 'admin_albums'],
        'member_media.php' => ['directory', 'gdpr', 'profile'],
        'member_profile_helpers.php' => ['directory', 'gdpr', 'profile', 'register', 'tools_geocode'],
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
