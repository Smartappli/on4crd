<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('comics', $locale);

$boards = [
    ['key' => 'field', 'title' => (string) $t['board_field_title'], 'text' => (string) $t['board_field_text']],
    ['key' => 'station', 'title' => (string) $t['board_station_title'], 'text' => (string) $t['board_station_text']],
    ['key' => 'event', 'title' => (string) $t['board_event_title'], 'text' => (string) $t['board_event_text']],
    ['key' => 'night', 'title' => (string) $t['board_night_title'], 'text' => (string) $t['board_night_text']],
    ['key' => 'map', 'title' => (string) $t['board_map_title'], 'text' => (string) $t['board_map_text']],
    ['key' => 'antenna', 'title' => (string) $t['board_antenna_title'], 'text' => (string) $t['board_antenna_text']],
];

set_page_meta([
    'title' => (string) $t['meta_title'],
    'description' => (string) $t['meta_desc'],
    'schema_type' => 'CollectionPage',
    'image' => asset_url('assets/img/comics-a4-thumbnails.png'),
    'image_alt' => (string) $t['hero_image_alt'],
    'tags' => ['comics', 'bd', 'a4', 'radioamateur'],
    'json_ld' => [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => (string) $t['meta_title'],
        'description' => (string) $t['meta_desc'],
        'url' => route_url('comics'),
        'image' => asset_url('assets/img/comics-a4-thumbnails.png'),
        'mainEntity' => [
            '@type' => 'ItemList',
            'itemListElement' => array_map(
                static fn(array $board, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => (string) $board['title'],
                    'description' => (string) $board['text'],
                ],
                $boards,
                array_keys($boards)
            ),
        ],
    ],
]);

ob_start();
?>
<section class="comics-hero" aria-labelledby="comics-title">
    <div class="comics-hero-copy">
        <p class="eyebrow"><?= e((string) $t['eyebrow']) ?></p>
        <h1 id="comics-title"><?= e((string) $t['title']) ?></h1>
        <p class="comics-lead"><?= e((string) $t['lead']) ?></p>
    </div>
    <figure class="comics-hero-media">
        <img src="<?= e(asset_url('assets/img/comics-a4-thumbnails.png')) ?>" alt="<?= e((string) $t['hero_image_alt']) ?>" loading="eager" decoding="async">
    </figure>
</section>

<section class="comics-gallery" aria-labelledby="comics-gallery-title">
    <div class="section-header">
        <div>
            <p class="eyebrow"><?= e((string) $t['gallery_eyebrow']) ?></p>
            <h2 id="comics-gallery-title"><?= e((string) $t['gallery_title']) ?></h2>
        </div>
        <span class="comics-format"><?= e((string) $t['format_label']) ?></span>
    </div>
    <div class="comics-grid">
        <?php foreach ($boards as $index => $board): ?>
            <article class="comics-card">
                <span class="comics-thumb comics-thumb-<?= e((string) ($index + 1)) ?>" role="img" aria-label="<?= e((string) $board['title']) ?>"></span>
                <div class="comics-card-copy">
                    <h3><?= e((string) $board['title']) ?></h3>
                    <p><?= e((string) $board['text']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
