<?php
declare(strict_types=1);

require_permission('admin.access');
require_permission('events.manage');
$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/admin_events.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, (string) $key);
}
$calendarLocale = fullcalendar_locale_code($locale);
$calendarLocaleAsset = fullcalendar_locale_asset_url($locale);

$calendarConfig = [
    'locale' => $calendarLocale,
    'eventsUrl' => route_url('admin_events_feed'),
    'buttonText' => [
        'today' => (string) $t['today'],
        'month' => (string) $t['month'],
        'week' => (string) $t['week'],
        'list' => (string) $t['list'],
    ],
];

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

function admin_event_slug_base(string $value, int $maxLength = 190): string
{
    $base = slugify($value);
    if ($base === '' || $base === 'n-a') {
        $base = 'event';
    }
    if (strlen($base) > $maxLength) {
        $base = substr($base, 0, $maxLength);
    }

    $base = trim($base, '-');
    return $base !== '' ? $base : 'event';
}

function admin_event_slug_candidate(string $base, int $suffix = 0, int $maxLength = 190): string
{
    $base = admin_event_slug_base($base, $maxLength);
    if ($suffix <= 1) {
        return $base;
    }

    $suffixText = '-' . $suffix;
    $prefixLength = max(1, $maxLength - strlen($suffixText));
    $prefix = rtrim(substr($base, 0, $prefixLength), '-');
    if ($prefix === '') {
        $prefix = substr('event', 0, $prefixLength);
    }

    return $prefix . $suffixText;
}

