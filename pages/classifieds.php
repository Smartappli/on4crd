<?php
declare(strict_types=1);

$locale = current_locale();
$messages = i18n_domain_locale('classifieds', $locale);
$t = static function (string $key) use ($messages): string {
    return (string) ($messages[$key] ?? $key);
};

if (!module_enabled('classifieds')) {
    echo render_layout('<div class="card"><p>' . e($t('module_disabled')) . '</p></div>', $t('title'));
    return;
}

if (!ensure_classified_ads_table()) {
    $message = '<section class="card"><h1>' . e($t('title')) . '</h1><p class="help">' . e($t('storage_unavailable')) . '</p></section>';
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
    'pending' => $t('status_pending'),
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
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'save');

        if ($action === 'propose_category') {
            $user = require_login();
            $proposalName = trim(strip_tags((string) ($_POST['proposal_name'] ?? '')));
            $proposalEmail = trim((string) ($_POST['proposal_email'] ?? ''));
            $proposalCategory = trim(strip_tags((string) ($_POST['proposal_category'] ?? '')));
            $proposalDetails = trim(strip_tags((string) ($_POST['proposal_details'] ?? '')));

            if (
                $proposalName === ''
                || mb_strlen($proposalName) > 190
                || !filter_var($proposalEmail, FILTER_VALIDATE_EMAIL)
                || mb_strlen($proposalEmail) > 190
                || $proposalCategory === ''
                || mb_strlen($proposalCategory) > 120
                || mb_strlen($proposalDetails) > 1200
            ) {
                throw new RuntimeException($t('propose_category_invalid'));
            }

            $safeName = str_replace(["\r", "\n"], ' ', $proposalName);
            $safeEmail = str_replace(["\r", "\n"], '', $proposalEmail);
            $subject = $t('propose_category_subject');
            $body = $t('propose_category_email_intro') . "\n\n"
                . $t('propose_category_name_label') . ': ' . $proposalCategory . "\n"
                . $t('propose_category_sender_name_label') . ': ' . $safeName . "\n"
                . $t('propose_category_sender_email_label') . ': ' . $safeEmail . "\n\n"
                . $t('propose_category_details_label') . ":\n" . ($proposalDetails !== '' ? $proposalDetails : '-') . "\n";
            $headers = 'From: ON4CRD Website <no-reply@on4crd.be>' . "\r\n"
                . 'Reply-To: ' . $safeEmail . "\r\n"
                . 'Content-Type: text/plain; charset=UTF-8';

            if (@mail(site_contact_email(), $subject, $body, $headers)) {
                set_flash('success', $t('propose_category_sent'));
            } else {
                set_flash('error', $t('propose_category_failed'));
            }

            redirect_url(route_url('classifieds'));
        }

        $user = require_login();

        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $category = (string) ($_POST['category_code'] ?? 'gear');
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $location = trim((string) ($_POST['location'] ?? ''));
            $contact = trim((string) ($_POST['contact'] ?? ''));
            $priceCents = max(0, parse_price_to_cents((string) ($_POST['price'] ?? '0')));
            $requestedStatus = classifieds_member_publication_status((string) ($_POST['status'] ?? 'draft'));
            classifieds_validate_payload($category, $title, $description, $location, $contact, $categories, $t('invalid'));

            if ($id > 0) {
                if (!classifieds_member_ad_exists($id, (int) $user['id'])) {
                    throw new RuntimeException($t('missing'));
                }
                $expiresAt = null;
                if ($requestedStatus === 'active') {
                    $expiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
                }
                $stmt = db()->prepare('UPDATE classified_ads SET category_code = ?, title = ?, description = ?, location = ?, contact = ?, price_cents = ?, status = ?, expires_at = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
                $stmt->execute([$category, $title, $description, $location, $contact, $priceCents, $requestedStatus, $expiresAt, $id, (int) $user['id']]);
                set_flash('success', $t('updated_ok'));
            } else {
                classifieds_enforce_submission_limits((int) $user['id'], [
                    'invalid_user' => $t('limit_invalid_user'),
                    'rate_limited' => $t('limit_rate_limited'),
                    'daily_limit' => $t('limit_daily'),
                ]);
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
            if (!classifieds_member_ad_exists($id, (int) $user['id'])) {
                throw new RuntimeException($t('missing'));
            }
            $status = (string) ($_POST['status'] ?? 'active');
            if (!in_array($status, ['draft', 'pending', 'active', 'sold', 'archived'], true)) {
                throw new RuntimeException($t('invalid'));
            }
            $status = classifieds_member_publication_status($status);
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
            if (!classifieds_member_ad_exists($id, (int) $user['id'])) {
                throw new RuntimeException($t('missing'));
            }
            $status = has_permission('ads.moderate') ? 'active' : 'pending';
            $expiresAt = $status === 'active' ? date('Y-m-d H:i:s', time() + (30 * 86400)) : null;
            $stmt = db()->prepare('UPDATE classified_ads SET status = ?, expires_at = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
            $stmt->execute([$status, $expiresAt, $id, (int) $user['id']]);
            set_flash('success', $t('renewed_ok'));
        }

        if ($action === 'toggle_favorite') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $adStmt = db()->prepare('SELECT id, title FROM classified_ads WHERE id = ? AND status = "active" AND (expires_at IS NULL OR expires_at >= NOW()) LIMIT 1');
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

$proposalPrefillName = '';
$proposalPrefillEmail = '';
if ($user !== null) {
    $proposalPrefillName = trim((string) ($user['full_name'] ?? ''));
    if ($proposalPrefillName === '') {
        $proposalPrefillName = trim((string) ($user['callsign'] ?? ''));
    }
    $proposalPrefillEmail = trim((string) ($user['email'] ?? ''));
}
$showCategoryProposalForm = $user !== null && (string) ($_GET['propose_category'] ?? '') === '1';
$renderCategoryProposalForm = static function (bool $dialogMode) use ($t, $proposalPrefillName, $proposalPrefillEmail): string {
    ob_start();
    ?>
    <form method="post" class="classifieds-category-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="propose_category">
        <label>
            <span><?= e($t('propose_category_name_label')) ?></span>
            <input type="text" name="proposal_category" maxlength="120" required placeholder="<?= e($t('propose_category_name_placeholder')) ?>">
        </label>
        <div class="classifieds-category-form-grid">
            <label>
                <span><?= e($t('propose_category_sender_name_label')) ?></span>
                <input type="text" name="proposal_name" maxlength="190" required value="<?= e($proposalPrefillName) ?>">
            </label>
            <label>
                <span><?= e($t('propose_category_sender_email_label')) ?></span>
                <input type="email" name="proposal_email" maxlength="190" required value="<?= e($proposalPrefillEmail) ?>">
            </label>
        </div>
        <label>
            <span><?= e($t('propose_category_details_label')) ?></span>
            <textarea name="proposal_details" rows="4" maxlength="1200" placeholder="<?= e($t('propose_category_details_placeholder')) ?>"></textarea>
        </label>
        <div class="classifieds-category-dialog-actions">
            <button class="button" type="submit"><?= e($t('propose_category_submit')) ?></button>
            <?php if ($dialogMode): ?>
                <button class="button secondary" type="button" data-classifieds-category-close><?= e($t('propose_category_cancel')) ?></button>
            <?php else: ?>
                <a class="button secondary" href="<?= e(route_url('classifieds')) ?>"><?= e($t('propose_category_cancel')) ?></a>
            <?php endif; ?>
        </div>
    </form>
    <?php
    return (string) ob_get_clean();
};

set_page_meta(['title' => $t('title'), 'description' => $t('lead')]);
ob_start();
?>
<section class="classifieds-page">
    <header class="page-hero classifieds-hero">
        <div class="classifieds-hero-copy">
            <p class="directory-eyebrow classifieds-hero-title"><?= e($t('title')) ?></p>
            <h1 class="classifieds-hero-heading"><?= e($t('title')) ?></h1>
            <p class="directory-lead"><?= e($t('lead')) ?></p>
        </div>
        <div class="classifieds-hero-side">
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
            <p class="classifieds-hero-action">
                <a class="button" href="<?= e(route_url('classifieds_manage')) ?>"><?= e($t('propose_ad')) ?></a>
                <?php if ($user !== null): ?>
                    <a class="button secondary" href="<?= e(route_url('classifieds', ['propose_category' => '1'])) ?>" data-classifieds-category-open aria-haspopup="dialog" aria-controls="classifieds-category-dialog"><?= e($t('propose_category')) ?></a>
                <?php else: ?>
                    <a class="button secondary" href="<?= e(route_url('classifieds_manage')) ?>"><?= e($t('propose_category')) ?></a>
                <?php endif; ?>
            </p>
        </div>
    </header>

    <?php if ($user !== null): ?>
    <?php if ($showCategoryProposalForm): ?>
    <section class="card classifieds-category-inline" id="classifieds-category-inline">
        <div class="classifieds-category-dialog-header">
            <div>
                <p class="directory-eyebrow"><?= e($t('category_label')) ?></p>
                <h2><?= e($t('propose_category')) ?></h2>
                <p class="help"><?= e($t('propose_category_intro')) ?></p>
            </div>
        </div>
        <?= $renderCategoryProposalForm(false) ?>
    </section>
    <?php endif; ?>

    <dialog class="classifieds-category-dialog" id="classifieds-category-dialog" aria-labelledby="classifieds-category-title">
        <div class="classifieds-category-dialog-card">
            <div class="classifieds-category-dialog-header">
                <div>
                    <p class="directory-eyebrow"><?= e($t('category_label')) ?></p>
                    <h2 id="classifieds-category-title"><?= e($t('propose_category')) ?></h2>
                    <p class="help"><?= e($t('propose_category_intro')) ?></p>
                </div>
                <button class="classifieds-category-dialog-close" type="button" data-classifieds-category-close aria-label="<?= e($t('propose_category_close')) ?>">&times;</button>
            </div>
            <?= $renderCategoryProposalForm(true) ?>
        </div>
    </dialog>
    <?php endif; ?>

    <section class="wiki-search-panel classifieds-search-bar">
        <form method="get" class="wiki-search-form classifieds-search-form">
            <input type="hidden" name="route" value="classifieds">
            <?php if ($categoryFilter !== ''): ?>
                <input type="hidden" name="category" value="<?= e($categoryFilter) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($query) ?>" placeholder="<?= e($t('search_placeholder')) ?>">
            <button class="button" type="submit"><?= e($t('filter')) ?></button>
            <?php if ($query !== '' || $categoryFilter !== ''): ?>
                <a class="button secondary" href="<?= e(route_url('classifieds')) ?>"><?= e($t('reset')) ?></a>
            <?php endif; ?>
        </form>
    </section>

    <nav class="classifieds-category-strip classifieds-category-filter" aria-label="<?= e($t('category_label')) ?>">
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
                        <?= e((string) ($ad['callsign'] ?? $t('not_available'))) ?>
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
