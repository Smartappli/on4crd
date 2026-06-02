<?php
declare(strict_types=1);

if (!function_exists('set_page_meta')) {
function set_page_meta(string|array $title = '', string $description = ''): void
{
    if (is_array($title)) {
        $_SESSION['_page_meta'] = $title;
        return;
    }
    $_SESSION['_page_meta'] = ['title' => $title, 'description' => $description];
}
}

if (!function_exists('render_footer_social_links')) {
function render_footer_social_links(): string
{
    $socialLinks = [
        [
            'name' => 'Facebook',
            'href' => 'https://www.facebook.com/groups/clubradiodurnal/',
            'path' => 'M22 12a10 10 0 1 0-11.56 9.87v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.23.19 2.23.19v2.45h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.88h-2.34v6.99A10 10 0 0 0 22 12z',
        ],
        [
            'name' => 'LinkedIn',
            'href' => 'https://www.linkedin.com/',
            'path' => 'M4.98 3.5a2.49 2.49 0 1 0 0 4.98 2.49 2.49 0 0 0 0-4.98zM3 8.98h3.96V21H3zM9.34 8.98h3.8v1.64h.05c.53-1 1.82-2.05 3.75-2.05C20.95 8.57 22 11.2 22 14.62V21h-3.96v-5.66c0-1.35-.02-3.09-1.88-3.09-1.88 0-2.17 1.47-2.17 2.99V21H10.03z',
        ],
        [
            'name' => 'X',
            'href' => 'https://x.com/',
            'path' => 'M18.9 2H22l-6.77 7.74L23 22h-6.2l-4.85-6.33L6.41 22H3.3l7.24-8.28L1 2h6.36l4.38 5.78zM17.82 20h1.72L6.45 3.9H4.6z',
        ],
        [
            'name' => 'Instagram',
            'href' => 'https://www.instagram.com/',
            'path' => 'M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm11.25 1.5a1.25 1.25 0 1 1-1.25 1.25 1.25 1.25 0 0 1 1.25-1.25zM12 7a5 5 0 1 1-5 5 5 5 0 0 1 5-5zm0 2a3 3 0 1 0 3 3 3 3 0 0 0-3-3z',
        ],
    ];

    $html = '<span style="display:inline-flex;align-items:center;gap:.6rem;">';
    foreach ($socialLinks as $social) {
        $name = (string) ($social['name'] ?? '');
        $href = (string) ($social['href'] ?? '#');
        $path = (string) ($social['path'] ?? '');
        $html .= '<a href="' . e($href) . '" target="_blank" rel="noopener noreferrer" aria-label="' . e($name . ' - Club Radio Durnal') . '" title="' . e($name . ' - Club Radio Durnal') . '">'
            . '<svg aria-hidden="true" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="' . e($path) . '"></path></svg>'
            . '<span class="sr-only">' . e($name) . '</span>'
            . '</a>';
    }
    $html .= '</span>';

    return $html;
}
}

if (!function_exists('render_site_footer')) {
function render_site_footer(string $currentRoute): string
{
    $locale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
    $i18n = i18n_domain_locale('footer', $locale);

    return '<footer class="site-footer"><div class="footer-inner"><div class="footer-meta"><span>© 2026 Radio Club Durnal (ON4CRD)</span>' . render_footer_social_links() . '<span>' . e((string) $i18n['built_by']) . ' <a href="https://smartappli.eu">Smartappli ®</a></span></div></div></footer>';
}
}

