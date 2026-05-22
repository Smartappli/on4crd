<?php
declare(strict_types=1);

newsletter_ensure_tables();
$locale = current_locale();
$t = i18n_domain_translator('newsletter_unsubscribe', $locale);

set_page_meta([
    'title' => $t('title'),
    'description' => $t('desc'),
    'robots' => 'noindex,nofollow',
]);
$token = (string) ($_GET['token'] ?? '');
$ok = newsletter_unsubscribe_by_token($token);

$message = $ok
    ? '<div class="card"><h1>' . e($t('heading')) . '</h1><p>' . e($t('ok')) . '</p></div>'
    : '<div class="card"><h1>' . e($t('heading')) . '</h1><p>' . e($t('invalid')) . '</p></div>';

echo render_layout($message, $t('title'));
