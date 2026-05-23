<?php
declare(strict_types=1);

require_permission('albums.manage');
$locale = current_locale();
$t = i18n_domain_locale('admin_albums', $locale);

set_page_meta([
    'title' => (string) $t['manage_title'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

function albums_admin_tables_ready(): bool
{
    return table_exists('albums') && table_exists('album_photos');
}

function albums_admin_safe_photo_path(string $publicPath): ?string
{
    return safe_storage_public_path_or_null($publicPath, ['storage/uploads/albums/']);
}

function albums_admin_delete_photo_files(string $publicPath): void
{
    $safePath = albums_admin_safe_photo_path($publicPath);
    if ($safePath === null) {
        return;
    }
    $absolute = dirname(__DIR__) . '/' . $safePath;
    if (is_file($absolute)) {
        @unlink($absolute);
    }
    $thumbPublic = album_thumbnail_public_path($safePath);
    $thumbAbsolute = dirname(__DIR__) . '/' . $thumbPublic;
    if (is_file($thumbAbsolute)) {
        @unlink($thumbAbsolute);
    }
}

function albums_admin_clear_cache(): void
{
    cache_forget('admin_albums_list_v2');
    cache_forget('admin_albums_photos_total_v2');
}

if (!albums_admin_tables_ready()) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['manage_title']) . '</h1><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['manage_title']);
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'create_album') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            if ($title === '') {
                throw new RuntimeException((string) $t['title_required']);
            }
            db()->prepare('INSERT INTO albums (title, description, is_public) VALUES (?, ?, ?)')->execute([$title, $description, $isPublic]);
            albums_admin_clear_cache();
            set_flash('success', (string) $t['created_ok']);
            redirect('admin_albums');
        }

        if ($action === 'update_album') {
            $albumId = (int) ($_POST['album_id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            if ($albumId <= 0 || $title === '') {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            db()->prepare('UPDATE albums SET title = ?, description = ?, is_public = ? WHERE id = ?')->execute([$title, $description, $isPublic, $albumId]);
            albums_admin_clear_cache();
            set_flash('success', (string) $t['updated_ok']);
            redirect('admin_albums');
        }

        if ($action === 'delete_album') {
            $albumId = (int) ($_POST['album_id'] ?? 0);
            if ($albumId <= 0) {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            $photoStmt = db()->prepare('SELECT file_path FROM album_photos WHERE album_id = ?');
            $photoStmt->execute([$albumId]);
            $photoRows = $photoStmt->fetchAll() ?: [];
            db()->beginTransaction();
            db()->prepare('DELETE FROM album_photos WHERE album_id = ?')->execute([$albumId]);
            db()->prepare('DELETE FROM albums WHERE id = ?')->execute([$albumId]);
            db()->commit();
            foreach ($photoRows as $photoRow) {
                albums_admin_delete_photo_files((string) ($photoRow['file_path'] ?? ''));
            }
            albums_admin_clear_cache();
            set_flash('success', (string) $t['album_deleted_ok']);
            redirect('admin_albums');
        }

        if ($action === 'upload_photo') {
            $albumId = (int) ($_POST['album_id'] ?? 0);
            if ($albumId <= 0) {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            $albumStmt = db()->prepare('SELECT * FROM albums WHERE id = ?');
            $albumStmt->execute([$albumId]);
            $albumRow = $albumStmt->fetch();
            if (!$albumRow) {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            $title = trim((string) ($_POST['title'] ?? ''));
            $caption = trim((string) ($_POST['caption'] ?? ''));
            $files = $_FILES['photos'] ?? $_FILES['photo'] ?? null;
            $insertPhotoStmt = db()->prepare('INSERT INTO album_photos (album_id, title, caption, file_path) VALUES (?, ?, ?, ?)');
            $importedCount = 0;
            $lastTitle = $title !== '' ? $title : (string) $t['photo'];

            if (is_array($files) && is_array($files['name'] ?? null)) {
                $total = count($files['name']);
                for ($i = 0; $i < $total; $i++) {
                    $single = [
                        'name' => $files['name'][$i] ?? '',
                        'type' => $files['type'][$i] ?? '',
                        'tmp_name' => $files['tmp_name'][$i] ?? '',
                        'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $files['size'][$i] ?? 0,
                    ];
                    if ((int) $single['error'] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $path = handle_album_upload($single, (string) (current_user()['callsign'] ?? 'album'));
                    $photoTitle = $title !== '' && $total === 1 ? $title : pathinfo((string) $single['name'], PATHINFO_FILENAME);
                    $photoTitle = trim($photoTitle) !== '' ? trim($photoTitle) : (string) $t['photo'];
                    $insertPhotoStmt->execute([$albumId, $photoTitle, $caption, $path]);
                    $lastTitle = $photoTitle;
                    $importedCount++;
                }
            } else {
                $path = handle_album_upload(is_array($files) ? $files : null, (string) (current_user()['callsign'] ?? 'album'));
                $photoTitle = $title !== '' ? $title : (string) $t['photo'];
                $insertPhotoStmt->execute([$albumId, $photoTitle, $caption, $path]);
                $lastTitle = $photoTitle;
                $importedCount = 1;
            }

            if ($importedCount <= 0) {
                throw new RuntimeException((string) $t['no_photo_imported']);
            }
            if ((int) $albumRow['is_public'] === 1) {
                notify_album_webhooks([
                    'event' => 'album.photo_uploaded',
                    'album_id' => $albumId,
                    'album_title' => (string) $albumRow['title'],
                    'photo_title' => $lastTitle,
                    'photo_path' => 'batch',
                    'public_url' => route_url('album', ['id' => $albumId]),
                ]);
            }
            albums_admin_clear_cache();
            set_flash('success', $importedCount . ' ' . (string) $t['uploaded_count']);
            redirect('admin_albums');
        }

        if ($action === 'update_photo') {
            $photoId = (int) ($_POST['photo_id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $caption = trim((string) ($_POST['caption'] ?? ''));
            if ($photoId <= 0 || $title === '') {
                throw new RuntimeException((string) $t['invalid_photo']);
            }
            db()->prepare('UPDATE album_photos SET title = ?, caption = ? WHERE id = ?')->execute([$title, $caption, $photoId]);
            albums_admin_clear_cache();
            set_flash('success', (string) $t['photo_updated_ok']);
            redirect('admin_albums');
        }

        if ($action === 'delete_photo') {
            $photoId = (int) ($_POST['photo_id'] ?? 0);
            if ($photoId <= 0) {
                throw new RuntimeException((string) $t['invalid_photo']);
            }
            $photoStmt = db()->prepare('SELECT file_path FROM album_photos WHERE id = ?');
            $photoStmt->execute([$photoId]);
            $photoRow = $photoStmt->fetch();
            db()->prepare('DELETE FROM album_photos WHERE id = ?')->execute([$photoId]);
            if (is_array($photoRow)) {
                albums_admin_delete_photo_files((string) ($photoRow['file_path'] ?? ''));
            }
            albums_admin_clear_cache();
            set_flash('success', (string) $t['photo_deleted_ok']);
            redirect('admin_albums');
        }

        if ($action === 'rebuild_thumbnails') {
            $photoRows = db()->query('SELECT file_path FROM album_photos ORDER BY id DESC')->fetchAll() ?: [];
            $created = 0;
            foreach ($photoRows as $photoRow) {
                $safePath = albums_admin_safe_photo_path((string) ($photoRow['file_path'] ?? ''));
                if ($safePath !== null && create_album_thumbnail($safePath, 640, 640) !== null) {
                    $created++;
                }
            }
            albums_admin_clear_cache();
            set_flash('success', $created . ' ' . (string) $t['created_thumbs']);
            redirect('admin_albums');
        }
    } catch (Throwable $throwable) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        set_flash('error', $throwable->getMessage());
        redirect('admin_albums');
    }
}

$albums = cache_remember('admin_albums_list_v2', 30, static fn(): array => db()->query(
    'SELECT a.*, COUNT(p.id) AS photo_count, MAX(p.created_at) AS last_photo_at
     FROM albums a
     LEFT JOIN album_photos p ON p.album_id = a.id
     GROUP BY a.id
     ORDER BY a.created_at DESC'
)->fetchAll() ?: []);
$albumsCount = count($albums);
$publicCount = 0;
$totalPhotos = 0;
foreach ($albums as $albumRow) {
    $publicCount += (int) $albumRow['is_public'] === 1 ? 1 : 0;
    $totalPhotos += (int) ($albumRow['photo_count'] ?? 0);
}

$photosPage = max(1, (int) ($_GET['photos_page'] ?? 1));
$photosPerPage = 36;
$photosTotal = (int) cache_remember('admin_albums_photos_total_v2', 30, static fn(): int => (int) (db()->query('SELECT COUNT(*) FROM album_photos')?->fetchColumn() ?: 0));
$pagination = pagination_state($photosTotal, $photosPage, $photosPerPage);
$photosPage = $pagination['page'];
$photosMaxPage = $pagination['total_pages'];
$photosOffset = $pagination['offset'];
$photos = db()->query(
    'SELECT p.*, a.title AS album_title
     FROM album_photos p
     INNER JOIN albums a ON a.id = p.album_id
     ORDER BY p.id DESC
     LIMIT ' . (int) $photosPerPage . ' OFFSET ' . (int) $photosOffset
)->fetchAll() ?: [];

ob_start();
?>
<div class="stack">
    <section class="card gallery-header">
        <div class="row-between">
            <div>
                <h1><?= e((string) $t['manage_title']) ?></h1>
                <p class="help"><?= e((string) $t['intro']) ?></p>
            </div>
            <form method="post">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="rebuild_thumbnails">
                <button class="button secondary small" type="submit"><?= e((string) $t['rebuild_thumbs']) ?></button>
            </form>
        </div>
        <div class="stats-grid">
            <article class="stat-card"><span class="help"><?= e((string) $t['albums']) ?></span><strong><?= $albumsCount ?></strong></article>
            <article class="stat-card"><span class="help"><?= e((string) $t['public_albums']) ?></span><strong><?= $publicCount ?></strong></article>
            <article class="stat-card"><span class="help"><?= e((string) $t['photos']) ?></span><strong><?= $totalPhotos ?></strong></article>
        </div>
    </section>

    <div class="grid-2">
        <section class="card">
            <h2><?= e((string) $t['create_album']) ?></h2>
            <form method="post">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create_album">
                <label><?= e((string) $t['title']) ?>
                    <input type="text" name="title" required maxlength="190">
                </label>
                <label><?= e((string) $t['description']) ?>
                    <textarea name="description" rows="4"></textarea>
                </label>
                <label><input type="checkbox" name="is_public" checked> <?= e((string) $t['public_album']) ?></label>
                <button class="button"><?= e((string) $t['create_album']) ?></button>
            </form>
        </section>

        <section class="card">
            <h2><?= e((string) $t['add_photo']) ?></h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="upload_photo">
                <label><?= e((string) $t['album_label']) ?>
                    <select name="album_id" required>
                        <?php foreach ($albums as $album): ?>
                            <option value="<?= (int) $album['id'] ?>"><?= e((string) $album['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?= e((string) $t['photo_title']) ?>
                    <input type="text" name="title" maxlength="190" placeholder="<?= e((string) $t['batch_title_hint']) ?>">
                </label>
                <label><?= e((string) $t['caption']) ?>
                    <textarea name="caption" rows="3"></textarea>
                </label>
                <label><?= e((string) $t['files_dropzone']) ?>
                    <div id="album-dropzone" class="card" style="border:2px dashed var(--border);padding:14px;text-align:center;cursor:pointer;">
                        <?= e((string) $t['dropzone_hint']) ?>
                    </div>
                    <input id="album-photos-input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required style="display:none;">
                </label>
                <p class="help"><?= e((string) $t['upload_help']) ?></p>
                <button class="button"><?= e((string) $t['upload']) ?></button>
            </form>
        </section>
    </div>

    <section class="card">
        <h2><?= e((string) $t['edit_albums']) ?></h2>
        <?php if ($albums === []): ?>
            <p class="help"><?= e((string) $t['no_albums']) ?></p>
        <?php else: ?>
            <div class="stack">
                <?php foreach ($albums as $album): ?>
                    <article class="article-item">
                        <form method="post" class="grid-2">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_album">
                            <input type="hidden" name="album_id" value="<?= (int) $album['id'] ?>">
                            <label><?= e((string) $t['title']) ?>
                                <input type="text" name="title" value="<?= e((string) $album['title']) ?>" required maxlength="190">
                            </label>
                            <label><input type="checkbox" name="is_public" <?= (int) $album['is_public'] === 1 ? 'checked' : '' ?>> <?= e((string) $t['public_album']) ?></label>
                            <label style="grid-column:1 / -1;"><?= e((string) $t['description']) ?>
                                <textarea name="description" rows="3"><?= e((string) ($album['description'] ?? '')) ?></textarea>
                            </label>
                            <p class="help"><?= (int) $album['photo_count'] ?> <?= e((string) $t['photos']) ?> · <?= e((string) $t['created_at']) ?> <?= e((string) $album['created_at']) ?></p>
                            <div class="actions">
                                <button class="button small" type="submit"><?= e((string) $t['save']) ?></button>
                                <a class="button secondary small" href="<?= e(route_url('album', ['id' => (int) $album['id']])) ?>"><?= e((string) $t['view_public']) ?></a>
                            </div>
                        </form>
                        <form method="post" style="margin-top:8px;" onsubmit="return confirm(<?= e(json_encode((string) $t['confirm_delete_album'], JSON_UNESCAPED_UNICODE)) ?>)">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_album">
                            <input type="hidden" name="album_id" value="<?= (int) $album['id'] ?>">
                            <button class="button small secondary" type="submit"><?= e((string) $t['delete_album']) ?></button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2><?= e((string) $t['photos_editor']) ?></h2>
        <?php if ($photos === []): ?>
            <p class="help"><?= e((string) $t['no_photos']) ?></p>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($photos as $photo):
                    $safePath = albums_admin_safe_photo_path((string) ($photo['file_path'] ?? ''));
                    $thumbPath = $safePath !== null ? album_thumbnail_public_path($safePath) : '';
                    $thumbAbs = $thumbPath !== '' ? dirname(__DIR__) . '/' . $thumbPath : '';
                    $imageSrc = $thumbPath !== '' && is_file($thumbAbs) ? $thumbPath : ($safePath ?? '');
                    ?>
                    <article class="gallery-item">
                        <?php if ($imageSrc !== ''): ?>
                            <img src="<?= e(base_url($imageSrc)) ?>" alt="<?= e((string) ($photo['title'] ?? $t['photo'])) ?>">
                        <?php endif; ?>
                        <p class="help"><?= e((string) $t['album_word']) ?> : <?= e((string) $photo['album_title']) ?></p>
                        <form method="post">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_photo">
                            <input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>">
                            <label><?= e((string) $t['title']) ?>
                                <input type="text" name="title" value="<?= e((string) $photo['title']) ?>" required maxlength="190">
                            </label>
                            <label><?= e((string) $t['caption']) ?>
                                <textarea name="caption" rows="2"><?= e((string) ($photo['caption'] ?? '')) ?></textarea>
                            </label>
                            <div class="actions">
                                <button class="button small" type="submit"><?= e((string) $t['update']) ?></button>
                                <?php if ($safePath !== null): ?><a class="button secondary small" href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a><?php endif; ?>
                            </div>
                        </form>
                        <form method="post" style="margin-top:8px;" onsubmit="return confirm(<?= e(json_encode((string) $t['confirm_delete_photo'], JSON_UNESCAPED_UNICODE)) ?>)">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_photo">
                            <input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>">
                            <button class="button small secondary" type="submit"><?= e((string) $t['delete']) ?></button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if ($photosMaxPage > 1): ?>
                <div class="actions mt-3">
                    <?php if ($photosPage > 1): ?>
                        <a class="button secondary small" href="<?= e(route_url_clean('admin_albums', ['photos_page' => $photosPage - 1])) ?>"><?= e((string) $t['previous']) ?></a>
                    <?php endif; ?>
                    <span class="pill"><?= e((string) $t['page']) ?> <?= $photosPage ?> / <?= $photosMaxPage ?></span>
                    <?php if ($photosPage < $photosMaxPage): ?>
                        <a class="button secondary small" href="<?= e(route_url_clean('admin_albums', ['photos_page' => $photosPage + 1])) ?>"><?= e((string) $t['next']) ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['manage_title']);
?>
<script nonce="<?= e(csp_nonce()) ?>">
(() => {
    const dropzone = document.querySelector('#album-dropzone');
    const input = document.querySelector('#album-photos-input');
    if (!(dropzone instanceof HTMLElement) || !(input instanceof HTMLInputElement)) return;
    const ready = <?= json_encode((string) $t['ready_files'], JSON_UNESCAPED_UNICODE) ?>;
    const setCount = () => {
        const count = input.files?.length || 0;
        if (count > 0) dropzone.textContent = count + ' ' + ready;
    };
    dropzone.addEventListener('click', () => input.click());
    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.style.background = 'var(--panel-3)';
    });
    dropzone.addEventListener('dragleave', () => {
        dropzone.style.background = '';
    });
    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.style.background = '';
        const files = event.dataTransfer?.files;
        if (!files || files.length === 0) return;
        input.files = files;
        setCount();
    });
    input.addEventListener('change', setCount);
})();
</script>
