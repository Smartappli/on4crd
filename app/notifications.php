<?php
declare(strict_types=1);

if (!function_exists('ensure_member_notifications_table')) {
function ensure_member_notifications_table(): bool
{
    if (!table_exists('members')) {
        return false;
    }

    db()->exec('CREATE TABLE IF NOT EXISTS member_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        type VARCHAR(64) NOT NULL DEFAULT "info",
        title VARCHAR(255) NOT NULL,
        body TEXT DEFAULT NULL,
        url VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL DEFAULT NULL,
        KEY idx_member_unread (member_id, is_read, created_at),
        KEY idx_member_created (member_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    return true;
}
}

if (!function_exists('notify_member')) {
function notify_member(int $memberId, string $type, string $title, ?string $body = null, ?string $url = null): void
{
    if ($memberId <= 0 || !ensure_member_notifications_table()) {
        return;
    }

    $stmt = db()->prepare('INSERT INTO member_notifications (member_id, type, title, body, url) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $memberId,
        mb_safe_substr(trim($type), 0, 64) ?: 'info',
        mb_safe_substr(trim($title), 0, 255),
        $body !== null ? trim($body) : null,
        $url !== null ? mb_safe_substr(trim($url), 0, 255) : null,
    ]);
}
}

if (!function_exists('member_notifications_recent')) {
function member_notifications_recent(int $memberId, int $limit = 10): array
{
    if ($memberId <= 0 || !ensure_member_notifications_table()) {
        return [];
    }

    $limit = max(1, min(100, $limit));
    $stmt = db()->prepare('SELECT id, type, title, body, url, is_read, created_at FROM member_notifications WHERE member_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
    $stmt->execute([$memberId]);
    return $stmt->fetchAll() ?: [];
}
}

if (!function_exists('member_notifications_unread_count')) {
function member_notifications_unread_count(int $memberId): int
{
    if ($memberId <= 0 || !ensure_member_notifications_table()) {
        return 0;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM member_notifications WHERE member_id = ? AND is_read = 0');
    $stmt->execute([$memberId]);
    return (int) ($stmt->fetchColumn() ?: 0);
}
}

if (!function_exists('member_notifications_mark_all_read')) {
function member_notifications_mark_all_read(int $memberId): void
{
    if ($memberId <= 0 || !ensure_member_notifications_table()) {
        return;
    }

    $stmt = db()->prepare('UPDATE member_notifications SET is_read = 1, read_at = NOW() WHERE member_id = ? AND is_read = 0');
    $stmt->execute([$memberId]);
}
}

if (!function_exists('member_notification_mark_read')) {
function member_notification_mark_read(int $memberId, int $notificationId): void
{
    if ($memberId <= 0 || $notificationId <= 0 || !ensure_member_notifications_table()) {
        return;
    }

    $stmt = db()->prepare('UPDATE member_notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND member_id = ? AND is_read = 0');
    $stmt->execute([$notificationId, $memberId]);
}
}
