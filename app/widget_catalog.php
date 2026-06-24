<?php
declare(strict_types=1);

require_once __DIR__ . '/hamqsl_widgets.php';
require_once __DIR__ . '/widget_i18n.php';

if (!function_exists('widget_catalog')) {
function widget_catalog(): array
{
    return [
        'welcome' => [
            'title' => dashboard_widget_text('widget_welcome_title'),
            'description' => dashboard_widget_text('widget_welcome_description'),
        ],
        'open_meteo' => [
            'title' => dashboard_widget_text('widget_open_meteo_title'),
            'description' => dashboard_widget_text('widget_open_meteo_description'),
        ],
        'radio_clocks' => [
            'title' => dashboard_widget_text('widget_radio_clocks_title'),
            'description' => dashboard_widget_text('widget_radio_clocks_description'),
        ],
        'ham_weather_advice' => [
            'title' => dashboard_widget_text('widget_ham_weather_advice_title'),
            'description' => dashboard_widget_text('widget_ham_weather_advice_description'),
        ],
    ] + hamqsl_widget_catalog();
}
}


if (!function_exists('enabled_widget_catalog')) {
function enabled_widget_catalog(): array
{
    $catalog = widget_catalog();
    if (!table_exists('dashboard_widget_settings')) {
        return $catalog;
    }

    $rows = db()->query('SELECT widget_key, is_enabled FROM dashboard_widget_settings');
    $settings = $rows !== false ? ($rows->fetchAll() ?: []) : [];
    $enabledMap = [];
    foreach ($settings as $row) {
        $key = (string) ($row['widget_key'] ?? '');
        if ($key === '') {
            continue;
        }
        $enabledMap[$key] = (int) ($row['is_enabled'] ?? 0) === 1;
    }

    $filtered = [];
    foreach ($catalog as $key => $meta) {
        if (($enabledMap[$key] ?? true) === true) {
            $filtered[$key] = $meta;
        }
    }

    return $filtered;
}
}
