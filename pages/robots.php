<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$base = rtrim((string) config('app.base_url', ''), '/');
if ($base === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $base = $scheme . '://' . $host;
}

$lines = [
    'User-agent: *',
    'Allow: /',
    'Disallow: /index.php?route=admin',
    'Disallow: /index.php?route=dashboard',
    'Disallow: /index.php?route=shop_cart',
    'Disallow: /index.php?route=shop_checkout',
    'Disallow: /index.php?route=qsl',
    'Disallow: /storage/cache/',
    'Disallow: /storage/uploads/',
    '',
    'Sitemap: ' . $base . '/index.php?route=sitemap.xml',
    'Host: ' . preg_replace('#^https?://#', '', $base),
];

echo implode("\n", $lines) . "\n";