if (!function_exists('render_layout')) {
function route_url_with_locale(string $route, string $locale, array $query = []): string
{
    $query['lang'] = $locale;
    return route_url_clean($route, $query);
}

function seo_public_current_query(): array
{
    $query = (array) $_GET;
    foreach (['route', 'lang', 'locale', '_csrf', 'maintenance_bypass', 'fbclid', 'gclid', 'msclkid', 'mc_cid', 'mc_eid'] as $key) {
        unset($query[$key]);
    }
    foreach (array_keys($query) as $key) {
        if (str_starts_with(strtolower((string) $key), 'utm_')) {
            unset($query[$key]);
        }
    }

    ksort($query);
    return clean_query_params($query);
}

function localized_seo_defaults(string $route, string $locale, array $pageMeta, string $siteName): array
{
    $seo = i18n_domain_locale('seo', $locale);
    $routeKey = preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: 'home';
    $canonicalRoute = in_array($route, ['install.php', 'sitemap.xml', 'robots.txt', 'llms.txt'], true) ? $route : $routeKey;
    $routeSeo = [
        'ad_click' => ['title' => 'Redirection partenaire ON4CRD', 'description' => 'Redirection securisee vers une annonce ou un partenaire du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,nofollow'],
        'admin' => ['title' => 'Administration ON4CRD', 'description' => 'Tableau d administration du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,nofollow'],
        'ads' => ['title' => 'Annonces partenaires ON4CRD', 'description' => 'Annonces, partenaires et communications sponsorisees du Radio Club Durnal ON4CRD.'],
        'album' => ['title' => 'Album photo ON4CRD', 'description' => 'Album photo public des activites, sorties et evenements du Radio Club Durnal ON4CRD.'],
        'albums' => ['title' => 'Galerie photo ON4CRD', 'description' => 'Galerie des albums publics du Radio Club Durnal ON4CRD.'],
        'article' => ['title' => 'Article ON4CRD', 'description' => 'Article radioamateur publie par le Radio Club Durnal ON4CRD.'],
        'articles' => ['title' => 'Articles radioamateurs ON4CRD', 'description' => 'Articles, guides et retours d experience radioamateurs du Radio Club Durnal ON4CRD.'],
        'auction_bid' => ['title' => 'Offre enchere ON4CRD', 'description' => 'Endpoint de soumission d offre pour les encheres ON4CRD.', 'robots' => 'noindex,nofollow'],
        'auction_view' => ['title' => 'Detail enchere ON4CRD', 'description' => 'Detail d un lot ou d une enchere radioamateur proposee par ON4CRD.'],
        'auctions' => ['title' => 'Encheres ON4CRD', 'description' => 'Encheres de materiel radioamateur du Radio Club Durnal ON4CRD.'],
        'bandplan_harec' => ['title' => 'Band plan HAREC', 'description' => 'Plan de bandes radioamateur HAREC et reperes de frequences pour les operateurs ON4CRD.'],
        'bandplan_on2' => ['title' => 'Band plan ON2', 'description' => 'Plan de bandes ON2 pour preparer ses communications radioamateurs en Belgique.'],
        'bandplan_on3' => ['title' => 'Band plan ON3', 'description' => 'Plan de bandes ON3 et ressources pratiques pour les radioamateurs debutants.'],
        'chatbot' => ['title' => 'Assistant ON4CRD', 'description' => 'Assistant pratique du Radio Club Durnal pour retrouver les informations du site et les ressources radioamateurs.'],
        'classifieds' => ['title' => 'Petites annonces ON4CRD', 'description' => 'Petites annonces radioamateurs et materiel entre membres et visiteurs ON4CRD.'],
        'code_cw' => ['title' => 'Code CW et Morse', 'description' => 'Ressources ON4CRD pour apprendre, reviser et pratiquer le code Morse CW.'],
        'code_q' => ['title' => 'Code Q radioamateur', 'description' => 'Liste des codes Q utiles pour le trafic radioamateur et les echanges ON4CRD.'],
        'committee' => ['title' => 'Comite ON4CRD', 'description' => 'Presentation du comite et de l organisation du Radio Club Durnal ON4CRD.'],
        'conditions_utilisation' => ['title' => 'Conditions d utilisation ON4CRD', 'description' => 'Conditions generales d utilisation du site du Radio Club Durnal ON4CRD.'],
        'dashboard' => ['title' => 'Tableau de bord ON4CRD', 'description' => 'Tableau de bord personnel de l espace membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'dashboard_widget_card' => ['title' => 'Widget dashboard ON4CRD', 'description' => 'Fragment technique de widget du tableau de bord membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'directory' => ['title' => 'Annuaire des membres ON4CRD', 'description' => 'Annuaire radioamateur des membres visibles du Radio Club Durnal ON4CRD, indicatifs, licences et QTH.'],
        'event_view' => ['title' => 'Detail evenement ON4CRD', 'description' => 'Detail d un evenement, d une reunion ou d une activite radioamateur du Radio Club Durnal.'],
        'events' => ['title' => 'Agenda ON4CRD', 'description' => 'Agenda des reunions, activites, sorties et evenements radioamateurs du Radio Club Durnal.'],
        'events_feed' => ['title' => 'Flux calendrier ON4CRD', 'description' => 'Flux technique des evenements ON4CRD pour FullCalendar et exports.', 'robots' => 'noindex,nofollow'],
        'footer_contact' => ['title' => 'Contact ON4CRD', 'description' => 'Endpoint de contact du pied de page ON4CRD.', 'robots' => 'noindex,nofollow'],
        'forgot_password' => ['title' => 'Mot de passe oublie ON4CRD', 'description' => 'Procedure de recuperation d acces a l espace membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'gdpr' => ['title' => 'Vie privee ON4CRD', 'description' => 'Gestion des preferences de confidentialite et de visibilite du profil membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'home' => ['title' => 'Radio Club Durnal ON4CRD', 'description' => 'Portail du Radio Club Durnal ON4CRD : actualites, evenements, outils et ressources radioamateurs.'],
        'installphp' => ['title' => 'Installation ON4CRD', 'description' => 'Endpoint d installation technique ON4CRD.', 'robots' => 'noindex,nofollow'],
        'llmstxt' => ['title' => 'LLMS ON4CRD', 'description' => 'Fichier de contexte public pour assistants et moteurs de recherche.', 'robots' => 'noindex,follow'],
        'login' => ['title' => 'Connexion membre ON4CRD', 'description' => 'Connexion securisee a l espace membre du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,nofollow'],
        'logout' => ['title' => 'Deconnexion ON4CRD', 'description' => 'Deconnexion securisee de l espace membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'members_library' => ['title' => 'Bibliotheque membres ON4CRD', 'description' => 'Bibliotheque documentaire reservee aux membres du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,follow'],
        'membership' => ['title' => 'Devenir membre du CRD', 'description' => 'Informations pour devenir membre du Radio Club Durnal, rejoindre les activites et participer a la communaute ON4CRD.'],
        'mentions_legales' => ['title' => 'Mentions legales ON4CRD', 'description' => 'Mentions legales et informations editoriales du site Radio Club Durnal ON4CRD.'],
        'news' => ['title' => 'Actualites ON4CRD', 'description' => 'Dernieres actualites, annonces et informations du Radio Club Durnal ON4CRD.'],
        'news_view' => ['title' => 'Actualite ON4CRD', 'description' => 'Article d actualite du Radio Club Durnal ON4CRD et informations radioamateurs locales.'],
        'newsletter' => ['title' => 'Newsletter membre ON4CRD', 'description' => 'Gestion de l abonnement newsletter pour les membres du Radio Club Durnal.', 'robots' => 'noindex,nofollow'],
        'newsletter_public' => ['title' => 'Newsletter ON4CRD', 'description' => 'Newsletter publique du Radio Club Durnal ON4CRD.'],
        'newsletter_unsubscribe' => ['title' => 'Desinscription newsletter ON4CRD', 'description' => 'Desinscription securisee de la newsletter ON4CRD.', 'robots' => 'noindex,nofollow'],
        'notifications' => ['title' => 'Notifications membre ON4CRD', 'description' => 'Notifications personnelles de l espace membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'press' => ['title' => 'Presse ON4CRD', 'description' => 'Communiques, publications et informations presse du Radio Club Durnal ON4CRD.'],
        'profile' => ['title' => 'Profil membre ON4CRD', 'description' => 'Profil personnel et informations de membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'qsl' => ['title' => 'QSL ON4CRD', 'description' => 'Gestion QSL reservee aux membres ON4CRD.', 'robots' => 'noindex,nofollow'],
        'qsl_export' => ['title' => 'Export QSL ON4CRD', 'description' => 'Export technique des donnees QSL ON4CRD.', 'robots' => 'noindex,nofollow'],
        'qsl_preview' => ['title' => 'Apercu QSL ON4CRD', 'description' => 'Apercu technique QSL ON4CRD.', 'robots' => 'noindex,nofollow'],
        'register' => ['title' => 'Creer un compte ON4CRD', 'description' => 'Creation d un compte membre pour acceder aux services du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,nofollow'],
        'reglement_interieur' => ['title' => 'Reglement interieur ON4CRD', 'description' => 'Reglement interieur et cadre de fonctionnement du Radio Club Durnal.'],
        'relais' => ['title' => 'Relais ON4CRD', 'description' => 'Informations relais et ressources radioamateurs locales du Radio Club Durnal ON4CRD.'],
        'reset_password' => ['title' => 'Reinitialisation du mot de passe ON4CRD', 'description' => 'Reinitialisation securisee du mot de passe membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'robotstxt' => ['title' => 'Robots ON4CRD', 'description' => 'Fichier robots.txt du site ON4CRD.', 'robots' => 'noindex,nofollow'],
        'save_dashboard' => ['title' => 'Sauvegarde tableau de bord ON4CRD', 'description' => 'Endpoint de sauvegarde du tableau de bord membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'schools' => ['title' => 'Ecoles et sensibilisation ON4CRD', 'description' => 'Actions pedagogiques, animations et sensibilisation radioamateur du Radio Club Durnal.'],
        'search' => ['title' => 'Recherche globale ON4CRD', 'description' => 'Recherche dans les contenus, actualites, articles, wiki et ressources ON4CRD.'],
        'settings' => ['title' => 'Preferences ON4CRD', 'description' => 'Preferences d affichage, de langue et de compte pour ON4CRD.', 'robots' => 'noindex,nofollow'],
        'sitemapxml' => ['title' => 'Sitemap ON4CRD', 'description' => 'Plan XML public du site ON4CRD.', 'robots' => 'noindex,follow'],
        'sponsoring' => ['title' => 'Sponsoring Radio Club Durnal', 'description' => 'Possibilites de sponsoring et de partenariat avec le Radio Club Durnal ON4CRD.'],
        'tools' => ['title' => 'Outils radioamateurs ON4CRD', 'description' => 'Outils radioamateurs ON4CRD : calculs, conversions, codes, bandes et ressources pratiques.'],
        'tools_geocode' => ['title' => 'Geocodage outils ON4CRD', 'description' => 'Service de geocodage utilise par les outils radioamateurs ON4CRD.', 'robots' => 'noindex,nofollow'],
        'widget_render' => ['title' => 'Rendu widget ON4CRD', 'description' => 'Endpoint technique de rendu des widgets ON4CRD.', 'robots' => 'noindex,nofollow'],
        'wiki' => ['title' => 'Wiki radioamateur ON4CRD', 'description' => 'Wiki collaboratif du Radio Club Durnal : procedures, techniques, ressources et connaissances radioamateurs.'],
        'wiki_edit' => ['title' => 'Edition wiki ON4CRD', 'description' => 'Interface d edition du wiki ON4CRD.', 'robots' => 'noindex,nofollow'],
        'wiki_view' => ['title' => 'Page wiki ON4CRD', 'description' => 'Page du wiki radioamateur collaboratif ON4CRD.'],
    ];
    foreach ([
        'admin_ads', 'admin_albums', 'admin_articles', 'admin_auctions', 'admin_classifieds', 'admin_committee',
        'admin_dashboard', 'admin_dinner_reservations', 'admin_editorial', 'admin_events', 'admin_events_feed', 'admin_library',
        'admin_live_feeds', 'admin_members', 'admin_modules', 'admin_news', 'admin_newsletters', 'admin_permissions',
        'admin_press', 'admin_translation_reviews', 'admin_wiki',
    ] as $adminRoute) {
        $routeSeo[$adminRoute] ??= [
            'title' => 'Administration ON4CRD',
            'description' => 'Interface d administration du Radio Club Durnal ON4CRD.',
            'robots' => 'noindex,nofollow',
        ];
    }
    $titleKey = $routeKey . '_title';
    $descriptionKey = $routeKey . '_description';
    $title = trim((string) ($pageMeta['title'] ?? ''));
    $description = trim((string) ($pageMeta['description'] ?? ''));

    if ($title === '') {
        $title = trim((string) ($seo[$titleKey] ?? $routeSeo[$routeKey]['title'] ?? $seo['default_title'] ?? $siteName));
    }
    if ($description === '') {
        $description = trim((string) ($seo[$descriptionKey] ?? $routeSeo[$routeKey]['description'] ?? $seo['default_description'] ?? ''));
    }

    $canonicalQuery = seo_public_current_query();
    $alternates = isset($pageMeta['alternates']) && is_array($pageMeta['alternates']) ? $pageMeta['alternates'] : [];
    foreach (supported_locales() as $supportedLocale) {
        $alternates[$supportedLocale] = route_url_with_locale($canonicalRoute, $supportedLocale, $canonicalQuery);
    }
    $alternates['x-default'] = route_url_with_locale($canonicalRoute, 'fr', $canonicalQuery);

    $defaults = array_replace([
        'title' => $title,
        'description' => $description,
        'canonical' => route_url_with_locale($canonicalRoute, $locale, $canonicalQuery),
        'locale' => str_replace('-', '_', locale_open_graph_code($locale)),
        'geo_region' => 'BE-WNA',
        'geo_placename' => (string) ($seo['geo_placename'] ?? 'Durnal, Yvoir, Namur, Belgium'),
        'geo_position' => '50.3150;4.9452',
        'icbm' => '50.3150, 4.9452',
        'latitude' => '50.3150',
        'longitude' => '4.9452',
        'schema_type' => 'WebPage',
        'alternates' => $alternates,
        'robots' => (string) ($routeSeo[$routeKey]['robots'] ?? 'index,follow'),
    ], array_filter($pageMeta, static fn($value): bool => $value !== null && $value !== ''));
    $defaults['alternates'] = $alternates;
    if (!isset($defaults['json_ld'])) {
        $defaults['json_ld'] = [
            '@context' => 'https://schema.org',
            '@type' => (string) ($defaults['schema_type'] ?? 'WebPage'),
            'name' => (string) $defaults['title'],
            'description' => (string) $defaults['description'],
            'url' => (string) $defaults['canonical'],
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => route_url_with_locale('home', $locale),
            ],
            'about' => [
                '@type' => 'Organization',
                'name' => 'Radio Club Durnal ON4CRD',
                'url' => route_url_with_locale('home', $locale),
                'location' => [
                    '@type' => 'Place',
                    'name' => 'Bocq Arena',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => 'Rue des Ecoles',
                        'postalCode' => '5530',
                        'addressLocality' => 'Purnode',
                        'addressRegion' => 'Namur',
                        'addressCountry' => 'BE',
                    ],
                    'geo' => [
                        '@type' => 'GeoCoordinates',
                        'latitude' => 50.3150,
                        'longitude' => 4.9452,
                    ],
                ],
            ],
        ];
    }

    return $defaults;
}

function locale_open_graph_code(string $locale): string
{
    return match ($locale) {
        'fr' => 'fr_BE',
        'en' => 'en_US',
        'de' => 'de_DE',
        'nl' => 'nl_BE',
        'it' => 'it_IT',
        'es' => 'es_ES',
        'pt' => 'pt_PT',
        'ar' => 'ar_AR',
        'hi' => 'hi_IN',
        'ja' => 'ja_JP',
        'zh' => 'zh_CN',
        'bn' => 'bn_BD',
        'ru' => 'ru_RU',
        'id' => 'id_ID',
        default => 'fr_BE',
    };
}

function module_css_assets_for_route(string $route): array
{
    $route = preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: 'home';
    $moduleByRoute = [
        'album' => 'albums',
        'auction_bid' => 'auctions',
        'auction_view' => 'auctions',
        'classifieds_manage' => 'classifieds',
        'event_view' => 'events',
        'news_view' => 'news',
        'wiki_edit' => 'wiki',
        'wiki_view' => 'wiki',
    ];
    $module = $moduleByRoute[$route] ?? $route;
    $assets = [];

    $candidates = [$module];
    if ($route !== $module) {
        $candidates[] = $route;
    }

    foreach (array_unique($candidates) as $candidate) {
        $path = 'assets/css/modules/' . $candidate . '.css';
        if (is_file(dirname(__DIR__) . '/' . $path)) {
            $assets[] = $path;
        }
    }

    return $assets;
}

function module_js_assets_for_route(string $route): array
{
    $route = preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: 'home';
    $moduleByRoute = [
        'event_view' => 'events',
        'save_dashboard' => 'dashboard',
        'widget_render' => 'dashboard',
        'wiki_edit' => 'wiki_edit',
    ];
    $module = $moduleByRoute[$route] ?? $route;
    $assets = [];

    $candidates = [$module];
    if ($route === 'home') {
        $candidates[] = 'tools';
    }
    if (str_starts_with($route, 'admin_') || in_array($route, ['ads', 'classifieds', 'classifieds_manage', 'wiki_edit'], true)) {
        $candidates[] = 'wysiwyg';
    }

    foreach (array_unique($candidates) as $candidate) {
        $path = 'assets/js/modules/' . $candidate . '.js';
        if (is_file(dirname(__DIR__) . '/' . $path)) {
            $assets[] = $path;
        }
    }

    return $assets;
}

function render_layout(string $content, string $title = ''): string
{
    $flashes = consume_flashes();
    $currentRoute = (string) ($_GET['route'] ?? 'home');
    $currentTheme = (string) ($_SESSION['theme'] ?? 'dark');
    if ($currentTheme !== 'dark') {
        $currentTheme = 'light';
    }
    $currentLocale = current_locale();
    if (!in_array($currentLocale, supported_locales(), true)) {
        $currentLocale = 'fr';
    }
    $layoutI18n = i18n_domain_locale('layout', $currentLocale);
    $currentAccent = strtolower((string) ($_SESSION['accent'] ?? 'blue'));
    $accentPalette = [
        'blue' => ['color' => '#2f6fed', 'strong' => '#1f59cf', 'label' => 'Bleu'],
        'emerald' => ['color' => '#059669', 'strong' => '#047857', 'label' => 'Émeraude'],
        'violet' => ['color' => '#7c3aed', 'strong' => '#6d28d9', 'label' => 'Violet'],
        'red' => ['color' => '#dc2626', 'strong' => '#b91c1c', 'label' => 'Rouge'],
        'amber' => ['color' => '#d97706', 'strong' => '#b45309', 'label' => 'Ambre'],
        'orange' => ['color' => '#ea580c', 'strong' => '#c2410c', 'label' => 'Orange'],
    ];
    if ($currentAccent === 'rose') {
        $currentAccent = 'red';
    }
    if (!array_key_exists($currentAccent, $accentPalette)) {
        $currentAccent = 'blue';
    }
    $accentColor = (string) $accentPalette[$currentAccent]['color'];
    $accentStrongColor = (string) $accentPalette[$currentAccent]['strong'];
    $user = current_user();
    $flashHtml = '';
    foreach ($flashes as $flash) {
        $type = (string) ($flash['type'] ?? 'info');
        $message = e((string) ($flash['message'] ?? ''));
        $flashHtml .= '<div class="flash flash-' . e($type) . '">' . $message . '</div>';
    }

    $navPrimaryItems = [
        ['label' => (string) $layoutI18n['nav_home'], 'route' => 'home', 'module' => ''],
        ['label' => (string) $layoutI18n['nav_news'], 'route' => 'news', 'module' => 'news'],
        ['label' => (string) $layoutI18n['nav_shop'], 'route' => 'classifieds', 'module' => 'classifieds'],
        ['label' => (string) $layoutI18n['nav_events'], 'route' => 'events', 'module' => 'events'],
        ['label' => (string) $layoutI18n['nav_tools'], 'route' => 'tools', 'module' => ''],
        ['label' => (string) $layoutI18n['search_submit'], 'route' => 'search', 'module' => ''],
        ['label' => (string) $layoutI18n['nav_directory'], 'route' => 'directory', 'module' => 'directory'],
    ];
    $navMemberItems = [
        ['label' => (string) $layoutI18n['nav_dashboard'], 'route' => 'dashboard', 'module' => 'dashboard'],
        ['label' => (string) $layoutI18n['nav_wiki'], 'route' => 'wiki', 'module' => 'wiki'],
        ['label' => (string) $layoutI18n['nav_gallery'], 'route' => 'albums', 'module' => 'albums'],
        ['label' => (string) $layoutI18n['nav_articles'], 'route' => 'articles', 'module' => 'articles'],
        ['label' => (string) $layoutI18n['nav_library'], 'route' => 'members_library', 'module' => ''],
        ['label' => 'QSL', 'route' => 'qsl', 'module' => 'qsl'],
        ['label' => (string) $layoutI18n['nav_auctions'], 'route' => 'auctions', 'module' => 'auctions'],
        ['label' => (string) $layoutI18n['nav_assistant'], 'route' => 'chatbot', 'module' => 'chatbot'],
    ];

    $buildNavLinks = static function (array $items, string $currentRoute): string {
        $links = '';
        foreach ($items as $item) {
            $module = (string) ($item['module'] ?? '');
            if ($module !== '' && !module_enabled($module)) {
                continue;
            }

            $route = (string) $item['route'];
            $isCurrent = $currentRoute === $route || ($currentRoute === '' && $route === 'home');
            $links .= '<a class="transition-colors duration-200" href="' . e(route_url($route)) . '"' . ($isCurrent ? ' aria-current="page"' : '') . '>'
                . e((string) $item['label']) . '</a>';
        }

        return $links;
    };
    $navHtml = '<div class="nav-row nav-row-primary">' . $buildNavLinks($navPrimaryItems, $currentRoute) . '</div>';
    if ($user !== null) {
        $memberLinks = $buildNavLinks($navMemberItems, $currentRoute);
        if ($memberLinks !== '') {
            $navHtml .= '<div class="nav-row nav-row-member">' . $memberLinks . '</div>';
        }
    }

    $authHtml = '';
    if ($user !== null) {
        $accountLabel = trim((string) ($user['callsign'] ?? '')) !== '' ? (string) $user['callsign'] : (string) $layoutI18n['account_space'];
        $accountPrivacyLabel = (string) ($layoutI18n['account_privacy'] ?? 'Vie privée');
        $adminMenuLink = '';
        if (has_permission('admin.access')) {
            $adminMenuLink = '<hr class="account-menu-separator">'
                . '<a class="account-menu-link" href="' . e(route_url('admin')) . '">' . e((string) $layoutI18n['account_admin']) . '</a>';
        }

        $authHtml = '<details class="account-menu">'
            . '<summary class="button small account-menu-trigger">' . e($accountLabel) . '</summary>'
            . '<div class="account-menu-panel">'
            . '<a class="account-menu-link" href="' . e(route_url('profile')) . '">' . e((string) $layoutI18n['account_profile']) . '</a>'
            . '<a class="account-menu-link" href="' . e(route_url('gdpr')) . '">' . e($accountPrivacyLabel) . '</a>'
            . '<a class="account-menu-link" href="' . e(route_url('settings')) . '">' . e((string) $layoutI18n['account_settings']) . '</a>'
            . $adminMenuLink
            . '<hr class="account-menu-separator">'
            . '<form class="nav-form account-menu-form" method="post" action="' . e(route_url('logout')) . '">'
            . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
            . '<button type="submit" class="button small account-menu-logout">' . e((string) $layoutI18n['logout']) . '</button>'
            . '</form>'
            . '</div>'
            . '</details>';
    } else {
        $authHtml = '<a class="button toolbar-login-button" href="' . e(route_url('login')) . '">' . e((string) $layoutI18n['login']) . '</a>';
    }

    $siteName = (string) config('app.site_name', 'ON4CRD');
    $pageMeta = (array) ($_SESSION['_page_meta'] ?? []);
    unset($_SESSION['_page_meta']);
    $pageMeta = localized_seo_defaults($currentRoute, $currentLocale, $pageMeta, $siteName);
    $metaTitle = trim((string) ($pageMeta['title'] ?? ''));
    $pageTitle = $title !== '' ? $title : ($metaTitle !== '' ? $metaTitle : $siteName);
    $metaDescription = trim((string) ($pageMeta['description'] ?? ''));
    if ($metaDescription === '') {
        $metaDescription = 'Radio Club Durnal ON4CRD : actualités, événements, formation, ressources et vie du club radioamateur.';
    }
    $metaCanonical = trim((string) ($pageMeta['canonical'] ?? ''));
    $metaRobots = trim((string) ($pageMeta['robots'] ?? 'index,follow'));
    $metaOgType = trim((string) ($pageMeta['og_type'] ?? 'website'));
    $metaTwitterCard = trim((string) ($pageMeta['twitter_card'] ?? 'summary_large_image'));
    $metaLocale = trim((string) ($pageMeta['locale'] ?? 'fr_BE'));
    $metaSiteName = trim((string) ($pageMeta['site_name'] ?? $siteName));
    $metaGeoRegion = trim((string) ($pageMeta['geo_region'] ?? ''));
    $metaGeoPlacename = trim((string) ($pageMeta['geo_placename'] ?? ''));
    $metaGeoPosition = trim((string) ($pageMeta['geo_position'] ?? ''));
    $metaIcbm = trim((string) ($pageMeta['icbm'] ?? ''));
    $metaAlternates = (array) ($pageMeta['alternates'] ?? []);
    $metaImage = trim((string) ($pageMeta['image'] ?? ''));
    $metaImageAlt = trim((string) ($pageMeta['image_alt'] ?? $metaSiteName));
    $metaLatitude = trim((string) ($pageMeta['latitude'] ?? ''));
    $metaLongitude = trim((string) ($pageMeta['longitude'] ?? ''));
    $metaAiSummary = trim((string) ($pageMeta['ai_summary'] ?? $metaDescription));
    $metaCitationAuthor = trim((string) ($pageMeta['citation_author'] ?? 'Radio Club Durnal ON4CRD'));
    $metaKeywords = [];
    foreach (array_merge((array) ($pageMeta['keywords'] ?? []), (array) ($pageMeta['tags'] ?? [])) as $keyword) {
        $keyword = trim((string) $keyword);
        if ($keyword !== '') {
            $metaKeywords[$keyword] = true;
        }
    }
    $jsonLdItems = [];
    if (isset($pageMeta['json_ld'])) {
        $jsonLdItems = is_array($pageMeta['json_ld']) && array_is_list($pageMeta['json_ld'])
            ? $pageMeta['json_ld']
            : [$pageMeta['json_ld']];
    }
    $metaHead = '<meta name="description" content="' . e($metaDescription) . '">'
        . '<meta name="robots" content="' . e($metaRobots) . '">'
        . '<meta name="dcterms.title" content="' . e($pageTitle) . '">'
        . '<meta name="dcterms.description" content="' . e($metaAiSummary) . '">'
        . '<meta name="citation_title" content="' . e($pageTitle) . '">'
        . '<meta name="citation_author" content="' . e($metaCitationAuthor) . '">'
        . '<meta name="citation_abstract" content="' . e($metaAiSummary) . '">'
        . '<meta property="og:title" content="' . e($pageTitle) . '">'
        . '<meta property="og:description" content="' . e($metaDescription) . '">'
        . '<meta property="og:type" content="' . e($metaOgType) . '">'
        . '<meta property="og:locale" content="' . e($metaLocale) . '">'
        . '<meta property="og:site_name" content="' . e($metaSiteName) . '">'
        . '<meta name="twitter:card" content="' . e($metaTwitterCard) . '">'
        . '<meta name="twitter:title" content="' . e($pageTitle) . '">'
        . '<meta name="twitter:description" content="' . e($metaDescription) . '">';
    if ($metaImage !== '') {
        $metaHead .= '<meta property="og:image" content="' . e($metaImage) . '">'
            . '<meta property="og:image:alt" content="' . e($metaImageAlt) . '">'
            . '<meta name="twitter:image" content="' . e($metaImage) . '">'
            . '<meta name="twitter:image:alt" content="' . e($metaImageAlt) . '">';
    }
    if ($metaCanonical !== '') {
        $metaHead .= '<link rel="canonical" href="' . e($metaCanonical) . '">'
            . '<meta property="og:url" content="' . e($metaCanonical) . '">'
            . '<meta name="citation_public_url" content="' . e($metaCanonical) . '">';
    }
    if ($metaKeywords !== []) {
        $keywords = implode(', ', array_keys($metaKeywords));
        $metaHead .= '<meta name="keywords" content="' . e($keywords) . '">'
            . '<meta name="citation_keywords" content="' . e($keywords) . '">';
    }
    foreach ($metaAlternates as $hreflang => $href) {
        $lang = trim((string) $hreflang);
        $url = trim((string) $href);
        if ($lang === '' || $url === '') {
            continue;
        }
        $metaHead .= '<link rel="alternate" hreflang="' . e($lang) . '" href="' . e($url) . '">';
        if ($lang !== 'x-default') {
            $metaHead .= '<meta property="og:locale:alternate" content="' . e(locale_open_graph_code($lang)) . '">';
        }
    }
    if ($metaGeoRegion !== '') {
        $metaHead .= '<meta name="geo.region" content="' . e($metaGeoRegion) . '">';
    }
    if ($metaGeoPlacename !== '') {
        $metaHead .= '<meta name="geo.placename" content="' . e($metaGeoPlacename) . '">';
    }
    if ($metaGeoPosition !== '') {
        $metaHead .= '<meta name="geo.position" content="' . e($metaGeoPosition) . '">';
    }
    if ($metaIcbm !== '') {
        $metaHead .= '<meta name="ICBM" content="' . e($metaIcbm) . '">';
    }
    if ($metaLatitude !== '' && $metaLongitude !== '') {
        $metaHead .= '<meta property="place:location:latitude" content="' . e($metaLatitude) . '">'
            . '<meta property="place:location:longitude" content="' . e($metaLongitude) . '">';
    }
    if (!empty($pageMeta['published_time'])) {
        $publishedTime = trim((string) $pageMeta['published_time']);
        $metaHead .= '<meta property="article:published_time" content="' . e($publishedTime) . '">'
            . '<meta name="citation_publication_date" content="' . e($publishedTime) . '">';
    }
    if (!empty($pageMeta['modified_time'])) {
        $modifiedTime = trim((string) $pageMeta['modified_time']);
        $metaHead .= '<meta property="article:modified_time" content="' . e($modifiedTime) . '">'
            . '<meta property="og:updated_time" content="' . e($modifiedTime) . '">'
            . '<meta name="citation_online_date" content="' . e($modifiedTime) . '">';
    }
    if (!empty($pageMeta['section'])) {
        $metaHead .= '<meta property="article:section" content="' . e((string) $pageMeta['section']) . '">';
    }
    foreach ((array) ($pageMeta['tags'] ?? []) as $tag) {
        $tag = trim((string) $tag);
        if ($tag !== '') {
            $metaHead .= '<meta property="article:tag" content="' . e($tag) . '">';
        }
    }
    foreach ($jsonLdItems as $jsonLdItem) {
        if (!is_array($jsonLdItem) || $jsonLdItem === []) {
            continue;
        }
        try {
            $encodedJsonLd = json_encode($jsonLdItem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $metaHead .= '<script nonce="' . e(csp_nonce()) . '" type="application/ld+json">' . $encodedJsonLd . '</script>';
        } catch (Throwable) {
            continue;
        }
    }
    $year = gmdate('Y');
    $themeOptions = [
        'light' => ['icon' => '☀️', 'label' => (string) $layoutI18n['theme_light']],
        'dark' => ['icon' => '🌙', 'label' => (string) $layoutI18n['theme_dark']],
    ];
    $languageOptions = [
        'fr' => ['icon' => '🇫🇷', 'label' => 'Français'],
        'en' => ['icon' => '🇬🇧', 'label' => 'English'],
        'de' => ['icon' => '🇩🇪', 'label' => 'Deutsch'],
        'nl' => ['icon' => '🇳🇱', 'label' => 'Nederlands'],
        'es' => ['icon' => '🇪🇸', 'label' => 'Español'],
        'it' => ['icon' => '🇮🇹', 'label' => 'Italiano'],
        'pt' => ['icon' => '🇵🇹', 'label' => 'Português'],
        'bg' => ['icon' => '🇧🇬', 'label' => 'Български'],
        'hr' => ['icon' => '🇭🇷', 'label' => 'Hrvatski'],
        'cs' => ['icon' => '🇨🇿', 'label' => 'Čeština'],
        'da' => ['icon' => '🇩🇰', 'label' => 'Dansk'],
        'et' => ['icon' => '🇪🇪', 'label' => 'Eesti'],
        'fi' => ['icon' => '🇫🇮', 'label' => 'Suomi'],
        'el' => ['icon' => '🇬🇷', 'label' => 'Ελληνικά'],
        'hu' => ['icon' => '🇭🇺', 'label' => 'Magyar'],
        'ga' => ['icon' => '🇮🇪', 'label' => 'Gaeilge'],
        'lv' => ['icon' => '🇱🇻', 'label' => 'Latviešu'],
        'lt' => ['icon' => '🇱🇹', 'label' => 'Lietuvių'],
        'mt' => ['icon' => '🇲🇹', 'label' => 'Malti'],
        'pl' => ['icon' => '🇵🇱', 'label' => 'Polski'],
        'ro' => ['icon' => '🇷🇴', 'label' => 'Română'],
        'sk' => ['icon' => '🇸🇰', 'label' => 'Slovenčina'],
        'sl' => ['icon' => '🇸🇮', 'label' => 'Slovenščina'],
        'sv' => ['icon' => '🇸🇪', 'label' => 'Svenska'],
        'ar' => ['icon' => '🇸🇦', 'label' => 'العربية'],
        'hi' => ['icon' => '🇮🇳', 'label' => 'हिन्दी'],
        'ja' => ['icon' => '🇯🇵', 'label' => '日本語'],
        'zh' => ['icon' => '🇨🇳', 'label' => '中文'],
        'bn' => ['icon' => '🇧🇩', 'label' => 'বাংলা'],
        'ru' => ['icon' => '🇷🇺', 'label' => 'Русский'],
        'id' => ['icon' => '🇮🇩', 'label' => 'Bahasa Indonesia'],
    ];
    $accentIcons = [
        'blue' => '🔵',
        'emerald' => '🟢',
        'violet' => '🟣',
        'red' => '🔴',
        'amber' => '🟡',
        'orange' => '🟠',
    ];
    $languageOptionHtml = '';
    foreach ($languageOptions as $localeCode => $localeConfig) {
        $isActive = $localeCode === $currentLocale;
        $localeLabel = (string) ($localeConfig['label'] ?? strtoupper($localeCode));
        $localeIcon = (string) ($localeConfig['icon'] ?? '');
        $languageOptionHtml .= '<option value="' . e($localeCode) . '"' . ($isActive ? ' selected' : '') . '>'
            . e(trim($localeIcon . ' ' . $localeLabel))
            . '</option>';
    }
    $themeOptionHtml = '';
    foreach ($themeOptions as $themeCode => $themeConfig) {
        $isActive = $themeCode === $currentTheme;
        $themeIcon = (string) ($themeConfig['icon'] ?? '');
        $themeLabel = (string) ($themeConfig['label'] ?? $themeCode);
        $themeOptionHtml .= '<option value="' . e($themeCode) . '"' . ($isActive ? ' selected' : '') . '>'
            . e(trim($themeIcon . ' ' . $themeLabel))
            . '</option>';
    }
    $accentOptionHtml = '';
    foreach ($accentPalette as $accentCode => $accentConfig) {
        $isActive = $accentCode === $currentAccent;
        $accentIcon = (string) ($accentIcons[$accentCode] ?? '🎨');
        $accentLabel = (string) ($layoutI18n['accent_' . $accentCode] ?? ($accentConfig['label'] ?? ucfirst($accentCode)));
        $accentDotColor = (string) ($accentConfig['color'] ?? '#2f6fed');
        $accentOptionHtml .= '<option value="' . e($accentCode) . '"' . ($isActive ? ' selected' : '') . ' style="color:' . e($accentDotColor) . ';">'
            . e(trim($accentIcon . ' ' . $accentLabel))
            . '</option>';
    }
    $languageFormHtml = '<form class="toolbar-form inline-form" method="post" action="' . e(route_url('set_language')) . '">'
        . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="return_route" value="' . e($currentRoute) . '">'
        . '<label class="sr-only" for="language-selector">' . e((string) $layoutI18n['language_choice']) . '</label>'
        . '<select id="language-selector" class="preference-select js-auto-submit" name="locale" aria-label="' . e((string) $layoutI18n['language_choice']) . '" aria-describedby="language-help">' . $languageOptionHtml . '</select>'
        . '<span class="sr-only" id="language-help">' . e((string) $layoutI18n['language_help']) . '</span>'
        . '</form>';
    $themeFormHtml = '<form class="toolbar-form" method="post" action="' . e(route_url('set_theme')) . '">'
        . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="return_route" value="' . e($currentRoute) . '">'
        . '<label class="sr-only" for="theme-selector">' . e((string) $layoutI18n['theme_choice']) . '</label>'
        . '<select id="theme-selector" class="preference-select js-auto-submit" name="theme" aria-label="' . e((string) $layoutI18n['theme_choice']) . '" aria-describedby="theme-help">' . $themeOptionHtml . '</select>'
        . '<span class="sr-only" id="theme-help">' . e((string) $layoutI18n['theme_help']) . '</span>'
        . '</form>';
    $accentFormHtml = '<form class="toolbar-form inline-form" method="post" action="' . e(route_url('set_accent')) . '">'
        . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="return_route" value="' . e($currentRoute) . '">'
        . '<label class="sr-only" for="accent-selector">' . e((string) $layoutI18n['accent_choice']) . '</label>'
        . '<select id="accent-selector" class="preference-select js-auto-submit" name="accent" aria-label="' . e((string) $layoutI18n['accent_choice']) . '" aria-describedby="accent-help">' . $accentOptionHtml . '</select>'
        . '<span class="sr-only" id="accent-help">' . e((string) $layoutI18n['accent_help']) . '</span>'
        . '</form>';
    $installButtonHtml = '<button type="button" class="button secondary" data-pwa-install hidden disabled aria-label="' . e((string) $layoutI18n['install_app']) . '">' . e((string) $layoutI18n['install_app']) . '</button>';
    $menuToolsHtml = '<div class="toolbar-preferences">'
        . '<div class="toolbar-preferences-row">' . $languageFormHtml . $themeFormHtml . '</div>'
        . '<div class="toolbar-preferences-row">' . $accentFormHtml . '<div class="toolbar-auth">' . $installButtonHtml . $authHtml . '</div></div>'
        . '</div>';
    $nonce = csp_nonce();
    $htmlDir = is_rtl_locale($currentLocale) ? 'rtl' : 'ltr';
    $moduleCssHtml = '';
    foreach (module_css_assets_for_route($currentRoute) as $moduleCssPath) {
        $moduleCssHtml .= '<link rel="stylesheet" href="' . e(asset_url($moduleCssPath)) . '">';
    }
    $moduleJsHtml = '';
    foreach (module_js_assets_for_route($currentRoute) as $moduleJsPath) {
        $moduleJsHtml .= '<script nonce="' . e($nonce) . '" src="' . e(asset_url($moduleJsPath)) . '" defer></script>';
    }

    return '<!doctype html><html lang="' . e($currentLocale) . '" dir="' . e($htmlDir) . '" class="notranslate" translate="no" data-theme="' . e($currentTheme) . '" style="--accent: ' . e($accentColor) . '; --accent-strong: ' . e($accentStrongColor) . ';"><head><meta charset="utf-8"><meta name="google" content="notranslate"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
        . e($pageTitle)
        . '</title>' . $metaHead
        . '<meta name="theme-color" content="#2f6fed">'
        . '<link rel="manifest" href="' . e(asset_url('manifest.webmanifest')) . '">'
        . '<link rel="alternate" type="text/plain" title="LLM context" href="' . e(base_url('llms.txt')) . '">'
        . '<link rel="alternate" type="application/json" title="AI content index" href="' . e(base_url('ai-index.json')) . '">'
        . '<link rel="alternate" type="application/ld+json" title="ON4CRD knowledge graph" href="' . e(base_url('knowledge-graph.jsonld')) . '">'
        . '<link rel="icon" href="' . e(asset_url('assets/icons/icon.svg')) . '" type="image/svg+xml">'
        . '<link rel="apple-touch-icon" href="' . e(asset_url('assets/icons/apple-touch-icon.png')) . '">'
        . '<link rel="stylesheet" href="' . e(asset_url('assets/css/app.css')) . '">'
        . $moduleCssHtml
        . '<script nonce="' . e($nonce) . '" src="https://cdn.tailwindcss.com"></script>'
        . '<script nonce="' . e($nonce) . '">tailwind.config={theme:{extend:{colors:{club:{900:"#0f172a",700:"#1d4ed8",500:"#3b82f6",100:"#dbeafe"}}}}};</script>'
        . '</head><body data-route="' . e($currentRoute) . '" data-sw-url="' . e(base_url('sw.js')) . '">'
        . '<a class="skip-link" href="#main-content">' . e((string) ($layoutI18n['skip_to_content'] ?? 'Skip to content')) . '</a>'
        . '<header class="topbar"><div class="brand-wrap"><div class="brand-mark"><img class="brand-mark-img" src="' . e(asset_url('assets/logo/LOGO-CRD-HALO-2020.png')) . '" alt="Logo ON4CRD"></div><a class="brand" href="' . e(route_url('home')) . '">'
        . '<span class="brand-title">ON4CRD.be</span><span class="brand-subtitle">Club Radio Durnal</span></a></div>'
        . '<button class="menu-toggle button secondary" type="button" aria-controls="main-nav" aria-expanded="false"><span aria-hidden="true">☰</span><span class="menu-label">Menu</span></button>'
        . '<button class="nav-backdrop" type="button" aria-label="' . e((string) ($layoutI18n['close_menu'] ?? 'Close menu')) . '" hidden></button>'
        . '<nav id="main-nav" class="nav" aria-label="' . e((string) ($layoutI18n['main_navigation'] ?? 'Main navigation')) . '">' . $navHtml . '<div class="nav-mobile-tools">' . $menuToolsHtml . '</div></nav>'
        . '<div class="toolbar">' . $menuToolsHtml . '</div></header>'
        . '<main id="main-content" class="layout container py-6">' . $flashHtml . $content . '</main>'
        . render_site_footer($currentRoute)
        . '<script nonce="' . e($nonce) . '" src="' . e(asset_url('assets/js/app.js')) . '" defer></script>'
        . $moduleJsHtml
        . '</body></html>';
}
}

function is_https_request(): bool
{
    $forwardedProtoHeader = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    $forwardedProto = $forwardedProtoHeader !== '' ? trim(explode(',', $forwardedProtoHeader)[0]) : '';
    $serverPort = (string) ($_SERVER['SERVER_PORT'] ?? '');

    return (
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || ($serverPort === '443')
        || ($forwardedProto === 'https')
    );
}

function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    return $nonce;
}


function mb_safe_substr(string $value, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null ? mb_substr($value, $start) : mb_substr($value, $start, $length);
    }

    return $length === null ? substr($value, $start) : substr($value, $start, $length);
}

function mb_safe_strtolower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function mb_safe_strtoupper(string $value): string
{
    return function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
}

function mb_safe_strimwidth(string $value, int $start, int $width, string $trimMarker = ''): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, $start, $width, $trimMarker);
    }

    $slice = substr($value, $start, $width);

    if (strlen($value) > ($start + $width) && $trimMarker !== '') {
        return rtrim($slice) . $trimMarker;
    }

    return $slice;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'n-a';
    }

    if (function_exists('iconv')) {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) {
            $value = $transliterated;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'n-a';
}

function sanitize_href_attribute(string $url): ?string
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return null;
    }

    if (preg_match('/^(?:javascript|data|vbscript):/i', $trimmed) === 1) {
        return null;
    }

    try {
        return normalize_http_url($trimmed, true);
    } catch (Throwable) {
        return null;
    }
}

