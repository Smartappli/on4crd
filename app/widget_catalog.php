<?php
declare(strict_types=1);

require_once __DIR__ . '/hamqsl_widgets.php';

if (!function_exists('widget_catalog')) {
function widget_catalog(): array
{
    return [
        'welcome' => [
            'title' => 'Bienvenue',
            'description' => 'Message d’accueil du tableau de bord membre.',
        ],
        'open_meteo' => [
            'title' => 'Météo locale',
            'description' => 'Conditions locales via Agromet avec secours Open-Meteo.',
        ],
        'radio_clocks' => [
            'title' => 'Horloges radio',
            'description' => 'Deux horloges synchronisées pour la date et heure UTC et locale.',
        ],
        'ham_weather_advice' => [
            'title' => 'Météo radioamateur',
            'description' => 'Score QSO, bandes, modes et créneau conseillé depuis les données météo et propagation.',
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
