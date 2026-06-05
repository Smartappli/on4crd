<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$i18nRoot = $root . '/app/i18n';

require_once $root . '/app/i18n_helpers.php';

$expectedLocales = supported_locales();
$jsonOutput = in_array('--json', $argv, true);
$failOnWarnings = in_array('--strict', $argv, true)
    || in_array('--strict-warnings', $argv, true)
    || in_array('--fail-on-warnings', $argv, true);
$maxSamples = 5;
$moduleFilter = null;
$localeFilter = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--max-samples=')) {
        $value = (int) substr($arg, strlen('--max-samples='));
        $maxSamples = max(0, $value);
        continue;
    }
    if (str_starts_with($arg, '--module=')) {
        $raw = trim((string) substr($arg, strlen('--module=')));
        $moduleFilter = $raw === '' ? null : array_values(array_filter(array_map('trim', explode(',', $raw))));
        continue;
    }
    if (str_starts_with($arg, '--locale=')) {
        $raw = trim((string) substr($arg, strlen('--locale=')));
        $localeFilter = $raw === '' ? null : array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}

$activeLocales = $expectedLocales;
if (is_array($localeFilter)) {
    $activeLocales = array_values(array_intersect($expectedLocales, $localeFilter));
}
$loadLocales = array_values(array_unique(array_merge(['fr', 'en'], $activeLocales)));

/**
 * @param array<int, array<string, mixed>> $issues
 */
function add_issue(
    array &$issues,
    string $severity,
    string $type,
    string $module,
    ?string $locale,
    ?string $key,
    string $message,
    ?string $file = null
): void {
    $issues[] = [
        'severity' => $severity,
        'type' => $type,
        'module' => $module,
        'locale' => $locale,
        'key' => $key,
        'message' => $message,
        'file' => $file,
    ];
}

function relative_path(string $path, string $root): string
{
    $path = str_replace('\\', '/', $path);
    $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
    if (str_starts_with($path, $root)) {
        return substr($path, strlen($root));
    }
    return $path;
}

/**
 * @return array{ok: bool, value: mixed, error: ?string}
 */
function load_php_value(string $path): array
{
    try {
        return [
            'ok' => true,
            'value' => require $path,
            'error' => null,
        ];
    } catch (Throwable $e) {
        return [
            'ok' => false,
            'value' => null,
            'error' => $e::class . ': ' . $e->getMessage(),
        ];
    }
}

/**
 * @param array<mixed> $values
 * @return array{flat: array<string, string>, non_string: array<string, string>}
 */
function flatten_dictionary(array $values, string $prefix = ''): array
{
    $flat = [];
    $nonString = [];

    foreach ($values as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;
        if (is_array($value)) {
            $nested = flatten_dictionary($value, $path);
            $flat += $nested['flat'];
            $nonString += $nested['non_string'];
            continue;
        }
        if (is_string($value)) {
            $flat[$path] = $value;
            continue;
        }
        $nonString[$path] = get_debug_type($value);
    }

    return [
        'flat' => $flat,
        'non_string' => $nonString,
    ];
}

function has_mojibake_artifact(string $value): bool
{
    static $patterns = null;
    if ($patterns === null) {
        $byte = '[\x{0080}-\x{00BF}\x{0152}\x{0153}\x{0160}\x{0161}\x{0178}\x{017D}\x{017E}\x{0192}\x{02C6}\x{02DC}\x{2013}\x{2014}\x{2018}\x{2019}\x{201A}\x{201C}\x{201D}\x{201E}\x{2020}\x{2021}\x{2022}\x{2026}\x{2030}\x{2039}\x{203A}\x{20AC}\x{2122}]';
        $patterns = [
            '/[\x{00E0}-\x{00EF}]' . $byte . $byte . '/u',
            '/[\x{00C2}-\x{00DF}]' . $byte . '/u',
            '/\x{00EF}\x{00BF}\x{00BD}/u',
            '/\x{FFFD}/u',
        ];
    }

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value) === 1) {
            return true;
        }
    }

    return false;
}

/**
 * @return array<int, string>
 */
function extract_placeholders(string $value): array
{
    $matches = [];

    if (preg_match_all('/(?<!%)%(?:\d+\$)?[+\-]?\d*(?:\.\d+)?[bcdeEufFgGosxX]/', $value, $found) !== false) {
        foreach ($found[0] as $match) {
            if (preg_match('/([bcdeEufFgGosxX])$/', $match, $typeMatch) === 1) {
                $matches[] = '%' . (string) $typeMatch[1];
            }
        }
    }

    foreach (['/\{[A-Za-z_][A-Za-z0-9_]*\}/', '/%[A-Za-z_][A-Za-z0-9_]*%/'] as $pattern) {
        if (preg_match_all($pattern, $value, $found) === false) {
            continue;
        }
        foreach ($found[0] as $match) {
            $matches[] = $match;
        }
    }

    sort($matches);
    return $matches;
}

