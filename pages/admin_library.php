<?php
declare(strict_types=1);

require_permission('admin.access');
$user = current_user();
$locale = current_locale();
$t = i18n_domain_locale('admin_library', $locale);
set_page_meta(['title' => (string) $t['title'], 'description' => (string) $t['meta_desc']]);

function library_category_slug(string $value): string
{
    $slug = slugify($value);
    return $slug !== '' ? $slug : 'general';
}

function ensure_member_library_categories_table(): void
{
    db()->exec('CREATE TABLE IF NOT EXISTS member_library_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(120) NOT NULL UNIQUE,
        label VARCHAR(160) NOT NULL,
        sort_order INT NOT NULL DEFAULT 100,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
    db()->prepare('INSERT IGNORE INTO member_library_categories (code, label, sort_order) VALUES (?, ?, ?)')->execute(['general', 'General', 1]);
}

function library_extract_text(string $path, string $extension): string
{
    $extension = strtolower($extension);
    if ($extension === 'pdf') {
        return article_extract_pdf_text($path);
    }
    if ($extension === 'docx') {
        return trim(strip_tags(article_extract_docx_html($path)));
    }
    $raw = @file_get_contents($path);
    if (!is_string($raw)) {
        return '';
    }
    if (in_array($extension, ['html', 'htm'], true)) {
        $raw = strip_tags($raw);
    }
    return trim((string) preg_replace('/\s+/u', ' ', $raw));
}

function library_store_upload(array $file, int $memberId): array
{
    $allowedExtensions = ['pdf', 'docx', 'txt', 'md', 'html', 'htm'];
    $allowedMimes = [
        'pdf' => ['application/pdf', 'application/x-pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'txt' => ['text/plain', 'application/octet-stream'],
        'md' => ['text/plain', 'text/markdown', 'application/octet-stream'],
        'html' => ['text/html', 'text/plain', 'application/octet-stream'],
        'htm' => ['text/html', 'text/plain', 'application/octet-stream'],
    ];

    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('err_upload');
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 25 * 1024 * 1024) {
        throw new RuntimeException('err_size');
    }
    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('err_upload');
    }
    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('err_invalid');
    }

    $mime = detect_uploaded_mime_type($tmpName);
    if (!in_array($mime, $allowedMimes[$extension] ?? [], true)) {
        throw new RuntimeException('err_invalid');
    }
    $signature = (string) @file_get_contents($tmpName, false, null, 0, 8);
    if ($extension === 'pdf' && !str_starts_with($signature, '%PDF-')) {
        throw new RuntimeException('err_invalid');
    }
    if ($extension === 'docx' && !str_starts_with($signature, "PK\x03\x04")) {
        throw new RuntimeException('err_invalid');
    }

    $targetDir = dirname(__DIR__) . '/storage/uploads/library';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        throw new RuntimeException('err_upload');
    }
    $base = slugify(pathinfo($originalName, PATHINFO_FILENAME));
    if ($base === '') {
        $base = 'document';
    }
    $filename = 'doc_' . $memberId . '-' . $base . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $targetDir . '/' . $filename;
    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('err_upload');
    }

    return [
        'public_path' => 'storage/uploads/library/' . $filename,
        'absolute_path' => $destination,
        'extension' => $extension,
        'original_name' => $originalName,
    ];
}

try {
    ensure_member_library_categories_table();
} catch (Throwable) {
}

