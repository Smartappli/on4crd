<?php
declare(strict_types=1);

require_permission('admin.access');
$user = require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['role_assigned' => 'Rôle attribué.', 'role_removed' => 'Rôle retiré.', 'member_updated' => 'Membre mis à jour.', 'title' => 'Gestion complète des membres', 'th_permission' => 'Permission', 'th_label' => 'Libellé', 'assign_role' => 'Attribuer un rôle', 'member' => 'Membre', 'role' => 'Rôle', 'assign' => 'Attribuer', 'layout' => 'Permissions', 'meta_desc' => 'Gestion des rôles, permissions et profils des membres.', 'members' => 'Membres', 'th_callsign' => 'Indicatif', 'th_name' => 'Nom', 'th_email' => 'Email', 'th_locator' => 'Locator', 'th_active' => 'Actif', 'th_committee' => 'Comité', 'th_roles' => 'Rôles', 'th_actions' => 'Actions', 'save' => 'Enregistrer', 'remove_role' => 'Retirer', 'none' => 'Aucun rôle'],
    'en' => ['role_assigned' => 'Role assigned.', 'role_removed' => 'Role removed.', 'member_updated' => 'Member updated.', 'title' => 'Complete member management', 'th_permission' => 'Permission', 'th_label' => 'Label', 'assign_role' => 'Assign a role', 'member' => 'Member', 'role' => 'Role', 'assign' => 'Assign', 'layout' => 'Permissions', 'meta_desc' => 'Manage roles, permissions and member profiles.', 'members' => 'Members', 'th_callsign' => 'Callsign', 'th_name' => 'Name', 'th_email' => 'Email', 'th_locator' => 'Locator', 'th_active' => 'Active', 'th_committee' => 'Committee', 'th_roles' => 'Roles', 'th_actions' => 'Actions', 'save' => 'Save', 'remove_role' => 'Remove', 'none' => 'No role'],
    'de' => ['role_assigned' => 'Rolle zugewiesen.', 'role_removed' => 'Rolle entfernt.', 'member_updated' => 'Mitglied aktualisiert.', 'title' => 'Vollständige Mitgliederverwaltung', 'th_permission' => 'Berechtigung', 'th_label' => 'Bezeichnung', 'assign_role' => 'Rolle zuweisen', 'member' => 'Mitglied', 'role' => 'Rolle', 'assign' => 'Zuweisen', 'layout' => 'Berechtigungen', 'meta_desc' => 'Rollen, Berechtigungen und Mitgliedsprofile verwalten.', 'members' => 'Mitglieder', 'th_callsign' => 'Rufzeichen', 'th_name' => 'Name', 'th_email' => 'E-Mail', 'th_locator' => 'Locator', 'th_active' => 'Aktiv', 'th_committee' => 'Komitee', 'th_roles' => 'Rollen', 'th_actions' => 'Aktionen', 'save' => 'Speichern', 'remove_role' => 'Entfernen', 'none' => 'Keine Rolle'],
    'nl' => ['role_assigned' => 'Rol toegewezen.', 'role_removed' => 'Rol verwijderd.', 'member_updated' => 'Lid bijgewerkt.', 'title' => 'Volledig ledenbeheer', 'th_permission' => 'Recht', 'th_label' => 'Label', 'assign_role' => 'Rol toewijzen', 'member' => 'Lid', 'role' => 'Rol', 'assign' => 'Toewijzen', 'layout' => 'Rechten', 'meta_desc' => 'Beheer van rollen, rechten en ledenprofielen.', 'members' => 'Leden', 'th_callsign' => 'Callsign', 'th_name' => 'Naam', 'th_email' => 'E-mail', 'th_locator' => 'Locator', 'th_active' => 'Actief', 'th_committee' => 'Comité', 'th_roles' => 'Rollen', 'th_actions' => 'Acties', 'save' => 'Opslaan', 'remove_role' => 'Verwijderen', 'none' => 'Geen rol'],
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
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'assign_role') {
            db()->prepare('INSERT IGNORE INTO member_roles (member_id, role_id) VALUES (?, ?)')->execute([
                (int) ($_POST['member_id'] ?? 0),
                (int) ($_POST['role_id'] ?? 0),
            ]);
            set_flash('success', (string) $t['role_assigned']);
        }
        if ($action === 'remove_role') {
            db()->prepare('DELETE FROM member_roles WHERE member_id = ? AND role_id = ? LIMIT 1')->execute([
                (int) ($_POST['member_id'] ?? 0),
                (int) ($_POST['role_id'] ?? 0),
            ]);
            set_flash('success', (string) $t['role_removed']);
        }
        if ($action === 'update_member') {
            db()->prepare('UPDATE members SET callsign = ?, full_name = ?, email = ?, locator = ?, is_active = ?, is_committee = ? WHERE id = ? LIMIT 1')->execute([
                trim((string) ($_POST['callsign'] ?? '')),
                trim((string) ($_POST['full_name'] ?? '')),
                trim((string) ($_POST['email'] ?? '')),
                strtoupper(trim((string) ($_POST['locator'] ?? ''))),
                isset($_POST['is_active']) ? 1 : 0,
                isset($_POST['is_committee']) ? 1 : 0,
                (int) ($_POST['member_id'] ?? 0),
            ]);
            set_flash('success', (string) $t['member_updated']);
        }
        redirect('admin_permissions');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('admin_permissions');
    }
}

