<?php

declare(strict_types=1);

if (!function_exists('privacy_hash_value')) {
    function privacy_hash_value(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $salt = (string) config('security.csrf_key', '');
        if ($salt === '' || $salt === 'replace-with-a-random-32-byte-secret') {
            $salt = (string) config('app.site_name', 'ON4CRD');
        }

        return hash_hmac('sha256', $value, $salt);
    }
}

if (!function_exists('privacy_request_ip_hash')) {
    function privacy_request_ip_hash(): string
    {
        return privacy_hash_value((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    }
}

if (!function_exists('privacy_request_user_agent_hash')) {
    function privacy_request_user_agent_hash(): string
    {
        return privacy_hash_value(substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255));
    }
}

if (!function_exists('privacy_current_notice_version')) {
    function privacy_current_notice_version(): string
    {
        return '2026-06-05';
    }
}

if (!function_exists('privacy_ensure_tables')) {
    function privacy_ensure_tables(): void
    {
        try {
            db()->exec(
                "CREATE TABLE IF NOT EXISTS privacy_requests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    member_id INT NOT NULL,
                    request_type ENUM('access','rectification','erasure','restriction','objection','portability') NOT NULL,
                    status ENUM('pending','in_progress','resolved','rejected') NOT NULL DEFAULT 'pending',
                    notes TEXT NULL,
                    admin_notes TEXT NULL,
                    request_ip_hash CHAR(64) NULL,
                    request_user_agent_hash CHAR(64) NULL,
                    notice_version VARCHAR(32) NULL,
                    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    resolved_at DATETIME NULL,
                    INDEX idx_privacy_requests_member (member_id),
                    INDEX idx_privacy_requests_status (status),
                    CONSTRAINT fk_privacy_requests_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Throwable $exception) {
            // Runtime schema updates are best-effort because some installs run with limited SQL privileges.
        }
    }
}

if (!function_exists('privacy_create_request')) {
    function privacy_create_request(int $memberId, string $type, string $notes = ''): int
    {
        privacy_ensure_tables();

        $allowed = ['access', 'rectification', 'erasure', 'restriction', 'objection', 'portability'];
        if (!in_array($type, $allowed, true)) {
            $type = 'access';
        }

        $notes = trim($notes);
        $existing = db()->prepare(
            'SELECT id FROM privacy_requests WHERE member_id = ? AND request_type = ? AND status IN ("pending", "in_progress") ORDER BY id DESC LIMIT 1'
        );
        $existing->execute([$memberId, $type]);
        $existingId = (int) ($existing->fetchColumn() ?: 0);
        if ($existingId > 0) {
            return $existingId;
        }

        $stmt = db()->prepare(
            'INSERT INTO privacy_requests (
                member_id, request_type, notes, request_ip_hash, request_user_agent_hash, notice_version
            ) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $memberId,
            $type,
            $notes !== '' ? $notes : null,
            privacy_request_ip_hash() ?: null,
            privacy_request_user_agent_hash() ?: null,
            privacy_current_notice_version(),
        ]);

        return (int) db()->lastInsertId();
    }
}

