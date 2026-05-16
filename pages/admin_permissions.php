<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$i18n = [
    'fr' => ['role_assigned' => 'Rôle attribué.', 'role_removed' => 'Rôle retiré.', 'title' => 'Rôles et permissions', 'th_permission' => 'Permission', 'th_label' => 'Libellé', 'assign_role' => 'Attribuer un rôle', 'member' => 'Membre', 'role' => 'Rôle', 'assign' => 'Attribuer', 'layout' => 'Rôles & permissions', 'meta_desc' => 'Gestion des rôles et permissions.', 'remove_role' => 'Retirer', 'assignments' => 'Attributions'],
    'en' => ['role_assigned' => 'Role assigned.', 'role_removed' => 'Role removed.', 'title' => 'Roles and permissions', 'th_permission' => 'Permission', 'th_label' => 'Label', 'assign_role' => 'Assign a role', 'member' => 'Member', 'role' => 'Role', 'assign' => 'Assign', 'layout' => 'Roles & permissions', 'meta_desc' => 'Manage roles and permissions.', 'remove_role' => 'Remove', 'assignments' => 'Assignments'],
    'de' => ['role_assigned' => 'Rolle zugewiesen.', 'role_removed' => 'Rolle entfernt.', 'title' => 'Rollen und Berechtigungen', 'th_permission' => 'Berechtigung', 'th_label' => 'Bezeichnung', 'assign_role' => 'Rolle zuweisen', 'member' => 'Mitglied', 'role' => 'Rolle', 'assign' => 'Zuweisen', 'layout' => 'Rollen & Berechtigungen', 'meta_desc' => 'Rollen und Berechtigungen verwalten.', 'remove_role' => 'Entfernen', 'assignments' => 'Zuweisungen'],
    'es' => ['role_assigned' => 'Rol asignado.', 'role_removed' => 'Rol retirado.', 'title' => 'Roles y permisos', 'th_permission' => 'Permiso', 'th_label' => 'Etiqueta', 'assign_role' => 'Asignar rol', 'member' => 'Miembro', 'role' => 'Rol', 'assign' => 'Asignar', 'layout' => 'Roles y permisos', 'meta_desc' => 'Gestión de roles y permisos.', 'remove_role' => 'Retirar', 'assignments' => 'Asignaciones'],
    'it' => ['role_assigned' => 'Ruolo assegnato.', 'role_removed' => 'Ruolo rimosso.', 'title' => 'Ruoli e permessi', 'th_permission' => 'Permesso', 'th_label' => 'Etichetta', 'assign_role' => 'Assegna ruolo', 'member' => 'Membro', 'role' => 'Ruolo', 'assign' => 'Assegna', 'layout' => 'Ruoli e permessi', 'meta_desc' => 'Gestione ruoli e permessi.', 'remove_role' => 'Rimuovi', 'assignments' => 'Assegnazioni'],
    'pt' => ['role_assigned' => 'Função atribuída.', 'role_removed' => 'Função removida.', 'title' => 'Funções e permissões', 'th_permission' => 'Permissão', 'th_label' => 'Rótulo', 'assign_role' => 'Atribuir função', 'member' => 'Membro', 'role' => 'Função', 'assign' => 'Atribuir', 'layout' => 'Funções e permissões', 'meta_desc' => 'Gestão de funções e permissões.', 'remove_role' => 'Remover', 'assignments' => 'Atribuições'],
    'nl' => ['role_assigned' => 'Rol toegewezen.', 'role_removed' => 'Rol verwijderd.', 'title' => 'Rollen en rechten', 'th_permission' => 'Recht', 'th_label' => 'Label', 'assign_role' => 'Rol toewijzen', 'member' => 'Lid', 'role' => 'Rol', 'assign' => 'Toewijzen', 'layout' => 'Rollen & rechten', 'meta_desc' => 'Beheer van rollen en rechten.', 'remove_role' => 'Verwijderen', 'assignments' => 'Toewijzingen'],
    'ar' => ['role_assigned' => 'تم تعيين الدور.', 'role_removed' => 'تمت إزالة الدور.', 'title' => 'الأدوار والصلاحيات', 'th_permission' => 'الصلاحية', 'th_label' => 'التسمية', 'assign_role' => 'تعيين دور', 'member' => 'العضو', 'role' => 'الدور', 'assign' => 'تعيين', 'layout' => 'الأدوار والصلاحيات', 'meta_desc' => 'إدارة الأدوار والصلاحيات.', 'remove_role' => 'إزالة', 'assignments' => 'التعيينات'],
    'hi' => ['role_assigned' => 'भूमिका असाइन की गई।', 'role_removed' => 'भूमिका हटाई गई।', 'title' => 'भूमिकाएँ और अनुमतियाँ', 'th_permission' => 'अनुमति', 'th_label' => 'लेबल', 'assign_role' => 'भूमिका असाइन करें', 'member' => 'सदस्य', 'role' => 'भूमिका', 'assign' => 'असाइन करें', 'layout' => 'भूमिकाएँ और अनुमतियाँ', 'meta_desc' => 'भूमिकाओं और अनुमतियों का प्रबंधन।', 'remove_role' => 'हटाएँ', 'assignments' => 'असाइनमेंट'],
    'ja' => ['role_assigned' => 'ロールを割り当てました。', 'role_removed' => 'ロールを削除しました。', 'title' => 'ロールと権限', 'th_permission' => '権限', 'th_label' => 'ラベル', 'assign_role' => 'ロールを割り当てる', 'member' => 'メンバー', 'role' => 'ロール', 'assign' => '割り当て', 'layout' => 'ロールと権限', 'meta_desc' => 'ロールと権限を管理します。', 'remove_role' => '削除', 'assignments' => '割り当て一覧'],
    'zh' => ['role_assigned' => '角色已分配。', 'role_removed' => '角色已移除。', 'title' => '角色与权限', 'th_permission' => '权限', 'th_label' => '标签', 'assign_role' => '分配角色', 'member' => '成员', 'role' => '角色', 'assign' => '分配', 'layout' => '角色与权限', 'meta_desc' => '管理角色与权限。', 'remove_role' => '移除', 'assignments' => '分配记录'],
    'bn' => ['role_assigned' => 'ভূমিকা বরাদ্দ করা হয়েছে।', 'role_removed' => 'ভূমিকা সরানো হয়েছে।', 'title' => 'ভূমিকা ও অনুমতি', 'th_permission' => 'অনুমতি', 'th_label' => 'লেবেল', 'assign_role' => 'ভূমিকা বরাদ্দ করুন', 'member' => 'সদস্য', 'role' => 'ভূমিকা', 'assign' => 'বরাদ্দ করুন', 'layout' => 'ভূমিকা ও অনুমতি', 'meta_desc' => 'ভূমিকা ও অনুমতির ব্যবস্থাপনা।', 'remove_role' => 'সরান', 'assignments' => 'বরাদ্দসমূহ'],
    'ru' => ['role_assigned' => 'Роль назначена.', 'role_removed' => 'Роль удалена.', 'title' => 'Роли и права', 'th_permission' => 'Право', 'th_label' => 'Метка', 'assign_role' => 'Назначить роль', 'member' => 'Участник', 'role' => 'Роль', 'assign' => 'Назначить', 'layout' => 'Роли и права', 'meta_desc' => 'Управление ролями и правами.', 'remove_role' => 'Удалить', 'assignments' => 'Назначения'],
    'id' => ['role_assigned' => 'Peran ditetapkan.', 'role_removed' => 'Peran dihapus.', 'title' => 'Peran dan izin', 'th_permission' => 'Izin', 'th_label' => 'Label', 'assign_role' => 'Tetapkan peran', 'member' => 'Anggota', 'role' => 'Peran', 'assign' => 'Tetapkan', 'layout' => 'Peran & izin', 'meta_desc' => 'Kelola peran dan izin.', 'remove_role' => 'Hapus', 'assignments' => 'Penetapan'],
];
$t = $i18n[$locale] ?? $i18n['fr'];
set_page_meta(['title' => (string) $t['layout'], 'description' => (string) $t['meta_desc'], 'robots' => 'noindex,nofollow']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'assign_role') {
        db()->prepare('INSERT IGNORE INTO member_roles (member_id, role_id) VALUES (?, ?)')->execute([(int) ($_POST['member_id'] ?? 0), (int) ($_POST['role_id'] ?? 0)]);
        set_flash('success', (string) $t['role_assigned']);
    }
    if ($action === 'remove_role') {
        db()->prepare('DELETE FROM member_roles WHERE member_id = ? AND role_id = ? LIMIT 1')->execute([(int) ($_POST['member_id'] ?? 0), (int) ($_POST['role_id'] ?? 0)]);
        set_flash('success', (string) $t['role_removed']);
    }
    redirect('admin_permissions');
}

