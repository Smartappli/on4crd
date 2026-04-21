<?php
declare(strict_types=1);

require_permission('admin.access');
require_permission('events.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException('Titre obligatoire.');
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
        set_flash('success', 'Événement enregistré.');
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

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= $edit ? 'Modifier' : 'Créer' ?> un événement</h1>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
            <label>Titre<input type="text" name="title" value="<?= e((string) ($edit['title'] ?? '')) ?>" required></label>
            <label>Slug<input type="text" name="slug" value="<?= e((string) ($edit['slug'] ?? '')) ?>"></label>
            <label>Résumé<textarea name="summary" rows="3"><?= e((string) ($edit['summary'] ?? '')) ?></textarea></label>
            <label>Description<textarea name="description" rows="6"><?= e((string) ($edit['description'] ?? '')) ?></textarea></label>
            <div class="grid-2">
                <label>Début<input type="datetime-local" name="start_at" value="<?= !empty($edit['start_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $edit['start_at']))) : '' ?>"></label>
                <label>Fin<input type="datetime-local" name="end_at" value="<?= !empty($edit['end_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $edit['end_at']))) : '' ?>"></label>
            </div>
            <div class="grid-2">
                <label>Lieu<input type="text" name="location" value="<?= e((string) ($edit['location'] ?? '')) ?>"></label>
                <label>URL externe<input type="text" name="external_url" value="<?= e((string) ($edit['external_url'] ?? '')) ?>"></label>
            </div>
            <div class="grid-2">
                <label>Type
                    <select name="kind">
                        <option value="club" <?= (($edit['kind'] ?? 'club') === 'club') ? 'selected' : '' ?>>Club</option>
                        <option value="contest" <?= (($edit['kind'] ?? '') === 'contest') ? 'selected' : '' ?>>Contest</option>
                    </select>
                </label>
                <label>Statut
                    <select name="status">
                        <option value="draft" <?= (($edit['status'] ?? 'published') === 'draft') ? 'selected' : '' ?>>Brouillon</option>
                        <option value="published" <?= (($edit['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>Publié</option>
                    </select>
                </label>
            </div>
            <button class="button">Enregistrer</button>
        </form>
    </section>
    <section class="card">
        <h2>Événements enregistrés</h2>
        <?php if ($rows === []): ?><p>Aucun événement.</p><?php else: ?><ul class="list-clean list-spaced"><?php foreach ($rows as $row): ?><li><a href="<?= e(route_url('admin_events', ['edit' => (int) $row['id']])) ?>"><?= e((string) $row['title']) ?></a><span class="help"><?= e(date('d/m/Y H:i', strtotime((string) $row['start_at']))) ?> — <?= e((string) $row['kind']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>

        <?php if ($calendarEvents !== []): ?>
            <hr>
            <h3>Vue calendrier (FullCalendar)</h3>
            <p class="help">Cliquez sur un événement pour ouvrir son édition.</p>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
            <div id="admin-events-calendar"></div>
            <script type="application/json" id="admin-events-calendar-data"><?= e(json_encode($calendarEvents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]') ?></script>
            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/fr.global.min.js"></script>
            <script>
                (() => {
                    const calendarEl = document.getElementById('admin-events-calendar');
                    const dataEl = document.getElementById('admin-events-calendar-data');
                    if (!calendarEl || !dataEl || !window.FullCalendar) {
                        return;
                    }

                    const events = JSON.parse(dataEl.textContent || '[]');
                    const calendar = new FullCalendar.Calendar(calendarEl, {
                        locale: 'fr',
                        firstDay: 1,
                        height: 'auto',
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,listMonth'
                        },
                        buttonText: {
                            today: 'Aujourd’hui',
                            month: 'Mois',
                            week: 'Semaine',
                            list: 'Liste'
                        },
                        events
                    });

                    calendar.render();
                })();
            </script>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Administration agenda');
