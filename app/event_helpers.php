<?php
declare(strict_types=1);

function event_publish_club_event(string $slug, string $title, int $startTs, string $descriptionText = '', string $location = ''): int
{
    $description = $descriptionText !== ''
        ? sanitize_rich_html('<p>' . nl2br(e($descriptionText), false) . '</p>')
        : '';
    $summary = $descriptionText !== '' ? mb_safe_strimwidth($descriptionText, 0, 280, '...') : '';
    $endTs = $startTs + (2 * 3600);

    db()->prepare('INSERT INTO events (slug, title, summary, description, kind, start_at, end_at, location, external_url, status) VALUES (?, ?, ?, ?, "club", ?, ?, ?, NULL, "published")')
        ->execute([
            $slug,
            $title,
            $summary,
            $description,
            date('Y-m-d H:i:s', $startTs),
            date('Y-m-d H:i:s', $endTs),
            $location !== '' ? $location : null,
        ]);
    cache_forget('home_next_event_v1');

    return (int) db()->lastInsertId();
}
