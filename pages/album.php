<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('album', $locale);

if (!table_exists('albums') || !table_exists('album_photos')) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['title']) . '</h1><p>' . e((string) $t['gallery_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

$albumId = (int) ($_GET['id'] ?? 0);
$albumStmt = db()->prepare('SELECT * FROM albums WHERE id = ? AND is_public = 1');
$albumStmt->execute([$albumId]);
$album = $albumStmt->fetch();

if (!$album) {
    http_response_code(404);
    echo render_layout('<div class="card"><p>' . e((string) $t['not_found']) . '</p></div>', (string) $t['title']);
    return;
}

$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'toggle_favorite') {
    $user = require_login();
    verify_csrf();
    $saved = favorite_toggle(
        (int) $user['id'],
        'album',
        (int) $album['id'],
        (string) ($album['title'] ?? ''),
        route_url('album', ['id' => (int) $album['id']])
    );
    notify_member((int) $user['id'], 'favorite', $saved ? 'Favorite added' : 'Favorite removed', (string) ($album['title'] ?? ''), route_url('album', ['id' => (int) $album['id']]));
    set_flash('success', $saved ? 'Album added to favorites.' : 'Album removed from favorites.');
    redirect_url(route_url_clean('album', ['id' => (int) $album['id'], 'p' => max(1, (int) ($_GET['p'] ?? 1))]));
}
$isFavorite = $user !== null ? favorite_is_saved((int) $user['id'], 'album', (int) $album['id']) : false;

$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 24;
$countStmt = db()->prepare('SELECT COUNT(*) FROM album_photos WHERE album_id = ?');
$countStmt->execute([(int) $album['id']]);
$photoTotal = (int) $countStmt->fetchColumn();
$pagination = pagination_state($photoTotal, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];

$photosStmt = db()->prepare('SELECT * FROM album_photos WHERE album_id = ? ORDER BY sort_order ASC, id ASC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
$photosStmt->execute([(int) $album['id']]);
$photos = $photosStmt->fetchAll() ?: [];

$coverStmt = db()->prepare('SELECT file_path FROM album_photos WHERE album_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1');
$coverStmt->execute([(int) $album['id']]);
$coverPath = safe_storage_public_path_or_null((string) ($coverStmt->fetchColumn() ?: ''), ['storage/uploads/albums/']);
$pageMeta = [
    'title' => (string) $album['title'],
    'description' => trim((string) ($album['description'] ?? '')) !== '' ? (string) $album['description'] : (string) $t['meta_desc'],
    'ai_summary' => trim((string) ($album['description'] ?? '')) !== '' ? (string) $album['description'] : (string) $t['meta_desc'],
    'canonical' => route_url_with_locale('album', $locale, ['id' => (int) $album['id']]),
    'schema_type' => 'ImageGallery',
    'keywords' => ['ON4CRD', 'Radio Club Durnal', 'album photo', 'radioamateur', 'activites club'],
    'citation_author' => 'Radio Club Durnal ON4CRD',
];
if ($coverPath !== null) {
    $pageMeta['image'] = base_url($coverPath);
}
$imageItems = [];
foreach (array_slice($photos, 0, 12) as $position => $photo) {
    $filePath = safe_storage_public_path_or_null((string) ($photo['file_path'] ?? ''), ['storage/uploads/albums/']);
    if ($filePath === null) {
        continue;
    }
    $imageItems[] = [
        '@type' => 'ImageObject',
        'position' => $position + 1,
        'url' => base_url($filePath),
        'name' => trim((string) ($photo['title'] ?? '')) !== '' ? (string) $photo['title'] : (string) $album['title'],
        'caption' => trim((string) ($photo['caption'] ?? '')),
    ];
}
$pageMeta['json_ld'] = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'ImageGallery',
        'name' => (string) $album['title'],
        'description' => (string) $pageMeta['description'],
        'url' => (string) $pageMeta['canonical'],
        'numberOfItems' => $photoTotal,
        'image' => $imageItems,
        'inLanguage' => $locale,
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Radio Club Durnal ON4CRD',
            'url' => route_url_with_locale('home', $locale),
        ],
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'ON4CRD',
                'item' => route_url_with_locale('home', $locale),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => (string) $t['title'],
                'item' => route_url_with_locale('albums', $locale),
            ],
            [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => (string) $album['title'],
                'item' => (string) $pageMeta['canonical'],
            ],
        ],
    ],
];
set_page_meta($pageMeta);

