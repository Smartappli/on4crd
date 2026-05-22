<?php
declare(strict_types=1);

require_permission('admin.access');
$user = current_user();
$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/admin_library.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, (string) $key);
}
set_page_meta(['title' => (string) $t['title'], 'description' => (string) $t['meta_desc']]);

try { db()->exec('CREATE TABLE IF NOT EXISTS member_library_categories (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(120) NOT NULL UNIQUE, label VARCHAR(160) NOT NULL, sort_order INT NOT NULL DEFAULT 100, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)'); } catch (Throwable) {}

if (!ensure_member_library_table()) {
    echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'upload');
    if ($action === 'add_category') {
        $code = trim((string) ($_POST['category_code'] ?? ''));
        if ($code !== '') { db()->prepare('INSERT IGNORE INTO member_library_categories (code, label) VALUES (?, ?)')->execute([$code, $code]); }
        redirect('admin_library');
    }
    if ($action === 'delete_category') {
        $code = trim((string) ($_POST['category_code'] ?? ''));
        if ($code !== '' && $code !== 'general') {
            db()->prepare('UPDATE member_library_documents SET category = "general" WHERE category = ?')->execute([$code]);
            db()->prepare('DELETE FROM member_library_categories WHERE code = ? LIMIT 1')->execute([$code]);
        }
        redirect('admin_library');
    }

    $category = trim((string) ($_POST['category'] ?? '')) ?: 'general';
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $file = $_FILES['pdf'] ?? null;
    if ($title === '' || !is_array($file)) { set_flash('error', (string) $t['err_required']); redirect('admin_library'); }

    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) { set_flash('error', (string) $t['err_upload']); redirect('admin_library'); }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 15 * 1024 * 1024) { set_flash('error', (string) $t['err_size']); redirect('admin_library'); }
    $tmpName = (string) ($file['tmp_name'] ?? '');
    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    $mime = '';
    if ($tmpName !== '') { if (class_exists('finfo')) { $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = (string) ($finfo->file($tmpName) ?: ''); } if ($mime === '') { $mime = (string) @mime_content_type($tmpName); } }
    $pdfSignature = '';
    if ($tmpName !== '' && is_readable($tmpName)) {
        $handle = fopen($tmpName, 'rb');
        if (is_resource($handle)) {
            $pdfSignature = (string) fread($handle, 5);
            fclose($handle);
        }
    }
    if ($extension !== 'pdf' || ($mime !== 'application/pdf' && $mime !== 'application/x-pdf') || $pdfSignature !== '%PDF-') { set_flash('error', (string) $t['err_invalid']); redirect('admin_library'); }

    try { $saved = secure_move_uploaded_file($file, dirname(__DIR__) . '/storage/uploads/library', 'doc_' . (int) ($user['id'] ?? 0), ['pdf'], ['application/pdf', 'application/x-pdf'], 15 * 1024 * 1024); }
    catch (Throwable) { set_flash('error', (string) $t['err_upload']); redirect('admin_library'); }

    $publicPath = 'storage/uploads/library/' . $saved;
    db()->prepare('INSERT INTO member_library_documents (member_id, category, title, description, file_path, extracted_text) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([(int) ($user['id'] ?? 0), $category, $title, $description, $publicPath, '']);
    set_flash('success', (string) $t['ok_added']);
    redirect('admin_library');
}

