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

if (!function_exists('route_url_with_locale')) {
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
    $discoveryRoutes = ['install.php', 'sitemap.xml', 'robots.txt', 'llms.txt', 'ai-index.json', 'knowledge-graph.jsonld'];
    $canonicalRoute = in_array($route, $discoveryRoutes, true) ? $route : $routeKey;
    $routeSeo = [
        'ad_click' => ['title' => 'Redirection partenaire ON4CRD', 'description' => 'Redirection securisee vers une annonce ou un partenaire du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,nofollow'],
        'admin' => ['title' => 'Administration ON4CRD', 'description' => 'Tableau d administration du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,nofollow'],
        'ads' => ['title' => 'Annonces partenaires ON4CRD', 'description' => 'Annonces, partenaires et communications sponsorisees du Radio Club Durnal ON4CRD.'],
        'aiindexjson' => ['title' => 'AI index ON4CRD', 'description' => 'Index JSON public des contenus ON4CRD pour assistants et moteurs generatifs.', 'robots' => 'noindex,follow'],
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
        'change_password' => ['title' => 'Modification du mot de passe ON4CRD', 'description' => 'Modification securisee du mot de passe membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'classifieds' => ['title' => 'Petites annonces ON4CRD', 'description' => 'Petites annonces radioamateurs et materiel entre membres et visiteurs ON4CRD.'],
        'code_cw' => ['title' => 'Code CW et Morse', 'description' => 'Ressources ON4CRD pour apprendre, reviser et pratiquer le code Morse CW.'],
        'code_q' => ['title' => 'Code Q radioamateur', 'description' => 'Liste des codes Q utiles pour le trafic radioamateur et les echanges ON4CRD.'],
        'committee' => ['title' => 'Comite ON4CRD', 'description' => 'Presentation du comite et de l organisation du Radio Club Durnal ON4CRD.'],
        'conditions_utilisation' => ['title' => 'Conditions d utilisation ON4CRD', 'description' => 'Conditions generales d utilisation du site du Radio Club Durnal ON4CRD.'],
        'dashboard' => ['title' => 'Tableau de bord ON4CRD', 'description' => 'Tableau de bord personnel de l espace membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'dashboard_widget_card' => ['title' => 'Widget dashboard ON4CRD', 'description' => 'Fragment technique de widget du tableau de bord membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'directory' => ['title' => 'Annuaire des membres ON4CRD', 'description' => 'Annuaire radioamateur des membres visibles du Radio Club Durnal ON4CRD, indicatifs, licences et QTH.'],
        'donation' => ['title' => 'Faire un don au Radio Club Durnal', 'description' => 'Page dediee aux dons ponctuels pour soutenir les activites, le materiel et les projets radioamateurs ON4CRD.'],
        'event_view' => ['title' => 'Detail evenement ON4CRD', 'description' => 'Detail d un evenement, d une reunion ou d une activite radioamateur du Radio Club Durnal.'],
        'events' => ['title' => 'Agenda ON4CRD', 'description' => 'Agenda des reunions, activites, sorties et evenements radioamateurs du Radio Club Durnal.'],
        'events_feed' => ['title' => 'Flux calendrier ON4CRD', 'description' => 'Flux technique des evenements ON4CRD pour FullCalendar et exports.', 'robots' => 'noindex,nofollow'],
        'footer_contact' => ['title' => 'Contact ON4CRD', 'description' => 'Endpoint de contact du pied de page ON4CRD.', 'robots' => 'noindex,nofollow'],
        'forgot_password' => ['title' => 'Mot de passe oublie ON4CRD', 'description' => 'Procedure de recuperation d acces a l espace membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'gdpr' => ['title' => 'Vie privée ON4CRD', 'description' => 'Gestion des préférences de confidentialité et de visibilité du profil membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'home' => ['title' => 'Radio Club Durnal ON4CRD', 'description' => 'Portail du Radio Club Durnal ON4CRD : actualites, evenements, outils et ressources radioamateurs.'],
        'installphp' => ['title' => 'Installation ON4CRD', 'description' => 'Endpoint d installation technique ON4CRD.', 'robots' => 'noindex,nofollow'],
        'knowledgegraphjsonld' => ['title' => 'Knowledge graph ON4CRD', 'description' => 'Graphe JSON-LD public du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,follow'],
        'llmstxt' => ['title' => 'LLMS ON4CRD', 'description' => 'Fichier de contexte public pour assistants et moteurs de recherche.', 'robots' => 'noindex,follow'],
        'login' => ['title' => 'Connexion membre ON4CRD', 'description' => 'Connexion securisee a l espace membre du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,nofollow'],
        'logout' => ['title' => 'Deconnexion ON4CRD', 'description' => 'Deconnexion securisee de l espace membre ON4CRD.', 'robots' => 'noindex,nofollow'],
        'members_library' => ['title' => 'Bibliotheque membres ON4CRD', 'description' => 'Bibliotheque documentaire reservee aux membres du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,follow'],
        'presentations' => ['title' => 'Presentations membres ON4CRD', 'description' => 'Presentations et supports reserves aux membres du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,follow'],
        'videos' => ['title' => 'Videos membres ON4CRD', 'description' => 'Videos et ressources audiovisuelles reservees aux membres du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,follow'],
        'pv' => ['title' => 'PV membres ON4CRD', 'description' => 'Proces-verbaux et comptes rendus reserves aux membres du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,follow'],
        'fichiers' => ['title' => 'Fichiers membres ON4CRD', 'description' => 'Fichiers et ressources a telecharger reserves aux membres du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,follow'],
        'telechargements' => ['title' => 'Fichiers membres ON4CRD', 'description' => 'Fichiers et ressources a telecharger reserves aux membres du Radio Club Durnal ON4CRD.', 'robots' => 'noindex,follow'],
        'membership' => ['title' => 'Devenir membre du CRD', 'description' => 'Informations pour devenir membre du Radio Club Durnal, rejoindre les activites et participer a la communaute ON4CRD.'],
        'mentions_legales' => ['title' => 'Mentions legales ON4CRD', 'description' => 'Mentions legales et informations editoriales du site Radio Club Durnal ON4CRD.'],
        'my_requests' => ['title' => 'Mes demandes ON4CRD', 'description' => 'Suivi des demandes et raccourcis utiles de l espace membre ON4CRD.', 'robots' => 'noindex,nofollow'],
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
        'admin_live_feeds', 'admin_members', 'admin_modules', 'admin_news', 'admin_newsletters', 'admin_permissions', 'admin_privacy',
        'admin_presentations', 'admin_videos', 'admin_pv', 'admin_fichiers', 'admin_telechargements',
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
        'content_type' => 'public_webpage',
        'ai_summary' => $description,
        'alternates' => $alternates,
        'robots' => (string) ($routeSeo[$routeKey]['robots'] ?? 'index,follow'),
    ], array_filter($pageMeta, static fn($value): bool => $value !== null && $value !== ''));
    $defaults['alternates'] = $alternates;
    if (!isset($defaults['json_ld'])) {
        $defaults['json_ld'] = [
            '@context' => 'https://schema.org',
            '@type' => (string) ($defaults['schema_type'] ?? 'WebPage'),
            '@id' => (string) $defaults['canonical'] . '#webpage',
            'name' => (string) $defaults['title'],
            'description' => (string) $defaults['description'],
            'url' => (string) $defaults['canonical'],
            'inLanguage' => $locale,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => route_url_with_locale('home', $locale),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Radio Club Durnal ON4CRD',
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
        if (!empty($defaults['image'])) {
            $defaults['json_ld']['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                'url' => (string) $defaults['image'],
            ];
        }
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
}

if (!function_exists('render_layout')) {
function render_layout(string $content, string $title = ''): string
{
    require_once __DIR__ . '/layout_renderer.php';

    return render_layout_impl($content, $title);
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
    $wrapped = '<?xml encoding="UTF-8"><!doctype html><html><body>' . $html . '</body></html>';
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