if (!function_exists('privacy_member_requests')) {
    function privacy_member_requests(int $memberId): array
    {
        privacy_ensure_tables();

        try {
            $stmt = db()->prepare(
                'SELECT id, request_type, status, notes, admin_notes, requested_at, resolved_at
                 FROM privacy_requests
                 WHERE member_id = ?
                 ORDER BY requested_at DESC, id DESC'
            );
            $stmt->execute([$memberId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $exception) {
            return [];
        }
    }
}

if (!function_exists('privacy_export_rows_by_member')) {
    function privacy_export_rows_by_member(string $table, int $memberId, string $memberColumn = 'member_id', int $limit = 1000): array
    {
        if (!table_exists($table) || !table_has_column($table, $memberColumn)) {
            return [];
        }

        try {
            $stmt = db()->prepare(
                sprintf(
                    'SELECT * FROM `%s` WHERE `%s` = ? ORDER BY 1 DESC LIMIT %d',
                    str_replace('`', '``', $table),
                    str_replace('`', '``', $memberColumn),
                    max(1, $limit)
                )
            );
            $stmt->execute([$memberId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $exception) {
            return [];
        }
    }
}

if (!function_exists('privacy_export_member_data')) {
    function privacy_export_member_data(int $memberId): array
    {
        privacy_ensure_tables();

        $member = [];
        if (table_exists('members')) {
            $stmt = db()->prepare('SELECT * FROM members WHERE id = ? LIMIT 1');
            $stmt->execute([$memberId]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        $export = [
            'exported_at' => gmdate('c'),
            'notice_version' => privacy_current_notice_version(),
            'member' => $member,
            'auth_user' => null,
            'newsletter_subscriptions' => [],
            'privacy_requests' => privacy_member_requests($memberId),
            'related_records' => [],
        ];

        $authUserId = (int) ($member['auth_user_id'] ?? 0);
        if ($authUserId > 0 && table_exists('users')) {
            $stmt = db()->prepare('SELECT id, email, username, role, status, created_at, updated_at FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$authUserId]);
            $export['auth_user'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if (table_exists('newsletter_subscribers')) {
            try {
                $sql = 'SELECT * FROM newsletter_subscribers WHERE member_id = ?';
                $params = [$memberId];
                if (isset($member['email']) && (string) $member['email'] !== '' && table_has_column('newsletter_subscribers', 'email')) {
                    $sql .= ' OR email = ?';
                    $params[] = (string) $member['email'];
                }
                $stmt = db()->prepare($sql);
                $stmt->execute($params);
                $export['newsletter_subscriptions'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $exception) {
                $export['newsletter_subscriptions'] = [];
            }
        }

        $tables = [
            'member_preferences',
            'member_favorites',
            'member_notifications',
            'member_library_documents',
            'classified_ads',
            'article_proposals',
            'article_revisions',
            'wiki_revisions',
            'qso_logs',
            'qsl_cards',
            'chatbot_logs',
        ];
        foreach ($tables as $table) {
            $rows = privacy_export_rows_by_member($table, $memberId);
            if ($rows !== []) {
                $export['related_records'][$table] = $rows;
            }
        }

        if (table_exists('users_audit_log') && $authUserId > 0 && table_has_column('users_audit_log', 'user_id')) {
            $export['related_records']['users_audit_log'] = privacy_export_rows_by_member('users_audit_log', $authUserId, 'user_id');
        }

        return $export;
    }
}

if (!function_exists('privacy_send_member_export')) {
    function privacy_send_member_export(int $memberId): void
    {
        $payload = json_encode(
            privacy_export_member_data($memberId),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($payload === false) {
            $payload = '{}';
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="on4crd-rgpd-export-' . $memberId . '-' . date('Ymd-His') . '.json"');
        echo $payload;
        exit;
    }
}

if (!function_exists('privacy_purge_expired_data')) {
    function privacy_purge_expired_data(): void
    {
        $purges = [
            ['ad_events', 'created_at', '13 MONTH'],
            ['chatbot_logs', 'created_at', '180 DAY'],
            ['newsletter_deliveries', 'created_at', '24 MONTH'],
            ['member_notifications', 'created_at', '24 MONTH', 'is_read = 1'],
            ['privacy_requests', 'resolved_at', '5 YEAR', 'resolved_at IS NOT NULL'],
        ];

        foreach ($purges as $purge) {
            [$table, $column, $interval] = $purge;
            $extraWhere = $purge[3] ?? '1 = 1';
            if (!table_exists($table) || !table_has_column($table, $column)) {
                continue;
            }

            try {
                db()->exec(
                    sprintf(
                        'DELETE FROM `%s` WHERE %s AND `%s` < DATE_SUB(NOW(), INTERVAL %s)',
                        str_replace('`', '``', $table),
                        $extraWhere,
                        str_replace('`', '``', $column),
                        $interval
                    )
                );
            } catch (Throwable $exception) {
                continue;
            }
        }

        if (table_exists('users_audit_log') && table_has_column('users_audit_log', 'event_at')) {
            try {
                db()->exec('DELETE FROM users_audit_log WHERE event_at < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 13 MONTH))');
            } catch (Throwable $exception) {
                // Best-effort cleanup.
            }
        }

        if (function_exists('storage_path')) {
            $legacyResetLog = storage_path('auth/password_resets.log');
            if (is_file($legacyResetLog)) {
                @unlink($legacyResetLog);
            }
        }
    }
}

if (!function_exists('privacy_notice_short_html')) {
    function privacy_notice_short_html(string $context = 'default'): string
    {
        $link = function_exists('route_url') ? route_url('gdpr') : 'index.php?route=gdpr';
        $messages = [
            'register' => 'Les donnees du compte servent a creer le profil, securiser les acces et afficher uniquement les informations que vous rendez visibles. Le geocodage postal externe est optionnel.',
            'profile' => 'Les informations du profil restent controlees par vos reglages de visibilite. Le geocodage postal externe ne part que si vous le demandez.',
            'newsletter' => 'La newsletter exige un consentement explicite. Une preuve de consentement et des donnees techniques pseudonymisees sont conservees.',
            'geocode' => 'Si vous cochez le geocodage automatique, l adresse postale saisie est transmise a Nominatim pour calculer le locator radio.',
            'default' => 'Les donnees personnelles sont limitees aux usages du site, avec export, opposition et demande de suppression disponibles.',
        ];
        $message = $messages[$context] ?? $messages['default'];

        return '<div class="privacy-inline-notice">' . e($message) . ' <a href="' . e($link) . '">Voir la notice RGPD</a>.</div>';
    }
}

if (!function_exists('privacy_notice_sections')) {
    function privacy_notice_sections(): array
    {
        return [
            'Responsable du traitement' => 'ON4CRD traite les donnees necessaires au fonctionnement du site, des comptes membres, des contenus, des annonces, des newsletters et de la securite applicative.',
            'Finalites' => 'Gestion des comptes, annuaire radioamateur, publication de contenus, proposition d articles, moderation, securite, statistiques techniques, newsletter et demandes RGPD.',
            'Bases legales' => 'Execution du service pour le compte membre, interet legitime pour la securite et la moderation, consentement pour la newsletter, le tracking non essentiel et le geocodage postal externe.',
            'Donnees traitees' => 'Identite radioamateur, email, profil, contenus publies, preferences, abonnements, historiques techniques pseudonymises, demandes RGPD et journaux de securite limites.',
            'Destinataires' => 'Administrateurs habilites, membres selon vos reglages de visibilite, visiteurs pour les contenus publics, prestataires techniques strictement necessaires. Le geocodage optionnel utilise Nominatim.',
            'Conservation' => 'Les journaux techniques sont limites et purges automatiquement. Les donnees de compte sont conservees tant que le compte existe. Les demandes RGPD resolues sont conservees cinq ans.',
            'Droits' => 'Vous pouvez demander acces, export, rectification, limitation, opposition et suppression. L export JSON est disponible depuis cette page pour les membres connectes.',
            'Cookies et mesure' => 'Les cookies indispensables servent a la session et aux preferences. Le tracking Matomo est configure en mode privacy-first avec cookies desactives ou consentement requis selon la configuration.',
            'Contact' => 'Pour toute demande relative aux donnees personnelles, utilisez le formulaire de demande RGPD disponible sur cette page apres connexion.',
        ];
    }
}

