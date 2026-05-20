<?php
declare(strict_types=1);

/** @return list<string> */
function audit_target_locales(): array
{
    return ['en','fr','de','es','it','nl','pt','ru','ar','ja','zh','hi','bn','id'];
}

function extract_i18n_array_body(string $content): ?string
{
    $anchor = strpos($content, '$i18n');
    if ($anchor === false) {
        return null;
    }

    $eq = strpos($content, '=', $anchor);
    if ($eq === false) {
        return null;
    }

    $start = strpos($content, '[', $eq);
    if ($start === false) {
        return null;
    }

    $depth = 0;
    $inSingle = false;
    $inDouble = false;
    $escaped = false;
    $len = strlen($content);

    for ($i = $start; $i < $len; $i++) {
        $ch = $content[$i];

        if ($escaped) {
            $escaped = false;
            continue;
        }

        if ($ch === '\\') {
            $escaped = true;
            continue;
        }

        if (!$inDouble && $ch === "'") {
            $inSingle = !$inSingle;
            continue;
        }

        if (!$inSingle && $ch === '"') {
            $inDouble = !$inDouble;
            continue;
        }

        if ($inSingle || $inDouble) {
            continue;
        }

        if ($ch === '[') {
            $depth++;
        } elseif ($ch === ']') {
            $depth--;
            if ($depth === 0) {
                $body = substr($content, $start + 1, $i - $start - 1);
                return $body === false ? null : $body;
            }
        }
    }

    return null;
}

/**
 * @return array<string, string>
 */
function extract_locale_blocks(string $content): array
{
    $arrayBody = extract_i18n_array_body($content);
    if (!is_string($arrayBody) || $arrayBody === '') {
        return [];
    }

    $blocks = [];
    if (!preg_match_all('/(["\'])([a-z]{2})\1\s*=>\s*\[/u', $arrayBody, $matches, PREG_OFFSET_CAPTURE)) {
        return [];
    }

    foreach ($matches[0] as $idx => $full) {
        $locale = $matches[2][$idx][0];
        $start = $full[1] + strlen($full[0]);

        $depth = 1;
        $inSingle = false;
        $inDouble = false;
        $escaped = false;
        $len = strlen($arrayBody);
        $i = $start;

        while ($i < $len && $depth > 0) {
            $ch = $arrayBody[$i];

            if ($escaped) {
                $escaped = false;
                $i++;
                continue;
            }
            if ($ch === '\\') {
                $escaped = true;
                $i++;
                continue;
            }
            if (!$inDouble && $ch === "'") {
                $inSingle = !$inSingle;
                $i++;
                continue;
            }
            if (!$inSingle && $ch === '"') {
                $inDouble = !$inDouble;
                $i++;
                continue;
            }
            if (!$inSingle && !$inDouble) {
                if ($ch === '[') {
                    $depth++;
                } elseif ($ch === ']') {
                    $depth--;
                }
            }
            $i++;
        }

        if ($depth !== 0) {
            continue;
        }

        $payload = substr($arrayBody, $start, ($i - 1) - $start);
        if ($payload !== false) {
            $blocks[$locale] = normalize_block($payload);
        }
    }

    return $blocks;
}

function normalize_block(string $payload): string
{
    $payload = preg_replace('/\s+/u', ' ', $payload) ?? $payload;
    return trim($payload);
}

/**
 * @param list<string> $locales
 * @return array{pages_with_i18n:int,issues:list<array{0:string,1:string,2:string}>}
 */
function run_i18n_audit(string $pagesDir, array $locales): array
{
    $pages = glob(rtrim($pagesDir, '/') . '/*.php') ?: [];
    sort($pages);
    $total = 0;
    $issues = [];

    foreach ($pages as $page) {
        $raw = file_get_contents($page);
        if (!is_string($raw)) {
            continue;
        }
        $content = $raw;
        if (!str_contains($content, '$i18n')) {
            continue;
        }

        $blocks = extract_locale_blocks($content);
        if ($blocks === []) {
            continue;
        }
        $total++;

        foreach ($locales as $locale) {
            if (!isset($blocks[$locale])) {
                $issues[] = [basename($page), $locale, 'missing_locale_block'];
            }
        }

        if (isset($blocks['en'], $blocks['fr'])) {
            foreach ($locales as $locale) {
                if (!isset($blocks[$locale]) || $locale === 'en' || $locale === 'fr') {
                    continue;
                }
                if ($blocks[$locale] === $blocks['en'] || $blocks[$locale] === $blocks['fr']) {
                    $issues[] = [basename($page), $locale, 'fallback_like_content'];
                }
            }
        }
    }

    return ['pages_with_i18n' => $total, 'issues' => $issues];
}

if (PHP_SAPI === 'cli' && realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $result = run_i18n_audit(__DIR__ . '/../pages', audit_target_locales());
    echo 'pages_with_i18n=' . $result['pages_with_i18n'] . "\n";
    echo 'issues=' . count($result['issues']) . "\n";
    foreach ($result['issues'] as [$page, $locale, $type]) {
        echo $page . "\t" . $locale . "\t" . $type . "\n";
    }
}
