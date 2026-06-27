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
$comicsUrl = route_url('comics');
$homeUrl = route_url('home');
$keywords = array_values(array_filter(array_map('trim', explode(',', (string) $t['keywords']))));
$boardListItems = array_map(
    static fn(array $board, int $index): array => [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => asset_url((string) $board['image']),
        'name' => (string) $board['title'],
        'description' => (string) $board['text'],
        'item' => [
            '@type' => 'CreativeWork',
            'name' => (string) $board['title'],
            'description' => (string) $board['text'],
            'url' => asset_url((string) $board['image']),
            'image' => asset_url((string) $board['image']),
            'encodingFormat' => 'image/png',
            'inLanguage' => $locale,
            'isPartOf' => ['@id' => $comicsUrl . '#collection'],
            'creator' => [
                '@type' => 'Organization',
                'name' => 'Radio Club Durnal ON4CRD',
                'url' => $homeUrl,
            ],
            'about' => [
                ['@type' => 'Thing', 'name' => 'radioamateurisme'],
                ['@type' => 'Thing', 'name' => 'amateur radio education'],
            ],
        ],
    ],
    $boards,
    array_keys($boards)
);

set_page_meta([
    'title' => (string) $t['meta_title'],
    'description' => (string) $t['meta_desc'],
    'schema_type' => 'CollectionPage',
    'content_type' => 'public_comics_collection',
    'ai_summary' => (string) $t['ai_summary'],
    'image' => asset_url($heroImagePath),
    'image_alt' => (string) $t['hero_image_alt'],
    'keywords' => $keywords,
    'tags' => array_merge(['comics', 'bd', 'a4', 'radioamateur'], $keywords),
    'section' => 'Comics',
    'citation_author' => 'Radio Club Durnal ON4CRD',
    'json_ld' => [
        [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => $comicsUrl . '#webpage',
            'name' => (string) $t['meta_title'],
            'description' => (string) $t['meta_desc'],
            'url' => $comicsUrl,
            'inLanguage' => $locale,
            'keywords' => $keywords,
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => $homeUrl . '#website',
                'name' => 'ON4CRD.be',
                'url' => $homeUrl,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Radio Club Durnal ON4CRD',
                'url' => $homeUrl,
            ],
            'about' => [
                ['@type' => 'Thing', 'name' => 'radioamateurisme'],
                ['@type' => 'Thing', 'name' => 'bande dessinee radioamateur'],
                ['@type' => 'Thing', 'name' => 'education radioamateur'],
            ],
            'primaryImageOfPage' => [
                '@type' => 'ImageObject',
                'url' => asset_url($heroImagePath),
                'caption' => (string) $t['board_commandments_title'],
            ],
            'mainEntity' => [
                '@type' => 'ItemList',
                '@id' => $comicsUrl . '#collection',
                'numberOfItems' => count($boards),
                'itemListElement' => $boardListItems,
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'ON4CRD.be',
                    'item' => $homeUrl,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => (string) $t['layout'],
                    'item' => $comicsUrl,
                ],
            ],
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
echo render_layout((string) ob_get_clean(), (string) $t['meta_title']);
