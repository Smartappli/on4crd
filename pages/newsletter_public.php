<?php
declare(strict_types=1);

newsletter_ensure_tables();
$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Inscription newsletter', 'desc' => 'Inscrivez-vous à la newsletter ON4CRD.', 'invalid_email' => 'Adresse email invalide.', 'ok' => 'Votre inscription à la newsletter est confirmée.', 'intro' => 'Recevez les actualités du Radio Club Durnal directement par email.', 'email_label' => 'Email newsletter', 'submit' => "S'inscrire à la newsletter"],
    'en' => ['title' => 'Newsletter signup', 'desc' => 'Subscribe to the ON4CRD newsletter.', 'invalid_email' => 'Invalid email address.', 'ok' => 'Your newsletter subscription is confirmed.', 'intro' => 'Receive Radio Club Durnal news directly by email.', 'email_label' => 'Newsletter email', 'submit' => 'Subscribe to newsletter'],
    'de' => ['title' => 'Newsletter-Anmeldung', 'desc' => 'Melden Sie sich für den ON4CRD-Newsletter an.', 'invalid_email' => 'Ungültige E-Mail-Adresse.', 'ok' => 'Ihre Newsletter-Anmeldung wurde bestätigt.', 'intro' => 'Erhalten Sie Neuigkeiten des Radio Club Durnal direkt per E-Mail.', 'email_label' => 'Newsletter-E-Mail', 'submit' => 'Für Newsletter anmelden'],
    'nl' => ['title' => 'Nieuwsbriefinschrijving', 'desc' => 'Schrijf je in voor de ON4CRD-nieuwsbrief.', 'invalid_email' => 'Ongeldig e-mailadres.', 'ok' => 'Je nieuwsbriefinschrijving is bevestigd.', 'intro' => 'Ontvang nieuws van Radio Club Durnal rechtstreeks per e-mail.', 'email_label' => 'Nieuwsbrief e-mail', 'submit' => 'Inschrijven op nieuwsbrief'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

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

        if (!newsletter_upsert_subscriber($email, null, 'public_form')) {
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
        <button type="submit" class="button"><?= e($t('submit')) ?></button>
    </form>
</div>
<?php

echo render_layout((string) ob_get_clean(), $t('title'));
