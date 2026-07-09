<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/login.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = $i18n[$locale] ?? $i18n['fr'];
$nextUrl = safe_login_next_url((string) ($_POST['next'] ?? $_GET['next'] ?? ''));
$defaultLoginRedirectUrl = route_url(module_enabled('dashboard') ? 'dashboard' : 'home');
$membershipLabel = (string) ($t['membership_link'] ?? $t['create_account']);

if (current_user() !== null) {
    redirect_url($nextUrl ?? $defaultLoginRedirectUrl);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shouldRecordFailure = false;
    try {
        verify_csrf();
        enforce_login_throttle();
        $shouldRecordFailure = true;
        $identifier = trim((string) ($_POST['callsign'] ?? ''));
        $callsign = strtoupper($identifier);
        $password = (string) ($_POST['password'] ?? '');
        $captcha = trim((string) ($_POST['captcha'] ?? ''));

        if ($identifier === '' || $password === '' || $captcha === '') {
            throw new RuntimeException((string) $t['required']);
        }
        if (!login_captcha_verify($captcha)) {
            throw new RuntimeException((string) $t['captcha_invalid']);
        }
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

        login_captcha_clear();
        clear_login_failures();
        $_SESSION['member_id'] = (int) ($memberRow['id'] ?? 0);
        set_flash('success', (string) $t['login_success']);
        redirect_url($nextUrl ?? $defaultLoginRedirectUrl);
    } catch (Throwable $throwable) {
        login_captcha_clear();
        if ($shouldRecordFailure) {
            record_login_failure();
        }
        set_flash('error', $throwable->getMessage());
        redirect_url(route_url('login', $nextUrl !== null ? ['next' => $nextUrl] : []));
    }
}

$captchaChallenge = login_captcha_challenge();
$captchaQuestion = (string) $captchaChallenge['question'];

$content = '<div class="card narrow login-card"><h1>' . e((string) $t['title']) . '</h1>'
    . '<form method="post" data-login-form><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . ($nextUrl !== null ? '<input type="hidden" name="next" value="' . e($nextUrl) . '">' : '')
    . '<label>' . e((string) $t['callsign']) . '<input type="text" name="callsign" required></label>'
    . '<label>' . e((string) $t['password']) . '<input type="password" name="password" required></label>'
    . '<label class="login-captcha-field"><span class="login-captcha-label">' . e((string) $t['captcha_question']) . '</span>'
    . '<span class="login-captcha-challenge">' . e($captchaQuestion) . ' = ?</span>'
    . '<input type="text" name="captcha" inputmode="numeric" pattern="[0-9]*" autocomplete="off" required></label>'
    . '<button class="button">' . e((string) $t['login']) . '</button></form>'
    . '<p><a href="' . e(route_url('forgot_password')) . '">' . e((string) $t['forgot_password']) . '</a></p>'
    . '<p>' . e((string) $t['no_member']) . ' <a href="' . e(route_url('membership')) . '">' . e($membershipLabel) . '</a></p>'
    . '</div>'
    . '<script nonce="' . e(csp_nonce()) . '">(function(){var hash=window.location.hash;if(!/^[#][A-Za-z0-9][A-Za-z0-9_-]{0,79}$/.test(hash)){return;}var next=document.querySelector("[data-login-form] input[name=next]");if(!next||next.value.indexOf("#")!==-1){return;}next.value+=hash;})();</script>';

echo render_layout($content, (string) $t['title']);