ob_start();
?>
<div class="album-detail-page">
    <section class="album-detail-hero">
        <div class="album-detail-cover">
            <?php if ($coverPath !== null): ?>
                <img src="<?= e(base_url($coverPath)) ?>" alt="<?= e((string) $album['title']) ?>" loading="eager" decoding="async" fetchpriority="high">
            <?php else: ?>
                <span class="album-placeholder-mark" aria-hidden="true"></span>
            <?php endif; ?>
        </div>
        <div class="album-detail-copy">
            <p><a href="<?= e(route_url('albums')) ?>"><?= e((string) $t['back']) ?></a></p>
            <h1><?= e((string) $album['title']) ?></h1>
            <?php if (trim((string) ($album['description'] ?? '')) !== ''): ?>
                <p><?= e((string) $album['description']) ?></p>
            <?php endif; ?>
            <div class="album-detail-stats">
                <article>
                    <span><?= e((string) $t['photos']) ?></span>
                    <strong><?= (int) $photoTotal ?></strong>
                </article>
                <article>
                    <span><?= e((string) $t['page']) ?></span>
                    <strong><?= $page ?> / <?= $totalPages ?></strong>
                </article>
            </div>
            <?php if ($user !== null): ?>
                <form method="post" class="album-favorite-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="toggle_favorite">
                    <button class="button secondary" type="submit"><?= $isFavorite ? '&#9733;' : '&#9734;' ?></button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <section class="album-photo-section">
        <div class="row-between">
            <h2><?= e((string) $t['album_photos']) ?></h2>
        </div>
        <?php if ($photos === []): ?>
            <p class="help"><?= e((string) $t['none']) ?></p>
        <?php else: ?>
            <div class="album-photo-grid">
                <?php foreach ($photos as $photo): ?>
                    <?php
                    $filePath = safe_storage_public_path_or_null((string) ($photo['file_path'] ?? ''), ['storage/uploads/albums/']);
                    if ($filePath === null) {
                        continue;
                    }
                    $thumbPath = album_thumbnail_public_path($filePath);
                    $thumbAbs = dirname(__DIR__) . '/' . $thumbPath;
                    $imageSrc = is_file($thumbAbs) ? $thumbPath : $filePath;
                    $title = trim((string) ($photo['title'] ?? ''));
                    $caption = trim((string) ($photo['caption'] ?? ''));
                    ?>
                    <figure class="album-photo-card">
                        <a href="<?= e(base_url($filePath)) ?>" target="_blank" rel="noopener">
                            <img src="<?= e(base_url($imageSrc)) ?>" alt="<?= e($title !== '' ? $title : (string) $t['photo_alt']) ?>" loading="lazy" decoding="async">
                        </a>
                        <?php if ($title !== '' || $caption !== ''): ?>
                            <figcaption>
                                <?php if ($title !== ''): ?><strong><?= e($title) ?></strong><?php endif; ?>
                                <?php if ($caption !== ''): ?><span><?= e($caption) ?></span><?php endif; ?>
                            </figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <nav class="actions mt-3" aria-label="<?= e((string) $t['pagination']) ?>">
                    <?php if ($page > 1): ?>
                        <a class="button secondary" href="<?= e(route_url_clean('album', ['id' => (int) $album['id'], 'p' => $page - 1])) ?>"><?= e((string) $t['previous']) ?></a>
                    <?php endif; ?>
                    <span class="pill"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a class="button secondary" href="<?= e(route_url_clean('album', ['id' => (int) $album['id'], 'p' => $page + 1])) ?>"><?= e((string) $t['next']) ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $album['title']);
