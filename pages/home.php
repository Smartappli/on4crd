<?php
declare(strict_types=1);

$user = current_user();
$isAuthenticated = $user !== null;

$primaryCta = $isAuthenticated
    ? '<a class="button" href="' . e(route_url('dashboard')) . '">Accéder à mon espace membre</a>'
    : '<a class="button" href="' . e(route_url('login')) . '">Rejoindre le club</a>';

$secondaryCta = '<a class="button secondary" href="' . e(route_url('events')) . '">Voir l’agenda des activités</a>';

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
    $moduleCards .= '<a class="landing-module-card" href="' . e(route_url((string) $module['route'])) . '">'
        . '<h3>' . e((string) $module['title']) . '</h3>'
        . '<p>' . e((string) $module['desc']) . '</p>'
        . '<span>Explorer →</span>'
        . '</a>';
}

if ($moduleCards === '') {
    $moduleCards = '<div class="empty-state">Les espaces publics sont en cours de mise à jour.</div>';
}

$heroTitle = $isAuthenticated
    ? 'Heureux de vous revoir sur le portail ON4CRD'
    : 'Le portail officiel du Radio Club de Durnal';

$heroSubtitle = $isAuthenticated
    ? 'Retrouvez en un clic vos outils, vos contenus et les prochaines activités du club.'
    : 'Une landing page professionnelle avec une navigation claire pour les visiteurs et les membres.';

$content = '<section class="landing-v2-hero">'
    . '<div class="landing-v2-copy">'
    . '<span class="badge">ON4CRD · Radio Club de Durnal</span>'
    . '<h1>' . e($heroTitle) . '</h1>'
    . '<p>' . e($heroSubtitle) . '</p>'
    . '<div class="actions">' . $primaryCta . $secondaryCta . '</div>'
    . '<div class="landing-v2-tags"><span>📡 Radioamateurisme</span><span>🤝 Vie associative</span><span>🎓 Transmission technique</span></div>'
    . '</div>'
    . '<aside class="landing-v2-panel">'
    . '<h2>Vue d’ensemble</h2>'
    . '<div class="landing-v2-stats">'
    . '<article><strong>' . e((string) count($activeModules)) . '</strong><span>espaces actifs</span></article>'
    . '<article><strong>100%</strong><span>navigation unifiée</span></article>'
    . '<article><strong>24/7</strong><span>accès aux ressources</span></article>'
    . '</div>'
    . '<ul class="landing-v2-checks"><li>Présentation claire pour les visiteurs</li><li>Expérience cohérente pour les membres</li><li>Accès rapide aux modules du site</li></ul>'
    . '</aside>'
    . '</section>'
    . '<section class="landing-v2-values">'
    . '<article class="landing-value-card"><span class="landing-icon">📶</span><h3>Initiation & accompagnement</h3><p>Des membres expérimentés accompagnent les nouveaux opérateurs.</p></article>'
    . '<article class="landing-value-card"><span class="landing-icon">🛠️</span><h3>Pratique concrète</h3><p>Ateliers, essais matériels, activations et exercices radio.</p></article>'
    . '<article class="landing-value-card"><span class="landing-icon">🌍</span><h3>Impact local</h3><p>Un club ancré à Durnal, ouvert aux projets et partenariats.</p></article>'
    . '</section>'
    . '<section class="landing-v2-modules">'
    . '<header><h2>Explorer les rubriques du club</h2><p>Un design homogène pour naviguer facilement entre les modules.</p></header>'
    . '<div class="landing-v2-module-grid">' . $moduleCards . '</div>'
    . '</section>'
    . '<section class="landing-v2-journeys">'
    . '<article class="landing-journey-card"><h3>Parcours visiteur</h3><ol><li>Découvrir le club via les actualités et la galerie.</li><li>Identifier les prochaines activités ouvertes.</li><li>Prendre contact puis participer à une rencontre.</li></ol></article>'
    . '<article class="landing-journey-card"><h3>Parcours membre</h3><ol><li>Se connecter à son espace personnel.</li><li>Contribuer aux contenus techniques et à la vie du club.</li><li>S’engager dans les projets, événements et actions locales.</li></ol></article>'
    . '</section>'
    . '<section class="landing-v2-bottom-cta">'
    . '<div><h2>Envie de découvrir la radioamateur à Durnal ?</h2><p>Le club accueille débutants et opérateurs confirmés pour apprendre, expérimenter et partager la passion des ondes.</p></div>'
    . '<div class="landing-v2-bottom-actions">' . $primaryCta . '<a class="button secondary" href="' . e(route_url('news')) . '">Lire les actualités</a></div>'
    . '</section>';

echo render_layout($content, 'Accueil');
