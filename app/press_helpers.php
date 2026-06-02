<?php
declare(strict_types=1);

/**
 * @return array<int,array<string,mixed>>
 */
function press_contacts(): array
{
    if (!table_exists('press_contacts')) {
        return [];
    }
    return db()->query('SELECT * FROM press_contacts ORDER BY sort_order ASC, id ASC')->fetchAll() ?: [];
}

/**
 * @return array<int,array<string,mixed>>
 */
function latest_press_releases(int $limit = 20): array
{
    if (!table_exists('press_releases')) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    return db()->query('SELECT * FROM press_releases ORDER BY release_date DESC, id DESC LIMIT ' . (int) $limit)->fetchAll() ?: [];
}
