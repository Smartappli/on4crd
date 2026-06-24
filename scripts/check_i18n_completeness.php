<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$i18nRoot = $root . '/app/i18n';

$strict = in_array('--strict', $argv, true);

$allowedExactValues = array_fill_keys([
    'AM',
    'APRS',
    'BIC',
    'Bootstrap',
    'CRD',
    'CSV',
    'CW',
    'CSS',
    'Docker',
    'DMR',
    'EchoLink',
    'Facebook',
    'FM',
    'GDPR',
    'GitHub',
    'Google',
    'GPS',
    'HF',
    'HTML',
    'IBAN',
    'JSON',
    'LoTW',
    'Markdown',
    'ON4CRD',
    'OpenAI',
    'OpenStreetMap',
    'PDF',
    'PHP',
    'QR',
    'QRL',
    'QRM',
    'QRN',
    'QRO',
    'QRP',
    'QRZ',
    'QSL',
    'QSO',
    'RSS',
    'SEPA',
    'SHF',
    'SQL',
    'SSB',
    'UBA',
    'UHF',
    'URL',
    'UTC',
    'VHF',
    'YouTube',
    'eQSL',
    'macOS',
    'Administration',
    'Agenda ON4CRD',
    'Album',
    'Albums',
    'Adresse.',
    'Altitude',
    'Antennes',
    'Aurora',
    'Assistant',
    'Bid',
    'Canal',
    'Clics',
    'CQ zone',
    'Details',
    'Document',
    'Export',
    'Format',
    'Google Map - Radio Club Durnal',
    'Grid',
    'Hz',
    'Illustration ON4CRD',
    'ITU zone',
    'Licence',
    'Liste',
    'Locator',
    'Logo UBA',
    'LoTW + eQSL',
    'Newsletter',
    'ON4CRD Newsletter',
    'ON4CRD Radio Club Durnal',
    'ON4CRD Wiki',
    'Orange',
    'Pause',
    'Parser',
    'Polarisation',
    'Privacy',
    'Profil',
    'Prosigns',
    'Public',
    'QSL via',
    'Radio / RF',
    'Reference',
    'Rotation',
    'Service',
    'Sponsoring',
    'Station',
    'Status',
    'Stats',
    'Sponsoring ON4CRD',
    'Separator',
    'Tiramisu',
    'Type',
    'UBA logo',
    'UBA-logo',
    'Verticale 6,5 dBi',
    'Visual',
    'Vol-au-vent',
    'Website',
    'Week',
    'Wiki',
    'Widgets',
    'lot',
], true);

/**
 * @param array<mixed> $dictionary
 * @return array<string, mixed>
 */
function flatten_i18n_dictionary(array $dictionary, string $prefix = ''): array
{
    $flattened = [];

    foreach ($dictionary as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;

        if (is_array($value)) {
            $flattened += flatten_i18n_dictionary($value, $path);
            continue;
        }

        $flattened[$path] = $value;
    }

    return $flattened;
}

function normalize_i18n_value(string $value): string
{
    $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $normalized = preg_replace('/\s+/u', ' ', trim($decoded));

    return $normalized ?? trim($decoded);
}

function i18n_value_has_letters(string $value): bool
{
    return preg_match('/\p{L}/u', $value) === 1;
}

/**
 * Filters values that are expected to remain stable across locales: placeholders,
 * URLs, radio abbreviations, file formats, callsigns, and similar technical tokens.
 */
function is_i18n_stable_value(string $value, string $key, string $locale, array $allowedExactValues): bool
{
    $value = normalize_i18n_value($value);

    if ($value === '' || !i18n_value_has_letters($value)) {
        return true;
    }

    if (isset($allowedExactValues[$value])) {
        return true;
    }

    if ($value === 'No' && in_array($locale, ['es', 'it'], true)) {
        return true;
    }

    if ($value === 'Contact' && in_array($locale, ['nl', 'ro'], true)) {
        return true;
    }

    if (preg_match('/^https?:\/\//iu', $value) === 1) {
        return true;
    }

    if (preg_match('/^[\w.+-]+@[\w.-]+\.[a-z]{2,}$/iu', $value) === 1) {
        return true;
    }

    if (preg_match('/^%[bcdeEfFgGosuxX]$/u', $value) === 1) {
        return true;
    }

    if (preg_match('/^\{[a-z0-9_]+\}$/iu', $value) === 1) {
        return true;
    }

    if (preg_match('/^(ON|OO|OP|OQ|OR|OS|OT)[0-9][A-Z]{1,4}$/u', $value) === 1) {
        return true;
    }

    if (
        preg_match('/(address|calendar_name|code|contact_item_[0-9]+|filename|geo_placename|ics_|location_value|altitude_value|map_title|path|route|slug|url|venue_)/i', $key) === 1
        && preg_match('/^[\p{L}\p{N}\s.,:;\/+_.()\-]+$/u', $value) === 1
    ) {
        return true;
    }

    if (preg_match('/(cw_qrp_value|dbd|dbi|dbm|dbuv|dbw|frequency|gain|impedance|km_unit|locator|meters_unit|mhz|ohm|power|r1_|r2_|rx_|tx_|watt)/i', $key) === 1) {
        return true;
    }

    if (
        preg_match('/[Ωµ]|(?:^|\b)(?:dBd|dBi|dBm|dBW|dBµV|GHz|Grx|Gtx|kHz|km|MHz|Ptx|R[12])(?:\b|$)/u', $value) === 1
        && preg_match('/(field|gain|label|level|niveau|power|tool|unit)/i', $key) === 1
    ) {
        return true;
    }

    if (preg_match('/^[A-Z0-9][A-Z0-9\s\/+_.:-]{1,30}$/u', $value) === 1) {
        return true;
    }

    if (
        preg_match('/^\p{Lu}[\p{L}\p{N}_.-]{0,12}$/u', $value) === 1
        && preg_match('/(band|bande|bic|call|callsign|code|email|iban|indicatif|logo|mail|mode|phone|qrz|qsl|qso|slug|tel|url|utc)/i', $key) === 1
    ) {
        return true;
    }

    if (
        preg_match('/^[A-Z][a-z]+(?:\s[A-Z][a-z]+){0,3}$/u', $value) === 1
        && preg_match('/(author|auteur|city|club|lieu|name|nom|place|provider|source|ville)/i', $key) === 1
    ) {
        return true;
    }

    return false;
}

