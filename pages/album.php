<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('album', $locale);
$viewerLabels = i18n_domain_locale('idea', $locale);
$viewerCloseLabel = trim((string) ($viewerLabels['close'] ?? ''));
if ($viewerCloseLabel === '') {
    $viewerCloseLabel = $locale === 'fr' ? 'Fermer' : 'Close';
}

if (!table_exists('albums') || !table_exists('album_photos')) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['title']) . '</h1><p>' . e((string) $t['gallery_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}
if (!album_ensure_photo_sort_order_column() || !album_ensure_source_proposal_column()) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['title']) . '</h1><p>' . e((string) $t['gallery_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

$albumId = (int) ($_GET['id'] ?? 0);
$user = current_user();
$albumStmt = db()->prepare('SELECT * FROM albums WHERE id = ? LIMIT 1');
$albumStmt->execute([$albumId]);
$album = $albumStmt->fetch();
$canPreviewPrivateAlbum = is_array($album)
    && $user !== null
    && (
        has_permission('albums.manage')
        || (int) ($album['member_id'] ?? 0) === (int) ($user['id'] ?? 0)
    );

if (!$album || ((int) ($album['is_public'] ?? 0) !== 1 && !$canPreviewPrivateAlbum)) {
    http_response_code(404);
    echo render_layout('<div class="card"><p>' . e((string) $t['not_found']) . '</p></div>', (string) $t['title']);
    return;
}

$albumTitle = trim((string) ($album['title'] ?? ''));
if ($albumTitle === '') {
    $albumTitle = (string) $t['title'];
}
$albumDescription = trim((string) ($album['description'] ?? ''));
$albumDescriptionText = $albumDescription;
if ($albumDescriptionText !== '') {
    $albumDescriptionText = (string) preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $albumDescriptionText);
    $albumDescriptionText = (string) preg_replace('/<\s*\/\s*(p|div|li|h[1-6])\s*>/i', "\n", $albumDescriptionText);
    $albumDescriptionText = html_entity_decode(strip_tags($albumDescriptionText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $albumDescriptionText = str_replace(["\r\n", "\r"], "\n", $albumDescriptionText);
    $albumDescriptionText = (string) preg_replace('/[ \t]+/', ' ', $albumDescriptionText);
    $albumDescriptionText = (string) preg_replace('/\n{3,}/', "\n\n", $albumDescriptionText);
    $albumDescriptionText = trim($albumDescriptionText);
}

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
$photoTotal = 0;
$totalPages = 1;
$offset = 0;
$photos = [];
$coverPath = null;
$coverDisplayPath = null;
try {
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
    $coverPath = album_photo_public_path_or_null((string) ($coverStmt->fetchColumn() ?: ''));
    if ($coverPath !== null) {
        $coverThumbPath = album_thumbnail_public_path($coverPath);
        $coverThumbAbs = dirname(__DIR__) . '/' . $coverThumbPath;
        $coverDisplayPath = $coverThumbPath !== '' && is_file($coverThumbAbs) ? $coverThumbPath : $coverPath;
    }
} catch (Throwable $throwable) {
    log_structured_event('album_detail_photos_prepare_failed', [
        'album_id' => (int) $album['id'],
        'message' => $throwable->getMessage(),
    ]);
}
$pageMeta = [
    'title' => $albumTitle,
    'description' => $albumDescriptionText !== '' ? $albumDescriptionText : (string) $t['meta_desc'],
    'ai_summary' => $albumDescriptionText !== '' ? $albumDescriptionText : (string) $t['meta_desc'],
    'canonical' => route_url_with_locale('album', $locale, ['id' => (int) $album['id']]),
    'schema_type' => 'ImageGallery',
    'keywords' => ['ON4CRD', 'Radio Club Durnal', 'album photo', 'radioamateur', 'activités club'],
    'citation_author' => 'Radio Club Durnal ON4CRD',
];
if ($coverPath !== null) {
    $pageMeta['image'] = base_url($coverPath);
}
if ((int) ($album['is_public'] ?? 0) !== 1) {
    $pageMeta['robots'] = 'noindex,nofollow';
}
$imageItems = [];
foreach (array_slice($photos, 0, 12) as $position => $photo) {
    try {
        $filePath = album_photo_public_path_or_null((string) ($photo['file_path'] ?? ''));
        if ($filePath === null) {
            continue;
        }
        $photoTitle = trim((string) ($photo['title'] ?? ''));
        $imageItems[] = [
            '@type' => 'ImageObject',
            'position' => $position + 1,
            'url' => base_url($filePath),
            'name' => $photoTitle !== '' ? $photoTitle : $albumTitle,
            'caption' => trim((string) ($photo['caption'] ?? '')),
        ];
    } catch (Throwable $throwable) {
        log_structured_event('album_detail_jsonld_photo_skipped', [
            'album_id' => (int) $album['id'],
            'photo_id' => (int) ($photo['id'] ?? 0),
            'message' => $throwable->getMessage(),
        ]);
    }
}
$pageMeta['json_ld'] = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'ImageGallery',
        'name' => $albumTitle,
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
                'name' => $albumTitle,
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
            <?php if ($coverDisplayPath !== null): ?>
                <img src="<?= e(base_url($coverDisplayPath)) ?>" alt="<?= e($albumTitle) ?>" loading="eager" decoding="async" fetchpriority="high">
            <?php else: ?>
                <span class="album-placeholder-mark" aria-hidden="true"></span>
            <?php endif; ?>
        </div>
        <div class="album-detail-copy">
            <p><a href="<?= e(route_url('albums')) ?>"><?= e((string) $t['back']) ?></a></p>
            <h1><?= e($albumTitle) ?></h1>
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
        <?php if ($photos === []): ?>
            <p class="help"><?= e((string) $t['none']) ?></p>
        <?php else: ?>
            <div class="album-photo-grid">
                <?php foreach ($photos as $photo): ?>
                    <?php
                    try {
                        $filePath = album_photo_public_path_or_null((string) ($photo['file_path'] ?? ''));
                        if ($filePath === null) {
                            continue;
                        }
                        $thumbPath = album_thumbnail_public_path($filePath);
                        $thumbAbs = dirname(__DIR__) . '/' . $thumbPath;
                        $imageSrc = is_file($thumbAbs) ? $thumbPath : $filePath;
                        $title = trim((string) ($photo['title'] ?? ''));
                        $caption = trim((string) ($photo['caption'] ?? ''));
                    } catch (Throwable $throwable) {
                        log_structured_event('album_detail_photo_render_skipped', [
                            'album_id' => (int) $album['id'],
                            'photo_id' => (int) ($photo['id'] ?? 0),
                            'message' => $throwable->getMessage(),
                        ]);
                        continue;
                    }
                    ?>
                    <figure class="album-photo-card">
                        <a href="<?= e(base_url($filePath)) ?>" target="_blank" rel="noopener" data-album-viewer-open data-photo-title="<?= e($title) ?>" data-photo-caption="<?= e($caption) ?>">
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

    <dialog class="album-photo-viewer" id="album-photo-viewer" data-album-description="<?= e($albumDescriptionText) ?>" aria-labelledby="album-photo-viewer-title">
        <div class="album-photo-viewer-card">
            <button class="album-photo-viewer-close" type="button" data-album-viewer-close aria-label="<?= e($viewerCloseLabel) ?>">&times;</button>
            <div class="album-photo-viewer-media">
                <img src="" alt="" data-album-viewer-image>
            </div>
            <aside class="album-photo-viewer-copy" aria-live="polite">
                <h2 id="album-photo-viewer-title" data-album-viewer-title></h2>
                <p data-album-viewer-caption></p>
                <p class="album-photo-viewer-description" data-album-viewer-description></p>
            </aside>
        </div>
    </dialog>
</div>
<?php
echo render_layout((string) ob_get_clean(), $albumTitle);
