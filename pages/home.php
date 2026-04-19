<?php
declare(strict_types=1);

$user = current_user();
$primaryCta = $user === null
    ? '<a class="button" href="' . e(route_url('login')) . '">Se connecter</a>'
    : '<a class="button" href="' . e(route_url('dashboard')) . '">Ouvrir le tableau de bord</a>';

$secondaryCta = '<a class="button secondary" href="' . e(route_url('articles')) . '">Découvrir les contenus</a>';
$headline = 'La plateforme radioamateur moderne pour votre club';
$lead = 'ON4CRD centralise actualités, wiki, événements, annuaire membres, boutique, enchères et outils QSL dans une expérience claire et fiable.';

$highlights = [
    ['title' => 'Actualités éditoriales', 'desc' => 'Publiez des nouvelles multilingues et des articles techniques validés.'],
    ['title' => 'Vie du club', 'desc' => 'Agenda, comité, presse et albums pour garder toute la communauté alignée.'],
    ['title' => 'Opérations numériques', 'desc' => 'Permissions fines, observabilité et modules activables selon vos besoins.'],
];

$modules = [
    ['code' => 'news', 'route' => 'news', 'title' => 'News', 'desc' => 'Fil d’actualités et publications du club.'],
    ['code' => 'wiki', 'route' => 'wiki', 'title' => 'Wiki', 'desc' => 'Base de connaissances collaborative.'],
    ['code' => 'events', 'route' => 'events', 'title' => 'Événements', 'desc' => 'Calendrier et détails des activités.'],
    ['code' => 'directory', 'route' => 'directory', 'title' => 'Annuaire', 'desc' => 'Coordonnées et profils des membres.'],
    ['code' => 'shop', 'route' => 'shop', 'title' => 'Boutique', 'desc' => 'Catalogue et commandes du club.'],
    ['code' => 'auctions', 'route' => 'auctions', 'title' => 'Enchères', 'desc' => 'Annonces et suivi des offres.'],
];

$moduleCards = '';
foreach ($modules as $module) {
    if (!module_enabled((string) $module['code'])) {
        continue;
    }

    $moduleCards .= '<a class="audience-card" href="' . e(route_url((string) $module['route'])) . '"><strong>'
        . e((string) $module['title']) . '</strong><span>' . e((string) $module['desc']) . '</span></a>';
}

if ($moduleCards === '') {
    $moduleCards = '<div class="empty-state">Aucun module public actif pour le moment.</div>';
}

$highlightList = '';
foreach ($highlights as $item) {
    $highlightList .= '<li><strong>' . e($item['title']) . '</strong><span>' . e($item['desc']) . '</span></li>';
}

$content = '<section class="hero hero-home">'
    . '<article class="card hero-copy">'
    . '<span class="badge">Plateforme ON4CRD</span>'
    . '<h1>' . e($headline) . '</h1>'
    . '<p class="hero-lead">' . e($lead) . '</p>'
    . '<div class="actions">' . $primaryCta . $secondaryCta . '</div>'
    . '</article>'
    . '<aside class="hero-panel">'
    . '<h2>Pourquoi ON4CRD&nbsp;?</h2>'
    . '<ul class="feature-list">' . $highlightList . '</ul>'
    . '</aside>'
    . '</section>'
    . '<section class="card stack inner-card">'
    . '<div class="section-header"><h2>Accès rapide aux modules</h2><span class="help">Navigation orientée productivité</span></div>'
    . '<div class="audience-grid">' . $moduleCards . '</div>'
    . '</section>';

echo render_layout($content, 'Accueil');
