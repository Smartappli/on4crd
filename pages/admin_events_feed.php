<?php
declare(strict_types=1);

require_permission('admin.access');
require_permission('events.manage');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=60');
header('X-Content-Type-Options: nosniff');

if (!table_exists('events')) {
    echo '[]';
    exit;
}

try {
    $rows = db()->query('SELECT id, title, start_at, end_at FROM events ORDER BY start_at ASC, id ASC')->fetchAll();
} catch (Throwable) {
    echo '[]';
    exit;
}

$calendarEvents = [];
foreach ($rows as $row) {
    $eventId = (int) ($row['id'] ?? 0);
    $title = trim((string) ($row['title'] ?? ''));
    $startAtRaw = (string) ($row['start_at'] ?? '');
    $endAtRaw = (string) ($row['end_at'] ?? '');
    if ($eventId <= 0 || $title === '' || $startAtRaw === '' || $endAtRaw === '') {
        continue;
    }

    try {
        $startAt = new DateTimeImmutable($startAtRaw);
        $endAt = new DateTimeImmutable($endAtRaw);
    } catch (Throwable) {
        continue;
    }

    $calendarEvents[] = [
        'id' => (string) $eventId,
        'title' => $title,
        'start' => $startAt->format(DateTimeInterface::ATOM),
        'end' => $endAt->format(DateTimeInterface::ATOM),
        'url' => route_url('admin_events', ['edit' => $eventId]),
    ];
}

try {
    echo json_encode($calendarEvents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (JsonException) {
    echo '[]';
}
