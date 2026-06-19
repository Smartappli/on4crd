<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/login.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = $i18n[$locale] ?? $i18n['fr'];
$nextUrl = safe_login_next_url((string) ($_POST['next'] ?? $_GET['next'] ?? ''));
$defaultLoginRedirectUrl = route_url(module_enabled('dashboard') ? 'dashboard' : 'home');
$membershipLabel = $locale === 'fr' ? 'Devenir membre' : 'Become a member';

if (current_user() !== null) {
    redirect_url($nextUrl ?? $defaultLoginRedirectUrl);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $identifier = trim((string) ($_POST['callsign'] ?? ''));
        $callsign = strtoupper($identifier);
        $password = (string) ($_POST['password'] ?? '');
        $captcha = trim((string) ($_POST['captcha'] ?? ''));
        $captchaExpected = (string) ($_SESSION['login_captcha'] ?? '');

        if ($identifier === '' || $password === '' || $captcha === '') {
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
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false) {
                $authClient->login($identifier, $password);
            } else {
                $authClient->loginWithUsername($callsign, $password);
            }
        } catch (\Delight\Auth\InvalidEmailException|\Delight\Auth\UnknownUsernameException|\Delight\Auth\InvalidPasswordException $exception) {
            throw new RuntimeException((string) $t['invalid_credentials']);
        } catch (\Delight\Auth\TooManyRequestsException $exception) {
            throw new RuntimeException((string) $t['too_many']);
        } catch (\Delight\Auth\EmailNotVerifiedException $exception) {
            throw new RuntimeException((string) $t['not_verified']);
        }

        $memberRow = authenticated_member_row($authClient, (int) ($_SESSION['member_id'] ?? 0));
        if ($memberRow === null) {
            try {
                $authClient->logOut();
            } catch (Throwable) {
                // The local session is cleared below even if the auth backend cannot update its tables.
            }
            unset($_SESSION['member_id']);
            throw new RuntimeException((string) ($t['member_unavailable'] ?? $t['auth_unavailable']));
        }

        unset($_SESSION['login_captcha']);
        $_SESSION['member_id'] = (int) ($memberRow['id'] ?? 0);
        set_flash('success', (string) $t['login_success']);
        redirect_url($nextUrl ?? $defaultLoginRedirectUrl);
    } catch (Throwable $throwable) {
        unset($_SESSION['login_captcha'], $_SESSION['login_captcha_operands']);
        set_flash('error', $throwable->getMessage());
        redirect_url(route_url('login', $nextUrl !== null ? ['next' => $nextUrl] : []));
    }
}

$captchaOperands = $_SESSION['login_captcha_operands'] ?? null;
if (
    is_array($captchaOperands)
    && isset($captchaOperands['a'], $captchaOperands['b'])
    && is_int($captchaOperands['a'])
    && is_int($captchaOperands['b'])
) {
    $captchaA = $captchaOperands['a'];
    $captchaB = $captchaOperands['b'];
} else {
    $captchaA = random_int(1, 9);
    $captchaB = random_int(1, 9);
    $_SESSION['login_captcha_operands'] = ['a' => $captchaA, 'b' => $captchaB];
}
$captchaExpected = $captchaA + $captchaB;
$_SESSION['login_captcha'] = (string) $captchaExpected;

$content = '<div class="card narrow login-card"><h1>' . e((string) $t['title']) . '</h1>'
    . '<form method="post" data-login-form><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . ($nextUrl !== null ? '<input type="hidden" name="next" value="' . e($nextUrl) . '">' : '')
    . '<label>' . e((string) $t['callsign']) . '<input type="text" name="callsign" required></label>'
    . '<label>' . e((string) $t['password']) . '<input type="password" name="password" required></label>'
    . '<label>' . e((string) $t['captcha_question']) . ' ' . $captchaA . ' + ' . $captchaB . ' ?'
    . '<input type="text" name="captcha" inputmode="numeric" autocomplete="off" required></label>'
    . '<button class="button">' . e((string) $t['login']) . '</button></form>'
    . '<p><a href="' . e(route_url('forgot_password')) . '">' . e((string) $t['forgot_password']) . '</a></p>'
    . '<p>' . e((string) $t['no_member']) . ' <a href="' . e(route_url('membership')) . '">' . e($membershipLabel) . '</a></p>'
    . '</div>'
    . '<script nonce="' . e(csp_nonce()) . '">(function(){var hash=window.location.hash;if(!/^[#][A-Za-z0-9][A-Za-z0-9_-]{0,79}$/.test(hash)){return;}var next=document.querySelector("[data-login-form] input[name=next]");if(!next||next.value.indexOf("#")!==-1){return;}next.value+=hash;})();</script>';

echo render_layout($content, (string) $t['title']);
