<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$files = glob($root . '/pages/*.php') ?: [];
$requiredLocales = ['fr', 'en', 'de', 'nl', 'es', 'it', 'pt', 'ar', 'bn', 'hi', 'id', 'ja', 'ru', 'zh'];
$homeRequiredLocales = $requiredLocales;
$required = array_map(static fn (string $locale): string => "'" . $locale . "'", $requiredLocales);
$issues = [];
$warnings = [];
$strictWarnings = in_array('--strict-warnings', $argv, true) || in_array('--fail-on-warnings', $argv, true);

/**
 * Extract an array block starting at a marker (ex: "$i18n = [") by matching brackets.
 */
function extract_array_block(string $content, string $marker): ?string
{
    $start = strpos($content, $marker);
    if ($start === false) {
        return null;
    }

    $openPos = strpos($content, '[', $start);
    if ($openPos === false) {
        return null;
    }

    $depth = 0;
    $len = strlen($content);
    for ($i = $openPos; $i < $len; $i++) {
        $ch = $content[$i];
        if ($ch === '[') {
            $depth++;
        } elseif ($ch === ']') {
            $depth--;
            if ($depth === 0) {
                return substr($content, $start, ($i - $start) + 1);
            }
        }
    }

    return substr($content, $start);
}

foreach ($files as $file) {
    $content = (string) file_get_contents($file);
    if (!str_contains($content, '$i18n = [')) {
        continue;
    }

    $slice = extract_array_block($content, '$i18n = [');
    if ($slice === null) {
        $issues[] = [basename($file), ['unreadable_i18n_block']];
        continue;
    }

    $missing = [];
    foreach ($required as $lang) {
        if (!str_contains($slice, $lang . ' =>')) {
            $missing[] = trim($lang, "'");
        }
    }
    if ($missing !== [] && !str_contains($content, 'i18n_expand_supported_locales($i18n)')) {
        $issues[] = [basename($file), $missing];
    }
}

/**
 * @return array<string, array<string, string>>
 */
function extract_i18n_top_locales(string $slice, array $locales): array
{
    $result = [];
    $localePattern = implode('|', array_map(static fn (string $locale): string => preg_quote($locale, '/'), $locales));
    if (!preg_match_all("/'(?P<locale>" . $localePattern . ")'\\s*=>\\s*\\[(?P<body>.*?)\\](?:\\s*,|\\s*$)/s", $slice, $matches, PREG_SET_ORDER)) {
        return $result;
    }
    foreach ($matches as $match) {
        $locale = (string) ($match['locale'] ?? '');
        $body = (string) ($match['body'] ?? '');
        $pairs = [];
        if (preg_match_all("/'(?P<key>[a-zA-Z0-9_]+)'\\s*=>\\s*'(?P<value>(?:\\\\'|[^'])*)'/s", $body, $kvMatches, PREG_SET_ORDER)) {
            foreach ($kvMatches as $kv) {
                $pairs[(string) $kv['key']] = str_replace("\\'", "'", (string) $kv['value']);
            }
        }
        $result[$locale] = $pairs;
    }
    return $result;
}

function looks_french_sentence(string $value): bool
{
    if (preg_match('/[éèêàçùôîï]/iu', $value) === 1) {
        return true;
    }
    return preg_match('/\\b(le|la|les|des|une|vous|avec|pour|dans|aucun|actuellement)\\b/iu', $value) === 1;
}

foreach ($files as $file) {
    $content = (string) file_get_contents($file);
    if (!str_contains($content, '$i18n = [')) {
        continue;
    }
    $slice = extract_array_block($content, '$i18n = [');
    if ($slice === null) {
        continue;
    }
    $locales = extract_i18n_top_locales($slice, $requiredLocales);
    if (!isset($locales['fr'])) {
        continue;
    }
    foreach ($requiredLocales as $locale) {
        if ($locale === 'fr') {
            continue;
        }
        if (!isset($locales[$locale])) {
            continue;
        }
        foreach ($locales['fr'] as $key => $frValue) {
            $otherValue = $locales[$locale][$key] ?? null;
            if (!is_string($otherValue) || $otherValue === '') {
                continue;
            }
            if ($otherValue === $frValue && looks_french_sentence($frValue)) {
                $warnings[] = basename($file) . ':' . $locale . ':' . $key;
            }
        }
    }
}

/**
 * @return array<string,string>
 */
function extract_locale_blocks(string $content, array $locales): array
{
    $blocks = [];
    foreach ($locales as $idx => $locale) {
        $marker = "'" . $locale . "' => [";
        $start = strpos($content, $marker);
        if ($start === false) {
            continue;
        }

        $nextStart = null;
        for ($j = $idx + 1; $j < count($locales); $j++) {
            $candidate = strpos($content, "'" . $locales[$j] . "' => [", $start + 1);
            if ($candidate !== false) {
                $nextStart = $candidate;
                break;
            }
        }

        $blocks[$locale] = $nextStart !== null ? substr($content, $start, $nextStart - $start) : substr($content, $start);
    }

    return $blocks;
}

$homeFile = $root . '/pages/home.php';
if (is_file($homeFile)) {
    $homeContent = (string) file_get_contents($homeFile);
    $localeBlocks = extract_locale_blocks($homeContent, $homeRequiredLocales);
    $homeRequiredKeys = [
        'member_modules_title',
        'member_modules_empty',
        'member_audience',
        'page_title',
        'club_name',
        'venue_line_1',
        'venue_line_2',
        'venue_line_3',
        'alt_partner_ad',
        'alt_hero_illustration',
        'alt_uba_logo',
        'alt_repeater_logo',
    ];

    foreach ($homeRequiredLocales as $locale) {
        if (!isset($localeBlocks[$locale])) {
            $issues[] = ['home.php', ['missing_locale_block_' . $locale]];
            continue;
        }

        $missingKeys = [];
        foreach ($homeRequiredKeys as $key) {
            if (!str_contains($localeBlocks[$locale], "'" . $key . "' =>")) {
                $missingKeys[] = $locale . ':' . $key;
            }
        }

        if ($missingKeys !== []) {
            $issues[] = ['home.php', $missingKeys];
        }
    }
}

if ($issues === []) {
    echo "OK: all page-level i18n dictionaries expose " . implode('/', $requiredLocales) . "\n";
    if ($warnings !== []) {
        echo "WARN: potential French string leakage in non-fr locales -> " . implode(',', $warnings) . "\n";
        if ($strictWarnings) {
            exit(2);
        }
    }
    if ($strictWarnings) {
        echo "STRICT MODE: enabled (--strict-warnings)\n";
    }
    exit(0);
}

foreach ($issues as [$file, $missing]) {
    echo $file . ': missing locales -> ' . implode(',', $missing) . "\n";
}
exit(1);
