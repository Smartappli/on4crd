<?php

declare(strict_types=1);

if (!function_exists('privacy_log_internal')) {
    /**
     * @param array<string, mixed> $context
     */
    function privacy_log_internal(string $event, array $context = []): void
    {
        try {
            if (function_exists('log_structured_event')) {
                log_structured_event($event, $context);
                return;
            }
        } catch (Throwable) {
            // Fall back to PHP's error log below.
        }

        $safeContext = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $safeContext[$key] = $value;
            }
        }
        error_log('[on4crd-privacy] ' . json_encode(['event' => $event] + $safeContext, JSON_UNESCAPED_SLASHES));
    }
}

if (!function_exists('privacy_hash_secret')) {
    function privacy_hash_secret(): string
    {
        static $secret = null;
        if (is_string($secret) && $secret !== '') {
            return $secret;
        }

        $configured = trim((string) config('security.csrf_key', ''));
        if ($configured !== '' && $configured !== 'replace-with-a-random-32-byte-secret') {
            $secret = $configured;
            return $secret;
        }

        $saltFile = function_exists('storage_path')
            ? storage_path('privacy/hash_salt.key')
            : dirname(__DIR__) . '/storage/privacy/hash_salt.key';

        if (is_file($saltFile)) {
            $stored = trim((string) @file_get_contents($saltFile));
            if (strlen($stored) >= 32) {
                $secret = $stored;
                return $secret;
            }
        }

        try {
            $directory = dirname($saltFile);
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to create privacy salt directory.');
            }
            $generated = bin2hex(random_bytes(32));
            if (file_put_contents($saltFile, $generated, LOCK_EX) === false) {
                throw new RuntimeException('Unable to write privacy salt file.');
            }
            @chmod($saltFile, 0600);
            $secret = $generated;
            return $secret;
        } catch (Throwable $exception) {
            error_log('[on4crd-privacy] privacy_hash_secret_fallback: ' . $exception->getMessage());
        }

        $secret = hash('sha256', (string) config('db.dsn', '') . '|' . __DIR__);
        return $secret;
    }
}

