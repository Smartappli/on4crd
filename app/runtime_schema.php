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
    return '2026-06-02.1';
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

        apply_runtime_schema_updates();
        @file_put_contents($markerPath, $expectedVersion, LOCK_EX);

        return true;
    } finally {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

function apply_runtime_schema_updates(): void
{
    require_once __DIR__ . '/content_helpers.php';
    require_once __DIR__ . '/member_content.php';
    require_once __DIR__ . '/notifications.php';

    if (!table_exists('users')) {
        db()->exec(
            'CREATE TABLE users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(249) NOT NULL,
                password VARCHAR(255) NOT NULL,
                username VARCHAR(100) DEFAULT NULL,
                status TINYINT UNSIGNED NOT NULL DEFAULT 0,
                verified TINYINT UNSIGNED NOT NULL DEFAULT 0,
                resettable TINYINT UNSIGNED NOT NULL DEFAULT 1,
                roles_mask INT UNSIGNED NOT NULL DEFAULT 0,
                registered INT UNSIGNED NOT NULL,
                last_login INT UNSIGNED DEFAULT NULL,
                force_logout MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
                UNIQUE KEY users_email_unique (email),
                UNIQUE KEY users_username_unique (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_2fa (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            mechanism TINYINT UNSIGNED NOT NULL,
            seed VARCHAR(255) DEFAULT NULL,
            created_at INT UNSIGNED NOT NULL,
            expires_at INT UNSIGNED DEFAULT NULL,
            UNIQUE KEY users_2fa_user_id_mechanism_unique (user_id, mechanism),
            CONSTRAINT users_2fa_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_audit_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED DEFAULT NULL,
            event_at INT UNSIGNED NOT NULL,
            event_type VARCHAR(128) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
            admin_id INT UNSIGNED DEFAULT NULL,
            ip_address VARCHAR(49) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            details_json TEXT DEFAULT NULL,
            KEY users_audit_log_event_at_index (event_at),
            KEY users_audit_log_user_id_event_at_index (user_id, event_at),
            KEY users_audit_log_user_id_event_type_event_at_index (user_id, event_type, event_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_confirmations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            email VARCHAR(249) NOT NULL,
            selector VARCHAR(16) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires INT UNSIGNED NOT NULL,
            UNIQUE KEY users_confirmations_selector_unique (selector),
            KEY users_confirmations_email_expires_index (email, expires),
            KEY users_confirmations_user_id_index (user_id),
            CONSTRAINT users_confirmations_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_remembered (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user INT UNSIGNED NOT NULL,
            selector VARCHAR(24) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires INT UNSIGNED NOT NULL,
            UNIQUE KEY users_remembered_selector_unique (selector),
            KEY users_remembered_user_index (user),
            CONSTRAINT users_remembered_user_foreign FOREIGN KEY (user) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_otps (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            mechanism TINYINT UNSIGNED NOT NULL,
            single_factor TINYINT UNSIGNED NOT NULL DEFAULT 0,
            selector VARCHAR(24) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at INT UNSIGNED DEFAULT NULL,
            KEY users_otps_user_id_mechanism_index (user_id, mechanism),
            KEY users_otps_selector_user_id_index (selector, user_id),
            CONSTRAINT users_otps_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_resets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user INT UNSIGNED NOT NULL,
            selector VARCHAR(20) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires INT UNSIGNED NOT NULL,
            UNIQUE KEY users_resets_selector_unique (selector),
            KEY users_resets_user_index (user),
            KEY users_resets_user_expires_index (user, expires),
            CONSTRAINT users_resets_user_foreign FOREIGN KEY (user) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS users_throttling (
            bucket VARCHAR(44) NOT NULL,
            tokens FLOAT UNSIGNED NOT NULL,
            replenished_at INT UNSIGNED NOT NULL,
            expires_at INT UNSIGNED NOT NULL,
            PRIMARY KEY (bucket),
            KEY users_throttling_expires_at_index (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (!table_has_index('users_confirmations', 'users_confirmations_email_expires_index')) {
        db()->exec('ALTER TABLE users_confirmations ADD INDEX users_confirmations_email_expires_index (email, expires)');
    }
    if (!table_has_index('users_resets', 'users_resets_user_expires_index')) {
        db()->exec('ALTER TABLE users_resets ADD INDEX users_resets_user_expires_index (user, expires)');
    }
    if (!table_has_index('users_throttling', 'users_throttling_expires_at_index')) {
        db()->exec('ALTER TABLE users_throttling ADD INDEX users_throttling_expires_at_index (expires_at)');
    }

    if (table_exists('articles')) {
        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnStmt->execute(['articles', 'category']);
        $hasCategory = (int) $columnStmt->fetchColumn() > 0;
        if (!$hasCategory) {
            db()->exec('ALTER TABLE articles ADD COLUMN category VARCHAR(120) NOT NULL DEFAULT "autres" AFTER status');
        }

        $columnStmt->execute(['articles', 'scheduled_at']);
        if ((int) $columnStmt->fetchColumn() === 0) {
            db()->exec('ALTER TABLE articles ADD COLUMN scheduled_at DATETIME NULL DEFAULT NULL AFTER status');
        }

        $columnStmt->execute(['articles', 'published_at']);
        if ((int) $columnStmt->fetchColumn() === 0) {
            db()->exec('ALTER TABLE articles ADD COLUMN published_at DATETIME NULL DEFAULT NULL AFTER scheduled_at');
        }

        db()->exec('ALTER TABLE articles MODIFY COLUMN status ENUM("draft","scheduled","published") NOT NULL DEFAULT "draft"');

        db()->exec(
            'CREATE TABLE IF NOT EXISTS article_revisions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                article_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                excerpt TEXT NULL,
                content LONGTEXT NULL,
                status VARCHAR(32) NOT NULL DEFAULT "draft",
                category VARCHAR(120) NOT NULL DEFAULT "autres",
                scheduled_at DATETIME NULL DEFAULT NULL,
                published_at DATETIME NULL DEFAULT NULL,
                author_id INT NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_article_revision_article_created (article_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }



    db()->exec(
        'CREATE TABLE IF NOT EXISTS dashboard_widget_settings (
            widget_key VARCHAR(120) PRIMARY KEY,
            is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (table_exists('dashboard_widgets')) {
        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnStmt->execute(['dashboard_widgets', 'config_json']);
        $hasConfigJson = (int) $columnStmt->fetchColumn() > 0;
        if (!$hasConfigJson) {
            db()->exec('ALTER TABLE dashboard_widgets ADD COLUMN config_json LONGTEXT DEFAULT NULL AFTER widget_key');
        }
    }

    if (table_exists('album_photos')) {
        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnStmt->execute(['album_photos', 'sort_order']);
        if ((int) $columnStmt->fetchColumn() === 0) {
            db()->exec('ALTER TABLE album_photos ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER album_id');
            db()->exec('UPDATE album_photos SET sort_order = id WHERE sort_order = 0');
        }
    }

    if (table_exists('members')) {
        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $requiredColumns = [
            'auth_user_id' => 'ALTER TABLE members ADD COLUMN auth_user_id INT UNSIGNED DEFAULT NULL UNIQUE',
            'country' => 'ALTER TABLE members ADD COLUMN country VARCHAR(190) DEFAULT NULL',
            'is_uba_member' => 'ALTER TABLE members ADD COLUMN is_uba_member TINYINT(1) NOT NULL DEFAULT 0',
            'uba_member_number' => 'ALTER TABLE members ADD COLUMN uba_member_number VARCHAR(64) DEFAULT NULL',
            'visibility_full_name' => 'ALTER TABLE members ADD COLUMN visibility_full_name ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_country' => 'ALTER TABLE members ADD COLUMN visibility_country ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_locator' => 'ALTER TABLE members ADD COLUMN visibility_locator ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_bio' => 'ALTER TABLE members ADD COLUMN visibility_bio ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_licence_class' => 'ALTER TABLE members ADD COLUMN visibility_licence_class ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_qsl' => 'ALTER TABLE members ADD COLUMN visibility_qsl ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_qrz' => 'ALTER TABLE members ADD COLUMN visibility_qrz ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_uba' => 'ALTER TABLE members ADD COLUMN visibility_uba ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_favourite_bands' => 'ALTER TABLE members ADD COLUMN visibility_favourite_bands ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_favourite_modes' => 'ALTER TABLE members ADD COLUMN visibility_favourite_modes ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_antennas' => 'ALTER TABLE members ADD COLUMN visibility_antennas ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_interests' => 'ALTER TABLE members ADD COLUMN visibility_interests ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_photo' => 'ALTER TABLE members ADD COLUMN visibility_photo ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'avatar_path' => 'ALTER TABLE members ADD COLUMN avatar_path VARCHAR(255) DEFAULT NULL',
        ];

        foreach ($requiredColumns as $columnName => $statement) {
            $columnStmt->execute(['members', $columnName]);
            $hasColumn = (int) $columnStmt->fetchColumn() > 0;
            if (!$hasColumn) {
                db()->exec($statement);
            }
        }
    }


    if (table_exists('modules')) {
        $columnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnStmt->execute(['modules', 'visibility']);
        $hasVisibility = (int) $columnStmt->fetchColumn() > 0;
        if (!$hasVisibility) {
            db()->exec('ALTER TABLE modules ADD COLUMN visibility ENUM("public","members","admin") NOT NULL DEFAULT "members" AFTER is_enabled');
        }
        db()->exec("UPDATE modules SET is_enabled = 1, visibility = 'public' WHERE code IN ('news', 'articles', 'wiki', 'albums', 'events', 'auctions', 'chatbot', 'advertising', 'classifieds', 'press', 'education', 'committee', 'directory')");
        db()->exec("UPDATE modules SET is_enabled = 1, visibility = 'members' WHERE code IN ('dashboard', 'members', 'qsl')");
        db()->exec("UPDATE modules SET visibility = 'admin' WHERE code = 'admin'");
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS quotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_fr TEXT DEFAULT NULL,
            quote_en TEXT DEFAULT NULL,
            quote_de TEXT DEFAULT NULL,
            quote_nl TEXT DEFAULT NULL,
            quote_it TEXT DEFAULT NULL,
            quote_es TEXT DEFAULT NULL,
            quote_pt TEXT DEFAULT NULL,
            quote_ar TEXT DEFAULT NULL,
            quote_hi TEXT DEFAULT NULL,
            quote_ja TEXT DEFAULT NULL,
            quote_zh TEXT DEFAULT NULL,
            quote_bn TEXT DEFAULT NULL,
            quote_ru TEXT DEFAULT NULL,
            quote_id TEXT DEFAULT NULL,
            author VARCHAR(190) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    if (table_exists('quotes')) {
        $legacyColumnStmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $legacyColumnStmt->execute(['quotes', 'quote_text']);
        if ((int) $legacyColumnStmt->fetchColumn() > 0) {
            db()->exec('ALTER TABLE quotes DROP COLUMN quote_text');
        }

        foreach (quote_locale_columns() as $quoteColumn) {
            $columnStmt = db()->prepare(
                'SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
            );
            $columnStmt->execute(['quotes', $quoteColumn]);
            if ((int) $columnStmt->fetchColumn() === 0) {
                db()->exec('ALTER TABLE quotes ADD COLUMN ' . $quoteColumn . ' TEXT DEFAULT NULL');
            }
        }
    }

    $quoteCount = db()->query('SELECT COUNT(*) FROM quotes');
    $hasQuotes = $quoteCount !== false ? (int) $quoteCount->fetchColumn() > 0 : false;
    if (!$hasQuotes) {
        $seedCandidates = [
            __DIR__ . '/../assets/sql/radioamateur_citations_multilingue_3532_mysql.sql',
        ];
        $seedFile = '';
        foreach ($seedCandidates as $candidatePath) {
            if (is_file($candidatePath)) {
                $seedFile = $candidatePath;
                break;
            }
        }
        if ($seedFile !== '') {
            try {
                seed_quotes_from_sql_file($seedFile);
            } catch (Throwable $throwable) {
                log_structured_event('quotes_seed_failed', [
                    'message' => $throwable->getMessage(),
                    'file' => $seedFile,
                ]);
            }
        }
    }

    ensure_classified_ads_table();
    ensure_wiki_tables();

    ensure_member_favorites_table();
    ensure_member_notifications_table();
}
