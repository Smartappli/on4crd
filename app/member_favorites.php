<?php
declare(strict_types=1);

if (!function_exists('ensure_member_favorites_table')) {
function ensure_member_favorites_table(): bool
{
    if (!table_exists('members')) {
        return false;
    }

    db()->exec('CREATE TABLE IF NOT EXISTS member_favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        target_type VARCHAR(48) NOT NULL,
        target_id INT NOT NULL,
        target_key VARCHAR(190) DEFAULT NULL,
        title VARCHAR(255) NOT NULL DEFAULT "",
        url VARCHAR(255) NOT NULL DEFAULT "",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_member_target (member_id, target_type, target_id),
        KEY idx_member_created (member_id, created_at),
        KEY idx_target (target_type, target_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    return true;
}
}

if (!function_exists('favorite_is_saved')) {
function favorite_is_saved(int $memberId, string $targetType, int $targetId): bool
{
    if ($memberId <= 0 || $targetId <= 0 || !ensure_member_favorites_table()) {
        return false;
    }

    $stmt = db()->prepare('SELECT id FROM member_favorites WHERE member_id = ? AND target_type = ? AND target_id = ? LIMIT 1');
    $stmt->execute([$memberId, $targetType, $targetId]);
    return $stmt->fetchColumn() !== false;
}
}

if (!function_exists('favorite_toggle')) {
function favorite_toggle(int $memberId, string $targetType, int $targetId, string $title = '', string $url = '', ?string $targetKey = null): bool
{
    if ($memberId <= 0 || $targetId <= 0 || !ensure_member_favorites_table()) {
        return false;
    }

    if (favorite_is_saved($memberId, $targetType, $targetId)) {
        $stmt = db()->prepare('DELETE FROM member_favorites WHERE member_id = ? AND target_type = ? AND target_id = ?');
        $stmt->execute([$memberId, $targetType, $targetId]);
        return false;
    }

    $stmt = db()->prepare('INSERT INTO member_favorites (member_id, target_type, target_id, target_key, title, url) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$memberId, $targetType, $targetId, $targetKey, mb_safe_substr(trim($title), 0, 255), mb_safe_substr(trim($url), 0, 255)]);
    return true;
}
}

if (!function_exists('member_favorites_recent')) {
function member_favorites_recent(int $memberId, int $limit = 12): array
{
    if ($memberId <= 0 || !ensure_member_favorites_table()) {
        return [];
    }

    $limit = max(1, min(100, $limit));
    $stmt = db()->prepare('SELECT target_type, target_id, target_key, title, url, created_at FROM member_favorites WHERE member_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
    $stmt->execute([$memberId]);
    return $stmt->fetchAll() ?: [];
}
}

