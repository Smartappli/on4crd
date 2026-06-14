<?php
declare(strict_types=1);

function seed_modules(): void
{
    if (!table_exists('modules')) {
        return;
    }

    $modules = [
        ['dashboard', 'Tableau de bord', 'Personnalisation du dashboard', 0, 1, 'members', 10],
        ['members', 'Membres', 'Espace membres et profil', 0, 1, 'members', 20],
        ['webotheque', 'Webotheque', 'Liens et ressources web reserves aux membres', 0, 1, 'members', 29],
        ['presentations', 'Présentations', 'Supports et présentations réservés aux membres', 0, 1, 'members', 30],
        ['videos', 'Videos', 'Ressources vidéo réservées aux membres', 0, 1, 'members', 31],
        ['pv', 'Procès verbaux', 'Procès verbaux et comptes rendus réservés aux membres', 0, 1, 'members', 32],
        ['fichiers', 'Fichiers', 'Fichiers et ressources a telecharger', 0, 1, 'members', 33],
        ['news', 'Actualités', 'Section des actualités du club', 0, 1, 'public', 30],
        ['articles', 'Articles', 'Articles techniques', 0, 1, 'public', 40],
        ['wiki', 'Wiki', 'Base de connaissances collaborative', 0, 1, 'public', 50],
        ['albums', 'Albums', 'Galerie photos', 0, 1, 'public', 60],
        ['tools', 'Outils', 'Calculateurs et outils radioamateurs', 0, 1, 'public', 65],
        ['events', 'Événements', 'Agenda du club', 0, 1, 'public', 70],
        ['auctions', 'Enchères', 'Ventes aux enchères', 0, 1, 'public', 90],
        ['qsl', 'QSL', 'Gestion des cartes QSL', 0, 1, 'members', 100],
        ['chatbot', 'Raymond vous répond', 'Assistant conversationnel intégré au tableau de bord des membres', 0, 1, 'public', 110],
        ['advertising', 'Publicités', 'Gestion des annonces/publicités', 0, 1, 'public', 120],
        ['classifieds', 'Petites annonces', 'Module petites annonces', 0, 1, 'public', 121],
        ['press', 'Presse', 'Communiqués et contacts presse', 0, 1, 'public', 130],
        ['education', 'Éducation', 'Activités écoles/formation', 0, 1, 'public', 140],
        ['committee', 'Comité', 'Informations du comité', 0, 1, 'public', 150],
        ['directory', 'Annuaire', 'Annuaire public du club', 0, 1, 'public', 160],
        ['admin', 'Administration', 'Administration générale', 1, 1, 'admin', 1000],
    ];

    $hasVisibility = table_has_column('modules', 'visibility');
    if ($hasVisibility) {
        $stmt = db()->prepare(
            'INSERT INTO modules (code, label, description, is_core, is_enabled, visibility, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE label = VALUES(label), description = VALUES(description), is_core = VALUES(is_core), is_enabled = VALUES(is_enabled), visibility = VALUES(visibility), sort_order = VALUES(sort_order)'
        );
    } else {
        $stmt = db()->prepare(
            'INSERT INTO modules (code, label, description, is_core, is_enabled, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE label = VALUES(label), description = VALUES(description), is_core = VALUES(is_core), is_enabled = VALUES(is_enabled), sort_order = VALUES(sort_order)'
        );
    }

    foreach ($modules as $module) {
        if (!$hasVisibility) {
            unset($module[5]);
            $module = array_values($module);
        }
        $stmt->execute($module);
    }
}

function seed_dashboard_widgets(): void
{
    // Hook conservé pour compatibilité installateur.
}

function seed_news_sections(): void
{
    if (!table_exists('news_sections')) {
        return;
    }

    $sections = [
        ['on4crd', 'ON4CRD', 10],
        ['autre-club', 'Autre club', 20],
        ['contests', 'Contests', 30],
    ];

    $stmt = db()->prepare(
        'INSERT INTO news_sections (slug, name, sort_order)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order)'
    );
    foreach ($sections as $section) {
        $stmt->execute($section);
    }
}

