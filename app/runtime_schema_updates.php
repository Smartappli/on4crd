<?php
declare(strict_types=1);

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

    db()->exec(
        'CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(100) NOT NULL UNIQUE,
            label VARCHAR(190) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(120) NOT NULL UNIQUE,
            label VARCHAR(190) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            PRIMARY KEY (role_id, permission_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS member_roles (
            member_id INT NOT NULL,
            role_id INT NOT NULL,
            PRIMARY KEY (member_id, role_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS member_permissions (
            member_id INT NOT NULL,
            permission_id INT NOT NULL,
            PRIMARY KEY (member_id, permission_id)
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
            'first_name' => 'ALTER TABLE members ADD COLUMN first_name VARCHAR(95) DEFAULT NULL AFTER callsign',
            'last_name' => 'ALTER TABLE members ADD COLUMN last_name VARCHAR(95) DEFAULT NULL AFTER first_name',
            'country' => 'ALTER TABLE members ADD COLUMN country VARCHAR(190) DEFAULT NULL',
            'address' => 'ALTER TABLE members ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER country',
            'postal_code' => 'ALTER TABLE members ADD COLUMN postal_code VARCHAR(32) DEFAULT NULL AFTER address',
            'is_uba_member' => 'ALTER TABLE members ADD COLUMN is_uba_member TINYINT(1) NOT NULL DEFAULT 0',
            'uba_member_number' => 'ALTER TABLE members ADD COLUMN uba_member_number VARCHAR(64) DEFAULT NULL',
            'visibility_full_name' => 'ALTER TABLE members ADD COLUMN visibility_full_name ENUM("public","members","private") NOT NULL DEFAULT "private"',
            'visibility_first_name' => 'ALTER TABLE members ADD COLUMN visibility_first_name ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_last_name' => 'ALTER TABLE members ADD COLUMN visibility_last_name ENUM("public","members","private") NOT NULL DEFAULT "private"',
            'visibility_country' => 'ALTER TABLE members ADD COLUMN visibility_country ENUM("public","members","private") NOT NULL DEFAULT "members"',
            'visibility_address' => 'ALTER TABLE members ADD COLUMN visibility_address ENUM("public","members","private") NOT NULL DEFAULT "private"',
            'visibility_postal_code' => 'ALTER TABLE members ADD COLUMN visibility_postal_code ENUM("public","members","private") NOT NULL DEFAULT "private"',
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

        $memberColumnsExist = static function (array $columnNames) use ($columnStmt): bool {
            foreach ($columnNames as $columnName) {
                $columnStmt->execute(['members', $columnName]);
                if ((int) $columnStmt->fetchColumn() === 0) {
                    return false;
                }
            }

            return true;
        };

        if ($memberColumnsExist(['first_name', 'last_name', 'full_name'])) {
            db()->exec('UPDATE members SET first_name = TRIM(SUBSTRING_INDEX(full_name, " ", 1)) WHERE (first_name IS NULL OR first_name = "") AND full_name IS NOT NULL AND full_name <> ""');
            db()->exec('UPDATE members SET last_name = NULLIF(TRIM(CASE WHEN LOCATE(" ", full_name) > 0 THEN SUBSTRING(full_name, LOCATE(" ", full_name) + 1) ELSE "" END), "") WHERE (last_name IS NULL OR last_name = "") AND full_name IS NOT NULL AND full_name <> ""');
        }
        $visibilityDefaults = [
            'visibility_full_name' => 'private',
            'visibility_first_name' => 'members',
            'visibility_last_name' => 'private',
            'visibility_address' => 'private',
            'visibility_postal_code' => 'private',
        ];
        foreach ($visibilityDefaults as $visibilityColumn => $visibilityDefault) {
            if ($memberColumnsExist([$visibilityColumn])) {
                db()->exec('ALTER TABLE members MODIFY COLUMN ' . $visibilityColumn . ' ENUM("public","members","private") NOT NULL DEFAULT "' . $visibilityDefault . '"');
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

    ensure_configured_administrator_roles();

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