if (!ensure_member_library_table()) {
    echo render_layout('<div class="card"><p>' . e((string) $t['storage_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'upload');
        if ($action === 'add_category') {
            $code = library_category_slug((string) ($_POST['category_code'] ?? ''));
            $label = trim((string) ($_POST['category_label'] ?? '')) ?: $code;
            db()->prepare('INSERT INTO member_library_categories (code, label) VALUES (?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')->execute([$code, $label]);
            set_flash('success', (string) $t['ok_category']);
            redirect('admin_library');
        }
        if ($action === 'delete_category') {
            $code = library_category_slug((string) ($_POST['category_code'] ?? ''));
            if ($code !== 'general') {
                db()->prepare('UPDATE member_library_documents SET category = "general" WHERE category = ?')->execute([$code]);
                db()->prepare('DELETE FROM member_library_categories WHERE code = ? LIMIT 1')->execute([$code]);
            }
            set_flash('success', (string) $t['ok_category_deleted']);
            redirect('admin_library');
        }
        if ($action === 'delete_document') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = db()->prepare('SELECT file_path FROM member_library_documents WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $path = (string) ($stmt->fetchColumn() ?: '');
            $safePath = safe_storage_public_path_or_null($path, ['storage/uploads/library/']);
            if ($safePath !== null) {
                $absolute = dirname(__DIR__) . '/' . $safePath;
                if (is_file($absolute)) {
                    @unlink($absolute);
                }
            }
            db()->prepare('DELETE FROM member_library_documents WHERE id = ?')->execute([$id]);
            set_flash('success', (string) $t['ok_deleted']);
            redirect('admin_library');
        }
        if ($action === 'bulk_delete_documents') {
            $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), static fn(int $v): bool => $v > 0));
            if ($ids === []) {
                throw new RuntimeException('err_required');
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sel = db()->prepare('SELECT file_path FROM member_library_documents WHERE id IN (' . $placeholders . ')');
            $sel->execute($ids);
            foreach ($sel->fetchAll() ?: [] as $row) {
                $safePath = safe_storage_public_path_or_null((string) ($row['file_path'] ?? ''), ['storage/uploads/library/']);
                if ($safePath !== null) {
                    $absolute = dirname(__DIR__) . '/' . $safePath;
                    if (is_file($absolute)) {
                        @unlink($absolute);
                    }
                }
            }
            db()->prepare('DELETE FROM member_library_documents WHERE id IN (' . $placeholders . ')')->execute($ids);
            set_flash('success', (string) $t['ok_deleted']);
            redirect('admin_library');
        }

        $category = library_category_slug((string) ($_POST['category'] ?? 'general'));
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $tags = trim((string) ($_POST['tags'] ?? ''));
        $tags = mb_safe_substr((string) preg_replace('/\s*,\s*/', ',', $tags), 0, 255);
        $file = $_FILES['document'] ?? null;
        if ($title === '' || !is_array($file)) {
            throw new RuntimeException('err_required');
        }
        $stored = library_store_upload($file, (int) ($user['id'] ?? 0));
        $extractedText = library_extract_text((string) $stored['absolute_path'], (string) $stored['extension']);

        db()->prepare('INSERT INTO member_library_documents (member_id, category, tags, title, description, file_path, extracted_text) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([(int) ($user['id'] ?? 0), $category, $tags, $title, $description, (string) $stored['public_path'], $extractedText]);
        set_flash('success', (string) $t['ok_added']);
        redirect('admin_library');
    } catch (Throwable $throwable) {
        $key = $throwable->getMessage();
        set_flash('error', (string) ($t[$key] ?? $key));
        redirect('admin_library');
    }
}

$categoryOptions = db()->query('SELECT code, label FROM member_library_categories ORDER BY sort_order ASC, label ASC')->fetchAll() ?: [];
$perPage = 20;
$page = max(1, (int) ($_GET['p'] ?? 1));
$adminCategory = library_category_slug((string) ($_GET['category'] ?? ''));
if ($adminCategory === 'general' && !isset($_GET['category'])) {
    $adminCategory = '';
}
$adminSearch = trim((string) ($_GET['q'] ?? ''));
$adminTag = trim((string) ($_GET['tag'] ?? ''));
$where = [];
$params = [];
if ($adminCategory !== '') {
    $where[] = 'category = ?';
    $params[] = $adminCategory;
}
if ($adminSearch !== '') {
    $where[] = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ?)';
    $needle = '%' . $adminSearch . '%';
    array_push($params, $needle, $needle, $needle);
}
if ($adminTag !== '') {
    $where[] = 'tags LIKE ?';
    $params[] = '%' . $adminTag . '%';
}
$whereSql = $where === [] ? '' : (' WHERE ' . implode(' AND ', $where));
$countStmt = db()->prepare('SELECT COUNT(*) FROM member_library_documents' . $whereSql);
$countStmt->execute($params);
$totalDocuments = (int) ($countStmt->fetchColumn() ?: 0);
$pagination = pagination_state($totalDocuments, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];
$stmt = db()->prepare('SELECT id, category, tags, title, description, file_path, extracted_text, uploaded_at FROM member_library_documents' . $whereSql . ' ORDER BY uploaded_at DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
$stmt->execute($params);
$documents = $stmt->fetchAll() ?: [];

ob_start();
?>
<div class="card admin-library-shell">
    <header class="admin-library-header">
        <h1><?= e((string) $t['title']) ?></h1>
        <p><?= e((string) $t['intro']) ?></p>
        <div class="admin-library-meta">
            <span class="badge muted"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $totalPages ?></span>
            <span class="badge muted"><?= $totalDocuments ?> <?= e((string) $t['documents']) ?></span>
        </div>
    </header>

    <form method="post" enctype="multipart/form-data" class="admin-library-upload-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="upload">
        <div class="admin-library-upload-grid">
            <label class="admin-library-field"><span><?= e((string) $t['category_ph']) ?></span><select name="category"><?php foreach ($categoryOptions as $catOpt): ?><option value="<?= e((string) $catOpt['code']) ?>"><?= e((string) $catOpt['label']) ?></option><?php endforeach; ?></select></label>
            <label class="admin-library-field"><span><?= e((string) $t['title_ph']) ?></span><input type="text" name="title" required></label>
            <label class="admin-library-field"><span><?= e((string) $t['tags_ph']) ?></span><input type="text" name="tags" placeholder="<?= e((string) $t['tags_help']) ?>"></label>
            <label class="admin-library-field admin-library-field-wide"><span><?= e((string) $t['desc_ph']) ?></span><textarea name="description"></textarea></label>
            <label class="admin-library-field"><span><?= e((string) $t['file']) ?></span><input type="file" name="document" accept=".pdf,.docx,.txt,.md,.html,.htm,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/html,text/markdown" required></label>
        </div>
        <p class="help"><?= e((string) $t['file_help']) ?></p>
        <button class="button"><?= e((string) $t['upload']) ?></button>
    </form>

    <section class="card admin-library-categories">
        <h2><?= e((string) $t['categories']) ?></h2>
        <form method="post" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_category">
            <input type="text" name="category_code" placeholder="<?= e((string) $t['category_code']) ?>">
            <input type="text" name="category_label" placeholder="<?= e((string) $t['category_label']) ?>">
            <button class="button" type="submit"><?= e((string) $t['add_category']) ?></button>
        </form>
        <div class="admin-library-category-list">
            <?php foreach ($categoryOptions as $catOpt): ?>
                <form method="post" class="inline-form admin-library-category-item">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="category_code" value="<?= e((string) $catOpt['code']) ?>">
                    <span class="badge muted"><?= e((string) $catOpt['label']) ?></span>
                    <?php if ((string) $catOpt['code'] !== 'general'): ?><button class="button secondary" type="submit"><?= e((string) $t['delete']) ?></button><?php endif; ?>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-library-documents">
    <form method="get" class="inline-form" style="margin-bottom:.8rem;flex-wrap:wrap;">
        <input type="hidden" name="route" value="admin_library">
        <select name="category">
            <option value=""><?= e((string) $t['all_categories']) ?></option>
            <?php foreach ($categoryOptions as $catOpt): ?>
                <option value="<?= e((string) $catOpt['code']) ?>" <?= $adminCategory === (string) $catOpt['code'] ? 'selected' : '' ?>><?= e((string) $catOpt['label']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="q" value="<?= e($adminSearch) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
        <input type="search" name="tag" value="<?= e($adminTag) ?>" placeholder="<?= e((string) $t['tag_search_ph']) ?>">
        <button class="button" type="submit"><?= e((string) $t['filter']) ?></button>
        <a class="button secondary" href="<?= e(route_url('admin_library')) ?>"><?= e((string) $t['reset']) ?></a>
    </form>
    <?php if ($documents === []): ?>
        <article class="card admin-library-empty"><p><?= e((string) $t['empty']) ?></p></article>
    <?php endif; ?>
    <?php if ($documents !== []): ?>
    <form method="post" id="bulk-delete-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="bulk_delete_documents">
        <p><button class="button secondary" type="submit" onclick="return confirm('<?= e((string) $t['confirm_delete']) ?>');"><?= e((string) $t['bulk_delete']) ?></button></p>
    </form>
    <?php endif; ?>
    <?php foreach ($documents as $document): ?>
        <?php $safePath = safe_storage_public_path_or_null((string) ($document['file_path'] ?? ''), ['storage/uploads/library/']); if ($safePath === null) { continue; } ?>
        <?php $extension = strtolower(pathinfo($safePath, PATHINFO_EXTENSION)); ?>
        <article class="card admin-library-document">
            <p><input type="checkbox" form="bulk-delete-form" name="ids[]" value="<?= (int) $document['id'] ?>"> <span class="help"><?= e((string) $t['select']) ?></span></p>
            <p><span class="badge muted"><?= e((string) ($document['category'] ?? 'general')) ?></span> <span class="badge muted"><?= e(strtoupper($extension)) ?></span></p>
            <h3><?= e((string) $document['title']) ?></h3>
            <p><?= e((string) ($document['description'] ?? '')) ?></p>
            <?php if (trim((string) ($document['tags'] ?? '')) !== ''): ?><p class="help"><?= e((string) $t['tags']) ?>: <?= e((string) $document['tags']) ?></p><?php endif; ?>
            <?php if (trim((string) ($document['extracted_text'] ?? '')) !== ''): ?><p class="help"><?= e(mb_safe_strimwidth((string) $document['extracted_text'], 0, 220, '...')) ?></p><?php endif; ?>
            <?php if ($extension === 'pdf'): ?>
                <details class="admin-library-preview-toggle"><summary><?= e((string) $t['preview']) ?></summary><iframe src="<?= e(base_url($safePath)) ?>" class="admin-library-pdf-preview" loading="lazy"></iframe></details>
            <?php endif; ?>
            <div class="actions">
                <a class="button secondary" href="<?= e(base_url($safePath)) ?>" target="_blank" rel="noopener"><?= e((string) $t['open']) ?></a>
                <form method="post" onsubmit="return confirm('<?= e((string) $t['confirm_delete']) ?>');">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete_document">
                    <input type="hidden" name="id" value="<?= (int) $document['id'] ?>">
                    <button class="button secondary" type="submit"><?= e((string) $t['delete_document']) ?></button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
    </section>
    <?php if ($totalPages > 1): ?>
        <nav class="admin-library-pagination" aria-label="Pagination documents">
            <?php if ($page > 1): ?><a class="button secondary" href="<?= e(route_url_clean('admin_library', ['category' => $adminCategory, 'q' => $adminSearch, 'tag' => $adminTag, 'p' => $page - 1])) ?>">&larr; <?= e((string) $t['prev']) ?></a><?php endif; ?>
            <span class="badge muted"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?><a class="button secondary" href="<?= e(route_url_clean('admin_library', ['category' => $adminCategory, 'q' => $adminSearch, 'tag' => $adminTag, 'p' => $page + 1])) ?>"><?= e((string) $t['next']) ?> &rarr;</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);
