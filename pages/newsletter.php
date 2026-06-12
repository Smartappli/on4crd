<?php
declare(strict_types=1);

$user = require_login();
newsletter_ensure_tables();
$locale = current_locale();
$t = i18n_domain_translator('newsletter', $locale);

set_page_meta([
    'title' => $t('meta_title'),
    'description' => $t('meta_desc'),
    'robots' => 'noindex,nofollow',
]);

$memberId = (int) ($user['id'] ?? 0);
$memberEmail = newsletter_normalize_email((string) ($user['email'] ?? ''));
$current = newsletter_subscriber_for_member($memberId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'subscribe') {
            $email = newsletter_normalize_email((string) ($_POST['email'] ?? $memberEmail));
            if ($email === '') {
                throw new RuntimeException($t('err_invalid_email'));
            }
            if ((string) ($_POST['newsletter_consent'] ?? '') !== '1') {
                throw new RuntimeException($t('consent_required'));
            }
            newsletter_upsert_subscriber($email, $memberId, 'member', true, $t('consent_proof_member'));
            set_flash('success', $t('ok_subscribed'));
        } elseif ($action === 'unsubscribe') {
            if ($current === null) {
                throw new RuntimeException($t('err_no_sub'));
            }
            newsletter_set_subscriber_status((int) $current['id'], 'unsubscribed');
            set_flash('success', $t('ok_unsubscribed'));
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }

    redirect('newsletter');
}

$current = newsletter_subscriber_for_member($memberId);
$isSubscribed = $current !== null && (string) ($current['status'] ?? '') === 'active';

ob_start();
?>
<div class="newsletter-page stack">
<section class="card newsletter-hero member-module-hero">
    <p class="eyebrow"><?= e(member_area_eyebrow_label($locale)) ?></p>
    <h1><?= e($t('title')) ?></h1>
    <p><?= e($t('intro')) ?></p>
</section>

<section class="card newsletter-preferences-card">
    <?php if ($isSubscribed): ?>
        <p><strong><?= e($t('status')) ?></strong> <?= e($t('subscribed')) ?> (<?= e((string) $current['email']) ?>)</p>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="unsubscribe">
            <button class="button danger"><?= e($t('unsubscribe')) ?></button>
        </form>
    <?php else: ?>
        <p><strong><?= e($t('status')) ?></strong> <?= e($t('not_subscribed')) ?></p>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="subscribe">
            <label><?= e($t('email_label')) ?>
                <input type="email" name="email" value="<?= e($memberEmail) ?>" required>
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="newsletter_consent" value="1" required>
                <span><?= e($t('consent_label')) ?></span>
            </label>
            <?= privacy_notice_short_html('newsletter') ?>
            <button class="button"><?= e($t('subscribe')) ?></button>
        </form>
    <?php endif; ?>
</section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $t('layout_title'));
