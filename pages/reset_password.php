<?php
declare(strict_types=1);

$selector = trim((string) ($_GET['selector'] ?? $_POST['selector'] ?? ''));
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

$locale = current_locale();
$i18n = [
    'fr' => ['err_incomplete' => 'Informations de réinitialisation incomplètes.', 'err_auth_unavailable' => 'Module d’authentification indisponible. Lancez composer install.', 'ok_updated' => 'Mot de passe mis à jour. Vous pouvez vous connecter.', 'err_invalid_link' => 'Le lien est invalide ou expiré.', 'err_reset_disabled' => 'La réinitialisation est désactivée pour ce compte.', 'err_invalid_password' => 'Mot de passe invalide (minimum 8 caractères recommandés).', 'err_too_many' => 'Trop de tentatives. Réessayez plus tard.', 'title' => 'Réinitialiser le mot de passe', 'new_password' => 'Nouveau mot de passe', 'submit' => 'Mettre à jour', 'back_login' => 'Retour à la connexion', 'layout_title' => 'Réinitialisation du mot de passe'],
    'en' => ['err_incomplete' => 'Incomplete reset information.', 'err_auth_unavailable' => 'Authentication module unavailable. Run composer install.', 'ok_updated' => 'Password updated. You can now sign in.', 'err_invalid_link' => 'The link is invalid or expired.', 'err_reset_disabled' => 'Password reset is disabled for this account.', 'err_invalid_password' => 'Invalid password (minimum 8 characters recommended).', 'err_too_many' => 'Too many attempts. Try again later.', 'title' => 'Reset password', 'new_password' => 'New password', 'submit' => 'Update', 'back_login' => 'Back to login', 'layout_title' => 'Password reset'],
    'de' => ['err_incomplete' => 'Unvollständige Informationen zur Zurücksetzung.', 'err_auth_unavailable' => 'Authentifizierungsmodul nicht verfügbar. Führen Sie composer install aus.', 'ok_updated' => 'Passwort aktualisiert. Sie können sich jetzt anmelden.', 'err_invalid_link' => 'Der Link ist ungültig oder abgelaufen.', 'err_reset_disabled' => 'Die Zurücksetzung ist für dieses Konto deaktiviert.', 'err_invalid_password' => 'Ungültiges Passwort (mindestens 8 Zeichen empfohlen).', 'err_too_many' => 'Zu viele Versuche. Bitte später erneut versuchen.', 'title' => 'Passwort zurücksetzen', 'new_password' => 'Neues Passwort', 'submit' => 'Aktualisieren', 'back_login' => 'Zurück zur Anmeldung', 'layout_title' => 'Passwortzurücksetzung'],
    'nl' => ['err_incomplete' => 'Onvolledige resetinformatie.', 'err_auth_unavailable' => 'Authenticatiemodule niet beschikbaar. Voer composer install uit.', 'ok_updated' => 'Wachtwoord bijgewerkt. Je kunt nu inloggen.', 'err_invalid_link' => 'De link is ongeldig of verlopen.', 'err_reset_disabled' => 'Resetten is uitgeschakeld voor dit account.', 'err_invalid_password' => 'Ongeldig wachtwoord (minimaal 8 tekens aanbevolen).', 'err_too_many' => 'Te veel pogingen. Probeer het later opnieuw.', 'title' => 'Wachtwoord resetten', 'new_password' => 'Nieuw wachtwoord', 'submit' => 'Bijwerken', 'back_login' => 'Terug naar inloggen', 'layout_title' => 'Wachtwoord resetten'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $newPassword = (string) ($_POST['password'] ?? '');
        if ($selector === '' || $token === '' || $newPassword === '') {
            throw new RuntimeException($t('err_incomplete'));
        }

        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException($t('err_auth_unavailable'));
        }

        $authClient->resetPassword($selector, $token, $newPassword);
        unset($_SESSION['password_reset_pending']);
        set_flash('success', $t('ok_updated'));
        redirect('login');
    } catch (\Delight\Auth\InvalidSelectorTokenPairException|\Delight\Auth\TokenExpiredException $exception) {
        set_flash('error', $t('err_invalid_link'));
        redirect('forgot_password');
    } catch (\Delight\Auth\ResetDisabledException $exception) {
        set_flash('error', $t('err_reset_disabled'));
        redirect('forgot_password');
    } catch (\Delight\Auth\InvalidPasswordException $exception) {
        set_flash('error', $t('err_invalid_password'));
        redirect_url(route_url('reset_password', ['selector' => $selector, 'token' => $token]));
    } catch (\Delight\Auth\TooManyRequestsException $exception) {
        set_flash('error', $t('err_too_many'));
        redirect('forgot_password');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('forgot_password');
    }
}

$content = '<div class="card narrow login-card"><h1>' . e($t('title')) . '</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<input type="hidden" name="selector" value="' . e($selector) . '">'
    . '<input type="hidden" name="token" value="' . e($token) . '">'
    . '<label>' . e($t('new_password')) . '<input type="password" name="password" minlength="8" required></label>'
    . '<button class="button">' . e($t('submit')) . '</button></form>'
    . '<p><a href="' . e(route_url('login')) . '">' . e($t('back_login')) . '</a></p>'
    . '</div>';

echo render_layout($content, $t('layout_title'));
