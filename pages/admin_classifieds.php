<?php
declare(strict_types=1);

require_permission('ads.moderate');

$locale = current_locale();
$t = i18n_domain_locale('classifieds', $locale);
$tText = static function (string $key) use ($t): string {
    $value = (string) ($t[$key] ?? '');
    return $value !== '' ? $value : $key;
};
$formatText = static function (string $key, array $vars = []) use ($tText): string {
    return strtr($tText($key), $vars);
};

if (!ensure_classified_ads_table()) {
    echo render_layout('<section class="card"><h1>' . e($tText('admin_title')) . '</h1><p class="help">' . e($tText('storage_unavailable')) . '</p></section>', $tText('admin_title'));
    return;
}
classifieds_sync_expired();

$categories = [
    'gear' => $tText('category_gear'),
    'wanted' => $tText('category_wanted'),
    'service' => $tText('category_service'),
];
$statuses = [
    'draft' => $tText('status_draft'),
    'pending' => $tText('status_pending'),
    'active' => $tText('status_active'),
    'sold' => $tText('status_sold'),
    'archived' => $tText('status_archived'),
    'expired' => $tText('status_expired'),
];
$notifyModeration = static function (int $ownerId, string $body) use ($tText): void {
    if ($ownerId > 0) {
        notify_member($ownerId, 'moderation', $tText('notification_moderated_title'), $body, route_url('classifieds'));
    }
};
$deleteConfirm = json_encode($tText('delete_confirm'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
if (!is_string($deleteConfirm)) {
    $deleteConfirm = '"delete_confirm"';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'save');
        $id = (int) ($_POST['id'] ?? 0);

        if ($action === 'bulk_update') {
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), static fn(int $v): bool => $v > 0)));
            if ($ids === []) {
                throw new RuntimeException($tText('bulk_no_selection'));
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $ownerStmt = db()->prepare('SELECT id, owner_member_id, title FROM classified_ads WHERE id IN (' . $placeholders . ')');
            $ownerStmt->execute($ids);
            $ownerRows = $ownerStmt->fetchAll() ?: [];
            if (count($ownerRows) !== count($ids)) {
                throw new RuntimeException($tText('invalid'));
            }
            $bulkOp = (string) ($_POST['bulk_op'] ?? '');
            if ($bulkOp === 'delete') {
                db()->prepare('DELETE FROM classified_ads WHERE id IN (' . $placeholders . ')')->execute($ids);
                foreach ($ownerRows as $ownerRow) {
                    $notifyModeration((int) ($ownerRow['owner_member_id'] ?? 0), $formatText('notification_removed_body', [
                        '{title}' => (string) ($ownerRow['title'] ?? ''),
                    ]));
                }
            } else {
                $allowed = ['draft', 'pending', 'active', 'sold', 'archived', 'expired'];
                if (!in_array($bulkOp, $allowed, true)) {
                    throw new RuntimeException($tText('invalid'));
                }
                $expiresAt = classifieds_expires_at_for_status($bulkOp);
                db()->prepare('UPDATE classified_ads SET status = ?, expires_at = ?, updated_at = NOW() WHERE id IN (' . $placeholders . ')')
                    ->execute(array_merge([$bulkOp, $expiresAt], $ids));
                foreach ($ownerRows as $ownerRow) {
                    $notifyModeration((int) ($ownerRow['owner_member_id'] ?? 0), $formatText('notification_status_body', [
                        '{status}' => $statuses[$bulkOp] ?? $bulkOp,
                        '{title}' => (string) ($ownerRow['title'] ?? ''),
                    ]));
                }
            }
            set_flash('success', $tText('saved'));
            redirect_url(route_url('admin_classifieds'));
        }

        if ($id < 1) {
            throw new RuntimeException($tText('invalid'));
        }

        $ownerStmt = db()->prepare('SELECT owner_member_id, title FROM classified_ads WHERE id = ? LIMIT 1');
        $ownerStmt->execute([$id]);
        $ownerRow = $ownerStmt->fetch() ?: null;
        if (!is_array($ownerRow)) {
            throw new RuntimeException($tText('invalid'));
        }

        if ($action === 'delete') {
            db()->prepare('DELETE FROM classified_ads WHERE id = ?')->execute([$id]);
            $notifyModeration((int) ($ownerRow['owner_member_id'] ?? 0), $formatText('notification_removed_body', [
                '{title}' => (string) ($ownerRow['title'] ?? ''),
            ]));
            set_flash('success', $tText('deleted'));
            redirect_url(route_url('admin_classifieds'));
        }

        $category = (string) ($_POST['category_code'] ?? 'gear');
        $status = (string) ($_POST['status'] ?? 'draft');
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $contact = trim((string) ($_POST['contact'] ?? ''));
        if (!isset($statuses[$status])) {
            throw new RuntimeException($tText('invalid'));
        }
        classifieds_validate_payload($category, $title, $description, $location, $contact, $categories, $tText('invalid'));

        $expiresAtRaw = trim((string) ($_POST['expires_at'] ?? ''));
        $expiresAtValue = null;
        if ($expiresAtRaw !== '') {
            $expiresAtDate = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $expiresAtRaw);
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (
                !$expiresAtDate
                || ($dateErrors !== false && ((int) $dateErrors['warning_count'] > 0 || (int) $dateErrors['error_count'] > 0))
            ) {
                throw new RuntimeException($tText('invalid'));
            }
            $expiresAtValue = $expiresAtDate->format('Y-m-d H:i:s');
        } elseif ($status === 'active') {
            $expiresAtValue = classifieds_expires_at_for_status($status);
        }

        db()->prepare('UPDATE classified_ads SET category_code = ?, title = ?, description = ?, location = ?, contact = ?, price_cents = ?, status = ?, expires_at = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$category, $title, $description, $location, $contact, max(0, parse_price_to_cents((string) ($_POST['price'] ?? '0'))), $status, $expiresAtValue, $id]);
        $ownerId = (int) ($ownerRow['owner_member_id'] ?? 0);
        $notifyModeration($ownerId, $formatText('notification_status_body', [
            '{status}' => $statuses[$status] ?? $status,
            '{title}' => $title,
        ]));
        set_flash('success', $tText('saved'));
        redirect_url(route_url('admin_classifieds'));
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect_url(route_url('admin_classifieds'));
    }
}

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT ca.*, m.callsign FROM classified_ads ca LEFT JOIN members m ON m.id = ca.owner_member_id WHERE ca.id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}

