<?php
declare(strict_types=1);

/**
 * @return array<int, array<string,mixed>>
 */
function committee_members(): array
{
    if (!table_exists('committee_members')) {
        return [];
    }
    return db()->query('SELECT * FROM committee_members WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll() ?: [];
}

function placeholder_avatar(string $seed = '', int $size = 256): string
{
    return 'assets/icons/icon-192.png';
}

function editorial_text(string $slot, string $fallback = ''): string
{
    if (table_exists('editorial_contents')) {
        $locale = current_locale();
        $stmt = db()->prepare('SELECT fr, en, de, nl FROM editorial_contents WHERE slot = ? LIMIT 1');
        $stmt->execute([$slot]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            $candidate = trim((string) ($row[$locale] ?? ''));
            if ($candidate === '') {
                $candidate = trim((string) ($row['fr'] ?? ''));
            }
            if ($candidate !== '') {
                return $candidate;
            }
        }
    }

    $defaults = [
        'committee.title' => 'Comité',
        'committee.intro' => 'Présentation du comité du radio club.',
        'committee.mission' => 'Transparence',
        'committee.onboarding' => 'Accueil des membres',
        'committee.contact_title' => 'Contact',
        'committee.contact_text' => 'Le comité est disponible pour vos questions.',
    ];

    return (string) ($defaults[$slot] ?? $fallback);
}

/**
 * @return array<string,mixed>|null
 */
function editorial_content_row(string $slot): ?array
{
    if ($slot === '' || !table_exists('editorial_contents')) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM editorial_contents WHERE slot = ? LIMIT 1');
    $stmt->execute([$slot]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function save_editorial_content(string $slot, string $fr = '', string $en = '', string $de = '', string $nl = ''): void
{
    if ($slot === '' || !table_exists('editorial_contents')) {
        return;
    }
    $update = db()->prepare('UPDATE editorial_contents SET fr = ?, en = ?, de = ?, nl = ?, updated_at = NOW() WHERE slot = ?');
    $update->execute([$fr, $en, $de, $nl, $slot]);
    if ($update->rowCount() > 0) {
        return;
    }
    db()->prepare('INSERT INTO editorial_contents (slot, fr, en, de, nl) VALUES (?, ?, ?, ?, ?)')
        ->execute([$slot, $fr, $en, $de, $nl]);
}
