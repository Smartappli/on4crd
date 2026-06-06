<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$t = i18n_domain_translator('change_password', $locale);
$redirectRoute = module_enabled('dashboard') ? 'dashboard' : 'home';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['password_confirm'] ?? '');

        if (trim($currentPassword) === '' || trim($newPassword) === '' || trim($confirmPassword) === '') {
            throw new RuntimeException($t('err_required'));
        }
        if ($newPassword !== $confirmPassword) {
            throw new RuntimeException($t('err_mismatch'));
        }
        if (hash_equals(trim($currentPassword), trim($newPassword))) {
            throw new RuntimeException($t('err_same_password'));
        }

        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException($t('err_auth_unavailable'));
        }

        $authClient->changePassword($currentPassword, $newPassword);

        $memberId = (int) ($user['id'] ?? 0);
        if ($memberId > 0 && table_exists('members')) {
            $updates = ['password_hash = ?'];
            $params = [password_hash($newPassword, PASSWORD_DEFAULT)];
            if (table_has_column('members', 'password_change_required')) {
                $updates[] = 'password_change_required = 0';
            }
            $params[] = $memberId;
            db()->prepare('UPDATE members SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute($params);
        }

        set_flash('success', $t('ok_updated'));
        redirect($redirectRoute);
    } catch (\Delight\Auth\InvalidPasswordException $exception) {
        set_flash('error', $t('err_invalid_password'));
        redirect('change_password');
    } catch (\Delight\Auth\NotLoggedInException $exception) {
        set_flash('error', $t('err_not_logged_in'));
        redirect('login');
    } catch (\Delight\Auth\TooManyRequestsException $exception) {
        set_flash('error', $t('err_too_many'));
        redirect('change_password');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('change_password');
    }
}

$content = '<div class="card narrow login-card"><h1>' . e($t('title')) . '</h1>'
    . '<p class="help">' . e($t('intro_optional')) . '</p>'
    . '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>' . e($t('current_password')) . '<input type="password" name="current_password" autocomplete="current-password" required></label>'
    . '<label>' . e($t('new_password')) . '<input type="password" name="password" minlength="8" autocomplete="new-password" required></label>'
    . '<label>' . e($t('confirm_password')) . '<input type="password" name="password_confirm" minlength="8" autocomplete="new-password" required></label>'
    . '<button class="button">' . e($t('submit')) . '</button></form>'
    . '</div>';

echo render_layout($content, $t('layout_title'));