$keyIssues = [];
$translationIssues = [];
$moduleDirs = glob($i18nRoot . '/*', GLOB_ONLYDIR) ?: [];
sort($moduleDirs);

foreach ($moduleDirs as $moduleDir) {
    $module = basename($moduleDir);
    $frFile = $moduleDir . '/fr.php';
    $enFile = $moduleDir . '/en.php';

    if (!is_file($frFile) || !is_file($enFile)) {
        continue;
    }

    $fr = require $frFile; // NOSONAR - utility script validates local dictionaries.
    $en = require $enFile; // NOSONAR - utility script validates local dictionaries.

    if (!is_array($fr) || !is_array($en)) {
        $keyIssues[] = $module . ': fr/en dictionaries must return arrays';
        continue;
    }

    $fr = flatten_i18n_dictionary($fr);
    $en = flatten_i18n_dictionary($en);
    $baseKeys = array_values(array_unique(array_merge(array_keys($fr), array_keys($en))));
    sort($baseKeys);

    $localeFiles = glob($moduleDir . '/*.php') ?: [];
    sort($localeFiles);

    foreach ($localeFiles as $localeFile) {
        $locale = basename($localeFile, '.php');
        $dictionary = require $localeFile; // NOSONAR - utility script validates local dictionaries.

        if (!is_array($dictionary)) {
            $keyIssues[] = $module . '/' . $locale . ': dictionary must return an array';
            continue;
        }

        $dictionary = flatten_i18n_dictionary($dictionary);
        $keys = array_keys($dictionary);
        sort($keys);

        $missing = array_values(array_diff($baseKeys, $keys));
        $extra = array_values(array_diff($keys, $baseKeys));

        if ($missing !== [] || $extra !== []) {
            $parts = [];
            if ($missing !== []) {
                $parts[] = 'missing=' . implode(',', $missing);
            }
            if ($extra !== []) {
                $parts[] = 'extra=' . implode(',', $extra);
            }
            $keyIssues[] = $module . '/' . $locale . ': ' . implode(' ', $parts);
        }

        if ($locale === 'fr' || $locale === 'en') {
            continue;
        }

        foreach ($dictionary as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            $normalizedValue = normalize_i18n_value($value);
            if (is_i18n_stable_value($normalizedValue, $key, $locale, $allowedExactValues)) {
                continue;
            }

            $sources = [];
            if (
                isset($en[$key])
                && is_string($en[$key])
                && $normalizedValue === normalize_i18n_value($en[$key])
                && !is_i18n_stable_value($en[$key], $key, $locale, $allowedExactValues)
            ) {
                $sources[] = 'en';
            }

            if (
                isset($fr[$key])
                && is_string($fr[$key])
                && $normalizedValue === normalize_i18n_value($fr[$key])
                && !is_i18n_stable_value($fr[$key], $key, $locale, $allowedExactValues)
            ) {
                $sources[] = 'fr';
            }

            if ($sources !== []) {
                $translationIssues[] = $module . '/' . $locale . ':' . $key . ' copied_from=' . implode('+', $sources) . ' value=' . $normalizedValue;
            }
        }
    }
}

if ($keyIssues === [] && $translationIssues === []) {
    echo "OK: all i18n module dictionaries match fr/en keys and no obvious copied fr/en strings remain.\n";
    exit(0);
}

if ($keyIssues !== []) {
    echo "Key parity issues:\n";
    foreach ($keyIssues as $issue) {
        echo '- ' . $issue . PHP_EOL;
    }
}

if ($translationIssues !== []) {
    if ($keyIssues !== []) {
        echo PHP_EOL;
    }
    echo "Potential untranslated strings:\n";
    foreach ($translationIssues as $issue) {
        echo '- ' . $issue . PHP_EOL;
    }
}

if ($strict) {
    exit(1);
}

echo "\nReport-only mode: exiting with success. Use --strict to return a failing exit code.\n";
exit(0);
