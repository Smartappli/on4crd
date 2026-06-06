<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$i18nRoot = $root . '/app/i18n';

require_once $root . '/app/i18n_helpers.php';

$jsonOutput = in_array('--json', $argv, true);
$failOnWarning = in_array('--strict', $argv, true) || in_array('--fail-on-warning', $argv, true);

$locales = supported_locales();
$sourceLocales = ['fr', 'en'];
$targetLocales = array_values(array_filter(
    $locales,
    static fn (string $locale): bool => !in_array($locale, $sourceLocales, true)
));

$technicalValues = array_fill_keys([
    'ADIF',
    'AM',
    'APRS',
    'API',
    'CSV',
    'CW',
    'D-STAR',
    'DMR',
    'EchoLink',
    'FM',
    'FT4',
    'FT8',
    'GDPR',
    'HAMQSL',
    'HamQSL',
    'HAREC',
    'HTML',
    'JSON',
    'LoTW',
    'LoTW + eQSL',
    'LoRa',
    'MHz',
    'ON2',
    'ON3',
    'ON4CRD',
    'PDF',
    'QRZ',
    'QRZ.com',
    'QSL',
    'RGPD',
    'RSS',
    'RTTY',
    'SSB',
    'SSTV',
    'UTC',
    'URL',
    'Winlink',
    'WSPR',
    'eQSL',
    'kHz',
], true);

/**
 * @return array<string, string>
 */
function flatten_i18n_values(array $values, string $prefix = ''): array
{
    $flat = [];

    foreach ($values as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;
        if (is_array($value)) {
            $flat += flatten_i18n_values($value, $path);
            continue;
        }
        if (is_string($value)) {
            $flat[$path] = $value;
        }
    }

    return $flat;
}

/**
 * @return array<string, string>
 */
function collect_non_string_values(array $values, string $prefix = ''): array
{
    $issues = [];

    foreach ($values as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;
        if (is_array($value)) {
            $issues += collect_non_string_values($value, $path);
            continue;
        }
        if (!is_string($value)) {
            $issues[$path] = get_debug_type($value);
        }
    }

    return $issues;
}

/**
 * @return array{ok: bool, value: mixed, error: string}
 */
