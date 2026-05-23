<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$i18nRoot = $root . '/app/i18n';

$strict = in_array('--strict', $argv, true);
$json = in_array('--json', $argv, true);
$knownLocales = ['fr', 'en', 'de', 'nl', 'es', 'it', 'pt', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'];

/**
 * Heuristics for common UTF-8 mojibake artifacts produced by double/mis-decoding.
 * Kept intentionally simple to avoid expensive false-negative logic.
 */
$patterns = [
    '/Ã./u',
    '/â[\x80-\xBF]/u',
    '/�/u',
];

$issues = [];
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($i18nRoot));
foreach ($iter as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $basename = $file->getBasename('.php');
    if (!in_array($basename, $knownLocales, true)) {
        continue;
    }

    $path = $file->getPathname();
    $rel = substr($path, strlen($root) + 1);
    $content = file_get_contents($path);
    if ($content === false || trim($content) === '') {
        continue;
    }

    $lines = explode("\n", $content);
    foreach ($lines as $idx => $line) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line) === 1) {
                $issues[] = [
                    'file' => $rel,
                    'line' => $idx + 1,
                    'snippet' => trim($line),
                    'pattern' => $pattern,
                ];
                break;
            }
        }
    }
}

if ($json) {
    echo json_encode([
        'total' => count($issues),
        'issues' => $issues,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($strict && $issues !== [] ? 1 : 0);
}

if ($issues === []) {
    echo "OK: no mojibake-like artifacts detected in app/i18n locale files.\n";
    exit(0);
}

echo "Potential encoding artifacts detected in app/i18n:\n";
foreach ($issues as $row) {
    echo '- ' . $row['file'] . ':' . $row['line'] . ' => ' . $row['snippet'] . PHP_EOL;
}

if ($strict) {
    exit(1);
}

echo "\nReport-only mode: exiting with success. Use --strict to fail on findings.\n";
exit(0);
