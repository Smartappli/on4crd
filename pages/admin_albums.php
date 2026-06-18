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

function albums_admin_ensure_photo_order_column(): bool
{
    return album_ensure_photo_sort_order_column();
}

function albums_admin_safe_photo_path(string $publicPath): ?string
{
    return album_photo_public_path_or_null($publicPath);
}

function albums_admin_delete_photo_files(string $publicPath): bool
{
    $safePath = albums_admin_safe_photo_path($publicPath);
    if ($safePath === null) {
        return true;
    }
    $ok = true;
    $absolute = dirname(__DIR__) . '/' . $safePath;
    if (is_file($absolute)) {
        $ok = @unlink($absolute) && $ok;
    }
    $thumbPublic = album_thumbnail_public_path($safePath);
    $thumbAbsolute = dirname(__DIR__) . '/' . $thumbPublic;
    if (is_file($thumbAbsolute)) {
        $ok = @unlink($thumbAbsolute) && $ok;
    }
    return $ok;
}

/**
 * @param array<string, mixed> $photo
 * @param array<string, mixed> $messages
 * @return array{safe_path:?string,image_src:string,title:string,caption:string,album_title:string}
 */
function albums_admin_photo_render_data(array $photo, array $messages, string $logEvent): array
{
    $safePath = null;
    $imageSrc = '';

    try {
        $safePath = albums_admin_safe_photo_path((string) ($photo['file_path'] ?? ''));
        if ($safePath !== null) {
            $imageSrc = $safePath;
            $thumbPath = album_thumbnail_public_path($safePath);
            $thumbAbs = $thumbPath !== '' ? dirname(__DIR__) . '/' . $thumbPath : '';
            if ($thumbAbs !== '' && is_file($thumbAbs)) {
                $imageSrc = $thumbPath;
            }
        }
    } catch (Throwable $throwable) {
        log_structured_event($logEvent, [
            'photo_id' => (int) ($photo['id'] ?? 0),
            'album_id' => (int) ($photo['album_id'] ?? 0),
            'message' => $throwable->getMessage(),
        ]);
        $safePath = null;
        $imageSrc = '';
    }

    $title = trim((string) ($photo['title'] ?? ''));
    if ($title === '') {
        $title = (string) ($messages['photo'] ?? 'Photo');
    }

    return [
        'safe_path' => $safePath,
        'image_src' => $imageSrc,
        'title' => $title,
        'caption' => trim((string) ($photo['caption'] ?? '')),
        'album_title' => trim((string) ($photo['album_title'] ?? '')),
    ];
}

function albums_admin_js_string(string $message): string
{
    $encoded = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return is_string($encoded) ? $encoded : '""';
}

/**
 * @param mixed $photo
 */
function albums_admin_log_photo_render_failure(Throwable $throwable, mixed $photo, string $event): void
{
    $photoRow = is_array($photo) ? $photo : [];
    log_structured_event($event, [
        'photo_id' => (int) ($photoRow['id'] ?? 0),
        'album_id' => (int) ($photoRow['album_id'] ?? 0),
        'message' => $throwable->getMessage(),
        'file' => $throwable->getFile(),
        'line' => $throwable->getLine(),
    ]);
}

function albums_admin_clear_cache(): void
{
    cache_forget('admin_albums_list_v2');
    cache_forget('admin_albums_photos_total_v2');
}

function albums_admin_validate_text_lengths(string $title, string $description = '', string $caption = ''): void
{
    if (mb_strlen($title) > 190 || mb_strlen($description) > 10000 || mb_strlen($caption) > 5000) {
        throw new RuntimeException('Un des champs dépasse la longueur autorisée.');
    }
}