/**
 * @return array<int, string>
 */
function extract_html_tags(string $value): array
{
    if (preg_match_all('/<\s*(\/?)\s*([A-Za-z][A-Za-z0-9:-]*)\b[^>]*>/u', $value, $matches, PREG_SET_ORDER) === false) {
        return [];
    }

    $tags = [];
    foreach ($matches as $match) {
        $name = strtolower((string) $match[2]);
        $prefix = ((string) $match[1]) === '/' ? 'close:' : 'open:';
        $tags[] = $prefix . $name;
    }

    sort($tags);
    return $tags;
}

function contains_suspicious_fragment(string $value): bool
{
    return preg_match('/\b(?:TODO|FIXME|TBD)\b/u', $value) === 1
        || preg_match('/\b(?:lorem ipsum|translate me|untranslated)\b/iu', $value) === 1;
}

function is_translatable_text(string $value): bool
{
    $trimmed = trim($value);
    $length = function_exists('mb_strlen') ? mb_strlen($trimmed, 'UTF-8') : strlen($trimmed);
    if ($length < 3) {
        return false;
    }
    if (preg_match('/\p{L}/u', $trimmed) !== 1) {
        return false;
    }
    return preg_match('/^[A-Z0-9 _.\-:\/()+#%&]+$/u', $trimmed) !== 1;
}

/**
 * @param array<string, array<string, string>> $dictionaries
 * @return array<int, string>
 */
function select_base_keys(array $dictionaries): array
{
    foreach (['fr', 'en'] as $locale) {
        if (isset($dictionaries[$locale]) && $dictionaries[$locale] !== []) {
            return array_keys($dictionaries[$locale]);
        }
    }

    foreach ($dictionaries as $dictionary) {
        if ($dictionary !== []) {
            return array_keys($dictionary);
        }
    }

    return [];
}

/**
 * @param array<string, array<string, string>> $dictionaries
 * @param array<int, string> $activeLocales
 * @param array<int, array<string, mixed>> $issues
 */
function audit_catalog_values(
    string $module,
    array $dictionaries,
    array $files,
    array $activeLocales,
    array &$issues
): int {
    $checkedValues = 0;
    $baseLocale = isset($dictionaries['fr']) ? 'fr' : (isset($dictionaries['en']) ? 'en' : null);
    $baseKeys = select_base_keys($dictionaries);

    if ($baseLocale === null) {
        add_issue($issues, 'error', 'missing_base_locale', $module, null, null, 'No fr or en base dictionary is available.');
        return 0;
    }

    $baseDictionary = $dictionaries[$baseLocale];
    foreach ($activeLocales as $locale) {
        if (!isset($dictionaries[$locale])) {
            continue;
        }

        $dictionary = $dictionaries[$locale];
        foreach (array_diff($baseKeys, array_keys($dictionary)) as $missingKey) {
            add_issue(
                $issues,
                'error',
                'missing_key',
                $module,
                $locale,
                $missingKey,
                'Key is missing compared with ' . $baseLocale . '.',
                $files[$locale] ?? null
            );
        }

        foreach (array_diff(array_keys($dictionary), $baseKeys) as $extraKey) {
            add_issue(
                $issues,
                'warning',
                'extra_key',
                $module,
                $locale,
                $extraKey,
                'Key does not exist in the base dictionary ' . $baseLocale . '.',
                $files[$locale] ?? null
            );
        }

        foreach ($dictionary as $key => $value) {
            $checkedValues++;
            $trimmed = trim($value);
            if ($trimmed === '') {
                add_issue($issues, 'error', 'empty_value', $module, $locale, $key, 'Translation value is empty.', $files[$locale] ?? null);
                continue;
            }
            if (has_mojibake_artifact($value)) {
                add_issue($issues, 'error', 'mojibake', $module, $locale, $key, 'Value contains a mojibake-like encoding artifact.', $files[$locale] ?? null);
            }
            if (contains_suspicious_fragment($value)) {
                add_issue($issues, 'warning', 'suspicious_fragment', $module, $locale, $key, 'Value contains a placeholder-like editorial fragment.', $files[$locale] ?? null);
            }
            if (!array_key_exists($key, $baseDictionary)) {
                continue;
            }

            $baseValue = $baseDictionary[$key];
            $basePlaceholders = extract_placeholders($baseValue);
            $valuePlaceholders = extract_placeholders($value);
            if ($basePlaceholders !== $valuePlaceholders) {
                add_issue(
                    $issues,
                    'error',
                    'placeholder_mismatch',
                    $module,
                    $locale,
                    $key,
                    'Placeholder set differs from ' . $baseLocale . '.',
                    $files[$locale] ?? null
                );
            }

            $baseTags = extract_html_tags($baseValue);
            $valueTags = extract_html_tags($value);
            if ($baseTags !== $valueTags) {
                add_issue(
                    $issues,
                    'error',
                    'html_tag_mismatch',
                    $module,
                    $locale,
                    $key,
                    'HTML tag set differs from ' . $baseLocale . '.',
                    $files[$locale] ?? null
                );
            }
        }
    }

    foreach (['en', 'fr'] as $referenceLocale) {
        if (!isset($dictionaries[$referenceLocale])) {
            continue;
        }
        $reference = $dictionaries[$referenceLocale];
        foreach ($activeLocales as $locale) {
            if ($locale === $referenceLocale || !isset($dictionaries[$locale])) {
                continue;
            }

            $same = 0;
            $checked = 0;
            foreach ($reference as $key => $referenceValue) {
                if (!isset($dictionaries[$locale][$key])) {
                    continue;
                }
                if (!is_translatable_text($referenceValue)) {
                    continue;
                }
                $checked++;
                if (trim($dictionaries[$locale][$key]) === trim($referenceValue)) {
                    $same++;
                }
            }

            if ($checked >= 5 && $same >= 3 && ($same / $checked) >= 0.70) {
                add_issue(
                    $issues,
                    'warning',
                    'mostly_identical_to_' . $referenceLocale,
                    $module,
                    $locale,
                    null,
                    $same . '/' . $checked . ' translatable values are identical to ' . $referenceLocale . '.',
                    $files[$locale] ?? null
                );
            }
        }
    }

    return $checkedValues;
}