function seed_brocante_crd_2026_news(): void
{
    if (!table_exists('news_posts') || !table_exists('news_sections')) {
        return;
    }

    $sectionStmt = db()->prepare('SELECT id FROM news_sections WHERE slug = ? LIMIT 1');
    $sectionStmt->execute(['on4crd']);
    $sectionId = (int) ($sectionStmt->fetchColumn() ?: 0);
    if ($sectionId <= 0) {
        return;
    }

    $slug = 'brocante-crd-20-06-2026';

    $title = 'Brocante CRD - samedi 20 juin 2026';
    $excerpt = 'Brocante CRD à Leuze Longchamps le samedi 20 juin 2026 dès 09h00, avec petit-déjeuner, pains saucisses, tables exposants et repas du soir sur réservation.';
    $content = <<<'HTML'
<figure class="news-event-poster">
    <img src="/assets/news/brocante-crd-2026.png" alt="Bourse Radio 20/06 - Club Radio Durnal">
</figure>
<div class="news-event-lead">
    <p><strong>À vos agendas !</strong> Brocante CRD, le samedi 20/06/2026 à partir de 09h00.</p>
    <p>Celle-ci se tiendra à Leuze Longchamps, entité d'Eghezée, route de La Bruyère 62, à côté de l'église.</p>
</div>
<div class="news-event-info-grid">
    <p><strong>Exposants</strong><span>Accueil à partir de 08h00. Le mètre de table est proposé à 2,- €.</span></p>
    <p><strong>Visiteurs</strong><span>Accueil à partir de 09h00.</span></p>
    <p><strong>Petit-déjeuner</strong><span>Café + croissant de bienvenue au prix de 2,- €.</span></p>
    <p><strong>Midi</strong><span>Pains saucisses proposés à un prix très démocratique.</span></p>
</div>
<h2>Réservations brocante</h2>
<p>Ne tardez pas à réserver votre ou vos table(s). Nous risquons aussi d'agrandir significativement l'espace d'exposition.</p>
<p><strong>Infos et réservations obligatoires brocante :</strong> <a href="mailto:on4dg@uba.be">on4dg@uba.be</a>.</p>
<p><strong>Réservation pains saucisses souhaitée :</strong> Bruno <a href="mailto:ON7ZB@uba.be">ON7ZB@uba.be</a>.</p>
<h2>Repas du soir</h2>
<p>En clôture de journée, à partir de 18h00, nous vous proposons un repas à prix d'ami pour la modique somme de <strong>25,- €</strong>.</p>
<ul class="news-event-menu">
    <li>Apéritif</li>
    <li>Assiette froide géante avec pas moins de 4 viandes différentes et ses accompagnements</li>
    <li>Café + dessert : Merveilleux ou Éclair</li>
</ul>
<div class="news-event-payment">
    <p><strong>Paiement anticipatif avant le 16/06</strong></p>
    <p>Versement sur le compte du CRD : <strong>BE82 9501 7301 2868</strong>.</p>
    <p>Mentionnez en communication votre indicatif, le nombre de repas souhaité et votre gourmandise de prédilection : code M pour Merveilleux ou code E pour Éclair au chocolat.</p>
    <p><strong>Réservations repas :</strong> Bruno <a href="mailto:on7zb@uba.be">on7zb@uba.be</a>.</p>
</div>
<p>À très bientôt pour vivre ensemble cet événement.</p>
<p>Pour le comité, Benoît ON4BEN.</p>
HTML;

    db()->prepare(
        'INSERT INTO news_posts (section_id, author_id, slug, title, excerpt, content, status, published_at)
         VALUES (?, NULL, ?, ?, ?, ?, "published", ?)'
        . ' ON DUPLICATE KEY UPDATE section_id = VALUES(section_id), title = VALUES(title), excerpt = VALUES(excerpt), content = VALUES(content), status = VALUES(status), published_at = COALESCE(published_at, VALUES(published_at))'
    )->execute([$sectionId, $slug, $title, $excerpt, $content, '2026-06-09 09:00:00']);

    if (function_exists('cache_forget')) {
        cache_forget('news_published_count_v1');
        cache_forget('news_categories_v2');
        cache_forget('news_archives_v1');
        cache_forget('home_latest_news_v1');
    }
}

