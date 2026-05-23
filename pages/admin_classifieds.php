<?php
declare(strict_types=1);

require_permission('ads.moderate');

$locale = current_locale();
$i18n = [
    'fr' => [
        'title' => 'Administration petites annonces',
        'meta_desc' => 'Modération et gestion des petites annonces du club.',
        'saved' => 'Annonce mise à jour.',
        'deleted' => 'Annonce supprimée.',
        'invalid' => 'Données invalides.',
        'all_ads' => 'Toutes les annonces',
        'edit_ad' => 'Modifier une annonce',
        'no_ads' => 'Aucune annonce.',
        'owner' => 'Membre',
        'category' => 'Catégorie',
        'ad_title' => 'Titre',
        'description' => 'Description',
        'location' => 'Localisation',
        'contact' => 'Contact',
        'price' => 'Prix',
        'status' => 'Statut',
        'created' => 'Création',
        'actions' => 'Actions',
        'save' => 'Enregistrer',
        'delete' => 'Supprimer',
        'cancel' => 'Annuler',
        'active' => 'Active',
        'sold' => 'Vendue',
        'archived' => 'Archivée',
        'gear' => 'Matériel',
        'wanted' => 'Recherche',
        'service' => 'Service',
        'stats_total' => 'Annonces',
        'stats_active' => 'Actives',
        'stats_sold' => 'Vendues',
    ],
    'en' => [
        'title' => 'Classifieds administration',
        'meta_desc' => 'Moderation and management of club classifieds.',
        'saved' => 'Ad updated.',
        'deleted' => 'Ad deleted.',
        'invalid' => 'Invalid data.',
        'all_ads' => 'All ads',
        'edit_ad' => 'Edit an ad',
        'no_ads' => 'No ad.',
        'owner' => 'Member',
        'category' => 'Category',
        'ad_title' => 'Title',
        'description' => 'Description',
        'location' => 'Location',
        'contact' => 'Contact',
        'price' => 'Price',
        'status' => 'Status',
        'created' => 'Created',
        'actions' => 'Actions',
        'save' => 'Save',
        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'active' => 'Active',
        'sold' => 'Sold',
        'archived' => 'Archived',
        'gear' => 'Gear',
        'wanted' => 'Wanted',
        'service' => 'Service',
        'stats_total' => 'Ads',
        'stats_active' => 'Active',
        'stats_sold' => 'Sold',
    ],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, $key);
}

if (!table_exists('classified_ads')) {
    echo render_layout('<section class="card"><h1>' . e((string) $t['title']) . '</h1><p class="help">Table classified_ads manquante.</p></section>', (string) $t['title']);
    return;
}
classifieds_sync_expired();

