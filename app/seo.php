<?php
declare(strict_types=1);

/**
 * @param array<string, string|int|float|bool> $query
 */
function seo_build_canonical_query(array $query): string
{
    $blocked = ['_csrf', 'maintenance_bypass', 'fbclid', 'gclid', 'msclkid', 'mc_cid', 'mc_eid'];
    foreach ($blocked as $key) {
        unset($query[$key]);
    }
    foreach (array_keys($query) as $key) {
        $normalizedKey = strtolower((string) $key);
        if (str_starts_with($normalizedKey, 'utm_')) {
            unset($query[$key]);
        }
    }

    ksort($query);

    return http_build_query($query);
}

function seo_build_canonical_url(string $route): string
{
    $requestHost = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $requestScheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
    $configuredBase = rtrim((string) config('app.base_url', ''), '/');
    $normalizedHost = strtolower(explode(':', $requestHost)[0] ?? $requestHost);
    $configuredCdnHost = strtolower(trim((string) config('app.cdn_host', '')));
    $isCdnHost = $normalizedHost !== '' && (
        str_starts_with($normalizedHost, 'cdn.')
        || ($configuredCdnHost !== '' && $normalizedHost === $configuredCdnHost)
    );
    $base = $configuredBase !== ''
        ? $configuredBase
        : ((!$isCdnHost && $requestHost !== '') ? ($requestScheme . '://' . $requestHost) : '');
    $requestBase = (!$isCdnHost && $requestHost !== '') ? ($requestScheme . '://' . $requestHost) : '';
    if ($base === '') {
        return '';
    }

    /** @var array<string, string|int|float|bool> $query */
    $query = array_filter(
        (array) $_GET,
        static fn(string $k): bool => $k !== 'route',
        ARRAY_FILTER_USE_KEY
    );

    $queryString = seo_build_canonical_query($query);
    $directRoutes = [
        'sitemap.xml' => true,
        'robots.txt' => true,
        'llms.txt' => true,
        'ai-index.json' => true,
        'knowledge-graph.jsonld' => true,
    ];
    $url = isset($directRoutes[$route])
        ? ($requestBase !== '' ? $requestBase : $base) . '/' . $route
        : $base . '/index.php?route=' . rawurlencode($route);

    if ($queryString !== '') {
        $url .= '&' . $queryString;
    }

    return $url;
}


function seo_supported_locales(): array
{
    return supported_locales();
}

/**
 * @return array<string,string>
 */
function seo_build_hreflang_alternates(string $route): array
{
    $canonical = seo_build_canonical_url($route);
    if ($canonical === '') {
        return [];
    }

    $canonicalParts = parse_url($canonical);
    if (!is_array($canonicalParts)) {
        return [];
    }

    parse_str((string) ($canonicalParts['query'] ?? ''), $canonicalQuery);
    unset($canonicalQuery['lang'], $canonicalQuery['locale']);
    $alternates = [];
    foreach (seo_supported_locales() as $locale) {
        $alternates[$locale] = route_url_with_locale($route, $locale, $canonicalQuery);
    }

    $alternates['x-default'] = route_url_with_locale($route, (string) config('app.default_locale', 'fr'), $canonicalQuery);

    return $alternates;
}

function seo_i18n_route_key(string $route): string
{
    $route = trim($route);
    if ($route === '') {
        return 'home';
    }

    return preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: 'home';
}

function seo_default_robots_for_route(string $route): string
{
    $routeKey = seo_i18n_route_key($route);
    $noindexFollowRoutes = array_fill_keys([
        'aiindexjson',
        'knowledgegraphjsonld',
        'llmstxt',
        'members_library',
        'webotheque',
        'presentations',
        'videos',
        'pv',
        'fichiers',
        'telechargements',
        'sitemapxml',
    ], true);
    if (isset($noindexFollowRoutes[$routeKey])) {
        return 'noindex,follow';
    }

    if ($routeKey === 'admin' || str_starts_with($routeKey, 'admin_')) {
        return 'noindex,nofollow';
    }

    $noindexNoFollowRoutes = array_fill_keys([
        'ad_click',
        'auction_bid',
        'change_password',
        'dashboard',
        'dashboard_widget_card',
        'events_feed',
        'footer_contact',
        'forgot_password',
        'gdpr',
        'installphp',
        'login',
        'logout',
        'my_requests',
        'newsletter',
        'newsletter_unsubscribe',
        'notifications',
        'profile',
        'qsl',
        'qsl_export',
        'qsl_preview',
        'register',
        'reset_password',
        'robotstxt',
        'save_dashboard',
        'settings',
        'tools_geocode',
        'widget_render',
        'wiki_edit',
    ], true);

    return isset($noindexNoFollowRoutes[$routeKey]) ? 'noindex,nofollow' : 'index,follow';
}

