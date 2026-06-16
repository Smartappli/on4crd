<?php
declare(strict_types=1);

if (!function_exists('layout_option_map')) {
function layout_option_map(array $values): array
{
    return $values;
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
        $name = (string) $social['name'];
        $href = (string) $social['href'];
        $path = (string) $social['path'];
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

function module_css_assets_for_route(string $route): array
{
    $route = preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: 'home';
    $moduleByRoute = [
        'album' => 'albums',
        'article_propose' => 'articles',
        'auction_bid' => 'auctions',
        'auction_view' => 'auctions',
        'presentations' => 'member_documents',
        'videos' => 'member_documents',
        'pv' => 'member_documents',
        'fichiers' => 'member_documents',
        'telechargements' => 'member_documents',
        'admin_presentations' => 'admin_member_documents',
        'admin_videos' => 'admin_member_documents',
        'admin_pv' => 'admin_member_documents',
        'admin_fichiers' => 'admin_member_documents',
        'admin_telechargements' => 'admin_member_documents',
        'classifieds_manage' => 'classifieds',
        'admin_webotheque' => 'webotheque',
        'event_view' => 'events',
        'news_view' => 'news',
        'wiki_edit' => 'wiki',
        'wiki_propose' => 'wiki',
        'wiki_view' => 'wiki',
    ];
    $module = $moduleByRoute[$route] ?? $route;
    $sharedPath = 'assets/css/modules/shared.css';
    $assets = is_file(dirname(__DIR__) . '/' . $sharedPath) ? [$sharedPath] : [];

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

if (!function_exists('render_bandplan_page')) {
/**
 * @param array<string, string> $messages
 * @param list<array{0: string, 1: string, 2: string, 3: string, 4: string}> $rows
 */
function render_bandplan_page(string $title, array $messages, array $rows, string $cardClass = ''): string
{
    $sectionClass = trim('card ' . $cardClass);

    ob_start();
    ?>
<section class="<?= e($sectionClass) ?>">
    <h1><?= e($title) ?></h1>
    <p class="help">IBPT/BIPT: <a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">Freq-FR.pdf</a></p>
    <div class="table-wrap mt-3">
        <table>
            <thead>
            <tr>
                <th><?= e((string) $messages['header_band']) ?></th>
                <th><?= e((string) $messages['header_freq']) ?></th>
                <th><?= e((string) $messages['header_modes']) ?></th>
                <th><?= e((string) $messages['header_power']) ?></th>
                <th><?= e((string) $messages['header_notes']) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row[0]) ?></td>
                    <td><?= e($row[1]) ?></td>
                    <td><?= e($row[2]) ?></td>
                    <td><?= e($row[3]) ?></td>
                    <td><?= e((string) ($messages[$row[4]] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
    <?php

    return render_layout((string) ob_get_clean(), $title);
}
}

function module_js_assets_for_route(string $route): array
{
    $route = preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: 'home';
    $moduleByRoute = [
        'event_view' => 'events',
        'article_propose' => 'articles',
        'save_dashboard' => 'dashboard',
        'widget_render' => 'dashboard',
        'admin_webotheque' => 'webotheque',
        'presentations' => 'member_documents',
        'videos' => 'member_documents',
        'wiki_edit' => 'wiki_edit',
        'wiki_propose' => 'wiki_edit',
        'wiki_view' => 'wiki',
    ];
    $module = $moduleByRoute[$route] ?? $route;
    $assets = [];

    $candidates = [$module];
    if ($route === 'home') {
        $candidates[] = 'tools';
    }
    if (str_starts_with($route, 'admin_') || in_array($route, ['ads', 'article_propose', 'classifieds', 'classifieds_manage', 'wiki_edit', 'wiki_propose', 'wiki_view'], true)) {
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

function module_preload_assets_for_route(string $route): array
{
    $route = preg_replace('/[^a-z0-9_]/', '', strtolower($route)) ?: 'home';
    $heroImages = [
        'admin_webotheque' => 'assets/img/webotheque-hero-bg.png',
        'albums' => 'assets/img/module-hero-albums.png',
        'article_propose' => 'assets/img/module-hero-articles.png',
        'articles' => 'assets/img/module-hero-articles.png',
        'auctions' => 'assets/img/module-hero-auctions.png',
        'chatbot' => 'assets/img/module-hero-chatbot.png',
        'classifieds' => 'assets/img/module-hero-classifieds.png',
        'classifieds_manage' => 'assets/img/module-hero-classifieds.png',
        'directory' => 'assets/img/module-hero-directory.png',
        'events' => 'assets/img/module-hero-events.png',
        'fichiers' => 'assets/img/module-hero-files.png',
        'members_library' => 'assets/img/module-hero-library.png',
        'my_requests' => 'assets/img/module-hero-requests.png',
        'news' => 'assets/img/module-hero-news.png',
        'newsletter' => 'assets/img/module-hero-newsletter.png',
        'notifications' => 'assets/img/module-hero-notifications.png',
        'presentations' => 'assets/img/module-hero-presentations.png',
        'profile' => 'assets/img/module-hero-profile.png',
        'pv' => 'assets/img/module-hero-minutes.png',
        'qsl' => 'assets/img/module-hero-qsl.png',
        'search' => 'assets/img/module-hero-search.png',
        'settings' => 'assets/img/module-hero-settings.png',
        'tools' => 'assets/img/module-hero-tools.png',
        'videos' => 'assets/img/module-hero-videos.png',
        'webotheque' => 'assets/img/webotheque-hero-bg.png',
        'wiki' => 'assets/img/module-hero-wiki.png',
        'wiki_edit' => 'assets/img/module-hero-wiki.png',
        'wiki_propose' => 'assets/img/module-hero-wiki.png',
        'wiki_view' => 'assets/img/module-hero-wiki.png',
    ];
    $assets = [];
    $imagePath = $heroImages[$route] ?? '';
    if ($imagePath !== '' && is_file(dirname(__DIR__) . '/' . $imagePath)) {
        $assets[] = ['as' => 'image', 'href' => asset_url($imagePath)];
    }

    return $assets;
}

if (!function_exists('render_layout_impl')) {
function render_layout_impl(string $content, string $title = ''): string
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
    $ideaI18n = i18n_domain_locale('idea', $currentLocale);
    $ideaText = static function (string $key, string $fallback) use ($ideaI18n): string {
        $value = trim((string) ($ideaI18n[$key] ?? ''));

        return $value !== '' ? $value : $fallback;
    };
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
        ['label' => 'Ham Academy', 'url' => 'https://ham.academy', 'module' => ''],
        ['label' => (string) $layoutI18n['nav_events'], 'route' => 'events', 'module' => 'events'],
        ['label' => (string) $layoutI18n['nav_tools'], 'route' => 'tools', 'module' => ''],
        ['label' => (string) $layoutI18n['search_submit'], 'route' => 'search', 'module' => ''],
        ['label' => (string) ($layoutI18n['nav_idea'] ?? 'Idée'), 'dialog' => 'idea-dialog', 'module' => ''],
        ['label' => (string) $layoutI18n['nav_directory'], 'route' => 'directory', 'module' => 'directory'],
    ];
    $navMemberItems = [
        ['label' => (string) $layoutI18n['nav_dashboard'], 'route' => 'dashboard', 'module' => 'dashboard'],
        ['label' => (string) $layoutI18n['nav_wiki'], 'route' => 'wiki', 'module' => 'wiki'],
        ['label' => (string) $layoutI18n['nav_articles'], 'route' => 'articles', 'module' => 'articles'],
        ['label' => (string) $layoutI18n['nav_shop'], 'route' => 'classifieds', 'module' => 'classifieds'],
        ['label' => 'QSL', 'route' => 'qsl', 'module' => 'qsl'],
        ['label' => (string) $layoutI18n['nav_auctions'], 'route' => 'auctions', 'module' => 'auctions'],
        ['label' => (string) $layoutI18n['nav_assistant'], 'route' => 'chatbot', 'module' => 'chatbot'],
    ];
    $navMemberLibraryItems = [
        ['label' => (string) $layoutI18n['nav_library'], 'route' => 'members_library', 'module' => ''],
        ['label' => (string) ($layoutI18n['nav_webotheque'] ?? 'Webothèque'), 'route' => 'webotheque', 'module' => 'webotheque'],
        ['label' => (string) ($layoutI18n['nav_presentations'] ?? 'Présentations'), 'route' => 'presentations', 'module' => 'presentations'],
        ['label' => (string) ($layoutI18n['nav_gallery'] ?? 'Photos'), 'route' => 'albums', 'module' => 'albums'],
        ['label' => (string) ($layoutI18n['nav_videos'] ?? 'Vidéos'), 'route' => 'videos', 'module' => 'videos'],
        ['label' => (string) ($layoutI18n['nav_files'] ?? 'Fichiers'), 'route' => 'fichiers', 'module' => 'fichiers'],
        ['label' => (string) ($layoutI18n['nav_minutes'] ?? 'Procès verbaux'), 'route' => 'pv', 'module' => 'pv'],
    ];

    $buildNavLinks = static function (array $items, string $currentRoute): string {
        $links = '';
        foreach ($items as $item) {
            $module = (string) ($item['module'] ?? '');
            if ($module !== '' && !module_enabled($module)) {
                continue;
            }

            $dialogId = trim((string) ($item['dialog'] ?? ''));
            if ($dialogId !== '') {
                $links .= '<button type="button" class="nav-link-button transition-colors duration-200" data-idea-modal-open aria-haspopup="dialog" aria-controls="' . e($dialogId) . '">'
                    . e((string) $item['label']) . '</button>';
                continue;
            }

            $externalUrl = trim((string) ($item['url'] ?? ''));
            if ($externalUrl !== '') {
                $externalAttrs = str_starts_with($externalUrl, 'http://') || str_starts_with($externalUrl, 'https://')
                    ? ' target="_blank" rel="noopener noreferrer"'
                    : '';
                $links .= '<a class="transition-colors duration-200" href="' . e($externalUrl) . '"' . $externalAttrs . '>'
                    . e((string) $item['label']) . '</a>';
                continue;
            }

            $route = (string) $item['route'];
            $query = isset($item['query']) && is_array($item['query']) ? clean_query_params($item['query']) : [];
            $queryMatches = true;
            foreach ($query as $key => $value) {
                if ((string) ($_GET[(string) $key] ?? '') !== (string) $value) {
                    $queryMatches = false;
                    break;
                }
            }
            $isCurrent = ($currentRoute === $route || ($currentRoute === '' && $route === 'home')) && ($query === [] || $queryMatches);
            $links .= '<a class="transition-colors duration-200" href="' . e(route_url_clean($route, $query)) . '"' . ($isCurrent ? ' aria-current="page"' : '') . '>'
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
        $memberLibraryLinks = $buildNavLinks($navMemberLibraryItems, $currentRoute);
        if ($memberLibraryLinks !== '') {
            $navHtml .= '<div class="nav-row nav-row-member nav-row-member-secondary">' . $memberLibraryLinks . '</div>';
        }
    }

    $authHtml = '';
    $membershipBadgeHtml = '';
    if ($user !== null) {
        $accountLabel = trim((string) ($user['callsign'] ?? '')) !== '' ? (string) $user['callsign'] : (string) $layoutI18n['account_space'];
        $membershipBadgeLabel = (string) ($layoutI18n['membership_good_standing'] ?? 'En ordre de cotisation');
        $membershipBadgeHtml = '<span class="membership-status-badge">' . e($membershipBadgeLabel) . '</span>';
        $accountPrivacyLabel = (string) ($layoutI18n['account_privacy'] ?? 'Vie privée');
        $adminMenuLink = '';
        if (has_permission('admin.access')) {
            $adminMenuLink = '<hr class="account-menu-separator">'
                . '<a class="account-menu-link" href="' . e(route_url('admin')) . '">' . e((string) $layoutI18n['account_admin']) . '</a>';
        }

        $authHtml = '<div class="toolbar-account-stack"><details class="account-menu">'
            . '<summary class="button small account-menu-trigger">' . e($accountLabel) . '</summary>'
            . '<div class="account-menu-panel">'
            . '<a class="account-menu-link" href="' . e(route_url('profile')) . '">' . e((string) $layoutI18n['account_profile']) . '</a>'
            . '<a class="account-menu-link" href="' . e(route_url('gdpr')) . '">' . e($accountPrivacyLabel) . '</a>'
            . '<a class="account-menu-link" href="' . e(route_url('my_requests')) . '">' . e((string) ($layoutI18n['account_requests'] ?? 'Mes demandes')) . '</a>'
            . '<a class="account-menu-link" href="' . e(route_url('settings')) . '">' . e((string) $layoutI18n['account_settings']) . '</a>'
            . $adminMenuLink
            . '<hr class="account-menu-separator">'
            . '<form class="nav-form account-menu-form" method="post" action="' . e(route_url('logout')) . '">'
            . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
            . '<button type="submit" class="button small account-menu-logout">' . e((string) $layoutI18n['logout']) . '</button>'
            . '</form>'
            . '</div>'
            . '</details></div>';
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
    $metaContentType = trim((string) ($pageMeta['content_type'] ?? $pageMeta['schema_type'] ?? 'WebPage'));
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
        . '<meta name="language" content="' . e($currentLocale) . '">'
        . '<meta name="ai-summary" content="' . e($metaAiSummary) . '">'
        . '<meta name="answer-engine-summary" content="' . e($metaAiSummary) . '">'
        . '<meta name="content-type" content="' . e($metaContentType) . '">'
        . '<meta name="dcterms.title" content="' . e($pageTitle) . '">'
        . '<meta name="dcterms.description" content="' . e($metaAiSummary) . '">'
        . '<meta name="dcterms.language" content="' . e($currentLocale) . '">'
        . '<meta name="dcterms.publisher" content="' . e($metaSiteName) . '">'
        . '<meta name="dcterms.type" content="' . e($metaContentType) . '">'
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
            . '<meta name="dcterms.subject" content="' . e($keywords) . '">'
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
    $accentIcons = layout_option_map($accentIcons);
    $languageOptionHtml = '';
    foreach ($languageOptions as $localeCode => $localeConfig) {
        $isActive = $localeCode === $currentLocale;
        $localeLabel = (string) $localeConfig['label'];
        $localeIcon = (string) $localeConfig['icon'];
        $languageOptionHtml .= '<option value="' . e($localeCode) . '"' . ($isActive ? ' selected' : '') . '>'
            . e(trim($localeIcon . ' ' . $localeLabel))
            . '</option>';
    }
    $themeOptionHtml = '';
    foreach ($themeOptions as $themeCode => $themeConfig) {
        $isActive = $themeCode === $currentTheme;
        $themeIcon = (string) $themeConfig['icon'];
        $themeLabel = (string) $themeConfig['label'];
        $themeOptionHtml .= '<option value="' . e($themeCode) . '"' . ($isActive ? ' selected' : '') . '>'
            . e(trim($themeIcon . ' ' . $themeLabel))
            . '</option>';
    }
    $accentOptionHtml = '';
    foreach ($accentPalette as $accentCode => $accentConfig) {
        $accentCode = strtolower((string) $accentCode);
        $isActive = $accentCode === $currentAccent;
        $accentIcon = (string) ($accentIcons[$accentCode] ?? '🎨');
        $accentLabel = (string) ($layoutI18n['accent_' . $accentCode] ?? $accentConfig['label']);
        $accentDotColor = (string) $accentConfig['color'];
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
    $menuToolsHtml = '<div class="toolbar-preferences-stack"><div class="toolbar-preferences">'
        . '<div class="toolbar-preferences-row">' . $languageFormHtml . $themeFormHtml . '</div>'
        . '<div class="toolbar-preferences-row">' . $accentFormHtml . '<div class="toolbar-auth">' . $installButtonHtml . $authHtml . '</div></div>'
        . '</div>' . $membershipBadgeHtml . '</div>';
    $returnQuery = $_GET;
    unset($returnQuery['route'], $returnQuery['_csrf']);
    $returnRoute = preg_match('/^[a-z0-9_]+$/', $currentRoute) === 1 ? $currentRoute : 'home';
    $currentReturnUrl = route_url_clean($returnRoute, $returnQuery);
    $ideaCategoryOptions = [
        'general' => 'General',
        'activity' => 'Activite club',
        'training' => 'Formation',
        'technical' => 'Technique',
        'website' => 'Site web',
        'equipment' => 'Materiel',
        'event' => 'Evenement',
    ];
    $ideaCategoryOptionHtml = '';
    foreach ($ideaCategoryOptions as $ideaCategoryCode => $ideaCategoryLabel) {
        $ideaCategoryOptionHtml .= '<option value="' . e($ideaCategoryCode) . '">' . e($ideaCategoryLabel) . '</option>';
    }
    $ideaDialogHtml = '<dialog id="idea-dialog" class="idea-dialog" aria-labelledby="idea-dialog-title" aria-describedby="idea-dialog-intro">'
        . '<div class="idea-dialog-card">'
        . '<div class="idea-dialog-header"><div>'
        . '<p class="idea-dialog-eyebrow">' . e((string) ($layoutI18n['nav_idea'] ?? 'Idée')) . '</p>'
        . '<h2 id="idea-dialog-title">' . e($ideaText('title', 'Faire part d’une idée')) . '</h2>'
        . '<p id="idea-dialog-intro">' . e($ideaText('intro', 'Envoyez une idée, une suggestion ou une amélioration au comité.')) . '</p>'
        . '</div><button type="button" class="idea-dialog-close" data-idea-modal-close aria-label="' . e($ideaText('close', 'Fermer')) . '">&times;</button></div>'
        . '<form class="idea-form" method="post" action="' . e(route_url('idea_submit')) . '">'
        . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="return_url" value="' . e($currentReturnUrl) . '">'
        . '<div class="idea-form-grid">'
        . '<label for="idea-category"><span>' . e($ideaText('category_label', 'Thematique')) . '</span><select id="idea-category" name="idea_category">' . $ideaCategoryOptionHtml . '</select></label>'
        . '<label for="idea-keywords"><span>' . e($ideaText('keywords_label', 'Mots cles')) . '</span><input id="idea-keywords" type="text" name="idea_keywords" maxlength="255" placeholder="' . e($ideaText('keywords_placeholder', 'ex: antennes, formation')) . '"></label>'
        . '</div>'
        . '<div class="idea-form-grid">'
        . '<label for="idea-name"><span>' . e($ideaText('name_label', 'Votre nom')) . '</span><input id="idea-name" type="text" name="idea_name" maxlength="160" autocomplete="name" required placeholder="' . e($ideaText('name_placeholder', 'Nom et indicatif, si applicable')) . '"></label>'
        . '<label for="idea-email"><span>' . e($ideaText('email_label', 'Votre e-mail')) . '</span><input id="idea-email" type="email" name="idea_email" maxlength="190" autocomplete="email" required placeholder="' . e($ideaText('email_placeholder', 'vous@example.com')) . '"></label>'
        . '</div>'
        . '<label for="idea-title"><span>' . e($ideaText('idea_title_label', 'Titre de l’idée')) . '</span><input id="idea-title" type="text" name="idea_title" maxlength="190" required placeholder="' . e($ideaText('idea_title_placeholder', 'Résumez votre idée')) . '"></label>'
        . '<label for="idea-message"><span>' . e($ideaText('message_label', 'Votre idée')) . '</span><textarea id="idea-message" name="idea_message" rows="6" maxlength="4000" required placeholder="' . e($ideaText('message_placeholder', 'Décrivez votre idée, le besoin et l’intérêt pour le club.')) . '"></textarea></label>'
        . '<div class="idea-dialog-actions"><button type="button" class="button secondary" data-idea-modal-close>' . e($ideaText('cancel', 'Annuler')) . '</button><button type="submit" class="button">' . e($ideaText('submit', 'Envoyer l’idée')) . '</button></div>'
        . '</form>'
        . '</div>'
        . '</dialog>';
    $nonce = csp_nonce();
    $htmlDir = is_rtl_locale($currentLocale) ? 'rtl' : 'ltr';
    $moduleCssHtml = '';
    foreach (module_css_assets_for_route($currentRoute) as $moduleCssPath) {
        $moduleCssHtml .= '<link rel="stylesheet" href="' . e(asset_url($moduleCssPath)) . '">';
    }
    $preloadHtml = '';
    foreach (module_preload_assets_for_route($currentRoute) as $asset) {
        $preloadAs = trim((string) ($asset['as'] ?? ''));
        $preloadHref = trim((string) ($asset['href'] ?? ''));
        if ($preloadAs !== '' && $preloadHref !== '') {
            $fetchPriority = $preloadAs === 'image' ? ' fetchpriority="high"' : '';
            $preloadHtml .= '<link rel="preload" as="' . e($preloadAs) . '" href="' . e($preloadHref) . '"' . $fetchPriority . '>';
        }
    }
    $moduleJsHtml = '';
    foreach (module_js_assets_for_route($currentRoute) as $moduleJsPath) {
        $moduleJsHtml .= '<script nonce="' . e($nonce) . '" src="' . e(asset_url($moduleJsPath)) . '" defer></script>';
    }
    $matomoHtml = '';
    $matomoIncludePath = __DIR__ . '/includes/matomo.php';
    if (is_file($matomoIncludePath)) {
        $matomoOptions = [
            'route' => $currentRoute,
            'query' => (array) $_GET,
            'page_title' => $pageTitle,
        ];
        require_once __DIR__ . '/matomo_helpers.php';
        $matomoHtml = render_matomo_tracking_html($matomoOptions);
    }

    return '<!doctype html><html lang="' . e($currentLocale) . '" dir="' . e($htmlDir) . '" class="notranslate" translate="no" data-theme="' . e($currentTheme) . '" style="--accent: ' . e($accentColor) . '; --accent-strong: ' . e($accentStrongColor) . ';"><head><meta charset="utf-8"><meta name="google" content="notranslate"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
        . e($pageTitle)
        . '</title>' . $metaHead
        . '<meta name="theme-color" content="#2f6fed">'
        . '<link rel="manifest" href="' . e(asset_url('manifest.webmanifest')) . '">'
        . '<link rel="sitemap" type="application/xml" title="Sitemap" href="' . e(route_url('sitemap.xml')) . '">'
        . '<link rel="alternate" type="text/plain" title="LLM context" href="' . e(route_url('llms.txt')) . '">'
        . '<link rel="alternate" type="application/json" title="AI content index" href="' . e(route_url('ai-index.json')) . '">'
        . '<link rel="alternate" type="application/ld+json" title="ON4CRD knowledge graph" href="' . e(route_url('knowledge-graph.jsonld')) . '">'
        . '<link rel="icon" href="' . e(asset_url('assets/icons/icon.svg')) . '" type="image/svg+xml">'
        . '<link rel="apple-touch-icon" href="' . e(asset_url('assets/icons/apple-touch-icon.png')) . '">'
        . $preloadHtml
        . '<link rel="stylesheet" href="' . e(asset_url('assets/css/app.css')) . '">'
        . $moduleCssHtml
        . '<link rel="stylesheet" href="' . e(asset_url('assets/css/tailwind-local.css')) . '">'
        . '</head><body data-route="' . e($currentRoute) . '" data-sw-url="' . e(base_url('sw.js')) . '">'
        . '<a class="skip-link" href="#main-content">' . e((string) ($layoutI18n['skip_to_content'] ?? 'Skip to content')) . '</a>'
        . '<header class="topbar"><div class="brand-wrap"><div class="brand-mark"><img class="brand-mark-img" src="' . e(asset_url('assets/logo/LOGO-CRD-HALO-2020.png')) . '" alt="Logo ON4CRD"></div><a class="brand" href="' . e(route_url('home')) . '">'
        . '<span class="brand-title">ON4CRD.be</span><span class="brand-subtitle">Club Radio Durnal</span></a></div>'
        . '<button class="menu-toggle button secondary" type="button" aria-controls="main-nav" aria-expanded="false"><span aria-hidden="true">☰</span><span class="menu-label">Menu</span></button>'
        . '<button class="nav-backdrop" type="button" aria-label="' . e((string) ($layoutI18n['close_menu'] ?? 'Close menu')) . '" hidden></button>'
        . '<nav id="main-nav" class="nav" aria-label="' . e((string) ($layoutI18n['main_navigation'] ?? 'Main navigation')) . '">' . $navHtml . '<div class="nav-mobile-tools">' . $menuToolsHtml . '</div></nav>'
        . '<div class="toolbar">' . $menuToolsHtml . '</div></header>'
        . $ideaDialogHtml
        . '<main id="main-content" class="layout container py-6">' . $flashHtml . $content . '</main>'
        . render_site_footer($currentRoute)
        . '<script nonce="' . e($nonce) . '" src="' . e(asset_url('assets/js/app.js')) . '" defer></script>'
        . $moduleJsHtml
        . $matomoHtml
        . '</body></html>';
}
}
