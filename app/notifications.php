<?php
declare(strict_types=1);

if (!function_exists('ensure_member_notifications_table')) {
function ensure_member_notifications_table(): bool
{
    static $ready = null;

    if (is_bool($ready)) {
        return $ready;
    }

    if (!table_exists('members')) {
        $ready = false;
        return false;
    }

    try {
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

        if (!table_exists('member_notifications')) {
            $ready = false;
            return false;
        }

        if (!table_has_column('member_notifications', 'member_id')) {
            db()->exec('ALTER TABLE member_notifications ADD COLUMN member_id INT NOT NULL AFTER id');
        }
        if (!table_has_column('member_notifications', 'type')) {
            db()->exec('ALTER TABLE member_notifications ADD COLUMN type VARCHAR(64) NOT NULL DEFAULT "info" AFTER member_id');
        }
        if (!table_has_column('member_notifications', 'title')) {
            db()->exec('ALTER TABLE member_notifications ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT "" AFTER type');
        }
        if (!table_has_column('member_notifications', 'body')) {
            db()->exec('ALTER TABLE member_notifications ADD COLUMN body TEXT DEFAULT NULL AFTER title');
        }
        if (!table_has_column('member_notifications', 'url')) {
            db()->exec('ALTER TABLE member_notifications ADD COLUMN url VARCHAR(255) DEFAULT NULL AFTER body');
        }
        if (!table_has_column('member_notifications', 'is_read')) {
            db()->exec('ALTER TABLE member_notifications ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER url');
        }
        if (!table_has_column('member_notifications', 'created_at')) {
            db()->exec('ALTER TABLE member_notifications ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_read');
        }
        if (!table_has_column('member_notifications', 'read_at')) {
            db()->exec('ALTER TABLE member_notifications ADD COLUMN read_at TIMESTAMP NULL DEFAULT NULL AFTER created_at');
        }

        $typeColumn = member_notifications_column_metadata('type');
        $typeDataType = strtolower((string) ($typeColumn['DATA_TYPE'] ?? ''));
        $typeLength = (int) ($typeColumn['CHARACTER_MAXIMUM_LENGTH'] ?? 0);
        if ($typeDataType !== 'varchar' || $typeLength < 64) {
            db()->exec('ALTER TABLE member_notifications MODIFY COLUMN type VARCHAR(64) NOT NULL DEFAULT "info"');
        }

        if (!table_has_index('member_notifications', 'idx_member_unread')) {
            db()->exec('ALTER TABLE member_notifications ADD INDEX idx_member_unread (member_id, is_read, created_at)');
        }
        if (!table_has_index('member_notifications', 'idx_member_created')) {
            db()->exec('ALTER TABLE member_notifications ADD INDEX idx_member_created (member_id, created_at)');
        }
    } catch (Throwable $throwable) {
        if (function_exists('log_structured_event')) {
            log_structured_event('member_notifications_schema_failed', [
                'message' => $throwable->getMessage(),
            ]);
        }
        $ready = false;
        return false;
    }

    $ready = true;
    return true;
}
}

if (!function_exists('member_notifications_column_metadata')) {
function member_notifications_column_metadata(string $column): array
{
    try {
        $stmt = db()->prepare(
            'SELECT DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_DEFAULT
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
             LIMIT 1'
        );
        $stmt->execute(['member_notifications', strtolower(trim($column))]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : [];
    } catch (Throwable) {
        return [];
    }
}
}

if (!function_exists('notify_member')) {
function notify_member(int $memberId, string $type, string $title, ?string $body = null, ?string $url = null): void
{
    try {
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
    } catch (Throwable $throwable) {
        if (function_exists('log_structured_event')) {
            log_structured_event('member_notification_create_failed', [
                'member_id' => $memberId,
                'type' => mb_safe_substr(trim($type), 0, 64) ?: 'info',
                'message' => $throwable->getMessage(),
            ]);
        }
    }
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
