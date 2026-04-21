<?php
declare(strict_types=1);

if (!table_exists('events')) {
    echo render_layout('<div class="card"><h1>Événements</h1><p>L\'agenda n\'est pas encore disponible.</p></div>', 'Événements');
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

$monthDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $monthRaw . '-01 00:00:00');
if (!$monthDate instanceof DateTimeImmutable) {
    $monthDate = new DateTimeImmutable('first day of this month midnight');
}

$calendarStart = $monthDate->modify('monday this week');
$calendarEnd = $monthDate->modify('last day of this month')->modify('sunday this week');
$period = new DatePeriod($calendarStart, new DateInterval('P1D'), $calendarEnd->modify('+1 day'));

$eventsByDay = [];
$eventCards = [];
foreach ($rows as $event) {
    $startAt = new DateTimeImmutable((string) $event['start_at']);
    $dayKey = $startAt->format('Y-m-d');
    $eventsByDay[$dayKey][] = $event;

    $summary = trim((string) ($event['summary'] ?? ''));
    if ($summary === '') {
        $summary = trim(strip_tags((string) ($event['description'] ?? '')));
    }

    $eventCards[(int) $event['id']] = [
        'id' => (int) $event['id'],
        'title' => (string) $event['title'],
        'summary' => $summary,
        'startLabel' => $startAt->format('d/m/Y H:i'),
        'endLabel' => (new DateTimeImmutable((string) $event['end_at']))->format('d/m/Y H:i'),
        'location' => trim((string) ($event['location'] ?? '')),
        'detailUrl' => route_url('event_view', ['slug' => (string) $event['slug']]),
        'externalUrl' => trim((string) ($event['external_url'] ?? '')),
    ];
}

$defaultEvent = $eventCards !== [] ? reset($eventCards) : null;

$prevMonth = $monthDate->modify('-1 month')->format('Y-m');
$nextMonth = $monthDate->modify('+1 month')->format('Y-m');
$monthNames = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$monthLabel = ($monthNames[(int) $monthDate->format('n')] ?? $monthDate->format('F')) . ' ' . $monthDate->format('Y');

ob_start();
?>
<section class="events-layout">
    <article class="card events-calendar-card">
        <header class="events-toolbar">
            <div>
                <h1>Événements</h1>
                <p class="help">Calendrier du club avec export iCalendar (.ics).</p>
            </div>
            <div class="events-toolbar-actions">
                <a class="button secondary" href="<?= e(route_url('events', ['ym' => $prevMonth])) ?>">← Mois précédent</a>
                <a class="button secondary" href="<?= e(route_url('events', ['ym' => $nextMonth])) ?>">Mois suivant →</a>
                <a class="button" href="<?= e(route_url('events', ['format' => 'ics'])) ?>">Exporter ICS</a>
            </div>
        </header>

        <h2 class="events-current-month"><?= e($monthLabel) ?></h2>

        <div class="events-calendar-head" aria-hidden="true">
            <span>Lun</span><span>Mar</span><span>Mer</span><span>Jeu</span><span>Ven</span><span>Sam</span><span>Dim</span>
        </div>
        <div class="events-calendar-grid">
            <?php foreach ($period as $day):
                $dayKey = $day->format('Y-m-d');
                $inMonth = $day->format('m') === $monthDate->format('m');
                $dayEvents = $eventsByDay[$dayKey] ?? [];
                ?>
                <section class="events-day <?= $inMonth ? '' : 'is-outside' ?>">
                    <header>
                        <time datetime="<?= e($dayKey) ?>"><?= e($day->format('d')) ?></time>
                    </header>
                    <div class="events-day-list">
                        <?php foreach ($dayEvents as $event):
                            $eventId = (int) $event['id'];
                            $eventData = $eventCards[$eventId] ?? null;
                            if (!is_array($eventData)) {
                                continue;
                            }
                            ?>
                            <button
                                type="button"
                                class="event-chip"
                                data-event-id="<?= $eventId ?>"
                                data-title="<?= e($eventData['title']) ?>"
                                data-summary="<?= e($eventData['summary']) ?>"
                                data-start="<?= e($eventData['startLabel']) ?>"
                                data-end="<?= e($eventData['endLabel']) ?>"
                                data-location="<?= e($eventData['location']) ?>"
                                data-detail-url="<?= e($eventData['detailUrl']) ?>"
                                data-external-url="<?= e($eventData['externalUrl']) ?>"
                            >
                                <?= e($eventData['startLabel']) ?> · <?= e($eventData['title']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </article>

    <aside class="card events-detail-card" id="event-detail">
        <h2>Détail</h2>
        <?php if (is_array($defaultEvent)): ?>
            <h3 id="event-detail-title"><?= e($defaultEvent['title']) ?></h3>
            <p id="event-detail-summary"><?= e($defaultEvent['summary'] !== '' ? $defaultEvent['summary'] : 'Aucun résumé disponible.') ?></p>
            <dl>
                <dt>Début</dt><dd id="event-detail-start"><?= e($defaultEvent['startLabel']) ?></dd>
                <dt>Fin</dt><dd id="event-detail-end"><?= e($defaultEvent['endLabel']) ?></dd>
                <dt>Lieu</dt><dd id="event-detail-location"><?= e($defaultEvent['location'] !== '' ? $defaultEvent['location'] : 'À confirmer') ?></dd>
            </dl>
            <p class="events-detail-actions">
                <a id="event-detail-link" class="button" href="<?= e($defaultEvent['detailUrl']) ?>">Voir la fiche</a>
                <a id="event-detail-external" class="button secondary <?= $defaultEvent['externalUrl'] === '' ? 'is-hidden' : '' ?>" href="<?= e($defaultEvent['externalUrl']) ?>" target="_blank" rel="noopener noreferrer">Lien externe</a>
            </p>
        <?php else: ?>
            <p>Aucun événement publié pour le moment.</p>
        <?php endif; ?>
    </aside>
</section>
<?php
echo render_layout((string) ob_get_clean(), 'Événements');