$categories = ['gear' => (string) $t['gear'], 'wanted' => (string) $t['wanted'], 'service' => (string) $t['service']];
$statuses = [
    'draft' => 'Brouillon',
    'active' => (string) $t['active'],
    'sold' => (string) $t['sold'],
    'archived' => (string) $t['archived'],
    'expired' => 'Expirée',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'save');
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            throw new RuntimeException((string) $t['invalid']);
        }

        if ($action === 'bulk_update') {
            $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), static fn(int $v): bool => $v > 0));
            if ($ids === []) {
                throw new RuntimeException((string) $t['invalid']);
            }
            $bulkOp = (string) ($_POST['bulk_op'] ?? '');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            if ($bulkOp === 'delete') {
                db()->prepare('DELETE FROM classified_ads WHERE id IN (' . $placeholders . ')')->execute($ids);
            } else {
                $allowed = ['draft', 'active', 'sold', 'archived', 'expired'];
                if (!in_array($bulkOp, $allowed, true)) {
                    throw new RuntimeException((string) $t['invalid']);
                }
                $expiresAt = $bulkOp === 'active' ? date('Y-m-d H:i:s', time() + (30 * 86400)) : null;
                $sql = 'UPDATE classified_ads SET status = ?, expires_at = ?, updated_at = NOW() WHERE id IN (' . $placeholders . ')';
                db()->prepare($sql)->execute(array_merge([$bulkOp, $expiresAt], $ids));
            }
            set_flash('success', (string) $t['saved']);
            redirect_url(route_url('admin_classifieds'));
        }

        if ($action === 'delete') {
            db()->prepare('DELETE FROM classified_ads WHERE id = ?')->execute([$id]);
            set_flash('success', (string) $t['deleted']);
            redirect_url(route_url('admin_classifieds'));
        }

        $category = (string) ($_POST['category_code'] ?? 'gear');
        $status = (string) ($_POST['status'] ?? 'draft');
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $contact = trim((string) ($_POST['contact'] ?? ''));

        if ($title === '' || $description === '' || $contact === '' || !isset($categories[$category]) || !isset($statuses[$status])) {
            throw new RuntimeException((string) $t['invalid']);
        }

        $expiresAtRaw = trim((string) ($_POST['expires_at'] ?? ''));
        $expiresAtValue = null;
        if ($expiresAtRaw !== '') {
            $timestamp = strtotime($expiresAtRaw);
            if ($timestamp === false) {
                throw new RuntimeException((string) $t['invalid']);
            }
            $expiresAtValue = date('Y-m-d H:i:s', $timestamp);
        } elseif ($status === 'active') {
            $expiresAtValue = date('Y-m-d H:i:s', time() + (30 * 86400));
        }

        db()->prepare('UPDATE classified_ads SET category_code = ?, title = ?, description = ?, location = ?, contact = ?, price_cents = ?, status = ?, expires_at = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$category, $title, $description, $location, $contact, max(0, parse_price_to_cents((string) ($_POST['price'] ?? '0'))), $status, $expiresAtValue, $id]);
        set_flash('success', (string) $t['saved']);
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
$stats = [
    'total' => count($rows),
    'active' => count(array_filter($rows, static fn(array $row): bool => (string) $row['status'] === 'active' && (empty($row['expires_at']) || strtotime((string) $row['expires_at']) >= time()))),
    'sold' => count(array_filter($rows, static fn(array $row): bool => (string) $row['status'] === 'sold')),
];

set_page_meta([
    'title' => (string) $t['title'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

ob_start();
?>
<div class="grid-3 stats-grid">
    <div class="stat-card"><strong><?= (int) $stats['total'] ?></strong><span><?= e((string) $t['stats_total']) ?></span></div>
    <div class="stat-card"><strong><?= (int) $stats['active'] ?></strong><span><?= e((string) $t['stats_active']) ?></span></div>
    <div class="stat-card"><strong><?= (int) $stats['sold'] ?></strong><span><?= e((string) $t['stats_sold']) ?></span></div>
</div>

<div class="grid-2">
    <section class="card">
        <h1><?= e((string) $t['edit_ad']) ?></h1>
        <?php if ($edit === null): ?>
            <p class="help"><?= e((string) $t['no_ads']) ?></p>
        <?php else: ?>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
            <p class="help"><?= e((string) $t['owner']) ?>: <?= e((string) ($edit['callsign'] ?? 'N/A')) ?></p>
            <label><?= e((string) $t['category']) ?>
                <select name="category_code">
                    <?php foreach ($categories as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= (string) $edit['category_code'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e((string) $t['ad_title']) ?><input type="text" name="title" maxlength="190" required value="<?= e((string) $edit['title']) ?>"></label>
            <label><?= e((string) $t['description']) ?><textarea name="description" rows="6" required><?= e((string) $edit['description']) ?></textarea></label>
            <div class="grid-2">
                <label><?= e((string) $t['price']) ?><input type="text" name="price" value="<?= e(number_format(((int) $edit['price_cents']) / 100, 2, ',', '')) ?>"></label>
                <label><?= e((string) $t['status']) ?>
                    <select name="status">
                        <?php foreach ($statuses as $code => $label): ?>
                            <option value="<?= e($code) ?>" <?= (string) $edit['status'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Expiration
                <input type="datetime-local" name="expires_at" value="<?= !empty($edit['expires_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $edit['expires_at']))) : '' ?>">
            </label>
            <label><?= e((string) $t['location']) ?><input type="text" name="location" maxlength="120" value="<?= e((string) ($edit['location'] ?? '')) ?>"></label>
            <label><?= e((string) $t['contact']) ?><input type="text" name="contact" maxlength="190" required value="<?= e((string) ($edit['contact'] ?? '')) ?>"></label>
            <p><button class="button"><?= e((string) $t['save']) ?></button> <a class="button ghost" href="<?= e(route_url('admin_classifieds')) ?>"><?= e((string) $t['cancel']) ?></a></p>
        </form>
        <form method="post" onsubmit="return confirm('Supprimer cette annonce ?');">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
            <button class="button ghost"><?= e((string) $t['delete']) ?></button>
        </form>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2><?= e((string) $t['all_ads']) ?></h2>
        <form method="get" class="inline-form" style="margin-bottom:.7rem;flex-wrap:wrap;">
            <input type="hidden" name="route" value="admin_classifieds">
            <input type="search" name="q" value="<?= e($adminSearch) ?>" placeholder="Recherche">
            <select name="status">
                <option value="">Tous statuts</option>
                <?php foreach ($statuses as $statusCode => $statusLabel): ?>
                    <option value="<?= e($statusCode) ?>" <?= $adminStatus === $statusCode ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="category">
                <option value="">Toutes catégories</option>
                <?php foreach ($categories as $categoryCode => $categoryLabel): ?>
                    <option value="<?= e($categoryCode) ?>" <?= $adminCategory === $categoryCode ? 'selected' : '' ?>><?= e($categoryLabel) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button" type="submit">Filtrer</button>
            <a class="button secondary" href="<?= e(route_url('admin_classifieds')) ?>">Reset</a>
        </form>
        <?php if ($rows === []): ?><p class="help"><?= e((string) $t['no_ads']) ?></p><?php else: ?>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="bulk_update">
            <div class="inline-form" style="margin-bottom:.7rem;">
                <select name="bulk_op">
                    <option value="active">Publier (active)</option>
                    <option value="draft">Passer en brouillon</option>
                    <option value="sold">Passer en vendue</option>
                    <option value="archived">Archiver</option>
                    <option value="delete">Supprimer</option>
                </select>
                <button class="button secondary" type="submit">Appliquer</button>
            </div>
        <div class="table-wrap"><table>
            <thead><tr><th></th><th><?= e((string) $t['ad_title']) ?></th><th><?= e((string) $t['owner']) ?></th><th><?= e((string) $t['status']) ?></th><th><?= e((string) $t['actions']) ?></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= (int) $row['id'] ?>"></td>
                    <td><strong><?= e((string) $row['title']) ?></strong><div class="help"><?= e((string) ($categories[$row['category_code']] ?? $row['category_code'])) ?> - <?= e(format_price_eur((int) $row['price_cents'])) ?></div></td>
                    <td><?= e((string) ($row['callsign'] ?? 'N/A')) ?></td>
                    <td>
                        <span class="badge muted"><?= e((string) ($statuses[$row['status']] ?? $row['status'])) ?></span>
                        <?php if (!empty($row['expires_at'])): ?><div class="help">Expire: <?= e(date('d/m/Y', strtotime((string) $row['expires_at']))) ?></div><?php endif; ?>
                    </td>
                    <td><a href="<?= e(route_url('admin_classifieds', ['edit' => (int) $row['id']])) ?>"><?= e((string) $t['edit_ad']) ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        </form>
        <?php if ($totalPages > 1): ?>
            <nav class="actions mt-3">
                <?php if ($page > 1): ?><a class="button secondary" href="<?= e(route_url_clean('admin_classifieds', ['q' => $adminSearch, 'status' => $adminStatus, 'category' => $adminCategory, 'p' => $page - 1])) ?>">&larr; Prev</a><?php endif; ?>
                <span class="badge muted"><?= $page ?> / <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?><a class="button secondary" href="<?= e(route_url_clean('admin_classifieds', ['q' => $adminSearch, 'status' => $adminStatus, 'category' => $adminCategory, 'p' => $page + 1])) ?>">Next &rarr;</a><?php endif; ?>
            </nav>
        <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['title']);
