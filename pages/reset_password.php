<?php
declare(strict_types=1);

$selector = trim((string) ($_GET['selector'] ?? $_POST['selector'] ?? ''));
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $newPassword = (string) ($_POST['password'] ?? '');
        if ($selector === '' || $token === '' || $newPassword === '') {
            throw new RuntimeException('Informations de réinitialisation incomplètes.');
        }

        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException('Module d’authentification indisponible. Lancez composer install.');
        }

        $authClient->resetPassword($selector, $token, $newPassword);
        unset($_SESSION['password_reset_link']);
        set_flash('success', 'Mot de passe mis à jour. Vous pouvez vous connecter.');
        redirect('login');
    } catch (\Delight\Auth\InvalidSelectorTokenPairException|\Delight\Auth\TokenExpiredException $exception) {
        set_flash('error', 'Le lien est invalide ou expiré.');
        redirect('forgot_password');
    } catch (\Delight\Auth\ResetDisabledException $exception) {
        set_flash('error', 'La réinitialisation est désactivée pour ce compte.');
        redirect('forgot_password');
    } catch (\Delight\Auth\InvalidPasswordException $exception) {
        set_flash('error', 'Mot de passe invalide (minimum 8 caractères recommandés).');
        redirect_url(route_url('reset_password', ['selector' => $selector, 'token' => $token]));
    } catch (\Delight\Auth\TooManyRequestsException $exception) {
        set_flash('error', 'Trop de tentatives. Réessayez plus tard.');
        redirect('forgot_password');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('forgot_password');
    }
}

$content = '<div class="card narrow login-card"><h1>Réinitialiser le mot de passe</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<input type="hidden" name="selector" value="' . e($selector) . '">'
    . '<input type="hidden" name="token" value="' . e($token) . '">'
    . '<label>Nouveau mot de passe<input type="password" name="password" minlength="8" required></label>'
    . '<button class="button">Mettre à jour</button></form>'
    . '<p><a href="' . e(route_url('login')) . '">Retour à la connexion</a></p>'
    . '</div>';

echo render_layout($content, 'Réinitialisation du mot de passe');
