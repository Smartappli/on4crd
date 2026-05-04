<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$jsonMode = in_array('--json', $argv ?? [], true);
$pathArgs = array_values(array_filter($argv ?? [], static fn(string $arg): bool => str_starts_with($arg, '--path=')));
$targets = $pathArgs === []
    ? [$root . '/app', $root . '/pages']
    : array_merge([$root . '/app'], array_map(static fn(string $arg): string => $root . '/' . ltrim(substr($arg, 7), '/'), $pathArgs));

$definitions = [];
$calls = [];

$iterators = [];
foreach ($targets as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $iterators[] = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
}

$builtin = array_fill_keys(array_map('strtolower', get_defined_functions()['internal'] ?? []), true);
$keywords = array_fill_keys([
    'if','else','elseif','for','foreach','while','switch','case','default','new','clone','echo','print','include','require','include_once','require_once','return','isset','empty','array','list','exit','die','match'
], true);
$optionalExtensionFunctions = array_fill_keys([
    'imagecreatefromwebp',
    'imagewebp',
], true);

foreach ($iterators as $it) {
    foreach ($it as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }
        $path = (string) $file;
        $code = (string) file_get_contents($path);
        $tokens = token_get_all($code);
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $tok = $tokens[$i];
            if (!is_array($tok) || $tok[0] !== T_STRING) {
                continue;
            }

            $name = strtolower($tok[1]);

            // definition: function name(
            $jDef = $i - 1;
            while ($jDef >= 0 && is_array($tokens[$jDef]) && in_array($tokens[$jDef][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG, T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG], true)) {
                $jDef--;
            }
            $prev = $tokens[$jDef] ?? null;
            if (is_array($prev) && $prev[0] === T_FUNCTION) {
                $definitions[$name] = true;
                continue;
            }

            if (isset($keywords[$name])) {
                continue;
            }

            // skip method/static calls: ->foo( or ::foo(
            $j = $i - 1;
            while ($j >= 0 && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $j--;
            }
            $prevSig = $tokens[$j] ?? null;
            if (is_array($prevSig) && in_array($prevSig[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW], true)) {
                continue;
            }
            if ($prevSig === '->' || $prevSig === '?->' || $prevSig === '::') {
                continue;
            }

            // must be followed by (
            $k = $i + 1;
            while ($k < $count && is_array($tokens[$k]) && in_array($tokens[$k][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $k++;
            }
            if (($tokens[$k] ?? null) !== '(') {
                continue;
            }

            $calls[$name][$path] = true;
        }
    }
}

$missing = [];
foreach ($calls as $name => $files) {
    if (isset($definitions[$name]) || isset($builtin[$name]) || isset($optionalExtensionFunctions[$name])) {
        continue;
    }
    $missing[$name] = array_keys($files);
}
ksort($missing);

if ($jsonMode) {
    $normalized = [];
    foreach ($missing as $name => $files) {
        $normalized[$name] = array_map(static fn(string $f): string => str_replace($root . '/', '', $f), $files);
    }
    echo json_encode([
        'missing_calls' => count($missing),
        'missing' => $normalized,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo 'MISSING_CALLS=' . count($missing) . PHP_EOL;
    foreach ($missing as $name => $files) {
        echo $name . ' => ' . str_replace($root . '/', '', $files[0]) . PHP_EOL;
    }
}

exit(count($missing) > 0 ? 1 : 0);
