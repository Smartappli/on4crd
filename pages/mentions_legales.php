<?php
declare(strict_types=1);

$title = t_page('mentions_legales', 'title');
$body = t_page('mentions_legales', 'body');

set_page_meta([
    'title' => $title,
    'description' => mb_substr(trim((string) $body), 0, 160),
    'schema_type' => 'WebPage',
]);

echo render_layout('<div class="card"><h1>' . e($title) . '</h1><p>' . e($body) . '</p></div>', $title);