function seed_published_club_event(
    string $slug,
    string $title,
    string $summary,
    string $description,
    string $startAt,
    string $endAt,
    string $location
): void {
    if (!table_exists('events')) {
        return;
    }

    db()->prepare(
        'INSERT INTO events (slug, title, summary, description, kind, start_at, end_at, location, external_url, status)
         VALUES (?, ?, ?, ?, "club", ?, ?, ?, NULL, "published")
         ON DUPLICATE KEY UPDATE title = VALUES(title), summary = VALUES(summary), description = VALUES(description), kind = VALUES(kind), start_at = VALUES(start_at), end_at = VALUES(end_at), location = VALUES(location), external_url = VALUES(external_url), status = VALUES(status)'
    )->execute([$slug, $title, $summary, $description, $startAt, $endAt, $location]);

    if (function_exists('cache_forget')) {
        cache_forget('home_next_event_v1');
    }
}

/**
 * @param list{string, string, string, string, string, string, string} $event
 */
function seed_published_club_event_row(array $event): void
{
    seed_published_club_event(...$event);
}

function seed_liberation_eghezee_2026_event(): void
{
    $slug = 'liberation-eghezee-82e-anniversaire-2026';
    $title = 'Libération d\'Eghezée';
    $summary = 'Participation au 82ème anniversaire de la Libération d\'Eghezée les 25, 26 et 27 septembre 2026.';
    $description = <<<'HTML'
<figure class="events-event-poster">
    <img src="/assets/events/liberation-eghezee-2026.png" alt="Libération d'Eghezée - 82ème anniversaire, 25, 26 et 27 septembre 2026">
</figure>
<p>Participation au 82ème anniversaire de la Libération d'Eghezée, organisé les 25, 26 et 27 septembre 2026.</p>
HTML;

    seed_published_club_event_row([
        $slug,
        $title,
        $summary,
        $description,
        '2026-09-25 00:00:00',
        '2026-09-27 23:59:00',
        'Eghezée',
    ]);

    if (function_exists('cache_forget')) {
        cache_forget('home_next_event_v1');
    }
}

function seed_brocante_souper_crd_2026_event(): void
{
    $slug = 'brocante-souper-crd-2026';
    $title = 'Brocante et souper du CRD';
    $summary = 'Brocante et souper du CRD le samedi 20 juin 2026 à Leuze Longchamps.';
    $description = <<<'HTML'
<figure class="events-event-poster">
    <img src="/assets/events/brocante-souper-crd-2026.png" alt="Brocante et souper du CRD - 20 juin 2026">
</figure>
<p>Brocante et souper du CRD le samedi 20 juin 2026 à Leuze Longchamps, route de La Bruyère 62.</p>
<p>Accueil exposants dès 08h00, visiteurs dès 09h00. Souper à partir de 18h00 sur réservation.</p>
HTML;

    seed_published_club_event_row([
        $slug,
        $title,
        $summary,
        $description,
        '2026-06-20 08:00:00',
        '2026-06-20 22:00:00',
        'Leuze Longchamps, route de La Bruyère 62',
    ]);

    if (function_exists('cache_forget')) {
        cache_forget('home_next_event_v1');
    }
}

function seed_fete_aischois_2026_event(): void
{
    $slug = 'fete-des-aischois-2026';
    $title = 'Fête des Aischois';
    $summary = 'Activation radio par Raymond (ON4DG) à la fête des Aischois le 8 août 2026, dès le matin jusque vers 17h00.';
    $description = <<<'HTML'
<figure class="events-event-poster events-event-poster-grid">
    <img src="/assets/events/fete-des-aischois-2026-1.png" alt="Installation ON4CRD à la fête des Aischois">
    <img src="/assets/events/fete-des-aischois-2026-2.png" alt="Antenne et caravane radio ON4CRD à la fête des Aischois">
</figure>
<p>Raymond (ON4DG) activera la fête des Aischois depuis le même emplacement que l'an dernier.</p>
<p>L'activation est prévue le samedi 8 août 2026, dès le matin jusque vers 17h00.</p>
HTML;

    seed_published_club_event_row([
        $slug,
        $title,
        $summary,
        $description,
        '2026-08-08 08:00:00',
        '2026-08-08 17:00:00',
        'Aische-en-Refail',
    ]);

    if (function_exists('cache_forget')) {
        cache_forget('home_next_event_v1');
    }
}

