<?php
declare(strict_types=1);

require_permission('admin.access');
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if (($_POST['action'] ?? '') === 'assign_role') {
            db()->prepare('INSERT IGNORE INTO member_roles (member_id, role_id) VALUES (?, ?)')->execute([
                (int) ($_POST['member_id'] ?? 0),
                (int) ($_POST['role_id'] ?? 0),
            ]);
            set_flash('success', 'Rôle attribué.');
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
        <h1>Rôles et permissions</h1>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Permission</th><th>Libellé</th></tr></thead>
                <tbody>
                <?php foreach ($permissions as $permission): ?>
                    <tr><td><code><?= e((string) $permission['code']) ?></code></td><td><?= e((string) $permission['label']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Attribuer un rôle</h2>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="assign_role">
            <label>Membre
                <select name="member_id">
                    <?php foreach ($members as $member): ?>
                        <option value="<?= (int) $member['id'] ?>"><?= e((string) $member['callsign']) ?> — <?= e((string) $member['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Rôle
                <select name="role_id">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>"><?= e((string) $role['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <p><button class="button">Attribuer</button></p>
        </form>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Permissions');