/**
 * @param array<int, array<string, mixed>> $issues
 * @return array{dictionaries: array<string, array<string, string>>, files: array<string, string>, loaded: int, checked_values: int}
 */
function audit_directory_module(
    string $module,
    string $dir,
    string $root,
    array $expectedLocales,
    array $loadLocales,
    array $activeLocales,
    array &$issues
): array {
    $dictionaries = [];
    $files = [];
    $expectedMap = array_fill_keys($expectedLocales, true);

    foreach (glob($dir . '/*.php') ?: [] as $path) {
        $locale = basename($path, '.php');
        if (!isset($expectedMap[$locale])) {
            add_issue($issues, 'error', 'unexpected_locale_file', $module, $locale, null, 'Unexpected locale file.', relative_path($path, $root));
        }
    }

    foreach ($loadLocales as $locale) {
        $path = $dir . '/' . $locale . '.php';
        $rel = relative_path($path, $root);
        if (!is_file($path)) {
            add_issue($issues, 'error', 'missing_locale_file', $module, $locale, null, 'Expected locale file is missing.', $rel);
            continue;
        }

        $loaded = load_php_value($path);
        if (!$loaded['ok']) {
            add_issue($issues, 'error', 'invalid_php_file', $module, $locale, null, (string) $loaded['error'], $rel);
            continue;
        }
        if (!is_array($loaded['value'])) {
            add_issue($issues, 'error', 'invalid_array', $module, $locale, null, 'Locale file must return an array.', $rel);
            continue;
        }

        $flattened = flatten_dictionary($loaded['value']);
        foreach ($flattened['non_string'] as $key => $type) {
            add_issue($issues, 'error', 'non_string_value', $module, $locale, $key, 'Expected string, got ' . $type . '.', $rel);
        }
        $dictionaries[$locale] = $flattened['flat'];
        $files[$locale] = $rel;
    }

    return [
        'dictionaries' => $dictionaries,
        'files' => $files,
        'loaded' => count($dictionaries),
        'checked_values' => audit_catalog_values($module, $dictionaries, $files, $activeLocales, $issues),
    ];
}

/**
 * @param array<int, array<string, mixed>> $issues
 * @return array{dictionaries: array<string, array<string, string>>, files: array<string, string>, loaded: int, checked_values: int}
 */
