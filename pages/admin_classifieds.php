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

$categories = ['gear' => (string) $t['gear'], 'wanted' => (string) $t['wanted'], 'service' => (string) $t['service']];
$statuses = ['active' => (string) $t['active'], 'sold' => (string) $t['sold'], 'archived' => (string) $t['archived']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'save');
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            throw new RuntimeException((string) $t['invalid']);
        }

        if ($action === 'delete') {
            db()->prepare('DELETE FROM classified_ads WHERE id = ?')->execute([$id]);
            set_flash('success', (string) $t['deleted']);
            redirect_url(route_url('admin_classifieds'));
        }

        $category = (string) ($_POST['category_code'] ?? 'gear');
        $status = (string) ($_POST['status'] ?? 'active');
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $contact = trim((string) ($_POST['contact'] ?? ''));

        if ($title === '' || $description === '' || $contact === '' || !isset($categories[$category]) || !isset($statuses[$status])) {
            throw new RuntimeException((string) $t['invalid']);
        }

        db()->prepare('UPDATE classified_ads SET category_code = ?, title = ?, description = ?, location = ?, contact = ?, price_cents = ?, status = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$category, $title, $description, $location, $contact, max(0, parse_price_to_cents((string) ($_POST['price'] ?? '0'))), $status, $id]);
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

$rows = db()->query('SELECT ca.*, m.callsign FROM classified_ads ca LEFT JOIN members m ON m.id = ca.owner_member_id ORDER BY ca.updated_at DESC, ca.id DESC')->fetchAll() ?: [];
$stats = [
    'total' => count($rows),
    'active' => count(array_filter($rows, static fn(array $row): bool => (string) $row['status'] === 'active')),
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
        <?php if ($rows === []): ?><p class="help"><?= e((string) $t['no_ads']) ?></p><?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th><?= e((string) $t['ad_title']) ?></th><th><?= e((string) $t['owner']) ?></th><th><?= e((string) $t['status']) ?></th><th><?= e((string) $t['actions']) ?></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><strong><?= e((string) $row['title']) ?></strong><div class="help"><?= e((string) ($categories[$row['category_code']] ?? $row['category_code'])) ?> - <?= e(format_price_eur((int) $row['price_cents'])) ?></div></td>
                    <td><?= e((string) ($row['callsign'] ?? 'N/A')) ?></td>
                    <td><span class="badge muted"><?= e((string) ($statuses[$row['status']] ?? $row['status'])) ?></span></td>
                    <td><a href="<?= e(route_url('admin_classifieds', ['edit' => (int) $row['id']])) ?>"><?= e((string) $t['edit_ad']) ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['title']);
