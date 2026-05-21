<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/register.php';
$i18n = i18n_expand_supported_locales($i18n);
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
