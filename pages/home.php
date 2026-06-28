<?php

/** @var string $homeLocale */
$homeLocale = current_locale();
$homeFullCalendarLocale = fullcalendar_locale_code($homeLocale);
$homeFullCalendarLocaleAsset = fullcalendar_locale_asset_url($homeLocale);
$homeI18n = i18n_domain_locale('home', $homeLocale);
$homeEventsI18n = i18n_domain_locale('events', $homeLocale);
$homeText = static function (string $key) use ($homeI18n): string {
    $value = trim((string) ($homeI18n[$key] ?? ''));

    return $value !== '' ? $value : $key;
};
$homeEventText = static function (string $key) use ($homeEventsI18n, $homeText): string {
    $value = trim((string) ($homeEventsI18n[$key] ?? ''));

    return $value !== '' ? $value : $homeText($key);
};
$homeTodayDate = date('d/m/Y');
$homeFallbackBox = static function (string $message): string {
    return '<p class="help">' . e($message) . '</p>';
};
$homeSafeWidget = static function (string $slug) use ($homeFallbackBox, $homeText): string {
    try {
        return render_widget($slug);
    } catch (Throwable) {
        return $homeFallbackBox($homeText('widget_temporarily_unavailable'));
    }
};
$homeSafeHamAdvice = static function () use ($homeFallbackBox, $homeText): string {
    try {
        return render_ham_weather_advice(current_user() ?? []);
    } catch (Throwable) {
        return $homeFallbackBox($homeText('ham_advice_temporarily_unavailable'));
    }
};

$user = current_user();
$isAuthenticated = $user !== null;

$primaryCta = $isAuthenticated
    ? '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('dashboard')) . '">' . e((string) $homeI18n['cta_member_area']) . '</a>'
    : '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('membership')) . '">' . e((string) $homeI18n['cta_join_club']) . '</a>';
$newsletterCta = '<a class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-5 py-3 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50" href="' . e(route_url('newsletter_public')) . '">' . e((string) $homeI18n['cta_newsletter']) . '</a>';
$homeContactCaptcha = public_form_captcha_challenge('footer_contact');
$homeContactCaptchaLabel = public_form_captcha_label($homeContactCaptcha, $homeLocale);


$moduleCatalog = admin_module_cards_catalog();
if (!is_array($moduleCatalog)) {
    $moduleCatalog = [];
}


