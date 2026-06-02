<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/events.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $pool = [];
    foreach ($i18n as $lang => $translations) {
        if (isset($translations[$key]) && is_string($translations[$key])) {
            $pool[$lang] = $translations[$key];
        }
    }
    $t[$key] = i18n_localized_value($pool, $locale, 'fr');
}

if (!table_exists('events')) {
    echo render_layout('<div class="card"><h1>' . e($t['title']) . '</h1><p>' . e($t['agenda_unavailable']) . '</p></div>', $t['title']);
    return;
}

$rows = [];
try {
    $stmt = db()->query('SELECT id, slug, title, summary, description, start_at, end_at, location, external_url FROM events WHERE status = "published" ORDER BY start_at ASC, id ASC');
    $rows = $stmt->fetchAll();
} catch (Throwable) {
    $rows = [];
}

$icalEscape = static function (string $value): string {
    $value = str_replace("\r", '', $value);
    $value = str_replace("\n", '\\n', $value);
    $value = str_replace([',', ';'], ['\\,', '\\;'], $value);
    return $value;
};

if (strtolower((string) ($_GET['format'] ?? '')) === 'ics') {
    $host = parse_url(base_url('/'), PHP_URL_HOST) ?: 'on4crd.local';
    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//ON4CRD//Agenda//FR',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-WR-CALNAME:' . $icalEscape((string) $t['calendar_name']),
    ];

    foreach ($rows as $event) {
        $startTs = strtotime((string) $event['start_at']);
        $endTs = strtotime((string) $event['end_at']);
        if ($startTs === false || $endTs === false) {
            continue;
        }

        $description = trim((string) ($event['summary'] ?? ''));
        if ($description === '') {
            $description = trim(strip_tags((string) ($event['description'] ?? '')));
        }
        $eventUrl = trim((string) ($event['external_url'] ?? ''));
        if ($eventUrl === '') {
            $eventUrl = route_url('event_view', ['slug' => (string) $event['slug']]);
        }

        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:event-' . (int) $event['id'] . '@' . $host;
        $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
        $lines[] = 'DTSTART:' . gmdate('Ymd\THis\Z', $startTs);
        $lines[] = 'DTEND:' . gmdate('Ymd\THis\Z', $endTs);
        $lines[] = 'SUMMARY:' . $icalEscape((string) $event['title']);
        if ($description !== '') {
            $lines[] = 'DESCRIPTION:' . $icalEscape($description);
        }
        $location = trim((string) ($event['location'] ?? ''));
        if ($location !== '') {
            $lines[] = 'LOCATION:' . $icalEscape($location);
        }
        if ($eventUrl !== '') {
            $lines[] = 'URL:' . $icalEscape($eventUrl);
        }
        $lines[] = 'END:VEVENT';
    }

    $lines[] = 'END:VCALENDAR';

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', (string) $t['ics_filename']) . '"');
    echo implode("\r\n", $lines) . "\r\n";
    exit;
}

$monthRaw = (string) ($_GET['ym'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $monthRaw)) {
    $monthRaw = date('Y-m');
}
$requestedView = (string) ($_GET['view'] ?? 'month');
/** @var 'month'|'week'|'list' $view */
$view = in_array($requestedView, ['month', 'week', 'list'], true)
    ? $requestedView
    : 'month';

$monthDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $monthRaw . '-01 00:00:00');
if (!$monthDate instanceof DateTimeImmutable) {
    $monthDate = new DateTimeImmutable('first day of this month midnight');
}

$weekRaw = (string) ($_GET['week'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekRaw)) {
    $weekRaw = date('Y-m-d');
}
$weekDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $weekRaw . ' 00:00:00');
if (!$weekDate instanceof DateTimeImmutable) {
    $weekDate = new DateTimeImmutable('today');
}