if (!albums_admin_tables_ready()) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['manage_title']) . '</h1><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['manage_title']);
    return;
}
if (!albums_admin_ensure_photo_order_column() || !album_ensure_source_proposal_column()) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['manage_title']) . '</h1><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['manage_title']);
    return;
}
album_sync_accepted_proposals();
$albumCategories = album_categories();
$albumSubcategoriesByCategory = album_subcategories_by_category();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'add_category') {
            if (!album_ensure_categories_table()) {
                throw new RuntimeException((string) $t['storage_unavailable']);
            }
            $label = content_proposal_clean_single_line((string) ($_POST['category_label'] ?? ''), 160);
            $code = album_category_code((string) ($_POST['category_code'] ?? $label));
            if ($label === '' || $code === '') {
                throw new RuntimeException((string) $t['title_required']);
            }
            db()->prepare('INSERT INTO album_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                ->execute([$code, $label]);
            album_clear_caches();
            set_flash('success', (string) $t['created_ok']);
            redirect('admin_albums');
        }

        if ($action === 'update_category') {
            if (!album_ensure_categories_table()) {
                throw new RuntimeException((string) $t['storage_unavailable']);
            }
            $category = album_category_from_input((string) ($_POST['category_code'] ?? ''), $albumCategories);
            $label = content_proposal_clean_single_line((string) ($_POST['category_label'] ?? ''), 160);
            if ($label === '') {
                throw new RuntimeException((string) $t['title_required']);
            }
            db()->prepare('INSERT INTO album_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')
                ->execute([$category, $label]);
            album_clear_caches();
            set_flash('success', (string) $t['created_ok']);
            redirect('admin_albums');
        }

        if ($action === 'delete_category') {
            if (!album_ensure_categories_table()) {
                throw new RuntimeException((string) $t['storage_unavailable']);
            }
            $category = album_category_from_input((string) ($_POST['category_code'] ?? ''), $albumCategories);
            if ($category === 'general') {
                throw new RuntimeException((string) ($t['invalid_album'] ?? 'Invalid album.'));
            }
            $subCountStmt = db()->prepare('SELECT COUNT(*) FROM album_subcategories WHERE category_code = ?');
            $subCountStmt->execute([$category]);
            if ((int) $subCountStmt->fetchColumn() > 0) {
                throw new RuntimeException((string) ($t['err_category_has_subcategories'] ?? 'Supprimez d abord les sous-thématiques.'));
            }
            db()->prepare('UPDATE albums SET category = "general", subcategory = "" WHERE category = ?')->execute([$category]);
            db()->prepare('INSERT INTO album_categories (code, label, deleted_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE deleted_at = NOW()')
                ->execute([$category, (string) ($albumCategories[$category] ?? album_category_label_from_code($category))]);
            album_clear_caches();
            set_flash('success', (string) $t['album_deleted_ok']);
            redirect('admin_albums');
        }

        if ($action === 'add_subcategory') {
            if (!album_ensure_subcategories_table()) {
                throw new RuntimeException((string) $t['storage_unavailable']);
            }
            $category = album_category_from_input((string) ($_POST['subcategory_category'] ?? 'general'), $albumCategories);
            $label = content_proposal_clean_single_line((string) ($_POST['subcategory_label'] ?? ''), 160);
            $code = album_subcategory_code((string) ($_POST['subcategory_code'] ?? $label));
            if ($label === '' || $code === '') {
                throw new RuntimeException((string) $t['title_required']);
            }
            db()->prepare('INSERT INTO album_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$category, $code, $label]);
            album_clear_caches();
            set_flash('success', (string) $t['created_ok']);
            redirect('admin_albums');
        }

        if ($action === 'update_subcategory') {
            if (!album_ensure_subcategories_table()) {
                throw new RuntimeException((string) $t['storage_unavailable']);
            }
            $parts = album_subcategory_ref_parts((string) ($_POST['subcategory_ref'] ?? ''));
            $category = album_category_from_input($parts['category'] !== '' ? $parts['category'] : (string) ($_POST['category'] ?? 'general'), $albumCategories);
            $subcategory = album_subcategory_code($parts['subcategory']);
            $label = content_proposal_clean_single_line((string) ($_POST['subcategory_label'] ?? ''), 160);
            if ($subcategory === '' || $label === '') {
                throw new RuntimeException((string) $t['title_required']);
            }
            db()->prepare('INSERT INTO album_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')
                ->execute([$category, $subcategory, $label]);
            album_clear_caches();
            set_flash('success', (string) $t['created_ok']);
            redirect('admin_albums');
        }

        if ($action === 'delete_subcategory') {
            if (!album_ensure_subcategories_table()) {
                throw new RuntimeException((string) $t['storage_unavailable']);
            }
            $parts = album_subcategory_ref_parts((string) ($_POST['subcategory_ref'] ?? ''));
            $category = album_category_from_input($parts['category'] !== '' ? $parts['category'] : (string) ($_POST['category'] ?? 'general'), $albumCategories);
            $subcategory = album_subcategory_code($parts['subcategory']);
            if ($subcategory === '') {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            $countStmt = db()->prepare('SELECT COUNT(*) FROM albums WHERE category = ? AND subcategory = ?');
            $countStmt->execute([$category, $subcategory]);
            if ((int) $countStmt->fetchColumn() > 0) {
                throw new RuntimeException((string) ($t['err_subcategory_has_documents'] ?? 'Cette sous-thématique contient encore des albums.'));
            }
            db()->prepare('DELETE FROM album_subcategories WHERE category_code = ? AND code = ?')->execute([$category, $subcategory]);
            album_clear_caches();
            set_flash('success', (string) $t['album_deleted_ok']);
            redirect('admin_albums');
        }

        if ($action === 'create_album') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            $category = album_category_from_input((string) ($_POST['category'] ?? 'general'), $albumCategories);
            $subcategory = '';
            $subcategoryRef = trim((string) ($_POST['subcategory_ref'] ?? ''));
            if ($subcategoryRef !== '') {
                $subcategoryParts = album_subcategory_ref_parts($subcategoryRef);
                if ($subcategoryParts['subcategory'] !== '') {
                    $subcategory = $subcategoryParts['subcategory'];
                    if ($subcategoryParts['category'] !== '') {
                        $category = album_category_from_input($subcategoryParts['category'], $albumCategories);
                    }
                }
            }
            albums_admin_validate_text_lengths($title, $description);
            if ($title === '') {
                throw new RuntimeException((string) $t['title_required']);
            }
            db()->prepare('INSERT INTO albums (member_id, category, subcategory, title, description, is_public, publish_requested) VALUES (?, ?, ?, ?, ?, 0, ?)')
                ->execute([(int) (current_user()['id'] ?? 0), $category, $subcategory, $title, $description, $isPublic]);
            $albumId = (int) db()->lastInsertId();
            album_clear_caches();
            set_flash('success', (string) ($t['album_created_continue'] ?? $t['created_ok']));
            redirect_url(album_admin_wizard_url(['album_wizard' => $albumId, 'step' => 2]));
        }

        if ($action === 'update_album') {
            $albumId = (int) ($_POST['album_id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            $category = album_category_from_input((string) ($_POST['category'] ?? 'general'), $albumCategories);
            $subcategory = '';
            $subcategoryRef = trim((string) ($_POST['subcategory_ref'] ?? ''));
            if ($subcategoryRef !== '') {
                $subcategoryParts = album_subcategory_ref_parts($subcategoryRef);
                if ($subcategoryParts['subcategory'] !== '') {
                    $subcategory = $subcategoryParts['subcategory'];
                    if ($subcategoryParts['category'] !== '') {
                        $category = album_category_from_input($subcategoryParts['category'], $albumCategories);
                    }
                }
            }
            albums_admin_validate_text_lengths($title, $description);
            if ($albumId <= 0 || $title === '') {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            $albumStmt = db()->prepare('SELECT id FROM albums WHERE id = ? LIMIT 1');
            $albumStmt->execute([$albumId]);
            if (!$albumStmt->fetchColumn()) {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            db()->prepare('UPDATE albums SET category = ?, subcategory = ?, title = ?, description = ?, is_public = ? WHERE id = ?')->execute([$category, $subcategory, $title, $description, $isPublic, $albumId]);
            albums_admin_clear_cache();
            set_flash('success', (string) $t['updated_ok']);
            redirect('admin_albums');
        }

        if ($action === 'delete_album') {
            $albumId = (int) ($_POST['album_id'] ?? 0);
            if ($albumId <= 0) {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            $albumStmt = db()->prepare('SELECT id FROM albums WHERE id = ? LIMIT 1');
            $albumStmt->execute([$albumId]);
            if (!$albumStmt->fetchColumn()) {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            album_delete_record($albumId);
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
            albums_admin_validate_text_lengths($title, '', $caption);
            $files = $_FILES['photos'] ?? $_FILES['photo'] ?? null;
            $insertPhotoStmt = db()->prepare('INSERT INTO album_photos (album_id, sort_order, title, caption, file_path) VALUES (?, ?, ?, ?, ?)');
            $importedCount = 0;
            $lastTitle = $title !== '' ? $title : (string) $t['photo'];
            $orderStmt = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM album_photos WHERE album_id = ?');
            $uploadBatch = [];
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
                    $uploadBatch[] = $single;
                }
            } else {
                $uploadBatch[] = is_array($files) ? $files : null;
            }

            $uploadBatch = array_values(array_filter($uploadBatch, static fn($item): bool => is_array($item)));
            if ($uploadBatch === []) {
                throw new RuntimeException((string) $t['no_photo_imported']);
            }
            if (count($uploadBatch) > 100) {
                throw new RuntimeException((string) ($t['batch_max_files'] ?? 'Maximum 100 photos par import.'));
            }
            $totalBytes = array_sum(array_map(static fn(array $item): int => max(0, (int) ($item['size'] ?? 0)), $uploadBatch));
            if ($totalBytes > 512 * 1024 * 1024) {
                throw new RuntimeException((string) ($t['batch_max_size'] ?? 'Le lot de photos depasse 512 Mo.'));
            }

            $createdPaths = [];
            try {
                $orderStmt->execute([$albumId]);
                $nextOrder = (int) ($orderStmt->fetchColumn() ?: 0);
                db()->beginTransaction();
                foreach ($uploadBatch as $single) {
                    $path = handle_album_upload($single, (string) (current_user()['callsign'] ?? 'album'));
                    $createdPaths[] = $path;
                    $nextOrder++;
                    $photoTitle = $title !== '' && count($uploadBatch) === 1 ? $title : ((string) $t['photo'] . ' ' . $nextOrder);
                    if (mb_strlen($photoTitle) > 190) {
                        $photoTitle = mb_substr($photoTitle, 0, 190);
                    }
                    $insertPhotoStmt->execute([$albumId, $nextOrder, $photoTitle, $caption, $path]);
                    $lastTitle = $photoTitle;
                    $importedCount++;
                }
                db()->commit();
            } catch (Throwable $throwable) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }
                foreach ($createdPaths as $createdPath) {
                    albums_admin_delete_photo_files($createdPath);
                }
                throw $throwable;
            }
            notify_member((int) current_user()['id'], 'import', 'Album import completed', $importedCount . ' photo(s) imported.', route_url('admin_albums'));
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
            if ((int) ($_POST['album_wizard'] ?? 0) === $albumId) {
                redirect_url(album_admin_wizard_url(['album_wizard' => $albumId, 'step' => 3]));
            }
            redirect('admin_albums');
        }

        if ($action === 'finalize_album_creation') {
            $albumId = (int) ($_POST['album_id'] ?? 0);
            if ($albumId <= 0) {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            $albumStmt = db()->prepare('SELECT * FROM albums WHERE id = ? LIMIT 1');
            $albumStmt->execute([$albumId]);
            $albumRow = $albumStmt->fetch() ?: null;
            if (!is_array($albumRow)) {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            $photoCountStmt = db()->prepare('SELECT COUNT(*) FROM album_photos WHERE album_id = ?');
            $photoCountStmt->execute([$albumId]);
            if ((int) ($photoCountStmt->fetchColumn() ?: 0) <= 0) {
                throw new RuntimeException((string) ($t['wizard_no_photos'] ?? $t['no_photo_imported']));
            }
            $publish = (int) ($albumRow['publish_requested'] ?? 0) === 1 ? 1 : 0;
            db()->prepare('UPDATE albums SET is_public = ?, publish_requested = 0 WHERE id = ?')->execute([$publish, $albumId]);
            $socialResult = ['errors' => [], 'skipped' => []];
            if ($publish === 1) {
                $socialResult = album_social_publish_if_public($albumId);
                notify_album_webhooks([
                    'event' => 'album.created',
                    'album_id' => $albumId,
                    'album_title' => (string) ($albumRow['title'] ?? ''),
                    'public_url' => route_url('album', ['id' => $albumId]),
                ]);
            }
            albums_admin_clear_cache();
            if (($socialResult['errors'] ?? []) !== []) {
                set_flash('warning', (string) ($t['album_finalized_social_warning'] ?? 'Album finalise, mais la synchronisation sociale a echoue.'));
            } else {
                set_flash('success', (string) ($t['album_finalized_ok'] ?? $t['created_ok']));
            }
            redirect('admin_albums');
        }

        if ($action === 'update_photo') {
            $photoId = (int) ($_POST['photo_id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $caption = trim((string) ($_POST['caption'] ?? ''));
            albums_admin_validate_text_lengths($title, '', $caption);
            if ($photoId <= 0 || $title === '') {
                throw new RuntimeException((string) $t['invalid_photo']);
            }
            $photoStmt = db()->prepare('SELECT id FROM album_photos WHERE id = ? LIMIT 1');
            $photoStmt->execute([$photoId]);
            if (!$photoStmt->fetchColumn()) {
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
            if (!is_array($photoRow)) {
                throw new RuntimeException((string) $t['invalid_photo']);
            }
            db()->prepare('DELETE FROM album_photos WHERE id = ?')->execute([$photoId]);
            if (!albums_admin_delete_photo_files((string) ($photoRow['file_path'] ?? ''))) {
                log_structured_event('album_photo_file_delete_failed', ['photo_id' => $photoId]);
            }
            albums_admin_clear_cache();
            set_flash('success', (string) $t['photo_deleted_ok']);
            $wizardAlbumId = (int) ($_POST['return_wizard_album_id'] ?? 0);
            if ($wizardAlbumId > 0) {
                redirect_url(album_admin_wizard_url(['album_wizard' => $wizardAlbumId, 'step' => 3]));
            }
            redirect('admin_albums');
        }

        if ($action === 'reorder_photo') {
            $photoId = (int) ($_POST['photo_id'] ?? 0);
            $direction = (string) ($_POST['direction'] ?? '');
            if ($photoId <= 0 || !in_array($direction, ['up', 'down'], true)) {
                throw new RuntimeException((string) $t['invalid_photo']);
            }
            $photoStmt = db()->prepare('SELECT id, album_id, sort_order FROM album_photos WHERE id = ? LIMIT 1');
            $photoStmt->execute([$photoId]);
            $photo = $photoStmt->fetch() ?: null;
            if (!is_array($photo)) {
                throw new RuntimeException((string) $t['invalid_photo']);
            }
            $albumId = (int) ($photo['album_id'] ?? 0);
            $sortOrder = (int) ($photo['sort_order'] ?? 0);
            if ($direction === 'up') {
                $swapStmt = db()->prepare('SELECT id, sort_order FROM album_photos WHERE album_id = ? AND sort_order < ? ORDER BY sort_order DESC, id DESC LIMIT 1');
            } else {
                $swapStmt = db()->prepare('SELECT id, sort_order FROM album_photos WHERE album_id = ? AND sort_order > ? ORDER BY sort_order ASC, id ASC LIMIT 1');
            }
            $swapStmt->execute([$albumId, $sortOrder]);
            $swap = $swapStmt->fetch() ?: null;
            if (is_array($swap)) {
                db()->beginTransaction();
                db()->prepare('UPDATE album_photos SET sort_order = ? WHERE id = ?')->execute([(int) $swap['sort_order'], $photoId]);
                db()->prepare('UPDATE album_photos SET sort_order = ? WHERE id = ?')->execute([$sortOrder, (int) $swap['id']]);
                db()->commit();
            }
            albums_admin_clear_cache();
            redirect('admin_albums');
        }

        if ($action === 'rebuild_thumbnails') {
            $photoRows = db()->query('SELECT file_path FROM album_photos ORDER BY id DESC LIMIT 500')->fetchAll() ?: [];
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
$albumCategoryCounts = [];
$albumSubcategoryCounts = [];
foreach ($albums as $albumRow) {
    $categoryCode = album_category_code((string) ($albumRow['category'] ?? 'general'));
    $subcategoryCode = album_subcategory_code((string) ($albumRow['subcategory'] ?? ''));
    if ($categoryCode !== '') {
        $albumCategoryCounts[$categoryCode] = ($albumCategoryCounts[$categoryCode] ?? 0) + 1;
    }
    if ($categoryCode !== '' && $subcategoryCode !== '') {
        $albumSubcategoryCounts[$categoryCode . ':' . $subcategoryCode] = ($albumSubcategoryCounts[$categoryCode . ':' . $subcategoryCode] ?? 0) + 1;
    }
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
     ORDER BY p.album_id ASC, p.sort_order ASC, p.id ASC
     LIMIT ' . (int) $photosPerPage . ' OFFSET ' . (int) $photosOffset
)->fetchAll() ?: [];

$wizardAlbumId = max(0, (int) ($_GET['album_wizard'] ?? 0));
$wizardStep = max(1, min(3, (int) ($_GET['step'] ?? 1)));
$wizardAlbum = null;
$wizardPhotos = [];
if ($wizardAlbumId > 0) {
    $wizardAlbumStmt = db()->prepare('SELECT * FROM albums WHERE id = ? LIMIT 1');
    $wizardAlbumStmt->execute([$wizardAlbumId]);
    $wizardAlbum = $wizardAlbumStmt->fetch() ?: null;
    if (!is_array($wizardAlbum)) {
        $wizardAlbum = null;
        $wizardAlbumId = 0;
        $wizardStep = 1;
    } else {
        $wizardPhotoStmt = db()->prepare('SELECT * FROM album_photos WHERE album_id = ? ORDER BY sort_order ASC, id ASC');
        $wizardPhotoStmt->execute([$wizardAlbumId]);
        $wizardPhotos = $wizardPhotoStmt->fetchAll() ?: [];
    }
}
if ($wizardAlbumId <= 0) {
    $wizardStep = 1;
}

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

    <section class="card">
        <h2><?= e((string) ($t['category_field'] ?? 'Thématiques')) ?></h2>
        <div class="grid-2">
            <form method="post">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_category">
                <label><?= e((string) ($t['category_field'] ?? 'Thématique')) ?>
                    <input type="text" name="category_label" maxlength="160" required>
                </label>
                <button class="button"><?= e((string) ($t['add_category'] ?? $t['create_album'])) ?></button>
            </form>
            <form method="post">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_subcategory">
                <label><?= e((string) ($t['category_field'] ?? 'Thématique')) ?>
                    <select name="subcategory_category">
                        <?php foreach ($albumCategories as $code => $label): ?>
                            <option value="<?= e((string) $code) ?>"><?= e((string) $label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?= e((string) ($t['subcategory_field'] ?? 'Sous-thématique')) ?>
                    <input type="text" name="subcategory_label" maxlength="160" required>
                </label>
                <button class="button"><?= e((string) ($t['add_subcategory'] ?? $t['create_album'])) ?></button>
            </form>
        </div>
        <div class="tags-cloud">
            <?php foreach ($albumCategories as $code => $label): ?>
                <?php $categoryTotal = (int) ($albumCategoryCounts[(string) $code] ?? 0); ?>
                <?php $subcategoryTotal = count($albumSubcategoriesByCategory[(string) $code] ?? []); ?>
                <?php $categoryDeleteDisabled = (string) $code === 'general' || $subcategoryTotal > 0; ?>
                <form method="post" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" name="category_code" value="<?= e((string) $code) ?>">
                    <span class="pill"><?= e((string) $code) ?> (<?= $categoryTotal ?>)</span>
                    <input type="text" name="category_label" value="<?= e((string) $label) ?>" maxlength="160" required>
                    <button class="button small" type="submit"><?= e((string) ($t['save'] ?? 'Enregistrer')) ?></button>
                    <button class="button secondary small" type="submit" name="action" value="delete_category"<?= $categoryDeleteDisabled ? ' disabled' : '' ?>><?= e((string) $t['delete']) ?></button>
                </form>
            <?php endforeach; ?>
            <?php foreach ($albumSubcategoriesByCategory as $parentCode => $subcategories): ?>
                <?php foreach ($subcategories as $subcategoryInfo): ?>
                    <?php $subCode = album_subcategory_code((string) ($subcategoryInfo['code'] ?? '')); ?>
                    <?php if ($subCode === '') { continue; } ?>
                    <?php $subTotal = (int) ($albumSubcategoryCounts[(string) $parentCode . ':' . $subCode] ?? 0); ?>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_subcategory">
                        <input type="hidden" name="subcategory_ref" value="<?= e(album_subcategory_ref((string) $parentCode, $subCode)) ?>">
                        <span class="pill"><?= e((string) ($albumCategories[(string) $parentCode] ?? $parentCode)) ?> / <?= e($subCode) ?> (<?= $subTotal ?>)</span>
                        <input type="text" name="subcategory_label" value="<?= e((string) ($subcategoryInfo['label'] ?? $subCode)) ?>" maxlength="160" required>
                        <button class="button small" type="submit"><?= e((string) ($t['save'] ?? 'Enregistrer')) ?></button>
                        <button class="button secondary small" type="submit" name="action" value="delete_subcategory"<?= $subTotal > 0 ? ' disabled' : '' ?>><?= e((string) $t['delete']) ?></button>
                    </form>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="grid-2">
        <section class="card album-wizard" id="album-wizard">
            <h2><?= e((string) ($t['wizard_title'] ?? $t['create_album'])) ?></h2>
            <div class="actions" aria-label="<?= e((string) ($t['wizard_title'] ?? $t['create_album'])) ?>">
                <span class="pill<?= $wizardStep === 1 ? ' is-active' : '' ?>">1. <?= e((string) ($t['wizard_step_details'] ?? $t['title'])) ?></span>
                <span class="pill<?= $wizardStep === 2 ? ' is-active' : '' ?>">2. <?= e((string) ($t['wizard_step_upload'] ?? $t['upload'])) ?></span>
                <span class="pill<?= $wizardStep === 3 ? ' is-active' : '' ?>">3. <?= e((string) ($t['wizard_step_review'] ?? $t['photos_editor'])) ?></span>
            </div>
            <?php if ($wizardStep === 1): ?>
                <form method="post" class="stack">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create_album">
                    <label><?= e((string) $t['title']) ?>
                        <input type="text" name="title" required maxlength="190">
                    </label>
                    <?= render_album_taxonomy_fields($albumCategories, $t) ?>
                    <label><?= e((string) $t['description']) ?>
                        <textarea name="description" rows="4"></textarea>
                    </label>
                    <label><input type="checkbox" name="is_public"> <?= e((string) $t['public_album']) ?></label>
                    <p class="help"><?= e((string) ($t['wizard_private_help'] ?? 'The album stays private until final validation.')) ?></p>
                    <button class="button"><?= e((string) ($t['wizard_continue_upload'] ?? $t['create_album'])) ?></button>
                </form>
            <?php elseif ($wizardStep === 2 && is_array($wizardAlbum)): ?>
                <h3><?= e((string) ($wizardAlbum['title'] ?? $t['create_album'])) ?></h3>
                <form method="post" enctype="multipart/form-data" class="stack">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="upload_photo">
                    <input type="hidden" name="album_id" value="<?= (int) $wizardAlbumId ?>">
                    <input type="hidden" name="album_wizard" value="<?= (int) $wizardAlbumId ?>">
                    <label><?= e((string) $t['caption']) ?>
                        <textarea name="caption" rows="3"></textarea>
                    </label>
                    <label><?= e((string) $t['files_dropzone']) ?>
                        <div id="album-wizard-dropzone" class="album-dropzone" data-ready-files="<?= e((string) $t['ready_files']) ?>">
                            <?= e((string) $t['dropzone_hint']) ?>
                        </div>
                        <input id="album-wizard-photos-input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required style="display:none;">
                    </label>
                    <p class="help"><?= e((string) ($t['upload_help'] ?? '')) ?></p>
                    <div class="actions">
                        <button class="button"><?= e((string) $t['upload']) ?></button>
                        <a class="button secondary" href="<?= e(album_admin_wizard_url(['album_wizard' => $wizardAlbumId, 'step' => 3])) ?>"><?= e((string) ($t['wizard_review_now'] ?? $t['photos_editor'])) ?></a>
                    </div>
                </form>
            <?php elseif ($wizardStep === 3 && is_array($wizardAlbum)): ?>
                <h3><?= e((string) ($wizardAlbum['title'] ?? $t['create_album'])) ?></h3>
                <?php if ($wizardPhotos === []): ?>
                    <p class="help"><?= e((string) ($t['wizard_no_photos'] ?? $t['no_photo_imported'])) ?></p>
                    <a class="button secondary" href="<?= e(album_admin_wizard_url(['album_wizard' => $wizardAlbumId, 'step' => 2])) ?>"><?= e((string) $t['upload']) ?></a>
                <?php else: ?>
                    <div class="gallery-grid">
                        <?php foreach ($wizardPhotos as $photo): ?>
                            <?php
                            try {
                                $photoRow = is_array($photo) ? $photo : [];
                                $photoRender = albums_admin_photo_render_data($photoRow, $t, 'album_admin_wizard_photo_prepare_failed');
                                $imageSrc = $photoRender['image_src'];
                            ?>
                            <article class="gallery-item">
                                <?php if ($imageSrc !== ''): ?>
                                    <img src="<?= e(base_url($imageSrc)) ?>" alt="<?= e($photoRender['title']) ?>">
                                <?php endif; ?>
                                <form method="post" onsubmit="return confirm(<?= e(albums_admin_js_string((string) $t['confirm_delete_photo'])) ?>)">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_photo">
                                    <input type="hidden" name="photo_id" value="<?= (int) ($photoRow['id'] ?? 0) ?>">
                                    <input type="hidden" name="return_wizard_album_id" value="<?= (int) $wizardAlbumId ?>">
                                    <button class="button small secondary" type="submit"><?= e((string) $t['delete']) ?></button>
                                </form>
                            </article>
                            <?php } catch (Throwable $throwable) {
                                albums_admin_log_photo_render_failure($throwable, $photo, 'album_admin_wizard_photo_render_failed');
                                ?>
                                <article class="gallery-item">
                                    <p class="help"><?= e((string) ($t['invalid_photo'] ?? 'Photo invalide.')) ?></p>
                                </article>
                            <?php } ?>
                        <?php endforeach; ?>
                    </div>
                    <form method="post" class="actions mt-3">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="finalize_album_creation">
                        <input type="hidden" name="album_id" value="<?= (int) $wizardAlbumId ?>">
                        <a class="button secondary" href="<?= e(album_admin_wizard_url(['album_wizard' => $wizardAlbumId, 'step' => 2])) ?>"><?= e((string) $t['upload']) ?></a>
                        <button class="button" type="submit"><?= e((string) ($t['wizard_finalize'] ?? $t['save'])) ?></button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
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
                    <div id="album-dropzone" class="album-dropzone" data-ready-files="<?= e((string) $t['ready_files']) ?>">
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
                            <div style="grid-column:1 / -1;">
                                <?= render_album_taxonomy_fields($albumCategories, $t, (string) ($album['category'] ?? 'general'), (string) ($album['subcategory'] ?? '')) ?>
                            </div>
                            <label style="grid-column:1 / -1;"><?= e((string) $t['description']) ?>
                                <textarea name="description" rows="3"><?= e((string) ($album['description'] ?? '')) ?></textarea>
                            </label>
                            <p class="help"><?= (int) $album['photo_count'] ?> <?= e((string) $t['photos']) ?> · <?= e((string) $t['created_at']) ?> <?= e((string) $album['created_at']) ?></p>
                            <div class="actions">
                                <button class="button small" type="submit"><?= e((string) $t['save']) ?></button>
                                <span class="pill"><?= e((string) $t['public_album']) ?>: <?= (int) $album['is_public'] === 1 ? e((string) $t['yes']) : e((string) $t['no']) ?></span>
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
                <?php foreach ($photos as $photo): ?>
                    <?php
                    try {
                        $photoRow = is_array($photo) ? $photo : [];
                        $photoRender = albums_admin_photo_render_data($photoRow, $t, 'album_admin_photo_prepare_failed');
                        $safePath = $photoRender['safe_path'];
                        $imageSrc = $photoRender['image_src'];
                    ?>
                    <article class="gallery-item">
                        <?php if ($imageSrc !== ''): ?>
                            <img src="<?= e(base_url($imageSrc)) ?>" alt="<?= e($photoRender['title']) ?>">
                        <?php endif; ?>
                        <p class="help"><?= e((string) $t['album_word']) ?> : <?= e($photoRender['album_title']) ?></p>
                        <form method="post">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_photo">
                            <input type="hidden" name="photo_id" value="<?= (int) ($photoRow['id'] ?? 0) ?>">
                            <label><?= e((string) $t['title']) ?>
                                <input type="text" name="title" value="<?= e($photoRender['title']) ?>" required maxlength="190">
                            </label>
                            <label><?= e((string) $t['caption']) ?>
                                <textarea name="caption" rows="2"><?= e($photoRender['caption']) ?></textarea>
                            </label>
                            <div class="actions">
                                <button class="button small" type="submit"><?= e((string) $t['update']) ?></button>
                                <?php if ($safePath !== null): ?><a class="button secondary small" href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a><?php endif; ?>
                            </div>
                        </form>
                        <form method="post" class="inline-form" style="margin-top:8px;">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="reorder_photo">
                            <input type="hidden" name="photo_id" value="<?= (int) ($photoRow['id'] ?? 0) ?>">
                            <button class="button small secondary" type="submit" name="direction" value="up">&uarr;</button>
                            <button class="button small secondary" type="submit" name="direction" value="down">&darr;</button>
                        </form>
                        <form method="post" style="margin-top:8px;" onsubmit="return confirm(<?= e(albums_admin_js_string((string) $t['confirm_delete_photo'])) ?>)">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_photo">
                            <input type="hidden" name="photo_id" value="<?= (int) ($photoRow['id'] ?? 0) ?>">
                            <button class="button small secondary" type="submit"><?= e((string) $t['delete']) ?></button>
                        </form>
                    </article>
                    <?php } catch (Throwable $throwable) {
                        albums_admin_log_photo_render_failure($throwable, $photo, 'album_admin_photo_render_failed');
                        ?>
                        <article class="gallery-item">
                            <p class="help"><?= e((string) ($t['invalid_photo'] ?? 'Photo invalide.')) ?></p>
                        </article>
                    <?php } ?>
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