$categoryOptions = db()->query('SELECT code, label FROM member_library_categories ORDER BY sort_order ASC, label ASC')->fetchAll() ?: [];
if ($categoryOptions === []) {
    db()->prepare('INSERT IGNORE INTO member_library_categories (code, label, sort_order) VALUES (?, ?, ?)')->execute(['general', 'general', 1]);
    $categoryOptions = db()->query('SELECT code, label FROM member_library_categories ORDER BY sort_order ASC, label ASC')->fetchAll() ?: [];
}
$perPage = 20;
$page = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;
$totalDocuments = (int) (db()->query('SELECT COUNT(*) FROM member_library_documents')->fetchColumn() ?: 0);
$totalPages = max(1, (int) ceil($totalDocuments / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}
$stmt = db()->prepare('SELECT category, title, description, file_path FROM member_library_documents ORDER BY uploaded_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$documents = $stmt->fetchAll() ?: [];
$prevPage = $page > 1 ? $page - 1 : null;
$nextPage = $page < $totalPages ? $page + 1 : null;
ob_start();
?>
<div class="card admin-library-shell">
    <header class="admin-library-header">
        <h1><?= e((string) $t['title']) ?></h1>
        <p><?= e((string) $t['intro']) ?></p>
        <div class="admin-library-meta">
            <span class="badge muted"><?= e((string) $t['page']) ?> <?= e((string) $page) ?> / <?= e((string) $totalPages) ?></span>
            <span class="badge muted"><?= e((string) $totalDocuments) ?> docs</span>
        </div>
    </header>
    <form method="post" enctype="multipart/form-data" class="admin-library-upload-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="upload">
        <div class="admin-library-upload-grid">
            <label class="admin-library-field"><span><?= e((string) $t['category_ph']) ?></span><select name="category"><?php foreach ($categoryOptions as $catOpt): ?><option value="<?= e((string) $catOpt['code']) ?>"><?= e((string) $catOpt['label']) ?></option><?php endforeach; ?></select></label>
            <label class="admin-library-field"><span><?= e((string) $t['title_ph']) ?></span><input type="text" name="title" placeholder="<?= e((string) $t['title_ph']) ?>" required></label>
            <label class="admin-library-field admin-library-field-wide"><span><?= e((string) $t['desc_ph']) ?></span><textarea name="description" placeholder="<?= e((string) $t['desc_ph']) ?>"></textarea></label>
            <label class="admin-library-field"><span>PDF</span><input type="file" name="pdf" accept="application/pdf" required></label>
        </div>
        <button class="button"><?= e((string) $t['upload']) ?></button>
    </form>

    <section class="card admin-library-categories">
        <h2><?= e((string) $t['categories']) ?></h2>
        <form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add_category"><input type="text" name="category_code" placeholder="<?= e((string) $t['category_ph']) ?>"><button class="button" type="submit"><?= e((string) $t['add_category']) ?></button></form>
        <p class="help"><?= e((string) $t['existing_categories']) ?></p>
        <div class="admin-library-category-list">
            <?php foreach ($categoryOptions as $catOpt): ?>
                <form method="post" class="inline-form admin-library-category-item"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_category"><input type="hidden" name="category_code" value="<?= e((string) $catOpt['code']) ?>"><span class="badge muted"><?= e((string) $catOpt['label']) ?></span><?php if ((string) $catOpt['code'] !== 'general'): ?><button class="button secondary" type="submit"><?= e((string) $t['delete']) ?></button><?php endif; ?></form>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-library-documents">
    <?php if ($documents === []): ?>
        <article class="card admin-library-empty">
            <p><?= e((string) $t['intro']) ?></p>
        </article>
    <?php endif; ?>
    <?php foreach ($documents as $document): $safePath = safe_storage_public_path_or_null((string) ($document['file_path'] ?? ''), ['storage/uploads/library/']); if ($safePath === null) { continue; } ?>
        <article class="card admin-library-document">
            <p><span class="badge muted"><?= e((string) ($document['category'] ?? 'general')) ?></span></p>
            <h3><?= e((string) $document['title']) ?></h3>
            <p><?= e((string) ($document['description'] ?? '')) ?></p>
            <details class="admin-library-preview-toggle">
                <summary><?= e((string) $t['preview']) ?></summary>
                <iframe src="<?= e(base_url($safePath)) ?>" class="admin-library-pdf-preview" loading="lazy"></iframe>
            </details>
            <p><a class="button secondary" href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a></p>
        </article>
    <?php endforeach; ?>
    </section>
    <?php if ($totalPages > 1): ?>
        <nav class="admin-library-pagination" aria-label="Pagination documents">
            <?php if ($prevPage !== null): ?><a class="button secondary" href="<?= e(route_url('admin_library', ['p' => $prevPage])) ?>">&larr; <?= e((string) $t['prev']) ?></a><?php endif; ?>
            <span class="badge muted"><?= e((string) $t['page']) ?> <?= e((string) $page) ?> / <?= e((string) $totalPages) ?></span>
            <?php if ($nextPage !== null): ?><a class="button secondary" href="<?= e(route_url('admin_library', ['p' => $nextPage])) ?>"><?= e((string) $t['next']) ?> &rarr;</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);
