<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/admin_committee.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, $key);
}

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $stmt = db()->prepare('UPDATE members SET is_committee = ?, committee_role = ?, committee_bio = ?, committee_sort_order = ? WHERE id = ?');
        foreach ((array) ($_POST['members'] ?? []) as $memberId => $payload) {
            $stmt->execute([
                !empty($payload['is_committee']) ? 1 : 0,
                trim((string) ($payload['committee_role'] ?? '')),
                trim((string) ($payload['committee_bio'] ?? '')),
                (int) ($payload['committee_sort_order'] ?? 100),
                (int) $memberId,
            ]);
        }
        set_flash('success', (string) $t['updated']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_committee');
}

$rows = db()->query('SELECT id, callsign, full_name, is_committee, committee_role, committee_bio, committee_sort_order FROM members WHERE is_active = 1 ORDER BY callsign ASC')->fetchAll();

ob_start();
?>
<div class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <p><?= e((string) $t['intro']) ?></p>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="stack">
            <?php foreach ($rows as $row): ?>
                <section class="card muted-card">
                    <div class="row-between">
                        <strong><?= e((string) $row['callsign']) ?> — <?= e((string) $row['full_name']) ?></strong>
                        <label><input type="checkbox" name="members[<?= (int) $row['id'] ?>][is_committee]" value="1" <?= (int) $row['is_committee'] === 1 ? 'checked' : '' ?>> <?= e((string) $t['show_on_page']) ?></label>
                    </div>
                    <div class="form-grid">
                        <label><?= e((string) $t['role']) ?>
                            <input type="text" name="members[<?= (int) $row['id'] ?>][committee_role]" value="<?= e((string) $row['committee_role']) ?>">
                        </label>
                        <label><?= e((string) $t['sort_order']) ?>
                            <input type="number" name="members[<?= (int) $row['id'] ?>][committee_sort_order]" value="<?= e((string) $row['committee_sort_order']) ?>">
                        </label>
                        <label><?= e((string) $t['bio']) ?>
                            <textarea name="members[<?= (int) $row['id'] ?>][committee_bio]" rows="3"><?= e((string) $row['committee_bio']) ?></textarea>
                        </label>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <p><button class="button"><?= e((string) $t['save']) ?></button></p>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
