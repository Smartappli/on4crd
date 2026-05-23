<?php
declare(strict_types=1);

$_base = [
    'fr' => require __DIR__ . '/admin_articles/fr.php',
    'en' => require __DIR__ . '/admin_articles/en.php',
    'de' => require __DIR__ . '/admin_articles/de.php',
    'nl' => require __DIR__ . '/admin_articles/nl.php',
    'es' => require __DIR__ . '/admin_articles/es.php',
    'it' => require __DIR__ . '/admin_articles/it.php',
    'pt' => require __DIR__ . '/admin_articles/pt.php',
    'ar' => require __DIR__ . '/admin_articles/ar.php',
    'hi' => require __DIR__ . '/admin_articles/hi.php',
    'ja' => require __DIR__ . '/admin_articles/ja.php',
    'zh' => require __DIR__ . '/admin_articles/zh.php',
    'bn' => require __DIR__ . '/admin_articles/bn.php',
    'ru' => require __DIR__ . '/admin_articles/ru.php',
    'id' => require __DIR__ . '/admin_articles/id.php',
];

$extra = require __DIR__ . '/admin_articles_extra.php';
foreach ($extra as $locale => $entries) {
    if (!isset($_base[$locale]) || !is_array($_base[$locale])) {
        $_base[$locale] = [];
    }
    $_base[$locale] = array_replace($_base[$locale], $entries);
}

return $_base;
