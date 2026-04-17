<?php
declare(strict_types=1);

/**
 * @param array<string, string|int|float|bool> $query
 */
function seo_build_canonical_query(array $query): string
{
    $blocked = ['_csrf', 'maintenance_bypass'];
    foreach ($blocked as $key) {
        unset($query[$key]);
    }

    ksort($query);

    return http_build_query($query);
}

function seo_build_canonical_url(string $route): string
{
    $base = rtrim((string) config('app.base_url', ''), '/');
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
    $url = $base . '/index.php?route=' . rawurlencode($route);

    if ($queryString !== '') {
        $url .= '&' . $queryString;
    }

    return $url;
}

function seo_route_should_noindex(string $route): bool
{
    $noindexRoutes = [
        'login',
        'dashboard',
        'save_dashboard',
        'widget_render',
        'profile',
        'newsletter',
        'newsletter_unsubscribe',
        'shop_cart',
        'shop_checkout',
        'auction_bid',
        'qsl',
        'qsl_preview',
        'qsl_export',
        'ads',
        'admin',
        'admin_permissions',
        'admin_newsletters',
        'admin_modules',
        'admin_articles',
        'admin_committee',
        'admin_wiki',
        'admin_albums',
        'admin_news',
        'admin_press',
        'admin_editorial',
        'admin_translation_reviews',
        'admin_live_feeds',
        'admin_events',
        'admin_shop',
        'admin_auctions',
        'admin_ads',
    ];

    return in_array($route, $noindexRoutes, true);
}

function seo_apply_defaults(string $route): void
{
    if (!function_exists('set_page_meta')) {
        return;
    }

    $meta = [
        'canonical' => seo_build_canonical_url($route),
        'robots' => seo_route_should_noindex($route) ? 'noindex,nofollow' : 'index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1',
        'og_type' => 'website',
        'twitter_card' => 'summary_large_image',
        'schema_type' => 'WebPage',
    ];

    if (in_array($route, ['article', 'news_view'], true)) {
        $meta['og_type'] = 'article';
        $meta['schema_type'] = 'Article';
    }

    if ($route === 'shop_product') {
        $meta['schema_type'] = 'Product';
    }

    set_page_meta($meta);
}
