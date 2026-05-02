<?php
declare(strict_types=1);

/** @var string $homeLocale */
$homeLocale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
$homeMessages = [
    'fr' => [
        'quote_day' => 'Citation du jour',
        'ham_weather' => 'Météo radioamateur',
        'ham_weather_desc' => 'Recommandations calculées automatiquement selon votre localisation, l\'heure, la météo et la propagation pour identifier les bandes et modes les plus propices aux QSO.',
        'quote_fallback' => 'Chaque contact radio est une nouvelle aventure.',
    ],
    'en' => [
        'quote_day' => 'Quote of the day',
        'ham_weather' => 'Ham radio weather',
        'ham_weather_desc' => 'Recommendations are computed automatically from your location, time of day, weather and propagation to identify the most favorable bands and modes for QSO.',
        'quote_fallback' => 'Every radio contact is a new adventure.',
    ],
    'de' => [
        'quote_day' => 'Zitat des Tages',
        'ham_weather' => 'Funkwetter',
        'ham_weather_desc' => 'Die Empfehlungen werden automatisch aus Standort, Tageszeit, Wetter und Ausbreitung berechnet, um die besten Bänder und Betriebsarten für QSOs zu ermitteln.',
        'quote_fallback' => 'Jeder Funkkontakt ist ein neues Abenteuer.',
    ],
    'nl' => [
        'quote_day' => 'Quote van de dag',
        'ham_weather' => 'Zendweer voor radioamateurs',
        'ham_weather_desc' => 'Aanbevelingen worden automatisch berekend op basis van uw locatie, tijdstip, weer en propagatie om de beste banden en modes voor QSO te bepalen.',
        'quote_fallback' => 'Elk radiocontact is een nieuw avontuur.',
    ],
];
$homeI18n = $homeMessages[$homeLocale] ?? $homeMessages['fr'];

$user = current_user();
$isAuthenticated = $user !== null;

$primaryCta = $isAuthenticated
    ? '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('dashboard')) . '">Accéder à mon espace membre</a>'
    : '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('membership')) . '">Rejoindre le club</a>';
$newsletterCta = '<a class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-5 py-3 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50" href="' . e(route_url('newsletter_public')) . '">S\'inscrire à la newsletter</a>';


