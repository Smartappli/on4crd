<?php
declare(strict_types=1);

require_permission('admin.access');
$user = require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['role_assigned' => 'Rôle attribué.', 'title' => 'Rôles et permissions', 'th_permission' => 'Permission', 'th_label' => 'Libellé', 'assign_role' => 'Attribuer un rôle', 'member' => 'Membre', 'role' => 'Rôle', 'assign' => 'Attribuer', 'layout' => 'Permissions', 'meta_desc' => 'Gestion des rôles et permissions des membres.'],
    'en' => ['role_assigned' => 'Role assigned.', 'title' => 'Roles and permissions', 'th_permission' => 'Permission', 'th_label' => 'Label', 'assign_role' => 'Assign a role', 'member' => 'Member', 'role' => 'Role', 'assign' => 'Assign', 'layout' => 'Permissions', 'meta_desc' => 'Manage member roles and permissions.'],
    'de' => ['role_assigned' => 'Rolle zugewiesen.', 'title' => 'Rollen und Berechtigungen', 'th_permission' => 'Berechtigung', 'th_label' => 'Bezeichnung', 'assign_role' => 'Rolle zuweisen', 'member' => 'Mitglied', 'role' => 'Rolle', 'assign' => 'Zuweisen', 'layout' => 'Berechtigungen', 'meta_desc' => 'Rollen und Berechtigungen der Mitglieder verwalten.'],
    'nl' => ['role_assigned' => 'Rol toegewezen.', 'title' => 'Rollen en rechten', 'th_permission' => 'Recht', 'th_label' => 'Label', 'assign_role' => 'Rol toewijzen', 'member' => 'Lid', 'role' => 'Rol', 'assign' => 'Toewijzen', 'layout' => 'Rechten', 'meta_desc' => 'Beheer van rollen en rechten van leden.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if (($_POST['action'] ?? '') === 'assign_role') {
            db()->prepare('INSERT IGNORE INTO member_roles (member_id, role_id) VALUES (?, ?)')->execute([
                (int) ($_POST['member_id'] ?? 0),
                (int) ($_POST['role_id'] ?? 0),
            ]);
            set_flash('success', (string) $t['role_assigned']);
        }
        redirect('admin_permissions');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('admin_permissions');
    }
}

$members = db()->query('SELECT id, callsign, full_name FROM members ORDER BY callsign')->fetchAll();
$roles = db()->query('SELECT id, code, label FROM roles ORDER BY label')->fetchAll();
$permissions = db()->query('SELECT code, label FROM permissions ORDER BY code')->fetchAll();

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= e((string) $t['title']) ?></h1>
        <div class="table-wrap">
            <table>
                <thead><tr><th><?= e((string) $t['th_permission']) ?></th><th><?= e((string) $t['th_label']) ?></th></tr></thead>
                <tbody>
                <?php foreach ($permissions as $permission): ?>
                    <tr><td><code><?= e((string) $permission['code']) ?></code></td><td><?= e((string) $permission['label']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2><?= e((string) $t['assign_role']) ?></h2>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="assign_role">
            <label><?= e((string) $t['member']) ?>
                <select name="member_id">
                    <?php foreach ($members as $member): ?>
                        <option value="<?= (int) $member['id'] ?>"><?= e((string) $member['callsign']) ?> — <?= e((string) $member['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e((string) $t['role']) ?>
                <select name="role_id">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>"><?= e((string) $role['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <p><button class="button"><?= e((string) $t['assign']) ?></button></p>
        </form>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
