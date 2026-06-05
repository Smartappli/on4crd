<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$i18nRoot = $root . '/app/i18n';

$strict = in_array('--strict', $argv, true);
$json = in_array('--json', $argv, true);

/**
 * Heuristics for UTF-8 mojibake produced by CP1252/Latin-1 double decoding.
 * The byte class maps CP1252-rendered continuation bytes, including printable
 * replacements for the 0x80-0x9F range.
 */
$byte = '[\x{0080}-\x{00BF}\x{0152}\x{0153}\x{0160}\x{0161}\x{0178}\x{017D}\x{017E}\x{0192}\x{02C6}\x{02DC}\x{2013}\x{2014}\x{2018}\x{2019}\x{201A}\x{201C}\x{201D}\x{201E}\x{2020}\x{2021}\x{2022}\x{2026}\x{2030}\x{2039}\x{203A}\x{20AC}\x{2122}]';
$patterns = [
    '/[\x{00E0}-\x{00EF}]' . $byte . $byte . '/u',
    '/[\x{00C2}-\x{00DF}]' . $byte . '/u',
    '/\x{00EF}\x{00BF}\x{00BD}/u',
    '/\x{FFFD}/u',
];

$issues = [];
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($i18nRoot));
foreach ($iter as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
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
    echo "OK: no mojibake-like artifacts detected in app/i18n PHP files.\n";
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