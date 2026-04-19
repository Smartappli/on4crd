<?php
declare(strict_types=1);

$user = current_user();
$isAuthenticated = $user !== null;
$primaryCta = $isAuthenticated
    ? '<a class="button" href="' . e(route_url('dashboard')) . '">Accéder au tableau de bord</a>'
    : '<a class="button" href="' . e(route_url('login')) . '">Connexion membre</a>';
$secondaryCta = '<a class="button secondary" href="' . e(route_url('events')) . '">Voir les événements</a>';

$modules = [
    ['code' => 'news', 'route' => 'news', 'title' => 'News', 'desc' => 'Fil d’actualités et publications du club.'],
    ['code' => 'wiki', 'route' => 'wiki', 'title' => 'Wiki', 'desc' => 'Base de connaissances collaborative.'],
    ['code' => 'events', 'route' => 'events', 'title' => 'Événements', 'desc' => 'Calendrier et détails des activités.'],
    ['code' => 'directory', 'route' => 'directory', 'title' => 'Annuaire', 'desc' => 'Coordonnées et profils des membres.'],
    ['code' => 'shop', 'route' => 'shop', 'title' => 'Boutique', 'desc' => 'Catalogue et commandes du club.'],
    ['code' => 'auctions', 'route' => 'auctions', 'title' => 'Enchères', 'desc' => 'Annonces et suivi des offres.'],
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
    $moduleCards = '<div class="empty-state">Aucun module public actif pour le moment.</div>';
}

$featureCards = [
    ['title' => 'Communication du club', 'desc' => 'Actualités, articles et contenus éditoriaux dans une expérience cohérente.'],
    ['title' => 'Collaboration interne', 'desc' => 'Wiki, annuaire membres et rôles pour fluidifier le travail quotidien.'],
    ['title' => 'Engagement communautaire', 'desc' => 'Événements, albums, boutique et enchères pour dynamiser la vie du club.'],
];
$featureMarkup = '';
foreach ($featureCards as $feature) {
    $featureMarkup .= '<article class="card feature-card"><h3>' . e($feature['title']) . '</h3><p class="help">'
        . e($feature['desc']) . '</p></article>';
}

$quickLinks = [
    ['label' => 'Lire les actualités', 'route' => 'news'],
    ['label' => 'Explorer le wiki', 'route' => 'wiki'],
    ['label' => 'Consulter l’agenda', 'route' => 'events'],
];
$quickLinkMarkup = '';
foreach ($quickLinks as $link) {
    $quickLinkMarkup .= '<li><a href="' . e(route_url((string) $link['route'])) . '">' . e((string) $link['label']) . '</a></li>';
}

$kpiAuthLabel = $isAuthenticated ? 'Membre connecté' : 'Visiteur public';
$kpiAuthValue = $isAuthenticated ? e((string) ($user['callsign'] ?? 'Compte actif')) : 'Accès libre';

$content = '<section class="hero hero-home landing-hero">'
    . '<article class="card hero-copy">'
    . '<span class="badge">Plateforme ON4CRD</span>'
    . '<h1>Une landing page professionnelle pour votre club radioamateur</h1>'
    . '<p class="hero-lead">Pilotez les activités, valorisez les contenus techniques et offrez une expérience claire aux membres comme aux visiteurs.</p>'
    . '<div class="actions">' . $primaryCta . $secondaryCta . '</div>'
    . '</article>'
    . '<aside class="hero-panel landing-kpi-panel">'
    . '<h2>Vue d’ensemble</h2>'
    . '<div class="landing-kpi-grid">'
    . '<div class="stat-card"><span class="help">Modules publics actifs</span><strong>' . e((string) count($activeModules)) . '</strong></div>'
    . '<div class="stat-card"><span class="help">' . e($kpiAuthLabel) . '</span><strong>' . $kpiAuthValue . '</strong></div>'
    . '<div class="stat-card"><span class="help">Parcours rapide</span><strong>3 étapes</strong></div>'
    . '</div>'
    . '</aside>'
    . '</section>'
    . '<section class="grid-3 inner-card">' . $featureMarkup . '</section>'
    . '<section class="card stack inner-card">'
    . '<div class="section-header"><h2>Accès rapide aux modules</h2><span class="help">Navigation orientée productivité</span></div>'
    . '<div class="audience-grid">' . $moduleCards . '</div>'
    . '</section>'
    . '<section class="card split inner-card">'
    . '<article>'
    . '<h2>Démarrage en moins de 2 minutes</h2>'
    . '<ol class="list-spaced">'
    . '<li><strong>1. Explorer</strong><span class="help">Parcourez les pages publiques pour découvrir les activités du club.</span></li>'
    . '<li><strong>2. Se connecter</strong><span class="help">Accédez à vos fonctionnalités membres selon vos rôles.</span></li>'
    . '<li><strong>3. Contribuer</strong><span class="help">Publiez, collaborez et participez aux modules actifs.</span></li>'
    . '</ol>'
    . '</article>'
    . '<aside>'
    . '<h3>Raccourcis utiles</h3>'
    . '<ul class="list-clean list-spaced">' . $quickLinkMarkup . '</ul>'
    . '</aside>'
    . '</section>';

echo render_layout($content, 'Accueil');
