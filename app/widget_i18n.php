<?php
declare(strict_types=1);

if (!function_exists('dashboard_widget_i18n_messages')) {
/**
 * @return array<string, string>
 */
function dashboard_widget_i18n_messages(?string $locale = null): array
{
    return i18n_domain_locale('dashboard', $locale ?? current_locale());
}
}

if (!function_exists('dashboard_widget_text')) {
function dashboard_widget_text(string $key, ?string $locale = null, string $fallback = ''): string
{
    $messages = dashboard_widget_i18n_messages($locale);
    $value = trim((string) ($messages[$key] ?? ''));

    return $value !== '' ? $value : ($fallback !== '' ? $fallback : $key);
}
}
