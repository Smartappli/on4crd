<?php
declare(strict_types=1);

if (!function_exists('ensure_member_preference_table')) {
function ensure_member_preference_table(): bool
{
    if (!table_exists('members')) {
        return false;
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS member_preferences (
            member_id INT NOT NULL,
            preference_key VARCHAR(80) NOT NULL,
            preference_value VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (member_id, preference_key),
            CONSTRAINT fk_member_preferences_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
        )'
    );

    return table_exists('member_preferences');
}

function member_preference_bool(int $memberId, string $key, bool $default = true): bool
{
    if ($memberId <= 0 || $key === '' || !ensure_member_preference_table()) {
        return $default;
    }

    $stmt = db()->prepare('SELECT preference_value FROM member_preferences WHERE member_id = ? AND preference_key = ? LIMIT 1');
    $stmt->execute([$memberId, $key]);
    $value = $stmt->fetchColumn();
    if ($value === false) {
        return $default;
    }

    return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
}

function set_member_preference_bool(int $memberId, string $key, bool $value): void
{
    if ($memberId <= 0 || $key === '' || !ensure_member_preference_table()) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO member_preferences (member_id, preference_key, preference_value)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$memberId, $key, $value ? '1' : '0']);
}

function member_preference_string(int $memberId, string $key, string $default = ''): string
{
    if ($memberId <= 0 || $key === '' || !ensure_member_preference_table()) {
        return $default;
    }

    $stmt = db()->prepare('SELECT preference_value FROM member_preferences WHERE member_id = ? AND preference_key = ? LIMIT 1');
    $stmt->execute([$memberId, $key]);
    $value = $stmt->fetchColumn();
    if ($value === false) {
        return $default;
    }

    return (string) $value;
}

function set_member_preference_string(int $memberId, string $key, string $value): void
{
    if ($memberId <= 0 || $key === '' || !ensure_member_preference_table()) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO member_preferences (member_id, preference_key, preference_value)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$memberId, $key, mb_safe_substr(trim($value), 0, 255)]);
}
}
