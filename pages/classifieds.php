<?php
declare(strict_types=1);

$locale = current_locale();
$messages = i18n_domain_locale('classifieds', $locale);
$t = static function (string $key) use ($messages): string {
    return (string) ($messages[$key] ?? $key);
};

if (!module_enabled('classifieds')) {
    echo render_layout('<div class="card"><p>Module disabled.</p></div>', $t('title'));
    return;
}

if (!table_exists('classified_ads')) {
    $message = '<section class="card"><h1>' . e($t('title')) . '</h1><p class="help">Module temporairement indisponible : table <code>classified_ads</code> manquante.</p></section>';
    echo render_layout($message, $t('title'));
    return;
}

$categories = [
    'gear' => $t('category_gear'),
    'wanted' => $t('category_wanted'),
    'service' => $t('category_service'),
];
$statuses = [
    'active' => $t('status_active'),
    'sold' => $t('status_sold'),
    'archived' => $t('status_archived'),
];

$user = current_user();
$editing = null;
if ($user !== null && !empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM classified_ads WHERE id = ? AND owner_member_id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit'], (int) $user['id']]);
    $editing = $stmt->fetch() ?: null;
    if ($editing === null) {
        set_flash('error', $t('missing'));
        redirect_url(route_url('classifieds'));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_login();
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'save');

        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $category = (string) ($_POST['category_code'] ?? 'gear');
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $location = trim((string) ($_POST['location'] ?? ''));
            $contact = trim((string) ($_POST['contact'] ?? ''));
            $priceCents = max(0, parse_price_to_cents((string) ($_POST['price'] ?? '0')));

            if ($title === '' || $description === '' || $contact === '' || !isset($categories[$category])) {
                throw new RuntimeException($t('invalid'));
            }

            if ($id > 0) {
                $stmt = db()->prepare('UPDATE classified_ads SET category_code = ?, title = ?, description = ?, location = ?, contact = ?, price_cents = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
                $stmt->execute([$category, $title, $description, $location, $contact, $priceCents, $id, (int) $user['id']]);
                set_flash('success', $t('updated_ok'));
            } else {
                $stmt = db()->prepare('INSERT INTO classified_ads (owner_member_id, category_code, title, description, location, contact, price_cents, status) VALUES (?, ?, ?, ?, ?, ?, ?, "active")');
                $stmt->execute([(int) $user['id'], $category, $title, $description, $location, $contact, $priceCents]);
                set_flash('success', $t('created_ok'));
            }
        }

        if ($action === 'set_status') {
            $id = (int) ($_POST['id'] ?? 0);
            $status = (string) ($_POST['status'] ?? 'active');
            if (!in_array($status, ['active', 'sold', 'archived'], true)) {
                throw new RuntimeException($t('invalid'));
            }
            $stmt = db()->prepare('UPDATE classified_ads SET status = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
            $stmt->execute([$status, $id, (int) $user['id']]);
            set_flash('success', $t('status_ok'));
        }

        redirect_url(route_url('classifieds'));
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
        redirect_url(route_url('classifieds'));
    }
}

