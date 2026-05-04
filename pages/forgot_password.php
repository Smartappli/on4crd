<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['err_email_required' => 'Veuillez renseigner votre email.', 'err_auth_unavailable' => 'Module d’authentification indisponible. Lancez composer install.', 'ok_sent' => 'Si un compte existe pour cet email, un lien de réinitialisation a été généré.', 'err_invalid_email' => 'Email invalide.', 'err_not_verified' => 'Compte non vérifié.', 'err_reset_disabled' => 'La réinitialisation est désactivée pour ce compte.', 'err_too_many' => 'Trop de demandes. Réessayez plus tard.', 'title' => 'Mot de passe oublié', 'submit' => 'Envoyer le lien', 'back_login' => 'Retour à la connexion'],
    'en' => ['err_email_required' => 'Please enter your email address.', 'err_auth_unavailable' => 'Authentication module unavailable. Run composer install.', 'ok_sent' => 'If an account exists for this email, a reset link has been generated.', 'err_invalid_email' => 'Invalid email address.', 'err_not_verified' => 'Account not verified.', 'err_reset_disabled' => 'Password reset is disabled for this account.', 'err_too_many' => 'Too many requests. Try again later.', 'title' => 'Forgot password', 'submit' => 'Send link', 'back_login' => 'Back to login'],
    'de' => ['err_email_required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.', 'err_auth_unavailable' => 'Authentifizierungsmodul nicht verfügbar. Führen Sie composer install aus.', 'ok_sent' => 'Wenn ein Konto für diese E-Mail existiert, wurde ein Zurücksetzungslink erstellt.', 'err_invalid_email' => 'Ungültige E-Mail-Adresse.', 'err_not_verified' => 'Konto nicht verifiziert.', 'err_reset_disabled' => 'Die Zurücksetzung ist für dieses Konto deaktiviert.', 'err_too_many' => 'Zu viele Anfragen. Bitte später erneut versuchen.', 'title' => 'Passwort vergessen', 'submit' => 'Link senden', 'back_login' => 'Zurück zur Anmeldung'],
    'nl' => ['err_email_required' => 'Vul je e-mailadres in.', 'err_auth_unavailable' => 'Authenticatiemodule niet beschikbaar. Voer composer install uit.', 'ok_sent' => 'Als er een account bestaat voor dit e-mailadres, is een resetlink gegenereerd.', 'err_invalid_email' => 'Ongeldig e-mailadres.', 'err_not_verified' => 'Account niet geverifieerd.', 'err_reset_disabled' => 'Resetten is uitgeschakeld voor dit account.', 'err_too_many' => 'Te veel aanvragen. Probeer het later opnieuw.', 'title' => 'Wachtwoord vergeten', 'submit' => 'Link verzenden', 'back_login' => 'Terug naar inloggen'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email === '') {
            throw new RuntimeException($t('err_email_required'));
        }

        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException($t('err_auth_unavailable'));
        }

        $authClient->forgotPassword($email, static function (string $selector, string $token): void {
            // Intégration e-mail à brancher ici (SMTP/API) pour transmettre le lien de reset.
            // Le callback est obligatoire pour récupérer selector/token.
            $_SESSION['password_reset_pending'] = hash('sha256', $selector . ':' . $token);
        });

        set_flash('success', $t('ok_sent'));
        redirect('forgot_password');
    } catch (\Delight\Auth\InvalidEmailException $exception) {
        set_flash('error', $t('err_invalid_email'));
        redirect('forgot_password');
    } catch (\Delight\Auth\EmailNotVerifiedException $exception) {
        set_flash('error', $t('err_not_verified'));
        redirect('forgot_password');
    } catch (\Delight\Auth\ResetDisabledException $exception) {
        set_flash('error', $t('err_reset_disabled'));
        redirect('forgot_password');
    } catch (\Delight\Auth\TooManyRequestsException $exception) {
        set_flash('error', $t('err_too_many'));
        redirect('forgot_password');
    }
}

$content = '<div class="card narrow login-card"><h1>' . e($t('title')) . '</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>Email<input type="email" name="email" required></label>'
    . '<button class="button">' . e($t('submit')) . '</button></form>'
    . '<p><a href="' . e(route_url('login')) . '">' . e($t('back_login')) . '</a></p>';

$content .= '</div>';

echo render_layout($content, $t('title'));
