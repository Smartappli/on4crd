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
        if (isset($translations[$key])) {
            $pool[$lang] = $translations[$key];
        }
    }
    $t[$key] = i18n_localized_value($pool, $locale, 'fr');
}
set_page_meta(['title' => (string) $t['layout'], 'description' => (string) $t['meta_desc'], 'robots' => 'noindex,nofollow']);

$returnQuery = http_build_query([
    'member_q' => (string) ($_GET['member_q'] ?? ''),
    'sort' => (string) ($_GET['sort'] ?? 'callsign'),
    'dir' => (string) ($_GET['dir'] ?? 'asc'),
    'page' => max(1, (int) ($_GET['page'] ?? 1)),
]);
$passwordChangeColumnAvailable = table_has_column('members', 'password_change_required');
$passwordResetMarkerColumnAvailable = table_has_column('members', 'password_reset_forced_at');
$passwordResetForceAvailable = $passwordChangeColumnAvailable && $passwordResetMarkerColumnAvailable;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'update_member');

    if ($action === 'create_member') {
        try {
            $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $emailInput = trim((string) ($_POST['email'] ?? ''));
            $email = member_contact_email_from_input($emailInput);
            $password = (string) ($_POST['password'] ?? '');
            $locator = strtoupper(trim((string) ($_POST['locator'] ?? '')));

            if ($callsign === '' || mb_strlen($callsign) > 32 || preg_match('/^[A-Z0-9\/-]{2,32}$/', $callsign) !== 1) {
                throw new RuntimeException((string) $t['err_callsign']);
            }
            if ($fullName === '' || mb_strlen($fullName) > 190) {
                throw new RuntimeException((string) $t['err_name']);
            }
            if ($emailInput !== '' && filter_var($emailInput, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException((string) $t['err_email']);
            }
            if ($locator !== '' && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $locator) !== 1) {
                throw new RuntimeException((string) $t['err_locator']);
            }
            if (strlen($password) < 8) {
                throw new RuntimeException((string) $t['err_password']);
            }

            $existsStmt = db()->prepare('SELECT COUNT(*) FROM members WHERE UPPER(callsign) = ?');
            $existsStmt->execute([$callsign]);
            if ((int) $existsStmt->fetchColumn() > 0) {
                throw new RuntimeException((string) $t['err_exists']);
            }

            $authClient = auth();
            if ($authClient === null) {
                throw new RuntimeException((string) $t['auth_unavailable']);
            }

            $authEmail = member_auth_email_for_contact_email($email, $callsign);
            member_cleanup_registration_auth_orphan($authEmail, $callsign);
            $authUserId = 0;
            try {
                $authUserId = (int) $authClient->admin()->createUserWithUniqueUsername($authEmail, $password, $callsign);
            } catch (\Delight\Auth\InvalidEmailException|\Delight\Auth\InvalidPasswordException $exception) {
                throw new RuntimeException((string) $t['err_password']);
            } catch (\Delight\Auth\UserAlreadyExistsException|\Delight\Auth\DuplicateUsernameException $exception) {
                throw new RuntimeException((string) $t['err_exists']);
            }

            $nameParts = member_name_parts_from_full_name($fullName);
            $passwordChangeRequired = isset($_POST['password_change_required']) ? 1 : 0;
            $columns = ['auth_user_id', 'callsign', 'first_name', 'last_name', 'full_name', 'email', 'password_hash', 'locator', 'is_active', 'is_committee'];
            $values = [
                $authUserId,
                $callsign,
                $nameParts['first_name'],
                $nameParts['last_name'],
                $fullName,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $locator !== '' ? $locator : null,
                isset($_POST['is_active']) ? 1 : 0,
                isset($_POST['is_committee']) ? 1 : 0,
            ];
            if ($passwordChangeColumnAvailable) {
                $columns[] = 'password_change_required';
                $values[] = $passwordChangeRequired;
            }
            if ($passwordResetMarkerColumnAvailable) {
                $columns[] = 'password_reset_forced_at';
                $values[] = $passwordChangeRequired === 1 ? date('Y-m-d H:i:s') : null;
            }

            try {
                db()->prepare('INSERT INTO members (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')')
                    ->execute($values);
            } catch (Throwable $exception) {
                member_delete_unlinked_auth_user($authUserId);
                throw $exception;
            }

            set_flash('success', (string) $t['member_created']);
            unset($_SESSION['_admin_member_create_old']);
        } catch (Throwable $throwable) {
            $_SESSION['_admin_member_create_old'] = [
                'callsign' => (string) ($_POST['callsign'] ?? ''),
                'full_name' => (string) ($_POST['full_name'] ?? ''),
                'email' => (string) ($_POST['email'] ?? ''),
                'locator' => (string) ($_POST['locator'] ?? ''),
                'is_active' => isset($_POST['is_active']),
                'is_committee' => isset($_POST['is_committee']),
                'password_change_required' => isset($_POST['password_change_required']),
            ];
            set_flash('error', $throwable->getMessage());
        }
        redirect('admin_members');
    }

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
    $postReturnParams = [];
    parse_str((string) ($_POST['return_query'] ?? ''), $postReturnParams);
    $postReturnSort = (string) ($postReturnParams['sort'] ?? 'callsign');
    $postReturnDir = strtolower((string) ($postReturnParams['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
    $postReturnPage = max(1, (int) ($postReturnParams['page'] ?? 1));
    redirect_url(route_url_clean('admin_members', [
        'member_q' => trim((string) ($postReturnParams['member_q'] ?? '')),
        'sort' => in_array($postReturnSort, ['callsign', 'full_name', 'email', 'locator', 'is_active', 'is_committee'], true) ? $postReturnSort : 'callsign',
        'dir' => $postReturnDir,
        'page' => $postReturnPage,
    ]));
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
$memberWhere = '';
$memberParams = [];
if ($memberSearch !== '') {
    $memberWhere = ' WHERE callsign LIKE ? OR full_name LIKE ? OR email LIKE ?';
    $memberNeedle = '%' . $memberSearch . '%';
    $memberParams = [$memberNeedle, $memberNeedle, $memberNeedle];
}
$memberCountStmt = db()->prepare('SELECT COUNT(*) FROM members' . $memberWhere);
$memberCountStmt->execute($memberParams);
$memberTotal = (int) $memberCountStmt->fetchColumn();
$memberPagination = pagination_state($memberTotal, $memberPage, $memberPerPage);
$memberPage = $memberPagination['page'];
$memberPages = $memberPagination['total_pages'];
$memberOffset = $memberPagination['offset'];
$memberSortSql = $memberSort . ' ' . strtoupper($memberDir) . ', id ASC';
$memberListStmt = db()->prepare('SELECT ' . $memberColumns . ' FROM members' . $memberWhere . ' ORDER BY ' . $memberSortSql . ' LIMIT ' . $memberPerPage . ' OFFSET ' . $memberOffset);
$memberListStmt->execute($memberParams);
$members = $memberListStmt->fetchAll() ?: [];
$memberCreateOld = isset($_SESSION['_admin_member_create_old']) && is_array($_SESSION['_admin_member_create_old'])
    ? $_SESSION['_admin_member_create_old']
    : [];
unset($_SESSION['_admin_member_create_old']);
$memberPageQuery = static function (int $targetPage) use ($memberSearch, $memberSort, $memberDir): string {
    return route_url_clean('admin_members', [
        'member_q' => $memberSearch,
        'sort' => $memberSort,
        'dir' => $memberDir,
        'page' => $targetPage,
    ]);
};

ob_start();
?>
<div class="stack admin-members-module">
<section class="card admin-members-header">
    <div class="admin-section-head">
        <div>
            <h1><?= e((string) $t['title']) ?></h1>
            <p class="help"><?= e((string) $t['meta_desc']) ?></p>
        </div>
        <span class="badge muted"><?= $memberTotal ?> <?= e((string) $t['members']) ?></span>
    </div>
</section>

<section class="card admin-member-create-card">
    <div class="admin-section-head">
        <div>
            <h2><?= e((string) $t['create_title']) ?></h2>
            <p class="help"><?= e((string) $t['temporary_password']) ?></p>
        </div>
    </div>
    <form method="post" class="admin-member-create-form" data-admin-dirty-track>
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_member">
            <label><?= e((string) $t['th_callsign']) ?><input type="text" name="callsign" maxlength="32" value="<?= e((string) ($memberCreateOld['callsign'] ?? '')) ?>" required></label>
            <label><?= e((string) $t['th_name']) ?><input type="text" name="full_name" maxlength="190" value="<?= e((string) ($memberCreateOld['full_name'] ?? '')) ?>" required></label>
            <label><?= e((string) $t['th_email']) ?><input type="email" name="email" maxlength="190" value="<?= e((string) ($memberCreateOld['email'] ?? '')) ?>" placeholder="<?= e(member_default_contact_email()) ?>"></label>
            <label><?= e((string) $t['th_locator']) ?><input type="text" name="locator" maxlength="6" value="<?= e((string) ($memberCreateOld['locator'] ?? '')) ?>"></label>
            <label><?= e((string) $t['temporary_password']) ?><input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
            <label class="admin-member-toggle"><input type="checkbox" name="is_active" value="1" <?= $memberCreateOld === [] || !empty($memberCreateOld['is_active']) ? 'checked' : '' ?>> <?= e((string) $t['th_active']) ?></label>
            <label class="admin-member-toggle"><input type="checkbox" name="is_committee" value="1" <?= !empty($memberCreateOld['is_committee']) ? 'checked' : '' ?>> <?= e((string) $t['th_committee']) ?></label>
            <?php if ($passwordChangeColumnAvailable): ?>
                <label class="admin-member-toggle"><input type="checkbox" name="password_change_required" value="1" <?= $memberCreateOld === [] || !empty($memberCreateOld['password_change_required']) ? 'checked' : '' ?>> <?= e((string) $t['password_reset_force']) ?></label>
            <?php endif; ?>
            <button class="button" type="submit"><?= e((string) $t['create_submit']) ?></button>
    </form>
</section>

<section class="card admin-member-list-card">
    <div class="admin-section-head">
        <div>
            <h2><?= e((string) $t['members']) ?></h2>
            <p class="help"><?= e((string) $t['search_ph']) ?></p>
        </div>
        <form method="get" class="admin-member-search">
        <label><?= e((string) $t['search']) ?>
            <input type="text" name="member_q" value="<?= e($memberSearch) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
        </label>
            <input type="hidden" name="sort" value="<?= e($memberSort) ?>">
            <input type="hidden" name="dir" value="<?= e($memberDir) ?>">
            <button class="button secondary" type="submit"><?= e((string) $t['search_btn']) ?></button>
        </form>
    </div>
    <div class="table-wrap"><table><thead><tr>
        <th><?= e((string) $t['th_callsign']) ?></th><th><?= e((string) $t['th_name']) ?></th><th><?= e((string) $t['th_email']) ?></th><th><?= e((string) $t['th_locator']) ?></th><th><?= e((string) $t['th_active']) ?></th><th><?= e((string) $t['th_committee']) ?></th><th><?= e((string) $t['th_password_reset']) ?></th><th><?= e((string) $t['th_actions']) ?></th>
    </tr></thead><tbody>
    <?php foreach ($members as $member): ?>
        <tr><td colspan="8"><form method="post" class="admin-member-row-form" data-admin-dirty-track>
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update_member"><input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>"><input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
            <input type="text" name="callsign" value="<?= e((string) $member['callsign']) ?>" aria-label="<?= e((string) $t['th_callsign']) ?>"><input type="text" name="full_name" value="<?= e((string) $member['full_name']) ?>" aria-label="<?= e((string) $t['th_name']) ?>"><input type="email" name="email" value="<?= e((string) $member['email']) ?>" aria-label="<?= e((string) $t['th_email']) ?>"><input type="text" name="locator" value="<?= e((string) $member['locator']) ?>" maxlength="6" aria-label="<?= e((string) $t['th_locator']) ?>">
            <label class="admin-member-toggle"><input type="checkbox" name="is_active" value="1" <?= (int) $member['is_active'] === 1 ? 'checked' : '' ?>> <?= e((string) $t['th_active']) ?></label>
            <label class="admin-member-toggle"><input type="checkbox" name="is_committee" value="1" <?= (int) $member['is_committee'] === 1 ? 'checked' : '' ?>> <?= e((string) $t['th_committee']) ?></label>
            <?php if ($passwordResetForceAvailable): ?>
                <?php $passwordResetForced = (int) ($member['password_change_required'] ?? 0) === 1 && trim((string) ($member['password_reset_forced_at'] ?? '')) !== ''; ?>
                <label class="admin-member-toggle"><input type="checkbox" name="password_change_required" value="1" <?= $passwordResetForced ? 'checked' : '' ?>> <?= e((string) $t['password_reset_force']) ?></label>
            <?php else: ?>
                <span class="help"><?= e((string) $t['password_reset_unavailable']) ?></span>
            <?php endif; ?>
            <button class="button" type="submit"><?= e((string) $t['save']) ?></button>
        </form></td></tr>
    <?php endforeach; ?>
    <?php if ($members === []): ?><tr><td colspan="8"><?= e((string) $t['members']) ?></td></tr><?php endif; ?>
    </tbody></table></div>
    <?php if ($memberPages > 1): ?>
        <nav class="admin-pagination" aria-label="<?= e((string) $t['members']) ?>">
            <?php if ($memberPage > 1): ?><a class="button secondary small" href="<?= e($memberPageQuery($memberPage - 1)) ?>" rel="prev">←</a><?php endif; ?>
            <span class="admin-pagination-status" aria-current="page"><?= $memberPage ?> / <?= $memberPages ?></span>
            <?php if ($memberPage < $memberPages): ?><a class="button secondary small" href="<?= e($memberPageQuery($memberPage + 1)) ?>" rel="next">→</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