$categoryFilter = (string) ($_GET['category'] ?? '');
$query = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($query) > 120) {
    $query = mb_substr($query, 0, 120);
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$where = ["ca.status = 'active'"];
$params = [];

if (isset($categories[$categoryFilter])) {
    $where[] = 'ca.category_code = ?';
    $params[] = $categoryFilter;
}
if ($query !== '') {
    $where[] = '(ca.title LIKE ? OR ca.description LIKE ? OR ca.location LIKE ?)';
    $needle = '%' . $query . '%';
    $params[] = $needle;
    $params[] = $needle;
    $params[] = $needle;
}

$whereSql = implode(' AND ', $where);
$countStmt = db()->prepare("SELECT COUNT(*) FROM classified_ads ca WHERE $whereSql");
$countStmt->execute($params);
$totalAds = (int) $countStmt->fetchColumn();
$pagination = pagination_state($totalAds, $page, $perPage);
$page = $pagination['page'];
$maxPage = $pagination['total_pages'];
$offset = $pagination['offset'];

$adsStmt = db()->prepare("SELECT ca.*, m.callsign FROM classified_ads ca LEFT JOIN members m ON m.id = ca.owner_member_id WHERE $whereSql ORDER BY ca.created_at DESC, ca.id DESC LIMIT $perPage OFFSET $offset");
$adsStmt->execute($params);
$allAds = $adsStmt->fetchAll() ?: [];

$myAds = [];
if ($user !== null) {
    $myStmt = db()->prepare('SELECT * FROM classified_ads WHERE owner_member_id = ? ORDER BY created_at DESC, id DESC');
    $myStmt->execute([(int) $user['id']]);
    $myAds = $myStmt->fetchAll() ?: [];
}

set_page_meta(['title' => $t('title'), 'description' => $t('lead')]);
ob_start();
?>
<section class="stack classifieds-page">
    <section class="card">
        <div class="section-header">
            <div>
                <h1><?= e($t('title')) ?></h1>
                <p class="help"><?= e($t('lead')) ?></p>
            </div>
            <?php if ($user === null): ?>
                <a class="button" href="<?= e(route_url('login')) ?>"><?= e($t('login_to_post')) ?></a>
            <?php endif; ?>
        </div>
        <form method="get" class="grid-3">
            <input type="hidden" name="route" value="classifieds">
            <label><?= e($t('search_label')) ?><input type="search" name="q" value="<?= e($query) ?>" placeholder="<?= e($t('search_placeholder')) ?>"></label>
            <label><?= e($t('category_label')) ?>
                <select name="category">
                    <option value=""><?= e($t('all_categories')) ?></option>
                    <?php foreach ($categories as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= $categoryFilter === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="actions" style="align-self:end;"><button class="button"><?= e($t('filter')) ?></button><a class="button ghost" href="<?= e(route_url('classifieds')) ?>"><?= e($t('reset')) ?></a></div>
        </form>
    </section>

    <?php if ($user !== null): ?>
    <div class="grid-2">
        <section class="card">
            <h2><?= e($editing ? $t('edit') : $t('new_ad')) ?></h2>
            <form method="post" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                <label><?= e($t('category_label')) ?>
                    <select name="category_code">
                        <?php foreach ($categories as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= (($editing['category_code'] ?? 'gear') === $code) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?= e($t('title_label')) ?><input type="text" name="title" maxlength="190" required value="<?= e((string) ($editing['title'] ?? '')) ?>"></label>
                <label><?= e($t('description_label')) ?><textarea name="description" rows="5" required><?= e((string) ($editing['description'] ?? '')) ?></textarea></label>
                <div class="grid-2">
                    <label><?= e($t('price_label')) ?><input type="text" name="price" value="<?= e(number_format(((int) ($editing['price_cents'] ?? 0)) / 100, 2, ',', '')) ?>"></label>
                    <label><?= e($t('location_label')) ?><input type="text" name="location" maxlength="120" value="<?= e((string) ($editing['location'] ?? '')) ?>"></label>
                </div>
                <label><?= e($t('contact_label')) ?><input type="text" name="contact" maxlength="190" required value="<?= e((string) ($editing['contact'] ?? ((string) ($user['email'] ?? $user['callsign'] ?? '')))) ?>"></label>
                <p><button class="button"><?= e($t('save')) ?></button><?php if ($editing): ?> <a class="button ghost" href="<?= e(route_url('classifieds')) ?>"><?= e($t('cancel')) ?></a><?php endif; ?></p>
            </form>
        </section>

        <section class="card">
            <h2><?= e($t('my_ads')) ?></h2>
            <?php if ($myAds === []): ?><p class="help"><?= e($t('none')) ?></p><?php else: ?>
                <div class="table-wrap"><table><thead><tr><th><?= e($t('title_label')) ?></th><th><?= e($t('status_label')) ?></th><th><?= e($t('actions')) ?></th></tr></thead><tbody>
                <?php foreach ($myAds as $ad): ?>
                    <tr>
                        <td><strong><?= e((string) $ad['title']) ?></strong><div class="help"><?= e((string) ($categories[$ad['category_code']] ?? $ad['category_code'])) ?> - <?= e(format_price_eur((int) $ad['price_cents'])) ?></div></td>
                        <td><span class="badge muted"><?= e((string) ($statuses[$ad['status']] ?? $ad['status'])) ?></span></td>
                        <td>
                            <a href="<?= e(route_url('classifieds', ['edit' => (int) $ad['id']])) ?>"><?= e($t('edit')) ?></a>
                            <form method="post" class="inline-form" style="display:inline-flex;gap:.4rem;margin-left:.5rem;">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                                <?php if ((string) $ad['status'] !== 'sold'): ?><button class="button ghost" name="status" value="sold"><?= e($t('mark_sold')) ?></button><?php endif; ?>
                                <?php if ((string) $ad['status'] !== 'active'): ?><button class="button ghost" name="status" value="active"><?= e($t('reactivate')) ?></button><?php endif; ?>
                                <?php if ((string) $ad['status'] !== 'archived'): ?><button class="button ghost" name="status" value="archived"><?= e($t('archive')) ?></button><?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </section>
    </div>
    <?php endif; ?>

    <section class="card">
        <div class="section-header"><h2><?= e($t('all_ads')) ?></h2><span class="badge"><?= $totalAds ?></span></div>
        <?php if ($allAds === []): ?><p class="help"><?= e($t('none')) ?></p><?php else: ?>
            <div class="grid-3">
                <?php foreach ($allAds as $ad): ?>
                <article class="card feature-card" style="margin:0;">
                    <div class="section-header">
                        <h3 style="margin:0;"><?= e((string) $ad['title']) ?></h3>
                        <strong class="price-tag"><?= e(format_price_eur((int) $ad['price_cents'])) ?></strong>
                    </div>
                    <p class="help"><?= e((string) ($categories[$ad['category_code']] ?? $ad['category_code'])) ?> - <?= e((string) ($ad['callsign'] ?? 'N/A')) ?><?php if ((string) ($ad['location'] ?? '') !== ''): ?> - <?= e((string) $ad['location']) ?><?php endif; ?></p>
                    <p><?= nl2br(e((string) $ad['description'])) ?></p>
                    <p><strong><?= e($t('contact_label')) ?>:</strong> <?= e((string) $ad['contact']) ?></p>
                </article>
                <?php endforeach; ?>
            </div>
            <?php if ($maxPage > 1): ?>
                <div class="actions mt-3">
                    <?php if ($page > 1): ?><a class="button secondary" href="<?= e(route_url_clean('classifieds', ['q' => $query, 'category' => $categoryFilter, 'page' => $page - 1])) ?>"><?= e($t('previous')) ?></a><?php endif; ?>
                    <span class="pill"><?= e($t('page')) ?> <?= $page ?> / <?= $maxPage ?></span>
                    <?php if ($page < $maxPage): ?><a class="button secondary" href="<?= e(route_url_clean('classifieds', ['q' => $query, 'category' => $categoryFilter, 'page' => $page + 1])) ?>"><?= e($t('next')) ?></a><?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</section>
<?php

echo render_layout((string) ob_get_clean(), $t('title'));
