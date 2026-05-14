<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['required' => 'Identifiants requis.', 'captcha_invalid' => 'Captcha invalide.', 'auth_unavailable' => 'Module d’authentification indisponible. Lancez composer install.', 'invalid_credentials' => 'Indicatif ou mot de passe invalide.', 'too_many' => 'Trop de tentatives de connexion. Réessayez dans quelques minutes.', 'not_verified' => 'Votre compte n’est pas encore vérifié.', 'login_success' => 'Connexion réussie.', 'title' => 'Connexion', 'callsign' => 'Indicatif', 'password' => 'Mot de passe', 'captcha_question' => 'Captcha : combien font', 'login' => 'Se connecter', 'forgot_password' => 'Mot de passe oublié ?', 'no_member' => 'Pas encore membre ?', 'create_account' => 'Créer un compte'],
    'en' => ['required' => 'Credentials required.', 'captcha_invalid' => 'Invalid captcha.', 'auth_unavailable' => 'Authentication module unavailable. Run composer install.', 'invalid_credentials' => 'Invalid callsign or password.', 'too_many' => 'Too many login attempts. Try again in a few minutes.', 'not_verified' => 'Your account is not verified yet.', 'login_success' => 'Login successful.', 'title' => 'Login', 'callsign' => 'Callsign', 'password' => 'Password', 'captcha_question' => 'Captcha: how much is', 'login' => 'Log in', 'forgot_password' => 'Forgot password?', 'no_member' => 'Not a member yet?', 'create_account' => 'Create an account'],
    'de' => ['required' => 'Anmeldedaten erforderlich.', 'captcha_invalid' => 'Ungültiges Captcha.', 'auth_unavailable' => 'Authentifizierungsmodul nicht verfügbar. Führen Sie composer install aus.', 'invalid_credentials' => 'Ungültiges Rufzeichen oder Passwort.', 'too_many' => 'Zu viele Anmeldeversuche. Versuchen Sie es in einigen Minuten erneut.', 'not_verified' => 'Ihr Konto ist noch nicht verifiziert.', 'login_success' => 'Anmeldung erfolgreich.', 'title' => 'Anmeldung', 'callsign' => 'Rufzeichen', 'password' => 'Passwort', 'captcha_question' => 'Captcha: wie viel ist', 'login' => 'Anmelden', 'forgot_password' => 'Passwort vergessen?', 'no_member' => 'Noch kein Mitglied?', 'create_account' => 'Konto erstellen'],
    'es' => ['required' => 'Credenciales obligatorias.', 'captcha_invalid' => 'Captcha no válido.', 'auth_unavailable' => 'Módulo de autenticación no disponible. Ejecute composer install.', 'invalid_credentials' => 'Indicativo o contraseña no válidos.', 'too_many' => 'Demasiados intentos de acceso. Inténtelo de nuevo en unos minutos.', 'not_verified' => 'Su cuenta aún no está verificada.', 'login_success' => 'Inicio de sesión correcto.', 'title' => 'Iniciar sesión', 'callsign' => 'Indicativo', 'password' => 'Contraseña', 'captcha_question' => 'Captcha: cuánto es', 'login' => 'Entrar', 'forgot_password' => '¿Olvidó su contraseña?', 'no_member' => '¿Aún no es miembro?', 'create_account' => 'Crear una cuenta'],
    'it' => ['required' => 'Credenziali obbligatorie.', 'captcha_invalid' => 'Captcha non valido.', 'auth_unavailable' => 'Modulo di autenticazione non disponibile. Esegui composer install.', 'invalid_credentials' => 'Nominativo o password non validi.', 'too_many' => 'Troppi tentativi di accesso. Riprova tra qualche minuto.', 'not_verified' => 'Il tuo account non è ancora verificato.', 'login_success' => 'Accesso effettuato.', 'title' => 'Accedi', 'callsign' => 'Nominativo', 'password' => 'Password', 'captcha_question' => 'Captcha: quanto fa', 'login' => 'Accedi', 'forgot_password' => 'Password dimenticata?', 'no_member' => 'Non sei ancora membro?', 'create_account' => 'Crea un account'],
    'pt' => ['required' => 'Credenciais obrigatórias.', 'captcha_invalid' => 'Captcha inválido.', 'auth_unavailable' => 'Módulo de autenticação indisponível. Execute composer install.', 'invalid_credentials' => 'Indicativo ou palavra-passe inválidos.', 'too_many' => 'Demasiadas tentativas de login. Tente novamente em alguns minutos.', 'not_verified' => 'A sua conta ainda não está verificada.', 'login_success' => 'Sessão iniciada com sucesso.', 'title' => 'Iniciar sessão', 'callsign' => 'Indicativo', 'password' => 'Palavra-passe', 'captcha_question' => 'Captcha: quanto é', 'login' => 'Entrar', 'forgot_password' => 'Esqueceu-se da palavra-passe?', 'no_member' => 'Ainda não é membro?', 'create_account' => 'Criar conta'],
    'nl' => ['required' => 'Inloggegevens vereist.', 'captcha_invalid' => 'Ongeldige captcha.', 'auth_unavailable' => 'Authenticatiemodule niet beschikbaar. Voer composer install uit.', 'invalid_credentials' => 'Ongeldig roepnaam of wachtwoord.', 'too_many' => 'Te veel inlogpogingen. Probeer het over enkele minuten opnieuw.', 'not_verified' => 'Je account is nog niet geverifieerd.', 'login_success' => 'Succesvol ingelogd.', 'title' => 'Inloggen', 'callsign' => 'Roepnaam', 'password' => 'Wachtwoord', 'captcha_question' => 'Captcha: hoeveel is', 'login' => 'Inloggen', 'forgot_password' => 'Wachtwoord vergeten?', 'no_member' => 'Nog geen lid?', 'create_account' => 'Account aanmaken'],
];
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
