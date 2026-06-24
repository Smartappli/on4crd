<?php
declare(strict_types=1);

$locale = current_locale();
$registerI18n = i18n_domain_locale('register', $locale);
$title = (string) ($registerI18n['registration_closed_title'] ?? $registerI18n['layout_title'] ?? $registerI18n['title'] ?? 'Registration closed');
$message = (string) ($registerI18n['registration_closed_message'] ?? $registerI18n['required'] ?? 'Public registration is closed.');
$loginLabel = (string) ($registerI18n['login'] ?? 'Log in');
$membershipLabel = (string) ($registerI18n['membership_link'] ?? 'Membership');

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
