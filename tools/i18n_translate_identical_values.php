<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$i18nRoot = $root . '/app/i18n';
$cacheFile = $root . '/storage/i18n-sequential-translation-cache.json';

require_once $root . '/app/i18n_helpers.php';

$dryRun = in_array('--dry-run', $argv, true);
$moduleFilter = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--module=')) {
        $raw = trim(substr($arg, strlen('--module=')));
        $moduleFilter = $raw === '' ? null : array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}

$sourceLocales = ['fr', 'en'];
$targetLocales = array_values(array_filter(
    supported_locales(),
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
    'LoRa',
    'MHz',
    'ON2',
    'ON3',
    'ON4CRD',
    'PDF',
    'QRZ',
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
    'kHz',
], true);

/**
 * @return array<string, mixed>
 */
function load_cache(string $cacheFile): array
{
    if (!is_file($cacheFile)) {
        return [];
    }

    $contents = file_get_contents($cacheFile);
    if (!is_string($contents) || trim($contents) === '') {
        return [];
    }

    $decoded = json_decode($contents, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * @param array<string, mixed> $cache
 */
function save_cache(string $cacheFile, array $cache): void
{
    $json = json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (is_string($json)) {
        file_put_contents($cacheFile, $json . PHP_EOL);
    }
}

function text_length_for_translate(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function is_non_translatable_i18n_literal_for_translate(string $key, string $value, array $technicalValues): bool
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

function is_translatable_i18n_literal_for_translate(string $key, string $value, array $technicalValues): bool
{
    $normalized = trim(strip_tags($value));
    if (is_non_translatable_i18n_literal_for_translate($key, $value, $technicalValues)) {
        return false;
    }
    if ($normalized === '' || text_length_for_translate($normalized) < 4) {
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
 * @return array<string, mixed>
 */
function load_i18n_array_for_translate(string $path): array
{
    $t = static fn (string $key): string => $key;
    $values = require $path;
    if (!is_array($values)) {
        throw new RuntimeException('Invalid i18n array: ' . $path);
    }

    return $values;
}

/**
 * @return array<string, string>
 */
function flatten_for_translate(array $values, string $prefix = ''): array
{
    $flat = [];
    foreach ($values as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;
        if (is_array($value)) {
            $flat += flatten_for_translate($value, $path);
            continue;
        }
        if (is_string($value)) {
            $flat[$path] = $value;
        }
    }

    return $flat;
}

/**
 * @return list<string>
 */
function extract_placeholders_for_translate(string $value): array
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
 * @return array{0: string, 1: array<string, string>}
 */
function protect_translation_literals(string $value): array
{
    $protected = [];
    $patterns = [
        '/(?<!%)%(?:\d+\$)?[+\-]?\d*(?:\.\d+)?[bcdeEufFgGosxX]/',
        '/\{[A-Za-z_][A-Za-z0-9_]*\}/',
        '/%[A-Za-z_][A-Za-z0-9_]*%/',
        '#<[^>]+>#',
        '#https?://[^\s<>"\']+#',
        '/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i',
        '/\b(?:ON4CRD|QSL|LoTW|eQSL|QRZ|Maidenhead|Nominatim|HAREC|GDPR|RGPD|UBA|CQ|ITU|ON[0-9A-Z]+|JO[0-9A-Z]{2,}|FT8|FT4|D-STAR|DMR|APRS|SSTV|RTTY|WSPR|Winlink|EchoLink)\b/u',
        '/\b\d+(?:[.,]\d+)?\s*(?:MHz|kHz|Hz|dBm|dBW|dBi|dBd|mAh|mA|W|V|Ω|µH|pF|km|m)\b/u',
        '/\b\d{4}-\d{2}-\d{2}\b/',
    ];

    foreach ($patterns as $pattern) {
        $value = preg_replace_callback(
            $pattern,
            static function (array $matches) use (&$protected): string {
                $token = 'XPROTECT' . count($protected) . 'X';
                $protected[$token] = (string) $matches[0];

                return $token;
            },
            $value
        ) ?? $value;
    }

    return [$value, $protected];
}

/**
 * @param array<string, string> $protected
 */
function restore_translation_literals(string $value, array $protected): string
{
    foreach ($protected as $token => $original) {
        $value = str_replace([$token, strtolower($token)], $original, $value);
        $value = preg_replace('/' . implode('\s*', str_split(preg_quote($token, '/'))) . '/i', $original, $value) ?? $value;
    }

    return $value;
}

/**
 * @param list<string> $texts
 * @return array<string, string>
 */
function translate_unique_texts(array $texts, string $sourceLocale, string $targetLocale, array &$cache): array
{
    $translations = [];
    $pending = [];

    foreach ($texts as $text) {
        $cacheKey = $sourceLocale . '|' . $targetLocale . '|' . $text;
        if (isset($cache[$cacheKey]) && is_string($cache[$cacheKey])) {
            $translations[$text] = $cache[$cacheKey];
            continue;
        }
        $pending[$text] = true;
    }

    $separator = 'ZXQSEPARATORZXQ';
    $pendingTexts = array_keys($pending);
    foreach (array_chunk($pendingTexts, 20) as $chunk) {
        $protectedMaps = [];
        $protectedTexts = [];
        foreach ($chunk as $text) {
            [$protectedText, $protectedMap] = protect_translation_literals($text);
            $protectedTexts[] = $protectedText;
            $protectedMaps[] = $protectedMap;
        }

        $translatedParts = translate_google_text(implode("\n" . $separator . "\n", $protectedTexts), $sourceLocale, $targetLocale);
        $parts = $translatedParts === null ? [] : array_map('trim', explode($separator, $translatedParts));
        if (count($parts) !== count($chunk)) {
            $parts = [];
            foreach ($protectedTexts as $protectedText) {
                $single = translate_google_text($protectedText, $sourceLocale, $targetLocale);
                $parts[] = $single ?? '';
            }
        }

        foreach ($chunk as $index => $text) {
            $translated = trim($parts[$index] ?? '');
            if ($translated === '') {
                continue;
            }
            $translated = restore_translation_literals($translated, $protectedMaps[$index]);
            if (extract_placeholders_for_translate($text) !== extract_placeholders_for_translate($translated)) {
                continue;
            }
            if (preg_match('/XPROTECT\d+X/i', $translated) === 1) {
                continue;
            }

            $translations[$text] = $translated;
            $cache[$sourceLocale . '|' . $targetLocale . '|' . $text] = $translated;
        }
    }

    return $translations;
}

function translate_google_text(string $text, string $sourceLocale, string $targetLocale): ?string
{
    $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl='
        . rawurlencode($sourceLocale)
        . '&tl='
        . rawurlencode($targetLocale)
        . '&dt=t&q='
        . rawurlencode($text);
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header' => "User-Agent: Mozilla/5.0\r\n",
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || !isset($decoded[0]) || !is_array($decoded[0])) {
        return null;
    }

    $translated = '';
    foreach ($decoded[0] as $segment) {
        if (is_array($segment) && isset($segment[0]) && is_string($segment[0])) {
            $translated .= $segment[0];
        }
    }

    return $translated === '' ? null : $translated;
}

/**
 * @param array<string, mixed> $values
 * @param list<string> $path
 */
function set_nested_translation_value(array &$values, array $path, string $translation): void
{
    $cursor = &$values;
    foreach ($path as $index => $key) {
        if ($index === count($path) - 1) {
            $cursor[$key] = $translation;
            return;
        }
        if (!isset($cursor[$key]) || !is_array($cursor[$key])) {
            return;
        }
        $cursor = &$cursor[$key];
    }
}

/**
 * @param array<string, mixed> $target
 * @param array<string, string> $fr
 * @param array<string, string> $en
 * @param list<string> $path
 * @param array<string, array<int, array{path: list<string>, key: string, text: string, source: string}>> $tasks
 */
function collect_translation_tasks(
    array $target,
    array $fr,
    array $en,
    string $locale,
    array $technicalValues,
    array &$tasks,
    array $path = []
): void {
    foreach ($target as $key => $value) {
        $currentPath = array_merge($path, [(string) $key]);
        $flatKey = implode('.', $currentPath);
        if (is_array($value)) {
            collect_translation_tasks($value, $fr, $en, $locale, $technicalValues, $tasks, $currentPath);
            continue;
        }
        if (!is_string($value)) {
            continue;
        }

        foreach (['en' => $en[$flatKey] ?? null, 'fr' => $fr[$flatKey] ?? null] as $sourceLocale => $sourceValue) {
            if (!is_string($sourceValue)) {
                continue;
            }
            if (trim($value) !== trim($sourceValue)) {
                continue;
            }
            if (!is_translatable_i18n_literal_for_translate($flatKey, $sourceValue, $technicalValues)) {
                continue;
            }

            $tasks[$locale][] = [
                'path' => $currentPath,
                'key' => $flatKey,
                'text' => $sourceValue,
                'source' => $sourceLocale,
            ];
            break;
        }
    }
}

function export_i18n_array(array $values): string
{
    return "<?php\n"
        . "declare(strict_types=1);\n\n"
        . 'return '
        . var_export($values, true)
        . ";\n";
}

$cache = load_cache($cacheFile);
$moduleDirs = glob($i18nRoot . '/*', GLOB_ONLYDIR) ?: [];
sort($moduleDirs);

$totalCandidates = 0;
$totalUpdated = 0;
$changedFiles = 0;
$failedModules = [];

foreach ($moduleDirs as $dir) {
    $module = basename($dir);
    if (is_array($moduleFilter) && !in_array($module, $moduleFilter, true)) {
        continue;
    }

    $frPath = $dir . '/fr.php';
    $enPath = $dir . '/en.php';
    if (!is_file($frPath) || !is_file($enPath)) {
        $failedModules[] = $module . ': missing fr/en reference';
        continue;
    }

    try {
        $fr = flatten_for_translate(load_i18n_array_for_translate($frPath));
        $en = flatten_for_translate(load_i18n_array_for_translate($enPath));
    } catch (Throwable $exception) {
        $failedModules[] = $module . ': ' . $exception->getMessage();
        continue;
    }

    $moduleCandidates = 0;
    $moduleUpdated = 0;
    $moduleFiles = 0;

    foreach ($targetLocales as $locale) {
        $path = $dir . '/' . $locale . '.php';
        if (!is_file($path)) {
            continue;
        }

        try {
            $target = load_i18n_array_for_translate($path);
        } catch (Throwable $exception) {
            $failedModules[] = $module . '/' . $locale . ': ' . $exception->getMessage();
            continue;
        }

        $tasksByLocale = [];
        collect_translation_tasks($target, $fr, $en, $locale, $technicalValues, $tasksByLocale);
        $tasks = $tasksByLocale[$locale] ?? [];
        if ($tasks === []) {
            continue;
        }

        $groupedTexts = [];
        foreach ($tasks as $task) {
            $groupedTexts[$task['source']][$task['text']] = true;
        }

        $translationsBySource = [];
        foreach ($groupedTexts as $sourceLocale => $texts) {
            $translationsBySource[$sourceLocale] = $dryRun
                ? []
                : translate_unique_texts(array_keys($texts), $sourceLocale, $locale, $cache);
        }

        $fileChanged = false;
        foreach ($tasks as $task) {
            $moduleCandidates++;
            if ($dryRun) {
                continue;
            }

            $translation = $translationsBySource[$task['source']][$task['text']] ?? null;
            if (!is_string($translation) || trim($translation) === '' || trim($translation) === trim($task['text'])) {
                continue;
            }

            set_nested_translation_value($target, $task['path'], $translation);
            $moduleUpdated++;
            $fileChanged = true;
        }

        if ($fileChanged) {
            file_put_contents($path, export_i18n_array($target));
            $moduleFiles++;
        }
    }

    $totalCandidates += $moduleCandidates;
    $totalUpdated += $moduleUpdated;
    $changedFiles += $moduleFiles;

    echo sprintf(
        '[%s] candidates=%d updated=%d files=%d',
        $module,
        $moduleCandidates,
        $moduleUpdated,
        $moduleFiles
    ), PHP_EOL;

    if (!$dryRun && $totalUpdated > 0 && $totalUpdated % 250 === 0) {
        save_cache($cacheFile, $cache);
    }
}

if (!$dryRun) {
    save_cache($cacheFile, $cache);
}

echo 'TOTAL_CANDIDATES=' . $totalCandidates . PHP_EOL;
echo 'TOTAL_UPDATED=' . $totalUpdated . PHP_EOL;
echo 'CHANGED_FILES=' . $changedFiles . PHP_EOL;
echo 'FAILED_MODULES=' . count($failedModules) . PHP_EOL;
foreach ($failedModules as $failedModule) {
    echo '- ' . $failedModule . PHP_EOL;
}

exit(count($failedModules) > 0 ? 1 : 0);
