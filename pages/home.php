<?php
declare(strict_types=1);

$user = current_user();
$isAuthenticated = $user !== null;

$primaryCta = $isAuthenticated
    ? '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('dashboard')) . '">Accéder à mon espace membre</a>'
    : '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('login')) . '">Rejoindre le club</a>';

$secondaryCta = '<a class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('events')) . '">Consulter l’agenda</a>';
$tertiaryCta = '<a class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-5 py-3 text-sm font-semibold text-blue-700 transition hover:bg-blue-100" href="' . e(route_url('events')) . '">Prochains événements</a>';

$moduleCatalog = [
    ['code' => 'news', 'route' => 'news', 'title' => 'Actualités', 'desc' => 'Suivez les annonces officielles, les comptes rendus et les temps forts du club.', 'icon' => '📰', 'audience' => 'Visiteurs & membres'],
    ['code' => 'events', 'route' => 'events', 'title' => 'Événements', 'desc' => 'Repérez les ateliers, sorties terrain, activations et réunions à venir.', 'icon' => '📅', 'audience' => 'Ouvert à tous'],
    ['code' => 'articles', 'route' => 'articles', 'title' => 'Articles techniques', 'desc' => 'Approfondissez vos connaissances avec des contenus pratiques et pédagogiques.', 'icon' => '🧠', 'audience' => 'Membres passionnés'],
    ['code' => 'wiki', 'route' => 'wiki', 'title' => 'Wiki du club', 'desc' => 'Consultez les procédures, fiches matériel et bonnes pratiques radioamateur.', 'icon' => '📚', 'audience' => 'Contributeurs & opérateurs'],
    ['code' => 'albums', 'route' => 'albums', 'title' => 'Galerie photo', 'desc' => 'Revivez les activités du club avec des albums thématiques régulièrement enrichis.', 'icon' => '📸', 'audience' => 'Visiteurs & membres'],
    ['code' => 'directory', 'route' => 'directory', 'title' => 'Annuaire', 'desc' => 'Identifiez rapidement les compétences, spécialités et profils de la communauté.', 'icon' => '👥', 'audience' => 'Membres'],
    ['code' => 'shop', 'route' => 'shop', 'title' => 'Boutique', 'desc' => 'Accédez aux ressources et supports utiles à la vie du club.', 'icon' => '🛍️', 'audience' => 'Membres'],
    ['code' => 'qsl', 'route' => 'qsl', 'title' => 'Espace QSL', 'desc' => 'Préparez, prévisualisez et exportez vos cartes QSL depuis un espace dédié.', 'icon' => '📨', 'audience' => 'Opérateurs actifs'],
    ['code' => 'auctions', 'route' => 'auctions', 'title' => 'Enchères', 'desc' => 'Donnez une seconde vie au matériel radio via les ventes entre membres.', 'icon' => '🔧', 'audience' => 'Membres'],
    ['code' => 'press', 'route' => 'press', 'title' => 'Presse', 'desc' => 'Retrouvez les communiqués et les informations institutionnelles du club.', 'icon' => '🎙️', 'audience' => 'Partenaires & médias'],
    ['code' => 'education', 'route' => 'schools', 'title' => 'Éducation', 'desc' => 'Découvrez les actions de sensibilisation et les initiatives pédagogiques locales.', 'icon' => '🎓', 'audience' => 'Écoles & grand public'],
];

$activeModules = [];
$moduleCards = '';
foreach ($moduleCatalog as $module) {
    if (!module_enabled((string) $module['code'])) {
        continue;
    }

    $activeModules[] = $module;
    $moduleCards .= '<a class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url((string) $module['route'])) . '">'
        . '<div class="flex items-center justify-between gap-3">'
        . '<h3 class="text-lg font-semibold text-slate-900">' . e((string) $module['title']) . '</h3>'
        . '<span class="text-xl" aria-hidden="true">' . e((string) $module['icon']) . '</span>'
        . '</div>'
        . '<p class="mt-2 text-sm text-slate-600">' . e((string) $module['desc']) . '</p>'
        . '<div class="mt-4 flex items-center justify-between">'
        . '<span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600">' . e((string) $module['audience']) . '</span>'
        . '<span class="text-sm font-semibold text-blue-600 group-hover:text-blue-700">Ouvrir →</span>'
        . '</div>'
        . '</a>';
}

if ($moduleCards === '') {
    $moduleCards = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">Les espaces publics sont en cours de mise à jour.</div>';
}

$heroTitle = $isAuthenticated
    ? 'Pilotez vos activités radio depuis un portail unique'
    : '';

