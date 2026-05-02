<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
$user = require_login();
$widget = (string) ($_GET['widget'] ?? '');

try {
    echo render_widget($widget, $user);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo '<p class="help">Widget temporairement indisponible.</p>';
}