function audit_standalone_module(
    string $module,
    string $path,
    string $root,
    array $expectedLocales,
    array $loadLocales,
    array $activeLocales,
    array &$issues
): array {
    $rel = relative_path($path, $root);
    $loaded = load_php_value($path);
    $dictionaries = [];
    $files = [];
    $localeMap = array_fill_keys($expectedLocales, true);

    if (!$loaded['ok']) {
        add_issue($issues, 'error', 'invalid_php_file', $module, null, null, (string) $loaded['error'], $rel);
        return ['dictionaries' => [], 'files' => [], 'loaded' => 0, 'checked_values' => 0];
    }
    if (!is_array($loaded['value'])) {
        add_issue($issues, 'error', 'invalid_array', $module, null, null, 'Standalone i18n file must return an array.', $rel);
        return ['dictionaries' => [], 'files' => [], 'loaded' => 0, 'checked_values' => 0];
    }

    $value = $loaded['value'];
    $topLocaleKeys = array_values(array_filter(
        array_keys($value),
        static fn ($key): bool => is_string($key) && isset($localeMap[$key]) && is_array($value[$key])
    ));

    if ($topLocaleKeys !== []) {
        foreach (array_keys($value) as $key) {
            if (is_string($key) && !isset($localeMap[$key])) {
                add_issue($issues, 'error', 'unexpected_standalone_key', $module, null, (string) $key, 'Standalone locale-map contains a non-locale top-level key.', $rel);
            }
        }

        foreach ($loadLocales as $locale) {
            if (!isset($value[$locale]) || !is_array($value[$locale])) {
                add_issue($issues, 'warning', 'missing_locale_catalog', $module, $locale, null, 'Standalone file falls back because it has no dictionary for this locale.', $rel);
                continue;
            }
            $flattened = flatten_dictionary($value[$locale]);
            foreach ($flattened['non_string'] as $key => $type) {
                add_issue($issues, 'error', 'non_string_value', $module, $locale, $key, 'Expected string, got ' . $type . '.', $rel);
            }
            $dictionaries[$locale] = $flattened['flat'];
            $files[$locale] = $rel;
        }

        return [
            'dictionaries' => $dictionaries,
            'files' => $files,
            'loaded' => count($dictionaries),
            'checked_values' => audit_catalog_values($module, $dictionaries, $files, $activeLocales, $issues),
        ];
    }

    $nestedLocales = [];
    foreach ($value as $key => $translations) {
        if (!is_array($translations)) {
            add_issue($issues, 'error', 'invalid_key_locale_map', $module, null, (string) $key, 'Expected nested locale map for this key.', $rel);
            continue;
        }

        foreach ($translations as $locale => $translation) {
            $locale = (string) $locale;
            if (!isset($localeMap[$locale])) {
                add_issue($issues, 'error', 'unexpected_locale_entry', $module, $locale, (string) $key, 'Unexpected locale entry in key-locale map.', $rel);
                continue;
            }
            $nestedLocales[$locale] = true;
            if (!in_array($locale, $loadLocales, true)) {
                continue;
            }
            if (!is_string($translation)) {
                add_issue($issues, 'error', 'non_string_value', $module, $locale, (string) $key, 'Expected string, got ' . get_debug_type($translation) . '.', $rel);
                continue;
            }
            $dictionaries[$locale][(string) $key] = $translation;
            $files[$locale] = $rel;
        }
    }

    foreach ($activeLocales as $locale) {
        if (!isset($nestedLocales[$locale])) {
            add_issue($issues, 'warning', 'missing_locale_catalog', $module, $locale, null, 'Standalone key-locale map falls back because it has no values for this locale.', $rel);
        }
    }

    return [
        'dictionaries' => $dictionaries,
        'files' => $files,
        'loaded' => count($dictionaries),
        'checked_values' => audit_catalog_values($module, $dictionaries, $files, $activeLocales, $issues),
    ];
}

/**
 * @param array<int, array<string, mixed>> $issues
 * @return array<string, int>
 */
function count_by(array $issues, string $field, ?string $severity = null): array
{
    $counts = [];
    foreach ($issues as $issue) {
        if ($severity !== null && $issue['severity'] !== $severity) {
            continue;
        }
        $key = (string) ($issue[$field] ?? '');
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }
    ksort($counts);
    return $counts;
}

/**
 * @param array<string, int> $counts
 */
function print_counts(array $counts): void
{
    foreach ($counts as $key => $count) {
        echo '- ' . $key . ': ' . $count . PHP_EOL;
    }
}

/**
 * @param array<int, array<string, mixed>> $issues
 */
