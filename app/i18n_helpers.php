<?php
declare(strict_types=1);

if (!function_exists('supported_locales')) {
function supported_locales(): array
{
    static $locales = ['fr', 'en', 'de', 'nl', 'it', 'es', 'pt', 'bg', 'hr', 'cs', 'da', 'et', 'fi', 'el', 'hu', 'ga', 'lv', 'lt', 'mt', 'pl', 'ro', 'sk', 'sl', 'sv', 'ar', 'hi', 'ja', 'zh', 'bn', 'ru', 'id'];
    return $locales;
}
}

if (!function_exists('supported_locales_map')) {
function supported_locales_map(): array
{
    static $map = null;
    if (is_array($map)) {
        return $map;
    }

    $map = array_fill_keys(supported_locales(), true);
    return $map;
}
}

if (!function_exists('is_rtl_locale')) {
function is_rtl_locale(string $locale): bool
{
    return in_array(strtolower(trim($locale)), ['ar'], true);
}
}

if (!function_exists('locale_fallback_chain')) {
function locale_fallback_chain(?string $locale = null): array
{
    $supported = supported_locales_map();
    $requested = strtolower(trim((string) ($locale ?? current_locale())));
    $requested = str_replace('_', '-', $requested);
    $normalized = $requested;
    if (str_contains($requested, '-')) {
        $normalized = (string) explode('-', $requested, 2)[0];
    }

    $chain = [];
    foreach ([$requested, $normalized, 'en', 'fr'] as $candidate) {
        if ($candidate === '' || in_array($candidate, $chain, true) || !isset($supported[$candidate])) {
            continue;
        }
        $chain[] = $candidate;
    }

    return $chain === [] ? ['fr'] : $chain;
}
}

