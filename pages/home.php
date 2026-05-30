<?php

/** @var string $homeLocale */
$homeLocale = current_locale();
$homeI18n = i18n_domain_locale('home', $homeLocale);
$homeEventsI18n = i18n_domain_locale('events', $homeLocale);
$homeTodayDate = date('d/m/Y');
$homeFallbackBox = static function (string $message): string {
    return '<p class="help">' . e($message) . '</p>';
};
$homeSafeWidget = static function (string $slug) use ($homeFallbackBox): string {
    try {
        return render_widget($slug);
    } catch (Throwable) {
        return $homeFallbackBox('Widget temporairement indisponible.');
    }
};
$homeSafeHamAdvice = static function () use ($homeFallbackBox): string {
    try {
        return render_ham_weather_advice(current_user() ?? []);
    } catch (Throwable) {
        return $homeFallbackBox('Conseil radio temporairement indisponible.');
    }
};

$user = current_user();
$isAuthenticated = $user !== null;

$primaryCta = $isAuthenticated
    ? '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('dashboard')) . '">' . e((string) $homeI18n['cta_member_area']) . '</a>'
    : '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('membership')) . '">' . e((string) $homeI18n['cta_join_club']) . '</a>';
$newsletterCta = '<a class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-5 py-3 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50" href="' . e(route_url('newsletter_public')) . '">' . e((string) $homeI18n['cta_newsletter']) . '</a>';


$moduleCatalog = admin_module_cards_catalog();
if (!is_array($moduleCatalog)) {
    $moduleCatalog = [];
}


$defaultVisibilityLabels = [
    'public' => 'Public',
    'members' => 'Membres',
    'private' => 'Privé',
];
$moduleVisibilityLabels = $defaultVisibilityLabels;
foreach ($defaultVisibilityLabels as $key => $label) {
    if (!isset($moduleVisibilityLabels[$key]) || $moduleVisibilityLabels[$key] === '') {
        $moduleVisibilityLabels[$key] = $label;
    }
}
$moduleVisibilityByCode = [];
if (table_exists('modules')) {
    try {
        $visibilitySelect = table_has_column('modules', 'visibility') ? 'visibility' : "'members' AS visibility";
        foreach (db()->query('SELECT code, ' . $visibilitySelect . ' FROM modules')->fetchAll() as $moduleRow) {
            $code = (string) ($moduleRow['code'] ?? '');
            if ($code === '') {
                continue;
            }
            $moduleVisibilityByCode[$code] = (string) ($moduleRow['visibility'] ?? 'members');
        }
    } catch (Throwable) {
        $moduleVisibilityByCode = [];
    }
}

$activeModules = [];
$moduleCards = '';
foreach ($moduleCatalog as $module) {
    if (!is_array($module) || empty($module['route'])) {
        continue;
    }
    $moduleCode = (string) ($module['code'] ?? $module['module'] ?? '');
    if ($moduleCode !== '' && !module_enabled($moduleCode)) {
        continue;
    }

    $activeModules[] = $module;
    $moduleTitle = is_array($module['title'] ?? null) ? i18n_localized_value((array) $module['title'], $homeLocale, 'fr') : (string) ($module['title'] ?? '');
    $moduleDesc = is_array($module['desc'] ?? null) ? i18n_localized_value((array) $module['desc'], $homeLocale, 'fr') : (string) ($module['desc'] ?? '');
    $moduleAudience = is_array($module['audience'] ?? null) ? i18n_localized_value((array) $module['audience'], $homeLocale, 'fr') : (string) ($module['audience'] ?? '');
    $moduleAudienceCode = (string) ($module['code'] ?? $module['module'] ?? '');
    $configuredVisibility = (string) ($moduleVisibilityByCode[$moduleAudienceCode] ?? '');
    if ($configuredVisibility !== '') {
        $moduleAudience = (string) ($moduleVisibilityLabels[$configuredVisibility] ?? ucfirst($configuredVisibility));
    } elseif ($moduleAudience === '') {
        $moduleAudience = (string) ($moduleVisibilityLabels['members'] ?? 'Membres');
    }
    $moduleIcon = is_array($module['icon'] ?? null) ? i18n_localized_value((array) $module['icon'], $homeLocale, '📦') : (string) ($module['icon'] ?? '📦');

    $moduleCards .= '<a class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2" href="' . e(route_url((string) $module['route'])) . '">'
        . '<div class="flex items-center justify-between gap-3">'
        . '<h3 class="text-lg font-semibold text-slate-900">' . e($moduleTitle) . '</h3>'
        . '<span class="text-xl" aria-hidden="true">' . e($moduleIcon) . '</span>'
        . '</div>'
        . '<p class="mt-2 text-sm text-slate-600">' . e($moduleDesc) . '</p>'
        . '<div class="mt-auto pt-4 flex items-center justify-between gap-3">'
        . '<span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600">' . e($moduleAudience) . '</span>'
        . '<span class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-sm font-semibold text-blue-700 transition group-hover:border-blue-300 group-hover:bg-blue-100">' . e((string) $homeI18n['open']) . ' →</span>'
        . '</div>'
        . '</a>';
}

if ($moduleCards === '') {
    $moduleCards = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">' . e((string) $homeI18n['public_updating']) . '</div>';
}


$memberModuleDefinitions = [];
$memberModuleCards = '';

