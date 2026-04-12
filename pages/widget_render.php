<?php
declare(strict_types=1);

$user = require_login();
$widget = (string) ($_GET['widget'] ?? '');
echo render_widget($widget, $user);