$defaultVisibilityLabels = [
    'public' => (string) ($homeI18n['visibility_public'] ?? 'Public'),
    'members' => (string) ($homeI18n['visibility_members'] ?? ($homeI18n['member_audience'] ?? 'Members')),
    'private' => (string) ($homeI18n['visibility_private'] ?? 'Private'),
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
        $moduleAudience = (string) ($moduleVisibilityLabels['members'] ?? ($homeI18n['visibility_members'] ?? 'Members'));
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


$memberModuleCards = '';
$memberModulesJoinCta = $homeText('member_modules_join_cta');
$memberModuleCodesRendered = [];
$memberModuleDefinitions = [
    'dashboard' => ['route' => 'dashboard', 'icon' => '▦', 'title_key' => 'member_module_dashboard_title', 'desc_key' => 'member_module_dashboard_desc'],
    'members' => ['route' => 'profile', 'icon' => '☷', 'title_key' => 'member_module_members_title', 'desc_key' => 'member_module_members_desc'],
    'qsl' => ['route' => 'qsl', 'icon' => '✉', 'title_key' => 'member_module_qsl_title', 'desc_key' => 'member_module_qsl_desc'],
    'library' => ['route' => 'members_library', 'icon' => '▤', 'title_key' => 'member_module_library_title', 'desc_key' => 'member_module_library_desc'],
    'webotheque' => ['route' => 'webotheque', 'icon' => 'W', 'title_key' => 'member_module_webotheque_title', 'desc_key' => 'member_module_webotheque_desc'],
    'presentations' => ['route' => 'presentations', 'icon' => 'P', 'title_key' => 'member_module_presentations_title', 'desc_key' => 'member_module_presentations_desc'],
    'videos' => ['route' => 'videos', 'icon' => 'V', 'title_key' => 'member_module_videos_title', 'desc_key' => 'member_module_videos_desc'],
    'pv' => ['route' => 'pv', 'icon' => 'PV', 'title_key' => 'member_module_pv_title', 'desc_key' => 'member_module_pv_desc'],
    'fichiers' => ['route' => 'fichiers', 'icon' => 'FI', 'title_key' => 'member_module_fichiers_title', 'desc_key' => 'member_module_fichiers_desc'],
    'auctions' => ['route' => 'auctions', 'icon' => '⌁', 'title_key' => 'member_module_auctions_title', 'desc_key' => 'member_module_auctions_desc'],
    'classifieds' => ['route' => 'classifieds', 'icon' => '□', 'title_key' => 'member_module_classifieds_title', 'desc_key' => 'member_module_classifieds_desc'],
    'chatbot' => ['route' => 'chatbot', 'icon' => '?', 'title_key' => 'member_module_chatbot_title', 'desc_key' => 'member_module_chatbot_desc'],
    'newsletter' => ['route' => 'newsletter', 'icon' => '≋', 'title_key' => 'member_module_newsletter_title', 'desc_key' => 'member_module_newsletter_desc'],
];
$memberModuleText = static function (array $moduleMeta, string $field, string $fallback = '') use ($homeText): string {
    $key = (string) ($moduleMeta[$field . '_key'] ?? '');

    return $key !== '' ? $homeText($key) : $fallback;
};
$memberModuleIconPaths = [
    'dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1.5"></rect><rect width="7" height="5" x="14" y="3" rx="1.5"></rect><rect width="7" height="9" x="14" y="12" rx="1.5"></rect><rect width="7" height="5" x="3" y="16" rx="1.5"></rect>',
    'members' => '<path d="M18 21a6 6 0 0 0-12 0"></path><circle cx="12" cy="8" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path>',
    'qsl' => '<rect width="18" height="14" x="3" y="5" rx="2"></rect><path d="m3 7 9 6 9-6"></path><path d="M7 17h4"></path>',
    'library' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2"></path><path d="M8 7h8"></path><path d="M8 11h6"></path>',
    'webotheque' => '<path d="M10 13a5 5 0 0 0 7.5.5l2-2a5 5 0 0 0-7.1-7.1l-1.2 1.2"></path><path d="M14 11a5 5 0 0 0-7.5-.5l-2 2a5 5 0 0 0 7.1 7.1l1.2-1.2"></path>',
    'presentations' => '<rect width="18" height="13" x="3" y="4" rx="2"></rect><path d="M8 21h8"></path><path d="M12 17v4"></path><path d="M8 9h8"></path><path d="M8 13h5"></path>',
    'videos' => '<rect width="18" height="14" x="3" y="5" rx="2"></rect><path d="m10 9 5 3-5 3V9Z"></path>',
    'pv' => '<path d="M9 3h6l1 2h3v16H5V5h3l1-2Z"></path><path d="M9 11h6"></path><path d="M9 15h6"></path>',
    'fichiers' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h5"></path>',
    'auctions' => '<path d="m14 13-8.5 8.5"></path><path d="m9 8 7 7"></path><path d="m12 5 7 7"></path><path d="m5 11 7-7"></path><path d="m2 22 6-6"></path>',
    'classifieds' => '<path d="M20.6 13.5 13.5 20.6a2 2 0 0 1-2.8 0L3 12.9V4h8.9l8.7 8.7a2 2 0 0 1 0 2.8Z"></path><circle cx="7.5" cy="7.5" r=".8"></circle>',
    'chatbot' => '<path d="M12 8V4H8"></path><rect width="16" height="12" x="4" y="8" rx="3"></rect><path d="M2 14h2"></path><path d="M20 14h2"></path><path d="M9 13v2"></path><path d="M15 13v2"></path>',
    'newsletter' => '<path d="M4 4h16v16H4z"></path><path d="M8 8h8"></path><path d="M8 12h8"></path><path d="M8 16h5"></path>',
];
$memberModuleIconClasses = [
    'dashboard' => 'border-sky-200 bg-sky-50 text-sky-700 group-hover:border-sky-300 group-hover:bg-sky-100',
    'members' => 'border-emerald-200 bg-emerald-50 text-emerald-700 group-hover:border-emerald-300 group-hover:bg-emerald-100',
    'qsl' => 'border-blue-200 bg-blue-50 text-blue-700 group-hover:border-blue-300 group-hover:bg-blue-100',
    'library' => 'border-amber-200 bg-amber-50 text-amber-700 group-hover:border-amber-300 group-hover:bg-amber-100',
    'webotheque' => 'border-indigo-200 bg-indigo-50 text-indigo-700 group-hover:border-indigo-300 group-hover:bg-indigo-100',
    'presentations' => 'border-teal-200 bg-teal-50 text-teal-700 group-hover:border-teal-300 group-hover:bg-teal-100',
    'videos' => 'border-red-200 bg-red-50 text-red-700 group-hover:border-red-300 group-hover:bg-red-100',
    'pv' => 'border-slate-200 bg-slate-50 text-slate-700 group-hover:border-slate-300 group-hover:bg-slate-100',
    'fichiers' => 'border-lime-200 bg-lime-50 text-lime-700 group-hover:border-lime-300 group-hover:bg-lime-100',
    'auctions' => 'border-rose-200 bg-rose-50 text-rose-700 group-hover:border-rose-300 group-hover:bg-rose-100',
    'classifieds' => 'border-cyan-200 bg-cyan-50 text-cyan-700 group-hover:border-cyan-300 group-hover:bg-cyan-100',
    'chatbot' => 'border-violet-200 bg-violet-50 text-violet-700 group-hover:border-violet-300 group-hover:bg-violet-100',
    'newsletter' => 'border-indigo-200 bg-indigo-50 text-indigo-700 group-hover:border-indigo-300 group-hover:bg-indigo-100',
];
$renderMemberModuleIcon = static function (string $moduleCode) use ($memberModuleIconPaths, $memberModuleIconClasses): string {
    $path = $memberModuleIconPaths[$moduleCode] ?? '<rect width="7" height="7" x="3" y="3" rx="1.5"></rect><rect width="7" height="7" x="14" y="3" rx="1.5"></rect><rect width="7" height="7" x="14" y="14" rx="1.5"></rect><rect width="7" height="7" x="3" y="14" rx="1.5"></rect>';
    $classes = $memberModuleIconClasses[$moduleCode] ?? 'border-slate-200 bg-slate-50 text-slate-700 group-hover:border-slate-300 group-hover:bg-slate-100';

    return '<span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border transition ' . $classes . '" aria-hidden="true">'
        . '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>'
        . '</span>';
};

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
        $memberModuleCards .= '<a class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2" href="' . e(route_url('membership')) . '">'
            . '<div class="flex items-center gap-3">'
            . $renderMemberModuleIcon($moduleCode)
            . '<h3 class="text-lg font-semibold text-slate-900">' . e($memberModuleText($moduleMeta, 'title', $moduleCode)) . '</h3>'
            . '</div>'
            . '<p class="mt-2 text-sm text-slate-600">' . e($memberModuleText($moduleMeta, 'desc')) . '</p>'
            . '<div class="mt-auto pt-4 flex items-center justify-between gap-3">'
            . '<span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600">' . e((string) $homeI18n['member_audience']) . '</span>'
            . '<span class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-sm font-semibold text-blue-700 transition group-hover:border-blue-300 group-hover:bg-blue-100">' . e($memberModulesJoinCta) . ' →</span>'
            . '</div>'
            . '</a>';
        $memberModuleCodesRendered[] = $moduleCode;
    }
}
$memberFallbackModuleCodes = ['dashboard', 'members', 'qsl', 'library', 'webotheque', 'presentations', 'videos', 'fichiers', 'pv', 'auctions', 'classifieds', 'chatbot', 'newsletter'];
foreach ($memberFallbackModuleCodes as $moduleCode) {
    if (!isset($memberModuleDefinitions[$moduleCode])) {
        continue;
    }
    if (in_array($moduleCode, ['dashboard', 'members', 'qsl', 'webotheque', 'presentations', 'videos', 'fichiers', 'pv', 'auctions', 'classifieds', 'chatbot'], true) && !module_enabled($moduleCode)) {
        continue;
    }
    $moduleMeta = $memberModuleDefinitions[$moduleCode];
    if (in_array($moduleCode, $memberModuleCodesRendered, true)) {
        continue;
    }
    $memberModuleCards .= '<a class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2" href="' . e(route_url('membership')) . '">'
        . '<div class="flex items-center gap-3">'
        . $renderMemberModuleIcon($moduleCode)
        . '<h3 class="text-lg font-semibold text-slate-900">' . e($memberModuleText($moduleMeta, 'title', $moduleCode)) . '</h3>'
        . '</div>'
        . '<p class="mt-2 text-sm text-slate-600">' . e($memberModuleText($moduleMeta, 'desc')) . '</p>'
        . '<div class="mt-auto pt-4 flex items-center justify-between gap-3">'
        . '<span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600">' . e((string) $homeI18n['member_audience']) . '</span>'
        . '<span class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-sm font-semibold text-blue-700 transition group-hover:border-blue-300 group-hover:bg-blue-100">' . e($memberModulesJoinCta) . ' →</span>'
        . '</div>'
        . '</a>';
    $memberModuleCodesRendered[] = $moduleCode;
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
    $heroImageCandidates = cache_remember('home_hero_image_candidates_v2', 300, static function (): array {
        return glob(__DIR__ . '/../assets/img/on4crd_hero*.png') ?: [];
    });
} catch (Throwable) {
    $heroImageCandidates = [];
}
if ($heroImageCandidates !== []) {
    $heroBackgroundUrl = asset_url('assets/img/' . basename((string) $heroImageCandidates[array_rand($heroImageCandidates)]));
}

