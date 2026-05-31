<?php
declare(strict_types=1);

$root = dirname(__DIR__) . '/app/i18n';
$expected = ['fr', 'en', 'de', 'nl', 'it', 'es', 'pt', 'bg', 'hr', 'cs', 'da', 'et', 'fi', 'el', 'hu', 'ga', 'lv', 'lt', 'mt', 'pl', 'ro', 'sk', 'sl', 'sv', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'];

$moduleIndexFiles = glob($root . '/*.php') ?: [];
$missingIssues = [];
$extraIssues = [];

foreach ($moduleIndexFiles as $indexFile) {
    $module = basename($indexFile, '.php');
    $moduleDir = $root . '/' . $module;
    if (!is_dir($moduleDir)) {
        continue;
    }

    $present = [];
    foreach (glob($moduleDir . '/*.php') ?: [] as $localeFile) {
        $present[] = basename($localeFile, '.php');
    }
    sort($present);

    $missing = array_values(array_diff($expected, $present));
    if ($missing !== []) {
        $missingIssues[] = [$module, $missing];
    }

    $extra = array_values(array_diff($present, $expected));
    if ($extra !== []) {
        $extraIssues[] = [$module, $extra];
    }
}

if ($missingIssues === [] && $extraIssues === []) {
    echo "OK: all i18n modules with locale directories match the expected locales.\n";
    exit(0);
}

if ($missingIssues !== []) {
    echo "Missing locales detected:\n";
    foreach ($missingIssues as [$module, $missing]) {
        echo '- ' . $module . ': ' . implode(',', $missing) . "\n";
    }
}

if ($extraIssues !== []) {
    if ($missingIssues !== []) {
        echo "\n";
    }
    echo "Unexpected locales detected:\n";
    foreach ($extraIssues as [$module, $extra]) {
        echo '- ' . $module . ': ' . implode(',', $extra) . "\n";
    }
}

exit(1);
