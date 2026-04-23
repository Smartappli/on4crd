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

    $routeMetaDefaults = [
        'home' => ['title' => 'Accueil', 'description' => 'Portail du Radio Club Durnal ON4CRD : actualités, événements, activités et ressources radioamateur.'],
        'news' => ['title' => 'Actualités', 'description' => 'Retrouvez les dernières nouvelles, annonces et comptes-rendus du club ON4CRD.'],
        'news_view' => ['title' => 'Détail actualité', 'description' => 'Consultez une actualité du club ON4CRD avec son contenu complet.'],
        'events' => ['title' => 'Événements', 'description' => 'Agenda des activités, réunions et sorties radioamateur du club ON4CRD.'],
        'event_view' => ['title' => 'Détail événement', 'description' => 'Informations pratiques et programme d’un événement du club ON4CRD.'],
        'articles' => ['title' => 'Articles techniques', 'description' => 'Tutoriels, retours d’expérience et documentation technique radioamateur.'],
        'article' => ['title' => 'Article technique', 'description' => 'Consultez un article technique publié par le club ON4CRD.'],
        'wiki' => ['title' => 'Wiki du club', 'description' => 'Base de connaissances du club : procédures, guides et fiches pratiques.'],
        'wiki_view' => ['title' => 'Page wiki', 'description' => 'Consultez une page de la base de connaissances ON4CRD.'],
        'albums' => ['title' => 'Galerie photo', 'description' => 'Albums photo des activités et événements du Radio Club Durnal.'],
        'album' => ['title' => 'Album photo', 'description' => 'Parcourez un album photo publié par le club ON4CRD.'],
        'shop' => ['title' => 'Boutique du club', 'description' => 'Catalogue des produits du club ON4CRD : textile, accessoires et documentation.'],
        'shop_product' => ['title' => 'Produit boutique', 'description' => 'Fiche produit de la boutique ON4CRD avec disponibilité et informations utiles.'],
        'auctions' => ['title' => 'Enchères radioamateur', 'description' => 'Découvrez les enchères de matériel radio proposées sur l’espace membres ON4CRD.'],
        'auction_view' => ['title' => 'Détail enchère', 'description' => 'Consultez les détails d’une enchère et les informations du lot proposé.'],
        'directory' => ['title' => 'Annuaire', 'description' => 'Annuaire des membres et contacts utiles du Radio Club Durnal ON4CRD.'],
        'committee' => ['title' => 'Comité', 'description' => 'Présentation du comité et de l’organisation du club ON4CRD.'],
        'press' => ['title' => 'Presse', 'description' => 'Communiqués, retombées presse et publications autour du club ON4CRD.'],
        'schools' => ['title' => 'Écoles & sensibilisation', 'description' => 'Actions pédagogiques et interventions radioamateur auprès des écoles.'],
        'membership' => ['title' => 'Adhésion', 'description' => 'Rejoignez le Radio Club Durnal ON4CRD et participez aux activités du club.'],
        'sponsoring' => ['title' => 'Sponsoring', 'description' => 'Soutenez les projets du club ON4CRD via le sponsoring.'],
        'mentions_legales' => ['title' => 'Mentions légales', 'description' => 'Informations légales et éditeur du site ON4CRD.'],
        'conditions_utilisation' => ['title' => 'Conditions d’utilisation', 'description' => 'Conditions générales d’utilisation de la plateforme ON4CRD.'],
    ];

    $routeSpecificMeta = $routeMetaDefaults[$route] ?? [];

    $meta = [
        'canonical' => seo_build_canonical_url($route),
        'robots' => seo_route_should_noindex($route) ? 'noindex,nofollow' : 'index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1',
        'og_type' => 'website',
        'twitter_card' => 'summary_large_image',
        'schema_type' => 'WebPage',
        'site_name' => (string) config('app.site_name', 'ON4CRD'),
        'locale' => 'fr_BE',
    ];

    if (in_array($route, ['article', 'news_view'], true)) {
        $meta['og_type'] = 'article';
        $meta['schema_type'] = 'Article';
    }

    if ($route === 'shop_product') {
        $meta['schema_type'] = 'Product';
    }

    set_page_meta(array_merge($meta, $routeSpecificMeta));
}