$homeSeo = i18n_domain_locale('seo', $homeLocale);
$homeSeoText = static function (string $key) use ($homeSeo): string {
    $value = trim((string) ($homeSeo[$key] ?? ''));

    return $value !== '' ? $value : $key;
};
$homeUrl = route_url_with_locale('home', $homeLocale);
$homeSearchUrl = route_url_with_locale('search', $homeLocale);
$homeLogoUrl = asset_url('assets/logo/LOGO-CRD-HALO-2020.png');
$homeSeoImageUrl = asset_url('assets/img/on4crd_hero.png');
$homeSeoTitle = $homeSeoText('home_title');
$homeSeoDescription = $homeSeoText('home_description');
$homeGeoPlace = $homeSeoText('geo_placename');

set_page_meta([
    'title' => $homeSeoTitle,
    'description' => $homeSeoDescription,
    'og_type' => 'website',
    'schema_type' => 'WebPage',
    'image' => $homeSeoImageUrl,
    'image_alt' => $homeText('alt_hero_illustration'),
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
                    'sameAs' => [
                        'https://www.facebook.com/groups/clubradiodurnal/',
                        'https://www.uba.be',
                    ],
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
$latestClassifiedAd = null;
$latestWikiPage = null;
$latestArticle = null;

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

    if (module_enabled('classifieds') && module_visible_for_current_user('classifieds') && table_exists('classified_ads')) {
        $latestClassifiedAd = cache_remember('home_latest_classified_ad_v1', 60, static function () {
            return db()->query('SELECT title, description, location, price_cents, created_at FROM classified_ads WHERE ' . classifieds_active_where_sql() . ' ORDER BY created_at DESC, id DESC LIMIT 1')->fetch();
        });
    }

    if (module_enabled('wiki') && table_exists('wiki_pages')) {
        $latestWikiPage = cache_remember('home_latest_wiki_page_v2', 60, static function () {
            return db()->query('SELECT slug, title, content, updated_at FROM wiki_pages WHERE ' . wiki_public_page_where_sql() . ' ORDER BY updated_at DESC LIMIT 1')->fetch();
        });
    }

    if (module_enabled('articles') && table_exists('articles')) {
        $latestArticle = cache_remember('home_latest_article_v2', 60, static function () {
            $sort = article_publication_sort_expression();
            return db()->query('SELECT id, slug, title, excerpt, content, published_at, created_at, updated_at FROM articles WHERE status = "published" ORDER BY ' . $sort . ' DESC, id DESC LIMIT 1')->fetch();
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

$classifiedsHtml = '<a class="group block rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('classifieds')) . '">'
    . '<p>' . e($homeText('spotlight_classifieds_empty')) . '</p>'
    . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e($homeText('spotlight_classifieds_cta')) . ' →</span>'
    . '</a>';
if (is_array($latestClassifiedAd) && !empty($latestClassifiedAd['title'])) {
    $classifiedDescription = mb_safe_strimwidth(trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($latestClassifiedAd['description'] ?? ''))) ?? ''), 0, 130, '...');
    $classifiedLocation = trim((string) ($latestClassifiedAd['location'] ?? ''));
    $classifiedPrice = format_price_eur((int) ($latestClassifiedAd['price_cents'] ?? 0));
    $classifiedMeta = trim($classifiedPrice . ($classifiedLocation !== '' ? ' · ' . $classifiedLocation : ''));
    $classifiedsHtml = '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('classifieds', ['q' => (string) $latestClassifiedAd['title']])) . '">'
        . '<p class="text-xs font-semibold uppercase tracking-wide text-blue-700">' . e($classifiedMeta) . '</p>'
        . '<h3 class="mt-2 text-lg font-bold text-slate-900 group-hover:text-blue-700">' . e((string) $latestClassifiedAd['title']) . '</h3>'
        . ($classifiedDescription !== '' ? '<p class="mt-2 text-sm text-slate-600">' . e($classifiedDescription) . '</p>' : '')
        . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e($homeText('spotlight_classifieds_cta')) . ' →</span>'
        . '</a>';
}

$latestWikiHtml = '<a class="group block rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('wiki')) . '">'
    . '<p>' . e($homeText('spotlight_member_wiki_empty')) . '</p>'
    . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e($homeText('spotlight_member_open')) . ' →</span>'
    . '</a>';
if (is_array($latestWikiPage) && !empty($latestWikiPage['slug'])) {
    $wikiExcerpt = mb_safe_strimwidth(trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) ($latestWikiPage['content'] ?? '')))), 0, 130, '...');
    $wikiDate = !empty($latestWikiPage['updated_at']) ? date('d/m/Y', strtotime((string) $latestWikiPage['updated_at'])) : '';
    $latestWikiHtml = '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('wiki_view', ['slug' => (string) $latestWikiPage['slug']])) . '">'
        . ($wikiDate !== '' ? '<p class="text-xs font-semibold uppercase tracking-wide text-blue-700">' . e($homeText('spotlight_member_updated_on')) . ' ' . e($wikiDate) . '</p>' : '')
        . '<h3 class="mt-2 text-lg font-bold text-slate-900 group-hover:text-blue-700">' . e((string) $latestWikiPage['title']) . '</h3>'
        . ($wikiExcerpt !== '' ? '<p class="mt-2 text-sm text-slate-600">' . e($wikiExcerpt) . '</p>' : '')
        . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e($homeText('spotlight_member_open')) . ' →</span>'
        . '</a>';
}