function seed_ad_placements(): void
{
    if (!table_exists('ad_placements')) {
        return;
    }

    $placements = [
        ['homepage_top', 'Accueil (haut)', 'Bannière en haut de la page d’accueil', 10],
        ['sidebar', 'Barre latérale', 'Emplacement encart latéral', 20],
        ['article_inline', 'Article (inline)', 'Annonce dans le contenu des articles', 30],
    ];

    $stmt = db()->prepare(
        'INSERT INTO ad_placements (code, name, description, sort_order, is_active)
         VALUES (?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), sort_order = VALUES(sort_order)'
    );
    foreach ($placements as $placement) {
        $stmt->execute($placement);
    }
}

function seed_live_feeds(): void
{
    if (!table_exists('live_feeds')) {
        return;
    }

    $feeds = [
        ['noaa-alerts', 'NOAA Alerts', 'https://services.swpc.noaa.gov/products/alerts.json', 'json', 120, 180, 1, 'Alertes météo spatiale NOAA'],
        ['open-meteo', 'Open-Meteo', 'https://api.open-meteo.com/v1/forecast?latitude=50.3150&longitude=4.9452&current=temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code&timezone=Europe%2FBrussels', 'json', 300, 300, 1, 'Météo locale via Open-Meteo (locator membre, fallback radio-club JO20LI)'],
        ['hamqth-dx', 'HamQTH DX', 'https://www.hamqth.com/dxc_csv.php?limit=12', 'csv', 300, 300, 1, 'Derniers spots DX'],
    ];

    $stmt = db()->prepare(
        'INSERT INTO live_feeds (code, label, url, parser, cache_ttl, refresh_seconds, is_enabled, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE label = VALUES(label), url = VALUES(url), parser = VALUES(parser), cache_ttl = VALUES(cache_ttl), refresh_seconds = VALUES(refresh_seconds), is_enabled = VALUES(is_enabled), notes = VALUES(notes)'
    );
    foreach ($feeds as $feed) {
        $stmt->execute($feed);
    }
}

function ensure_directories(): void
{
    $directories = [
        dirname(__DIR__) . '/storage/cache/data',
        dirname(__DIR__) . '/storage/auth',
        dirname(__DIR__) . '/storage/uploads/albums',
        dirname(__DIR__) . '/storage/uploads/ads',
        dirname(__DIR__) . '/storage/uploads/members',
        dirname(__DIR__) . '/storage/uploads/members/avatars',
        dirname(__DIR__) . '/storage/press',
    ];

    foreach ($directories as $directory) {
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de créer un dossier requis: ' . $directory);
        }
    }
}

function runtime_schema_version(): string
{
    return '2026-06-11.4';
}

function runtime_schema_marker_path(): string
{
    $directory = dirname(__DIR__) . '/storage/cache';
    if (!is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }

    return $directory . '/runtime-schema.version';
}

function apply_runtime_schema_updates_if_needed(?string $markerPath = null): bool
{
    $markerPath ??= runtime_schema_marker_path();
    $expectedVersion = runtime_schema_version();
    $currentVersion = is_file($markerPath) ? trim((string) @file_get_contents($markerPath)) : '';
    if ($currentVersion !== '' && hash_equals($expectedVersion, $currentVersion)) {
        return false;
    }

    $lockPath = $markerPath . '.lock';
    $lockDirectory = dirname($lockPath);
    if (!is_dir($lockDirectory)) {
        @mkdir($lockDirectory, 0775, true);
    }

    $lockHandle = @fopen($lockPath, 'c');
    if ($lockHandle === false) {
        require_once __DIR__ . '/runtime_schema_updates.php';
        apply_runtime_schema_updates();
        @file_put_contents($markerPath, $expectedVersion, LOCK_EX);
        return true;
    }

    try {
        flock($lockHandle, LOCK_EX);
        $currentVersion = is_file($markerPath) ? trim((string) @file_get_contents($markerPath)) : '';
        if ($currentVersion !== '' && hash_equals($expectedVersion, $currentVersion)) {
            return false;
        }

        require_once __DIR__ . '/runtime_schema_updates.php';
        apply_runtime_schema_updates();
        @file_put_contents($markerPath, $expectedVersion, LOCK_EX);

        return true;
    } finally {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}
