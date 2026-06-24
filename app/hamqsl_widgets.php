<?php
declare(strict_types=1);

require_once __DIR__ . '/widget_i18n.php';

if (!function_exists('hamqsl_widget_variants')) {
function hamqsl_widget_variants(): array
{
    return [
        'hamqsl_hf_vhf' => [
            'title' => dashboard_widget_text('widget_hamqsl_hf_vhf_title'),
            'description' => dashboard_widget_text('widget_hamqsl_hf_vhf_description'),
            'image' => 'solarn0nbh.php',
            'alt' => dashboard_widget_text('widget_hamqsl_hf_vhf_alt'),
            'layout' => 'wide',
        ],
        'hamqsl_solar_image' => [
            'title' => dashboard_widget_text('widget_hamqsl_solar_image_title'),
            'description' => dashboard_widget_text('widget_hamqsl_solar_image_description'),
            'image' => 'solarpic.php',
            'alt' => dashboard_widget_text('widget_hamqsl_solar_image_alt'),
            'layout' => 'tall',
        ],
        'hamqsl_band_conditions' => [
            'title' => dashboard_widget_text('widget_hamqsl_band_conditions_title'),
            'description' => dashboard_widget_text('widget_hamqsl_band_conditions_description'),
            'image' => 'solarbc.php',
            'alt' => dashboard_widget_text('widget_hamqsl_band_conditions_alt'),
            'layout' => 'compact',
        ],
        'hamqsl_muf_map' => [
            'title' => dashboard_widget_text('widget_hamqsl_muf_map_title'),
            'description' => dashboard_widget_text('widget_hamqsl_muf_map_description'),
            'image' => 'solarmuf.php',
            'alt' => dashboard_widget_text('widget_hamqsl_muf_map_alt'),
            'layout' => 'wide',
        ],
        'hamqsl_solar_globe' => [
            'title' => dashboard_widget_text('widget_hamqsl_solar_globe_title'),
            'description' => dashboard_widget_text('widget_hamqsl_solar_globe_description'),
            'image' => 'solarglobe.php',
            'alt' => dashboard_widget_text('widget_hamqsl_solar_globe_alt'),
            'layout' => 'wide',
        ],
        'hamqsl_moon_globe' => [
            'title' => dashboard_widget_text('widget_hamqsl_moon_globe_title'),
            'description' => dashboard_widget_text('widget_hamqsl_moon_globe_description'),
            'image' => 'moonglobe.php',
            'alt' => dashboard_widget_text('widget_hamqsl_moon_globe_alt'),
            'layout' => 'wide',
        ],
        'hamqsl_solar_system' => [
            'title' => dashboard_widget_text('widget_hamqsl_solar_system_title'),
            'description' => dashboard_widget_text('widget_hamqsl_solar_system_description'),
            'image' => 'solarsystem.php',
            'alt' => dashboard_widget_text('widget_hamqsl_solar_system_alt'),
            'layout' => 'wide',
        ],
    ];
}

function hamqsl_widget_catalog(): array
{
    $catalog = [];
    foreach (hamqsl_widget_variants() as $key => $variant) {
        $catalog[$key] = [
            'title' => (string) ($variant['title'] ?? $key),
            'description' => (string) ($variant['description'] ?? ''),
        ];
    }

    return $catalog;
}

function hamqsl_widget_variant(string $slug): ?array
{
    $key = strtolower(trim($slug));
    $variants = hamqsl_widget_variants();

    return is_array($variants[$key] ?? null) ? $variants[$key] : null;
}

function hamqsl_widget_source_url(): string
{
    return 'https://www.hamqsl.com/solar.html';
}

function hamqsl_widget_image_url(string $image): string
{
    $image = strtolower(trim($image));
    if (preg_match('/^[a-z0-9]+\.php$/', $image) !== 1) {
        throw new RuntimeException(dashboard_widget_text('widget_hamqsl_image_invalid'));
    }

    return 'https://www.hamqsl.com/' . $image;
}

function render_hamqsl_widget(string $slug): string
{
    $variant = hamqsl_widget_variant($slug);
    if ($variant === null) {
        return '<p class="help">' . e(dashboard_widget_text('widget_hamqsl_unavailable')) . '</p>';
    }

    $layout = preg_replace('/[^a-z0-9_-]/', '', (string) ($variant['layout'] ?? 'standard')) ?: 'standard';
    $title = (string) ($variant['title'] ?? 'HAMQSL');
    $description = (string) ($variant['description'] ?? '');
    $imageUrl = hamqsl_widget_image_url((string) ($variant['image'] ?? ''));
    $sourceUrl = hamqsl_widget_source_url();
    $alt = (string) ($variant['alt'] ?? $title);

    return '<figure class="hamqsl-widget hamqsl-widget--' . e($layout) . '" data-widget-refresh="manual" data-provider="hamqsl">'
        . '<a href="' . e($sourceUrl) . '" title="' . e(dashboard_widget_text('widget_hamqsl_source_title')) . '" target="_blank" rel="noopener noreferrer">'
        . '<img src="' . e($imageUrl) . '" alt="' . e($alt) . '" loading="lazy" decoding="async" referrerpolicy="no-referrer">'
        . '</a>'
        . '<figcaption class="help">' . e($description) . ' ' . e(dashboard_widget_text('widget_hamqsl_source_caption')) . '</figcaption>'
        . '</figure>';
}
}