if (!function_exists('i18n_localized_value')) {
function i18n_localized_value(array $localized, ?string $locale = null, string $default = 'fr'): string
{
    foreach (locale_fallback_chain($locale) as $candidateLocale) {
        if (!isset($localized[$candidateLocale])) {
            continue;
        }

        if (is_array($localized[$candidateLocale])) {
            $nestedValue = $localized[$candidateLocale][$default] ?? null;
            if (is_string($nestedValue)) {
                $nestedValue = trim($nestedValue);
                if ($nestedValue !== '') {
                    return $nestedValue;
                }
            }
            continue;
        }

        if (!is_string($localized[$candidateLocale])) {
            continue;
        }
        $value = trim($localized[$candidateLocale]);
        if ($value !== '') {
            return $value;
        }
    }

    if (isset($localized[$default]) && is_string($localized[$default])) {
        $value = trim($localized[$default]);
        if ($value !== '') {
            return $value;
        }
    }

    foreach ($localized as $value) {
        if (!is_string($value)) {
            continue;
        }

        $value = trim($value);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}
}


if (!function_exists('i18n_expand_supported_locales')) {
/**
 * Ensure a page-level i18n dictionary exposes every configured locale.
 *
 * @param array<string, array<string, string>> $messages
 * @param array<int, string>|null $locales
 * @return array<string, array<string, string>>
 */
function i18n_expand_supported_locales(array $messages, ?array $locales = null, string $primaryFallback = 'en', string $secondaryFallback = 'fr'): array
{
    $locales ??= supported_locales();
    $primary = isset($messages[$primaryFallback]) && is_array($messages[$primaryFallback]) ? $messages[$primaryFallback] : [];
    $secondary = isset($messages[$secondaryFallback]) && is_array($messages[$secondaryFallback]) ? $messages[$secondaryFallback] : [];
    $base = array_replace($secondary, $primary);

    foreach ($locales as $locale) {
        if (!is_string($locale) || $locale === '') {
            continue;
        }
        $current = isset($messages[$locale]) && is_array($messages[$locale]) ? $messages[$locale] : [];
        $messages[$locale] = array_replace($base, $current);
    }

    return $messages;
}
}

if (!function_exists('i18n_domain_messages')) {
/**
 * Load a module i18n catalog from app/i18n/<domain>.php and/or app/i18n/<domain>/<locale>.php.
 *
 * @return array<string, array<string, string>>
 */
function i18n_domain_messages(string $domain): array
{
    static $catalogs = [];

    $domain = preg_replace('/[^a-z0-9_]/', '', strtolower($domain)) ?: '';
    if ($domain === '') {
        return [];
    }

    if (array_key_exists($domain, $catalogs)) {
        return $catalogs[$domain];
    }

    $messages = [];
    $path = __DIR__ . '/i18n/' . $domain . '.php';
    if (is_file($path)) {
        $loadedMessages = require $path;
        if (is_array($loadedMessages)) {
            $messages = $loadedMessages;
        }
    }

    $directory = __DIR__ . '/i18n/' . $domain;
    if (is_dir($directory)) {
        foreach (supported_locales() as $localeCode) {
            $localePath = $directory . '/' . $localeCode . '.php';
            if (!is_file($localePath)) {
                continue;
            }
            $localeMessages = require $localePath;
            if (is_array($localeMessages)) {
                $messages[$localeCode] = array_replace(
                    isset($messages[$localeCode]) && is_array($messages[$localeCode]) ? $messages[$localeCode] : [],
                    $localeMessages
                );
            }
        }
    }

    if ($messages === []) {
        $catalogs[$domain] = [];
        return [];
    }

    $catalogs[$domain] = i18n_expand_supported_locales($messages);
    return $catalogs[$domain];
}
}

if (!function_exists('i18n_domain_locale')) {
/**
 * Return the best localized key/value map for a module catalog.
 *
 * @return array<string, string>
 */
function i18n_domain_locale(string $domain, ?string $locale = null, string $default = 'fr'): array
{
    $messages = i18n_domain_messages($domain);
    if ($messages === []) {
        return [];
    }

    foreach (locale_fallback_chain($locale) as $candidateLocale) {
        if (isset($messages[$candidateLocale]) && is_array($messages[$candidateLocale])) {
            return $messages[$candidateLocale];
        }
    }

    return isset($messages[$default]) && is_array($messages[$default]) ? $messages[$default] : [];
}
}

if (!function_exists('i18n_domain_translator')) {
/**
 * Return a compact translation callback for a module catalog.
 */
function i18n_domain_translator(string $domain, ?string $locale = null, string $default = 'fr'): Closure
{
    $messages = i18n_domain_locale($domain, $locale, $default);

    return static fn(string $key): string => (string) ($messages[$key] ?? $key);
}
}

if (!function_exists('current_locale')) {
function current_locale(): string
{
    $supported = supported_locales_map();
    $queryLocale = strtolower(trim((string) ($_GET['lang'] ?? '')));
    $queryLocale = str_replace('_', '-', $queryLocale);
    if (isset($supported[$queryLocale])) {
        return $queryLocale;
    }
    if (str_contains($queryLocale, '-')) {
        $queryLocaleBase = (string) explode('-', $queryLocale, 2)[0];
        if (isset($supported[$queryLocaleBase])) {
            return $queryLocaleBase;
        }
    }

    $locale = strtolower((string) ($_SESSION['locale'] ?? ''));
    if ($locale === '') {
        $acceptLanguage = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        if ($acceptLanguage !== '') {
            $raw = trim((string) explode(',', $acceptLanguage, 2)[0]);
            $locale = str_replace('_', '-', $raw);
        }
    }
    if (!isset($supported[$locale]) && str_contains($locale, '-')) {
        $locale = (string) explode('-', $locale, 2)[0];
    }
    if (!isset($supported[$locale])) {
        return 'fr';
    }

    return $locale;
}
}

if (!function_exists('t_page')) {
function t_page(string $domain, string $key, ?string $locale = null): string
{
    $fallbackChain = locale_fallback_chain($locale);

    $diskMessages = i18n_domain_messages($domain);
    if ($diskMessages !== []) {
        foreach ($fallbackChain as $candidateLocale) {
            if (isset($diskMessages[$candidateLocale][$key])) {
                return (string) $diskMessages[$candidateLocale][$key];
            }
        }
        if (isset($diskMessages['fr'][$key])) {
            return (string) $diskMessages['fr'][$key];
        }
    }

    return $key;
}
}
