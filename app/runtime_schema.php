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
        ['news', 'Actualités', 'Section des actualités du club', 0, 1, 'public', 30],
        ['articles', 'Articles', 'Articles techniques', 0, 1, 'public', 40],
        ['wiki', 'Wiki', 'Base de connaissances collaborative', 0, 1, 'public', 50],
        ['albums', 'Albums', 'Galerie photos', 0, 1, 'public', 60],
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
    return '2026-06-03.2';
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
