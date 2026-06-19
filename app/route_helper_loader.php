<?php
declare(strict_types=1);

function app_load_helper_file(string $filename): void
{
    require_once __DIR__ . '/' . ltrim($filename, '/');
}

/**
 * @return array<string, list<string>>
 */
function app_route_helper_map(): array
{
    return [
        'layout_renderer.php' => ['bandplan_on3', 'bandplan_on2', 'bandplan_harec'],
        'module_catalog.php' => ['home', 'admin', 'admin_dashboard'],
        'widget_catalog.php' => ['dashboard', 'save_dashboard', 'widget_render', 'admin_dashboard'],
        'widget_renderer.php' => ['home', 'dashboard', 'widget_render'],
        'ham_weather_advice.php' => ['home'],
        'qsl_helpers.php' => ['qsl', 'qsl_preview', 'qsl_export', 'members_library', 'admin_library', 'member_library_preview'],
        'member_library_helpers.php' => ['members_library', 'my_requests', 'search', 'admin_library', 'admin_articles', 'chatbot', 'wiki', 'wiki_edit', 'wiki_propose', 'wiki_view', 'admin_wiki', 'member_library_preview'],
        'member_module_documents.php' => ['my_requests', 'presentations', 'videos', 'pv', 'fichiers', 'telechargements', 'member_document_preview', 'admin_presentations', 'admin_videos', 'admin_pv', 'admin_fichiers', 'admin_telechargements'],
        'member_webotheque.php' => ['webotheque', 'admin_webotheque'],
        'knowledge_helpers.php' => ['chatbot'],
        'member_favorites.php' => ['dashboard', 'members_library', 'webotheque', 'presentations', 'wiki', 'wiki_view', 'articles', 'article', 'albums', 'album', 'classifieds'],
        'member_preferences.php' => ['dashboard', 'settings'],
        'member_recommendations.php' => ['settings'],
        'notifications.php' => ['dashboard', 'notifications', 'articles', 'article', 'albums', 'album', 'classifieds', 'members_library', 'admin_articles', 'admin_albums', 'admin_classifieds', 'admin_library', 'admin_news'],
        'article_import_helpers.php' => ['presentations', 'videos', 'pv', 'fichiers', 'members_library', 'member_library_preview', 'member_document_preview', 'admin_articles', 'admin_library', 'admin_presentations', 'admin_videos', 'admin_pv', 'admin_fichiers'],
        'article_helpers.php' => ['home', 'dashboard', 'search', 'chatbot', 'articles', 'article', 'article_propose', 'admin_articles', 'admin_translation_reviews', 'llms.txt', 'ai-index.json'],
        'ads_helpers.php' => ['ads', 'admin_ads', 'ad_click'],
        'committee_helpers.php' => ['committee', 'admin_editorial'],
        'news_helpers.php' => ['news', 'news_view', 'admin_news'],
        'press_helpers.php' => ['press', 'admin_press'],
        'album_helpers.php' => ['home', 'albums', 'album', 'admin_albums'],
        'member_media.php' => ['directory', 'gdpr', 'profile', 'admin_committee'],
        'member_profile_helpers.php' => ['directory', 'gdpr', 'profile', 'register', 'admin_members', 'tools_geocode'],
        'privacy_helpers.php' => ['forgot_password', 'register', 'profile', 'gdpr', 'my_requests', 'newsletter', 'newsletter_public', 'newsletter_unsubscribe', 'settings', 'admin_newsletters', 'ads', 'admin_ads', 'ad_click', 'admin_privacy'],
        'auction_helpers.php' => ['auctions', 'auction_view', 'auction_bid', 'admin_auctions'],
        'admin_helpers.php' => ['admin'],
        'newsletter.php' => ['newsletter', 'newsletter_public', 'newsletter_unsubscribe', 'settings', 'admin_newsletters'],
    ];
}

function app_load_route_helpers(string $route): void
{
    $helperRoutes = app_route_helper_map();
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
