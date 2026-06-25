<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('album', $locale);
$albumText = static function (string $key) use ($t): string {
    return trim((string) $t[$key]);
};
$viewerCloseLabel = (string) $t['close'];
$viewerPreviousLabel = (string) $t['previous'];
$viewerNextLabel = (string) $t['next'];

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
$canUploadAlbumPhotos = is_array($album)
    && $user !== null
    && (
        has_permission('albums.manage')
        || (
            (int) ($album['is_public'] ?? 0) !== 1
            && (int) ($album['member_id'] ?? 0) === (int) ($user['id'] ?? 0)
        )
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
$albumDescriptionText = album_description_display_text($albumDescription);
$albumDescriptionText = html_entity_decode(strip_tags($albumDescriptionText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$albumCategories = album_categories();
$albumCategoryCode = album_category_code((string) ($album['category'] ?? 'general'));
$albumSubcategoryCode = album_subcategory_code((string) ($album['subcategory'] ?? ''));
$albumCategoryLabel = (string) ($albumCategories[$albumCategoryCode] ?? album_category_label_from_code($albumCategoryCode));
$albumSubcategoryLabel = $albumSubcategoryCode !== '' ? album_category_label_from_code($albumSubcategoryCode) : '';
$albumSubcategoryDisplay = $albumSubcategoryLabel !== '' ? $albumSubcategoryLabel : (string) $t['no_subcategory'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'toggle_favorite') {
        $user = require_login();
        verify_csrf();
        $saved = favorite_toggle(
            (int) $user['id'],
            'album',
            (int) $album['id'],
            (string) ($album['title'] ?? ''),
            route_url('album', ['id' => (int) $album['id']])
        );
        notify_member((int) $user['id'], 'favorite', $saved ? $albumText('favorite_added') : $albumText('favorite_removed'), (string) ($album['title'] ?? ''), route_url('album', ['id' => (int) $album['id']]));
        set_flash('success', $saved ? $albumText('favorite_added_msg') : $albumText('favorite_removed_msg'));
        redirect_url(route_url_clean('album', ['id' => (int) $album['id'], 'p' => max(1, (int) ($_GET['p'] ?? 1))]));
    }

    if ($action === 'upload_album_photos') {
        $user = require_login(route_url('album', ['id' => (int) $album['id']]));
        verify_csrf();
        try {
            if (!$canUploadAlbumPhotos) {
                throw new RuntimeException($albumText('album_forbidden'));
            }

            $uploadResult = album_store_uploaded_photos(
                (int) $album['id'],
                $_FILES['photos'] ?? $_FILES['photo'] ?? null,
                '',
                (string) ($_POST['caption'] ?? ''),
                (string) ($user['callsign'] ?? 'album'),
                $albumText('photo')
            );
            notify_member(
                (int) $user['id'],
                'import',
                $albumText('notification_import_completed_title'),
                sprintf($albumText('notification_import_completed_body'), (int) $uploadResult['count']),
                route_url('album', ['id' => (int) $album['id']])
            );
            $totalStmt = db()->prepare('SELECT COUNT(*) FROM album_photos WHERE album_id = ?');
            $totalStmt->execute([(int) $album['id']]);
            $targetPage = max(1, (int) ceil(((int) $totalStmt->fetchColumn()) / 24));
            set_flash('success', (int) $uploadResult['count'] . ' ' . $albumText('uploaded_count'));
            redirect_url(route_url_clean('album', ['id' => (int) $album['id'], 'p' => $targetPage]) . '#album-upload');
        } catch (Throwable $throwable) {
            set_flash('error', $throwable->getMessage());
            redirect_url(route_url_clean('album', ['id' => (int) $album['id'], 'p' => max(1, (int) ($_GET['p'] ?? 1))]) . '#album-upload');
        }
    }
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
$coverDisplayWebpPath = '';
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
        $coverThumbPath = album_existing_thumbnail_fallback_public_path($coverPath);
        if ($coverThumbPath !== '') {
            $coverDisplayPath = $coverThumbPath;
            $coverDisplayWebpPath = album_existing_thumbnail_webp_public_path($coverPath);
        } else {
            $coverDisplayPath = $coverPath;
            $coverDisplayWebpPath = album_existing_display_webp_public_path($coverPath);
        }
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
        <div class="album-detail-cover-stack">
            <div class="album-detail-cover">
                <?php if ($coverDisplayPath !== null): ?>
                    <?= album_picture_html($coverDisplayPath, $albumTitle, ['loading' => 'eager', 'decoding' => 'async', 'fetchpriority' => 'high'], $coverDisplayWebpPath) ?>
                <?php else: ?>
                    <span class="album-placeholder-mark" aria-hidden="true"></span>
                <?php endif; ?>
            </div>
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
                    <button class="button secondary album-favorite-button" type="submit"><span><?= e((string) $t['favorites']) ?></span><span aria-hidden="true"><?= $isFavorite ? '&#9733;' : '&#9734;' ?></span></button>
                </form>
            <?php endif; ?>
            <?php if ($canUploadAlbumPhotos): ?>
                <p class="actions"><a class="button" href="#album-upload"><?= e($albumText('upload_photos_cta')) ?></a></p>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($canUploadAlbumPhotos): ?>
    <section class="card album-upload-panel" id="album-upload">
        <div>
            <h2><?= e($albumText('upload_photos_title')) ?></h2>
            <p class="help"><?= e($albumText('upload_photos_help')) ?></p>
        </div>
        <form method="post" enctype="multipart/form-data" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="upload_album_photos">
            <label><span><?= e($albumText('caption')) ?></span>
                <textarea name="caption" rows="3" maxlength="5000"></textarea>
            </label>
            <label><span><?= e($albumText('files_dropzone')) ?></span>
                <div class="album-dropzone" data-album-upload-dropzone data-ready-files="<?= e($albumText('ready_files')) ?>" role="button" tabindex="0">
                    <?= e($albumText('dropzone_hint')) ?>
                </div>
                <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required data-album-upload-input>
            </label>
            <p class="help"><?= e($albumText('upload_help')) ?></p>
            <p class="actions"><button class="button" type="submit"><?= e($albumText('upload')) ?></button></p>
        </form>
    </section>
    <?php endif; ?>

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
                        $thumbPath = album_existing_thumbnail_fallback_public_path($filePath);
                        $imageSrc = $thumbPath !== '' ? $thumbPath : $filePath;
                        $imageWebpSrc = $thumbPath !== '' ? album_existing_thumbnail_webp_public_path($filePath) : album_existing_display_webp_public_path($filePath);
                        $displayWebpSrc = album_existing_display_webp_public_path($filePath);
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
                        <a href="<?= e(base_url($filePath)) ?>" target="_blank" rel="noopener" data-album-viewer-open data-photo-title="<?= e($title) ?>" data-photo-caption="<?= e($caption) ?>" data-photo-display-src="<?= e($displayWebpSrc !== '' ? base_url($displayWebpSrc) : '') ?>" data-photo-fallback-src="<?= e(base_url($filePath)) ?>">
                            <?= album_picture_html($imageSrc, $title !== '' ? $title : (string) $t['photo_alt'], ['loading' => 'lazy', 'decoding' => 'async'], $imageWebpSrc) ?>
                        </a>
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
                <?php if (count($photos) > 1): ?>
                    <button class="album-photo-viewer-nav is-prev" type="button" data-album-viewer-prev aria-label="<?= e($viewerPreviousLabel) ?>">&#8249;</button>
                <?php endif; ?>
                <img src="" alt="" data-album-viewer-image>
                <?php if (count($photos) > 1): ?>
                    <button class="album-photo-viewer-nav is-next" type="button" data-album-viewer-next aria-label="<?= e($viewerNextLabel) ?>">&#8250;</button>
                <?php endif; ?>
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
