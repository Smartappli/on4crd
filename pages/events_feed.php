<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (!table_exists('events')) {
    echo '[]';
    exit;
}

try {
    $stmt = db()->query('SELECT id, slug, title, summary, description, start_at, end_at, location, external_url FROM events WHERE status = "published" ORDER BY start_at ASC, id ASC');
    $rows = $stmt->fetchAll();
} catch (Throwable) {
    echo '[]';
    exit;
}

$calendarEvents = [];
foreach ($rows as $event) {
    try {
        $startAt = new DateTimeImmutable((string) $event['start_at']);
        $endAt = new DateTimeImmutable((string) $event['end_at']);
    } catch (Throwable) {
        continue;
    }

    $summary = trim((string) ($event['summary'] ?? ''));
    if ($summary === '') {
        $summary = trim(strip_tags((string) ($event['description'] ?? '')));
    }

    $calendarEvents[] = [
        'id' => (string) ((int) $event['id']),
        'title' => (string) $event['title'],
        'start' => $startAt->format(DateTimeInterface::ATOM),
        'end' => $endAt->format(DateTimeInterface::ATOM),
        'url' => route_url('event_view', ['slug' => (string) $event['slug']]),
        'extendedProps' => [
            'summary' => $summary,
            'location' => trim((string) ($event['location'] ?? '')),
            'externalUrl' => trim((string) ($event['external_url'] ?? '')),
            'startLabel' => $startAt->format('d/m/Y H:i'),
            'endLabel' => $endAt->format('d/m/Y H:i'),
        ],
    ];
}

echo json_encode($calendarEvents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
