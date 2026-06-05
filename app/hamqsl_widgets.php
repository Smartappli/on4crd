<?php
declare(strict_types=1);

if (!function_exists('hamqsl_widget_variants')) {
function hamqsl_widget_variants(): array
{
    return [
        'hamqsl_hf_vhf' => [
            'title' => 'HAMQSL - Propagation HF/VHF',
            'description' => 'Resume visuel des conditions HF/VHF, MUF et EME.',
            'image' => 'solarn0nbh.php',
            'alt' => 'Donnees solaires et conditions HF VHF HAMQSL',
            'layout' => 'wide',
        ],
        'hamqsl_solar_image' => [
            'title' => 'HAMQSL - Image solaire',
            'description' => 'Image solaire et indices principaux dans une vue verticale.',
            'image' => 'solarpic.php',
            'alt' => 'Image solaire et indices HAMQSL',
            'layout' => 'tall',
        ],
        'hamqsl_band_conditions' => [
            'title' => 'HAMQSL - Bandes HF',
            'description' => 'Conditions de bandes HF sous forme compacte.',
            'image' => 'solarbc.php',
            'alt' => 'Conditions de bandes HF HAMQSL',
            'layout' => 'compact',
        ],
        'hamqsl_muf_map' => [
            'title' => 'HAMQSL - Carte MUF',
            'description' => 'Carte mondiale avec flux solaire, taches solaires et lectures MUF.',
            'image' => 'solarmuf.php',
            'alt' => 'Carte MUF et ensoleillement mondial HAMQSL',
            'layout' => 'wide',
        ],
        'hamqsl_solar_globe' => [
            'title' => 'HAMQSL - Globe solaire',
            'description' => 'Globe d ensoleillement mondial pour la propagation.',
            'image' => 'solarglobe.php',
            'alt' => 'Globe solaire HAMQSL',
            'layout' => 'wide',
        ],
        'hamqsl_moon_globe' => [
            'title' => 'HAMQSL - Globe lunaire',
            'description' => 'Vue lunaire du globe et degradation EME.',
            'image' => 'moonglobe.php',
            'alt' => 'Globe lunaire et degradation EME HAMQSL',
            'layout' => 'wide',
        ],
        'hamqsl_solar_system' => [
            'title' => 'HAMQSL - Systeme solaire',
            'description' => 'Position courante des planetes du systeme solaire.',
            'image' => 'solarsystem.php',
            'alt' => 'Vue du systeme solaire HAMQSL',
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
        throw new RuntimeException('Image HAMQSL non autorisee.');
    }

    return 'https://www.hamqsl.com/' . $image;
}

function render_hamqsl_widget(string $slug): string
{
    $variant = hamqsl_widget_variant($slug);
    if ($variant === null) {
        return '<p class="help">Widget HAMQSL indisponible.</p>';
    }

    $layout = preg_replace('/[^a-z0-9_-]/', '', (string) ($variant['layout'] ?? 'standard')) ?: 'standard';
    $title = (string) ($variant['title'] ?? 'HAMQSL');
    $description = (string) ($variant['description'] ?? '');
    $imageUrl = hamqsl_widget_image_url((string) ($variant['image'] ?? ''));
    $sourceUrl = hamqsl_widget_source_url();
    $alt = (string) ($variant['alt'] ?? $title);

    return '<figure class="hamqsl-widget hamqsl-widget--' . e($layout) . '" data-widget-refresh="manual" data-provider="hamqsl">'
        . '<a href="' . e($sourceUrl) . '" title="Codes et credits HAMQSL" target="_blank" rel="noopener noreferrer">'
        . '<img src="' . e($imageUrl) . '" alt="' . e($alt) . '" loading="lazy" decoding="async" referrerpolicy="no-referrer">'
        . '</a>'
        . '<figcaption class="help">' . e($description) . ' Source : HAMQSL / N0NBH.</figcaption>'
        . '</figure>';
}
}
