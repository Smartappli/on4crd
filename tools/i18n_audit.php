<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$pagesDir = $root . '/pages';
$publicOnly = in_array('--public-only', $argv, true);
$jsonOutput = in_array('--json', $argv, true);
$failOnFind = in_array('--fail-on-find', $argv, true);
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pagesDir));
$patterns = [
    '/[À-ÖØ-öø-ÿ]/u',
    '/\b(?:bonjour|actualité|actualités|gérer|supprimer|retour|connexion|inscription|mot de passe|mentions légales|règlement|enchères|boutique|articles|wiki|galerie|annuaire)\b/iu',
];

$results = [];
$perFile = [];
foreach ($rii as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $rel = substr($path, strlen($root) + 1);
    if ($publicOnly) {
        $base = basename($rel);
        if (str_starts_with($base, 'admin_') || $base === 'admin.php') {
            continue;
        }
    }
    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    // focus on quoted literals outside obvious translation arrays is hard;
    // simple heuristic: report lines with French-looking text literals.
    $lines = explode("\n", $content);
    foreach ($lines as $idx => $line) {
        if (str_contains($line, '$i18n') || str_contains($line, 't_page(')) {
            continue;
        }
        if (!preg_match('/[\'\"]([^\'\"]{3,})[\'\"]/', $line)) {
            continue;
        }
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line)) {
                $results[] = sprintf('%s:%d:%s', $rel, $idx + 1, trim($line));
                $perFile[$rel] = ($perFile[$rel] ?? 0) + 1;
                break;
            }
        }
    }
}

sort($results);

if ($jsonOutput) {
    echo json_encode([
        'mode' => $publicOnly ? 'public-only' : 'all-pages',
        'total' => count($results),
        'per_file' => $perFile,
        'matches' => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit($failOnFind && count($results) > 0 ? 1 : 0);
}

echo 'MODE=' . ($publicOnly ? 'public-only' : 'all-pages') . PHP_EOL . PHP_EOL;
foreach ($results as $row) {
    echo $row, PHP_EOL;
}

echo PHP_EOL . "---- SUMMARY BY FILE ----" . PHP_EOL;
arsort($perFile);
foreach ($perFile as $file => $count) {
    echo $file . ':' . $count . PHP_EOL;
}

echo PHP_EOL . 'TOTAL_POTENTIAL_FRENCH_LINES=' . count($results) . PHP_EOL;
if ($failOnFind && count($results) > 0) {
    exit(1);
}
