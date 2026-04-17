<?php
declare(strict_types=1);

$bootstrapConfigFile = __DIR__ . '/../config/config.php';
if (!is_file($bootstrapConfigFile)) {
    throw new RuntimeException('Missing config/config.php. Copy config.sample.php first.');
}

$bootstrapConfig = require $bootstrapConfigFile;
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) === '443')
    || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
);

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

session_name((string) ($bootstrapConfig['app']['session_name'] ?? 'on4crd_session'));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require __DIR__ . '/functions.php';
require __DIR__ . '/cache.php';
require __DIR__ . '/observability.php';
require __DIR__ . '/maintenance.php';
require __DIR__ . '/newsletter.php';
require __DIR__ . '/seo.php';

setup_observability((array) ($bootstrapConfig['observability'] ?? []));
apply_security_headers();
ensure_directories();
apply_runtime_schema_updates();
