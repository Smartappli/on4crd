<?php
declare(strict_types=1);

require_permission('admin.access');
require_permission('modules.manage');
$locale = current_locale();
$i18n = [
    'fr' => ['updated' => 'Modules mis à jour.', 'title' => 'Modules', 'core' => 'Cœur', 'enabled' => 'Activé', 'save' => 'Enregistrer', 'layout' => 'Modules'],
    'en' => ['updated' => 'Modules updated.', 'title' => 'Modules', 'core' => 'Core', 'enabled' => 'Enabled', 'save' => 'Save', 'layout' => 'Modules'],
    'de' => ['updated' => 'Module aktualisiert.', 'title' => 'Module', 'core' => 'Kern', 'enabled' => 'Aktiviert', 'save' => 'Speichern', 'layout' => 'Module'],
    'nl' => ['updated' => 'Modules bijgewerkt.', 'title' => 'Modules', 'core' => 'Kern', 'enabled' => 'Ingeschakeld', 'save' => 'Opslaan', 'layout' => 'Modules'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $stmt = db()->prepare('UPDATE modules SET is_enabled = ? WHERE id = ?');
        foreach (db()->query('SELECT id, code, is_core FROM modules ORDER BY sort_order')->fetchAll() as $module) {
            $enabled = isset($_POST['module_' . $module['id']]) ? 1 : 0;
            if ((int) $module['is_core'] === 1) {
                $enabled = 1;
            }
            $stmt->execute([$enabled, (int) $module['id']]);
        }
        set_flash('success', (string) $t['updated']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_modules');
}

$modules = db()->query('SELECT * FROM modules ORDER BY sort_order, label')->fetchAll();

ob_start();
?>
<div class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="stack">
            <?php foreach ($modules as $module): ?>
                <div class="module-row">
                    <div>
                        <strong><?= e((string) $module['label']) ?></strong>
                        <p class="help"><?= e((string) $module['description']) ?></p>
                    </div>
                    <div class="module-actions">
                        <?php if ((int) $module['is_core'] === 1): ?>
                            <span class="badge muted"><?= e((string) $t['core']) ?></span>
                        <?php endif; ?>
                        <label>
                            <input type="checkbox"
                                   name="module_<?= (int) $module['id'] ?>"
                                   value="1"
                                   <?= (int) $module['is_enabled'] === 1 ? 'checked' : '' ?>
                                   <?= (int) $module['is_core'] === 1 ? 'disabled' : '' ?>>
                            <?= e((string) $t['enabled']) ?>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p><button class="button"><?= e((string) $t['save']) ?></button></p>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
