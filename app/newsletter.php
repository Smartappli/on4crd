<?php
declare(strict_types=1);

require_once __DIR__ . '/privacy_helpers.php';

function newsletter_column_exists(string $column): bool
{
    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = "newsletter_subscribers" AND column_name = ?'
        );
        $stmt->execute([$column]);

        return (int) $stmt->fetchColumn() > 0;
    } catch (Throwable $exception) {
        return false;
    }
}

function newsletter_generate_token(): string
{
    return bin2hex(random_bytes(24));
}

function newsletter_normalize_email(string $email): string
{
    $normalized = mb_safe_strtolower(trim($email));

    return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : '';
}

/**
 * @return list<string>
 */
function newsletter_parse_csv_emails(string $csvContent): array
{
    $rows = preg_split('/\r\n|\n|\r/', $csvContent) ?: [];
    $emails = [];

    foreach ($rows as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $columns = str_getcsv($line, ',', '"', '\\');
        if (count($columns) <= 1) {
            $columns = str_getcsv($line, ';', '"', '\\');
        }

        foreach ($columns as $column) {
            $email = newsletter_normalize_email((string) $column);
            if ($email !== '') {
                $emails[$email] = true;
            }
        }
    }

    return array_keys($emails);
}

function newsletter_ensure_tables(): void
{
    db()->exec(
        'CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL UNIQUE,
            member_id INT DEFAULT NULL,
            status ENUM("active","unsubscribed") NOT NULL DEFAULT "active",
            source VARCHAR(32) NOT NULL DEFAULT "admin",
            subscribe_token CHAR(48) NOT NULL,
            unsubscribe_token CHAR(48) NOT NULL,
            consented_at DATETIME DEFAULT NULL,
            consent_ip_hash CHAR(64) DEFAULT NULL,
            consent_user_agent_hash CHAR(64) DEFAULT NULL,
            consent_notice_version VARCHAR(32) DEFAULT NULL,
            consent_proof VARCHAR(255) DEFAULT NULL,
            unsubscribed_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_newsletter_member (member_id),
            INDEX idx_newsletter_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $columns = [
        'consented_at' => 'DATETIME DEFAULT NULL',
        'consent_ip_hash' => 'CHAR(64) DEFAULT NULL',
        'consent_user_agent_hash' => 'CHAR(64) DEFAULT NULL',
        'consent_notice_version' => 'VARCHAR(32) DEFAULT NULL',
        'consent_proof' => 'VARCHAR(255) DEFAULT NULL',
    ];
    foreach ($columns as $column => $definition) {
        if (!newsletter_column_exists($column)) {
            try {
                db()->exec('ALTER TABLE newsletter_subscribers ADD COLUMN `' . $column . '` ' . $definition);
            } catch (Throwable $exception) {
                // Consent columns are used when available; legacy installs may require manual migration.
            }
        }
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS newsletter_campaigns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(190) NOT NULL,
            subject VARCHAR(190) NOT NULL,
            content LONGTEXT NOT NULL,
            status ENUM("draft","sent") NOT NULL DEFAULT "draft",
            created_by INT DEFAULT NULL,
            sent_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    db()->exec(
        'CREATE TABLE IF NOT EXISTS newsletter_deliveries (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT NOT NULL,
            subscriber_id INT NOT NULL,
            status ENUM("queued","sent","failed") NOT NULL DEFAULT "queued",
            error_message VARCHAR(255) DEFAULT NULL,
            sent_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_newsletter_delivery (campaign_id, subscriber_id),
            INDEX idx_newsletter_delivery_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function newsletter_upsert_subscriber(
    string $email,
    ?int $memberId = null,
    string $source = 'admin',
    bool $reactivateExisting = false,
    string $consentProof = ''
): bool
{
    newsletter_ensure_tables();

    $normalized = newsletter_normalize_email($email);
    if ($normalized === '') {
        return false;
    }
    $normalizedSource = mb_safe_substr(trim($source), 0, 32);
    $proof = mb_safe_substr(trim($consentProof), 0, 255);
    if ($proof === '') {
        return false;
    }
    $hasConsentColumns = newsletter_column_exists('consented_at')
        && newsletter_column_exists('consent_ip_hash')
        && newsletter_column_exists('consent_user_agent_hash')
        && newsletter_column_exists('consent_notice_version')
        && newsletter_column_exists('consent_proof');

    $existing = db()->prepare('SELECT id, status FROM newsletter_subscribers WHERE email = ? LIMIT 1');
    $existing->execute([$normalized]);
    $row = $existing->fetch();

    if ($row) {
        if ((string) ($row['status'] ?? '') === 'unsubscribed' && !$reactivateExisting) {
            if ($memberId !== null) {
                db()->prepare('UPDATE newsletter_subscribers SET member_id = COALESCE(member_id, ?), updated_at = NOW() WHERE id = ?')
                    ->execute([$memberId, (int) $row['id']]);
            }

            return false;
        }

        $sets = [
            'status = "active"',
            'unsubscribed_at = NULL',
            'member_id = COALESCE(?, member_id)',
            'source = ?',
            'updated_at = NOW()',
        ];
        $params = [$memberId, $normalizedSource];
        if ($hasConsentColumns) {
            $sets[] = 'consented_at = NOW()';
            $sets[] = 'consent_ip_hash = ?';
            $sets[] = 'consent_user_agent_hash = ?';
            $sets[] = 'consent_notice_version = ?';
            $sets[] = 'consent_proof = ?';
            $params[] = privacy_request_ip_hash() ?: null;
            $params[] = privacy_request_user_agent_hash() ?: null;
            $params[] = privacy_current_notice_version();
            $params[] = $proof;
        }
        $params[] = (int) $row['id'];
        $stmt = db()->prepare('UPDATE newsletter_subscribers SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($params);

        return true;
    }

    $columns = ['email', 'member_id', 'status', 'source', 'subscribe_token', 'unsubscribe_token'];
    $values = [
        $normalized,
        $memberId,
        'active',
        $normalizedSource,
        newsletter_generate_token(),
        newsletter_generate_token(),
    ];

    if ($hasConsentColumns) {
        $columns = array_merge($columns, [
            'consented_at',
            'consent_ip_hash',
            'consent_user_agent_hash',
            'consent_notice_version',
            'consent_proof',
        ]);
        $values[] = date('Y-m-d H:i:s');
        $values[] = privacy_request_ip_hash() ?: null;
        $values[] = privacy_request_user_agent_hash() ?: null;
        $values[] = privacy_current_notice_version();
        $values[] = $proof;
    }

    $columnSql = implode(', ', array_map(static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`', $columns));
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $stmt = db()->prepare('INSERT INTO newsletter_subscribers (' . $columnSql . ') VALUES (' . $placeholders . ')');

    return $stmt->execute($values);
}

function newsletter_unsubscribe_by_token(string $token): bool
{
    $token = trim($token);
    if ($token === '' || !preg_match('/^[a-f0-9]{48}$/', $token)) {
        return false;
    }

    $stmt = db()->prepare('UPDATE newsletter_subscribers SET status = "unsubscribed", unsubscribed_at = NOW() WHERE unsubscribe_token = ?');
    $stmt->execute([$token]);

    return $stmt->rowCount() > 0;
}

function newsletter_set_subscriber_status(int $id, string $status, string $consentProof = ''): bool
{
    newsletter_ensure_tables();

    if (!in_array($status, ['active', 'unsubscribed'], true)) {
        return false;
    }

    $proof = trim($consentProof);
    if ($status === 'active' && $proof === '') {
        return false;
    }

    $sets = [
        'status = ?',
        'unsubscribed_at = CASE WHEN ? = "unsubscribed" THEN NOW() ELSE NULL END',
        'source = CASE WHEN ? = "active" THEN "admin_status" ELSE source END',
    ];
    $params = [$status, $status, $status];
    if ($status === 'active'
        && newsletter_column_exists('consented_at')
        && newsletter_column_exists('consent_ip_hash')
        && newsletter_column_exists('consent_user_agent_hash')
        && newsletter_column_exists('consent_notice_version')
        && newsletter_column_exists('consent_proof')
    ) {
        $sets[] = 'consented_at = NOW()';
        $sets[] = 'consent_ip_hash = ?';
        $sets[] = 'consent_user_agent_hash = ?';
        $sets[] = 'consent_notice_version = ?';
        $sets[] = 'consent_proof = ?';
        $params[] = privacy_request_ip_hash() ?: null;
        $params[] = privacy_request_user_agent_hash() ?: null;
        $params[] = privacy_current_notice_version();
        $params[] = mb_safe_substr($proof, 0, 255);
    }
    $params[] = $id;

    $stmt = db()->prepare('UPDATE newsletter_subscribers SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $stmt->execute($params);

    return $stmt->rowCount() > 0;
}

function newsletter_delete_subscriber(int $id): bool
{
    $stmt = db()->prepare('DELETE FROM newsletter_subscribers WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);

    return $stmt->rowCount() > 0;
}

function newsletter_subscriber_for_member(int $memberId): ?array
{
    newsletter_ensure_tables();

    $stmt = db()->prepare(
        'SELECT * FROM newsletter_subscribers
         WHERE member_id = ?
         ORDER BY CASE WHEN status = "active" THEN 0 ELSE 1 END, updated_at DESC, id DESC
         LIMIT 1'
    );
    $stmt->execute([$memberId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function newsletter_import_csv(string $csvContent, string $consentProof = ''): int
{
    $proof = trim($consentProof);
    if ($proof === '') {
        return 0;
    }

    $emails = newsletter_parse_csv_emails($csvContent);
    $count = 0;
    foreach ($emails as $email) {
        if (newsletter_upsert_subscriber($email, null, 'import_csv', true, $proof)) {
            $count++;
        }
    }

    return $count;
}

function newsletter_create_campaign(string $title, string $subject, string $content, ?int $createdBy = null): int
{
    $stmt = db()->prepare('INSERT INTO newsletter_campaigns (title, subject, content, created_by) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        mb_safe_substr(trim($title), 0, 190),
        mb_safe_substr(trim($subject), 0, 190),
        trim($content),
        $createdBy,
    ]);

    return (int) db()->lastInsertId();
}

function newsletter_send_campaign(int $campaignId): array
{
    $campaignStmt = db()->prepare('SELECT * FROM newsletter_campaigns WHERE id = ? LIMIT 1');
    $campaignStmt->execute([$campaignId]);
    $campaign = $campaignStmt->fetch();
    if (!$campaign) {
        throw new RuntimeException('Campagne introuvable.');
    }

    $subscribers = db()->query('SELECT id, email, unsubscribe_token FROM newsletter_subscribers WHERE status = "active" ORDER BY id ASC')->fetchAll();
    $from = trim((string) config('app.site_name', 'ON4CRD'));
    $baseUrl = rtrim((string) config('app.base_url', ''), '/');

    $sent = 0;
    $failed = 0;

    $insertDelivery = db()->prepare('INSERT INTO newsletter_deliveries (campaign_id, subscriber_id, status, error_message, sent_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), error_message = VALUES(error_message), sent_at = VALUES(sent_at)');

    foreach ($subscribers as $subscriber) {
        $unsubscribeUrl = $baseUrl !== ''
            ? $baseUrl . '/index.php?route=newsletter_unsubscribe&token=' . urlencode((string) $subscriber['unsubscribe_token'])
            : 'index.php?route=newsletter_unsubscribe&token=' . urlencode((string) $subscriber['unsubscribe_token']);

        $body = (string) $campaign['content'] . "\n\n---\nSe désabonner: " . $unsubscribeUrl;
        $headers = 'From: ' . $from . " <no-reply@localhost>\r\n";

        $ok = @mail((string) $subscriber['email'], (string) $campaign['subject'], $body, $headers);

        if ($ok) {
            $sent++;
            $insertDelivery->execute([$campaignId, (int) $subscriber['id'], 'sent', null, date('Y-m-d H:i:s')]);
        } else {
            $failed++;
            $insertDelivery->execute([$campaignId, (int) $subscriber['id'], 'failed', 'mail() failed', null]);
        }
    }

    db()->prepare('UPDATE newsletter_campaigns SET status = "sent", sent_at = NOW() WHERE id = ?')->execute([$campaignId]);

    return ['sent' => $sent, 'failed' => $failed, 'total' => count($subscribers)];
}
