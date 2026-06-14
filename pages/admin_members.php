<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/admin_members.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $pool = [];
    foreach ($i18n as $lang => $translations) {
        if (isset($translations[$key]) && is_string($translations[$key])) {
            $pool[$lang] = $translations[$key];
        }
    }
    $t[$key] = i18n_localized_value($pool, $locale, 'fr');
}
set_page_meta(['title' => (string) $t['layout'], 'description' => (string) $t['meta_desc'], 'robots' => 'noindex,nofollow']);

$returnQuery = http_build_query(['member_q' => (string) ($_GET['member_q'] ?? ''), 'sort' => (string) ($_GET['sort'] ?? 'callsign'), 'dir' => (string) ($_GET['dir'] ?? 'asc')]);
$passwordChangeColumnAvailable = table_has_column('members', 'password_change_required');
$passwordResetMarkerColumnAvailable = table_has_column('members', 'password_reset_forced_at');
$passwordResetForceAvailable = $passwordChangeColumnAvailable && $passwordResetMarkerColumnAvailable;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $locator = strtoupper(trim((string) ($_POST['locator'] ?? '')));
    if ($callsign === '') { set_flash('error', (string) $t['err_callsign']); redirect('admin_members'); }
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) { set_flash('error', (string) $t['err_email']); redirect('admin_members'); }
    if ($locator !== '' && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $locator) !== 1) { set_flash('error', (string) $t['err_locator']); redirect('admin_members'); }
    $updates = ['callsign = ?', 'full_name = ?', 'email = ?', 'locator = ?', 'is_active = ?', 'is_committee = ?'];
    $params = [$callsign, $fullName, $email, $locator, isset($_POST['is_active']) ? 1 : 0, isset($_POST['is_committee']) ? 1 : 0];
    if ($passwordResetForceAvailable) {
        $forcePasswordReset = isset($_POST['password_change_required']);
        $updates[] = 'password_change_required = ?';
        $updates[] = 'password_reset_forced_at = ?';
        $params[] = $forcePasswordReset ? 1 : 0;
        $params[] = $forcePasswordReset ? date('Y-m-d H:i:s') : null;
    }
    $params[] = (int) ($_POST['member_id'] ?? 0);
    db()->prepare('UPDATE members SET ' . implode(', ', $updates) . ' WHERE id = ? LIMIT 1')->execute($params);
    set_flash('success', (string) $t['member_updated']);
    $postReturnQuery = trim((string) ($_POST['return_query'] ?? ''));
    redirect('admin_members' . ($postReturnQuery !== '' ? '&' . $postReturnQuery : ''));
}

$memberSearch = trim((string) ($_GET['member_q'] ?? ''));
$memberSort = (string) ($_GET['sort'] ?? 'callsign');
$memberPage = max(1, (int) ($_GET['page'] ?? 1));
$memberPerPage = 25;
$memberDir = strtolower((string) ($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
$allowedSort = ['callsign', 'full_name', 'email', 'locator', 'is_active', 'is_committee'];
if (!in_array($memberSort, $allowedSort, true)) { $memberSort = 'callsign'; }
$memberColumns = 'id, callsign, full_name, email, locator, is_active, is_committee';
if ($passwordChangeColumnAvailable) {
    $memberColumns .= ', password_change_required';
}
if ($passwordResetMarkerColumnAvailable) {
    $memberColumns .= ', password_reset_forced_at';
}
$members = db()->query('SELECT ' . $memberColumns . ' FROM members ORDER BY callsign')->fetchAll();
usort($members, static function (array $a, array $b) use ($memberSort, $memberDir): int { $cmp = strnatcasecmp((string) ($a[$memberSort] ?? ''), (string) ($b[$memberSort] ?? '')); return $memberDir === 'desc' ? -$cmp : $cmp; });
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

ob_start();
?>
<section class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <form method="get" style="margin:.5rem 0 1rem;">
        <label><?= e((string) $t['search']) ?>
            <input type="text" name="member_q" value="<?= e($memberSearch) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
        </label>
        <input type="hidden" name="sort" value="<?= e($memberSort) ?>"><input type="hidden" name="dir" value="<?= e($memberDir) ?>"><button class="button secondary" type="submit"><?= e((string) ($t['search_btn'] ?? 'OK')) ?></button>
    </form>
    <div class="table-wrap"><table><thead><tr>
        <th><?= e((string) $t['th_callsign']) ?></th><th><?= e((string) $t['th_name']) ?></th><th><?= e((string) $t['th_email']) ?></th><th><?= e((string) $t['th_locator']) ?></th><th><?= e((string) $t['th_active']) ?></th><th><?= e((string) $t['th_committee']) ?></th><th><?= e((string) $t['th_password_reset']) ?></th><th><?= e((string) $t['th_actions']) ?></th>
    </tr></thead><tbody>
    <?php foreach ($members as $member): ?>
        <tr><td colspan="8"><form method="post" class="grid" style="grid-template-columns: 1fr 1fr 1fr 1fr auto auto minmax(11rem, auto) auto; gap:.5rem; align-items:center;">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>"><input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
            <input type="text" name="callsign" value="<?= e((string) $member['callsign']) ?>"><input type="text" name="full_name" value="<?= e((string) $member['full_name']) ?>"><input type="email" name="email" value="<?= e((string) $member['email']) ?>"><input type="text" name="locator" value="<?= e((string) $member['locator']) ?>" maxlength="6">
            <label><input type="checkbox" name="is_active" value="1" <?= (int) $member['is_active'] === 1 ? 'checked' : '' ?>></label>
            <label><input type="checkbox" name="is_committee" value="1" <?= (int) $member['is_committee'] === 1 ? 'checked' : '' ?>></label>
            <?php if ($passwordResetForceAvailable): ?>
                <?php $passwordResetForced = (int) ($member['password_change_required'] ?? 0) === 1 && trim((string) ($member['password_reset_forced_at'] ?? '')) !== ''; ?>
                <label><input type="checkbox" name="password_change_required" value="1" <?= $passwordResetForced ? 'checked' : '' ?>> <?= e((string) $t['password_reset_force']) ?></label>
            <?php else: ?>
                <span class="help"><?= e((string) $t['password_reset_unavailable']) ?></span>
            <?php endif; ?>
            <button class="button" type="submit"><?= e((string) $t['save']) ?></button>
        </form></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