$latestArticleHtml = '<a class="group block rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('articles')) . '">'
    . '<p>' . e($homeText('spotlight_member_article_empty')) . '</p>'
    . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e($homeText('spotlight_member_open')) . ' →</span>'
    . '</a>';
if (is_array($latestArticle) && !empty($latestArticle['slug'])) {
    $latestArticle = localized_article_row($latestArticle);
    $articleExcerpt = article_excerpt_from_input((string) ($latestArticle['excerpt_localized'] ?? $latestArticle['excerpt'] ?? ''));
    $articlePublished = article_publication_datetime($latestArticle);
    $articleDate = $articlePublished !== null ? date('d/m/Y', strtotime($articlePublished)) : '';
    $latestArticleHtml = '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('article', ['slug' => (string) $latestArticle['slug']])) . '">'
        . ($articleDate !== '' ? '<p class="text-xs font-semibold uppercase tracking-wide text-blue-700">' . e($homeText('spotlight_member_updated_on')) . ' ' . e($articleDate) . '</p>' : '')
        . '<h3 class="mt-2 text-lg font-bold text-slate-900 group-hover:text-blue-700">' . e((string) ($latestArticle['title_localized'] ?? $latestArticle['title'] ?? '')) . '</h3>'
        . ($articleExcerpt !== '' ? '<p class="mt-2 text-sm text-slate-600">' . e($articleExcerpt) . '</p>' : '')
        . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e($homeText('spotlight_member_open')) . ' →</span>'
        . '</a>';
}

