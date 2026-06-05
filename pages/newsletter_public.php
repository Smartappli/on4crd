<?php
declare(strict_types=1);

newsletter_ensure_tables();
$locale = current_locale();
$t = i18n_domain_translator('newsletter_public', $locale);

set_page_meta([
    'title' => $t('title'),
    'description' => $t('desc'),
]);

$prefillEmail = trim((string) ($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $email = newsletter_normalize_email((string) ($_POST['email'] ?? ''));
        if ($email === '') {
            throw new RuntimeException($t('invalid_email'));
        }
        if ((string) ($_POST['newsletter_consent'] ?? '') !== '1') {
            throw new RuntimeException($t('consent_required'));
        }

        if (!newsletter_upsert_subscriber($email, null, 'public_form', true, $t('consent_proof_public'))) {
            throw new RuntimeException($t('invalid_email'));
        }

        set_flash('success', $t('ok'));
        redirect('newsletter_public');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        $prefillEmail = trim((string) ($_POST['email'] ?? ''));
    }
}

ob_start();
?>
<div class="card">
    <h1><?= e($t('title')) ?></h1>
    <p><?= e($t('intro')) ?></p>
    <form method="post" class="stack">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <label for="newsletter-public-email"><?= e($t('email_label')) ?></label>
        <input id="newsletter-public-email" type="email" name="email" value="<?= e($prefillEmail) ?>" required>
        <label class="checkbox-row">
            <input type="checkbox" name="newsletter_consent" value="1" required>
            <span><?= e($t('consent_label')) ?></span>
        </label>
        <?= privacy_notice_short_html('newsletter') ?>
        <button type="submit" class="button"><?= e($t('submit')) ?></button>
    </form>
</div>
<?php

echo render_layout((string) ob_get_clean(), $t('title'));
