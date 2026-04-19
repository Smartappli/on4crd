<?php
declare(strict_types=1);

$user = current_user();
$isAuthenticated = $user !== null;

$primaryCta = $isAuthenticated
    ? '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('dashboard')) . '">Accéder à mon espace membre</a>'
    : '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('login')) . '">Rejoindre le club</a>';

$secondaryCta = '<a class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('events')) . '">Voir l’agenda des activités</a>';

$modules = [
    ['code' => 'news', 'route' => 'news', 'title' => 'Actualités du club', 'desc' => 'Annonces, comptes rendus et informations essentielles.'],
    ['code' => 'events', 'route' => 'events', 'title' => 'Activités & événements', 'desc' => 'Rencontres, ateliers techniques et activations terrain.'],
    ['code' => 'wiki', 'route' => 'wiki', 'title' => 'Base technique', 'desc' => 'Documentation radioamateur, procédures et astuces.'],
    ['code' => 'directory', 'route' => 'directory', 'title' => 'Communauté', 'desc' => 'Annuaire des membres et spécialités disponibles.'],
    ['code' => 'albums', 'route' => 'albums', 'title' => 'Galerie photo', 'desc' => 'Moments marquants et vie associative du RC Durnal.'],
    ['code' => 'shop', 'route' => 'shop', 'title' => 'Ressources club', 'desc' => 'Boutique et supports pour la vie du club.'],
];

$activeModules = [];
$moduleCards = '';
foreach ($modules as $module) {
    if (!module_enabled((string) $module['code'])) {
        continue;
    }

    $activeModules[] = $module;
    $moduleCards .= '<a class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" href="' . e(route_url((string) $module['route'])) . '">'
        . '<h3 class="text-lg font-semibold text-slate-900">' . e((string) $module['title']) . '</h3>'
        . '<p class="mt-2 text-sm text-slate-600">' . e((string) $module['desc']) . '</p>'
        . '<span class="mt-4 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">Explorer →</span>'
        . '</a>';
}

if ($moduleCards === '') {
    $moduleCards = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">Les espaces publics sont en cours de mise à jour.</div>';
}

$heroTitle = $isAuthenticated
    ? 'Heureux de vous revoir sur le portail ON4CRD'
    : 'Le portail officiel du Radio Club de Durnal';

$heroSubtitle = $isAuthenticated
  
    ? 'Retrouvez en un clic vos outils, vos contenus et les prochaines activités du club.'
    : 'Un design moderne et une navigation homogène pour découvrir, suivre et rejoindre la communauté radioamateur de Durnal.';

$content = '<section class="grid gap-4 lg:grid-cols-[1.55fr_.95fr]">'
    . '<article class="rounded-3xl border border-slate-200 bg-gradient-to-br from-white to-blue-50 p-8 shadow-sm">'
    . '<span class="inline-flex rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">ON4CRD · Radio Club de Durnal</span>'
    . '<h1 class="mt-4 max-w-xl text-4xl font-extrabold leading-tight text-slate-900 lg:text-5xl">' . e($heroTitle) . '</h1>'
    . '<p class="mt-4 max-w-2xl text-base text-slate-600">' . e($heroSubtitle) . '</p>'
    . '<div class="mt-6 flex flex-wrap gap-3">' . $primaryCta . $secondaryCta . '</div>'
    . '<div class="mt-6 flex flex-wrap gap-2 text-sm font-medium text-slate-700">'
    . '<span class="rounded-full border border-blue-100 bg-white px-3 py-1">📡 Radioamateurisme</span>'
    . '<span class="rounded-full border border-blue-100 bg-white px-3 py-1">🤝 Vie associative</span>'
    . '<span class="rounded-full border border-blue-100 bg-white px-3 py-1">🎓 Transmission technique</span>'
    . '</div>'
    . '</article>'
    . '<aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<h2 class="text-xl font-bold text-slate-900">Vue d’ensemble</h2>'
    . '<div class="mt-4 grid gap-3">'
    . '<article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><strong class="block text-2xl font-extrabold text-slate-900">' . e((string) count($activeModules)) . '</strong><span class="text-sm text-slate-600">espaces actifs</span></article>'
    . '<article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><strong class="block text-2xl font-extrabold text-slate-900">100%</strong><span class="text-sm text-slate-600">navigation unifiée</span></article>'
    . '<article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><strong class="block text-2xl font-extrabold text-slate-900">24/7</strong><span class="text-sm text-slate-600">accès aux ressources</span></article>'
    . '</div>'
    . '<ul class="mt-4 space-y-2 text-sm text-slate-600">'
    . '<li>• Présentation claire pour les visiteurs.</li>'
    . '<li>• Expérience cohérente pour les membres.</li>'
    . '<li>• Accès rapide aux modules du site.</li>'
    . '</ul>'
    . '</aside>'
    . '</section>'
    . '<section class="mt-4 grid gap-4 md:grid-cols-3">'
    . '<article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-2xl">📶</p><h3 class="mt-2 text-lg font-semibold">Initiation & accompagnement</h3><p class="mt-2 text-sm text-slate-600">Des membres expérimentés accompagnent les nouveaux opérateurs.</p></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-2xl">🛠️</p><h3 class="mt-2 text-lg font-semibold">Pratique concrète</h3><p class="mt-2 text-sm text-slate-600">Ateliers, essais matériels, activations et exercices radio.</p></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-2xl">🌍</p><h3 class="mt-2 text-lg font-semibold">Impact local</h3><p class="mt-2 text-sm text-slate-600">Un club ancré à Durnal, ouvert aux projets et partenariats.</p></article>'
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<header class="mb-4"><h2 class="text-2xl font-bold text-slate-900">Explorer les rubriques du club</h2><p class="mt-1 text-slate-600">Un design homogène pour naviguer facilement entre les modules.</p></header>'
    . '<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">' . $moduleCards . '</div>'
    . '</section>'
    . '<section class="mt-4 grid gap-4 md:grid-cols-2">'
    . '<article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h3 class="text-xl font-bold">Parcours visiteur</h3><ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-600"><li>Découvrir le club via les actualités et la galerie.</li><li>Identifier les prochaines activités ouvertes.</li><li>Prendre contact puis participer à une rencontre.</li></ol></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h3 class="text-xl font-bold">Parcours membre</h3><ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-600"><li>Se connecter à son espace personnel.</li><li>Contribuer aux contenus techniques et à la vie du club.</li><li>S’engager dans les projets, événements et actions locales.</li></ol></article>'
    . '</section>'
    . '<section class="mt-4 grid gap-4 rounded-3xl border border-blue-200 bg-gradient-to-r from-blue-50 to-white p-6 shadow-sm lg:grid-cols-[1.8fr_1fr] lg:items-center">'
    . '<div><h2 class="text-2xl font-extrabold text-slate-900">Envie de découvrir la radioamateur à Durnal ?</h2><p class="mt-2 text-slate-600">Le club accueille débutants et opérateurs confirmés pour apprendre, expérimenter et partager la passion des ondes.</p></div>'
    . '<div class="grid gap-2">' . $primaryCta . '<a class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('news')) . '">Lire les actualités</a></div>'
    . '</section>';

echo render_layout($content, 'Accueil');