function admin_event_unique_slug(string $value, int $ignoreId = 0, int $maxLength = 190): string
{
    $base = admin_event_slug_base($value, $maxLength);
    $suffix = 1;
    do {
        $candidate = admin_event_slug_candidate($base, $suffix, $maxLength);
        $stmt = db()->prepare('SELECT id FROM events WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->execute([$candidate, max(0, $ignoreId)]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
        $suffix++;
    } while ($suffix < 10000);

    throw new RuntimeException('Impossible de générer un slug événement unique.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException((string) $t['title_required']);
        }
        if ($id > 0) {
            $eventStmt = db()->prepare('SELECT id FROM events WHERE id = ? LIMIT 1');
            $eventStmt->execute([$id]);
            if (!$eventStmt->fetchColumn()) {
                throw new RuntimeException((string) $t['no_event']);
            }
        }
        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $slug = admin_event_unique_slug($slugInput !== '' ? $slugInput : $title, $id);
        $startRaw = trim((string) ($_POST['start_at'] ?? ''));
        $endRaw = trim((string) ($_POST['end_at'] ?? ''));
        $startTs = $startRaw !== '' ? strtotime($startRaw) : false;
        $endTs = $endRaw !== '' ? strtotime($endRaw) : false;
        if ($startTs === false || $endTs === false || $endTs <= $startTs) {
            throw new RuntimeException((string) $t['title_required']);
        }
        $kind = (string) ($_POST['kind'] ?? 'club');
        if (!in_array($kind, ['club', 'contest'], true)) {
            $kind = 'club';
        }
        $status = (string) ($_POST['status'] ?? 'published');
        if (!in_array($status, ['draft', 'published'], true)) {
            $status = 'draft';
        }
        $externalUrlInput = trim((string) ($_POST['external_url'] ?? ''));
        $params = [
            $slug,
            $title,
            trim((string) ($_POST['summary'] ?? '')),
            sanitize_rich_html((string) ($_POST['description'] ?? '')),
            $kind,
            date('Y-m-d H:i:s', $startTs),
            date('Y-m-d H:i:s', $endTs),
            trim((string) ($_POST['location'] ?? '')),
            $externalUrlInput !== '' ? normalize_http_url($externalUrlInput, true) : null,
            $status,
        ];
        if ($id > 0) {
            db()->prepare('UPDATE events SET slug = ?, title = ?, summary = ?, description = ?, kind = ?, start_at = ?, end_at = ?, location = ?, external_url = ?, status = ? WHERE id = ?')
                ->execute([...$params, $id]);
        } else {
            db()->prepare('INSERT INTO events (slug, title, summary, description, kind, start_at, end_at, location, external_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute($params);
        }
        set_flash('success', (string) $t['event_saved']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_events');
}

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}
$rows = table_exists('events') ? db()->query('SELECT * FROM events ORDER BY start_at DESC, id DESC')->fetchAll() : [];

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= $edit ? e((string) $t['edit']) : e((string) $t['create']) ?> <?= e((string) $t['form_title']) ?></h1>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
            <label><?= e((string) $t['title']) ?><input type="text" name="title" value="<?= e((string) ($edit['title'] ?? '')) ?>" required></label>
            <label><?= e((string) $t['slug']) ?><input type="text" name="slug" value="<?= e((string) ($edit['slug'] ?? '')) ?>"></label>
            <label><?= e((string) $t['summary']) ?><textarea name="summary" rows="3"><?= e((string) ($edit['summary'] ?? '')) ?></textarea></label>
            <label><?= e((string) $t['description']) ?><textarea name="description" rows="6"><?= e((string) ($edit['description'] ?? '')) ?></textarea></label>
            <div class="grid-2">
                <label><?= e((string) $t['start']) ?><input type="datetime-local" name="start_at" value="<?= !empty($edit['start_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $edit['start_at']))) : '' ?>"></label>
                <label><?= e((string) $t['end']) ?><input type="datetime-local" name="end_at" value="<?= !empty($edit['end_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $edit['end_at']))) : '' ?>"></label>
            </div>
            <div class="grid-2">
                <label><?= e((string) $t['location']) ?><input type="text" name="location" value="<?= e((string) ($edit['location'] ?? '')) ?>"></label>
                <label><?= e((string) $t['external_url']) ?><input type="text" name="external_url" value="<?= e((string) ($edit['external_url'] ?? '')) ?>"></label>
            </div>
            <div class="grid-2">
                <label><?= e((string) $t['type']) ?>
                    <select name="kind">
                        <option value="club" <?= (($edit['kind'] ?? 'club') === 'club') ? 'selected' : '' ?>><?= e((string) $t['type_club']) ?></option>
                        <option value="contest" <?= (($edit['kind'] ?? '') === 'contest') ? 'selected' : '' ?>><?= e((string) $t['type_contest']) ?></option>
                    </select>
                </label>
                <label><?= e((string) $t['status']) ?>
                    <select name="status">
                        <option value="draft" <?= (($edit['status'] ?? 'published') === 'draft') ? 'selected' : '' ?>><?= e((string) $t['draft']) ?></option>
                        <option value="published" <?= (($edit['status'] ?? 'published') === 'published') ? 'selected' : '' ?>><?= e((string) $t['published']) ?></option>
                    </select>
                </label>
            </div>
            <button class="button"><?= e((string) $t['save']) ?></button>
        </form>
    </section>
    <section class="card">
        <h2><?= e((string) $t['saved_events']) ?></h2>
        <?php if ($rows === []): ?><p><?= e((string) $t['no_event']) ?></p><?php else: ?><ul class="list-clean list-spaced"><?php foreach ($rows as $row): ?><li><a href="<?= e(route_url('admin_events', ['edit' => (int) $row['id']])) ?>"><?= e((string) $row['title']) ?></a><span class="help"><?= e(date('d/m/Y H:i', strtotime((string) $row['start_at']))) ?> â€” <?= e((string) $row['kind']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>

        <?php if ($rows !== []): ?>
            <hr>
            <h3><?= e((string) $t['calendar_view']) ?></h3>
            <p class="help"><?= e((string) $t['calendar_help']) ?></p>
            <link rel="stylesheet" href="<?= e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/skeleton.css')) ?>">
            <link rel="stylesheet" href="<?= e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/themes/classic/theme.css')) ?>">
            <link rel="stylesheet" href="<?= e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/themes/classic/palette.css')) ?>">
            <div id="admin-events-calendar" class="fullcalendar-theme" data-calendar-config="<?= e(json_encode($calendarConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"></div>
            <script src="<?= e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/all.global.js')) ?>"></script>
            <script src="<?= e(asset_url('assets/vendor/fullcalendar/7.0.0-rc.2/themes/classic/global.js')) ?>"></script>
            <script src="<?= e($calendarLocaleAsset) ?>"></script>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);