$homeEventsCalendarConfig = [
    'locale' => $homeFullCalendarLocale,
    'initialView' => 'dayGridMonth',
    'eventsUrl' => route_url('events_feed'),
    'loadError' => $homeEventText('calendar_load_error'),
    'buttonText' => [
        'today' => $homeEventText('today'),
        'month' => $homeEventText('month'),
        'week' => $homeEventText('week'),
        'list' => $homeEventText('list'),
    ],
];
$nextEventHtml = '<div class="home-events-planning rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">'
    . '<link rel="stylesheet" href="' . e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/skeleton.css')) . '">'
    . '<link rel="stylesheet" href="' . e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/themes/classic/theme.css')) . '">'
    . '<link rel="stylesheet" href="' . e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/themes/classic/palette.css')) . '">'
    . '<div class="fullcalendar-theme home-events-calendar" data-home-events-calendar data-calendar-config="' . e(json_encode($homeEventsCalendarConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"></div>'
    . '<script src="' . e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/all.global.js')) . '" defer></script>'
    . '<script src="' . e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/themes/classic/global.js')) . '" defer></script>'
    . '<script src="' . e($homeFullCalendarLocaleAsset) . '" defer></script>'
    . '</div>';

$toolDayCta = trim((string) $homeI18n['spotlight_tool_day_cta']);
if ($toolDayCta !== '' && !str_ends_with($toolDayCta, '→')) {
    $toolDayCta .= ' →';
}

$toolDayHtml = '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="'
    . e(route_url('tools'))
    . '#tool-grid"><p class="text-sm font-semibold text-slate-900">'
    . e((string) $homeI18n['spotlight_tool_day_item'])
    . '</p><span class="mt-3 flex justify-end text-sm font-semibold text-blue-600 group-hover:text-blue-700">'
    . e($toolDayCta)
    . '</span></a>';
try {
    $toolsI18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/tools.php');
    $t = [];
    foreach (array_keys($toolsI18n['fr'] ?? []) as $key) {
        $value = trim(i18n_localized_value($toolsI18n, $homeLocale, (string) $key));
        $t[(string) $key] = $value !== '' ? $value : (string) ($toolsI18n['fr'][$key] ?? '');
    }
    $toolTr = static function (string $key, string $fallback = '') use ($t): string {
        $value = trim((string) ($t[$key] ?? ''));
        return $value !== '' ? $value : $fallback;
    };
    $toolCatalog = i18n_load_array_file_once(__DIR__ . '/../app/config/tools_catalog.php');
    $toolPanelMap = i18n_load_array_file_once(__DIR__ . '/../app/config/tools_panels.php');
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
        require_once (string) $selectedTool['path'];
        $selectedToolPanel = trim((string) ob_get_clean());
        if ($selectedToolPanel !== '') {
            $toolDayHtml = '<div class="home-tool-day">'
                . '<script type="application/json" id="tools-i18n">' . (json_encode($t, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}') . '</script>'
                . $selectedToolPanel
                . '<a class="mt-3 flex justify-end text-sm font-semibold text-blue-600 hover:text-blue-700" href="' . e(route_url('tools')) . '#' . e((string) $selectedTool['id']) . '">'
                . e($toolDayCta) . '</a>'
                . '</div>';
        }
    }
} catch (Throwable) {
    // The static link remains available if a random tool panel cannot be rendered.
}

$homeGalleryHtml = '';
if (module_enabled('albums') && table_exists('albums') && table_exists('album_photos')) {
    try {
        $homeGalleryPhotos = cache_remember('home_public_album_random_photos_v1', 60, static function (): array {
            $stmt = db()->query(
                'SELECT p.id, p.album_id, p.file_path, p.title, a.title AS album_title
                 FROM album_photos p
                 INNER JOIN albums a ON a.id = p.album_id
                 WHERE a.is_public = 1
                 ORDER BY RAND()
                 LIMIT 5'
            );

            return $stmt !== false ? ($stmt->fetchAll() ?: []) : [];
        });

        $homeGalleryItems = '';
        foreach ($homeGalleryPhotos as $photo) {
            $filePath = album_photo_public_path_or_null((string) ($photo['file_path'] ?? ''));
            if ($filePath === null) {
                continue;
            }

            $thumbPath = album_existing_thumbnail_fallback_public_path($filePath);
            $imageSrc = $thumbPath !== '' ? $thumbPath : $filePath;
            $imageWebpSrc = $thumbPath !== '' ? album_existing_thumbnail_webp_public_path($filePath) : album_existing_display_webp_public_path($filePath);
            $photoTitle = trim((string) ($photo['title'] ?? ''));
            $albumTitle = trim((string) ($photo['album_title'] ?? ''));
            $alt = $photoTitle !== '' ? $photoTitle : ($albumTitle !== '' ? $albumTitle : $homeText('spotlight_member_gallery'));

            $homeGalleryItems .= '<a class="home-media-slide home-gallery-slide" href="' . e(route_url('album', ['id' => (int) ($photo['album_id'] ?? 0)])) . '">'
                . album_picture_html($imageSrc, $alt, ['loading' => 'lazy', 'decoding' => 'async'], $imageWebpSrc)
                . '</a>';
        }

        if ($homeGalleryItems !== '') {
            $homeGalleryHtml = '<div class="home-media-carousel home-gallery-carousel" data-home-gallery-carousel aria-label="' . e($homeText('spotlight_member_gallery')) . '">'
                . '<div class="home-media-track home-gallery-track">' . $homeGalleryItems . '</div>'
                . '</div>';
        }
    } catch (Throwable) {
        // Keep the gallery empty when public album photos cannot be read.
    }
}

$memberSpotlightRowHtml = '';
if ($isAuthenticated) {
    $homeGalleryArticleHtml = $homeGalleryHtml !== ''
        ? '<article aria-label="' . e($homeText('spotlight_member_gallery')) . '"><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e($homeText('spotlight_member_gallery')) . '</h3>' . $homeGalleryHtml . '</article>'
        : '';
    $memberSpotlightRowHtml = '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e($homeText('spotlight_member_latest_wiki')) . '</h3>' . $latestWikiHtml . '</article>'
        . $homeGalleryArticleHtml
        . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e($homeText('spotlight_member_latest_article')) . '</h3>' . $latestArticleHtml . '</article>'
        . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e($homeText('spotlight_member_library')) . '</h3>'
        . '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('members_library')) . '">'
        . '<p class="text-sm font-semibold text-slate-900">' . e($homeText('spotlight_member_library')) . '</p>'
        . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e($homeText('spotlight_member_open')) . ' →</span></a></article>'
        . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e($homeText('spotlight_member_auctions')) . '</h3>'
        . '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('auctions')) . '">'
        . '<p class="text-sm font-semibold text-slate-900">' . e($homeText('spotlight_member_auctions')) . '</p>'
        . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e($homeText('spotlight_member_open')) . ' →</span></a></article>'
        . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e($homeText('spotlight_member_assistant')) . '</h3>'
        . '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('chatbot')) . '">'
        . '<p class="text-sm font-semibold text-slate-900">' . e($homeText('spotlight_member_assistant')) . '</p>'
        . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e($homeText('spotlight_member_open')) . ' →</span></a></article>'
        . '';
}

$adSlotHtml = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500"><p class="mt-2">' . e((string) $homeI18n['partner_ad_empty']) . '</p></div>';
try {
    $localAdCandidates = cache_remember('home_local_ad_candidates_v1', 300, static function (): array {
        return glob(__DIR__ . '/../assets/pub/*.{png,jpg,jpeg,webp,gif,avif}', GLOB_BRACE) ?: [];
    });
} catch (Throwable) {
    $localAdCandidates = [];
}
try {
    $sponsorSlides = [];
    foreach ($localAdCandidates as $localAdCandidate) {
        $localAdPath = 'assets/pub/' . basename((string) $localAdCandidate);
        $sponsorSlides[] = [
            'src' => asset_url($localAdPath),
            'alt' => (string) $homeI18n['alt_partner_ad'],
            'label' => $homeText('partner_ad_title'),
            'url' => '',
        ];
    }

    if (module_enabled('advertising') && table_exists('ads')) {
        $activeAds = cache_remember('home_active_ads_carousel_v1', 60, static function (): array {
            $stmt = db()->query('SELECT title, image_path, target_url FROM ads WHERE status = "active" AND image_path <> "" ORDER BY updated_at DESC LIMIT 12');

            return $stmt !== false ? ($stmt->fetchAll() ?: []) : [];
        });
        foreach ($activeAds as $activeAd) {
            $adImage = trim((string) ($activeAd['image_path'] ?? ''));
            if ($adImage === '') {
                continue;
            }

            $sponsorSlides[] = [
                'src' => asset_url($adImage),
                'alt' => trim((string) ($activeAd['title'] ?? '')) !== '' ? (string) $activeAd['title'] : (string) $homeI18n['alt_partner_ad'],
                'label' => trim((string) ($activeAd['title'] ?? '')),
                'url' => trim((string) ($activeAd['target_url'] ?? '')),
            ];
        }
    }

    $sponsorSlideHtml = '';
    foreach ($sponsorSlides as $slide) {
        $slideInner = '<img src="' . e((string) $slide['src']) . '" alt="' . e((string) $slide['alt']) . '" loading="lazy" decoding="async">';
        $sponsorSlideHtml .= (string) $slide['url'] !== ''
            ? '<a class="home-media-slide" href="' . e((string) $slide['url']) . '" target="_blank" rel="noopener noreferrer">' . $slideInner . '</a>'
            : '<div class="home-media-slide">' . $slideInner . '</div>';
    }

    if ($sponsorSlideHtml !== '') {
        $adSlotHtml = '<div class="home-media-carousel home-sponsor-carousel" data-home-sponsor-carousel aria-label="' . e($homeText('home_sponsors_title')) . '">'
            . '<div class="home-media-track">' . $sponsorSlideHtml . '</div>'
            . '</div>';
    }
} catch (Throwable) {
    // Keep the sponsor fallback when images cannot be loaded.
}

$trophySlotHtml = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500"><p class="mt-2">' . e($homeText('home_trophies_empty')) . '</p></div>';
try {
    $localTrophyCandidates = cache_remember('home_local_trophy_candidates_v1', 300, static function (): array {
        return glob(__DIR__ . '/../assets/trophy/*.{png,jpg,jpeg,webp,gif,avif}', GLOB_BRACE) ?: [];
    });
} catch (Throwable) {
    $localTrophyCandidates = [];
}
if ($localTrophyCandidates !== []) {
    $trophySlideHtml = '';
    foreach ($localTrophyCandidates as $localTrophyCandidate) {
        $localTrophyPath = 'assets/trophy/' . basename((string) $localTrophyCandidate);
        $trophySlideHtml .= '<div class="home-media-slide">'
            . '<img src="' . e(asset_url($localTrophyPath)) . '" alt="' . e($homeText('alt_trophy_image')) . '" loading="lazy" decoding="async">'
            . '</div>';
    }

    $trophySlotHtml = '<div class="home-media-carousel home-trophy-carousel" data-home-trophy-carousel aria-label="' . e($homeText('home_trophies_title')) . '">'
        . '<div class="home-media-track">' . $trophySlideHtml . '</div>'
        . '</div>';
}

$ubaLogoPath = 'assets/logo/UBA-Logo-Couleur-MID2.png';
$ibptLogoPath = 'assets/logo/logo_IBPT.png';
$relaisLogoPath = 'assets/logo/CRD-Echolink.jpg';
$homeWeatherHtml = $homeSafeWidget('open_meteo');
$homePropagationHtml = '';
$hasHomePropagation = false;
$homeHamAdviceHtml = $homeSafeHamAdvice();
$hamWeatherRefreshUrl = base_url('index.php?' . http_build_query(['route' => 'home', 'ajax' => 'ham_weather']));
$homeRadioInfoHtml = '<div class="grid gap-4">'
    . '<section>'
    . '<h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['ham_info_title']) . '</h4>'
    . '<ul class="mt-2 list-clean">'
    . '<li><strong>' . e((string) $homeI18n['vhf_voice_label']) . '</strong> ' . e((string) $homeI18n['vhf_voice_value']) . '</li>'
    . '<li><strong>' . e($homeText('cw_qrp_label')) . '</strong> ' . e($homeText('cw_qrp_value')) . '</li>'
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
            'propagation' => '',
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
    . '<div><h2 class="text-2xl font-extrabold text-slate-900">' . e($homeText('home_sponsors_title')) . '</h2>'
    . '<p class="mt-2 text-sm leading-6 text-slate-600">' . e($homeText('home_sponsors_desc')) . '</p></div>'
    . '<span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700">CRD</span>'
    . '</div>'
    . '<div class="mt-4">' . $adSlotHtml . '</div>'
    . '<div class="mt-4 flex"><a class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('sponsoring')) . '">' . e($homeText('home_sponsors_cta')) . '</a></div>'
    . '</article>'
    . '<article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="flex items-start justify-between gap-4">'
    . '<div><h2 class="text-2xl font-extrabold text-slate-900">' . e($homeText('home_trophies_title')) . '</h2>'
    . '<p class="mt-2 text-sm leading-6 text-slate-600">' . e($homeText('home_trophies_desc')) . '</p></div>'
    . '<span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-700">CRD</span>'
    . '</div>'
    . '<div class="mt-4">' . $trophySlotHtml . '</div>'
    . '<a class="mt-4 inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('events')) . '">' . e($homeText('home_trophies_cta')) . '</a>'
    . '</article>'
    . '<article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<h2 class="text-2xl font-extrabold text-slate-900">' . e($homeText('home_other_sections_title')) . '</h2>'
    . '<p class="mt-2 text-sm leading-6 text-slate-600">' . e($homeText('home_other_sections_desc')) . '</p>'
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
    . '<article class="home-hero-card relative isolate flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200 p-8 shadow-sm">'
    . '<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="' . e($heroBackgroundUrl) . '" alt="' . e((string) $homeI18n['alt_hero_illustration']) . '" loading="eager" decoding="async" fetchpriority="high">'
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
    . '<div class="grid gap-4 lg:grid-cols-2">'
    . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e($homeText('latest_news_title')) . '</h3>' . $latestNewsHtml . '</article>'
    . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e($homeText('spotlight_classifieds')) . '</h3>' . $classifiedsHtml . '</article>'
    . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e($homeText('next_event_title')) . '</h3>' . $nextEventHtml . '</article>'
    . '<article><h3 class="home-tool-day-title mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e($homeText('spotlight_auction_live')) . '</h3>' . $toolDayHtml . '</article>'
    . $memberSpotlightRowHtml
    . '</div>'
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="mb-5 max-w-3xl"><h2 class="text-2xl font-extrabold text-slate-900">' . e($homeText('join_benefits_title')) . '</h2><p class="mt-2 text-slate-600">' . e($homeText('join_benefits_desc')) . '</p></div>'
    . '<div class="grid gap-4 md:grid-cols-3">'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="text-lg font-semibold text-slate-900">' . e($homeText('join_benefit_1_title')) . '</h3><p class="mt-2 text-sm leading-6 text-slate-600">' . e($homeText('join_benefit_1_desc')) . '</p></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="text-lg font-semibold text-slate-900">' . e($homeText('join_benefit_2_title')) . '</h3><p class="mt-2 text-sm leading-6 text-slate-600">' . e($homeText('join_benefit_2_desc')) . '</p></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="text-lg font-semibold text-slate-900">' . e($homeText('join_benefit_3_title')) . '</h3><p class="mt-2 text-sm leading-6 text-slate-600">' . e($homeText('join_benefit_3_desc')) . '</p></article>'
    . '</div>'
    . '</section>'
    . $memberModulesSectionHtml
    . '<section class="mt-4 grid gap-4 rounded-3xl border border-blue-200 bg-gradient-to-r from-blue-50 to-white p-6 shadow-sm lg:grid-cols-[1.8fr_1fr] lg:items-center">'
    . '<div><h2 class="text-2xl font-extrabold text-slate-900">' . e((string) $homeI18n['join_title']) . '</h2><p class="mt-2 text-slate-600">' . e((string) $homeI18n['join_desc']) . '</p></div>'
    . '<div class="grid gap-2">' . $primaryCta . $newsletterCta . '</div>'
    . '</section>'
    . $homeSponsorsTrophiesSectionHtml
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="grid gap-4 lg:grid-cols-[1.15fr_.85fr]">'
    . '<div class="grid gap-4">'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-900">' . e((string) $homeI18n['uba_title']) . '</h3><p class="mt-2 text-sm text-slate-600">' . e((string) $homeI18n['uba_desc']) . '</p><a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="https://www.uba.be" target="_blank" rel="noopener noreferrer">' . e((string) $homeI18n['uba_cta']) . '</a></div><img class="h-20 w-auto object-contain" src="' . e(asset_url($ubaLogoPath)) . '" alt="' . e((string) $homeI18n['alt_uba_logo']) . '" loading="lazy" decoding="async"></div></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-900">' . e((string) $homeI18n['ibpt_title']) . '</h3><p class="mt-2 text-sm text-slate-600">' . e((string) $homeI18n['ibpt_desc']) . '</p><a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="https://www.ibpt.be/consommateurs/frequences-radio/utilisation-privee-de-loisir/radioamateurs" target="_blank" rel="noopener noreferrer">' . e((string) $homeI18n['ibpt_cta']) . '</a></div><img class="h-20 w-auto object-contain" src="' . e(asset_url($ibptLogoPath)) . '" alt="IBPT" loading="lazy" decoding="async"></div></article>'
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
    . '<article><h3 class="text-lg font-bold text-slate-900">' . e((string) $homeI18n['contact_title']) . '</h3><form class="mt-3 grid gap-2" method="post" action="' . e(route_url('footer_contact')) . '"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="return_route" value="home"><input type="text" name="contact_website" value="" autocomplete="off" tabindex="-1" aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden"><label for="home-contact-name" class="sr-only">' . e((string) $homeI18n['contact_name']) . '</label><input id="home-contact-name" type="text" name="name" placeholder="' . e((string) $homeI18n['contact_name']) . '" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><label for="home-contact-email" class="sr-only">' . e((string) $homeI18n['contact_email']) . '</label><input id="home-contact-email" type="email" name="email" placeholder="' . e((string) $homeI18n['contact_email']) . '" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><label for="home-contact-message" class="sr-only">' . e((string) $homeI18n['contact_message']) . '</label><textarea id="home-contact-message" name="message" placeholder="' . e((string) $homeI18n['contact_message']) . '" rows="3" maxlength="2000" data-wysiwyg="off" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea><label for="home-contact-captcha" class="text-xs font-semibold text-slate-600">' . e($homeContactCaptchaLabel) . '</label><input id="home-contact-captcha" type="text" inputmode="numeric" pattern="[0-9]*" name="contact_captcha" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">' . e((string) $homeI18n['contact_send']) . '</button></form></article>'
    . '<article><h3 class="text-lg font-bold text-slate-900">' . e((string) $homeI18n['important_info_title']) . '</h3><ul class="mt-3 list-inside list-disc space-y-1 text-sm text-slate-700"><li><a class="hover:underline" href="' . e(route_url('conditions_utilisation')) . '">' . e((string) $homeI18n['link_terms']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('mentions_legales')) . '">' . e((string) $homeI18n['link_legal']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('reglement_interieur')) . '">' . e((string) $homeI18n['link_internal_rules']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('donation')) . '">' . e((string) $homeI18n['link_donate']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('sponsoring')) . '">' . e((string) $homeI18n['link_sponsoring']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('code_q')) . '">' . e((string) $homeI18n['link_code_q']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('code_cw')) . '">' . e((string) $homeI18n['link_code_cw']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('bandplan_on3')) . '">' . e((string) $homeI18n['link_bandplan_on3']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('bandplan_on2')) . '">' . e((string) $homeI18n['link_bandplan_on2']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('bandplan_harec')) . '">' . e((string) $homeI18n['link_bandplan_harec']) . '</a></li></ul></article>'
    . '</div>'
    . '</section>';


echo render_layout($content, $homeText('page_title'));