$eventCards = [];
foreach ($rows as $event) {
    $startAt = new DateTimeImmutable((string) $event['start_at']);
    $endAt = new DateTimeImmutable((string) $event['end_at']);

    $summary = trim((string) ($event['summary'] ?? ''));
    if ($summary === '') {
        $summary = trim(strip_tags((string) ($event['description'] ?? '')));
    }

    $eventCards[(int) $event['id']] = [
        'id' => (int) $event['id'],
        'title' => (string) $event['title'],
        'summary' => $summary,
        'startLabel' => $startAt->format('d/m/Y H:i'),
        'endLabel' => $endAt->format('d/m/Y H:i'),
        'location' => trim((string) ($event['location'] ?? '')),
        'detailUrl' => route_url('event_view', ['slug' => (string) $event['slug']]),
        'externalUrl' => trim((string) ($event['external_url'] ?? '')),
    ];

}

$defaultEvent = $eventCards !== [] ? reset($eventCards) : null;
$calendarView = match ($view) {
    'week' => 'timeGridWeek',
    'list' => 'listMonth',
    default => 'dayGridMonth',
};
$initialDate = $view === 'week' ? $weekDate->format('Y-m-d') : $monthDate->format('Y-m-d');
$calendarConfig = [
    'locale' => $locale,
    'initialView' => $calendarView,
    'initialDate' => $initialDate,
    'eventsUrl' => route_url('events_feed'),
    'eventLabel' => $t['event'],
    'noSummary' => $t['no_summary'],
    'locationTbd' => $t['location_tbd'],
    'loadError' => $t['calendar_load_error'],
    'buttonText' => [
        'today' => $t['today'],
        'month' => $t['month'],
        'week' => $t['week'],
        'list' => $t['list'],
    ],
];
$proposalUrl = 'mailto:on4crd@gmail.com?subject=' . rawurlencode((string) $t['propose_event_subject'])
    . '&body=' . rawurlencode((string) $t['propose_event_body']);

ob_start();
?>
<section class="page-hero">
    <div>
        <p class="eyebrow events-hero-title"><?= e($t['calendar_name']) ?></p>
        <h1><?= e('Agenda ON4CRD') ?></h1>
        <p class="help"><?= e($t['detail']) ?>, <?= e($t['month']) ?>, <?= e($t['week']) ?>, <?= e($t['list']) ?></p>
    </div>
    <div class="events-hero-actions">
        <a class="button secondary" href="<?= e($proposalUrl) ?>"><?= e($t['propose_event']) ?></a>
        <a class="button" href="<?= e(route_url('events', ['format' => 'ics'])) ?>"><?= e($t['export']) ?></a>
    </div>
</section>

<section class="events-layout">
    <article class="card events-calendar-card">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/skeleton.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/theme.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/palette.css">
        <div id="events-calendar" class="fullcalendar-theme" data-calendar-config="<?= e(json_encode($calendarConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"></div>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/all.global.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/global.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/locales/<?= e($locale) ?>.global.js"></script>
    </article>

    <aside class="card events-detail-card" id="event-detail">
        <h2><?= e($t['detail']) ?></h2>
        <?php if (is_array($defaultEvent)): ?>
            <h3 id="event-detail-title"><?= e($defaultEvent['title']) ?></h3>
            <p id="event-detail-summary"><?= e($defaultEvent['summary'] !== '' ? $defaultEvent['summary'] : $t['no_summary']) ?></p>
            <dl>
                <dt><?= e($t['start']) ?></dt><dd id="event-detail-start"><?= e($defaultEvent['startLabel']) ?></dd>
                <dt><?= e($t['end']) ?></dt><dd id="event-detail-end"><?= e($defaultEvent['endLabel']) ?></dd>
                <dt><?= e($t['location']) ?></dt><dd id="event-detail-location"><?= e($defaultEvent['location'] !== '' ? $defaultEvent['location'] : $t['location_tbd']) ?></dd>
            </dl>
            <p class="events-detail-actions">
                <a id="event-detail-link" class="button" href="<?= e($defaultEvent['detailUrl']) ?>"><?= e($t['view_sheet']) ?></a>
                <a id="event-detail-external" class="button secondary <?= $defaultEvent['externalUrl'] === '' ? 'is-hidden' : '' ?>" href="<?= e($defaultEvent['externalUrl']) ?>" target="_blank" rel="noopener noreferrer"><?= e($t['external_link']) ?></a>
            </p>
        <?php else: ?>
            <p><?= e($t['no_event']) ?></p>
        <?php endif; ?>
    </aside>
</section>
<?php
echo render_layout((string) ob_get_clean(), $t['title']);

