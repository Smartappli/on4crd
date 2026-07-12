<?php
declare(strict_types=1);

require_module_enabled('dashboard');
require_permission('admin.access');
require_permission('modules.manage');
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/admin_dashboard.php');
$i18n = i18n_expand_supported_locales($i18n);
$locale = current_locale();
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, $key);
}

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

db()->exec(
    "CREATE TABLE IF NOT EXISTS dashboard_widget_settings (
        widget_key VARCHAR(120) PRIMARY KEY,
        is_enabled TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$catalog = widget_catalog();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $stmt = db()->prepare('REPLACE INTO dashboard_widget_settings (widget_key, is_enabled) VALUES (?, ?)');
    foreach ($catalog as $widgetKey => $_meta) {
        $enabled = isset($_POST['widget_' . $widgetKey]) ? 1 : 0;
        $stmt->execute([(string) $widgetKey, $enabled]);
    }
    set_flash('success', (string) $t['ok_updated']);
    redirect('admin_dashboard');
}

$rows = db()->query('SELECT widget_key, is_enabled FROM dashboard_widget_settings')->fetchAll() ?: [];
$enabledMap = [];
foreach ($rows as $row) {
    $enabledMap[(string) $row['widget_key']] = (int) $row['is_enabled'] === 1;
}

$memberStats = ['total' => 0, 'active' => 0, 'committee' => 0];
if (table_exists('members')) {
    $memberStats['total'] = (int) (db()->query('SELECT COUNT(*) FROM members')->fetchColumn() ?: 0);
    $memberStats['active'] = (int) (db()->query('SELECT COUNT(*) FROM members WHERE is_active = 1')->fetchColumn() ?: 0);
    $memberStats['committee'] = (int) (db()->query('SELECT COUNT(*) FROM members WHERE is_committee = 1')->fetchColumn() ?: 0);
}

ob_start();
?>
<div class="stack admin-dashboard-module">
    <section class="card admin-dashboard-header">
        <div class="admin-section-head">
            <div>
                <h1><?= e((string) $t['title']) ?></h1>
                <p class="help"><?= e((string) $t['help']) ?></p>
            </div>
            <div class="admin-dashboard-actions">
                <a class="button secondary small" href="<?= e(route_url('admin_members')) ?>"><?= e((string) $t['members_title']) ?></a>
                <a class="button secondary small" href="<?= e(route_url('admin_permissions')) ?>"><?= e((string) $t['members_roles']) ?></a>
                <a class="button secondary small" href="<?= e(route_url('admin_committee')) ?>"><?= e((string) $t['members_committee_cta']) ?></a>
            </div>
        </div>
        <div class="admin-dashboard-stats" aria-label="<?= e((string) $t['members_title']) ?>">
            <article>
                <span><?= e((string) $t['members_total']) ?></span>
                <strong><?= (int) $memberStats['total'] ?></strong>
            </article>
            <article>
                <span><?= e((string) $t['members_active']) ?></span>
                <strong><?= (int) $memberStats['active'] ?></strong>
            </article>
            <article>
                <span><?= e((string) $t['members_committee']) ?></span>
                <strong><?= (int) $memberStats['committee'] ?></strong>
            </article>
        </div>
    </section>

    <section class="card admin-dashboard-widget-card">
        <div class="admin-section-head">
            <div>
                <h2><?= e((string) $t['layout']) ?></h2>
                <p class="help"><?= e((string) $t['help']) ?></p>
            </div>
            <span class="badge muted"><?= count($catalog) ?></span>
        </div>
        <form method="post" class="admin-widget-form" data-admin-dirty-track>
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="admin-widget-list">
                <?php foreach ($catalog as $widgetKey => $widget): ?>
                    <?php $widgetEnabled = ($enabledMap[$widgetKey] ?? true); ?>
                    <label class="admin-widget-toggle">
                        <input type="checkbox" name="widget_<?= e((string) $widgetKey) ?>" value="1" <?= $widgetEnabled ? 'checked' : '' ?>>
                        <span class="admin-widget-copy">
                            <strong><?= e((string) ($widget['title'] ?? $widgetKey)) ?></strong>
                            <span class="help"><?= e((string) ($widget['description'] ?? '')) ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="actions">
                <button class="button" type="submit"><?= e((string) $t['save']) ?></button>
            </div>
        </form>
    </section>
</div>

<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