if (table_exists('modules')) {
    $whereVisibility = table_has_column('modules', 'visibility') ? " AND visibility = 'members'" : '';
    $orderBy = table_has_column('modules', 'sort_order') ? 'sort_order ASC' : 'code ASC';
    try {
        $memberModules = db()->query('SELECT code FROM modules WHERE is_enabled = 1' . $whereVisibility . ' ORDER BY ' . $orderBy)->fetchAll() ?: [];
    } catch (Throwable) {
        $memberModules = [];
    }
    foreach ($memberModules as $memberModuleRow) {
        $moduleCode = (string) ($memberModuleRow['code'] ?? '');
        if ($moduleCode === '' || !isset($memberModuleDefinitions[$moduleCode])) {
            continue;
        }
        $moduleMeta = $memberModuleDefinitions[$moduleCode];
        $memberModuleCards .= '<a class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2" href="' . e(route_url((string) $moduleMeta['route'])) . '">' 
            . '<div class="flex items-center justify-between gap-3">'
            . '<h3 class="text-lg font-semibold text-slate-900">' . e(i18n_localized_value((array) ($moduleMeta['title'] ?? []), $homeLocale, $moduleCode)) . '</h3>'
            . '<span class="text-xl" aria-hidden="true">' . e((string) ($moduleMeta['icon'] ?? '📦')) . '</span>'
            . '</div>'
            . '<p class="mt-2 text-sm text-slate-600">' . e(i18n_localized_value((array) ($moduleMeta['desc'] ?? []), $homeLocale, '')) . '</p>'
            . '<div class="mt-auto pt-4 flex items-center justify-between gap-3">'
            . '<span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600">' . e((string) $homeI18n['member_audience']) . '</span>'
            . '<span class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-sm font-semibold text-blue-700 transition group-hover:border-blue-300 group-hover:bg-blue-100">' . e((string) $homeI18n['open']) . ' →</span>'
            . '</div>'
            . '</a>';
    }
}
if ($memberModuleCards === '') {
    $memberModuleCards = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">' . e((string) $homeI18n['member_modules_empty']) . '</div>';
}


$memberModulesSectionHtml = '';
if (!$isAuthenticated) {
    $memberModulesSectionHtml = '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
        . '<header class="mb-4">'
        . '<h2 class="text-2xl font-bold text-slate-900">' . e((string) $homeI18n['member_modules_title']) . '</h2>'
        . '</header>'
        . '<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">' . $memberModuleCards . '</div>'
        . '</section>';
}

$heroTitle = '';

$heroSubtitle = '';

$heroIntro = '';
if ($heroTitle !== '') {
    $heroIntro .= '<h1 class="mt-4 max-w-2xl text-4xl font-extrabold leading-tight text-slate-900 lg:text-5xl">' . e($heroTitle) . '</h1>';
}
if ($heroSubtitle !== '') {
    $heroIntro .= '<p class="mt-4 max-w-2xl text-base text-slate-600">' . e($heroSubtitle) . '</p>';
}

$moduleCount = count($activeModules);
$heroBackgroundUrl = asset_url('assets/img/on4crd_hero.png');
try {
    $heroImageCandidates = cache_remember('home_hero_image_candidates_v1', 300, static function (): array {
        return glob(__DIR__ . '/../assets/img/*.{png,jpg,jpeg,webp,gif,avif}', GLOB_BRACE) ?: [];
    });
} catch (Throwable) {
    $heroImageCandidates = [];
}
if ($heroImageCandidates !== []) {
    $heroBackgroundUrl = asset_url('assets/img/' . basename((string) $heroImageCandidates[array_rand($heroImageCandidates)]));
}

$homeSeo = i18n_domain_locale('seo', $homeLocale);
$homeUrl = route_url_with_locale('home', $homeLocale);
$homeSearchUrl = route_url_with_locale('search', $homeLocale);
$homeLogoUrl = asset_url('assets/logo/LOGO-CRD-HALO-2020.png');
$homeSeoImageUrl = asset_url('assets/img/on4crd_hero.png');
$homeSeoTitle = trim((string) ($homeSeo['home_title'] ?? 'ON4CRD Radio Club Durnal'));
$homeSeoDescription = trim((string) ($homeSeo['home_description'] ?? 'Radio Club Durnal ON4CRD : actualités, événements, formation, outils radioamateurs et ressources locales à Durnal, Yvoir et Namur.'));
$homeGeoPlace = trim((string) ($homeSeo['geo_placename'] ?? 'Durnal, Yvoir, Namur, Belgique'));

set_page_meta([
    'title' => $homeSeoTitle,
    'description' => $homeSeoDescription,
    'og_type' => 'website',
    'schema_type' => 'WebPage',
    'image' => $homeSeoImageUrl,
    'image_alt' => (string) ($homeI18n['alt_hero_illustration'] ?? 'Radio Club Durnal ON4CRD'),
    'geo_region' => 'BE-WNA',
    'geo_placename' => $homeGeoPlace,
    'geo_position' => '50.3150;4.9452',
    'icbm' => '50.3150, 4.9452',
    'latitude' => '50.3150',
    'longitude' => '4.9452',
    'json_ld' => [
        [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $homeUrl . '#organization',
                    'name' => 'Radio Club Durnal ON4CRD',
                    'alternateName' => ['ON4CRD', 'Club Radio Durnal'],
                    'url' => $homeUrl,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $homeLogoUrl,
                    ],
                    'image' => $homeSeoImageUrl,
                    'telephone' => ['+32496260865', '+32478789193'],
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => 'Rue des Écoles',
                        'addressLocality' => 'Purnode',
                        'postalCode' => '5530',
                        'addressRegion' => 'Namur',
                        'addressCountry' => 'BE',
                    ],
                    'areaServed' => ['Durnal', 'Yvoir', 'Namur', 'Belgium'],
                    'knowsAbout' => ['radioamateurisme', 'amateur radio', 'ON4CRD', 'UBA', 'QSL', 'propagation radio', 'formations radio'],
                    'location' => ['@id' => $homeUrl . '#place'],
                ],
                [
                    '@type' => 'Place',
                    '@id' => $homeUrl . '#place',
                    'name' => 'Bocq Arena',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => 'Rue des Écoles',
                        'addressLocality' => 'Purnode',
                        'postalCode' => '5530',
                        'addressRegion' => 'Namur',
                        'addressCountry' => 'BE',
                    ],
                    'geo' => [
                        '@type' => 'GeoCoordinates',
                        'latitude' => 50.3150,
                        'longitude' => 4.9452,
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $homeUrl . '#website',
                    'name' => 'ON4CRD.be',
                    'url' => $homeUrl,
                    'publisher' => ['@id' => $homeUrl . '#organization'],
                    'inLanguage' => $homeLocale,
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => $homeSearchUrl . '&q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => $homeUrl . '#webpage',
                    'url' => $homeUrl,
                    'name' => $homeSeoTitle,
                    'description' => $homeSeoDescription,
                    'isPartOf' => ['@id' => $homeUrl . '#website'],
                    'about' => ['@id' => $homeUrl . '#organization'],
                    'primaryImageOfPage' => [
                        '@type' => 'ImageObject',
                        'url' => $homeSeoImageUrl,
                    ],
                    'inLanguage' => $homeLocale,
                ],
            ],
        ],
    ],
]);

