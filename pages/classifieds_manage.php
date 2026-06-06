<?php
declare(strict_types=1);

$locale = current_locale();
$messages = array_replace(
    i18n_domain_locale('classifieds', $locale),
    i18n_domain_locale('classifieds_manage', $locale)
);
$t = static function (string $key) use ($messages): string {
    return (string) ($messages[$key] ?? $key);
};

if (!module_enabled('classifieds')) {
    echo render_layout('<div class="card"><p>' . e($t('module_disabled')) . '</p></div>', $t('title'));
    return;
}

$user = require_login();

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

$editing = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM classified_ads WHERE id = ? AND owner_member_id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit'], (int) $user['id']]);
    $editing = $stmt->fetch() ?: null;
    if ($editing === null) {
        set_flash('error', $t('missing'));
        redirect_url(route_url('classifieds_manage'));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $requestedStatus = classifieds_member_publication_status((string) ($_POST['status'] ?? 'draft'));
            classifieds_validate_payload($category, $title, $description, $location, $contact, $categories, $t('invalid'));

            $expiresAt = classifieds_expires_at_for_status($requestedStatus);
            if ($id > 0) {
                if (!classifieds_member_ad_exists($id, (int) $user['id'])) {
                    throw new RuntimeException($t('missing'));
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
            $expiresAt = classifieds_expires_at_for_status($status);
            $stmt = db()->prepare('UPDATE classified_ads SET status = ?, expires_at = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
            $stmt->execute([$status, $expiresAt, $id, (int) $user['id']]);
            set_flash('success', $t('status_ok'));
        }

        if ($action === 'renew') {
            $id = (int) ($_POST['id'] ?? 0);
            if (!classifieds_member_ad_exists($id, (int) $user['id'])) {
                throw new RuntimeException($t('missing'));
            }
            $status = classifieds_can_moderate() ? 'active' : 'pending';
            $expiresAt = classifieds_expires_at_for_status($status);
            $stmt = db()->prepare('UPDATE classified_ads SET status = ?, expires_at = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
            $stmt->execute([$status, $expiresAt, $id, (int) $user['id']]);
            set_flash('success', $t('renewed_ok'));
        }

        redirect_url(route_url('classifieds_manage'));
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
        redirect_url(route_url('classifieds_manage'));
    }
}

$myStmt = db()->prepare('SELECT * FROM classified_ads WHERE owner_member_id = ? ORDER BY created_at DESC, id DESC');
$myStmt->execute([(int) $user['id']]);
$myAds = $myStmt->fetchAll() ?: [];

set_page_meta(['title' => $t('manage_page_title'), 'description' => $t('manage_page_description')]);
ob_start();
?>
<section class="classifieds-page classifieds-manage-page">
    <header class="page-hero classifieds-hero">
        <div class="classifieds-hero-copy">
            <p class="directory-eyebrow"><?= e($t('title')) ?></p>
            <h1><?= e($t('manage_hero_title')) ?></h1>
            <p class="directory-lead"><?= e($t('manage_page_description')) ?></p>
        </div>
        <p class="classifieds-manage-back">
            <a class="button secondary" href="<?= e(route_url('classifieds')) ?>"><?= e($t('manage_back_to_ads')) ?></a>
        </p>
    </header>

    <section class="classifieds-member-panel">
        <section class="classifieds-editor">
            <div class="classifieds-panel-heading">
                <h2><?= e($editing ? $t('edit') : $t('manage_submit_section')) ?></h2>
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
                        <option value="active" <?= in_array((string) ($editing['status'] ?? ''), ['active', 'pending'], true) ? 'selected' : '' ?>><?= e(classifieds_can_moderate() ? $t('published_30d') : $t('submit_for_review')) ?></option>
                    </select>
                </label>
                <div class="classifieds-editor-actions">
                    <button class="button"><?= e($t('save')) ?></button>
                    <?php if ($editing): ?><a class="button secondary" href="<?= e(route_url('classifieds_manage')) ?>"><?= e($t('cancel')) ?></a><?php endif; ?>
                </div>
            </form>
        </section>

        <section class="classifieds-my-ads">
            <div class="classifieds-panel-heading">
                <h2><?= e($t('manage_my_ads_section')) ?></h2>
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
                            <p class="help"><?= e((string) ($categories[$ad['category_code']] ?? $ad['category_code'])) ?> / <?= e(format_price_eur((int) $ad['price_cents'])) ?></p>
                            <?php if (!empty($ad['expires_at'])): ?><p class="help"><?= e($t('expires_on')) ?>: <?= e(date('d/m/Y', strtotime((string) $ad['expires_at']))) ?></p><?php endif; ?>
                        </div>
                        <span class="badge muted"><?= e((string) ($statuses[$ad['status']] ?? $ad['status'])) ?></span>
                        <div class="classifieds-my-actions">
                            <a class="button secondary small" href="<?= e(route_url('classifieds_manage', ['edit' => (int) $ad['id']])) ?>"><?= e($t('edit')) ?></a>
                            <form method="post" class="classifieds-status-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="set_status">
                                <input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                                <?php if ((string) $ad['status'] !== 'sold'): ?><button class="button secondary small" name="status" value="sold"><?= e($t('mark_sold')) ?></button><?php endif; ?>
                                <?php if ((string) $ad['status'] !== 'active'): ?><button class="button secondary small" name="status" value="active"><?= e($t('reactivate')) ?></button><?php endif; ?>
                                <?php if ((string) $ad['status'] !== 'archived'): ?><button class="button secondary small" name="status" value="archived"><?= e($t('archive')) ?></button><?php endif; ?>
                                <?php if (in_array((string) $ad['status'], ['active', 'expired'], true)): ?>
                                    <button class="button secondary small" name="action" value="renew"><?= e($t('renew_30d')) ?></button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
</section>
<?php

echo render_layout((string) ob_get_clean(), $t('manage_page_title'));
