<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('comics', $locale);
$collection = comics_public_collection($locale);
$boards = $collection['boards'];
$comicsUrl = route_url('comics');
$homeUrl = route_url('home');
$organizationId = $homeUrl . '#organization';
$keywords = $collection['keywords'];
$boardListItems = array_map(
    static fn(array $board, int $index): array => [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => (string) $board['url'],
        'name' => (string) $board['title'],
        'description' => (string) $board['text'],
        'item' => comics_public_creative_work($board, $locale, $comicsUrl . '#collection', $organizationId),
    ],
    $boards,
    array_keys($boards)
);

set_page_meta([
    'title' => (string) $collection['title'],
    'description' => (string) $collection['description'],
    'schema_type' => 'CollectionPage',
    'content_type' => 'public_comics_collection',
    'ai_summary' => (string) $collection['summary'],
    'image' => (string) $boards[0]['url'],
    'image_alt' => (string) $t['hero_image_alt'],
    'keywords' => $keywords,
    'tags' => array_merge(['comics', 'bd', 'a4', 'radioamateur'], $keywords),
    'section' => (string) $collection['layout'],
    'citation_author' => 'Radio Club Durnal ON4CRD',
    'json_ld' => [
        [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => $comicsUrl . '#webpage',
            'name' => (string) $collection['title'],
            'description' => (string) $collection['description'],
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
                '@id' => $organizationId,
                'name' => 'Radio Club Durnal ON4CRD',
                'url' => $homeUrl,
            ],
            'about' => [
                ['@type' => 'Thing', 'name' => 'radioamateurisme'],
                ['@type' => 'Thing', 'name' => 'bande dessinée radioamateur'],
                ['@type' => 'Thing', 'name' => 'éducation radioamateur'],
            ],
            'primaryImageOfPage' => comics_public_image_object($boards[0]),
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
                <img src="<?= e((string) $board['thumbnail_url']) ?>" width="<?= (int) $board['thumbnail_width'] ?>" height="<?= (int) $board['thumbnail_height'] ?>" alt="<?= e((string) $board['title']) ?>" loading="eager" decoding="async">
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
            <?php
            $openLabel = (string) $t['open_board_prefix'] . ' ' . (string) $board['title'];
            $downloadLabel = (string) $t['download_board_prefix'] . ' ' . (string) $board['title'];
            $downloadName = basename((string) $board['image']);
            ?>
            <article class="comics-card">
                <a class="comics-thumb-link"
                   href="<?= e((string) $board['url']) ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   data-comics-viewer-open
                   data-comics-title="<?= e((string) $board['title']) ?>"
                   data-comics-text="<?= e((string) $board['text']) ?>"
                   data-comics-alt="<?= e((string) $board['title']) ?>"
                   data-comics-download="<?= e($downloadName) ?>"
                   data-comics-download-label="<?= e($downloadLabel) ?>"
                   aria-label="<?= e($openLabel) ?>">
                    <img class="comics-thumb-img" src="<?= e((string) $board['thumbnail_url']) ?>" width="<?= (int) $board['thumbnail_width'] ?>" height="<?= (int) $board['thumbnail_height'] ?>" alt="<?= e((string) $board['title']) ?>" loading="lazy" decoding="async">
                </a>
                <div class="comics-card-copy">
                    <h3><?= e((string) $board['title']) ?></h3>
                    <p><?= e((string) $board['text']) ?></p>
                    <div class="comics-card-actions">
                        <a class="comics-card-action" href="<?= e((string) $board['url']) ?>" target="_blank" rel="noopener noreferrer" download="<?= e($downloadName) ?>" aria-label="<?= e($downloadLabel) ?>"><?= e((string) $t['download_board_label']) ?></a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<dialog class="comics-viewer" data-comics-viewer aria-labelledby="comics-viewer-title" aria-describedby="comics-viewer-description">
    <div class="comics-viewer-panel">
        <header class="comics-viewer-bar">
            <div>
                <h2 id="comics-viewer-title" data-comics-viewer-title></h2>
                <p id="comics-viewer-description" data-comics-viewer-description></p>
            </div>
            <div class="comics-viewer-actions">
                <a class="comics-viewer-download" data-comics-viewer-download href="#" target="_blank" rel="noopener noreferrer"><?= e((string) $t['download_board_label']) ?></a>
                <button class="comics-viewer-close" type="button" data-comics-viewer-close aria-label="<?= e((string) $t['viewer_close']) ?>">&times;</button>
            </div>
        </header>
        <div class="comics-viewer-canvas">
            <img data-comics-viewer-image alt="">
        </div>
    </div>
</dialog>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['meta_title']);
