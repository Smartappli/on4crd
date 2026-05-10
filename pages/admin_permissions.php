<?php
declare(strict_types=1);

require_permission('admin.access');
$user = require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['role_assigned' => 'Rôle attribué.', 'role_removed' => 'Rôle retiré.', 'member_updated' => 'Membre mis à jour.', 'title' => 'Gestion complète des membres', 'th_permission' => 'Permission', 'th_label' => 'Libellé', 'assign_role' => 'Attribuer un rôle', 'member' => 'Membre', 'role' => 'Rôle', 'assign' => 'Attribuer', 'layout' => 'Permissions', 'meta_desc' => 'Gestion des rôles, permissions et profils des membres.', 'members' => 'Membres', 'th_callsign' => 'Indicatif', 'th_name' => 'Nom', 'th_email' => 'Email', 'th_locator' => 'Locator', 'th_active' => 'Actif', 'th_committee' => 'Comité', 'th_roles' => 'Rôles', 'th_actions' => 'Actions', 'save' => 'Enregistrer', 'remove_role' => 'Retirer', 'none' => 'Aucun rôle', 'err_callsign' => 'Indicatif requis.', 'err_email' => 'Email invalide.', 'err_locator' => 'Locator invalide.', 'search' => 'Rechercher un membre', 'search_ph' => 'Indicatif, nom, email…'],
    'en' => ['role_assigned' => 'Role assigned.', 'role_removed' => 'Role removed.', 'member_updated' => 'Member updated.', 'title' => 'Complete member management', 'th_permission' => 'Permission', 'th_label' => 'Label', 'assign_role' => 'Assign a role', 'member' => 'Member', 'role' => 'Role', 'assign' => 'Assign', 'layout' => 'Permissions', 'meta_desc' => 'Manage roles, permissions and member profiles.', 'members' => 'Members', 'th_callsign' => 'Callsign', 'th_name' => 'Name', 'th_email' => 'Email', 'th_locator' => 'Locator', 'th_active' => 'Active', 'th_committee' => 'Committee', 'th_roles' => 'Roles', 'th_actions' => 'Actions', 'save' => 'Save', 'remove_role' => 'Remove', 'none' => 'No role', 'err_callsign' => 'Callsign required.', 'err_email' => 'Invalid email.', 'err_locator' => 'Invalid locator.', 'search' => 'Search member', 'search_ph' => 'Callsign, name, email…'],
    'de' => ['role_assigned' => 'Rolle zugewiesen.', 'role_removed' => 'Rolle entfernt.', 'member_updated' => 'Mitglied aktualisiert.', 'title' => 'Vollständige Mitgliederverwaltung', 'th_permission' => 'Berechtigung', 'th_label' => 'Bezeichnung', 'assign_role' => 'Rolle zuweisen', 'member' => 'Mitglied', 'role' => 'Rolle', 'assign' => 'Zuweisen', 'layout' => 'Berechtigungen', 'meta_desc' => 'Rollen, Berechtigungen und Mitgliedsprofile verwalten.', 'members' => 'Mitglieder', 'th_callsign' => 'Rufzeichen', 'th_name' => 'Name', 'th_email' => 'E-Mail', 'th_locator' => 'Locator', 'th_active' => 'Aktiv', 'th_committee' => 'Komitee', 'th_roles' => 'Rollen', 'th_actions' => 'Aktionen', 'save' => 'Speichern', 'remove_role' => 'Entfernen', 'none' => 'Keine Rolle', 'err_callsign' => 'Rufzeichen erforderlich.', 'err_email' => 'Ungültige E-Mail.', 'err_locator' => 'Ungültiger Locator.', 'search' => 'Mitglied suchen', 'search_ph' => 'Rufzeichen, Name, E-Mail…'],
    'nl' => ['role_assigned' => 'Rol toegewezen.', 'role_removed' => 'Rol verwijderd.', 'member_updated' => 'Lid bijgewerkt.', 'title' => 'Volledig ledenbeheer', 'th_permission' => 'Recht', 'th_label' => 'Label', 'assign_role' => 'Rol toewijzen', 'member' => 'Lid', 'role' => 'Rol', 'assign' => 'Toewijzen', 'layout' => 'Rechten', 'meta_desc' => 'Beheer van rollen, rechten en ledenprofielen.', 'members' => 'Leden', 'th_callsign' => 'Callsign', 'th_name' => 'Naam', 'th_email' => 'E-mail', 'th_locator' => 'Locator', 'th_active' => 'Actief', 'th_committee' => 'Comité', 'th_roles' => 'Rollen', 'th_actions' => 'Acties', 'save' => 'Opslaan', 'remove_role' => 'Verwijderen', 'none' => 'Geen rol', 'err_callsign' => 'Callsign verplicht.', 'err_email' => 'Ongeldig e-mailadres.', 'err_locator' => 'Ongeldige locator.', 'search' => 'Lid zoeken', 'search_ph' => 'Callsign, naam, e-mail…'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

$returnQuery = http_build_query([
    'member_q' => (string) ($_GET['member_q'] ?? ''),
    'sort' => (string) ($_GET['sort'] ?? 'callsign'),
    'dir' => (string) ($_GET['dir'] ?? 'asc'),
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
                (int) ($_POST['remove_role_id'] ?? $_POST['role_id'] ?? 0),
            ]);
            set_flash('success', (string) $t['role_removed']);
        }
        if ($action === 'update_member') {
            $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $locator = strtoupper(trim((string) ($_POST['locator'] ?? '')));
            if ($callsign === '') { throw new RuntimeException((string) $t['err_callsign']); }
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) { throw new RuntimeException((string) $t['err_email']); }
            if ($locator !== '' && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $locator) !== 1) { throw new RuntimeException((string) $t['err_locator']); }
            db()->prepare('UPDATE members SET callsign = ?, full_name = ?, email = ?, locator = ?, is_active = ?, is_committee = ? WHERE id = ? LIMIT 1')->execute([
                $callsign,
                $fullName,
                $email,
                $locator,
                isset($_POST['is_active']) ? 1 : 0,
                isset($_POST['is_committee']) ? 1 : 0,
                (int) ($_POST['member_id'] ?? 0),
            ]);
            set_flash('success', (string) $t['member_updated']);
        }
        $postReturnQuery = trim((string) ($_POST['return_query'] ?? ''));
        redirect('admin_permissions' . ($postReturnQuery !== '' ? '&' . $postReturnQuery : ''));
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        $postReturnQuery = trim((string) ($_POST['return_query'] ?? ''));
        redirect('admin_permissions' . ($postReturnQuery !== '' ? '&' . $postReturnQuery : ''));
    }
}

