<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/admin_live_feeds.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, $key);
}
$wizardNavigation = i18n_domain_locale('search', $locale);

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        foreach ((array) ($_POST['feeds'] ?? []) as $id => $payload) {
            $url = trim((string) ($payload['url'] ?? ''));
            $validatedUrl = $url !== '' ? validate_remote_feed_url($url) : null;
            db()->prepare('UPDATE live_feeds SET label = ?, url = ?, parser = ?, cache_ttl = ?, refresh_seconds = ?, is_enabled = ?, notes = ? WHERE id = ?')->execute([
                trim((string) ($payload['label'] ?? '')),
                $validatedUrl,
                trim((string) ($payload['parser'] ?? 'json')),
                max(60, (int) ($payload['cache_ttl'] ?? 900)),
                max(60, (int) ($payload['refresh_seconds'] ?? 900)),
                !empty($payload['is_enabled']) ? 1 : 0,
                trim((string) ($payload['notes'] ?? '')),
                (int) $id,
            ]);
        }
        set_flash('success', (string) $t['updated']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_live_feeds');
}

$rows = table_exists('live_feeds') ? db()->query('SELECT * FROM live_feeds ORDER BY code ASC')->fetchAll() : [];

ob_start();
?>
<div class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <p><?= e((string) $t['intro']) ?></p>
    <form method="post" data-admin-dirty-track data-admin-wizard data-admin-wizard-label="<?= e((string) $t['title']) ?>" data-admin-wizard-previous-label="<?= e((string) $wizardNavigation['previous']) ?>" data-admin-wizard-next-label="<?= e((string) $wizardNavigation['next']) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="stack">
            <?php foreach ($rows as $row): ?>
                <section class="card muted-card" data-admin-wizard-step data-admin-wizard-title="<?= e((string) $row['code']) ?>">
                    <div class="row-between">
                        <strong><?= e((string) $row['code']) ?></strong>
                        <label><input type="checkbox" name="feeds[<?= (int) $row['id'] ?>][is_enabled]" value="1" <?= (int) $row['is_enabled'] === 1 ? 'checked' : '' ?>> <?= e((string) $t['active']) ?></label>
                    </div>
                    <div class="form-grid">
                        <label><?= e((string) $t['label']) ?><input type="text" name="feeds[<?= (int) $row['id'] ?>][label]" value="<?= e((string) $row['label']) ?>"></label>
                        <label><?= e((string) $t['url']) ?><input type="text" name="feeds[<?= (int) $row['id'] ?>][url]" value="<?= e((string) $row['url']) ?>"></label>
                        <label><?= e((string) $t['parser']) ?><input type="text" name="feeds[<?= (int) $row['id'] ?>][parser]" value="<?= e((string) $row['parser']) ?>"></label>
                        <label><?= e((string) $t['cache_ttl']) ?><input type="number" name="feeds[<?= (int) $row['id'] ?>][cache_ttl]" value="<?= (int) $row['cache_ttl'] ?>"></label>
                        <label><?= e((string) $t['refresh']) ?><input type="number" name="feeds[<?= (int) $row['id'] ?>][refresh_seconds]" value="<?= (int) $row['refresh_seconds'] ?>"></label>
                        <label><?= e((string) $t['notes']) ?><textarea name="feeds[<?= (int) $row['id'] ?>][notes]" rows="3"><?= e((string) $row['notes']) ?></textarea></label>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <p><button class="button"><?= e((string) $t['save']) ?></button></p>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
