<?php
declare(strict_types=1);

require_permission('admin.access');

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
    set_flash('success', 'Widgets du dashboard mis à jour.');
    redirect('admin_dashboard');
}

$rows = db()->query('SELECT widget_key, is_enabled FROM dashboard_widget_settings')->fetchAll() ?: [];
$enabledMap = [];
foreach ($rows as $row) {
    $enabledMap[(string) $row['widget_key']] = (int) $row['is_enabled'] === 1;
}

ob_start();
?>
<div class="card">
    <h1>Admin dashboard</h1>
    <p class="help">Activez/désactivez les widgets disponibles sur le dashboard membre.</p>
    <form method="post" class="stack">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <?php foreach ($catalog as $widgetKey => $widget): ?>
            <label>
                <input type="checkbox" name="widget_<?= e((string) $widgetKey) ?>" value="1" <?= (($enabledMap[$widgetKey] ?? true) ? 'checked' : '') ?>>
                <strong><?= e((string) ($widget['title'] ?? $widgetKey)) ?></strong>
                <span class="help"><?= e((string) ($widget['description'] ?? '')) ?></span>
            </label>
        <?php endforeach; ?>
        <button class="button" type="submit">Enregistrer</button>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Admin dashboard');