$memberSearch = trim((string) ($_GET['member_q'] ?? ''));

$memberSort = (string) ($_GET['sort'] ?? 'callsign');

$memberPage = max(1, (int) ($_GET['page'] ?? 1));
$memberPerPage = 25;
$memberDir = strtolower((string) ($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
$allowedSort = ['callsign', 'full_name', 'email', 'locator', 'is_active', 'is_committee'];
if (!in_array($memberSort, $allowedSort, true)) {
    $memberSort = 'callsign';
}
$members = db()->query('SELECT id, callsign, full_name, email, locator, is_active, is_committee FROM members ORDER BY callsign')->fetchAll();
usort($members, static function (array $a, array $b) use ($memberSort, $memberDir): int {
    $va = (string) ($a[$memberSort] ?? '');
    $vb = (string) ($b[$memberSort] ?? '');
    $cmp = strnatcasecmp($va, $vb);
    return $memberDir === 'desc' ? -$cmp : $cmp;
});
if ($memberSearch !== '') {
    $needle = mb_safe_strtolower($memberSearch);
    $members = array_values(array_filter($members, static function (array $m) use ($needle): bool {
        $hay = mb_safe_strtolower((string) ($m['callsign'] ?? '') . ' ' . (string) ($m['full_name'] ?? '') . ' ' . (string) ($m['email'] ?? ''));
        return str_contains($hay, $needle);
    }));
}
$memberTotal = count($members);
$memberPages = max(1, (int) ceil($memberTotal / $memberPerPage));
if ($memberPage > $memberPages) { $memberPage = $memberPages; }
$members = array_slice($members, ($memberPage - 1) * $memberPerPage, $memberPerPage);

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
            <input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
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
    <form method="get" style="margin:.5rem 0 1rem;">
        <label><?= e((string) $t['search']) ?>
            <input type="text" name="member_q" value="<?= e($memberSearch) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
        </label>
        <input type="hidden" name="sort" value="<?= e($memberSort) ?>"><input type="hidden" name="dir" value="<?= e($memberDir) ?>"><button class="button secondary" type="submit">OK</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th><a href="?member_q=<?= urlencode($memberSearch) ?>&sort=callsign&dir=<?= $memberSort==='callsign'&&$memberDir==='asc'?'desc':'asc' ?>"><?= e((string) $t['th_callsign']) ?></a></th>
                    <th><a href="?member_q=<?= urlencode($memberSearch) ?>&sort=full_name&dir=<?= $memberSort==='full_name'&&$memberDir==='asc'?'desc':'asc' ?>"><?= e((string) $t['th_name']) ?></a></th>
                    <th><a href="?member_q=<?= urlencode($memberSearch) ?>&sort=email&dir=<?= $memberSort==='email'&&$memberDir==='asc'?'desc':'asc' ?>"><?= e((string) $t['th_email']) ?></a></th>
                    <th><a href="?member_q=<?= urlencode($memberSearch) ?>&sort=locator&dir=<?= $memberSort==='locator'&&$memberDir==='asc'?'desc':'asc' ?>"><?= e((string) $t['th_locator']) ?></a></th>
                    <th><a href="?member_q=<?= urlencode($memberSearch) ?>&sort=is_active&dir=<?= $memberSort==='is_active'&&$memberDir==='asc'?'desc':'asc' ?>"><?= e((string) $t['th_active']) ?></a></th>
                    <th><a href="?member_q=<?= urlencode($memberSearch) ?>&sort=is_committee&dir=<?= $memberSort==='is_committee'&&$memberDir==='asc'?'desc':'asc' ?>"><?= e((string) $t['th_committee']) ?></a></th>
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
                                                                <input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>">
                                    <input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
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
                                        <div style="display:flex; flex-wrap:wrap; gap:.25rem; margin-bottom:.35rem;">
                                            <?php foreach ($currentRoles as $r): ?>
                                                <span style="display:inline-flex; align-items:center; border:1px solid #e2e8f0; border-radius:999px; padding:.1rem .5rem;">
                                                    <?= e((string) $r['label']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <div style="display:flex; gap:.35rem; align-items:center;">
                                            <select name="remove_role_id">
                                                <?php foreach ($currentRoles as $r): ?>
                                                    <option value="<?= (int) $r['id'] ?>"><?= e((string) $r['label']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="button secondary" type="submit" name="action" value="remove_role"><?= e((string) $t['remove_role']) ?></button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button class="button" type="submit" name="action" value="update_member"><?= e((string) $t['save']) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($memberPages > 1): ?>
        <p class="help" style="margin-top:.75rem;">Page <?= (int) $memberPage ?> / <?= (int) $memberPages ?>
            <?php if ($memberPage > 1): ?>
                <a href="?member_q=<?= urlencode($memberSearch) ?>&sort=<?= urlencode($memberSort) ?>&dir=<?= urlencode($memberDir) ?>&page=<?= $memberPage - 1 ?>">←</a>
            <?php endif; ?>
            <?php if ($memberPage < $memberPages): ?>
                <a href="?member_q=<?= urlencode($memberSearch) ?>&sort=<?= urlencode($memberSort) ?>&dir=<?= urlencode($memberDir) ?>&page=<?= $memberPage + 1 ?>">→</a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
