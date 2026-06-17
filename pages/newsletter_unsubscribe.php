<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_translator('newsletter_unsubscribe', $locale);

set_page_meta([
    'title' => $t('title'),
    'description' => $t('desc'),
    'robots' => 'noindex,nofollow',
]);
$token = (string) ($_GET['token'] ?? '');
$ok = false;
try {
    $ok = newsletter_unsubscribe_by_token($token);
} catch (Throwable $throwable) {
    if (function_exists('log_structured_event')) {
        log_structured_event('newsletter_unsubscribe_failed', ['message' => $throwable->getMessage()]);
    }
}

$message = $ok
    ? '<div class="card"><h1>' . e($t('heading')) . '</h1><p>' . e($t('ok')) . '</p></div>'
    : '<div class="card"><h1>' . e($t('heading')) . '</h1><p>' . e($t('invalid')) . '</p></div>';

echo render_layout($message, $t('title'));
