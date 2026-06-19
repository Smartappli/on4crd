<?php
declare(strict_types=1);

$locale = current_locale();
$isFrench = $locale === 'fr';
$title = $isFrench ? 'Inscription membre' : 'Member registration';
$message = $isFrench
    ? 'L inscription publique est fermee. La creation des comptes membres est effectuee par l administration apres validation.'
    : 'Public registration is closed. Member accounts are created by the administration after validation.';
$loginLabel = $isFrench ? 'Connexion' : 'Sign in';
$membershipLabel = $isFrench ? 'Devenir membre' : 'Become a member';

if (current_user() !== null) {
    redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    set_flash('error', $message);
    redirect('login');
}

set_page_meta([
    'title' => $title,
    'description' => $message,
    'robots' => 'noindex,nofollow',
]);

$content = '<div class="card narrow login-card register-card"><h1>' . e($title) . '</h1>'
    . '<p>' . e($message) . '</p>'
    . '<p class="actions">'
    . '<a class="button" href="' . e(route_url('login')) . '">' . e($loginLabel) . '</a>'
    . '<a class="button secondary" href="' . e(route_url('membership')) . '">' . e($membershipLabel) . '</a>'
    . '</p>'
    . '</div>';

echo render_layout($content, $title);
