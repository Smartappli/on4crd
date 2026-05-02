<?php
declare(strict_types=1);

$locale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
$i18n = [
    'fr' => ['title' => 'Événements', 'agenda_unavailable' => "L'agenda n'est pas encore disponible.", 'export' => 'Exporter', 'calendar_load_error' => 'Impossible de charger le calendrier interactif.', 'event' => 'Événement', 'no_summary' => 'Aucun résumé disponible.', 'location_tbd' => 'À confirmer', 'today' => 'Aujourd’hui', 'month' => 'Mois', 'week' => 'Semaine', 'list' => 'Liste', 'detail' => 'Détail', 'start' => 'Début', 'end' => 'Fin', 'location' => 'Lieu', 'view_sheet' => 'Voir la fiche', 'external_link' => 'Lien externe', 'no_event' => 'Aucun événement publié pour le moment.'],
    'en' => ['title' => 'Events', 'agenda_unavailable' => 'The calendar is not available yet.', 'export' => 'Export', 'calendar_load_error' => 'Unable to load the interactive calendar.', 'event' => 'Event', 'no_summary' => 'No summary available.', 'location_tbd' => 'To be confirmed', 'today' => 'Today', 'month' => 'Month', 'week' => 'Week', 'list' => 'List', 'detail' => 'Details', 'start' => 'Start', 'end' => 'End', 'location' => 'Location', 'view_sheet' => 'View details', 'external_link' => 'External link', 'no_event' => 'No published event at the moment.'],
    'de' => ['title' => 'Veranstaltungen', 'agenda_unavailable' => 'Der Kalender ist noch nicht verfügbar.', 'export' => 'Exportieren', 'calendar_load_error' => 'Interaktiver Kalender konnte nicht geladen werden.', 'event' => 'Veranstaltung', 'no_summary' => 'Keine Zusammenfassung verfügbar.', 'location_tbd' => 'Noch offen', 'today' => 'Heute', 'month' => 'Monat', 'week' => 'Woche', 'list' => 'Liste', 'detail' => 'Details', 'start' => 'Beginn', 'end' => 'Ende', 'location' => 'Ort', 'view_sheet' => 'Details ansehen', 'external_link' => 'Externer Link', 'no_event' => 'Derzeit keine veröffentlichten Veranstaltungen.'],
    'nl' => ['title' => 'Evenementen', 'agenda_unavailable' => 'De agenda is nog niet beschikbaar.', 'export' => 'Exporteren', 'calendar_load_error' => 'Interactieve kalender kon niet geladen worden.', 'event' => 'Evenement', 'no_summary' => 'Geen samenvatting beschikbaar.', 'location_tbd' => 'Nog te bevestigen', 'today' => 'Vandaag', 'month' => 'Maand', 'week' => 'Week', 'list' => 'Lijst', 'detail' => 'Details', 'start' => 'Start', 'end' => 'Einde', 'location' => 'Locatie', 'view_sheet' => 'Bekijk detail', 'external_link' => 'Externe link', 'no_event' => 'Momenteel geen gepubliceerde evenementen.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

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
        'X-WR-CALNAME:Agenda ON4CRD',
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
    header('Content-Disposition: attachment; filename="on4crd-evenements.ics"');
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

ob_start();
?>
<section class="events-layout">
    <article class="card events-calendar-card">
        <header class="events-toolbar events-toolbar-right">
            <div class="events-toolbar-actions">
                <a class="button events-export-button" href="<?= e(route_url('events', ['format' => 'ics'])) ?>"><?= e($t['export']) ?></a>
            </div>
        </header>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/skeleton.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/theme.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/palette.css">
        <div id="events-calendar" class="fullcalendar-theme"></div>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/all.global.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/global.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/locales/fr.global.js"></script>
        <script nonce="<?= e(csp_nonce()) ?>">
            (() => {
                const calendarEl = document.getElementById('events-calendar');
                if (!calendarEl || !window.FullCalendar) {
                    calendarEl?.insertAdjacentHTML('beforeend', '<p class="help"><?= e($t['calendar_load_error']) ?></p>');
                    return;
                }
                const detail = {
                    title: document.getElementById('event-detail-title'),
                    summary: document.getElementById('event-detail-summary'),
                    start: document.getElementById('event-detail-start'),
                    end: document.getElementById('event-detail-end'),
                    location: document.getElementById('event-detail-location'),
                    link: document.getElementById('event-detail-link'),
                    external: document.getElementById('event-detail-external')
                };

                const updateDetails = (event) => {
                    const props = event.extendedProps || {};
                    if (detail.title) detail.title.textContent = event.title || <?= json_encode($t['event']) ?>;
                    if (detail.summary) detail.summary.textContent = props.summary || <?= json_encode($t['no_summary']) ?>;
                    if (detail.start) detail.start.textContent = props.startLabel || '';
                    if (detail.end) detail.end.textContent = props.endLabel || '';
                    if (detail.location) detail.location.textContent = props.location || <?= json_encode($t['location_tbd']) ?>;
                    if (detail.link) detail.link.setAttribute('href', event.url || '#');
                    if (detail.external) {
                        const externalUrl = props.externalUrl || '';
                        detail.external.setAttribute('href', externalUrl || '#');
                        detail.external.classList.toggle('is-hidden', !externalUrl);
                    }
                };
                const formatDate = (date) => {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                };

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: <?= json_encode($locale) ?>,
                    firstDay: 1,
                    height: 'auto',
                    initialView: <?= json_encode($calendarView) ?>,
                    initialDate: <?= json_encode($initialDate) ?>,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listMonth'
                    },
                    buttonText: {
                        today: <?= json_encode($t['today']) ?>,
                        month: <?= json_encode($t['month']) ?>,
                        week: <?= json_encode($t['week']) ?>,
                        list: <?= json_encode($t['list']) ?>
                    },
                    events: <?= json_encode(route_url('events_feed')) ?>,
                    eventClick(info) {
                        info.jsEvent.preventDefault();
                        updateDetails(info.event);
                    },
                    datesSet(info) {
                        const params = new URLSearchParams(window.location.search);
                        const viewMap = {
                            dayGridMonth: 'month',
                            timeGridWeek: 'week',
                            listMonth: 'list'
                        };
                        const route = params.get('route') || 'events';
                        const currentView = viewMap[info.view.type] || 'month';
                        const monthAnchor = info.view.currentStart instanceof Date ? info.view.currentStart : info.start;
                        const weekAnchor = info.start instanceof Date ? info.start : monthAnchor;
                        params.set('route', route);
                        params.set('view', currentView);
                        params.set('ym', formatDate(monthAnchor).slice(0, 7));
                        params.set('week', formatDate(weekAnchor));
                        history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    }
                });
                calendar.render();
            })();
        </script>
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