function print_samples(array $issues, int $maxSamples): void
{
    if ($maxSamples === 0 || $issues === []) {
        return;
    }

    $grouped = [];
    foreach ($issues as $issue) {
        $grouped[(string) $issue['severity']][(string) $issue['type']][] = $issue;
    }

    foreach (['error', 'warning'] as $severity) {
        if (!isset($grouped[$severity])) {
            continue;
        }
        echo PHP_EOL . strtoupper($severity) . ' SAMPLES' . PHP_EOL;
        ksort($grouped[$severity]);
        foreach ($grouped[$severity] as $type => $rows) {
            echo $type . ':' . PHP_EOL;
            foreach (array_slice($rows, 0, $maxSamples) as $issue) {
                $target = (string) $issue['module'];
                if ($issue['locale'] !== null) {
                    $target .= '/' . (string) $issue['locale'];
                }
                if ($issue['key'] !== null) {
                    $target .= ' key=' . (string) $issue['key'];
                }
                $file = $issue['file'] !== null ? ' file=' . (string) $issue['file'] : '';
                echo '- ' . $target . $file . ' :: ' . (string) $issue['message'] . PHP_EOL;
            }
            if (count($rows) > $maxSamples) {
                echo '- ... +' . (count($rows) - $maxSamples) . ' more' . PHP_EOL;
            }
        }
    }
}

$issues = [];
$moduleReports = [];
$seenModules = [];

foreach (glob($i18nRoot . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
    $module = basename($dir);
    if (is_array($moduleFilter) && !in_array($module, $moduleFilter, true)) {
        continue;
    }
    $seenModules[$module] = true;
    $report = audit_directory_module($module, $dir, $root, $expectedLocales, $loadLocales, $activeLocales, $issues);
    $moduleReports[$module] = [
        'source' => 'directory',
        'loaded_locales' => $report['loaded'],
        'checked_values' => $report['checked_values'],
    ];
}

foreach (glob($i18nRoot . '/*.php') ?: [] as $path) {
    $module = basename($path, '.php');
    if (isset($seenModules[$module])) {
        continue;
    }
    if (is_array($moduleFilter) && !in_array($module, $moduleFilter, true)) {
        continue;
    }
    $report = audit_standalone_module($module, $path, $root, $expectedLocales, $loadLocales, $activeLocales, $issues);
    $moduleReports[$module] = [
        'source' => 'standalone',
        'loaded_locales' => $report['loaded'],
        'checked_values' => $report['checked_values'],
    ];
}

ksort($moduleReports);
usort(
    $issues,
    static fn (array $a, array $b): int => [
        $a['severity'],
        $a['type'],
        $a['module'],
        (string) $a['locale'],
        (string) $a['key'],
    ] <=> [
        $b['severity'],
        $b['type'],
        $b['module'],
        (string) $b['locale'],
        (string) $b['key'],
    ]
);

$errorCount = count(array_filter($issues, static fn (array $issue): bool => $issue['severity'] === 'error'));
$warningCount = count(array_filter($issues, static fn (array $issue): bool => $issue['severity'] === 'warning'));
$checkedValues = array_sum(array_column($moduleReports, 'checked_values'));
$loadedLocales = array_sum(array_column($moduleReports, 'loaded_locales'));

if ($jsonOutput) {
    echo json_encode([
        'modules' => count($moduleReports),
        'locales' => count($activeLocales),
        'loaded_locale_catalogs' => $loadedLocales,
        'checked_values' => $checkedValues,
        'errors' => $errorCount,
        'warnings' => $warningCount,
        'issues' => $issues,
        'modules_detail' => $moduleReports,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit($errorCount > 0 ? 1 : ($failOnWarnings && $warningCount > 0 ? 2 : 0));
}

echo 'I18N SYSTEMATIC AUDIT' . PHP_EOL;
echo 'Modules: ' . count($moduleReports) . PHP_EOL;
echo 'Locales: ' . implode(',', $activeLocales) . PHP_EOL;
echo 'Loaded locale catalogs: ' . $loadedLocales . PHP_EOL;
echo 'Checked string values: ' . $checkedValues . PHP_EOL;
echo 'Errors: ' . $errorCount . PHP_EOL;
echo 'Warnings: ' . $warningCount . PHP_EOL;

if ($errorCount > 0) {
    echo PHP_EOL . 'ERRORS BY TYPE' . PHP_EOL;
    print_counts(count_by($issues, 'type', 'error'));
}

if ($warningCount > 0) {
    echo PHP_EOL . 'WARNINGS BY TYPE' . PHP_EOL;
    print_counts(count_by($issues, 'type', 'warning'));
}

if ($issues !== []) {
    echo PHP_EOL . 'ISSUES BY MODULE' . PHP_EOL;
    print_counts(count_by($issues, 'module'));
    print_samples($issues, $maxSamples);
} else {
    echo PHP_EOL . 'OK: all audited i18n catalogs passed structural and quality checks.' . PHP_EOL;
}

exit($errorCount > 0 ? 1 : ($failOnWarnings && $warningCount > 0 ? 2 : 0));
