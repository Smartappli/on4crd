<?php
declare(strict_types=1);

$locales = ['fr', 'en', 'de', 'nl', 'es', 'it', 'pt', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'];
$routes = [
    'home' => '/',
    'news' => '/index.php?route=news',
    'articles' => '/index.php?route=articles',
    'wiki' => '/index.php?route=wiki',
    'tools' => '/index.php?route=tools',
    'chatbot' => '/index.php?route=chatbot',
    'classifieds' => '/index.php?route=classifieds',
    'qsl' => '/index.php?route=qsl',
];

$baseUrl = rtrim((string) getenv('ON4CRD_BASE_URL'), '/');
if ($baseUrl === '') {
    $baseUrl = 'http://localhost:8000';
}

$manifest = [];
foreach ($locales as $locale) {
    foreach ($routes as $routeKey => $path) {
        $sep = str_contains($path, '?') ? '&' : '?';
        $manifest[] = [
            'locale' => $locale,
            'route' => $routeKey,
            'url' => $baseUrl . $path . $sep . 'lang=' . $locale,
        ];
    }
}

echo json_encode([
    'base_url' => $baseUrl,
    'total' => count($manifest),
    'items' => $manifest,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

