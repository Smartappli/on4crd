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

$calendarConfig = [
    'locale' => $locale,
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException((string) $t['title_required']);
        }
        $slug = slugify((string) ($_POST['slug'] ?? $title));
        $startAt = str_replace('T', ' ', (string) ($_POST['start_at'] ?? '')) . ':00';
        $endAt = str_replace('T', ' ', (string) ($_POST['end_at'] ?? '')) . ':00';
        $params = [
            $slug,
            $title,
            trim((string) ($_POST['summary'] ?? '')),
            sanitize_rich_html((string) ($_POST['description'] ?? '')),
            (string) ($_POST['kind'] ?? 'club'),
            $startAt,
            $endAt,
            trim((string) ($_POST['location'] ?? '')),
            normalize_http_url((string) ($_POST['external_url'] ?? ''), true),
            (string) ($_POST['status'] ?? 'published'),
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
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/skeleton.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/theme.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/palette.css">
            <div id="admin-events-calendar" class="fullcalendar-theme" data-calendar-config="<?= e(json_encode($calendarConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"></div>
            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/all.global.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/themes/classic/global.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.0.0-rc.2/locales/<?= e($locale) ?>.global.js"></script>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);

