<?php
declare(strict_types=1);

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
            throw new RuntimeException('Identifiants requis.');
        }
        if (!hash_equals($captchaExpected, $captcha)) {
            throw new RuntimeException('Captcha invalide.');
        }
        unset($_SESSION['login_captcha']);
        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException('Module d’authentification indisponible. Lancez composer install.');
        }

        try {
            $authClient->loginWithUsername($callsign, $password);
        } catch (\Delight\Auth\UnknownUsernameException|\Delight\Auth\InvalidPasswordException $exception) {
            throw new RuntimeException('Indicatif ou mot de passe invalide.');
        } catch (\Delight\Auth\TooManyRequestsException $exception) {
            throw new RuntimeException('Trop de tentatives de connexion. Réessayez dans quelques minutes.');
        } catch (\Delight\Auth\EmailNotVerifiedException $exception) {
            throw new RuntimeException('Votre compte n’est pas encore vérifié.');
        }

        $_SESSION['member_id'] = (int) $authClient->getUserId();
        set_flash('success', 'Connexion réussie.');
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

$content = '<div class="card narrow login-card"><h1>Connexion</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>Indicatif<input type="text" name="callsign" required></label>'
    . '<label>Mot de passe<input type="password" name="password" required></label>'
    . '<label>Captcha : combien font ' . $captchaA . ' + ' . $captchaB . ' ?'
    . '<input type="text" name="captcha" inputmode="numeric" autocomplete="off" required></label>'
    . '<button class="button">Se connecter</button></form>'
    . '<p><a href="' . e(route_url('forgot_password')) . '">Mot de passe oublié ?</a></p>'
    . '<p>Pas encore membre ? <a href="' . e(route_url('register')) . '">Créer un compte</a></p>'
    . '</div>';

echo render_layout($content, 'Connexion');
