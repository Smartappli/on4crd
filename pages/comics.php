<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('comics', $locale);

$boards = [
    [
        'key' => 'commandments',
        'image' => 'assets/comics/les-10-commandements-radio-amateur.png',
        'title' => (string) $t['board_commandments_title'],
        'text' => (string) $t['board_commandments_text'],
    ],
    [
        'key' => 'first_qso',
        'image' => 'assets/comics/ma-premiere-fois-premier-qso.png',
        'title' => (string) $t['board_first_qso_title'],
        'text' => (string) $t['board_first_qso_text'],
    ],
    [
        'key' => 'ohm',
        'image' => 'assets/comics/decouverte-loi-ohm.png',
        'title' => (string) $t['board_ohm_title'],
        'text' => (string) $t['board_ohm_text'],
    ],
];
$heroImagePath = (string) $boards[0]['image'];

set_page_meta([
    'title' => (string) $t['meta_title'],
    'description' => (string) $t['meta_desc'],
    'schema_type' => 'CollectionPage',
    'image' => asset_url($heroImagePath),
    'image_alt' => (string) $t['hero_image_alt'],
    'tags' => ['comics', 'bd', 'a4', 'radioamateur'],
    'json_ld' => [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => (string) $t['meta_title'],
        'description' => (string) $t['meta_desc'],
        'url' => route_url('comics'),
        'image' => asset_url($heroImagePath),
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
        <div class="comics-hero-strip" aria-label="<?= e((string) $t['hero_image_alt']) ?>">
            <?php foreach ($boards as $board): ?>
                <img src="<?= e(asset_url((string) $board['image'])) ?>" alt="<?= e((string) $board['title']) ?>" loading="eager" decoding="async">
            <?php endforeach; ?>
        </div>
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
        <?php foreach ($boards as $board): ?>
            <article class="comics-card">
                <a class="comics-thumb-link" href="<?= e(asset_url((string) $board['image'])) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e((string) $t['open_board_prefix'] . ' ' . (string) $board['title']) ?>">
                    <img class="comics-thumb-img" src="<?= e(asset_url((string) $board['image'])) ?>" alt="<?= e((string) $board['title']) ?>" loading="lazy" decoding="async">
                </a>
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