function load_i18n_file(string $path): array
{
    try {
        $t = static fn (string $key): string => $key;

        return [
            'ok' => true,
            'value' => require $path,
            'error' => '',
        ];
    } catch (Throwable $exception) {
        return [
            'ok' => false,
            'value' => null,
            'error' => $exception::class . ': ' . $exception->getMessage(),
        ];
    }
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function relative_i18n_path(string $path, string $root): string
{
    $normalizedPath = str_replace('\\', '/', $path);
    $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/') . '/';

    return str_starts_with($normalizedPath, $normalizedRoot)
        ? substr($normalizedPath, strlen($normalizedRoot))
        : $normalizedPath;
}

/**
 * @return list<string>
 */
function extract_printf_placeholders(string $value): array
{
    $placeholders = [];
    if (preg_match_all('/(?<!%)%(?:\d+\$)?[+\-]?\d*(?:\.\d+)?[bcdeEufFgGosxX]/', $value, $matches) !== false) {
        foreach ($matches[0] as $match) {
            if (preg_match('/([bcdeEufFgGosxX])$/', (string) $match, $type) === 1) {
                $placeholders[] = '%' . (string) $type[1];
            }
        }
    }

    foreach (['/\{[A-Za-z_][A-Za-z0-9_]*\}/', '/%[A-Za-z_][A-Za-z0-9_]*%/'] as $pattern) {
        if (preg_match_all($pattern, $value, $matches) === false) {
            continue;
        }
        foreach ($matches[0] as $match) {
            $placeholders[] = (string) $match;
        }
    }

    sort($placeholders);

    return $placeholders;
}

/**
 * @return list<string>
 */
function extract_html_tag_set(string $value): array
{
    if (preg_match_all('/<\s*(\/?)\s*([A-Za-z][A-Za-z0-9:-]*)\b[^>]*>/u', $value, $matches, PREG_SET_ORDER) === false) {
        return [];
    }

    $tags = [];
    foreach ($matches as $match) {
        $tags[] = (((string) $match[1]) === '/' ? 'close:' : 'open:') . strtolower((string) $match[2]);
    }
    sort($tags);

    return $tags;
}

function has_mojibake(string $value): bool
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

function is_non_translatable_i18n_literal(string $key, string $value, array $technicalValues): bool
{
    $normalized = trim(strip_tags($value));
    $key = strtolower($key);
    static $sharedValues = null;
    if ($sharedValues === null) {
        $sharedValues = array_fill_keys([
            'Administration',
            'Adresse',
            'Adresse.',
            'Agenda',
            'Album public',
            'Altitude',
            'Amber',
            'Antennes',
            'Assistant',
            'Aurora',
            'Band',
            'Campagnes',
            'Canal',
            'Clics',
            'Club',
            'Comité',
            'Contact',
            'CQ zone',
            'dBµV',
            'Dessert',
            'Details',
            'Deutsch',
            'Document',
            'Duty cycle',
            'Email',
            'E-mail',
            'Emerald',
            'Export',
            'File',
            'Filter',
            'Front SVG',
            'Grid',
            'Grx (dBi)',
            'Gtx (dBi)',
            'Illustration ON4CRD',
            'Impedance (Ω)',
            'Import',
            'Input ERP (W)',
            'ITU zone',
            'Label',
            'Legal',
            'Licence',
            'Liste',
            'Locator',
            'Locator A',
            'Locator B',
            'Logo UBA',
            'Modules',
            'Name',
            'ON4CRD Newsletter',
            'Orange',
            'Parser',
            'Password',
            'Pause',
            'Polarisation',
            'Preview',
            'Privacy',
            'Profil',
            'Prosigns',
            'Public',
            'QSL via',
            'QTH / Localité',
            'record(s)',
            'Reset',
            'Role',
            's to ms',
            'Separator',
            'Service',
            'Sponsoring ON4CRD',
            'Start',
            'Station',
            'Stats',
            'Status',
            'Total:',
            'Type',
            'UBA logo',
            'Upload',
            'Violet',
            'Visual',
            'Vpp to Vrms',
            'Vrms to Vpp',
            'Watts (W)',
            'Website',
            'Week',
            'Wh to Joules',
            'Widgets',
            'Wiki administration',
        ], true);
    }

    if ($normalized === '' || isset($technicalValues[$normalized]) || isset($sharedValues[$normalized])) {
        return true;
    }

    if (preg_match('/(?:^|_)(?:slug|code|filename|file_name|url|uri|path|route|id|uuid|token|format|unit|dbm|dbw|ohm|mhz|khz|hz)(?:_|$)/', $key) === 1) {
        return true;
    }
    if (preg_match('/(?:^|_)(?:value|line|venue|club_name|calendar_name|default_title|wiki_title|address_value|location_value|callsign_value)(?:_|$)/', $key) === 1) {
        return true;
    }
    if (preg_match('/^(?:[a-z][a-z0-9]*_)+[a-z0-9]+$/', $normalized) === 1) {
        return true;
    }
    if (preg_match('/\.(?:ics|pdf|csv|json|xml|html?|png|jpe?g|webp|gif|svg)\b/i', $normalized) === 1) {
        return true;
    }
    if (preg_match('/\b(?:Radio Club Durnal|Bocq Arena|Rue des Écoles|Purnode|Yvoir|Durnal)\b/u', $normalized) === 1) {
        return true;
    }
    if (in_array($normalized, ['Album', 'Albums', 'Dashboard', 'Galerie', 'Newsletter', 'Q-code', 'Sponsoring', 'Wiki'], true)) {
        return true;
    }

    return false;
}

function is_probably_translatable_literal(string $key, string $value, array $technicalValues): bool
{
    $normalized = trim(strip_tags($value));
    if (is_non_translatable_i18n_literal($key, $value, $technicalValues)) {
        return false;
    }
    if ($normalized === '' || text_length($normalized) < 4) {
        return false;
    }
    if (isset($technicalValues[$normalized])) {
        return false;
    }
    if (preg_match('/\p{L}/u', $normalized) !== 1) {
        return false;
    }
    if (preg_match('/^[A-Z0-9 _.\-:\/()+#%&]+$/u', $normalized) === 1) {
        return false;
    }
    if (preg_match('/^(?:[A-Z]{2,}|[A-Z0-9-]{2,})(?:\s+(?:[A-Z]{2,}|[A-Z0-9-]{2,}))*$/u', $normalized) === 1) {
        return false;
    }

    return true;
}

/**
 * @param list<string> $issues
 */
function add_module_issue(array &$issues, string $severity, string $locale, string $key, string $message): void
{
    $issues[] = strtoupper($severity) . ' ' . $locale . ' ' . $key . ' - ' . $message;
}

$moduleReports = [];
$totalErrors = 0;
$totalWarnings = 0;
$totalChecked = 0;

$moduleDirs = glob($i18nRoot . '/*', GLOB_ONLYDIR) ?: [];
sort($moduleDirs);

foreach ($moduleDirs as $dir) {
    $module = basename($dir);
    $issues = [];
    $loaded = [];
    $files = [];
    $checked = 0;

    foreach (glob($dir . '/*.php') ?: [] as $path) {
        $locale = basename($path, '.php');
        if (!in_array($locale, $locales, true)) {
            add_module_issue($issues, 'error', $locale, '-', 'Fichier de langue inattendu: ' . relative_i18n_path($path, $root));
        }
    }

    foreach ($locales as $locale) {
        $path = $dir . '/' . $locale . '.php';
        $relativePath = relative_i18n_path($path, $root);
        if (!is_file($path)) {
            add_module_issue($issues, 'error', $locale, '-', 'Fichier de langue manquant: ' . $relativePath);
            continue;
        }

        $contents = file_get_contents($path);
        if (!is_string($contents) || preg_match('//u', $contents) !== 1) {
            add_module_issue($issues, 'error', $locale, '-', 'Fichier non UTF-8: ' . $relativePath);
            continue;
        }

        $result = load_i18n_file($path);
        if (!$result['ok']) {
            add_module_issue($issues, 'error', $locale, '-', 'Erreur PHP: ' . $result['error']);
            continue;
        }
        if (!is_array($result['value'])) {
            add_module_issue($issues, 'error', $locale, '-', 'Le fichier doit retourner un tableau.');
            continue;
        }

        foreach (collect_non_string_values($result['value']) as $key => $type) {
            add_module_issue($issues, 'error', $locale, $key, 'Valeur non textuelle: ' . $type);
        }

        $loaded[$locale] = flatten_i18n_values($result['value']);
        $files[$locale] = $relativePath;
    }

    $reference = $loaded['fr'] ?? ($loaded['en'] ?? []);
    $referenceKeys = array_keys($reference);
    sort($referenceKeys);

    foreach ($loaded as $locale => $dictionary) {
        $keys = array_keys($dictionary);
        sort($keys);
        foreach (array_diff($referenceKeys, $keys) as $missingKey) {
            add_module_issue($issues, 'error', $locale, $missingKey, 'Clé absente par rapport à la référence.');
        }
        foreach (array_diff($keys, $referenceKeys) as $extraKey) {
            add_module_issue($issues, 'warning', $locale, $extraKey, 'Clé absente de la référence.');
        }

        foreach ($dictionary as $key => $value) {
            $checked++;
            if (trim($value) === '') {
                add_module_issue($issues, 'error', $locale, $key, 'Traduction vide.');
                continue;
            }
            if (has_mojibake($value)) {
                add_module_issue($issues, 'error', $locale, $key, 'Suspicion de mojibake.');
            }
            if (
                preg_match('/\b(?:TODO|FIXME|TBD)\b/u', $value) === 1
                || preg_match('/\b(?:translate me|untranslated|lorem ipsum)\b/iu', $value) === 1
            ) {
                add_module_issue($issues, 'warning', $locale, $key, 'Fragment éditorial ou placeholder suspect.');
            }
            if (!array_key_exists($key, $reference)) {
                continue;
            }

            if (extract_printf_placeholders($reference[$key]) !== extract_printf_placeholders($value)) {
                add_module_issue($issues, 'error', $locale, $key, 'Placeholders incohérents.');
            }
            if (extract_html_tag_set($reference[$key]) !== extract_html_tag_set($value)) {
                add_module_issue($issues, 'error', $locale, $key, 'Balises HTML incohérentes.');
            }
        }
    }

    foreach ($targetLocales as $locale) {
        if (!isset($loaded[$locale])) {
            continue;
        }

        foreach ($loaded[$locale] as $key => $value) {
            foreach ($sourceLocales as $sourceLocale) {
                if (!isset($loaded[$sourceLocale][$key])) {
                    continue;
                }
                $sourceValue = trim($loaded[$sourceLocale][$key]);
                if (
                    trim($value) === $sourceValue
                    && is_probably_translatable_literal($key, $sourceValue, $technicalValues)
                ) {
                    add_module_issue(
                        $issues,
                        'warning',
                        $locale,
                        $key,
                        'Valeur identique à ' . $sourceLocale . ': "' . $sourceValue . '"'
                    );
                    break;
                }
            }
        }
    }

    $errors = count(array_filter($issues, static fn (string $issue): bool => str_starts_with($issue, 'ERROR ')));
    $warnings = count(array_filter($issues, static fn (string $issue): bool => str_starts_with($issue, 'WARNING ')));
    $totalErrors += $errors;
    $totalWarnings += $warnings;
    $totalChecked += $checked;

    $moduleReports[$module] = [
        'locales' => count($loaded),
        'checked_values' => $checked,
        'errors' => $errors,
        'warnings' => $warnings,
        'issues' => $issues,
    ];
}

if ($jsonOutput) {
    echo json_encode([
        'modules' => count($moduleReports),
        'locales' => count($locales),
        'checked_values' => $totalChecked,
        'errors' => $totalErrors,
        'warnings' => $totalWarnings,
        'modules_detail' => $moduleReports,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit($totalErrors > 0 ? 1 : ($failOnWarning && $totalWarnings > 0 ? 2 : 0));
}

echo 'I18N MODULE-BY-MODULE AUDIT' . PHP_EOL;
echo 'Modules: ' . count($moduleReports) . PHP_EOL;
echo 'Locales: ' . count($locales) . PHP_EOL;
echo 'Checked string values: ' . $totalChecked . PHP_EOL;
echo 'Errors: ' . $totalErrors . PHP_EOL;
echo 'Warnings: ' . $totalWarnings . PHP_EOL . PHP_EOL;

foreach ($moduleReports as $module => $report) {
    echo sprintf(
        '[%s] locales=%d values=%d errors=%d warnings=%d',
        $module,
        $report['locales'],
        $report['checked_values'],
        $report['errors'],
        $report['warnings']
    ), PHP_EOL;

    foreach (array_slice($report['issues'], 0, 12) as $issue) {
        echo '  - ' . $issue . PHP_EOL;
    }
    if (count($report['issues']) > 12) {
        echo '  - ... +' . (count($report['issues']) - 12) . ' autres' . PHP_EOL;
    }
}

exit($totalErrors > 0 ? 1 : ($failOnWarning && $totalWarnings > 0 ? 2 : 0));
