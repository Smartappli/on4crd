<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$i18nRoot = $root . '/app/i18n';

$defaultLocales = ['ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'];
$targetLocales = $defaultLocales;
$strict = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--strict') {
        $strict = true;
        continue;
    }
    if (str_starts_with($arg, '--locales=')) {
        $raw = trim((string) substr($arg, strlen('--locales=')));
        if ($raw !== '') {
            $parts = array_map('trim', explode(',', $raw));
            $parts = array_filter($parts, static fn (string $value): bool => $value !== '');
            if ($parts !== []) {
                $targetLocales = array_values(array_unique($parts));
            }
        }
    }
}

$moduleDirs = glob($i18nRoot . '/*', GLOB_ONLYDIR) ?: [];
$modules = [];
foreach ($moduleDirs as $dir) {
    if (is_file($dir . '/en.php')) {
        $modules[] = basename($dir);
    }
}
sort($modules);

$issues = [];
foreach ($modules as $module) {
    $base = $i18nRoot . '/' . $module;
    $en = require $base . '/en.php'; // NOSONAR - utility script validates repeatable locale arrays.
    if (!is_array($en)) {
        $issues[] = [$module, 'en', 'invalid_array'];
        continue;
    }

    foreach ($targetLocales as $locale) {
        $file = $base . '/' . $locale . '.php';
        if (!is_file($file)) {
            $issues[] = [$module, $locale, 'missing_file'];
            continue;
        }

        $dict = require $file; // NOSONAR - utility script validates repeatable locale arrays.
        if (!is_array($dict)) {
            $issues[] = [$module, $locale, 'invalid_array'];
            continue;
        }
        if ($dict === $en) {
            $issues[] = [$module, $locale, 'identical_to_en'];
            continue;
        }

        $sameCount = 0;
        $checked = 0;
        foreach ($en as $key => $enValue) {
            if (!array_key_exists($key, $dict) || !is_string($dict[$key]) || !is_string($enValue)) {
                continue;
            }
            $checked++;
            if (trim($dict[$key]) === trim($enValue)) {
                $sameCount++;
            }
        }

        if ($checked > 0 && ($sameCount / $checked) >= 0.7) {
            $issues[] = [$module, $locale, 'mostly_english:' . $sameCount . '/' . $checked];
        }
    }
}

if ($issues === []) {
    echo "OK: no obvious non-native dictionaries detected for audited locales.\n";
    exit(0);
}

echo "Found potential non-native dictionaries:\n";
foreach ($issues as [$module, $locale, $reason]) {
    echo "- {$module}/{$locale}: {$reason}\n";
}

if ($strict) {
    exit(1);
}

echo "\nReport-only mode: exiting with success. Use --strict to return a failing exit code.\n";
exit(0);
