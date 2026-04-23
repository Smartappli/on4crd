<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email === '') {
            throw new RuntimeException('Veuillez renseigner votre email.');
        }

        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException('Module d’authentification indisponible. Lancez composer install.');
        }

        $authClient->forgotPassword($email, static function (string $selector, string $token): void {
            // Intégration e-mail à brancher ici (SMTP/API) pour transmettre le lien de reset.
            // Le callback est obligatoire pour récupérer selector/token.
            $_SESSION['password_reset_pending'] = hash('sha256', $selector . ':' . $token);
        });

        set_flash('success', 'Si un compte existe pour cet email, un lien de réinitialisation a été généré.');
        redirect('forgot_password');
    } catch (\Delight\Auth\InvalidEmailException $exception) {
        set_flash('error', 'Email invalide.');
        redirect('forgot_password');
    } catch (\Delight\Auth\EmailNotVerifiedException $exception) {
        set_flash('error', 'Compte non vérifié.');
        redirect('forgot_password');
    } catch (\Delight\Auth\ResetDisabledException $exception) {
        set_flash('error', 'La réinitialisation est désactivée pour ce compte.');
        redirect('forgot_password');
    } catch (\Delight\Auth\TooManyRequestsException $exception) {
        set_flash('error', 'Trop de demandes. Réessayez plus tard.');
        redirect('forgot_password');
    }
}

$content = '<div class="card narrow login-card"><h1>Mot de passe oublié</h1>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>Email<input type="email" name="email" required></label>'
    . '<button class="button">Envoyer le lien</button></form>'
    . '<p><a href="' . e(route_url('login')) . '">Retour à la connexion</a></p>';

$content .= '</div>';

echo render_layout($content, 'Mot de passe oublié');
