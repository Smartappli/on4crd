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
        ? $base . '/' . $route
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
        'presentations',
        'medias',
        'pv',
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
        'admin_editorial',
        'admin_translation_reviews',
        'admin_live_feeds',
        'admin_events',
        'admin_events_feed',
        'admin_dinner_reservations',
        'admin_dashboard',
        'admin_library',
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
        'auctions' => ['title' => 'Enchères radioamateur', 'description' => 'Découvrez les enchères de matériel radio proposées sur l’espace membres ON4CRD.'],
        'auction_view' => ['title' => 'Détail enchère', 'description' => 'Consultez les détails d’une enchère et les informations du lot proposé.'],
        'shop' => ['title' => 'Boutique du club', 'description' => 'Catalogue des produits du club ON4CRD : textile, accessoires et documentation.'],
        'classifieds' => ['title' => 'Petites annonces', 'description' => 'Consultez les petites annonces radioamateur du club ON4CRD.'],
        'directory' => ['title' => 'Annuaire', 'description' => 'Annuaire des membres et contacts utiles du Radio Club Durnal ON4CRD.'],
        'committee' => ['title' => 'Comité', 'description' => 'Présentation du comité et de l’organisation du club ON4CRD.'],
        'presentations' => ['title' => 'Présentations membres', 'description' => 'Supports et présentations réservés aux membres ON4CRD.'],
        'medias' => ['title' => 'Medias membres', 'description' => 'Ressources médias réservées aux membres ON4CRD.'],
        'pv' => ['title' => 'PV membres', 'description' => 'Procès-verbaux et comptes rendus réservés aux membres ON4CRD.'],
        'telechargements' => ['title' => 'Téléchargements membres', 'description' => 'Fichiers et ressources à télécharger réservés aux membres ON4CRD.'],
        'press' => ['title' => 'Presse', 'description' => 'Communiqués, retombées presse et publications autour du club ON4CRD.'],
        'schools' => ['title' => 'Écoles & sensibilisation', 'description' => 'Actions pédagogiques et interventions radioamateur auprès des écoles.'],
        'membership' => ['title' => 'Adhésion', 'description' => 'Rejoignez le Radio Club Durnal ON4CRD et participez aux activités du club.'],
        'sponsoring' => ['title' => 'Sponsoring', 'description' => 'Soutenez les projets du club ON4CRD via le sponsoring.'],
        'mentions_legales' => ['title' => 'Mentions légales', 'description' => 'Informations légales et éditeur du site ON4CRD.'],
        'conditions_utilisation' => ['title' => 'Conditions d’utilisation', 'description' => 'Conditions générales d’utilisation de la plateforme ON4CRD.'],
        'llms.txt' => ['title' => 'LLMS ON4CRD', 'description' => 'Fichier de contexte public ON4CRD pour assistants et moteurs generatifs.'],
        'ai-index.json' => ['title' => 'AI index ON4CRD', 'description' => 'Index JSON public des contenus ON4CRD pour moteurs generatifs.'],
        'knowledge-graph.jsonld' => ['title' => 'Knowledge graph ON4CRD', 'description' => 'Graphe JSON-LD public du Radio Club Durnal ON4CRD.'],
    ];

    $routeSpecificMeta = $routeMetaDefaults[$route] ?? [];

    $locale = current_locale();
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