$permissions = db()->query('SELECT code, label FROM permissions ORDER BY code')->fetchAll();
$roles = db()->query('SELECT id, label FROM roles ORDER BY label')->fetchAll();
$members = db()->query('SELECT id, callsign, full_name FROM members ORDER BY callsign')->fetchAll();
$memberRoles = db()->query('SELECT mr.member_id, mr.role_id, r.label FROM member_roles mr INNER JOIN roles r ON r.id = mr.role_id ORDER BY r.label')->fetchAll() ?: [];
$rolesByMember = [];
foreach ($memberRoles as $item) {
    $mid = (int) ($item['member_id'] ?? 0);
    if ($mid <= 0) { continue; }
    $rolesByMember[$mid][] = ['id' => (int) ($item['role_id'] ?? 0), 'label' => (string) ($item['label'] ?? '')];
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
                <select name="member_id"><?php foreach ($members as $member): ?><option value="<?= (int) $member['id'] ?>"><?= e((string) $member['callsign']) ?> — <?= e((string) $member['full_name']) ?></option><?php endforeach; ?></select>
            </label>
            <label><?= e((string) $t['role']) ?>
                <select name="role_id"><?php foreach ($roles as $role): ?><option value="<?= (int) $role['id'] ?>"><?= e((string) $role['label']) ?></option><?php endforeach; ?></select>
            </label>
            <p><button class="button"><?= e((string) $t['assign']) ?></button></p>
        </form>

        <h3><?= e((string) ($t['assignments'] ?? 'Attributions')) ?></h3>
        <?php foreach ($members as $member): ?>
            <?php $currentRoles = $rolesByMember[(int) $member['id']] ?? []; if ($currentRoles === []) { continue; } ?>
            <div style="margin:.6rem 0;">
                <strong><?= e((string) $member['callsign']) ?></strong>
                <?php foreach ($currentRoles as $r): ?>
                    <form method="post" style="display:inline-flex; gap:.35rem; margin:.2rem;">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="remove_role">
                        <input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>">
                        <input type="hidden" name="role_id" value="<?= (int) $r['id'] ?>">
                        <span class="badge muted"><?= e((string) $r['label']) ?></span>
                        <button class="button secondary" type="submit"><?= e((string) $t['remove_role']) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
