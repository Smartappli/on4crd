<?php
declare(strict_types=1);

$user = require_login();
newsletter_ensure_tables();
$locale = current_locale();
$i18n = [
    'fr' => ['meta_title' => 'Préférences newsletter', 'meta_desc' => 'Gestion de votre abonnement newsletter ON4CRD.', 'err_invalid_email' => 'Adresse email invalide.', 'ok_subscribed' => 'Vous êtes abonné à la newsletter.', 'err_no_sub' => 'Aucun abonnement trouvé.', 'ok_unsubscribed' => 'Vous êtes désabonné de la newsletter.', 'title' => 'Préférences newsletter', 'intro' => 'Abonnez-vous pour recevoir les actualités du radio-club par email. Vous pouvez vous désabonner à tout moment.', 'status' => 'Statut actuel :', 'subscribed' => 'abonné', 'not_subscribed' => 'non abonné', 'unsubscribe' => 'Se désabonner', 'email_label' => 'Email de contact', 'subscribe' => "S'abonner", 'layout_title' => 'Newsletter'],
    'en' => ['meta_title' => 'Newsletter preferences', 'meta_desc' => 'Manage your ON4CRD newsletter subscription.', 'err_invalid_email' => 'Invalid email address.', 'ok_subscribed' => 'You are subscribed to the newsletter.', 'err_no_sub' => 'No subscription found.', 'ok_unsubscribed' => 'You are unsubscribed from the newsletter.', 'title' => 'Newsletter preferences', 'intro' => 'Subscribe to receive radio-club news by email. You can unsubscribe at any time.', 'status' => 'Current status:', 'subscribed' => 'subscribed', 'not_subscribed' => 'not subscribed', 'unsubscribe' => 'Unsubscribe', 'email_label' => 'Contact email', 'subscribe' => 'Subscribe', 'layout_title' => 'Newsletter'],
    'de' => ['meta_title' => 'Newsletter-Einstellungen', 'meta_desc' => 'Verwalten Sie Ihr ON4CRD-Newsletter-Abonnement.', 'err_invalid_email' => 'Ungültige E-Mail-Adresse.', 'ok_subscribed' => 'Sie sind für den Newsletter angemeldet.', 'err_no_sub' => 'Kein Abonnement gefunden.', 'ok_unsubscribed' => 'Sie sind vom Newsletter abgemeldet.', 'title' => 'Newsletter-Einstellungen', 'intro' => 'Abonnieren Sie, um Neuigkeiten des Radioclubs per E-Mail zu erhalten. Sie können sich jederzeit abmelden.', 'status' => 'Aktueller Status:', 'subscribed' => 'abonniert', 'not_subscribed' => 'nicht abonniert', 'unsubscribe' => 'Abmelden', 'email_label' => 'Kontakt-E-Mail', 'subscribe' => 'Abonnieren', 'layout_title' => 'Newsletter'],
    'nl' => ['meta_title' => 'Nieuwsbriefvoorkeuren', 'meta_desc' => 'Beheer je ON4CRD-nieuwsbriefabonnement.', 'err_invalid_email' => 'Ongeldig e-mailadres.', 'ok_subscribed' => 'Je bent geabonneerd op de nieuwsbrief.', 'err_no_sub' => 'Geen abonnement gevonden.', 'ok_unsubscribed' => 'Je bent uitgeschreven voor de nieuwsbrief.', 'title' => 'Nieuwsbriefvoorkeuren', 'intro' => 'Schrijf je in om nieuws van de radioclub per e-mail te ontvangen. Je kunt je op elk moment uitschrijven.', 'status' => 'Huidige status:', 'subscribed' => 'geabonneerd', 'not_subscribed' => 'niet geabonneerd', 'unsubscribe' => 'Uitschrijven', 'email_label' => 'Contact e-mail', 'subscribe' => 'Inschrijven', 'layout_title' => 'Nieuwsbrief'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

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
            newsletter_upsert_subscriber($email, $memberId, 'member');
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
<div class="card">
    <h1><?= e($t('title')) ?></h1>
    <p><?= e($t('intro')) ?></p>

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
            <button class="button"><?= e($t('subscribe')) ?></button>
        </form>
    <?php endif; ?>
</div>
<?php

echo render_layout((string) ob_get_clean(), $t('layout_title'));