$adminStatus = (string) ($_GET['status'] ?? '');
$adminCategory = (string) ($_GET['category'] ?? '');
$adminSearch = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 25;
$where = [];
$params = [];
if ($adminStatus !== '' && isset($statuses[$adminStatus])) {
    $where[] = 'ca.status = ?';
    $params[] = $adminStatus;
}
if ($adminCategory !== '' && isset($categories[$adminCategory])) {
    $where[] = 'ca.category_code = ?';
    $params[] = $adminCategory;
}
if ($adminSearch !== '') {
    $where[] = '(ca.title LIKE ? OR ca.description LIKE ? OR m.callsign LIKE ?)';
    $needle = '%' . $adminSearch . '%';
    array_push($params, $needle, $needle, $needle);
}
$whereSql = $where === [] ? '' : (' WHERE ' . implode(' AND ', $where));
$countStmt = db()->prepare('SELECT COUNT(*) FROM classified_ads ca LEFT JOIN members m ON m.id = ca.owner_member_id' . $whereSql);
$countStmt->execute($params);
$totalRows = (int) ($countStmt->fetchColumn() ?: 0);
$pagination = pagination_state($totalRows, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];
$rowsStmt = db()->prepare('SELECT ca.*, m.callsign FROM classified_ads ca LEFT JOIN members m ON m.id = ca.owner_member_id' . $whereSql . ' ORDER BY ca.updated_at DESC, ca.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset);
$rowsStmt->execute($params);
$rows = $rowsStmt->fetchAll() ?: [];

$statsStmt = db()->prepare(
    'SELECT COUNT(*) AS total,'
    . ' SUM(CASE WHEN ca.status = "active" AND (ca.expires_at IS NULL OR ca.expires_at >= NOW()) THEN 1 ELSE 0 END) AS active,'
    . ' SUM(CASE WHEN ca.status = "sold" THEN 1 ELSE 0 END) AS sold'
    . ' FROM classified_ads ca LEFT JOIN members m ON m.id = ca.owner_member_id'
    . $whereSql
);
$statsStmt->execute($params);
$statsRow = $statsStmt->fetch() ?: [];
$stats = [
    'total' => (int) ($statsRow['total'] ?? $totalRows),
    'active' => (int) ($statsRow['active'] ?? 0),
    'sold' => (int) ($statsRow['sold'] ?? 0),
];

set_page_meta([
    'title' => $tText('admin_title'),
    'description' => $tText('admin_meta_desc'),
    'robots' => 'noindex,nofollow',
]);

ob_start();
?>
<div class="grid-3 stats-grid">
    <div class="stat-card"><strong><?= (int) $stats['total'] ?></strong><span><?= e($tText('stats_total')) ?></span></div>
    <div class="stat-card"><strong><?= (int) $stats['active'] ?></strong><span><?= e($tText('stats_active')) ?></span></div>
    <div class="stat-card"><strong><?= (int) $stats['sold'] ?></strong><span><?= e($tText('stats_sold')) ?></span></div>
</div>

<div class="grid-2">
    <section class="card">
        <h1><?= e($tText('edit_ad')) ?></h1>
        <?php if ($edit === null): ?>
            <p class="help"><?= e($tText('no_ads')) ?></p>
        <?php else: ?>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
            <p class="help"><?= e($tText('owner')) ?>: <?= e((string) ($edit['callsign'] ?? $tText('not_available'))) ?></p>
            <label><?= e($tText('category')) ?>
                <select name="category_code"><?php foreach ($categories as $code => $label): ?><option value="<?= e($code) ?>" <?= (string) $edit['category_code'] === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
            </label>
            <label><?= e($tText('ad_title')) ?><input type="text" name="title" maxlength="190" required value="<?= e((string) $edit['title']) ?>"></label>
            <label><?= e($tText('description')) ?><textarea name="description" rows="6" required><?= e((string) $edit['description']) ?></textarea></label>
            <div class="grid-2">
                <label><?= e($tText('price')) ?><input type="text" name="price" value="<?= e(number_format(((int) $edit['price_cents']) / 100, 2, ',', '')) ?>"></label>
                <label><?= e($tText('status')) ?>
                    <select name="status"><?php foreach ($statuses as $code => $label): ?><option value="<?= e($code) ?>" <?= (string) $edit['status'] === $code ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
                </label>
            </div>
            <label><?= e($tText('expires_on')) ?><input type="datetime-local" name="expires_at" value="<?= !empty($edit['expires_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $edit['expires_at']))) : '' ?>"></label>
            <label><?= e($tText('location')) ?><input type="text" name="location" maxlength="120" value="<?= e((string) ($edit['location'] ?? '')) ?>"></label>
            <label><?= e($tText('contact')) ?><input type="text" name="contact" maxlength="190" required value="<?= e((string) ($edit['contact'] ?? '')) ?>"></label>
            <p><button class="button"><?= e($tText('save')) ?></button> <a class="button ghost" href="<?= e(route_url('admin_classifieds')) ?>"><?= e($tText('cancel')) ?></a></p>
        </form>
        <form method="post" onsubmit="return confirm(<?= e($deleteConfirm) ?>);">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
            <button class="button ghost"><?= e($tText('delete')) ?></button>
        </form>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2><?= e($tText('all_ads')) ?></h2>
        <form method="get" class="inline-form" style="margin-bottom:.7rem;flex-wrap:wrap;">
            <input type="hidden" name="route" value="admin_classifieds">
            <input type="search" name="q" value="<?= e($adminSearch) ?>" placeholder="<?= e($tText('search')) ?>">
            <select name="status"><option value=""><?= e($tText('all_statuses')) ?></option><?php foreach ($statuses as $statusCode => $statusLabel): ?><option value="<?= e($statusCode) ?>" <?= $adminStatus === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option><?php endforeach; ?></select>
            <select name="category"><option value=""><?= e($tText('all_categories')) ?></option><?php foreach ($categories as $categoryCode => $categoryLabel): ?><option value="<?= e($categoryCode) ?>" <?= $adminCategory === $categoryCode ? 'selected' : '' ?>><?= e($categoryLabel) ?></option><?php endforeach; ?></select>
            <button class="button" type="submit"><?= e($tText('filter')) ?></button>
            <a class="button secondary" href="<?= e(route_url('admin_classifieds')) ?>"><?= e($tText('reset')) ?></a>
        </form>
        <?php if ($rows === []): ?><p class="help"><?= e($tText('no_ads')) ?></p><?php else: ?>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="bulk_update">
            <div class="inline-form" style="margin-bottom:.7rem;">
                <select name="bulk_op">
                    <option value="active"><?= e($tText('bulk_publish')) ?></option>
                    <option value="pending"><?= e($tText('bulk_to_pending')) ?></option>
                    <option value="draft"><?= e($tText('bulk_to_draft')) ?></option>
                    <option value="sold"><?= e($tText('bulk_to_sold')) ?></option>
                    <option value="archived"><?= e($tText('bulk_archive')) ?></option>
                    <option value="delete"><?= e($tText('bulk_delete')) ?></option>
                </select>
                <button class="button secondary" type="submit"><?= e($tText('bulk_apply')) ?></button>
            </div>
            <div class="table-wrap"><table>
                <thead><tr><th></th><th><?= e($tText('ad_title')) ?></th><th><?= e($tText('owner')) ?></th><th><?= e($tText('status')) ?></th><th><?= e($tText('actions')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= (int) $row['id'] ?>"></td>
                        <td><strong><?= e((string) $row['title']) ?></strong><div class="help"><?= e((string) ($categories[$row['category_code']] ?? $row['category_code'])) ?> - <?= e(format_price_eur((int) $row['price_cents'])) ?></div></td>
                        <td><?= e((string) ($row['callsign'] ?? $tText('not_available'))) ?></td>
                        <td><span class="badge muted"><?= e((string) ($statuses[$row['status']] ?? $row['status'])) ?></span><?php if (!empty($row['expires_at'])): ?><div class="help"><?= e($tText('expires_on')) ?>: <?= e(date('d/m/Y', strtotime((string) $row['expires_at']))) ?></div><?php endif; ?></td>
                        <td><a href="<?= e(route_url('admin_classifieds', ['edit' => (int) $row['id']])) ?>"><?= e($tText('edit_ad')) ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </form>
        <?php if ($totalPages > 1): ?>
            <nav class="actions mt-3">
                <?php if ($page > 1): ?><a class="button secondary" href="<?= e(route_url_clean('admin_classifieds', ['q' => $adminSearch, 'status' => $adminStatus, 'category' => $adminCategory, 'p' => $page - 1])) ?>">&larr; <?= e($tText('prev')) ?></a><?php endif; ?>
                <span class="badge muted"><?= $page ?> / <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?><a class="button secondary" href="<?= e(route_url_clean('admin_classifieds', ['q' => $adminSearch, 'status' => $adminStatus, 'category' => $adminCategory, 'p' => $page + 1])) ?>"><?= e($tText('next')) ?> &rarr;</a><?php endif; ?>
            </nav>
        <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), $tText('admin_title'));