$heroSubtitle = $isAuthenticated
    ? 'Retrouvez vos modules, vos contenus et vos prochaines actions en quelques clics.'
    : '';

$heroIntro = '';
if ($heroTitle !== '') {
    $heroIntro .= '<h1 class="mt-4 max-w-2xl text-4xl font-extrabold leading-tight text-slate-900 lg:text-5xl">' . e($heroTitle) . '</h1>';
}
if ($heroSubtitle !== '') {
    $heroIntro .= '<p class="mt-4 max-w-2xl text-base text-slate-600">' . e($heroSubtitle) . '</p>';
}

$moduleCount = count($activeModules);

$content = '<section class="grid gap-4 lg:grid-cols-[1.55fr_.95fr]">'
    . '<article class="flex h-full flex-col rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-white to-blue-50 p-8 shadow-sm">'
    . '<span class="inline-flex rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">Connecter, apprendre, transmettre</span>'
    . $heroIntro
    . '<div class="mt-auto pt-8 flex flex-wrap gap-3">' . $primaryCta . $secondaryCta . $tertiaryCta . '</div>'
    . '</article>'
    . '<aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<h2 class="text-xl font-bold text-slate-900">Informations utiles</h2>'
    . '<div class="mt-4 grid gap-3">'
    . '<article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><span class="text-sm text-slate-600">Nos réunions se déroulent le 3ième samedi du mois à partir de 14h.</span></article>'
    . '<article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><span class="text-sm text-slate-600">Adresse : Bocq Arena, Rue des Écoles, 5530 Purnode</span></article>'
    . '<article class="rounded-xl border border-slate-200 bg-slate-50 p-4">'
    . '<p class="text-sm text-slate-600">Pour le payement des cotisations, vos versements par sympathie, vos dons éventuels, et pour le sponsoring</p>'
    . '<p class="mt-2 text-sm text-slate-600"><strong>Nom:</strong> CRD / Alexis GREGOIRE Trésorier<br><strong>Adresse de contact :</strong> crdurnal@gmail.com<br><strong>Compte bancaire :</strong> BE82 9501 7301 2868</p>'
    . '<p class="mt-2 text-sm text-slate-600">Veuillez indiquer, en communication libre :</p>'
    . '<ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-600"><li>La raison de votre virement (Cotisation, par sympathie, don)</li><li>Votre nom et votre prénom et/ou indicatif</li><li>Votre raison sociale (pour les sponsors)</li></ul>'
    . '</article>'
    . '</div>'
    . '</aside>'
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<header class="mb-4">'
    . '<h2 class="text-2xl font-bold text-slate-900">Modules ON4CRD : utilité immédiate, impact concret</h2>'
    . '<p class="mt-1 text-slate-600">Chaque rubrique répond à un besoin précis : informer, former, coordonner et valoriser les activités du club.</p>'
    . '</header>'
    . '<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">' . $moduleCards . '</div>'
    . '</section>'
    . '<section class="mt-4 grid gap-4 md:grid-cols-2">'
    . '<article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<h3 class="text-xl font-bold text-slate-900">Vous êtes journaliste</h3>'
    . '<p class="mt-3 text-sm text-slate-600">Accédez directement à notre dossier de presse pour préparer vos publications et reportages.</p>'
    . '<a class="mt-4 inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('press')) . '">Consulter le dossier presse</a>'
    . '</article>'
    . '<article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<h3 class="text-xl font-bold text-slate-900">Vous êtes enseignant</h3>'
    . '<p class="mt-3 text-sm text-slate-600">Retrouvez nos dossiers pédagogiques pour vos activités scolaires et vos projets éducatifs.</p>'
    . '<a class="mt-4 inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('schools')) . '">Voir les dossiers pédagogiques</a>'
    . '</article>'
    . '</section>'
    . '<section class="mt-4 grid gap-4 rounded-3xl border border-blue-200 bg-gradient-to-r from-blue-50 to-white p-6 shadow-sm lg:grid-cols-[1.8fr_1fr] lg:items-center">'
    . '<div><h2 class="text-2xl font-extrabold text-slate-900">Prêt à rejoindre une communauté radio active et structurée ?</h2><p class="mt-2 text-slate-600">La nouvelle page d’accueil met en évidence les modules clés pour trouver rapidement l’information utile et participer aux projets ON4CRD.</p></div>'
    . '<div class="grid gap-2">' . $primaryCta . '<a class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('news')) . '">Lire les actualités</a></div>'
    . '</section>';

echo render_layout($content, 'Accueil');
