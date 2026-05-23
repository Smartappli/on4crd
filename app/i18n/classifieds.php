<?php
declare(strict_types=1);

$_base = [
    'fr' => require __DIR__ . '/classifieds/fr.php',
    'en' => require __DIR__ . '/classifieds/en.php',
    'de' => require __DIR__ . '/classifieds/de.php',
    'nl' => require __DIR__ . '/classifieds/nl.php',
    'it' => require __DIR__ . '/classifieds/it.php',
    'es' => require __DIR__ . '/classifieds/es.php',
    'pt' => require __DIR__ . '/classifieds/pt.php',
    'ar' => require __DIR__ . '/classifieds/ar.php',
    'hi' => require __DIR__ . '/classifieds/hi.php',
    'ja' => require __DIR__ . '/classifieds/ja.php',
    'zh' => require __DIR__ . '/classifieds/zh.php',
    'bn' => require __DIR__ . '/classifieds/bn.php',
    'ru' => require __DIR__ . '/classifieds/ru.php',
    'id' => require __DIR__ . '/classifieds/id.php',
];

$extra = require __DIR__ . '/classifieds_extra.php';
foreach ($extra as $locale => $entries) {
    if (!isset($_base[$locale]) || !is_array($_base[$locale])) {
        $_base[$locale] = [];
    }
    $_base[$locale] = array_replace($_base[$locale], $entries);
}

return $_base;
