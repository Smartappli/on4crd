<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/admin_committee.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, $key);
}
$tr = static function (string $key, string $fallback) use ($t): string {
    $value = trim((string) ($t[$key] ?? ''));

    return $value !== '' ? $value : $fallback;
};

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $memberId = (int) ($_POST['member_id'] ?? 0);
        $memberStmt = db()->prepare('SELECT id FROM members WHERE id = ? AND is_active = 1 LIMIT 1');
        $memberStmt->execute([$memberId]);
        if (!$memberStmt->fetchColumn()) {
            throw new RuntimeException($tr('invalid_member', 'Membre invalide.'));
        }

        $role = trim((string) ($_POST['committee_role'] ?? ''));
        $bio = trim((string) ($_POST['committee_bio'] ?? ''));
        if (mb_strlen($role) > 190 || mb_strlen($bio) > 5000) {
            throw new RuntimeException($tr('invalid_member', 'Membre invalide.'));
        }

        db()->prepare('UPDATE members SET is_committee = ?, committee_role = ?, committee_bio = ?, committee_sort_order = ? WHERE id = ?')
            ->execute([
                !empty($_POST['is_committee']) ? 1 : 0,
                $role !== '' ? $role : null,
                $bio !== '' ? $bio : null,
                (int) ($_POST['committee_sort_order'] ?? 100),
                $memberId,
            ]);
        set_flash('success', (string) $t['updated']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_committee');
}

$rows = db()->query('SELECT id, callsign, full_name, is_committee, committee_role, committee_bio, committee_sort_order FROM members WHERE is_active = 1 ORDER BY callsign ASC')->fetchAll() ?: [];
$committeeRows = db()->query('SELECT id, callsign, full_name, avatar_path, photo_path, committee_role, committee_sort_order FROM members WHERE is_active = 1 AND is_committee = 1 ORDER BY committee_sort_order ASC, callsign ASC')->fetchAll() ?: [];

$selectedId = (int) ($_GET['member_id'] ?? 0);
if ($selectedId <= 0 && $committeeRows !== []) {
    $selectedId = (int) ($committeeRows[0]['id'] ?? 0);
}
if ($selectedId <= 0 && $rows !== []) {
    $selectedId = (int) ($rows[0]['id'] ?? 0);
}
$selectedMember = null;
foreach ($rows as $row) {
    if ((int) ($row['id'] ?? 0) === $selectedId) {
        $selectedMember = $row;
        break;
    }
}

ob_start();
?>
<div class="stack admin-committee-module">
    <section class="card">
        <h1><?= e((string) $t['title']) ?></h1>
        <p><?= e((string) $t['intro']) ?></p>
        <?php if ($selectedMember === null): ?>
            <p class="help"><?= e($tr('no_active_members', 'Aucun membre actif disponible.')) ?></p>
        <?php else: ?>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="form-grid">
                <label><?= e($tr('member', 'Membre')) ?>
                    <select name="member_id" required>
                        <?php foreach ($rows as $row): ?>
                            <option value="<?= (int) $row['id'] ?>" <?= (int) $row['id'] === (int) $selectedMember['id'] ? 'selected' : '' ?>>
                                <?= e((string) $row['callsign']) ?> - <?= e((string) $row['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?= e((string) $t['sort_order']) ?>
                    <input type="number" name="committee_sort_order" value="<?= e((string) ($selectedMember['committee_sort_order'] ?? 100)) ?>">
                </label>
                <label class="admin-committee-toggle">
                    <input type="checkbox" name="is_committee" value="1" <?= (int) ($selectedMember['is_committee'] ?? 0) === 1 ? 'checked' : '' ?>>
                    <?= e((string) $t['show_on_page']) ?>
                </label>
                <label><?= e((string) $t['role']) ?>
                    <input type="text" name="committee_role" maxlength="190" value="<?= e((string) ($selectedMember['committee_role'] ?? '')) ?>">
                </label>
                <label class="admin-committee-wide"><?= e((string) $t['bio']) ?>
                    <textarea name="committee_bio" rows="4" maxlength="5000"><?= e((string) ($selectedMember['committee_bio'] ?? '')) ?></textarea>
                </label>
            </div>
            <p><button class="button"><?= e((string) $t['save']) ?></button></p>
        </form>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="row-between">
            <h2><?= e($tr('summary_title', 'Tableau de synthese')) ?></h2>
            <span class="badge muted"><?= count($committeeRows) ?> <?= e($tr('committee_members', 'membre(s)')) ?></span>
        </div>
        <?php if ($committeeRows === []): ?>
            <p class="help"><?= e($tr('empty_summary', 'Aucun membre n est affiche sur la page comite.')) ?></p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><?= e($tr('member', 'Membre')) ?></th>
                            <th><?= e((string) $t['role']) ?></th>
                            <th><?= e((string) $t['sort_order']) ?></th>
                            <th><?= e($tr('action', 'Action')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($committeeRows as $row): ?>
                            <tr>
                                <td>
                                    <div class="admin-committee-member">
                                        <img class="admin-committee-avatar" src="<?= e(member_avatar_src($row)) ?>" alt="<?= e($tr('avatar_alt', 'Avatar du membre')) ?> <?= e((string) $row['callsign']) ?>" loading="lazy" decoding="async">
                                        <span>
                                            <strong><?= e((string) $row['callsign']) ?></strong>
                                            <span class="help"><?= e((string) $row['full_name']) ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td><?= e((string) ($row['committee_role'] ?? '')) ?></td>
                                <td><?= (int) ($row['committee_sort_order'] ?? 100) ?></td>
                                <td><a href="<?= e(route_url('admin_committee', ['member_id' => (int) $row['id']])) ?>"><?= e($tr('edit', 'Modifier')) ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
