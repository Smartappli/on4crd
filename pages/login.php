<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/login.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = $i18n[$locale] ?? $i18n['fr'];

if (current_user() !== null) {
    redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $captcha = trim((string) ($_POST['captcha'] ?? ''));
        $captchaExpected = (string) ($_SESSION['login_captcha'] ?? '');

        if ($callsign === '' || $password === '' || $captcha === '') {
            throw new RuntimeException((string) $t['required']);
        }
        if (!hash_equals($captchaExpected, $captcha)) {
            throw new RuntimeException((string) $t['captcha_invalid']);
        }
        unset($_SESSION['login_captcha']);
        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException((string) $t['auth_unavailable']);
        }

        try {
            $authClient->loginWithUsername($callsign, $password);
        } catch (\Delight\Auth\UnknownUsernameException|\Delight\Auth\InvalidPasswordException $exception) {
            throw new RuntimeException((string) $t['invalid_credentials']);
        } catch (\Delight\Auth\TooManyRequestsException $exception) {
            throw new RuntimeException((string) $t['too_many']);
        } catch (\Delight\Auth\EmailNotVerifiedException $exception) {
            throw new RuntimeException((string) $t['not_verified']);
        }

        $_SESSION['member_id'] = (int) $authClient->getUserId();
        set_flash('success', (string) $t['login_success']);
        redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('login');
    }
}

$captchaA = random_int(1, 9);
$captchaB = random_int(1, 9);
$captchaExpected = $captchaA + $captchaB;
$_SESSION['login_captcha'] = (string) $captchaExpected;

$content = '<div class="card narrow login-card"><h1>' . e((string) $t['title']) . '</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>' . e((string) $t['callsign']) . '<input type="text" name="callsign" required></label>'
    . '<label>' . e((string) $t['password']) . '<input type="password" name="password" required></label>'
    . '<label>' . e((string) $t['captcha_question']) . ' ' . $captchaA . ' + ' . $captchaB . ' ?'
    . '<input type="text" name="captcha" inputmode="numeric" autocomplete="off" required></label>'
    . '<button class="button">' . e((string) $t['login']) . '</button></form>'
    . '<p><a href="' . e(route_url('forgot_password')) . '">' . e((string) $t['forgot_password']) . '</a></p>'
    . '<p>' . e((string) $t['no_member']) . ' <a href="' . e(route_url('register')) . '">' . e((string) $t['create_account']) . '</a></p>'
    . '</div>';

echo render_layout($content, (string) $t['title']);
