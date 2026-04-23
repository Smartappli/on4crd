<?php
declare(strict_types=1);

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
            throw new RuntimeException('Tous les champs sont obligatoires.');
        }

        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException('Module d’authentification indisponible. Lancez composer install.');
        }

        try {
            $userId = $authClient->registerWithUniqueUsername($email, $password, $callsign);
        } catch (\Delight\Auth\InvalidEmailException|\Delight\Auth\InvalidPasswordException|\Delight\Auth\InvalidUsernameException $exception) {
            throw new RuntimeException('Les informations fournies sont invalides.');
        } catch (\Delight\Auth\UserAlreadyExistsException|\Delight\Auth\DuplicateUsernameException $exception) {
            throw new RuntimeException('Un compte existe déjà avec cet email ou cet indicatif.');
        } catch (\Delight\Auth\TooManyRequestsException $exception) {
            throw new RuntimeException('Trop de tentatives. Merci de réessayer plus tard.');
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

        set_flash('success', 'Compte créé avec succès. Bienvenue dans l’espace membre !');
        redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('register');
    }
}

$content = '<div class="card narrow login-card"><h1>Créer un compte membre</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>Indicatif<input type="text" name="callsign" maxlength="32" required></label>'
    . '<label>Nom complet<input type="text" name="full_name" maxlength="190" required></label>'
    . '<label>Email<input type="email" name="email" maxlength="190" required></label>'
    . '<label>Mot de passe<input type="password" name="password" minlength="8" required></label>'
    . '<button class="button">Créer mon compte</button></form>'
    . '<p>Déjà inscrit ? <a href="' . e(route_url('login')) . '">Se connecter</a></p>'
    . '</div>';

echo render_layout($content, 'Inscription');
