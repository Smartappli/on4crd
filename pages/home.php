<?php
declare(strict_types=1);

$user = current_user();
$isAuthenticated = $user !== null;

$primaryCta = $isAuthenticated
    ? '<a class="button" href="' . e(route_url('dashboard')) . '">Accéder à mon espace membre</a>'
    : '<a class="button" href="' . e(route_url('login')) . '">Devenir membre / Se connecter</a>';

$secondaryCta = '<a class="button secondary" href="' . e(route_url('events')) . '">Découvrir les prochaines activités</a>';

$modules = [
    ['code' => 'news', 'route' => 'news', 'title' => 'Actualités', 'desc' => 'Vie du club, comptes rendus et annonces officielles.'],
    ['code' => 'events', 'route' => 'events', 'title' => 'Agenda radio', 'desc' => 'Rencontres, activations terrain et rendez-vous techniques.'],
    ['code' => 'wiki', 'route' => 'wiki', 'title' => 'Ressources techniques', 'desc' => 'Guides, procédures et documentation partagée.'],
    ['code' => 'directory', 'route' => 'directory', 'title' => 'Communauté', 'desc' => 'Annuaire des membres et expertises disponibles.'],
    ['code' => 'albums', 'route' => 'albums', 'title' => 'Albums', 'desc' => 'Photos des activités, stations et événements du club.'],
    ['code' => 'shop', 'route' => 'shop', 'title' => 'Boutique club', 'desc' => 'Goodies, matériel associatif et ressources imprimées.'],
];

$activeModules = [];
$moduleCards = '';
foreach ($modules as $module) {
    if (!module_enabled((string) $module['code'])) {
        continue;
    }

    $activeModules[] = $module;
    $moduleCards .= '<a class="audience-card" href="' . e(route_url((string) $module['route'])) . '"><strong>'
        . e((string) $module['title']) . '</strong><span>' . e((string) $module['desc']) . '</span></a>';
}

if ($moduleCards === '') {
    $moduleCards = '<div class="empty-state">Les espaces publics sont en cours de préparation.</div>';
}

$pillarCards = [
    ['title' => 'Former et transmettre', 'desc' => 'Initiation à la radio, mentorat et partage des bonnes pratiques entre générations.'],
    ['title' => 'Expérimenter sur le terrain', 'desc' => 'Activations, exercices, concours et sorties autour de la pratique radioamateur.'],
    ['title' => 'Animer la communauté locale', 'desc' => 'Présence à Durnal, partenariats associatifs et projets ouverts au public.'],
];

$pillarMarkup = '';
foreach ($pillarCards as $pillar) {
    $pillarMarkup .= '<article class="card feature-card"><h3>' . e($pillar['title']) . '</h3><p class="help">'
        . e($pillar['desc']) . '</p></article>';
}

$visitorJourney = [
    'Consultez les actualités et l’agenda pour suivre la vie du club.',
    'Découvrez nos activités techniques et nos projets associatifs.',
    'Prenez contact puis rejoignez la communauté ON4CRD.',
];

$memberJourney = [
    'Connectez-vous pour accéder à votre espace personnel.',
    'Participez au wiki, aux annonces et aux événements.',
    'Contribuez aux projets du club selon vos compétences et disponibilités.',
];

$visitorJourneyMarkup = '';
foreach ($visitorJourney as $step) {
    $visitorJourneyMarkup .= '<li><span class="help">' . e($step) . '</span></li>';
}

$memberJourneyMarkup = '';
foreach ($memberJourney as $step) {
    $memberJourneyMarkup .= '<li><span class="help">' . e($step) . '</span></li>';
}

$identityTitle = $isAuthenticated
    ? 'Bienvenue ' . e((string) ($user['callsign'] ?? 'membre ON4CRD'))
    : 'Portail officiel du Radio Club de Durnal';

$identitySubtitle = $isAuthenticated
    ? 'Retrouvez vos outils membres, vos contenus et les activités en un seul endroit.'
    : 'Une vitrine claire et moderne pour présenter nos activités radioamateurs et accueillir de nouveaux passionnés.';

$content = '<section class="hero hero-home landing-hero landing-durnal">'
    . '<article class="card hero-copy landing-hero-copy">'
    . '<span class="badge">Radio Club de Durnal · ON4CRD</span>'
    . '<h1>' . $identityTitle . '</h1>'
    . '<p class="hero-lead">' . e($identitySubtitle) . '</p>'
    . '<div class="actions">' . $primaryCta . $secondaryCta . '</div>'
    . '<div class="pill-row">'
    . '<span class="pill">📡 Radioamateurisme</span>'
    . '<span class="pill">🤝 Vie associative</span>'
    . '<span class="pill">🧭 Activités locales</span>'
    . '</div>'
    . '</article>'
    . '<aside class="hero-panel landing-kpi-panel">'
    . '<h2>Pourquoi cette plateforme&nbsp;?</h2>'
    . '<div class="landing-kpi-grid">'
    . '<div class="stat-card"><span class="help">Services accessibles</span><strong>' . e((string) count($activeModules)) . '</strong></div>'
    . '<div class="stat-card"><span class="help">Entrée principale</span><strong>Accueil public</strong></div>'
    . '<div class="stat-card"><span class="help">Expérience</span><strong>Simple & professionnelle</strong></div>'
    . '</div>'
    . '</aside>'
    . '</section>'
    . '<section class="grid-3 inner-card">' . $pillarMarkup . '</section>'
    . '<section class="card stack inner-card">'
    . '<div class="section-header"><h2>Explorer le club en quelques clics</h2><span class="help">Sections clés de la landing page</span></div>'
    . '<div class="audience-grid">' . $moduleCards . '</div>'
    . '</section>'
    . '<section class="grid-2 inner-card landing-journeys">'
    . '<article class="card stack">'
    . '<h2>Parcours visiteur</h2>'
    . '<ol class="list-spaced landing-steps">' . $visitorJourneyMarkup . '</ol>'
    . '</article>'
    . '<article class="card stack">'
    . '<h2>Parcours membre</h2>'
    . '<ol class="list-spaced landing-steps">' . $memberJourneyMarkup . '</ol>'
    . '</article>'
    . '</section>'
    . '<section class="card split inner-card landing-final-cta">'
    . '<article>'
    . '<h2>Construisons ensemble une communauté radio forte à Durnal</h2>'
    . '<p class="help">Que vous soyez curieux, débutant ou opérateur confirmé, le Radio Club de Durnal vous accueille pour apprendre, pratiquer et partager la passion des ondes.</p>'
    . '</article>'
    . '<aside class="stack">'
    . $primaryCta
    . '<a class="button secondary" href="' . e(route_url('news')) . '">Lire les dernières actualités</a>'
    . '</aside>'
    . '</section>';

echo render_layout($content, 'Accueil');
