<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/forgot_password.php';
$i18n = i18n_expand_supported_locales($i18n);
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
    . '<label>' . e($t('email_label')) . '<input type="email" name="email" required></label>'
    . '<button class="button">' . e($t('submit')) . '</button></form>'
    . '<p><a href="' . e(route_url('login')) . '">' . e($t('back_login')) . '</a></p>';

$content .= '</div>';

echo render_layout($content, $t('title'));