$latestNews = null;
$nextEvent = null;
$featuredAd = null;

try {
    if (table_exists('news_posts')) {
        $latestNews = cache_remember('home_latest_news_v1', 60, static function () {
            return db()->query('SELECT slug, title, excerpt, published_at, updated_at FROM news_posts WHERE status = "published" ORDER BY COALESCE(published_at, updated_at) DESC LIMIT 1')->fetch();
        });
    }

    if (table_exists('events')) {
        $nextEvent = cache_remember('home_next_event_v1', 60, static function () {
            $stmt = db()->prepare('SELECT slug, title, summary, start_at, location FROM events WHERE status = "published" AND end_at >= NOW() ORDER BY start_at ASC LIMIT 1');
            $stmt->execute();
            return $stmt->fetch();
        });
    }

    if (module_enabled('advertising') && table_exists('ads')) {
        $featuredAd = cache_remember('home_featured_ad_v1', 60, static function () {
            return db()->query('SELECT title, description, image_path, target_url FROM ads WHERE status = "active" ORDER BY updated_at DESC LIMIT 1')->fetch();
        });
    }
} catch (Throwable) {
    // Spotlight blocks stay in fallback mode when the database is unavailable.
}

$latestNewsHtml = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">' . e((string) $homeI18n['no_news']) . '</div>';
if (is_array($latestNews) && !empty($latestNews['slug'])) {
    $newsDate = !empty($latestNews['published_at']) ? date('d/m/Y', strtotime((string) $latestNews['published_at'])) : date('d/m/Y', strtotime((string) ($latestNews['updated_at'] ?? 'now')));
    $newsExcerpt = trim((string) ($latestNews['excerpt'] ?? ''));
    if ($newsExcerpt === '') {
        $newsExcerpt = (string) $homeI18n['news_fallback'];
    }

    $latestNewsHtml = '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('news_view', ['slug' => (string) $latestNews['slug']])) . '">'
        . '<p class="text-xs font-semibold uppercase tracking-wide text-blue-700">' . e((string) $homeI18n['published_on']) . ' ' . e($newsDate) . '</p>'
        . '<h3 class="mt-2 text-lg font-bold text-slate-900 group-hover:text-blue-700">' . e((string) $latestNews['title']) . '</h3>'
        . '<p class="mt-2 text-sm text-slate-600">' . e($newsExcerpt) . '</p>'
        . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e((string) $homeI18n['read_news']) . ' →</span>'
        . '</a>';
}

