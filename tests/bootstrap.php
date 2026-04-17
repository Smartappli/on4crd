<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/functions.php';
require_once __DIR__ . '/../app/cache.php';

require_once __DIR__ . '/../app/maintenance.php';
require_once __DIR__ . '/../app/newsletter.php';
require_once __DIR__ . '/../app/seo.php';