function sanitize_image_src_attribute(string $url): ?string
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return null;
    }
    if (preg_match('/^data:image\\/(?:png|jpe?g|gif|webp);base64,[a-z0-9+\\/=]+$/i', $trimmed) === 1) {
        return $trimmed;
    }

    return sanitize_href_attribute($trimmed);
}

function sanitize_rich_html(string $html): string
{
    if ($html === '') {
        return '';
    }

    $dom = new DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $wrapped = '<!doctype html><html><body>' . $html . '</body></html>';
    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    $removeTags = ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'base'];
    foreach ($removeTags as $tag) {
        while (($nodes = $dom->getElementsByTagName($tag))->length > 0) {
            $node = $nodes->item(0);
            if ($node !== null && $node->parentNode !== null) {
                $node->parentNode->removeChild($node);
            } else {
                break;
            }
        }
    }

    $allNodes = $dom->getElementsByTagName('*');
    for ($i = $allNodes->length - 1; $i >= 0; $i--) {
        $node = $allNodes->item($i);
        if (!$node instanceof DOMElement || !$node->hasAttributes()) {
            continue;
        }
        $toRemove = [];
        foreach ($node->attributes as $attribute) {
            $name = strtolower($attribute->name);
            if (str_starts_with($name, 'on')) {
                $toRemove[] = $attribute->name;
                continue;
            }
            if ($name === 'href') {
                $safe = sanitize_href_attribute($attribute->value);
                if ($safe === null) {
                    $toRemove[] = $attribute->name;
                } else {
                    $node->setAttribute('href', $safe);
                }
            }
            if ($name === 'src') {
                $safe = sanitize_image_src_attribute($attribute->value);
                if ($safe === null) {
                    $toRemove[] = $attribute->name;
                } else {
                    $node->setAttribute('src', $safe);
                }
            }
        }
        foreach ($toRemove as $attrName) {
            $node->removeAttribute($attrName);
        }
        if (strtolower($node->tagName) === 'img' && !$node->hasAttribute('loading')) {
            $node->setAttribute('loading', 'lazy');
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) {
        return '';
    }

    $result = '';
    foreach ($body->childNodes as $child) {
        $result .= $dom->saveHTML($child);
    }

    return $result;
}