if (!function_exists('privacy_hash_value')) {
    function privacy_hash_value(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return hash_hmac('sha256', $value, privacy_hash_secret());
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

if (!function_exists('privacy_contact_config')) {
    /**
     * @return array{controller_name:string,controller_email:string,controller_postal_address:string,dpo_email:string,supervisory_authority:string}
     */
    function privacy_contact_config(): array
    {
        return [
            'controller_name' => trim((string) config('privacy.controller_name', 'Radio Club Durnal ON4CRD')),
            'controller_email' => trim((string) config('privacy.controller_email', 'crdurnal@gmail.com')),
            'controller_postal_address' => trim((string) config('privacy.controller_postal_address', 'Rue des Ecoles, 5530 Purnode, Belgique')),
            'dpo_email' => trim((string) config('privacy.dpo_email', '')),
            'supervisory_authority' => trim((string) config('privacy.supervisory_authority', 'Autorité de protection des données, https://www.autoriteprotectiondonnees.be/')),
        ];
    }
}

if (!function_exists('privacy_retention_value')) {
    function privacy_retention_value(string $key, int $default): int
    {
        return max(1, (int) config('privacy.retention.' . $key, $default));
    }
}

if (!function_exists('privacy_request_statuses')) {
    /**
     * @return list<string>
     */
    function privacy_request_statuses(): array
    {
        return ['pending', 'in_progress', 'resolved', 'rejected'];
    }
}

if (!function_exists('privacy_request_types')) {
    /**
     * @return list<string>
     */
    function privacy_request_types(): array
    {
        return ['access', 'rectification', 'erasure', 'restriction', 'objection', 'portability'];
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
                    processed_by_member_id INT NULL,
                    processed_at DATETIME NULL,
                    erasure_completed_at DATETIME NULL,
                    request_ip_hash CHAR(64) NULL,
                    request_user_agent_hash CHAR(64) NULL,
                    notice_version VARCHAR(32) NULL,
                    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    resolved_at DATETIME NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_privacy_requests_member (member_id),
                    INDEX idx_privacy_requests_status (status),
                    INDEX idx_privacy_requests_processed_by (processed_by_member_id),
                    CONSTRAINT fk_privacy_requests_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            db()->exec(
                'CREATE TABLE IF NOT EXISTS privacy_request_events (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    request_id INT NOT NULL,
                    member_id INT DEFAULT NULL,
                    admin_member_id INT DEFAULT NULL,
                    event_type VARCHAR(64) NOT NULL,
                    from_status VARCHAR(32) DEFAULT NULL,
                    to_status VARCHAR(32) DEFAULT NULL,
                    notes TEXT DEFAULT NULL,
                    ip_hash CHAR(64) DEFAULT NULL,
                    user_agent_hash CHAR(64) DEFAULT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_privacy_events_request (request_id),
                    INDEX idx_privacy_events_member (member_id),
                    INDEX idx_privacy_events_admin (admin_member_id),
                    INDEX idx_privacy_events_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );

            $columns = [
                'processed_by_member_id' => 'ALTER TABLE privacy_requests ADD COLUMN processed_by_member_id INT NULL AFTER admin_notes',
                'processed_at' => 'ALTER TABLE privacy_requests ADD COLUMN processed_at DATETIME NULL AFTER processed_by_member_id',
                'erasure_completed_at' => 'ALTER TABLE privacy_requests ADD COLUMN erasure_completed_at DATETIME NULL AFTER processed_at',
                'updated_at' => 'ALTER TABLE privacy_requests ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER resolved_at',
            ];
            foreach ($columns as $column => $statement) {
                if (!table_has_column('privacy_requests', $column)) {
                    db()->exec($statement);
                }
            }
            if (!table_has_index('privacy_requests', 'idx_privacy_requests_processed_by')) {
                db()->exec('ALTER TABLE privacy_requests ADD INDEX idx_privacy_requests_processed_by (processed_by_member_id)');
            }
        } catch (Throwable $exception) {
            privacy_log_internal('privacy_schema_ensure_failed', ['message' => $exception->getMessage()]);
        }
    }
}

if (!function_exists('privacy_log_request_event')) {
    function privacy_log_request_event(
        int $requestId,
        ?int $memberId,
        ?int $adminMemberId,
        string $eventType,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        string $notes = ''
    ): void {
        if ($requestId <= 0) {
            return;
        }

        try {
            $stmt = db()->prepare(
                'INSERT INTO privacy_request_events (
                    request_id, member_id, admin_member_id, event_type, from_status, to_status, notes, ip_hash, user_agent_hash
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $requestId,
                $memberId !== null && $memberId > 0 ? $memberId : null,
                $adminMemberId !== null && $adminMemberId > 0 ? $adminMemberId : null,
                mb_safe_substr(trim($eventType), 0, 64),
                $fromStatus,
                $toStatus,
                trim($notes) !== '' ? mb_safe_substr(trim($notes), 0, 4000) : null,
                privacy_request_ip_hash() ?: null,
                privacy_request_user_agent_hash() ?: null,
            ]);
        } catch (Throwable $exception) {
            privacy_log_internal('privacy_request_event_failed', [
                'request_id' => $requestId,
                'event_type' => $eventType,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}

if (!function_exists('privacy_create_request')) {
    function privacy_create_request(int $memberId, string $type, string $notes = ''): int
    {
        privacy_ensure_tables();

        if (!in_array($type, privacy_request_types(), true)) {
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

        $requestId = (int) db()->lastInsertId();
        privacy_log_request_event($requestId, $memberId, null, 'created', null, 'pending', 'Demande creee par le membre.');

        return $requestId;
    }
}

if (!function_exists('privacy_member_requests')) {
    function privacy_member_requests(int $memberId): array
    {
        privacy_ensure_tables();

        try {
            $stmt = db()->prepare(
                'SELECT id, request_type, status, notes, admin_notes, requested_at, resolved_at, processed_at, erasure_completed_at
                 FROM privacy_requests
                 WHERE member_id = ?
                 ORDER BY requested_at DESC, id DESC'
            );
            $stmt->execute([$memberId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $exception) {
            privacy_log_internal('privacy_member_requests_failed', ['member_id' => $memberId, 'message' => $exception->getMessage()]);
            return [];
        }
    }
}

if (!function_exists('privacy_export_rows_by_column')) {
    function privacy_export_rows_by_column(string $table, string $column, int|string $value, int $limit = 1000): array
    {
        if (!table_exists($table) || !table_has_column($table, $column)) {
            return [];
        }

        try {
            $stmt = db()->prepare(
                sprintf(
                    'SELECT * FROM `%s` WHERE `%s` = ? ORDER BY 1 DESC LIMIT %d',
                    str_replace('`', '``', $table),
                    str_replace('`', '``', $column),
                    max(1, $limit)
                )
            );
            $stmt->execute([$value]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $exception) {
            privacy_log_internal('privacy_export_rows_failed', [
                'table' => $table,
                'column' => $column,
                'message' => $exception->getMessage(),
            ]);
            return [];
        }
    }
}

if (!function_exists('privacy_export_rows_by_member')) {
    function privacy_export_rows_by_member(string $table, int $memberId, string $memberColumn = 'member_id', int $limit = 1000): array
    {
        return privacy_export_rows_by_column($table, $memberColumn, $memberId, $limit);
    }
}

if (!function_exists('privacy_export_rows_by_ids')) {
    /**
     * @param list<int> $ids
     */
    function privacy_export_rows_by_ids(string $table, string $column, array $ids, int $limit = 1000): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
        if ($ids === [] || !table_exists($table) || !table_has_column($table, $column)) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = db()->prepare(
                sprintf(
                    'SELECT * FROM `%s` WHERE `%s` IN (%s) ORDER BY 1 DESC LIMIT %d',
                    str_replace('`', '``', $table),
                    str_replace('`', '``', $column),
                    $placeholders,
                    max(1, $limit)
                )
            );
            $stmt->execute($ids);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $exception) {
            privacy_log_internal('privacy_export_rows_by_ids_failed', [
                'table' => $table,
                'column' => $column,
                'message' => $exception->getMessage(),
            ]);
            return [];
        }
    }
}

if (!function_exists('privacy_safe_public_storage_path')) {
    /**
     * @param list<string> $allowedPrefixes
     */
    function privacy_safe_public_storage_path(string $path, array $allowedPrefixes): ?string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($normalized === '' || str_contains($normalized, '..')) {
            return null;
        }

        foreach ($allowedPrefixes as $prefix) {
            $prefix = ltrim(str_replace('\\', '/', trim($prefix)), '/');
            if ($prefix !== '' && str_starts_with($normalized, $prefix)) {
                return $normalized;
            }
        }

        return null;
    }
}

if (!function_exists('privacy_file_manifest_entry')) {
    /**
     * @param list<string> $allowedPrefixes
     * @return array<string, mixed>|null
     */
    function privacy_file_manifest_entry(string $label, string $path, array $allowedPrefixes): ?array
    {
        $safePath = privacy_safe_public_storage_path($path, $allowedPrefixes);
        if ($safePath === null) {
            return null;
        }

        $absolutePath = dirname(__DIR__) . '/' . $safePath;
        $entry = [
            'label' => $label,
            'path' => $safePath,
            'exists' => is_file($absolutePath),
        ];
        if (is_file($absolutePath)) {
            $entry['size_bytes'] = filesize($absolutePath) ?: 0;
            $entry['modified_at'] = gmdate('c', filemtime($absolutePath) ?: time());
        }

        return $entry;
    }
}

if (!function_exists('privacy_member_file_manifest')) {
    /**
     * @param array<string, mixed> $member
     * @return list<array<string, mixed>>
     */
    function privacy_member_file_manifest(int $memberId, array $member): array
    {
        $files = [];
        foreach (['photo_path' => 'member_photo', 'avatar_path' => 'member_avatar'] as $column => $label) {
            $path = trim((string) ($member[$column] ?? ''));
            if ($path !== '') {
                $entry = privacy_file_manifest_entry($label, $path, ['storage/uploads/members/']);
                if ($entry !== null) {
                    $files[] = $entry;
                }
            }
        }

        foreach (privacy_export_rows_by_member('member_library_documents', $memberId) as $row) {
            $entry = privacy_file_manifest_entry('member_library_document', (string) ($row['file_path'] ?? ''), ['storage/uploads/library/']);
            if ($entry !== null) {
                $entry['record_id'] = (int) ($row['id'] ?? 0);
                $entry['title'] = (string) ($row['title'] ?? '');
                $files[] = $entry;
            }
        }

        foreach (privacy_export_rows_by_column('ads', 'owner_member_id', $memberId) as $row) {
            $entry = privacy_file_manifest_entry('ad_image', (string) ($row['image_path'] ?? ''), ['storage/uploads/ads/']);
            if ($entry !== null) {
                $entry['record_id'] = (int) ($row['id'] ?? 0);
                $entry['title'] = (string) ($row['title'] ?? '');
                $files[] = $entry;
            }
        }

        return $files;
    }
}

if (!function_exists('privacy_add_export_records')) {
    /**
     * @param array<string, mixed> $export
     */
    function privacy_add_export_records(array &$export, string $key, array $rows): void
    {
        if ($rows !== []) {
            $export['related_records'][$key] = $rows;
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
            'file_manifest' => privacy_member_file_manifest($memberId, $member),
            'related_records' => [],
        ];

        $authUserId = (int) ($member['auth_user_id'] ?? 0);
        if ($authUserId > 0 && table_exists('users')) {
            $userColumns = array_values(array_filter(
                ['id', 'email', 'username', 'status', 'verified', 'resettable', 'roles_mask', 'registered', 'last_login', 'force_logout'],
                static fn(string $column): bool => table_has_column('users', $column)
            ));
            if ($userColumns !== []) {
                $stmt = db()->prepare('SELECT ' . implode(', ', $userColumns) . ' FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$authUserId]);
                $export['auth_user'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        }

        $newsletterSubscriberIds = [];
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
                foreach ($export['newsletter_subscriptions'] as $subscription) {
                    $newsletterSubscriberIds[] = (int) ($subscription['id'] ?? 0);
                }
            } catch (Throwable $exception) {
                privacy_log_internal('privacy_export_newsletter_failed', ['member_id' => $memberId, 'message' => $exception->getMessage()]);
                $export['newsletter_subscriptions'] = [];
            }
        }

        $sources = [
            ['member_preferences', 'member_id'],
            ['member_favorites', 'member_id'],
            ['member_notifications', 'member_id'],
            ['member_library_documents', 'member_id'],
            ['dashboard_widgets', 'member_id'],
            ['member_tool_presets', 'member_id'],
            ['member_tool_history', 'member_id'],
            ['classified_ads', 'owner_member_id'],
            ['ads', 'owner_member_id'],
            ['ad_events', 'member_id'],
            ['article_proposals', 'member_id'],
            ['articles', 'author_id'],
            ['article_revisions', 'author_id'],
            ['news_posts', 'author_id'],
            ['news_posts', 'moderator_id'],
            ['news_section_managers', 'member_id'],
            ['wiki_pages', 'author_id'],
            ['wiki_revisions', 'member_id'],
            ['qso_logs', 'member_id'],
            ['qsl_cards', 'member_id'],
            ['qsl_background_presets', 'member_id'],
            ['chatbot_logs', 'member_id'],
            ['auction_bids', 'member_id'],
            ['auction_lots', 'winner_member_id'],
            ['shop_orders', 'member_id'],
            ['privacy_request_events', 'member_id'],
        ];
        foreach ($sources as [$table, $column]) {
            $rows = privacy_export_rows_by_column($table, $column, $memberId);
            $key = $column === 'member_id' ? $table : ($table . '_by_' . $column);
            privacy_add_export_records($export, $key, $rows);
        }

        if (!empty($export['related_records']['shop_orders'])) {
            $orderIds = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), (array) $export['related_records']['shop_orders']);
            privacy_add_export_records($export, 'shop_order_items', privacy_export_rows_by_ids('shop_order_items', 'order_id', $orderIds));
        }
        privacy_add_export_records($export, 'newsletter_deliveries', privacy_export_rows_by_ids('newsletter_deliveries', 'subscriber_id', $newsletterSubscriberIds));

        if (table_exists('users_audit_log') && $authUserId > 0) {
            privacy_add_export_records($export, 'users_audit_log_by_user_id', privacy_export_rows_by_column('users_audit_log', 'user_id', $authUserId));
            privacy_add_export_records($export, 'users_audit_log_by_admin_id', privacy_export_rows_by_column('users_audit_log', 'admin_id', $authUserId));
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

if (!function_exists('privacy_delete_rows_by_column')) {
    function privacy_delete_rows_by_column(string $table, string $column, int|string $value): int
    {
        if (!table_exists($table) || !table_has_column($table, $column)) {
            return 0;
        }

        $stmt = db()->prepare(sprintf(
            'DELETE FROM `%s` WHERE `%s` = ?',
            str_replace('`', '``', $table),
            str_replace('`', '``', $column)
        ));
        $stmt->execute([$value]);

        return $stmt->rowCount();
    }
}

if (!function_exists('privacy_update_column_to_null')) {
    function privacy_update_column_to_null(string $table, string $targetColumn, string $whereColumn, int $memberId): int
    {
        if (!table_exists($table) || !table_has_column($table, $targetColumn) || !table_has_column($table, $whereColumn)) {
            return 0;
        }

        $stmt = db()->prepare(sprintf(
            'UPDATE `%s` SET `%s` = NULL WHERE `%s` = ?',
            str_replace('`', '``', $table),
            str_replace('`', '``', $targetColumn),
            str_replace('`', '``', $whereColumn)
        ));
        $stmt->execute([$memberId]);

        return $stmt->rowCount();
    }
}

if (!function_exists('privacy_delete_public_files')) {
    /**
     * @param list<string> $paths
     * @return array{deleted:int,missing:int,failed:int}
     */
    function privacy_delete_public_files(array $paths): array
    {
        $allowedPrefixes = [
            'storage/uploads/members/',
            'storage/uploads/library/',
            'storage/uploads/ads/',
        ];
        $root = realpath(dirname(__DIR__));
        $uploadsRoot = realpath(dirname(__DIR__) . '/storage/uploads');
        $result = ['deleted' => 0, 'missing' => 0, 'failed' => 0];

        foreach (array_values(array_unique(array_filter(array_map('strval', $paths)))) as $path) {
            $safePath = privacy_safe_public_storage_path($path, $allowedPrefixes);
            if ($safePath === null || $root === false || $uploadsRoot === false) {
                $result['failed']++;
                continue;
            }

            $absolutePath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safePath);
            $realPath = realpath($absolutePath);
            if ($realPath === false || !is_file($realPath)) {
                $result['missing']++;
                continue;
            }
            if (!str_starts_with($realPath, $uploadsRoot . DIRECTORY_SEPARATOR)) {
                $result['failed']++;
                continue;
            }

            if (@unlink($realPath)) {
                $result['deleted']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }
}

if (!function_exists('privacy_member_personal_file_paths')) {
    /**
     * @param array<string, mixed> $member
     * @return list<string>
     */
    function privacy_member_personal_file_paths(int $memberId, array $member): array
    {
        $paths = [];
        foreach (['photo_path', 'avatar_path'] as $column) {
            $path = trim((string) ($member[$column] ?? ''));
            if ($path !== '') {
                $paths[] = $path;
            }
        }
        foreach (privacy_export_rows_by_member('member_library_documents', $memberId) as $row) {
            $path = trim((string) ($row['file_path'] ?? ''));
            if ($path !== '') {
                $paths[] = $path;
            }
        }
        foreach (privacy_export_rows_by_column('ads', 'owner_member_id', $memberId) as $row) {
            $path = trim((string) ($row['image_path'] ?? ''));
            if ($path !== '') {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }
}

if (!function_exists('privacy_delete_newsletter_for_member')) {
    function privacy_delete_newsletter_for_member(int $memberId, string $email): int
    {
        if (!table_exists('newsletter_subscribers')) {
            return 0;
        }

        $where = ['member_id = ?'];
        $params = [$memberId];
        if ($email !== '' && table_has_column('newsletter_subscribers', 'email')) {
            $where[] = 'email = ?';
            $params[] = $email;
        }

        $subscriberIds = [];
        if (table_has_column('newsletter_subscribers', 'id')) {
            $stmt = db()->prepare('SELECT id FROM newsletter_subscribers WHERE ' . implode(' OR ', $where));
            $stmt->execute($params);
            $subscriberIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        }

        if ($subscriberIds !== [] && table_exists('newsletter_deliveries') && table_has_column('newsletter_deliveries', 'subscriber_id')) {
            $placeholders = implode(',', array_fill(0, count($subscriberIds), '?'));
            db()->prepare('DELETE FROM newsletter_deliveries WHERE subscriber_id IN (' . $placeholders . ')')->execute($subscriberIds);
        }

        $stmt = db()->prepare('DELETE FROM newsletter_subscribers WHERE ' . implode(' OR ', $where));
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}

if (!function_exists('privacy_delete_ads_for_member')) {
    /**
     * @return array{rows:int,files:list<string>}
     */
    function privacy_delete_ads_for_member(int $memberId): array
    {
        if (!table_exists('ads') || !table_has_column('ads', 'owner_member_id')) {
            return ['rows' => 0, 'files' => []];
        }

        $stmt = db()->prepare('SELECT id, image_path FROM ads WHERE owner_member_id = ?');
        $stmt->execute([$memberId]);
        $ads = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $adIds = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $ads);
        $files = array_values(array_filter(array_map(static fn(array $row): string => trim((string) ($row['image_path'] ?? '')), $ads)));

        if ($adIds !== [] && table_exists('ad_events') && table_has_column('ad_events', 'ad_id')) {
            $placeholders = implode(',', array_fill(0, count($adIds), '?'));
            db()->prepare('DELETE FROM ad_events WHERE ad_id IN (' . $placeholders . ')')->execute($adIds);
        }

        $delete = db()->prepare('DELETE FROM ads WHERE owner_member_id = ?');
        $delete->execute([$memberId]);

        return ['rows' => $delete->rowCount(), 'files' => $files];
    }
}

if (!function_exists('privacy_update_members_for_erasure')) {
    /**
     * @param array<string, mixed> $member
     */
    function privacy_update_members_for_erasure(int $memberId, array $member): int
    {
        if (!table_exists('members')) {
            return 0;
        }

        $assignments = [];
        $params = [];
        $set = static function (string $column, mixed $value) use (&$assignments, &$params): void {
            if (table_has_column('members', $column)) {
                $assignments[] = '`' . str_replace('`', '``', $column) . '` = ?';
                $params[] = $value;
            }
        };
        $setRaw = static function (string $column, string $sql) use (&$assignments): void {
            if (table_has_column('members', $column)) {
                $assignments[] = '`' . str_replace('`', '``', $column) . '` = ' . $sql;
            }
        };

        $set('callsign', 'ERASED' . $memberId);
        $set('full_name', 'Compte supprime ' . $memberId);
        $set('password_hash', password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT));
        $set('password_change_required', 1);
        $set('is_active', 0);
        $set('is_committee', 0);
        $set('committee_sort_order', 100);

        foreach ([
            'auth_user_id', 'first_name', 'last_name', 'email', 'country', 'address', 'postal_code', 'qth', 'locator',
            'phone', 'photo_path', 'avatar_path', 'licence_class', 'operator_since', 'cq_zone', 'itu_zone', 'qsl_via',
            'lotw_username', 'eqsl_username', 'qrz_url', 'website', 'uba_member_number', 'station_equipment',
            'antennas', 'favourite_bands', 'favourite_modes', 'interests', 'committee_role', 'committee_bio',
        ] as $column) {
            $setRaw($column, 'NULL');
        }
        foreach (['is_uba_member'] as $column) {
            $set($column, 0);
        }
        foreach ([
            'visibility_email', 'visibility_phone', 'visibility_full_name', 'visibility_first_name', 'visibility_last_name',
            'visibility_country', 'visibility_address', 'visibility_postal_code', 'visibility_qth', 'visibility_locator',
            'visibility_licence_class', 'visibility_operator_since', 'visibility_qsl', 'visibility_qrz', 'visibility_uba',
            'visibility_favourite_bands', 'visibility_favourite_modes', 'visibility_station', 'visibility_antennas',
            'visibility_interests', 'visibility_photo', 'visibility_online',
        ] as $column) {
            $set($column, 'private');
        }

        if ($assignments === []) {
            return 0;
        }

        $params[] = $memberId;
        $stmt = db()->prepare('UPDATE members SET ' . implode(', ', $assignments) . ' WHERE id = ? LIMIT 1');
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}

if (!function_exists('privacy_update_auth_user_for_erasure')) {
    function privacy_update_auth_user_for_erasure(int $authUserId, int $memberId): int
    {
        if ($authUserId <= 0 || !table_exists('users')) {
            return 0;
        }

        $assignments = [];
        $params = [];
        $set = static function (string $column, mixed $value) use (&$assignments, &$params): void {
            if (table_has_column('users', $column)) {
                $assignments[] = '`' . str_replace('`', '``', $column) . '` = ?';
                $params[] = $value;
            }
        };
        $setRaw = static function (string $column, string $sql) use (&$assignments): void {
            if (table_has_column('users', $column)) {
                $assignments[] = '`' . str_replace('`', '``', $column) . '` = ' . $sql;
            }
        };

        $set('email', 'erased-member-' . $memberId . '-' . substr(privacy_hash_value((string) $authUserId), 0, 12) . '@invalid.local');
        $set('username', null);
        $set('password', password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT));
        $set('status', 0);
        $set('verified', 0);
        $set('resettable', 0);
        $set('roles_mask', 0);
        $setRaw('force_logout', 'force_logout + 1');

        if ($assignments === []) {
            return 0;
        }

        $params[] = $authUserId;
        $stmt = db()->prepare('UPDATE users SET ' . implode(', ', $assignments) . ' WHERE id = ? LIMIT 1');
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}

if (!function_exists('privacy_apply_member_erasure_db')) {
    /**
     * @return array{files:list<string>,operations:array<string,int>}
     */
    function privacy_apply_member_erasure_db(int $memberId, int $adminMemberId): array
    {
        if ($memberId <= 0 || !table_exists('members')) {
            throw new RuntimeException('Membre introuvable pour effacement.');
        }

        $stmt = db()->prepare('SELECT * FROM members WHERE id = ? LIMIT 1');
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if ($member === []) {
            throw new RuntimeException('Membre introuvable pour effacement.');
        }

        $authUserId = (int) ($member['auth_user_id'] ?? 0);
        $memberEmail = trim((string) ($member['email'] ?? ''));
        $files = privacy_member_personal_file_paths($memberId, $member);
        $operations = [];

        $operations['newsletter_subscribers'] = privacy_delete_newsletter_for_member($memberId, $memberEmail);
        $adsDeletion = privacy_delete_ads_for_member($memberId);
        $operations['ads'] = $adsDeletion['rows'];
        $files = array_merge($files, $adsDeletion['files']);

        foreach ([
            ['member_preferences', 'member_id'],
            ['member_favorites', 'member_id'],
            ['member_notifications', 'member_id'],
            ['member_library_documents', 'member_id'],
            ['dashboard_widgets', 'member_id'],
            ['member_tool_presets', 'member_id'],
            ['member_tool_history', 'member_id'],
            ['news_section_managers', 'member_id'],
            ['member_roles', 'member_id'],
            ['member_permissions', 'member_id'],
            ['qso_logs', 'member_id'],
            ['qsl_cards', 'member_id'],
            ['qsl_background_presets', 'member_id'],
            ['chatbot_logs', 'member_id'],
            ['ad_events', 'member_id'],
            ['article_proposals', 'member_id'],
            ['classified_ads', 'owner_member_id'],
        ] as [$table, $column]) {
            $operations[$table] = privacy_delete_rows_by_column($table, $column, $memberId);
        }

        foreach ([
            ['articles', 'author_id'],
            ['article_revisions', 'author_id'],
            ['news_posts', 'author_id'],
            ['news_posts', 'moderator_id'],
            ['wiki_pages', 'author_id'],
            ['wiki_revisions', 'member_id'],
            ['auction_lots', 'winner_member_id'],
        ] as [$table, $column]) {
            $operations[$table . '_' . $column . '_nulled'] = privacy_update_column_to_null($table, $column, $column, $memberId);
        }

        if (table_exists('shop_orders') && table_has_column('shop_orders', 'member_id') && table_has_column('shop_orders', 'notes')) {
            $shopStmt = db()->prepare('UPDATE shop_orders SET notes = NULL WHERE member_id = ?');
            $shopStmt->execute([$memberId]);
            $operations['shop_orders_notes_nulled'] = $shopStmt->rowCount();
        }

        if ($authUserId > 0) {
            foreach ([
                ['users_confirmations', 'user_id'],
                ['users_remembered', 'user'],
                ['users_resets', 'user'],
                ['users_otps', 'user_id'],
                ['users_2fa', 'user_id'],
            ] as [$table, $column]) {
                $operations[$table] = privacy_delete_rows_by_column($table, $column, $authUserId);
            }

            if (table_exists('users_audit_log') && table_has_column('users_audit_log', 'user_id')) {
                $sets = [];
                foreach (['ip_address', 'user_agent', 'details_json'] as $column) {
                    if (table_has_column('users_audit_log', $column)) {
                        $sets[] = $column . ' = NULL';
                    }
                }
                if ($sets !== []) {
                    $auditStmt = db()->prepare('UPDATE users_audit_log SET ' . implode(', ', $sets) . ' WHERE user_id = ?');
                    $auditStmt->execute([$authUserId]);
                    $operations['users_audit_log_anonymized'] = $auditStmt->rowCount();
                }
            }

            $operations['users_anonymized'] = privacy_update_auth_user_for_erasure($authUserId, $memberId);
        }

        $operations['members_anonymized'] = privacy_update_members_for_erasure($memberId, $member);

        return [
            'files' => array_values(array_unique($files)),
            'operations' => $operations,
        ];
    }
}

if (!function_exists('privacy_update_request_status')) {
    /**
     * @return array<string, mixed>
     */
    function privacy_update_request_status(int $requestId, string $status, string $adminNotes, int $adminMemberId, bool $applyErasure = false): array
    {
        privacy_ensure_tables();
        if ($requestId <= 0 || !in_array($status, privacy_request_statuses(), true)) {
            throw new RuntimeException('Demande invalide.');
        }

        $filesToDelete = [];
        $erasureSummary = null;
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM privacy_requests WHERE id = ? LIMIT 1 FOR UPDATE');
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            if ($request === []) {
                throw new RuntimeException('Demande RGPD introuvable.');
            }

            $memberId = (int) ($request['member_id'] ?? 0);
            $previousStatus = (string) ($request['status'] ?? 'pending');
            $requestType = (string) ($request['request_type'] ?? '');

            if ($status === 'resolved' && $requestType === 'erasure') {
                if (!$applyErasure) {
                    throw new RuntimeException('Cochez l anonymisation automatique pour resoudre une demande de suppression.');
                }
                $erasureSummary = privacy_apply_member_erasure_db($memberId, $adminMemberId);
                $filesToDelete = (array) ($erasureSummary['files'] ?? []);
            }

            $update = $pdo->prepare(
                'UPDATE privacy_requests
                 SET status = ?,
                     admin_notes = ?,
                     processed_by_member_id = ?,
                     processed_at = NOW(),
                     resolved_at = CASE WHEN ? IN ("resolved", "rejected") THEN COALESCE(resolved_at, NOW()) ELSE NULL END,
                     erasure_completed_at = CASE WHEN ? = 1 THEN COALESCE(erasure_completed_at, NOW()) ELSE erasure_completed_at END
                 WHERE id = ?'
            );
            $update->execute([
                $status,
                trim($adminNotes) !== '' ? trim($adminNotes) : null,
                $adminMemberId > 0 ? $adminMemberId : null,
                $status,
                $erasureSummary !== null ? 1 : 0,
                $requestId,
            ]);

            privacy_log_request_event($requestId, $memberId, $adminMemberId, 'status_updated', $previousStatus, $status, $adminNotes);
            if ($erasureSummary !== null) {
                privacy_log_request_event($requestId, $memberId, $adminMemberId, 'erasure_applied', $previousStatus, $status, json_encode($erasureSummary['operations'] ?? [], JSON_UNESCAPED_SLASHES) ?: '');
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        $fileDeletion = $filesToDelete !== [] ? privacy_delete_public_files($filesToDelete) : ['deleted' => 0, 'missing' => 0, 'failed' => 0];
        if ($filesToDelete !== []) {
            privacy_log_request_event($requestId, null, $adminMemberId, 'erasure_files_processed', null, null, json_encode($fileDeletion, JSON_UNESCAPED_SLASHES) ?: '');
        }

        return [
            'erasure' => $erasureSummary,
            'files' => $fileDeletion,
        ];
    }
}

if (!function_exists('privacy_request_events_for_request_ids')) {
    /**
     * @param list<int> $requestIds
     * @return array<int, list<array<string, mixed>>>
     */
    function privacy_request_events_for_request_ids(array $requestIds): array
    {
        $requestIds = array_values(array_unique(array_filter(array_map('intval', $requestIds), static fn(int $id): bool => $id > 0)));
        if ($requestIds === []) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($requestIds), '?'));
            $stmt = db()->prepare(
                'SELECT pre.*, m.callsign AS admin_callsign
                 FROM privacy_request_events pre
                 LEFT JOIN members m ON m.id = pre.admin_member_id
                 WHERE pre.request_id IN (' . $placeholders . ')
                 ORDER BY pre.created_at DESC, pre.id DESC'
            );
            $stmt->execute($requestIds);
            $grouped = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $grouped[(int) ($row['request_id'] ?? 0)][] = $row;
            }

            return $grouped;
        } catch (Throwable $exception) {
            privacy_log_internal('privacy_request_events_fetch_failed', ['message' => $exception->getMessage()]);
            return [];
        }
    }
}

if (!function_exists('privacy_purge_password_reset_request_log')) {
    function privacy_purge_password_reset_request_log(int $retentionDays): void
    {
        if (!function_exists('storage_path')) {
            return;
        }

        $path = storage_path('auth/password_reset_requests.log');
        if (!is_file($path)) {
            return;
        }

        $cutoff = time() - ($retentionDays * 86400);
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        $kept = [];
        foreach ($lines as $line) {
            $decoded = json_decode((string) $line, true);
            if (!is_array($decoded)) {
                continue;
            }
            $createdAt = strtotime((string) ($decoded['created_at'] ?? ''));
            if ($createdAt === false || $createdAt >= $cutoff) {
                $kept[] = (string) $line;
            }
        }

        if ($kept === []) {
            @unlink($path);
            return;
        }

        if (count($kept) !== count($lines)) {
            @file_put_contents($path, implode(PHP_EOL, $kept) . PHP_EOL, LOCK_EX);
        }
    }
}

if (!function_exists('privacy_purge_geocode_cache')) {
    function privacy_purge_geocode_cache(int $retentionDays): void
    {
        $directory = function_exists('cache_storage_dir') ? cache_storage_dir() : dirname(__DIR__) . '/storage/cache/data';
        if (!is_dir($directory)) {
            return;
        }

        $cutoff = time() - ($retentionDays * 86400);
        foreach (glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . 'profile_geocode_*.cache.php') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $modifiedAt = filemtime($path);
            if ($modifiedAt !== false && $modifiedAt < $cutoff) {
                @unlink($path);
            }
        }
    }
}

if (!function_exists('privacy_purge_expired_data')) {
    function privacy_purge_expired_data(): void
    {
        $technicalLogMonths = privacy_retention_value('technical_logs_months', 13);
        $chatbotDays = privacy_retention_value('chatbot_days', 180);
        $newsletterDeliveriesMonths = privacy_retention_value('newsletter_deliveries_months', 24);
        $readNotificationsMonths = privacy_retention_value('read_notifications_months', 24);
        $privacyRequestsYears = privacy_retention_value('privacy_requests_years', 5);

        $purges = [
            ['ad_events', 'created_at', $technicalLogMonths . ' MONTH'],
            ['chatbot_logs', 'created_at', $chatbotDays . ' DAY'],
            ['newsletter_deliveries', 'created_at', $newsletterDeliveriesMonths . ' MONTH'],
            ['member_notifications', 'created_at', $readNotificationsMonths . ' MONTH', 'is_read = 1'],
            ['privacy_requests', 'resolved_at', $privacyRequestsYears . ' YEAR', 'resolved_at IS NOT NULL'],
            ['privacy_request_events', 'created_at', $privacyRequestsYears . ' YEAR'],
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
                privacy_log_internal('privacy_purge_table_failed', [
                    'table' => $table,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if (table_exists('users_audit_log') && table_has_column('users_audit_log', 'event_at')) {
            try {
                db()->exec('DELETE FROM users_audit_log WHERE event_at < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL ' . $technicalLogMonths . ' MONTH))');
            } catch (Throwable $exception) {
                privacy_log_internal('privacy_purge_users_audit_log_failed', ['message' => $exception->getMessage()]);
            }
        }

        privacy_purge_password_reset_request_log(privacy_retention_value('password_reset_log_days', 90));
        privacy_purge_geocode_cache(privacy_retention_value('geocode_cache_days', 30));

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
            'register' => 'Les données du compte servent à créer le profil, sécuriser les accès et afficher uniquement les informations que vous rendez visibles. Le géocodage postal externe est optionnel.',
            'profile' => 'Les informations du profil restent contrôlées par vos réglages de visibilité. Le géocodage postal externe ne part que si vous le demandez.',
            'newsletter' => 'La newsletter exige un consentement explicite. Une preuve de consentement et des données techniques pseudonymisées sont conservées.',
            'geocode' => 'Si vous cochez le géocodage automatique, l\'adresse postale saisie est transmise à Nominatim et le résultat est gardé en cache 30 jours.',
            'default' => 'Les données personnelles sont limitées aux usages du site, avec export, opposition et demande de suppression disponibles.',
        ];
        $message = $messages[$context] ?? $messages['default'];

        return '<div class="privacy-inline-notice">' . e($message) . ' <a href="' . e($link) . '">Voir la notice RGPD</a>.</div>';
    }
}

if (!function_exists('privacy_notice_sections')) {
    function privacy_notice_sections(): array
    {
        $contact = privacy_contact_config();
        $matomoConfigured = trim((string) config('tracking.matomo_url', '')) !== '' && trim((string) config('tracking.matomo_site_id', '')) !== '';
        $dpo = $contact['dpo_email'] !== '' ? ' DPO/contact dédié: ' . $contact['dpo_email'] . '.' : '';
        $trackingText = $matomoConfigured
            ? 'Une mesure d\'audience Matomo peut être chargée uniquement selon la configuration de consentement, avec anonymisation et cookies désactivés si configuré.'
            : 'Aucune mesure d\'audience externe n\'est chargée par défaut; Matomo reste désactivé tant qu\'aucune instance n\'est configurée.';

        return [
            'Responsable du traitement' => $contact['controller_name'] . ' est responsable du traitement. Contact public: ' . $contact['controller_email'] . '. Adresse: ' . $contact['controller_postal_address'] . '.' . $dpo,
            'Finalités' => 'Gestion des comptes, annuaire radioamateur, publication de contenus, proposition d\'articles, modération, sécurité, statistiques techniques internes, newsletter, boutique/événements et demandes RGPD.',
            'Bases légales' => 'Exécution du service pour le compte membre, intérêt légitime pour la sécurité et la modération, obligations légales pour certains journaux/transactions, consentement pour la newsletter, le tracking non essentiel et le géocodage postal externe.',
            'Données traitées' => 'Identité radioamateur, email, profil, visibilités, contenus publiés, documents téléversés, préférences, abonnements, historiques techniques pseudonymisés, demandes RGPD, logs de sécurité et traces de consentement.',
            'Destinataires' => 'Administrateurs habilités, membres selon vos réglages de visibilité, visiteurs pour les contenus publics, hébergeur du site, prestataires email strictement nécessaires, Google Maps si vous affichez la carte intégrée et Nominatim uniquement si vous activez le géocodage. Les bibliothèques front principales sont servies localement, sans CDN public.',
            'Conservation' => 'Compte conservé tant qu\'il est actif. Logs techniques: ' . privacy_retention_value('technical_logs_months', 13) . ' mois. Chatbot: ' . privacy_retention_value('chatbot_days', 180) . ' jours. Newsletter livraisons: ' . privacy_retention_value('newsletter_deliveries_months', 24) . ' mois. Notifications lues: ' . privacy_retention_value('read_notifications_months', 24) . ' mois. Demandes RGPD résolues: ' . privacy_retention_value('privacy_requests_years', 5) . ' ans. Reset password: ' . privacy_retention_value('password_reset_log_days', 90) . ' jours. Cache géocodage: ' . privacy_retention_value('geocode_cache_days', 30) . ' jours.',
            'Droits' => 'Vous pouvez demander accès, export, rectification, limitation, opposition, portabilité et suppression. L\'export JSON est disponible depuis cette page pour les membres connectés; la suppression lance une anonymisation contrôlée avec journal d\'action.',
            'Cookies et mesure' => 'Les cookies indispensables servent à la session, à la sécurité et aux préférences de langue/theme. ' . $trackingText,
            'Sécurité' => 'Les IP et user-agents applicatifs sont pseudonymisés par HMAC avec un secret local persistant. Les actions RGPD administratives sont journalisées avec identifiant administrateur, statut et horodatage.',
            'Contact et réclamation' => 'Pour toute demande relative aux données personnelles: ' . $contact['controller_email'] . '. Les membres connectés peuvent aussi utiliser le formulaire de cette page. Autorité de contrôle: ' . $contact['supervisory_authority'] . '.',
        ];
    }
}
