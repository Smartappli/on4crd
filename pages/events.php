<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/event_helpers.php';

$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/events.php');
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
$calendarLocale = fullcalendar_locale_code($locale);
$calendarLocaleAsset = fullcalendar_locale_asset_url($locale);

if (!table_exists('events')) {
    echo render_layout('<div class="card"><h1>' . e($t['title']) . '</h1><p>' . e($t['agenda_unavailable']) . '</p></div>', $t['title']);
    return;
}

if (!function_exists('events_public_unique_slug')) {
function events_public_unique_slug(string $title, int $ignoreId = 0, int $maxLength = 190): string
{
    $base = slugify($title);
    if ($base === '' || $base === 'n-a') {
        $base = 'event';
    }
    if (strlen($base) > $maxLength) {
        $base = rtrim(substr($base, 0, $maxLength), '-');
    }
    $base = $base !== '' ? $base : 'event';
    $suffix = 1;
    do {
        $suffixText = $suffix <= 1 ? '' : '-' . $suffix;
        $candidate = $suffixText === ''
            ? $base
            : rtrim(substr($base, 0, max(1, $maxLength - strlen($suffixText))), '-') . $suffixText;
        $stmt = db()->prepare('SELECT id FROM events WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->execute([$candidate, max(0, $ignoreId)]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
        $suffix++;
    } while ($suffix < 10000);

    throw new RuntimeException('Impossible de générer un slug événement unique.');
}
}

$user = current_user();
$canManageEvents = has_permission('events.manage');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'propose_event') {
            $user = require_login(route_url('events'));
            $proposalTitle = (string) ($_POST['proposal_title'] ?? '');
            $proposalContact = (string) ($_POST['proposal_contact'] ?? '');
            $proposalSummary = content_proposal_details_text([
                (string) $t['propose_event_datetime_label'] => (string) ($_POST['proposal_datetime'] ?? ''),
                (string) $t['propose_event_location_label'] => (string) ($_POST['proposal_location'] ?? ''),
                (string) $t['propose_event_description_label'] => (string) ($_POST['proposal_description'] ?? ''),
            ]);
            $autoPublish = has_permission('events.manage');
            if ($autoPublish) {
                $title = content_proposal_clean_single_line($proposalTitle, 160);
                $dateRaw = content_proposal_clean_single_line((string) ($_POST['proposal_datetime'] ?? ''), 160);
                $location = content_proposal_clean_single_line((string) ($_POST['proposal_location'] ?? ''), 190);
                $descriptionText = content_proposal_clean_multiline((string) ($_POST['proposal_description'] ?? ''), 1600);
                $startTs = $dateRaw !== '' ? strtotime($dateRaw) : false;
                if ($title === '' || $startTs === false) {
                    throw new RuntimeException((string) $t['invalid']);
                }
                $slug = events_public_unique_slug($title);
                event_publish_club_event($slug, $title, $startTs, $descriptionText, $location);
                set_flash('success', (string) $t['event_published_direct']);
                redirect_url(route_url('event_view', ['slug' => $slug]));
            }
            $proposalId = content_proposal_create((int) $user['id'], 'events', 'content', $proposalTitle, $proposalSummary, $proposalContact);
            content_proposal_notify_site((string) $t['propose_event_subject'], [
                'area' => 'events',
                'proposal_type' => 'content',
                'title' => content_proposal_clean_single_line($proposalTitle, 190),
                'summary' => $proposalSummary,
                'contact' => content_proposal_clean_single_line($proposalContact, 220),
                'source_ref' => 'content_proposals#' . $proposalId,
            ]);
            set_flash('success', (string) $t['proposal_recorded']);
            redirect('my_requests');
        }

        throw new RuntimeException((string) $t['invalid']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect_url(route_url('events'));
    }
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