$members = db()->query('SELECT id, callsign, full_name, email, locator, is_active, is_committee FROM members ORDER BY callsign')->fetchAll();
$roles = db()->query('SELECT id, code, label FROM roles ORDER BY label')->fetchAll();
$permissions = db()->query('SELECT code, label FROM permissions ORDER BY code')->fetchAll();
$memberRoles = db()->query('SELECT mr.member_id, mr.role_id, r.label FROM member_roles mr INNER JOIN roles r ON r.id = mr.role_id ORDER BY r.label')->fetchAll() ?: [];
$rolesByMember = [];
foreach ($memberRoles as $item) {
    $memberId = (int) ($item['member_id'] ?? 0);
    if ($memberId <= 0) {
        continue;
    }
    $rolesByMember[$memberId][] = ['id' => (int) ($item['role_id'] ?? 0), 'label' => (string) ($item['label'] ?? '')];
}

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

<section class="card" style="margin-top:1rem;">
    <h2><?= e((string) $t['members']) ?></h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th><?= e((string) $t['th_callsign']) ?></th>
                    <th><?= e((string) $t['th_name']) ?></th>
                    <th><?= e((string) $t['th_email']) ?></th>
                    <th><?= e((string) $t['th_locator']) ?></th>
                    <th><?= e((string) $t['th_active']) ?></th>
                    <th><?= e((string) $t['th_committee']) ?></th>
                    <th><?= e((string) $t['th_roles']) ?></th>
                    <th><?= e((string) $t['th_actions']) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                    <?php $currentRoles = $rolesByMember[(int) $member['id']] ?? []; ?>
                    <tr>
                        <td colspan="8">
                            <form method="post" class="grid" style="grid-template-columns: 1fr 1fr 1fr 1fr auto auto 2fr auto; gap:.5rem; align-items:center;">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update_member">
                                <input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>">
                                <input type="text" name="callsign" value="<?= e((string) $member['callsign']) ?>">
                                <input type="text" name="full_name" value="<?= e((string) $member['full_name']) ?>">
                                <input type="email" name="email" value="<?= e((string) $member['email']) ?>">
                                <input type="text" name="locator" value="<?= e((string) $member['locator']) ?>" maxlength="6">
                                <label><input type="checkbox" name="is_active" value="1" <?= (int) $member['is_active'] === 1 ? 'checked' : '' ?>></label>
                                <label><input type="checkbox" name="is_committee" value="1" <?= (int) $member['is_committee'] === 1 ? 'checked' : '' ?>></label>
                                <div>
                                    <?php if ($currentRoles === []): ?>
                                        <span class="help"><?= e((string) $t['none']) ?></span>
                                    <?php else: ?>
                                        <?php foreach ($currentRoles as $r): ?>
                                            <span style="display:inline-flex; align-items:center; gap:.25rem; border:1px solid #e2e8f0; border-radius:999px; padding:.1rem .5rem; margin:0 .25rem .25rem 0;">
                                                <?= e((string) $r['label']) ?>
                                                <button class="button secondary" style="padding:.1rem .35rem; font-size:.75rem;" name="action" value="remove_role">×</button>
                                                <input type="hidden" name="role_id" value="<?= (int) $r['id'] ?>">
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button class="button" type="submit"><?= e((string) $t['save']) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
