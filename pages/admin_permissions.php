<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/admin_permissions.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, (string) $key);
}
set_page_meta(['title' => (string) $t['layout'], 'description' => (string) $t['meta_desc'], 'robots' => 'noindex,nofollow']);

$permissionLabel = static function (array $permission) use ($t): string {
    $code = (string) ($permission['code'] ?? '');
    $key = 'permission_' . str_replace(['.', '-'], '_', $code);

    return (string) ($t[$key] ?? $permission['label'] ?? $code);
};
$roleLabel = static function (array $role) use ($t): string {
    $code = (string) ($role['code'] ?? '');
    $key = 'role_' . str_replace(['.', '-'], '_', $code);

    return (string) ($t[$key] ?? $role['label'] ?? $code);
};

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
$roles = db()->query('SELECT id, code, label FROM roles ORDER BY label')->fetchAll();
$members = db()->query('SELECT id, callsign, full_name FROM members ORDER BY callsign')->fetchAll();
$memberRoles = db()->query('SELECT mr.member_id, mr.role_id, r.code, r.label FROM member_roles mr INNER JOIN roles r ON r.id = mr.role_id ORDER BY r.label')->fetchAll() ?: [];
$rolesByMember = [];
foreach ($memberRoles as $item) {
    $mid = (int) ($item['member_id'] ?? 0);
    if ($mid <= 0) { continue; }
    $rolesByMember[$mid][] = [
        'id' => (int) ($item['role_id'] ?? 0),
        'code' => (string) ($item['code'] ?? ''),
        'label' => (string) ($item['label'] ?? ''),
    ];
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
                    <tr><td><code><?= e((string) $permission['code']) ?></code></td><td><?= e($permissionLabel($permission)) ?></td></tr>
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
                <select name="role_id"><?php foreach ($roles as $role): ?><option value="<?= (int) $role['id'] ?>"><?= e($roleLabel($role)) ?></option><?php endforeach; ?></select>
            </label>
            <p><button class="button"><?= e((string) $t['assign']) ?></button></p>
        </form>

        <h3><?= e((string) $t['assignments']) ?></h3>
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
                        <span class="badge muted"><?= e($roleLabel($r)) ?></span>
                        <button class="button secondary" type="submit"><?= e((string) $t['remove_role']) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