function seo_route_should_noindex(string $route): bool
{
    $noindexRoutes = [
        'login',
        'logout',
        'register',
        'forgot_password',
        'reset_password',
        'dashboard',
        'dashboard_widget_card',
        'events_feed',
        'footer_contact',
        'save_dashboard',
        'widget_render',
        'profile',
        'gdpr',
        'members_library',
        'webotheque',
        'presentations',
        'videos',
        'pv',
        'fichiers',
        'telechargements',
        'settings',
        'newsletter',
        'newsletter_unsubscribe',
        'ai-index.json',
        'knowledge-graph.jsonld',
        'notifications',
        'auction_bid',
        'tools_geocode',
        'ad_click',
        'wiki_edit',
        'qsl',
        'qsl_preview',
        'qsl_export',
        'ads',
        'admin',
        'admin_permissions',
        'admin_newsletters',
        'admin_privacy',
        'admin_modules',
        'admin_articles',
        'admin_committee',
        'admin_wiki',
        'admin_albums',
        'admin_news',
        'admin_press',
        'admin_presentations',
        'admin_videos',
        'admin_pv',
        'admin_fichiers',
        'admin_telechargements',
        'admin_editorial',
        'admin_translation_reviews',
        'admin_live_feeds',
        'admin_events',
        'admin_events_feed',
        'admin_dinner_reservations',
        'admin_dashboard',
        'admin_library',
        'admin_webotheque',
        'admin_auctions',
        'admin_classifieds',
        'admin_ads',
        'install.php',
    ];

    return in_array($route, $noindexRoutes, true);
}

function seo_apply_defaults(string $route): void
{
    if (!function_exists('set_page_meta')) {
        return;
    }

    $routeKey = function_exists('seo_i18n_route_key') ? seo_i18n_route_key($route) : (preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: 'home');
    $locale = current_locale();
    $seoMessages = function_exists('i18n_domain_locale') ? i18n_domain_locale('seo', $locale) : [];
    $routeSpecificMeta = [];
    $routeTitle = trim((string) ($seoMessages[$routeKey . '_title'] ?? ''));
    $routeDescription = trim((string) ($seoMessages[$routeKey . '_description'] ?? ''));
    if ($routeTitle !== '') {
        $routeSpecificMeta['title'] = $routeTitle;
    }
    if ($routeDescription !== '') {
        $routeSpecificMeta['description'] = $routeDescription;
    }
    $localeMap = [
        'fr' => 'fr_BE', 'en' => 'en_GB', 'de' => 'de_DE', 'nl' => 'nl_BE', 'es' => 'es_ES', 'it' => 'it_IT', 'pt' => 'pt_PT',
        'ar' => 'ar_SA', 'hi' => 'hi_IN', 'ja' => 'ja_JP', 'zh' => 'zh_CN', 'bn' => 'bn_BD', 'ru' => 'ru_RU', 'id' => 'id_ID',
    ];

    $meta = [
        'alternates' => seo_build_hreflang_alternates($route),
        'canonical' => seo_build_canonical_url($route),
        'robots' => seo_route_should_noindex($route) ? 'noindex,nofollow' : 'index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1',
        'og_type' => 'website',
        'twitter_card' => 'summary_large_image',
        'schema_type' => 'WebPage',
        'site_name' => (string) config('app.site_name', 'ON4CRD'),
        'locale' => (string) ($localeMap[$locale] ?? 'fr_BE'),
        'geo_region' => 'BE-WNA',
        'geo_placename' => 'Durnal, Belgium',
        'geo_position' => '50.3150;4.9452',
        'icbm' => '50.3150, 4.9452',
        'latitude' => '50.3150',
        'longitude' => '4.9452',
    ];

    if (in_array($route, ['article', 'news_view'], true)) {
        $meta['og_type'] = 'article';
        $meta['schema_type'] = 'Article';
    }

    set_page_meta(array_merge($meta, $routeSpecificMeta));
}
