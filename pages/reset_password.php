<?php
declare(strict_types=1);

$selector = trim((string) ($_GET['selector'] ?? $_POST['selector'] ?? ''));
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/reset_password.php');
$i18n = i18n_expand_supported_locales($i18n);
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