$moduleCatalog = [
    ['code' => 'dashboard', 'route' => 'dashboard', 'title' => 'Tableau de bord personnalisable', 'desc' => 'Configurez votre espace membre avec des widgets à afficher, organiser et adapter selon vos besoins.', 'icon' => '🧩', 'audience' => 'Membres connectés'],
    ['code' => 'articles', 'route' => 'articles', 'title' => 'Articles techniques', 'desc' => 'Approfondissez vos connaissances avec des contenus pratiques et pédagogiques.', 'icon' => '🧠', 'audience' => 'Membres passionnés'],
    ['code' => 'wiki', 'route' => 'wiki', 'title' => 'Wiki du club', 'desc' => 'Consultez les procédures, fiches matériel et bonnes pratiques radioamateur.', 'icon' => '📚', 'audience' => 'Contributeurs & opérateurs'],
    ['code' => 'albums', 'route' => 'albums', 'title' => 'Galerie photo', 'desc' => 'Revivez les activités du club avec des albums thématiques régulièrement enrichis.', 'icon' => '📸', 'audience' => 'Visiteurs & membres'],
    ['code' => 'qsl', 'route' => 'qsl', 'title' => 'Espace QSL', 'desc' => 'Préparez, prévisualisez et exportez vos cartes QSL depuis un espace dédié.', 'icon' => '📨', 'audience' => 'Opérateurs actifs'],
    ['code' => 'auctions', 'route' => 'auctions', 'title' => 'Enchères', 'desc' => 'Donnez une seconde vie au matériel radio via les ventes entre membres.', 'icon' => '🔧', 'audience' => 'Membres'],
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
$heroImageCandidates = glob(__DIR__ . '/../assets/img/*.{png,jpg,jpeg,webp,gif,avif}', GLOB_BRACE) ?: [];
if ($heroImageCandidates !== []) {
    $heroBackgroundUrl = asset_url('assets/img/' . basename((string) $heroImageCandidates[array_rand($heroImageCandidates)]));
}

$latestNews = null;
$nextEvent = null;
$featuredAd = null;

try {
    if (table_exists('news_posts')) {
        $latestNews = db()->query('SELECT slug, title, excerpt, published_at, updated_at FROM news_posts WHERE status = "published" ORDER BY COALESCE(published_at, updated_at) DESC LIMIT 1')->fetch();
    }

    if (table_exists('events')) {
        $stmt = db()->prepare('SELECT slug, title, summary, start_at, location FROM events WHERE status = "published" AND end_at >= NOW() ORDER BY start_at ASC LIMIT 1');
        $stmt->execute();
        $nextEvent = $stmt->fetch();
    }

    if (module_enabled('advertising') && table_exists('ads')) {
        $featuredAd = db()->query('SELECT title, description, image_path, target_url FROM ads WHERE status = "active" ORDER BY updated_at DESC LIMIT 1')->fetch();
    }
} catch (Throwable) {
    // Les blocs "À la une" restent en mode fallback si la base n'est pas disponible.
}

$latestNewsHtml = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">Aucune actualité publiée pour le moment.</div>';
if (is_array($latestNews) && !empty($latestNews['slug'])) {
    $newsDate = !empty($latestNews['published_at']) ? date('d/m/Y', strtotime((string) $latestNews['published_at'])) : date('d/m/Y', strtotime((string) ($latestNews['updated_at'] ?? 'now')));
    $newsExcerpt = trim((string) ($latestNews['excerpt'] ?? ''));
    if ($newsExcerpt === '') {
        $newsExcerpt = 'Consultez la dernière publication du club.';
    }

    $latestNewsHtml = '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('news_view', ['slug' => (string) $latestNews['slug']])) . '">'
        . '<p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Publié le ' . e($newsDate) . '</p>'
        . '<h3 class="mt-2 text-lg font-bold text-slate-900 group-hover:text-blue-700">' . e((string) $latestNews['title']) . '</h3>'
        . '<p class="mt-2 text-sm text-slate-600">' . e($newsExcerpt) . '</p>'
        . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">Lire l’actualité →</span>'
        . '</a>';
}

$nextEventHtml = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">Aucun évènement planifié actuellement.</div>';
if (is_array($nextEvent) && !empty($nextEvent['slug'])) {
    $eventDate = !empty($nextEvent['start_at']) ? date('d/m/Y H:i', strtotime((string) $nextEvent['start_at'])) : 'Date à confirmer';
    $eventSummary = trim((string) ($nextEvent['summary'] ?? ''));
    if ($eventSummary === '') {
        $eventSummary = 'Découvrez les détails du prochain rendez-vous du club.';
    }
    $eventLocation = trim((string) ($nextEvent['location'] ?? ''));

    $nextEventHtml = '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('event_view', ['slug' => (string) $nextEvent['slug']])) . '">'
        . '<p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Prochaine date · ' . e($eventDate) . '</p>'
        . '<h3 class="mt-2 text-lg font-bold text-slate-900 group-hover:text-blue-700">' . e((string) $nextEvent['title']) . '</h3>'
        . '<p class="mt-2 text-sm text-slate-600">' . e($eventSummary) . '</p>';

    if ($eventLocation !== '') {
        $nextEventHtml .= '<p class="mt-2 text-xs font-medium text-slate-500">Lieu : ' . e($eventLocation) . '</p>';
    }

    $nextEventHtml .= '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">Voir l’évènement →</span>'
        . '</a>';
}

$adSlotHtml = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500"><p class="mt-2">Aucune publicité partenaire disponible pour le moment.</p></div>';
$localAdCandidates = glob(__DIR__ . '/../assets/pub/*.{png,jpg,jpeg,webp,gif,avif}', GLOB_BRACE) ?: [];
if ($localAdCandidates !== []) {
    $localAdPath = 'assets/pub/' . basename((string) $localAdCandidates[array_rand($localAdCandidates)]);
    $adSlotHtml = '<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">'
        . '<div class="overflow-hidden rounded-lg aspect-square w-full">'
        . '<img class="h-full w-full object-cover" src="' . e(asset_url($localAdPath)) . '" alt="Publicité partenaire" loading="lazy" decoding="async">'
        . '</div>'
        . '</div>';
}
if (is_array($featuredAd) && !empty($featuredAd['title'])) {
    $adTarget = trim((string) ($featuredAd['target_url'] ?? ''));
    $adDescription = trim((string) ($featuredAd['description'] ?? ''));
    $adImage = trim((string) ($featuredAd['image_path'] ?? ''));

    $adInner = '<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">'
        . '<p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Annonce partenaire</p>'
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

$ubaLogoPath = 'assets/logo/UBA-Logo-Couleur-MID2.png';
$relaisLogoPath = 'assets/logo/CRD-Echolink.jpg';
$homeWeatherHtml = render_widget('open_meteo');
$homePropagationHtml = render_widget('propagation');
$homeHamAdviceHtml = render_ham_weather_advice(current_user() ?? []);
$homeRadioInfoHtml = '<div class="grid gap-4">'
    . '<section>'
    . '<h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Informations radioamateur</h4>'
    . '<ul class="mt-2 list-clean">'
    . '<li><strong>Phonie VHF :</strong> 145.500 MHz (appel simplex régional)</li>'
    . '<li><strong>QRG CW QRP :</strong> 7.030 MHz • 14.060 MHz</li>'
    . '<li><strong>Bon réflexe :</strong> annoncer indicatif + QTH + trafic recherché</li>'
    . '</ul>'
    . '</section>'
    . '</div>';

$homeQuote = random_quote_for_layout();
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

$content = '<section class="mb-4 grid gap-4 lg:grid-cols-2">'
    . '<article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" aria-label="Citation mise à l\'honneur">'
    . '<h2 class="text-xl font-bold text-slate-900">' . e((string) $homeI18n['quote_day']) . '</h2>'
    . '<blockquote class="mt-3 border-l-4 border-blue-200 pl-4 text-base italic text-slate-700">“' . e($homeQuoteText) . '”</blockquote>'
    . ($homeQuoteAuthor !== '' ? '<p class="mt-3 text-sm font-semibold text-slate-500">— ' . e($homeQuoteAuthor) . '</p>' : '')
    . '</article>'
    . '</section>'
    . '<section class="grid gap-4 lg:grid-cols-[1.55fr_.95fr]">'
    . '<article class="relative isolate flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200 p-8 shadow-sm">'
    . '<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="' . e($heroBackgroundUrl) . '" alt="Illustration ON4CRD" loading="eager" decoding="async">'
    . '<span class="hidden rounded-full bg-blue-600 px-3 py-1 text-[1.1rem] font-semibold uppercase tracking-wide text-white sm:inline-flex">ON4CRD · Connecter, expérimenter, partager</span>'
    . $heroIntro
    . '<div class="mt-auto pt-8 grid max-w-sm gap-2">' . $primaryCta . $newsletterCta . '</div>'
    . '</article>'
    . '<div class="grid gap-4">'
    . '<aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" aria-label="Météo radioamateur">'
    . '<h2 class="text-xl font-bold text-slate-900">' . e((string) $homeI18n['ham_weather']) . '</h2>'
    . '<div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">' . $homeHamAdviceHtml . '</div>'
    . '</aside>'
    . '<aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<h2 class="text-xl font-bold text-slate-900">Informations utiles</h2>'
    . '<div class="mt-4 grid gap-3">'
    . '<article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><span class="text-sm text-slate-600">Nos réunions se déroulent le 3ième samedi du mois à partir de 14h.</span></article>'
    . '<article class="rounded-xl border border-slate-200 bg-slate-50 p-4">'
    . '<p class="mt-2 text-sm text-slate-600">Bocq Arena, rue des Écoles, 5530 Purnode</p>'
    . '<div class="mt-3 overflow-hidden rounded-lg border border-slate-200">'
    . '<iframe class="h-56 w-full" title="Carte Google Map - Radio Club Durnal" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q=50%C2%B018%2754.1%22N+4%C2%B056%2742.7%22E&output=embed"></iframe>'
    . '</div>'
    . '<a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="https://www.google.com/maps?q=50%C2%B018%2754.1%22N+4%C2%B056%2742.7%22E" target="_blank" rel="noopener noreferrer">Itinéraire Google Maps</a>'
    . '</article>'
    . '</div>'
    . '</aside>'
    . '</div>'
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="grid gap-4 md:grid-cols-2">'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-900">Union royale belge des amateurs-émetteurs a.s.b.l.</h3><p class="mt-2 text-sm text-slate-600">Le Radio Club Durnal est affilié à l\'Union Belge des Amateurs-Émetteurs.</p><a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="https://www.uba.be" target="_blank" rel="noopener noreferrer">Visiter le site de l\'UBA</a></div><img class="h-20 w-auto object-contain" src="' . e(asset_url($ubaLogoPath)) . '" alt="Logo UBA" loading="lazy" decoding="async"></div></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-900">Notre relais</h3><p class="mt-2 text-sm text-slate-600">Retrouvez les informations essentielles concernant notre relai et ses paramètres.</p><a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('relais')) . '">Consulter les informations du relais</a></div><img class="h-20 w-auto object-contain" src="' . e(asset_url($relaisLogoPath)) . '" alt="Logo du relais" loading="lazy" decoding="async"></div></article>'
    . '</div>'
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<header class="mb-4">'
    . '<h2 class="text-2xl font-bold text-slate-900">À la une du club</h2>'
    . '</header>'
    . '<div class="grid gap-4 lg:grid-cols-3">'
    . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Dernière actualité</h3>' . $latestNewsHtml . '</article>'
    . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Prochain évènement</h3>' . $nextEventHtml . '</article>'
    . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Publicité</h3>' . $adSlotHtml . '</article>'
    . '</div>'
    . '<div class="mt-4 grid gap-4 lg:grid-cols-3">'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Météo</h3>' . $homeWeatherHtml . '</article>'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Propagation</h3>' . $homePropagationHtml . '</article>'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Repères radioamateur</h3>' . $homeRadioInfoHtml . '</article>'
    . '</div>'
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<header class="mb-4">'
    . '<h2 class="text-2xl font-bold text-slate-900">Modules réservés aux membres</h2>'
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
    . '<div class="grid gap-2">' . $primaryCta . $newsletterCta . '</div>'
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="grid gap-6 lg:grid-cols-3">'
    . '<article><h3 class="text-lg font-bold text-slate-900">Adresse</h3><p class="mt-3 text-sm text-slate-700">Radio Club Durnal</p><p class="text-sm text-slate-700">Bocq Arena</p><p class="text-sm text-slate-700">Rue des Écoles</p><p class="text-sm text-slate-700">5530 Purnode</p><p class="mt-4 text-lg font-bold text-slate-900">Personnes de contact</p><p class="text-sm text-slate-700">ON4BEN : +32 496 260 865</p><p class="text-sm text-slate-700">ON4DG : +32 478 789 193</p></article>'
    . '<article><h3 class="text-lg font-bold text-slate-900">Nous contacter</h3><form class="mt-3 grid gap-2" method="post" action="' . e(route_url('footer_contact')) . '"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="return_route" value="home"><label for="home-contact-name" class="sr-only">Nom</label><input id="home-contact-name" type="text" name="name" placeholder="Nom" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><label for="home-contact-email" class="sr-only">Email</label><input id="home-contact-email" type="email" name="email" placeholder="Email" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><label for="home-contact-message" class="sr-only">Message</label><textarea id="home-contact-message" name="message" placeholder="Message" rows="3" maxlength="2000" data-wysiwyg="off" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea><button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Envoyer</button></form></article>'
    . '<article><h3 class="text-lg font-bold text-slate-900">Informations importantes</h3><ul class="mt-3 list-inside list-disc space-y-1 text-sm text-slate-700"><li><a class="hover:underline" href="' . e(route_url('conditions_utilisation')) . '">Conditions générales d\'utilisation</a></li><li><a class="hover:underline" href="' . e(route_url('mentions_legales')) . '">Mentions légales</a></li><li><a class="hover:underline" href="' . e(route_url('reglement_interieur')) . '">Règlement d\'ordre intérieur</a></li><li><a class="hover:underline" href="' . e(route_url('membership')) . '">Faire un don</a></li><li><a class="hover:underline" href="' . e(route_url('sponsoring')) . '">Sponsoring</a></li></ul></article>'
    . '</div>'
    . '</section>';

echo render_layout($content, 'Accueil');