$homeEventsCalendarConfig = [
    'locale' => $homeLocale,
    'initialView' => 'listMonth',
    'eventsUrl' => route_url('events_feed'),
    'loadError' => (string) ($homeEventsI18n['calendar_load_error'] ?? $homeI18n['no_event']),
    'buttonText' => [
        'today' => (string) ($homeEventsI18n['today'] ?? 'Aujourd\'hui'),
        'month' => (string) ($homeEventsI18n['month'] ?? 'Mois'),
        'week' => (string) ($homeEventsI18n['week'] ?? 'Semaine'),
        'list' => (string) ($homeEventsI18n['list'] ?? 'Planning'),
    ],
];
$nextEventHtml = '<div class="home-events-planning rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">'
    . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/skeleton.css">'
    . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/theme.css">'
    . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/palette.css">'
    . '<div class="fullcalendar-theme home-events-calendar" data-home-events-calendar data-calendar-config="' . e(json_encode($homeEventsCalendarConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"></div>'
    . '<script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/all.global.js"></script>'
    . '<script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/global.js"></script>'
    . '<script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/locales/' . e($homeLocale) . '.global.js"></script>'
    . '</div>';

$toolDayCta = trim((string) $homeI18n['spotlight_tool_day_cta']);
if ($toolDayCta !== '' && !str_ends_with($toolDayCta, '→')) {
    $toolDayCta .= ' →';
}

$toolDayHtml = '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="'
    . e(route_url('tools'))
    . '#tool-grid"><p class="text-sm font-semibold text-slate-900">'
    . e((string) $homeI18n['spotlight_tool_day_item'])
    . '</p><span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">'
    . e($toolDayCta)
    . '</span></a>';
try {
    $toolsI18n = require __DIR__ . '/../app/i18n/tools.php';
    $t = [];
    foreach (array_keys($toolsI18n['fr'] ?? []) as $key) {
        $value = trim(i18n_localized_value($toolsI18n, $homeLocale, (string) $key));
        $t[(string) $key] = $value !== '' ? $value : (string) ($toolsI18n['fr'][$key] ?? '');
    }
    $toolTr = static function (string $key, string $fallback = '') use ($t): string {
        $value = trim((string) ($t[$key] ?? ''));
        return $value !== '' ? $value : $fallback;
    };
    $toolCatalog = require __DIR__ . '/../app/config/tools_catalog.php';
    $toolPanelMap = require __DIR__ . '/../app/config/tools_panels.php';
    $toolCandidates = [];
    foreach (['locators', 'conversion', 'antenna', 'power', 'advanced_propagation', 'rf_measures', 'radio_math'] as $group) {
        foreach (($toolCatalog[$group] ?? []) as $entry) {
            $toolId = (string) ($entry['id'] ?? '');
            if ($toolId === '' || empty($toolPanelMap[$toolId])) {
                continue;
            }
            $partialPath = __DIR__ . '/tools_panels/' . $toolPanelMap[$toolId];
            if (!is_file($partialPath)) {
                continue;
            }
            $title = isset($entry['title']) ? (string) $entry['title'] : $toolTr((string) ($entry['title_key'] ?? ''), $toolId);
            if (($entry['title_pattern'] ?? '') === 'conv_dot') {
                $title = trim($toolTr((string) ($entry['left_key'] ?? '')) . ' · ' . $toolTr((string) ($entry['right_key'] ?? '')));
            }
            $toolCandidates[] = ['id' => $toolId, 'title' => $title !== '' ? $title : $toolId, 'path' => $partialPath];
        }
    }
    if ($toolCandidates !== []) {
        $selectedTool = $toolCandidates[array_rand($toolCandidates)];
        ob_start();
        include (string) $selectedTool['path'];
        $selectedToolPanel = trim((string) ob_get_clean());
        if ($selectedToolPanel !== '') {
            $toolDayHtml = '<div class="home-tool-day">'
                . '<script type="application/json" id="tools-i18n">' . e(json_encode($t, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</script>'
                . $selectedToolPanel
                . '<a class="mt-3 inline-flex text-sm font-semibold text-blue-600 hover:text-blue-700" href="' . e(route_url('tools')) . '#' . e((string) $selectedTool['id']) . '">'
                . e($toolDayCta) . '</a>'
                . '</div>';
        }
    }
} catch (Throwable) {
    // The static link remains available if a random tool panel cannot be rendered.
}
$memberSpotlightRowHtml = '';
if ($isAuthenticated) {
    $memberSpotlightLinkClass = 'inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50';
    $memberSpotlightRowHtml = '<div class="mt-4 grid gap-4 lg:grid-cols-3">'
        . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e((string) ($homeI18n['spotlight_member_wiki_articles'] ?? 'Wiki / Articles')) . '</h3>'
        . '<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><div class="grid gap-2 sm:grid-cols-2">'
        . '<a class="' . $memberSpotlightLinkClass . '" href="' . e(route_url('wiki')) . '">' . e((string) ($homeI18n['spotlight_member_wiki'] ?? 'Wiki')) . '</a>'
        . '<a class="' . $memberSpotlightLinkClass . '" href="' . e(route_url('articles')) . '">' . e((string) ($homeI18n['spotlight_member_articles'] ?? 'Articles')) . '</a>'
        . '</div></div></article>'
        . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e((string) ($homeI18n['spotlight_member_library'] ?? 'Bibliothèque')) . '</h3>'
        . '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('members_library')) . '">'
        . '<p class="text-sm font-semibold text-slate-900">' . e((string) ($homeI18n['spotlight_member_library'] ?? 'Bibliothèque')) . '</p>'
        . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e((string) ($homeI18n['spotlight_member_open'] ?? 'Ouvrir')) . ' →</span></a></article>'
        . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e((string) ($homeI18n['spotlight_member_auctions'] ?? 'Enchères')) . '</h3>'
        . '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('auctions')) . '">'
        . '<p class="text-sm font-semibold text-slate-900">' . e((string) ($homeI18n['spotlight_member_auctions'] ?? 'Enchères')) . '</p>'
        . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e((string) ($homeI18n['spotlight_member_open'] ?? 'Ouvrir')) . ' →</span></a></article>'
        . '</div>';
}

$adSlotHtml = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500"><p class="mt-2">' . e((string) $homeI18n['partner_ad_empty']) . '</p></div>';
try {
    $localAdCandidates = cache_remember('home_local_ad_candidates_v1', 300, static function (): array {
        return glob(__DIR__ . '/../assets/pub/*.{png,jpg,jpeg,webp,gif,avif}', GLOB_BRACE) ?: [];
    });
} catch (Throwable) {
    $localAdCandidates = [];
}
if ($localAdCandidates !== []) {
    $localAdPath = 'assets/pub/' . basename((string) $localAdCandidates[array_rand($localAdCandidates)]);
    $adSlotHtml = '<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">'
        . '<div class="overflow-hidden rounded-lg aspect-square w-full">'
        . '<img class="h-full w-full object-cover" src="' . e(asset_url($localAdPath)) . '" alt="' . e((string) $homeI18n['alt_partner_ad']) . '" loading="lazy" decoding="async">'
        . '</div>'
        . '</div>';
}
if (is_array($featuredAd) && !empty($featuredAd['title'])) {
    $adTarget = trim((string) ($featuredAd['target_url'] ?? ''));
    $adDescription = trim((string) ($featuredAd['description'] ?? ''));
    $adImage = trim((string) ($featuredAd['image_path'] ?? ''));

    $adInner = '<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">'
        . '<p class="text-xs font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['partner_ad_title']) . '</p>'
        . '<h3 class="mt-2 text-lg font-bold text-slate-900">' . e((string) $featuredAd['title']) . '</h3>';

    if ($adDescription !== '') {
        $adInner .= '<p class="mt-2 text-sm text-slate-600">' . e($adDescription) . '</p>';
    }

    if ($adImage !== '') {
        $adInner .= '<div class="mt-3 overflow-hidden rounded-lg aspect-square w-full"><img class="h-full w-full object-cover" src="' . e(asset_url($adImage)) . '" alt="' . e((string) $featuredAd['title']) . '" loading="lazy" decoding="async"></div>';
    }

    $adInner .= '</div>';

    $adSlotHtml = $adTarget !== ''
        ? '<a class="block transition hover:-translate-y-0.5" href="' . e($adTarget) . '" target="_blank" rel="noopener noreferrer">' . $adInner . '</a>'
        : $adInner;
}

$trophySlotHtml = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500"><p class="mt-2">' . e((string) ($homeI18n['home_trophies_empty'] ?? 'Les trophées du club seront affichés ici.')) . '</p></div>';
try {
    $localTrophyCandidates = cache_remember('home_local_trophy_candidates_v1', 300, static function (): array {
        return glob(__DIR__ . '/../assets/trophy/*.{png,jpg,jpeg,webp,gif,avif}', GLOB_BRACE) ?: [];
    });
} catch (Throwable) {
    $localTrophyCandidates = [];
}
if ($localTrophyCandidates !== []) {
    $localTrophyPath = 'assets/trophy/' . basename((string) $localTrophyCandidates[array_rand($localTrophyCandidates)]);
    $trophySlotHtml = '<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">'
        . '<div class="overflow-hidden rounded-lg aspect-square w-full">'
        . '<img class="h-full w-full object-cover" src="' . e(asset_url($localTrophyPath)) . '" alt="' . e((string) ($homeI18n['alt_trophy_image'] ?? 'Trophée du club')) . '" loading="lazy" decoding="async">'
        . '</div>'
        . '</div>';
}

$ubaLogoPath = 'assets/logo/UBA-Logo-Couleur-MID2.png';
$relaisLogoPath = 'assets/logo/CRD-Echolink.jpg';
$homeWeatherHtml = $homeSafeWidget('open_meteo');
$homePropagationHtml = $homeSafeWidget('propagation');
$hasHomePropagation = trim((string) $homePropagationHtml) !== '';
$homeHamAdviceHtml = $homeSafeHamAdvice();
$hamWeatherRefreshUrl = base_url('index.php?' . http_build_query(['route' => 'home', 'ajax' => 'ham_weather']));
$homeRadioInfoHtml = '<div class="grid gap-4">'
    . '<section>'
    . '<h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['ham_info_title']) . '</h4>'
    . '<ul class="mt-2 list-clean">'
    . '<li><strong>' . e((string) $homeI18n['vhf_voice_label']) . '</strong> ' . e((string) $homeI18n['vhf_voice_value']) . '</li>'
    . '<li><strong>' . e((string) ($homeI18n['cw_qrp_label'] ?? 'QRG CW QRP :')) . '</strong> ' . e((string) ($homeI18n['cw_qrp_value'] ?? '7.030 MHz • 14.060 MHz')) . '</li>'
    . '<li><strong>' . e((string) $homeI18n['good_practice_label']) . '</strong> ' . e((string) $homeI18n['good_practice_value']) . '</li>'
    . '</ul>'
    . '</section>'
    . '</div>';

if (isset($_GET['ajax']) && (string) $_GET['ajax'] === 'ham_weather') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    try {
        echo json_encode([
            'weather' => $homeSafeWidget('open_meteo'),
            'propagation' => $homeSafeWidget('propagation'),
            'advice' => $homeSafeHamAdvice(),
            'updated_at' => gmdate('c'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        http_response_code(500);
        echo '{"weather":"","propagation":"","advice":""}';
    }

    return;
}

try {
    $homeQuote = random_quote_for_layout();
} catch (Throwable) {
    $homeQuote = null;
}
$homeQuoteText = (string) $homeI18n['quote_fallback'];
$homeQuoteAuthor = '';
if (is_array($homeQuote)) {
    $candidateHomeQuoteText = trim((string) ($homeQuote['quote'] ?? ''));
    $candidateHomeQuoteAuthor = trim((string) ($homeQuote['author'] ?? ''));
    if ($candidateHomeQuoteText !== '') {
        $homeQuoteText = $candidateHomeQuoteText;
    }
    if ($candidateHomeQuoteAuthor !== '') {
        $homeQuoteAuthor = $candidateHomeQuoteAuthor;
    }
}

$homeSponsorsTrophiesSectionHtml = '<section class="mt-4 grid gap-4 lg:grid-cols-3">'
    . '<article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="flex items-start justify-between gap-4">'
    . '<div><h2 class="text-2xl font-extrabold text-slate-900">' . e((string) ($homeI18n['home_sponsors_title'] ?? 'Nos sponsors')) . '</h2>'
    . '<p class="mt-2 text-sm leading-6 text-slate-600">' . e((string) ($homeI18n['home_sponsors_desc'] ?? 'Des partenaires locaux et radioamateurs soutiennent les activités, les projets et la visibilité du CRD.')) . '</p></div>'
    . '<span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700">CRD</span>'
    . '</div>'
    . '<div class="mt-4">' . $adSlotHtml . '</div>'
    . '<div class="mt-4 flex"><a class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('sponsoring')) . '">' . e((string) ($homeI18n['home_sponsors_cta'] ?? 'Devenir sponsor')) . '</a></div>'
    . '</article>'
    . '<article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="flex items-start justify-between gap-4">'
    . '<div><h2 class="text-2xl font-extrabold text-slate-900">' . e((string) ($homeI18n['home_trophies_title'] ?? 'Nos trophées')) . '</h2>'
    . '<p class="mt-2 text-sm leading-6 text-slate-600">' . e((string) ($homeI18n['home_trophies_desc'] ?? 'Les activités du club valorisent la technique, la participation aux concours et les réalisations collectives.')) . '</p></div>'
    . '<span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-700">CRD</span>'
    . '</div>'
    . '<div class="mt-4">' . $trophySlotHtml . '</div>'
    . '</article>'
    . '<article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<h2 class="text-2xl font-extrabold text-slate-900">' . e((string) ($homeI18n['home_other_sections_title'] ?? 'Dans les autres sections')) . '</h2>'
    . '<p class="mt-2 text-sm leading-6 text-slate-600">' . e((string) ($homeI18n['home_other_sections_desc'] ?? 'Le CRD valorise plusieurs pratiques radioamateurs complémentaires, des bandes HF aux modes numériques, en passant par les antennes et les activations.')) . '</p>'
    . '<div class="mt-4 grid gap-2">'
    . '<div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"><h3 class="text-sm font-bold text-slate-900">' . e((string) ($homeI18n['home_section_hf_title'] ?? 'HF et trafic longue distance')) . '</h3><p class="mt-1 text-xs leading-5 text-slate-600">' . e((string) ($homeI18n['home_section_hf_desc'] ?? 'Contacts internationaux, propagation, contests et bonnes pratiques de trafic.')) . '</p></div>'
    . '<div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"><h3 class="text-sm font-bold text-slate-900">' . e((string) ($homeI18n['home_section_vhf_title'] ?? 'VHF/UHF et relais')) . '</h3><p class="mt-1 text-xs leading-5 text-slate-600">' . e((string) ($homeI18n['home_section_vhf_desc'] ?? 'Expérimentations locales, antennes, relais et liaisons de proximité.')) . '</p></div>'
    . '<div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"><h3 class="text-sm font-bold text-slate-900">' . e((string) ($homeI18n['home_section_digital_title'] ?? 'Modes numériques')) . '</h3><p class="mt-1 text-xs leading-5 text-slate-600">' . e((string) ($homeI18n['home_section_digital_desc'] ?? 'FT8, DMR, APRS, logiciels radio et échanges autour des outils modernes.')) . '</p></div>'
    . '<div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"><h3 class="text-sm font-bold text-slate-900">' . e((string) ($homeI18n['home_section_activation_title'] ?? 'Activations et terrain')) . '</h3><p class="mt-1 text-xs leading-5 text-slate-600">' . e((string) ($homeI18n['home_section_activation_desc'] ?? 'Sorties radio, essais d’antennes, portable et activités pratiques.')) . '</p></div>'
    . '</div>'
    . '</article>'
    . '</section>';

$content = '<section class="mb-4 grid gap-4 lg:grid-cols-2">'
    . '<article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" aria-label="' . e((string) $homeI18n['quote_aria']) . '">'
    . '<h2 class="text-xl font-bold text-slate-900">' . e((string) $homeI18n['quote_day']) . '</h2>'
    . '<blockquote class="mt-3 border-l-4 border-blue-200 pl-4 text-base italic text-slate-700">“' . e($homeQuoteText) . '”</blockquote>'
    . ($homeQuoteAuthor !== '' ? '<p class="mt-3 text-sm font-semibold text-slate-500">— ' . e($homeQuoteAuthor) . '</p>' : '')
    . '</article>'
    . '<article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" aria-label="' . e((string) $homeI18n['clock_aria']) . '">'
    . '<div class="grid gap-3 sm:grid-cols-2">'
    . '<article class="rounded-xl border border-slate-200 bg-slate-50 p-4">'
    . '<p class="text-xs font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['utc_datetime']) . '</p>'
    . '<div class="mt-2 flex items-center justify-between gap-3">'
    . '<span class="text-xl font-bold text-slate-900" data-live-date data-timezone="UTC" aria-live="polite">' . e($homeTodayDate) . '</span>'
    . '<time class="text-xl font-bold text-slate-900" data-live-clock data-timezone="UTC" aria-live="polite">--:--:--</time>'
    . '</div>'
    . '</article>'
    . '<article class="rounded-xl border border-slate-200 bg-slate-50 p-4">'
    . '<p class="text-xs font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['local_datetime']) . '</p>'
    . '<div class="mt-2 flex items-center justify-between gap-3">'
    . '<span class="text-xl font-bold text-slate-900" data-live-date data-timezone="local" aria-live="polite">' . e($homeTodayDate) . '</span>'
    . '<time class="text-xl font-bold text-slate-900" data-live-clock data-timezone="local" aria-live="polite">--:--:--</time>'
    . '</div>'
    . '</article>'
    . '</div>'
    . '</article>'
    . '</section>'
    . '<section class="grid gap-4 lg:grid-cols-[1.55fr_.95fr]">'
    . '<article class="relative isolate flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200 p-8 shadow-sm">'
    . '<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="' . e($heroBackgroundUrl) . '" alt="' . e((string) $homeI18n['alt_hero_illustration']) . '" loading="eager" decoding="async">'
    . '<span class="hidden rounded-full bg-blue-600 px-3 py-1 text-[1.1rem] font-semibold uppercase tracking-wide text-white sm:inline-flex">' . e((string) $homeI18n['hero_tagline']) . '</span>'
    . $heroIntro
    . '<div class="mt-auto pt-8 grid max-w-sm gap-2">' . $primaryCta . $newsletterCta . '</div>'
    . '</article>'
    . '<div class="grid gap-4">'
    . '<aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" aria-label="' . e((string) $homeI18n['ham_weather_aria']) . '">'
    . '<div class="flex items-start justify-between gap-4">'
    . '<div>'
    . '<h2 class="text-xl font-bold text-slate-900">' . e((string) $homeI18n['ham_weather']) . '</h2>'
    . '<p class="mt-1 text-sm text-slate-600">' . e((string) $homeI18n['ham_weather_desc']) . '</p>'
    . '</div>'
    . '<span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">LIVE</span>'
    . '</div>'
    . '<div class="mt-4 rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 via-white to-slate-50 p-4" data-ham-weather-root data-refresh-ms="900000" data-refresh-url="' . e($hamWeatherRefreshUrl) . '" data-updated-label="' . e((string) $homeI18n['weather_updated']) . '">'
    . '<div class="grid gap-3">'
    . '<section class="rounded-xl border border-slate-200 bg-white p-3" data-ham-weather-weather>' . $homeWeatherHtml . '</section>'
    . ($hasHomePropagation ? '<section class="rounded-xl border border-slate-200 bg-white p-3" data-ham-weather-propagation-wrapper><div data-ham-weather-propagation>' . $homePropagationHtml . '</div></section>' : '')
    . '<section class="rounded-xl border border-slate-200 bg-white p-3" data-ham-weather-advice>' . $homeHamAdviceHtml . '</section>'
    . '<div class="mt-2 flex items-center justify-between gap-3 border-t border-slate-200 pt-3">'
    . '<p class="text-xs font-medium text-slate-500 whitespace-nowrap" data-ham-weather-updated></p>'
    . '<button type="button" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-60" data-ham-weather-refresh aria-label="' . e((string) $homeI18n['weather_refresh']) . '">⟳ ' . e((string) $homeI18n['weather_refresh']) . '</button>'
    . '</div>'
    . '</div>'
    . '</div>'
    . '</aside>'
    . '</div>'
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<header class="mb-4">'
    . '<h2 class="text-2xl font-bold text-slate-900">' . e((string) $homeI18n['club_spotlight_title']) . '</h2>'
    . '</header>'
    . '<div class="grid gap-4 lg:grid-cols-3">'
    . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['spotlight_tool_day']) . '</h3>' . $latestNewsHtml . '</article>'
    . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['spotlight_for_sale']) . '</h3>' . $nextEventHtml . '</article>'
    . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['spotlight_auction_live']) . '</h3>' . $toolDayHtml . '</article>'
    . '</div>'
    . $memberSpotlightRowHtml
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="mb-5 max-w-3xl"><h2 class="text-2xl font-extrabold text-slate-900">' . e((string) ($homeI18n['join_benefits_title'] ?? 'Ce que le CRD vous apporte')) . '</h2><p class="mt-2 text-slate-600">' . e((string) ($homeI18n['join_benefits_desc'] ?? 'Rejoindre le club, c’est avancer avec un cadre clair, des activités concrètes et une communauté disponible pour progresser en radioamateurisme.')) . '</p></div>'
    . '<div class="grid gap-4 md:grid-cols-3">'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="text-lg font-semibold text-slate-900">' . e((string) ($homeI18n['join_benefit_1_title'] ?? 'Un accompagnement utile')) . '</h3><p class="mt-2 text-sm leading-6 text-slate-600">' . e((string) ($homeI18n['join_benefit_1_desc'] ?? 'Des membres expérimentés partagent leurs pratiques, répondent aux questions et aident à préparer les démarches radio.')) . '</p></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="text-lg font-semibold text-slate-900">' . e((string) ($homeI18n['join_benefit_2_title'] ?? 'Des activités régulières')) . '</h3><p class="mt-2 text-sm leading-6 text-slate-600">' . e((string) ($homeI18n['join_benefit_2_desc'] ?? 'Réunions, projets, activation et échanges techniques donnent des occasions concrètes de pratiquer et d’apprendre.')) . '</p></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="text-lg font-semibold text-slate-900">' . e((string) ($homeI18n['join_benefit_3_title'] ?? 'Un réseau local actif')) . '</h3><p class="mt-2 text-sm leading-6 text-slate-600">' . e((string) ($homeI18n['join_benefit_3_desc'] ?? 'Le CRD facilite les contacts entre passionnés, l’accès aux informations utiles et la participation à la vie du club.')) . '</p></article>'
    . '</div>'
    . '</section>'
    . '<section class="mt-4 grid gap-4 rounded-3xl border border-blue-200 bg-gradient-to-r from-blue-50 to-white p-6 shadow-sm lg:grid-cols-[1.8fr_1fr] lg:items-center">'
    . '<div><h2 class="text-2xl font-extrabold text-slate-900">' . e((string) $homeI18n['join_title']) . '</h2><p class="mt-2 text-slate-600">' . e((string) $homeI18n['join_desc']) . '</p></div>'
    . '<div class="grid gap-2">' . $primaryCta . $newsletterCta . '</div>'
    . '</section>'
    . $homeSponsorsTrophiesSectionHtml
    . $memberModulesSectionHtml
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="grid gap-4 lg:grid-cols-[1.15fr_.85fr]">'
    . '<div class="grid gap-4">'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-900">' . e((string) $homeI18n['uba_title']) . '</h3><p class="mt-2 text-sm text-slate-600">' . e((string) $homeI18n['uba_desc']) . '</p><a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="https://www.uba.be" target="_blank" rel="noopener noreferrer">' . e((string) $homeI18n['uba_cta']) . '</a></div><img class="h-20 w-auto object-contain" src="' . e(asset_url($ubaLogoPath)) . '" alt="' . e((string) $homeI18n['alt_uba_logo']) . '" loading="lazy" decoding="async"></div></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="text-lg font-semibold text-slate-900">' . e((string) $homeI18n['ibpt_title']) . '</h3><p class="mt-2 text-sm text-slate-600">' . e((string) $homeI18n['ibpt_desc']) . '</p><a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="https://www.ibpt.be/consommateurs/frequences-radio/utilisation-privee-de-loisir/radioamateurs" target="_blank" rel="noopener noreferrer">' . e((string) $homeI18n['ibpt_cta']) . '</a></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-900">' . e((string) $homeI18n['repeater_title']) . '</h3><p class="mt-2 text-sm text-slate-600">' . e((string) $homeI18n['repeater_desc']) . '</p><a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('relais')) . '">' . e((string) $homeI18n['repeater_cta']) . '</a></div><img class="h-20 w-auto object-contain" src="' . e(asset_url($relaisLogoPath)) . '" alt="' . e((string) $homeI18n['alt_repeater_logo']) . '" loading="lazy" decoding="async"></div></article>'
    . '</div>'
    . '<article class="h-full rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="text-lg font-semibold text-slate-900">' . e((string) $homeI18n['useful_info']) . '</h3><p class="mt-2 text-sm text-slate-600">' . e((string) $homeI18n['meetings_info']) . '</p><p class="mt-2 text-sm text-slate-600">' . e((string) $homeI18n['venue_address']) . '</p><div class="mt-3 overflow-hidden rounded-lg border border-slate-200"><iframe class="h-64 w-full" title="' . e((string) $homeI18n['map_title']) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q=50%C2%B018%2754.1%22N+4%C2%B056%2742.7%22E&output=embed"></iframe></div><a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="https://www.google.com/maps?q=50%C2%B018%2754.1%22N+4%C2%B056%2742.7%22E" target="_blank" rel="noopener noreferrer">' . e((string) $homeI18n['maps_route']) . '</a></article>'
    . '</div>'
    . '</section>'
    . '<section class="mt-4 grid gap-4 md:grid-cols-2">'
    . '<article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<h3 class="text-xl font-bold text-slate-900">' . e((string) $homeI18n['journalist_title']) . '</h3>'
    . '<p class="mt-3 text-sm text-slate-600">' . e((string) $homeI18n['journalist_desc']) . '</p>'
    . '<a class="mt-4 inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('press')) . '">' . e((string) $homeI18n['journalist_cta']) . '</a>'
    . '</article>'
    . '<article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<h3 class="text-xl font-bold text-slate-900">' . e((string) $homeI18n['teacher_title']) . '</h3>'
    . '<p class="mt-3 text-sm text-slate-600">' . e((string) $homeI18n['teacher_desc']) . '</p>'
    . '<a class="mt-4 inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('schools')) . '">' . e((string) $homeI18n['teacher_cta']) . '</a>'
    . '</article>'
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="grid gap-6 lg:grid-cols-3">'
    . '<article><h3 class="text-lg font-bold text-slate-900">' . e((string) $homeI18n['address_title']) . '</h3><p class="mt-3 text-sm text-slate-700">' . e((string) $homeI18n['club_name']) . '</p><p class="text-sm text-slate-700">' . e((string) $homeI18n['venue_line_1']) . '</p><p class="text-sm text-slate-700">' . e((string) $homeI18n['venue_line_2']) . '</p><p class="text-sm text-slate-700">' . e((string) $homeI18n['venue_line_3']) . '</p><p class="mt-4 text-lg font-bold text-slate-900">' . e((string) $homeI18n['contact_people']) . '</p><p class="text-sm text-slate-700">ON4BEN : +32 496 260 865</p><p class="text-sm text-slate-700">ON4DG : +32 478 789 193</p></article>'
    . '<article><h3 class="text-lg font-bold text-slate-900">' . e((string) $homeI18n['contact_title']) . '</h3><form class="mt-3 grid gap-2" method="post" action="' . e(route_url('footer_contact')) . '"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="return_route" value="home"><label for="home-contact-name" class="sr-only">' . e((string) $homeI18n['contact_name']) . '</label><input id="home-contact-name" type="text" name="name" placeholder="' . e((string) $homeI18n['contact_name']) . '" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><label for="home-contact-email" class="sr-only">' . e((string) $homeI18n['contact_email']) . '</label><input id="home-contact-email" type="email" name="email" placeholder="' . e((string) $homeI18n['contact_email']) . '" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><label for="home-contact-message" class="sr-only">' . e((string) $homeI18n['contact_message']) . '</label><textarea id="home-contact-message" name="message" placeholder="' . e((string) $homeI18n['contact_message']) . '" rows="3" maxlength="2000" data-wysiwyg="off" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea><button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">' . e((string) $homeI18n['contact_send']) . '</button></form></article>'
    . '<article><h3 class="text-lg font-bold text-slate-900">' . e((string) $homeI18n['important_info_title']) . '</h3><ul class="mt-3 list-inside list-disc space-y-1 text-sm text-slate-700"><li><a class="hover:underline" href="' . e(route_url('conditions_utilisation')) . '">' . e((string) $homeI18n['link_terms']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('mentions_legales')) . '">' . e((string) $homeI18n['link_legal']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('reglement_interieur')) . '">' . e((string) $homeI18n['link_internal_rules']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('membership')) . '">' . e((string) $homeI18n['link_donate']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('sponsoring')) . '">' . e((string) $homeI18n['link_sponsoring']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('code_q')) . '">' . e((string) $homeI18n['link_code_q']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('code_cw')) . '">' . e((string) $homeI18n['link_code_cw']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('bandplan_on3')) . '">' . e((string) $homeI18n['link_bandplan_on3']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('bandplan_on2')) . '">' . e((string) $homeI18n['link_bandplan_on2']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('bandplan_harec')) . '">' . e((string) $homeI18n['link_bandplan_harec']) . '</a></li></ul></article>'
    . '</div>'
    . '</section>';


echo render_layout($content, (string) ($homeI18n['page_title'] ?? 'Accueil'));



