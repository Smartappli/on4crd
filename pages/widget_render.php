<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
$user = require_login();
$t = i18n_domain_translator('dashboard', current_locale());
$widget = strtolower(trim((string) ($_GET['widget'] ?? '')));
$availableWidgets = enabled_widget_catalog();
unset($availableWidgets['chatbot']);

if ($widget === '' || !isset($availableWidgets[$widget])) {
    http_response_code(404);
    echo '<p class="help">' . e($t('widget_unavailable')) . '</p>';
    return;
}

try {
    echo render_widget($widget, $user);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo '<p class="help">' . e($t('widget_unavailable')) . '</p>';
}
