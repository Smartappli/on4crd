<?php
declare(strict_types=1);

$title = t_page('sponsoring', 'title');
$body = t_page('sponsoring', 'body');

echo render_layout('<div class="card"><h1>' . e($title) . '</h1><p>' . e($body) . '</p></div>', $title);
