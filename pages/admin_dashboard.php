<?php
declare(strict_types=1);

require_module_enabled('dashboard');
require_permission('admin.access');
require_permission('modules.manage');
$i18n = [
    'fr' => ['ok_updated' => 'Widgets du dashboard mis à jour.', 'title' => 'Administration dashboard', 'help' => 'Activez/désactivez les widgets disponibles sur le dashboard membre.', 'save' => 'Enregistrer', 'layout' => 'Dashboard', 'meta_desc' => 'Configuration des widgets du dashboard membre.', 'members_title' => 'Gestion des membres', 'members_help' => 'Accès rapide aux outils d’administration des membres.', 'members_total' => 'Membres totaux', 'members_active' => 'Membres actifs', 'members_committee' => 'Membres comité', 'members_roles' => 'Rôles & permissions', 'members_committee_cta' => 'Gérer le comité'],
    'en' => ['ok_updated' => 'Dashboard widgets updated.', 'title' => 'Dashboard administration', 'help' => 'Enable/disable widgets available on the member dashboard.', 'save' => 'Save', 'layout' => 'Dashboard', 'meta_desc' => 'Configuration of member dashboard widgets.', 'members_title' => 'Member management', 'members_help' => 'Quick access to member administration tools.', 'members_total' => 'Total members', 'members_active' => 'Active members', 'members_committee' => 'Committee members', 'members_roles' => 'Roles & permissions', 'members_committee_cta' => 'Manage committee'],
    'de' => ['ok_updated' => 'Dashboard-Widgets aktualisiert.', 'title' => 'Dashboard-Verwaltung', 'help' => 'Aktivieren/deaktivieren Sie verfügbare Widgets im Mitglieder-Dashboard.', 'save' => 'Speichern', 'layout' => 'Dashboard', 'meta_desc' => 'Konfiguration der Widgets im Mitglieder-Dashboard.', 'members_title' => 'Mitgliederverwaltung', 'members_help' => 'Schnellzugriff auf Werkzeuge zur Mitgliederverwaltung.', 'members_total' => 'Mitglieder gesamt', 'members_active' => 'Aktive Mitglieder', 'members_committee' => 'Komiteemitglieder', 'members_roles' => 'Rollen & Rechte', 'members_committee_cta' => 'Komitee verwalten'],
    'nl' => ['ok_updated' => 'Dashboardwidgets bijgewerkt.', 'title' => 'Dashboardbeheer', 'help' => 'Activeer/deactiveer widgets die beschikbaar zijn op het ledendashboard.', 'save' => 'Opslaan', 'layout' => 'Dashboard', 'meta_desc' => 'Configuratie van widgets op het ledendashboard.', 'members_title' => 'Ledenbeheer', 'members_help' => 'Snelle toegang tot tools voor ledenbeheer.', 'members_total' => 'Totaal leden', 'members_active' => 'Actieve leden', 'members_committee' => 'Comitéleden', 'members_roles' => 'Rollen & rechten', 'members_committee_cta' => 'Comité beheren'],
];
$locale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
$t = $i18n[$locale] ?? $i18n['fr'];

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
<div class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <p class="help"><?= e((string) $t['help']) ?></p>
    <form method="post" class="stack">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <?php foreach ($catalog as $widgetKey => $widget): ?>
            <label>
                <input type="checkbox" name="widget_<?= e((string) $widgetKey) ?>" value="1" <?= (($enabledMap[$widgetKey] ?? true) ? 'checked' : '') ?>>
                <strong><?= e((string) ($widget['title'] ?? $widgetKey)) ?></strong>
                <span class="help"><?= e((string) ($widget['description'] ?? '')) ?></span>
            </label>
        <?php endforeach; ?>
        <button class="button" type="submit"><?= e((string) $t['save']) ?></button>
    </form>
</div>

<div class="card">
    <h2><?= e((string) $t['members_title']) ?></h2>
    <p class="help"><?= e((string) $t['members_help']) ?></p>
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem; margin: 0.75rem 0 1rem;">
        <article class="card" style="margin: 0; padding: 0.75rem;">
            <p class="help" style="margin:0;"><?= e((string) $t['members_total']) ?></p>
            <p style="margin:0.25rem 0 0; font-size:1.5rem; font-weight:700;"><?= (int) $memberStats['total'] ?></p>
        </article>
        <article class="card" style="margin: 0; padding: 0.75rem;">
            <p class="help" style="margin:0;"><?= e((string) $t['members_active']) ?></p>
            <p style="margin:0.25rem 0 0; font-size:1.5rem; font-weight:700;"><?= (int) $memberStats['active'] ?></p>
        </article>
        <article class="card" style="margin: 0; padding: 0.75rem;">
            <p class="help" style="margin:0;"><?= e((string) $t['members_committee']) ?></p>
            <p style="margin:0.25rem 0 0; font-size:1.5rem; font-weight:700;"><?= (int) $memberStats['committee'] ?></p>
        </article>
    </div>
    <p>
        <a class="button" href="<?= e(route_url('admin_permissions')) ?>"><?= e((string) $t['members_roles']) ?></a>
        <a class="button secondary" href="<?= e(route_url('admin_committee')) ?>"><?= e((string) $t['members_committee_cta']) ?></a>
    </p>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