$hasRequestedMonth = isset($_GET['ym']);
$hasRequestedWeek = isset($_GET['week']);
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
$nextEvent = null;
$now = new DateTimeImmutable('now');
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
    $externalUrl = sanitize_href_attribute((string) ($event['external_url'] ?? '')) ?? '';
    $imageUrl = first_image_src_from_html((string) ($event['description'] ?? ''));

    $eventCard = [
        'id' => (int) $event['id'],
        'title' => (string) $event['title'],
        'summary' => $summary,
        'startDate' => $startAt->format('Y-m-d'),
        'startLabel' => $startAt->format('d/m/Y H:i'),
        'endLabel' => $endAt->format('d/m/Y H:i'),
        'location' => trim((string) ($event['location'] ?? '')),
        'externalUrl' => $externalUrl,
        'imageUrl' => $imageUrl,
        'url' => route_url('event_view', ['slug' => (string) $event['slug']]),
    ];

    $eventCards[(int) $event['id']] = $eventCard;
    if ($nextEvent === null && $endAt >= $now) {
        $nextEvent = $eventCard;
    }
}

$calendarView = match ($view) {
    'week' => 'timeGridWeek',
    'list' => 'listMonth',
    default => 'dayGridMonth',
};
$initialDate = $view === 'week' ? $weekDate->format('Y-m-d') : $monthDate->format('Y-m-d');
if (!$hasRequestedMonth && !$hasRequestedWeek && is_array($nextEvent)) {
    $initialDate = (string) $nextEvent['startDate'];
}
$calendarConfig = [
    'locale' => $calendarLocale,
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
$contactEmail = site_contact_email();
$proposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode((string) $t['propose_event_subject'])
    . '&body=' . rawurlencode((string) $t['propose_event_body']);
$proposalContactDefault = '';
if ($user !== null) {
    $proposalContactDefault = trim((string) ($user['email'] ?? ''));
    if ($proposalContactDefault === '') {
        $proposalContactDefault = trim((string) ($user['callsign'] ?? ''));
    }
}
$eventProposalLabel = (string) $t['propose_event'];

ob_start();
?>
<section class="page-hero">
    <div>
        <p class="eyebrow events-hero-title"><?= e((string) $t['module_public']) ?></p>
        <h1><?= e((string) $t['calendar_name']) ?></h1>
        <p class="help"><?= e($t['detail']) ?>, <?= e($t['month']) ?>, <?= e($t['week']) ?>, <?= e($t['list']) ?></p>
    </div>
    <div class="events-hero-actions">
        <a class="button secondary" href="<?= e($proposalUrl) ?>" data-event-proposal-open aria-haspopup="dialog" aria-controls="events-proposal-dialog"><?= e($eventProposalLabel) ?></a>
        <?php if ($canManageEvents): ?>
            <a class="button secondary" href="<?= e(route_url('admin_events')) ?>">Administer</a>
        <?php endif; ?>
        <a class="button" href="<?= e(route_url('events', ['format' => 'ics'])) ?>"><?= e($t['export']) ?></a>
    </div>
</section>

<dialog class="events-proposal-dialog" id="events-proposal-dialog" aria-labelledby="events-proposal-title">
    <div class="events-proposal-dialog-card">
        <div class="events-proposal-dialog-header module-dialog-header">
            <div>
                <p class="events-hero-title"><?= e($t['calendar_name']) ?></p>
                <h2 id="events-proposal-title"><?= e($eventProposalLabel) ?></h2>
                <p class="help"><?= e($canManageEvents ? $t['propose_event_direct_help'] : $t['propose_event_intro']) ?></p>
            </div>
            <button class="events-proposal-dialog-close module-dialog-close" type="button" data-event-proposal-close aria-label="<?= e($t['propose_event_close']) ?>">&times;</button>
        </div>
        <form class="events-proposal-form module-dialog-form" method="<?= $user !== null ? 'post' : 'dialog' ?>" data-event-proposal-form data-event-proposal-recipient="<?= e($contactEmail) ?>" data-event-proposal-subject="<?= e($t['propose_event_subject']) ?>" data-event-proposal-intro="<?= e($t['propose_event_body_intro']) ?>">
            <?php if ($user !== null): ?>
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="propose_event">
            <?php endif; ?>
            <label>
                <span><?= e($t['propose_event_title_label']) ?></span>
                <input type="text" name="proposal_title" maxlength="160" required>
            </label>
            <div class="events-proposal-form-grid">
                <label>
                    <span><?= e($t['propose_event_datetime_label']) ?></span>
                    <input type="<?= $canManageEvents ? 'datetime-local' : 'text' ?>" name="proposal_datetime" maxlength="160">
                </label>
                <label>
                    <span><?= e($t['propose_event_location_label']) ?></span>
                    <input type="text" name="proposal_location" maxlength="160">
                </label>
            </div>
            <label>
                <span><?= e($t['propose_event_description_label']) ?></span>
                <textarea name="proposal_description" rows="5" maxlength="1600"></textarea>
            </label>
            <label>
                <span><?= e($t['propose_event_contact_label']) ?></span>
                <input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required>
            </label>
            <div class="events-proposal-dialog-actions module-dialog-actions">
                <button class="button" type="submit"><?= e($canManageEvents ? $t['publish_event'] : $t['propose_event_submit']) ?></button>
                <button class="button secondary" type="button" data-event-proposal-close><?= e($t['propose_event_cancel']) ?></button>
            </div>
        </form>
    </div>
</dialog>

<section class="events-layout">
    <article class="card events-calendar-card">
        <link rel="stylesheet" href="<?= e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/skeleton.css')) ?>">
        <link rel="stylesheet" href="<?= e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/themes/classic/theme.css')) ?>">
        <link rel="stylesheet" href="<?= e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/themes/classic/palette.css')) ?>">
        <div id="events-calendar" class="fullcalendar-theme" data-calendar-config="<?= e(json_encode($calendarConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"></div>
        <script src="<?= e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/all.global.js')) ?>" defer></script>
        <script src="<?= e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/themes/classic/global.js')) ?>" defer></script>
        <script src="<?= e($calendarLocaleAsset) ?>" defer></script>
    </article>

    <aside class="card events-side-card">
        <section class="events-next-card" id="events-next-event">
            <h2><?= e((string) $t['next_event_title']) ?></h2>
            <?php if (is_array($nextEvent)): ?>
                <?php if ($nextEvent['imageUrl'] !== ''): ?>
                    <figure class="events-preview-image">
                        <img src="<?= e((string) $nextEvent['imageUrl']) ?>" alt="<?= e((string) $nextEvent['title']) ?>" loading="lazy" decoding="async">
                    </figure>
                <?php endif; ?>
                <h3><?= e((string) $nextEvent['title']) ?></h3>
                <p><?= e($nextEvent['summary'] !== '' ? (string) $nextEvent['summary'] : $t['no_summary']) ?></p>
                <p><a class="button secondary" href="<?= e((string) $nextEvent['url']) ?>"><?= e($t['detail']) ?></a></p>
                <dl>
                    <dt><?= e($t['start']) ?></dt><dd><?= e((string) $nextEvent['startLabel']) ?></dd>
                    <dt><?= e($t['end']) ?></dt><dd><?= e((string) $nextEvent['endLabel']) ?></dd>
                    <dt><?= e($t['location']) ?></dt><dd><?= e($nextEvent['location'] !== '' ? (string) $nextEvent['location'] : $t['location_tbd']) ?></dd>
                </dl>
            <?php else: ?>
                <p><?= e($t['no_event']) ?></p>
            <?php endif; ?>
        </section>

        <section class="events-detail-card is-hidden" id="event-detail" hidden>
            <h2><?= e($t['detail']) ?></h2>
            <figure class="events-preview-image is-hidden" id="event-detail-image-wrap">
                <img id="event-detail-image" src="" alt="" loading="lazy" decoding="async">
            </figure>
            <h3 id="event-detail-title"></h3>
            <p id="event-detail-summary"></p>
            <dl>
                <dt><?= e($t['start']) ?></dt><dd id="event-detail-start"></dd>
                <dt><?= e($t['end']) ?></dt><dd id="event-detail-end"></dd>
                <dt><?= e($t['location']) ?></dt><dd id="event-detail-location"></dd>
            </dl>
            <p class="events-detail-actions is-hidden" id="event-detail-actions">
                <a id="event-detail-external" class="button secondary is-hidden" href="#" target="_blank" rel="noopener noreferrer"><?= e($t['external_link']) ?></a>
            </p>
        </section>
    </aside>
</section>
<?php
echo render_layout((string) ob_get_clean(), $t['title']);

