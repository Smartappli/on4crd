<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$dir = $root . '/app/i18n/chatbot';
$expectedLocales = ['en', 'fr', 'de', 'nl', 'es', 'it', 'pt', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'];
$requiredKeys = array_keys(require $dir . '/en.php'); // NOSONAR - utility script validates repeatable locale arrays.

$errors = [];
$warnings = [];
$suspiciousPattern = '/(?:\?{2,}|Ã|Â|ðŸ|�)/u';

foreach ($expectedLocales as $locale) {
    $path = $dir . '/' . $locale . '.php';
    if (!is_file($path)) {
        $errors[] = $locale . ': missing file';
        continue;
    }

    $dict = require $path; // NOSONAR - utility script validates repeatable locale arrays.
    if (!is_array($dict)) {
        $errors[] = $locale . ': invalid dictionary';
        continue;
    }

    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $dict)) {
            $errors[] = $locale . ': missing key "' . $key . '"';
            continue;
        }
        if (!is_string($dict[$key]) || trim($dict[$key]) === '') {
            $errors[] = $locale . ': empty key "' . $key . '"';
            continue;
        }
        if (preg_match($suspiciousPattern, $dict[$key]) === 1) {
            $warnings[] = $locale . ': suspicious encoding in key "' . $key . '"';
        }
    }
}

if ($errors !== []) {
    echo "Chatbot i18n quality check failed:\n";
    foreach ($errors as $line) {
        echo '- ' . $line . "\n";
    }
    exit(1);
}

if ($warnings !== []) {
    echo "Chatbot i18n quality warnings:\n";
    foreach ($warnings as $line) {
        echo '- ' . $line . "\n";
    }
    echo "\n";
}

echo "OK: chatbot i18n files are complete and pass baseline quality checks.\n";
exit(0);
