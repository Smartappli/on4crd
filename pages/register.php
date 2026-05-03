<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['required' => 'Tous les champs sont obligatoires.', 'auth_unavailable' => 'Module d’authentification indisponible. Lancez composer install.', 'invalid_data' => 'Les informations fournies sont invalides.', 'already_exists' => 'Un compte existe déjà avec cet email ou cet indicatif.', 'too_many' => 'Trop de tentatives. Merci de réessayer plus tard.', 'ok_created' => 'Compte créé avec succès. Bienvenue dans l’espace membre !', 'title' => 'Créer un compte membre', 'callsign' => 'Indicatif', 'full_name' => 'Nom complet', 'email' => 'Email', 'password' => 'Mot de passe', 'submit' => 'Créer mon compte', 'already_registered' => 'Déjà inscrit ?', 'login' => 'Se connecter', 'layout_title' => 'Inscription'],
    'en' => ['required' => 'All fields are required.', 'auth_unavailable' => 'Authentication module unavailable. Run composer install.', 'invalid_data' => 'Provided information is invalid.', 'already_exists' => 'An account already exists with this email or callsign.', 'too_many' => 'Too many attempts. Please try again later.', 'ok_created' => 'Account created successfully. Welcome to the member area!', 'title' => 'Create a member account', 'callsign' => 'Callsign', 'full_name' => 'Full name', 'email' => 'Email', 'password' => 'Password', 'submit' => 'Create my account', 'already_registered' => 'Already registered?', 'login' => 'Log in', 'layout_title' => 'Register'],
    'de' => ['required' => 'Alle Felder sind erforderlich.', 'auth_unavailable' => 'Authentifizierungsmodul nicht verfügbar. Führen Sie composer install aus.', 'invalid_data' => 'Die angegebenen Informationen sind ungültig.', 'already_exists' => 'Ein Konto mit dieser E-Mail oder diesem Rufzeichen existiert bereits.', 'too_many' => 'Zu viele Versuche. Bitte später erneut versuchen.', 'ok_created' => 'Konto erfolgreich erstellt. Willkommen im Mitgliederbereich!', 'title' => 'Mitgliedskonto erstellen', 'callsign' => 'Rufzeichen', 'full_name' => 'Vollständiger Name', 'email' => 'E-Mail', 'password' => 'Passwort', 'submit' => 'Mein Konto erstellen', 'already_registered' => 'Bereits registriert?', 'login' => 'Anmelden', 'layout_title' => 'Registrierung'],
    'nl' => ['required' => 'Alle velden zijn verplicht.', 'auth_unavailable' => 'Authenticatiemodule niet beschikbaar. Voer composer install uit.', 'invalid_data' => 'De opgegeven gegevens zijn ongeldig.', 'already_exists' => 'Er bestaat al een account met dit e-mailadres of deze roepnaam.', 'too_many' => 'Te veel pogingen. Probeer het later opnieuw.', 'ok_created' => 'Account succesvol aangemaakt. Welkom in de ledenruimte!', 'title' => 'Ledenaccount aanmaken', 'callsign' => 'Roepnaam', 'full_name' => 'Volledige naam', 'email' => 'E-mail', 'password' => 'Wachtwoord', 'submit' => 'Mijn account aanmaken', 'already_registered' => 'Al geregistreerd?', 'login' => 'Inloggen', 'layout_title' => 'Registratie'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

if (current_user() !== null) {
    redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($callsign === '' || $fullName === '' || $email === '' || $password === '') {
            throw new RuntimeException($t('required'));
        }

        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException($t('auth_unavailable'));
        }

        try {
            $userId = $authClient->registerWithUniqueUsername($email, $password, $callsign);
        } catch (\Delight\Auth\InvalidEmailException|\Delight\Auth\InvalidPasswordException|\Delight\Auth\InvalidUsernameException $exception) {
            throw new RuntimeException($t('invalid_data'));
        } catch (\Delight\Auth\UserAlreadyExistsException|\Delight\Auth\DuplicateUsernameException $exception) {
            throw new RuntimeException($t('already_exists'));
        } catch (\Delight\Auth\TooManyRequestsException $exception) {
            throw new RuntimeException($t('too_many'));
        }

        db()->prepare(
            'INSERT INTO members (auth_user_id, callsign, full_name, email, password_hash, is_active)
             VALUES (?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                 callsign = VALUES(callsign),
                 full_name = VALUES(full_name),
                 email = VALUES(email),
                 password_hash = VALUES(password_hash),
                 is_active = 1'
        )->execute([(int) $userId, $callsign, $fullName, $email, password_hash($password, PASSWORD_DEFAULT)]);

        $authClient->loginWithUsername($callsign, $password);
        $_SESSION['member_id'] = (int) $authClient->getUserId();

        set_flash('success', $t('ok_created'));
        redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('register');
    }
}

$content = '<div class="card narrow login-card"><h1>' . e($t('title')) . '</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>' . e($t('callsign')) . '<input type="text" name="callsign" maxlength="32" required></label>'
    . '<label>' . e($t('full_name')) . '<input type="text" name="full_name" maxlength="190" required></label>'
    . '<label>' . e($t('email')) . '<input type="email" name="email" maxlength="190" required></label>'
    . '<label>' . e($t('password')) . '<input type="password" name="password" minlength="8" required></label>'
    . '<button class="button">' . e($t('submit')) . '</button></form>'
    . '<p>' . e($t('already_registered')) . ' <a href="' . e(route_url('login')) . '">' . e($t('login')) . '</a></p>'
    . '</div>';

echo render_layout($content, $t('layout_title'));
