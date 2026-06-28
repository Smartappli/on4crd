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
    foreach (album_photo_derived_public_paths($safePath) as $derivedPublic) {
        $derivedAbsolute = dirname(__DIR__) . '/' . $derivedPublic;
        if (is_file($derivedAbsolute)) {
            $ok = @unlink($derivedAbsolute) && $ok;
        }
    }
    return $ok;
}

/**
 * @param array<string, mixed> $photo
 * @param array<string, mixed> $messages
 * @return array{safe_path:?string,image_src:string,image_webp_src:string,display_webp_src:string,title:string,caption:string,album_title:string}
 */
function albums_admin_photo_render_data(array $photo, array $messages, string $logEvent): array
{
    $safePath = null;
    $imageSrc = '';
    $imageWebpSrc = '';
    $displayWebpSrc = '';

    try {
        $safePath = albums_admin_safe_photo_path((string) ($photo['file_path'] ?? ''));
        if ($safePath !== null) {
            $imageSrc = $safePath;
            $displayWebpSrc = album_existing_display_webp_public_path($safePath);
            $fallbackThumbPath = album_existing_thumbnail_fallback_public_path($safePath);
            if ($fallbackThumbPath !== '') {
                $imageSrc = $fallbackThumbPath;
                $imageWebpSrc = album_existing_thumbnail_webp_public_path($safePath);
            } else {
                $imageWebpSrc = $displayWebpSrc;
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
        $imageWebpSrc = '';
        $displayWebpSrc = '';
    }

    $title = trim((string) ($photo['title'] ?? ''));
    if ($title === '') {
        $title = (string) $messages['photo'];
    }

    return [
        'safe_path' => $safePath,
        'image_src' => $imageSrc,
        'image_webp_src' => $imageWebpSrc,
        'display_webp_src' => $displayWebpSrc,
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

function albums_admin_text(string $key): string
{
    static $messages = null;
    if ($messages === null) {
        $messages = function_exists('i18n_domain_locale') ? i18n_domain_locale('admin_albums', function_exists('current_locale') ? current_locale() : null) : [];
    }

    return (string) ($messages[$key] ?? $key);
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

function albums_admin_rebuild_batch_size(): int
{
    $configured = (int) (getenv('ALBUM_THUMBNAIL_REBUILD_BATCH_SIZE') ?: 12);

    return max(1, min(30, $configured));
}

function albums_admin_rebuild_session_key(): string
{
    return 'admin_albums_rebuild_thumbnails_v1';
}

function albums_admin_validate_text_lengths(string $title, string $description = '', string $caption = ''): void
{
    if (mb_strlen($title) > 190 || mb_strlen($description) > 10000 || mb_strlen($caption) > 5000) {
        throw new RuntimeException(albums_admin_text('error_field_too_long'));
    }
}

function albums_admin_post_checkbox(string $key, ?int $recordId = null, string ...$fallbackKeys): int
{
    foreach (array_merge([$key], $fallbackKeys) as $candidateKey) {
        if (!array_key_exists($candidateKey, $_POST)) {
            continue;
        }

        $value = $_POST[$candidateKey];
        if ($recordId !== null && is_array($value)) {
            $recordKey = (string) $recordId;
            if (!array_key_exists($recordKey, $value)) {
                return 0;
            }
            $value = $value[$recordKey];
        }

        $values = is_array($value) ? $value : [$value];
        foreach ($values as $singleValue) {
            if (!is_scalar($singleValue)) {
                continue;
            }
            if (in_array(strtolower(trim((string) $singleValue)), ['1', 'on', 'true', 'yes'], true)) {
                return 1;
            }
        }

        return 0;
    }

    return 0;
}

function albums_admin_post_form_checkbox(string $key, string $presenceKey, ?int $recordId = null, string ...$fallbackKeys): int
{
    if (array_key_exists($presenceKey, $_POST)) {
        return albums_admin_post_checkbox($key, $recordId);
    }

    return albums_admin_post_checkbox($key, $recordId, ...$fallbackKeys);
}

if (!albums_admin_tables_ready()) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['manage_title']) . '</h1><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['manage_title']);
    return;
}
if (!albums_admin_ensure_photo_order_column() || !album_ensure_schema_columns_and_indexes() || !album_ensure_source_proposal_column()) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['manage_title']) . '</h1><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['manage_title']);
    return;
}
album_sync_accepted_proposals();
$albumCategories = album_categories();
$albumSubcategoriesByCategory = album_subcategories_by_category();
$featuredAlbumLabel = (string) $t['featured_album'];

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
                throw new RuntimeException((string) $t['invalid_album']);
            }
            $subCountStmt = db()->prepare('SELECT COUNT(*) FROM album_subcategories WHERE category_code = ?');
            $subCountStmt->execute([$category]);
            if ((int) $subCountStmt->fetchColumn() > 0) {
                throw new RuntimeException((string) $t['err_category_has_subcategories']);
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
                throw new RuntimeException((string) $t['err_subcategory_has_documents']);
            }
            db()->prepare('DELETE FROM album_subcategories WHERE category_code = ? AND code = ?')->execute([$category, $subcategory]);
            album_clear_caches();
            set_flash('success', (string) $t['album_deleted_ok']);
            redirect('admin_albums');
        }

        if ($action === 'create_album') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $isPublic = albums_admin_post_checkbox('is_public');
            $isFeatured = albums_admin_post_form_checkbox('album_is_featured', 'album_is_featured_present', null, 'is_featured');
            [$category, $subcategory] = album_taxonomy_from_input(
                (string) ($_POST['category'] ?? 'general'),
                trim((string) ($_POST['subcategory_ref'] ?? '')),
                $albumCategories
            );
            albums_admin_validate_text_lengths($title, $description);
            if ($title === '') {
                throw new RuntimeException((string) $t['title_required']);
            }
            db()->prepare('INSERT INTO albums (member_id, category, subcategory, title, description, is_public, is_featured, publish_requested) VALUES (?, ?, ?, ?, ?, 0, ?, ?)')
                ->execute([(int) (current_user()['id'] ?? 0), $category, $subcategory, $title, $description, $isFeatured, $isPublic]);
            $albumId = (int) db()->lastInsertId();
            album_clear_caches();
            set_flash('success', (string) $t['album_created_continue']);
            redirect_url(album_admin_wizard_url(['album_wizard' => $albumId, 'step' => 2]));
        }

        if ($action === 'update_album') {
            $albumId = (int) ($_POST['album_id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $isPublic = albums_admin_post_checkbox('is_public');
            $isFeatured = albums_admin_post_form_checkbox('album_is_featured', 'album_is_featured_present', null, 'is_featured');
            [$category, $subcategory] = album_taxonomy_from_input(
                (string) ($_POST['category'] ?? 'general'),
                trim((string) ($_POST['subcategory_ref'] ?? '')),
                $albumCategories
            );
            albums_admin_validate_text_lengths($title, $description);
            if ($albumId <= 0 || $title === '') {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            $albumStmt = db()->prepare('SELECT id FROM albums WHERE id = ? LIMIT 1');
            $albumStmt->execute([$albumId]);
            if (!$albumStmt->fetchColumn()) {
                throw new RuntimeException((string) $t['invalid_album']);
            }
            album_update_record($albumId, $title, $description, $isPublic, $category, $subcategory, $isFeatured);
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
            if (count($uploadBatch) > album_upload_batch_max_files()) {
                throw new RuntimeException((string) $t['batch_max_files']);
            }
            $totalBytes = array_sum(array_map(static fn(array $item): int => max(0, (int) ($item['size'] ?? 0)), $uploadBatch));
            if ($totalBytes > album_upload_batch_max_bytes()) {
                throw new RuntimeException((string) $t['batch_max_size']);
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
            notify_member(
                (int) current_user()['id'],
                'import',
                (string) $t['notification_import_completed_title'],
                sprintf((string) $t['notification_import_completed_body'], $importedCount),
                route_url('admin_albums')
            );
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
                throw new RuntimeException((string) $t['wizard_no_photos']);
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
                set_flash('warning', (string) $t['album_finalized_social_warning']);
            } else {
                set_flash('success', (string) $t['album_finalized_ok']);
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
            $sessionKey = albums_admin_rebuild_session_key();
            $batchSize = albums_admin_rebuild_batch_size();
            $total = (int) (db()->query('SELECT COUNT(*) FROM album_photos')?->fetchColumn() ?: 0);
            $progress = $_SESSION[$sessionKey] ?? null;
            if (
                !is_array($progress)
                || (int) ($progress['cursor'] ?? 0) <= 0
                || time() - (int) ($progress['started_at'] ?? 0) > 3600
            ) {
                $maxId = (int) (db()->query('SELECT COALESCE(MAX(id), 0) FROM album_photos')?->fetchColumn() ?: 0);
                $progress = [
                    'cursor' => $maxId + 1,
                    'processed' => 0,
                    'created' => 0,
                    'total' => $total,
                    'started_at' => time(),
                    'auto_continue' => true,
                ];
            }

            $cursor = max(1, (int) ($progress['cursor'] ?? 1));
            $photoStmt = db()->prepare('SELECT id, file_path FROM album_photos WHERE id < ? ORDER BY id DESC LIMIT ' . $batchSize);
            $photoStmt->execute([$cursor]);
            $photoRows = $photoStmt->fetchAll() ?: [];
            $created = 0;
            $processed = 0;
            $lastId = $cursor;
            foreach ($photoRows as $photoRow) {
                $lastId = max(0, (int) ($photoRow['id'] ?? 0));
                $processed++;
                $safePath = albums_admin_safe_photo_path((string) ($photoRow['file_path'] ?? ''));
                if ($safePath === null) {
                    continue;
                }
                $thumbPath = create_album_thumbnail($safePath, 640, 640);
                $pngThumbPath = create_album_png_thumbnail($safePath, 640, 640);
                $webpPaths = create_album_webp_derivatives($safePath);
                if ($thumbPath !== null || $pngThumbPath !== null || $webpPaths['thumbnail'] !== null || $webpPaths['display'] !== null) {
                    $created++;
                }
            }

            $progress['cursor'] = $photoRows !== [] ? $lastId : 0;
            $progress['processed'] = (int) ($progress['processed'] ?? 0) + $processed;
            $progress['created'] = (int) ($progress['created'] ?? 0) + $created;
            $progress['total'] = $total;
            $remaining = 0;
            if ((int) $progress['cursor'] > 0) {
                $remainingStmt = db()->prepare('SELECT COUNT(*) FROM album_photos WHERE id < ?');
                $remainingStmt->execute([(int) $progress['cursor']]);
                $remaining = (int) ($remainingStmt->fetchColumn() ?: 0);
            }
            albums_admin_clear_cache();
            if ($remaining > 0) {
                $progress['auto_continue'] = true;
                $_SESSION[$sessionKey] = $progress;
                set_flash(
                    'success',
                    $created . ' ' . (string) $t['created_thumbs']
                    . ' ' . min($total, (int) $progress['processed']) . '/' . $total
                    . '.'
                );
            } else {
                unset($_SESSION[$sessionKey]);
                set_flash('success', (int) $progress['created'] . ' ' . (string) $t['created_thumbs']);
            }
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

$albums = db()->query(
    'SELECT a.*, COUNT(p.id) AS photo_count, MAX(p.created_at) AS last_photo_at
     FROM albums a
     LEFT JOIN album_photos p ON p.album_id = a.id
     GROUP BY a.id
     ORDER BY a.created_at DESC'
)->fetchAll() ?: [];
$albumsCount = count($albums);
$publicCount = 0;
$featuredCount = 0;
$totalPhotos = 0;
foreach ($albums as $albumRow) {
    $publicCount += (int) $albumRow['is_public'] === 1 ? 1 : 0;
    $featuredCount += (int) ($albumRow['is_featured'] ?? 0) === 1 ? 1 : 0;
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
$thumbnailRebuildProgress = $_SESSION[albums_admin_rebuild_session_key()] ?? null;
$thumbnailRebuildActive = is_array($thumbnailRebuildProgress)
    && (bool) ($thumbnailRebuildProgress['auto_continue'] ?? false)
    && (int) ($thumbnailRebuildProgress['cursor'] ?? 0) > 0
    && time() - (int) ($thumbnailRebuildProgress['started_at'] ?? 0) <= 3600;
$thumbnailRebuildTotal = $thumbnailRebuildActive ? max(0, (int) ($thumbnailRebuildProgress['total'] ?? $photosTotal)) : 0;
$thumbnailRebuildProcessed = $thumbnailRebuildActive ? min($thumbnailRebuildTotal, max(0, (int) ($thumbnailRebuildProgress['processed'] ?? 0))) : 0;
$thumbnailRebuildLabel = $thumbnailRebuildActive && $thumbnailRebuildTotal > 0
    ? $thumbnailRebuildProcessed . ' / ' . $thumbnailRebuildTotal
    : '';

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
<div class="admin-albums-module">
    <nav class="admin-albums-quick-nav" aria-label="<?= e((string) $t['manage_title']) ?>">
        <a href="#admin-album-taxonomy"><?= e((string) $t['category_field']) ?></a>
        <a href="#album-wizard"><?= e((string) $t['wizard_title']) ?></a>
        <a href="#admin-album-upload"><?= e((string) $t['add_photo']) ?></a>
        <a href="#admin-album-list"><?= e((string) $t['edit_albums']) ?></a>
        <a href="#admin-album-photos"><?= e((string) $t['photos_editor']) ?></a>
    </nav>
    <section class="card gallery-header admin-albums-overview">
        <div class="admin-albums-overview-head">
            <div>
                <h1><?= e((string) $t['manage_title']) ?></h1>
                <p class="help"><?= e((string) $t['intro']) ?></p>
            </div>
            <form method="post" class="admin-albums-rebuild-form" data-admin-album-rebuild-form<?= $thumbnailRebuildActive ? ' data-auto-continue="1"' : '' ?>>
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="rebuild_thumbnails">
                <button class="button secondary small" type="submit"><?= e((string) $t['rebuild_thumbs']) ?></button>
                <?php if ($thumbnailRebuildLabel !== ''): ?>
                    <span class="pill" data-admin-album-rebuild-progress><?= e($thumbnailRebuildLabel) ?></span>
                <?php endif; ?>
            </form>
        </div>
        <div class="stats-grid">
            <article class="stat-card"><span class="help"><?= e((string) $t['albums']) ?></span><strong><?= $albumsCount ?></strong></article>
            <article class="stat-card"><span class="help"><?= e((string) $t['public_albums']) ?></span><strong><?= $publicCount ?></strong></article>
            <article class="stat-card"><span class="help"><?= e($featuredAlbumLabel) ?></span><strong><?= $featuredCount ?></strong></article>
            <article class="stat-card"><span class="help"><?= e((string) $t['photos']) ?></span><strong><?= $totalPhotos ?></strong></article>
        </div>
    </section>

    <section class="card admin-album-taxonomy-card" id="admin-album-taxonomy">
        <div class="admin-albums-section-head">
            <div>
                <h2><?= e((string) $t['category_field']) ?></h2>
                <p class="help"><?= count($albumCategories) ?> <?= e((string) $t['category_field']) ?></p>
            </div>
        </div>
        <div class="admin-album-taxonomy-create-grid">
            <form method="post" class="admin-album-taxonomy-create-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_category">
                <h3><?= e((string) $t['add_category']) ?></h3>
                <label><?= e((string) $t['category_field']) ?>
                    <input type="text" name="category_label" maxlength="160" required>
                </label>
                <button class="button"><?= e((string) $t['add_category']) ?></button>
            </form>
            <form method="post" class="admin-album-taxonomy-create-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_subcategory">
                <h3><?= e((string) $t['add_subcategory']) ?></h3>
                <label><?= e((string) $t['category_field']) ?>
                    <select name="subcategory_category">
                        <?php foreach ($albumCategories as $code => $label): ?>
                            <option value="<?= e((string) $code) ?>"><?= e((string) $label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?= e((string) $t['subcategory_field']) ?>
                    <input type="text" name="subcategory_label" maxlength="160" required>
                </label>
                <button class="button"><?= e((string) $t['add_subcategory']) ?></button>
            </form>
        </div>
        <div class="admin-album-taxonomy-manage-grid">
            <section class="admin-album-taxonomy-group" aria-labelledby="admin-album-taxonomy-categories-title">
                <div class="admin-album-taxonomy-group-head">
                    <h3 id="admin-album-taxonomy-categories-title"><?= e((string) $t['category_field']) ?></h3>
                    <span class="badge muted"><?= count($albumCategories) ?></span>
                </div>
            <?php foreach ($albumCategories as $code => $label): ?>
                <?php $categoryTotal = (int) ($albumCategoryCounts[(string) $code] ?? 0); ?>
                <?php $subcategoryTotal = count($albumSubcategoriesByCategory[(string) $code] ?? []); ?>
                <?php $categoryDeleteDisabled = (string) $code === 'general' || $subcategoryTotal > 0; ?>
                <form method="post" class="inline-form admin-album-taxonomy-row">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" name="category_code" value="<?= e((string) $code) ?>">
                    <span class="pill taxonomy-pill-category admin-album-taxonomy-code"><?= e((string) $code) ?> (<?= $categoryTotal ?>)</span>
                    <input type="text" name="category_label" value="<?= e((string) $label) ?>" maxlength="160" required>
                    <span class="admin-album-taxonomy-actions">
                        <button class="button small" type="submit"><?= e((string) $t['save']) ?></button>
                        <button class="button secondary small" type="submit" name="action" value="delete_category"<?= $categoryDeleteDisabled ? ' disabled' : '' ?>><?= e((string) $t['delete']) ?></button>
                    </span>
                </form>
            <?php endforeach; ?>
            </section>
            <section class="admin-album-taxonomy-group" aria-labelledby="admin-album-taxonomy-subcategories-title">
                <div class="admin-album-taxonomy-group-head">
                    <h3 id="admin-album-taxonomy-subcategories-title"><?= e((string) $t['subcategory_field']) ?></h3>
                    <span class="badge muted"><?= array_sum(array_map('count', $albumSubcategoriesByCategory)) ?></span>
                </div>
            <?php foreach ($albumSubcategoriesByCategory as $parentCode => $subcategories): ?>
                <?php foreach ($subcategories as $subcategoryInfo): ?>
                    <?php $subCode = album_subcategory_code((string) ($subcategoryInfo['code'] ?? '')); ?>
                    <?php if ($subCode === '') { continue; } ?>
                    <?php $subTotal = (int) ($albumSubcategoryCounts[(string) $parentCode . ':' . $subCode] ?? 0); ?>
                    <form method="post" class="inline-form admin-album-taxonomy-row">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update_subcategory">
                        <input type="hidden" name="subcategory_ref" value="<?= e(album_subcategory_ref((string) $parentCode, $subCode)) ?>">
                        <span class="taxonomy-badge-row admin-album-taxonomy-code">
                            <span class="badge muted taxonomy-pill-category"><?= e((string) ($albumCategories[(string) $parentCode] ?? $parentCode)) ?></span>
                            <span class="badge muted taxonomy-pill-subcategory"><?= e($subCode) ?></span>
                            <span class="badge muted"><?= $subTotal ?></span>
                        </span>
                        <input type="text" name="subcategory_label" value="<?= e((string) ($subcategoryInfo['label'] ?? $subCode)) ?>" maxlength="160" required>
                        <span class="admin-album-taxonomy-actions">
                            <button class="button small" type="submit"><?= e((string) $t['save']) ?></button>
                            <button class="button secondary small" type="submit" name="action" value="delete_subcategory"<?= $subTotal > 0 ? ' disabled' : '' ?>><?= e((string) $t['delete']) ?></button>
                        </span>
                    </form>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </section>
        </div>
    </section>

    <div class="admin-albums-workspace">
        <section class="card album-wizard admin-album-wizard-card" id="album-wizard">
            <h2><?= e((string) $t['wizard_title']) ?></h2>
            <div class="actions admin-album-wizard-steps" aria-label="<?= e((string) $t['wizard_title']) ?>">
                <span class="pill<?= $wizardStep === 1 ? ' is-active' : '' ?>">1. <?= e((string) $t['wizard_step_details']) ?></span>
                <span class="pill<?= $wizardStep === 2 ? ' is-active' : '' ?>">2. <?= e((string) $t['wizard_step_upload']) ?></span>
                <span class="pill<?= $wizardStep === 3 ? ' is-active' : '' ?>">3. <?= e((string) $t['wizard_step_review']) ?></span>
            </div>
            <?php if ($wizardStep === 1): ?>
                <form method="post" class="stack admin-album-wizard-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create_album">
                    <label><?= e((string) $t['title']) ?>
                        <input type="text" name="title" required maxlength="190">
                    </label>
                    <?= render_album_taxonomy_fields($albumCategories, $t) ?>
                    <label><?= e((string) $t['description']) ?>
                        <textarea name="description" rows="4"></textarea>
                    </label>
                    <input type="hidden" name="is_public" value="0">
                    <label><input type="checkbox" name="is_public" value="1"> <?= e((string) $t['public_album']) ?></label>
                    <input type="hidden" name="album_is_featured_present" value="1">
                    <input type="hidden" name="album_is_featured[]" value="0">
                    <label><input type="checkbox" name="album_is_featured[]" value="1" autocomplete="off"> <?= e($featuredAlbumLabel) ?></label>
                    <p class="help"><?= e((string) $t['wizard_private_help']) ?></p>
                    <button class="button"><?= e((string) $t['wizard_continue_upload']) ?></button>
                </form>
            <?php elseif ($wizardStep === 2 && is_array($wizardAlbum)): ?>
                <h3><?= e((string) ($wizardAlbum['title'] ?? $t['create_album'])) ?></h3>
                <form method="post" enctype="multipart/form-data" class="stack admin-album-wizard-form">
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
                        <input id="album-wizard-photos-input" class="admin-album-file-input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
                    </label>
                    <p class="help"><?= e((string) $t['upload_help']) ?></p>
                    <div class="actions">
                        <button class="button"><?= e((string) $t['upload']) ?></button>
                        <a class="button secondary" href="<?= e(album_admin_wizard_url(['album_wizard' => $wizardAlbumId, 'step' => 3])) ?>"><?= e((string) $t['wizard_review_now']) ?></a>
                    </div>
                </form>
            <?php elseif ($wizardStep === 3 && is_array($wizardAlbum)): ?>
                <h3><?= e((string) ($wizardAlbum['title'] ?? $t['create_album'])) ?></h3>
                <?php if ($wizardPhotos === []): ?>
                    <p class="help"><?= e((string) $t['wizard_no_photos']) ?></p>
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
                                    <?= album_picture_html($imageSrc, $photoRender['title'], ['loading' => 'lazy', 'decoding' => 'async'], $photoRender['image_webp_src']) ?>
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
                                    <p class="help"><?= e((string) $t['invalid_photo']) ?></p>
                                </article>
                            <?php } ?>
                        <?php endforeach; ?>
                    </div>
                    <form method="post" class="actions mt-3">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="finalize_album_creation">
                        <input type="hidden" name="album_id" value="<?= (int) $wizardAlbumId ?>">
                        <a class="button secondary" href="<?= e(album_admin_wizard_url(['album_wizard' => $wizardAlbumId, 'step' => 2])) ?>"><?= e((string) $t['upload']) ?></a>
                        <button class="button" type="submit"><?= e((string) $t['wizard_finalize']) ?></button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="card admin-album-upload-card" id="admin-album-upload">
            <h2><?= e((string) $t['add_photo']) ?></h2>
            <form method="post" enctype="multipart/form-data" class="admin-album-upload-form">
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
                    <input id="album-photos-input" class="admin-album-file-input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
                </label>
                <p class="help"><?= e((string) $t['upload_help']) ?></p>
                <button class="button"><?= e((string) $t['upload']) ?></button>
            </form>
        </section>
    </div>

    <section class="card admin-album-list-card" id="admin-album-list">
        <div class="admin-albums-section-head">
            <div>
                <h2><?= e((string) $t['edit_albums']) ?></h2>
                <p class="help"><?= $albumsCount ?> <?= e((string) $t['albums']) ?></p>
            </div>
        </div>
        <?php if ($albums === []): ?>
            <p class="help"><?= e((string) $t['no_albums']) ?></p>
        <?php else: ?>
            <div class="admin-album-list">
                <?php foreach ($albums as $album): ?>
                    <?php
                    $albumId = (int) $album['id'];
                    $albumEditFormId = 'admin-album-edit-form-' . $albumId;
                    $albumCategoryCode = album_category_code((string) ($album['category'] ?? 'general'));
                    $albumSubcategoryCode = album_subcategory_code((string) ($album['subcategory'] ?? ''));
                    $albumCategoryLabel = (string) ($albumCategories[$albumCategoryCode] ?? album_category_label_from_code($albumCategoryCode));
                    $albumSubcategoryLabel = $albumSubcategoryCode !== '' ? album_category_label_from_code($albumSubcategoryCode) : '';
                    foreach ($albumSubcategoriesByCategory[$albumCategoryCode] ?? [] as $subcategoryInfo) {
                        if (album_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $albumSubcategoryCode) {
                            $albumSubcategoryLabel = (string) ($subcategoryInfo['label'] ?? $albumSubcategoryLabel);
                            break;
                        }
                    }
                    ?>
                    <article class="article-item admin-album-list-item">
                        <form id="<?= e($albumEditFormId) ?>" method="post" action="<?= e(route_url('admin_albums')) ?>" class="grid-2 admin-album-edit-form" autocomplete="off">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_album">
                            <input type="hidden" name="album_id" value="<?= $albumId ?>">
                            <label><?= e((string) $t['title']) ?>
                                <input type="text" name="title" value="<?= e((string) $album['title']) ?>" required maxlength="190">
                            </label>
                            <input type="hidden" name="is_public" value="0">
                            <label><input type="checkbox" name="is_public" value="1" <?= (int) $album['is_public'] === 1 ? 'checked' : '' ?>> <?= e((string) $t['public_album']) ?></label>
                            <input type="hidden" name="album_is_featured_present" value="1">
                            <input type="hidden" name="album_is_featured[]" value="0">
                            <label><input type="checkbox" name="album_is_featured[]" value="1" autocomplete="off" <?= (int) ($album['is_featured'] ?? 0) === 1 ? 'checked' : '' ?>> <?= e($featuredAlbumLabel) ?></label>
                            <div class="taxonomy-badge-row admin-album-taxonomy">
                                <span class="badge muted taxonomy-pill-category"><?= e($albumCategoryLabel) ?></span>
                                <?php if ($albumSubcategoryLabel !== ''): ?><span class="badge muted taxonomy-pill-subcategory"><?= e($albumSubcategoryLabel) ?></span><?php endif; ?>
                            </div>
                            <div class="admin-album-form-wide">
                                <?= render_album_taxonomy_fields($albumCategories, $t, (string) ($album['category'] ?? 'general'), (string) ($album['subcategory'] ?? '')) ?>
                            </div>
                            <label class="admin-album-form-wide"><?= e((string) $t['description']) ?>
                                <textarea name="description" rows="3"><?= e((string) ($album['description'] ?? '')) ?></textarea>
                            </label>
                            <p class="help"><?= (int) $album['photo_count'] ?> <?= e((string) $t['photos']) ?> · <?= e((string) $t['created_at']) ?> <?= e((string) $album['created_at']) ?></p>
                            <div class="actions admin-album-form-wide admin-album-row-actions">
                                <button class="button small" type="submit" data-admin-album-save><?= e((string) $t['save']) ?></button>
                                <span class="pill"><?= e((string) $t['public_album']) ?>: <?= (int) $album['is_public'] === 1 ? e((string) $t['yes']) : e((string) $t['no']) ?></span>
                                <span class="pill"><?= e($featuredAlbumLabel) ?>: <?= (int) ($album['is_featured'] ?? 0) === 1 ? e((string) $t['yes']) : e((string) $t['no']) ?></span>
                                <a class="button secondary small" href="<?= e(route_url('album', ['id' => $albumId])) ?>"><?= e((string) $t['view_public']) ?></a>
                            </div>
                        </form>
                        <form method="post" class="admin-album-delete-form" onsubmit="return confirm(<?= e(json_encode((string) $t['confirm_delete_album'], JSON_UNESCAPED_UNICODE)) ?>)">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_album">
                            <input type="hidden" name="album_id" value="<?= $albumId ?>">
                            <button class="button small secondary" type="submit"><?= e((string) $t['delete_album']) ?></button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card admin-album-photos-card" id="admin-album-photos">
        <div class="admin-albums-section-head">
            <div>
                <h2><?= e((string) $t['photos_editor']) ?></h2>
                <p class="help"><?= $totalPhotos ?> <?= e((string) $t['photos']) ?></p>
            </div>
        </div>
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
                    <article class="gallery-item admin-album-photo-item">
                        <?php if ($imageSrc !== ''): ?>
                            <?= album_picture_html($imageSrc, $photoRender['title'], ['loading' => 'lazy', 'decoding' => 'async'], $photoRender['image_webp_src']) ?>
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
                        <form method="post" class="inline-form admin-album-photo-order-form">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="reorder_photo">
                            <input type="hidden" name="photo_id" value="<?= (int) ($photoRow['id'] ?? 0) ?>">
                            <button class="button small secondary" type="submit" name="direction" value="up">&uarr;</button>
                            <button class="button small secondary" type="submit" name="direction" value="down">&darr;</button>
                        </form>
                        <form method="post" class="admin-album-photo-delete-form" onsubmit="return confirm(<?= e(albums_admin_js_string((string) $t['confirm_delete_photo'])) ?>)">
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
                            <p class="help"><?= e((string) $t['invalid_photo']) ?></p>
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
