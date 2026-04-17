<?php
declare(strict_types=1);

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

        $columns = str_getcsv($line, ',');
        if (count($columns) <= 1) {
            $columns = str_getcsv($line, ';');
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
            unsubscribed_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_newsletter_member (member_id),
            INDEX idx_newsletter_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

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

function newsletter_upsert_subscriber(string $email, ?int $memberId = null, string $source = 'admin'): bool
{
    $normalized = newsletter_normalize_email($email);
    if ($normalized === '') {
        return false;
    }

    $existing = db()->prepare('SELECT id FROM newsletter_subscribers WHERE email = ? LIMIT 1');
    $existing->execute([$normalized]);
    $row = $existing->fetch();

    if ($row) {
        $stmt = db()->prepare('UPDATE newsletter_subscribers SET status = "active", unsubscribed_at = NULL, member_id = COALESCE(?, member_id), source = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$memberId, $source, (int) $row['id']]);

        return true;
    }

    $stmt = db()->prepare('INSERT INTO newsletter_subscribers (email, member_id, status, source, subscribe_token, unsubscribe_token) VALUES (?, ?, "active", ?, ?, ?)');

    return $stmt->execute([
        $normalized,
        $memberId,
        mb_safe_substr(trim($source), 0, 32),
        newsletter_generate_token(),
        newsletter_generate_token(),
    ]);
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

function newsletter_set_subscriber_status(int $id, string $status): bool
{
    if (!in_array($status, ['active', 'unsubscribed'], true)) {
        return false;
    }

    $stmt = db()->prepare('UPDATE newsletter_subscribers SET status = ?, unsubscribed_at = CASE WHEN ? = "unsubscribed" THEN NOW() ELSE NULL END WHERE id = ?');
    $stmt->execute([$status, $status, $id]);

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
    $stmt = db()->prepare('SELECT * FROM newsletter_subscribers WHERE member_id = ? LIMIT 1');
    $stmt->execute([$memberId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function newsletter_import_csv(string $csvContent): int
{
    $emails = newsletter_parse_csv_emails($csvContent);
    $count = 0;
    foreach ($emails as $email) {
        if (newsletter_upsert_subscriber($email, null, 'import_csv')) {
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
