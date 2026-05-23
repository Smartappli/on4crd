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
classifieds_sync_expired();

$categories = [
    'gear' => $t('category_gear'),
    'wanted' => $t('category_wanted'),
    'service' => $t('category_service'),
];
$statuses = [
    'draft' => $t('status_draft'),
    'active' => $t('status_active'),
    'sold' => $t('status_sold'),
    'archived' => $t('status_archived'),
    'expired' => $t('status_expired'),
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
            $requestedStatus = (string) ($_POST['status'] ?? 'draft');
            if (!in_array($requestedStatus, ['draft', 'active', 'sold', 'archived'], true)) {
                $requestedStatus = 'draft';
            }

            if ($title === '' || $description === '' || $contact === '' || !isset($categories[$category])) {
                throw new RuntimeException($t('invalid'));
            }

            if ($id > 0) {
                $expiresAt = null;
                if ($requestedStatus === 'active') {
                    $expiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
                }
                $stmt = db()->prepare('UPDATE classified_ads SET category_code = ?, title = ?, description = ?, location = ?, contact = ?, price_cents = ?, status = ?, expires_at = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
                $stmt->execute([$category, $title, $description, $location, $contact, $priceCents, $requestedStatus, $expiresAt, $id, (int) $user['id']]);
                set_flash('success', $t('updated_ok'));
            } else {
                $expiresAt = null;
                if ($requestedStatus === 'active') {
                    $expiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
                }
                $stmt = db()->prepare('INSERT INTO classified_ads (owner_member_id, category_code, title, description, location, contact, price_cents, status, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([(int) $user['id'], $category, $title, $description, $location, $contact, $priceCents, $requestedStatus, $expiresAt]);
                set_flash('success', $t('created_ok'));
            }
        }

        if ($action === 'set_status') {
            $id = (int) ($_POST['id'] ?? 0);
            $status = (string) ($_POST['status'] ?? 'active');
            if (!in_array($status, ['draft', 'active', 'sold', 'archived'], true)) {
                throw new RuntimeException($t('invalid'));
            }
            $expiresAt = null;
            if ($status === 'active') {
                $expiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
            }
            $stmt = db()->prepare('UPDATE classified_ads SET status = ?, expires_at = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
            $stmt->execute([$status, $expiresAt, $id, (int) $user['id']]);
            set_flash('success', $t('status_ok'));
        }

        if ($action === 'renew') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = db()->prepare('UPDATE classified_ads SET status = "active", expires_at = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
            $stmt->execute([date('Y-m-d H:i:s', time() + (30 * 86400)), $id, (int) $user['id']]);
            set_flash('success', $t('renewed_ok'));
        }

        if ($action === 'toggle_favorite') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $adStmt = db()->prepare('SELECT id, title FROM classified_ads WHERE id = ? LIMIT 1');
                $adStmt->execute([$id]);
                $adRow = $adStmt->fetch() ?: null;
                if ($adRow !== null) {
                    $title = (string) ($adRow['title'] ?? $t('default_ad_title'));
                    $url = route_url('classifieds', ['q' => $title]);
                    $saved = favorite_toggle((int) $user['id'], 'classified_ad', (int) $adRow['id'], $title, $url);
                    notify_member((int) $user['id'], 'favorite', $saved ? $t('favorite_added') : $t('favorite_removed'), $title, $url);
                    set_flash('success', $saved ? $t('favorite_added_msg') : $t('favorite_removed_msg'));
                }
            }
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
$where = ["ca.status = 'active'", '(ca.expires_at IS NULL OR ca.expires_at >= NOW())'];
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

$activeCategoryCounts = array_fill_keys(array_keys($categories), 0);
$categoryCountStmt = db()->query("SELECT category_code, COUNT(*) AS total FROM classified_ads WHERE status = 'active' AND (expires_at IS NULL OR expires_at >= NOW()) GROUP BY category_code");
foreach (($categoryCountStmt ? ($categoryCountStmt->fetchAll() ?: []) : []) as $countRow) {
    $code = (string) ($countRow['category_code'] ?? '');
    if (array_key_exists($code, $activeCategoryCounts)) {
        $activeCategoryCounts[$code] = (int) ($countRow['total'] ?? 0);
    }
}

$myAds = [];
if ($user !== null) {
    $myStmt = db()->prepare('SELECT * FROM classified_ads WHERE owner_member_id = ? ORDER BY created_at DESC, id DESC');
    $myStmt->execute([(int) $user['id']]);
    $myAds = $myStmt->fetchAll() ?: [];
}

set_page_meta(['title' => $t('title'), 'description' => $t('lead')]);
ob_start();
?>
<section class="classifieds-page">
    <header class="classifieds-hero">
        <div class="classifieds-hero-copy">
            <p class="directory-eyebrow"><?= e($t('title')) ?></p>
            <h1><?= e($t('title')) ?></h1>
            <p class="directory-lead"><?= e($t('lead')) ?></p>
        </div>
        <div class="classifieds-stats">
            <div class="classifieds-stat">
                <span><?= (int) $totalAds ?></span>
                <p><?= e($t('all_ads')) ?></p>
            </div>
            <div class="classifieds-stat">
                <span><?= (int) array_sum($activeCategoryCounts) ?></span>
                <p><?= e($t('status_active')) ?></p>
            </div>
            <div class="classifieds-stat">
                <span><?= (int) count($myAds) ?></span>
                <p><?= e($t('my_ads')) ?></p>
            </div>
        </div>
    </header>

    <section class="classifieds-search-panel">
        <div class="classifieds-search-header">
            <div>
                <h2><?= e($t('search_label')) ?></h2>
                <p class="help"><?= e($t('search_placeholder')) ?></p>
            </div>
            <?php if ($user === null): ?>
                <a class="button" href="<?= e(route_url('login')) ?>"><?= e($t('login_to_post')) ?></a>
            <?php endif; ?>
        </div>
        <form method="get" class="classifieds-filter-form">
            <input type="hidden" name="route" value="classifieds">
            <label>
                <span><?= e($t('search_label')) ?></span>
                <input type="search" name="q" value="<?= e($query) ?>" placeholder="<?= e($t('search_placeholder')) ?>">
            </label>
            <label>
                <span><?= e($t('category_label')) ?></span>
                <select name="category">
                    <option value=""><?= e($t('all_categories')) ?></option>
                    <?php foreach ($categories as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= $categoryFilter === $code ? 'selected' : '' ?>><?= e($label) ?> (<?= (int) ($activeCategoryCounts[$code] ?? 0) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="classifieds-filter-actions">
                <button class="button"><?= e($t('filter')) ?></button>
                <?php if ($query !== '' || $categoryFilter !== ''): ?>
                    <a class="button secondary" href="<?= e(route_url('classifieds')) ?>"><?= e($t('reset')) ?></a>
                <?php endif; ?>
            </div>
        </form>
        <nav class="classifieds-category-strip" aria-label="<?= e($t('category_label')) ?>">
            <a class="classifieds-category-pill<?= $categoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('classifieds', ['q' => $query])) ?>">
                <span><?= e($t('all_categories')) ?></span>
                <strong><?= (int) array_sum($activeCategoryCounts) ?></strong>
            </a>
            <?php foreach ($categories as $code => $label): ?>
                <a class="classifieds-category-pill<?= $categoryFilter === $code ? ' is-active' : '' ?>" href="<?= e(route_url_clean('classifieds', ['q' => $query, 'category' => $code])) ?>">
                    <span><?= e($label) ?></span>
                    <strong><?= (int) ($activeCategoryCounts[$code] ?? 0) ?></strong>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <?php if ($user !== null): ?>
    <section class="classifieds-member-panel">
        <section class="classifieds-editor">
            <div class="classifieds-panel-heading">
                <h2><?= e($editing ? $t('edit') : $t('new_ad')) ?></h2>
                <p class="help"><?= e($t('published_30d')) ?></p>
            </div>
            <form method="post" class="classifieds-editor-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                <label><span><?= e($t('category_label')) ?></span>
                    <select name="category_code">
                        <?php foreach ($categories as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= (($editing['category_code'] ?? 'gear') === $code) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span><?= e($t('title_label')) ?></span><input type="text" name="title" maxlength="190" required value="<?= e((string) ($editing['title'] ?? '')) ?>"></label>
                <label class="classifieds-editor-wide"><span><?= e($t('description_label')) ?></span><textarea name="description" rows="5" required><?= e((string) ($editing['description'] ?? '')) ?></textarea></label>
                <label><span><?= e($t('price_label')) ?></span><input type="text" name="price" value="<?= e(number_format(((int) ($editing['price_cents'] ?? 0)) / 100, 2, ',', '')) ?>"></label>
                <label><span><?= e($t('location_label')) ?></span><input type="text" name="location" maxlength="120" value="<?= e((string) ($editing['location'] ?? '')) ?>"></label>
                <label class="classifieds-editor-wide"><span><?= e($t('contact_label')) ?></span><input type="text" name="contact" maxlength="190" required value="<?= e((string) ($editing['contact'] ?? ((string) ($user['email'] ?? $user['callsign'] ?? '')))) ?>"></label>
                <label><span><?= e($t('publication_label')) ?></span>
                    <select name="status">
                        <option value="draft" <?= (($editing['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>><?= e($t('status_draft')) ?></option>
                        <option value="active" <?= (($editing['status'] ?? '') === 'active') ? 'selected' : '' ?>><?= e($t('published_30d')) ?></option>
                    </select>
                </label>
                <div class="classifieds-editor-actions">
                    <button class="button"><?= e($t('save')) ?></button>
                    <?php if ($editing): ?><a class="button secondary" href="<?= e(route_url('classifieds')) ?>"><?= e($t('cancel')) ?></a><?php endif; ?>
                </div>
            </form>
        </section>

        <section class="classifieds-my-ads">
            <div class="classifieds-panel-heading">
                <h2><?= e($t('my_ads')) ?></h2>
                <span class="badge muted"><?= (int) count($myAds) ?></span>
            </div>
            <?php if ($myAds === []): ?>
                <div class="classifieds-empty"><h3><?= e($t('none')) ?></h3></div>
            <?php else: ?>
                <div class="classifieds-my-list">
                <?php foreach ($myAds as $ad): ?>
                    <article class="classifieds-my-card">
                        <div>
                            <h3><?= e((string) $ad['title']) ?></h3>
                            <p class="help"><?= e((string) ($categories[$ad['category_code']] ?? $ad['category_code'])) ?> · <?= e(format_price_eur((int) $ad['price_cents'])) ?></p>
                            <?php if (!empty($ad['expires_at'])): ?><p class="help"><?= e($t('expires_on')) ?>: <?= e(date('d/m/Y', strtotime((string) $ad['expires_at']))) ?></p><?php endif; ?>
                        </div>
                        <span class="badge muted"><?= e((string) ($statuses[$ad['status']] ?? $ad['status'])) ?></span>
                        <div class="classifieds-my-actions">
                            <a class="button secondary small" href="<?= e(route_url('classifieds', ['edit' => (int) $ad['id']])) ?>"><?= e($t('edit')) ?></a>
                            <form method="post" class="classifieds-status-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                                <?php if ((string) $ad['status'] !== 'sold'): ?><button class="button secondary small" name="status" value="sold"><?= e($t('mark_sold')) ?></button><?php endif; ?>
                                <?php if ((string) $ad['status'] !== 'active'): ?><button class="button secondary small" name="status" value="active"><?= e($t('reactivate')) ?></button><?php endif; ?>
                                <?php if ((string) $ad['status'] !== 'archived'): ?><button class="button secondary small" name="status" value="archived"><?= e($t('archive')) ?></button><?php endif; ?>
                                <?php if (in_array((string) $ad['status'], ['active', 'expired'], true)): ?>
                                    <button class="button secondary small" formaction="<?= e(route_url('classifieds')) ?>" name="action" value="renew"><?= e($t('renew_30d')) ?></button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
    <?php endif; ?>

    <section class="classifieds-results">
        <div class="classifieds-results-header">
            <div>
                <h2><?= e($t('all_ads')) ?></h2>
                <p class="help"><?= e($t('lead')) ?></p>
            </div>
            <span class="badge"><?= (int) $totalAds ?></span>
        </div>
        <?php if ($allAds === []): ?>
            <div class="classifieds-empty"><h3><?= e($t('none')) ?></h3></div>
        <?php else: ?>
            <div class="classifieds-grid">
                <?php foreach ($allAds as $ad): ?>
                <?php $isFavorite = $user !== null ? favorite_is_saved((int) $user['id'], 'classified_ad', (int) $ad['id']) : false; ?>
                <article class="classifieds-card">
                    <div class="classifieds-card-top">
                        <span class="badge muted"><?= e((string) ($categories[$ad['category_code']] ?? $ad['category_code'])) ?></span>
                        <strong class="price-tag"><?= e(format_price_eur((int) $ad['price_cents'])) ?></strong>
                    </div>
                    <h3><?= e((string) $ad['title']) ?></h3>
                    <p class="classifieds-card-meta">
                        <?= e((string) ($ad['callsign'] ?? 'N/A')) ?>
                        <?php if ((string) ($ad['location'] ?? '') !== ''): ?> · <?= e((string) $ad['location']) ?><?php endif; ?>
                    </p>
                    <p class="classifieds-card-description"><?= nl2br(e(mb_safe_strimwidth((string) $ad['description'], 0, 260, '...'))) ?></p>
                    <p class="classifieds-card-contact"><strong><?= e($t('contact_label')) ?>:</strong> <?= e((string) $ad['contact']) ?></p>
                    <?php if ($user !== null): ?>
                        <form method="post" class="classifieds-favorite-form">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="toggle_favorite">
                            <input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                            <button class="button secondary" type="submit"><?= $isFavorite ? '★ ' : '☆ ' ?><?= e($t('favorite_label')) ?></button>
                        </form>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
            <?php if ($maxPage > 1): ?>
                <div class="classifieds-pagination">
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
