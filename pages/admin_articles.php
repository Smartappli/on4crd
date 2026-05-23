<?php
declare(strict_types=1);

require_permission('articles.manage');
$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/admin_articles.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key, string $fallback = '') use ($locale, $i18n): string {
    $value = i18n_localized_value($i18n, $locale, $key);
    return trim($value) !== '' && $value !== $key ? $value : $fallback;
};
set_page_meta([
    'title' => $t('layout'),
    'description' => $t('meta_desc'),
    'schema_type' => 'WebPage',
]);
articles_sync_scheduled_publications();

/**
 * @return array{excerpt:string,content:string}
 */
function import_article_document(array $file): array
{
    $locale = current_locale();
    $tm = i18n_domain_translator('admin_articles_import', $locale);
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['excerpt' => '', 'content' => ''];
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException($tm('upload_failed'));
    }

    $originalName = trim((string) ($file['name'] ?? 'document'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'docx', 'txt', 'md', 'html', 'htm'];
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException($tm('allowed_formats'));
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 25 * 1024 * 1024) {
        throw new RuntimeException($tm('upload_failed'));
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException($tm('invalid_doc'));
    }

    $allowedMimesByExtension = [
        'pdf' => ['application/pdf', 'application/x-pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'txt' => ['text/plain', 'application/octet-stream'],
        'md' => ['text/plain', 'text/markdown', 'application/octet-stream'],
        'html' => ['text/html', 'text/plain', 'application/octet-stream'],
        'htm' => ['text/html', 'text/plain', 'application/octet-stream'],
    ];
    $mime = detect_uploaded_mime_type($tmpPath);
    if (!in_array($mime, $allowedMimesByExtension[$extension] ?? [], true)) {
        throw new RuntimeException($tm('invalid_doc'));
    }
    if ($extension === 'pdf' || $extension === 'docx') {
        assert_upload_file_is_valid_signature($tmpPath, [$extension]);
    }

    $targetDir = __DIR__ . '/../storage/private/articles';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        throw new RuntimeException($tm('create_dir'));
    }

    $basename = slugify(pathinfo($originalName, PATHINFO_FILENAME));
    if ($basename === '') {
        $basename = 'article';
    }
    $filename = $basename . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $absolutePath = $targetDir . '/' . $filename;
    if (!move_uploaded_file($tmpPath, $absolutePath)) {
        throw new RuntimeException($tm('save_doc'));
    }

    $documentTitle = trim((string) pathinfo($originalName, PATHINFO_FILENAME));
    $sourceLabel = '<p class="help article-source-document">' . e($tm('source_file')) . ': ' . e($originalName) . '</p>';
    if (in_array($extension, ['txt', 'md'], true)) {
        $rawText = (string) file_get_contents($absolutePath);
        $content = article_import_text_to_html($rawText);
    } elseif (in_array($extension, ['html', 'htm'], true)) {
        $rawHtml = (string) file_get_contents($absolutePath);
        $content = sanitize_rich_html($rawHtml);
    } elseif ($extension === 'docx') {
        $content = article_extract_docx_html($absolutePath);
        if ($content === '') {
            $content = '<div class="article-document"><p>' . e($tm('docx_extraction_unavailable')) . '</p></div>';
        }
    } elseif ($extension === 'pdf') {
        $rawText = article_extract_pdf_text($absolutePath);
        $content = article_import_text_to_html($rawText);
        if ($content === '') {
            $content = '<div class="article-document"><p>' . e($tm('pdf_extraction_unavailable')) . '</p></div>';
        }
    } else {
        $content = '';
    }
    $content = trim($content);
    if ($content !== '') {
        $content .= "\n" . $sourceLabel;
    }

    return [
        'excerpt' => $tm('imported_doc') . ' ' . $documentTitle,
        'content' => $content,
    ];
}

function article_unique_slug(string $slug, int $ignoreId = 0): string
{
    $base = slugify($slug);
    if ($base === '' || $base === 'n-a') {
        $base = 'article';
    }

    $candidate = $base;
    $suffix = 2;
    while (true) {
        $stmt = db()->prepare('SELECT id FROM articles WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->execute([$candidate, $ignoreId]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
        $candidate = $base . '-' . $suffix;
        $suffix++;
    }
}

$defaultCategories = [
    'antennes' => $t('cat_antennes'),
    'trafic' => $t('cat_trafic'),
    'numerique' => $t('cat_numerique'),
    'materiel' => $t('cat_materiel'),
    'formation' => $t('cat_formation'),
    'autres' => $t('cat_autres'),
];

$existingCategoryRows = db()->query('SELECT DISTINCT category FROM articles WHERE category IS NOT NULL AND category <> "" ORDER BY category ASC')->fetchAll();
$knownCategories = $defaultCategories;
foreach ($existingCategoryRows as $existingCategoryRow) {
    $code = trim((string) ($existingCategoryRow['category'] ?? ''));
    if ($code !== '' && !isset($knownCategories[$code])) {
        $knownCategories[$code] = ucwords(str_replace('-', ' ', $code));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save_article') {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $slugInput = trim((string) ($_POST['slug'] ?? ''));
            $slug = article_unique_slug($slugInput !== '' ? $slugInput : $title, $id);
            $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
            $content = sanitize_rich_html((string) ($_POST['content'] ?? ''));
            $status = (string) ($_POST['status'] ?? 'draft');
            $categoryChoice = trim((string) ($_POST['category'] ?? 'autres'));
            $customCategory = slugify(trim((string) ($_POST['category_custom'] ?? '')));
            $category = $categoryChoice === '__custom__' ? $customCategory : slugify($categoryChoice);
            if ($category === '') {
                $category = 'autres';
            }
            if ($title === '' || !in_array($status, ['draft', 'scheduled', 'published'], true)) {
                throw new RuntimeException($t('err_invalid_article', 'Article invalide.'));
            }
            $scheduledAtRaw = trim((string) ($_POST['scheduled_at'] ?? ''));
            $scheduledAtValue = null;
            $publishedAtValue = null;
            if ($status === 'scheduled') {
                if ($scheduledAtRaw === '') {
                    throw new RuntimeException($t('err_invalid_article', 'Date de planification requise.'));
                }
                $scheduledTs = strtotime($scheduledAtRaw);
                if ($scheduledTs === false) {
                    throw new RuntimeException($t('err_invalid_article', 'Date de planification invalide.'));
                }
                if ($scheduledTs <= time()) {
                    $status = 'published';
                    $publishedAtValue = date('Y-m-d H:i:s');
                } else {
                    $scheduledAtValue = date('Y-m-d H:i:s', $scheduledTs);
                }
            } elseif ($status === 'published') {
                $publishedAtValue = date('Y-m-d H:i:s');
            }
            $imported = import_article_document($_FILES['article_document'] ?? []);
            if ($imported['content'] !== '') {
                $content = $imported['content'];
                if ($excerpt === '') {
                    $excerpt = $imported['excerpt'];
                }
            }
            if ($id > 0) {
                db()->prepare('UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, status = ?, category = ?, scheduled_at = ?, published_at = COALESCE(?, published_at) WHERE id = ?')->execute([$title, $slug, $excerpt, $content, $status, $category, $scheduledAtValue, $publishedAtValue, $id]);
            } else {
                db()->prepare('INSERT INTO articles (title, slug, excerpt, content, status, category, scheduled_at, published_at, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$title, $slug, $excerpt, $content, $status, $category, $scheduledAtValue, $publishedAtValue, (int) current_user()['id']]);
                $id = (int) db()->lastInsertId();
            }
            article_translation_upsert($id, 'en');
            article_translation_upsert($id, 'de');
            article_translation_upsert($id, 'nl');
            set_flash('success', $t('ok_saved'));
            redirect('admin_articles');
        } elseif ($action === 'delete_article') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException($t('err_invalid_article', 'Article invalide.'));
            }
            if (table_exists('article_translations')) {
                db()->prepare('DELETE FROM article_translations WHERE article_id = ?')->execute([$id]);
            }
            db()->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
            set_flash('success', $t('ok_deleted', 'Article supprimé.'));
            redirect('admin_articles');
        } elseif ($action === 'save_category') {
            $oldCode = slugify(trim((string) ($_POST['old_code'] ?? '')));
            $newCode = slugify(trim((string) ($_POST['new_code'] ?? '')));
            if ($oldCode === '' || $newCode === '') {
                throw new RuntimeException($t('err_invalid_category'));
            }
            db()->prepare('UPDATE articles SET category = ? WHERE category = ?')->execute([$newCode, $oldCode]);
            set_flash('success', $t('ok_category_updated'));
            redirect('admin_articles');
        } elseif ($action === 'delete_category') {
            $code = slugify(trim((string) ($_POST['code'] ?? '')));
            if ($code === '' || $code === 'autres') {
                throw new RuntimeException($t('err_delete_category'));
            }
            db()->prepare('UPDATE articles SET category = "autres" WHERE category = ?')->execute([$code]);
            set_flash('success', $t('ok_category_deleted'));
            redirect('admin_articles');
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('admin_articles');
    }
}

$adminStatus = (string) ($_GET['status'] ?? '');
$adminCategory = slugify(trim((string) ($_GET['category'] ?? '')));
$adminSearch = trim((string) ($_GET['q'] ?? ''));
$adminWhere = [];
$adminParams = [];
if (in_array($adminStatus, ['draft', 'scheduled', 'published'], true)) {
    $adminWhere[] = 'status = ?';
    $adminParams[] = $adminStatus;
}
if ($adminCategory !== '') {
    $adminWhere[] = 'category = ?';
    $adminParams[] = $adminCategory;
}
if ($adminSearch !== '') {
    $adminWhere[] = '(title LIKE ? OR excerpt LIKE ? OR content LIKE ?)';
    $needle = '%' . $adminSearch . '%';
    $adminParams[] = $needle;
    $adminParams[] = $needle;
    $adminParams[] = $needle;
}
$adminWhereSql = $adminWhere === [] ? '' : ('WHERE ' . implode(' AND ', $adminWhere));
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 30;
$countStmt = db()->prepare('SELECT COUNT(*) FROM articles ' . $adminWhereSql);
$countStmt->execute($adminParams);
$totalArticles = (int) ($countStmt->fetchColumn() ?: 0);
$pagination = pagination_state($totalArticles, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];
$articleStmt = db()->prepare('SELECT * FROM articles ' . $adminWhereSql . ' ORDER BY updated_at DESC, id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
$articleStmt->execute($adminParams);
$articles = $articleStmt->fetchAll() ?: [];
$articleStats = db()->query('SELECT status, COUNT(*) AS total FROM articles GROUP BY status')->fetchAll() ?: [];
$articleStatMap = ['draft' => 0, 'scheduled' => 0, 'published' => 0];
foreach ($articleStats as $statRow) {
    $articleStatMap[(string) $statRow['status']] = (int) $statRow['total'];
}
$editingId = (int) ($_GET['id'] ?? 0);
$editing = ['id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '<p></p>', 'status' => 'draft', 'category' => 'autres', 'scheduled_at' => null];
if ($editingId > 0) {
    $stmt = db()->prepare('SELECT * FROM articles WHERE id = ?');
    $stmt->execute([$editingId]);
    $editing = $stmt->fetch() ?: $editing;
}

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= $editingId > 0 ? e($t('edit')) : e($t('create')) ?> <?= e($t('an_article')) ?></h1>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_article">
            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
            <label><?= e($t('title')) ?><input type="text" name="title" value="<?= e((string) $editing['title']) ?>" required></label>
            <label><?= e($t('slug')) ?><input type="text" name="slug" value="<?= e((string) $editing['slug']) ?>" placeholder="<?= e($t('slug_placeholder', 'genere-depuis-le-titre')) ?>"></label>
            <label><?= e($t('category')) ?>
                <select name="category" id="article-category">
                    <?php $editingCategory = (string) ($editing['category'] ?? 'autres'); ?>
                    <?php foreach ($knownCategories as $categoryCode => $categoryLabel): ?>
                        <option value="<?= e($categoryCode) ?>" <?= $editingCategory === $categoryCode ? 'selected' : '' ?>><?= e($categoryLabel) ?></option>
                    <?php endforeach; ?>
                    <option value="__custom__"><?= e($t('new_category')) ?></option>
                </select>
            </label>
            <label id="article-category-custom" hidden><?= e($t('new_category_id')) ?>
                <input type="text" name="category_custom" value="" placeholder="<?= e($t('custom_category_ph')) ?>">
            </label>
            <label><?= e($t('import_document')) ?><input type="file" name="article_document" accept=".pdf,.docx,.txt,.md,.html,.htm,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/markdown,text/html"></label>
            <label><?= e($t('excerpt')) ?><textarea name="excerpt" rows="4"><?= e((string) $editing['excerpt']) ?></textarea></label>
            <label><?= e($t('content_simple_html')) ?><textarea name="content" rows="16"><?= e((string) $editing['content']) ?></textarea></label>
            <label><?= e($t('status')) ?>
                <select name="status">
                    <option value="draft" <?= (string) $editing['status'] === 'draft' ? 'selected' : '' ?>><?= e($t('draft')) ?></option>
                    <option value="scheduled" <?= (string) $editing['status'] === 'scheduled' ? 'selected' : '' ?>><?= e($t('scheduled', 'Programmée')) ?></option>
                    <option value="published" <?= (string) $editing['status'] === 'published' ? 'selected' : '' ?>><?= e($t('published')) ?></option>
                </select>
            </label>
            <label><?= e($t('scheduled_at', 'Date de publication')) ?><input type="datetime-local" name="scheduled_at" value="<?= !empty($editing['scheduled_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $editing['scheduled_at']))) : '' ?>"></label>
            <button class="button"><?= e($t('save')) ?></button>
        </form>
        <?php if ((int) $editing['id'] > 0): ?>
            <form method="post" style="margin-top:1rem;" onsubmit="return confirm('<?= e($t('confirm_delete', 'Supprimer cet article ?')) ?>');">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete_article">
                <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                <button class="button secondary" type="submit"><?= e($t('delete_article', 'Supprimer l’article')) ?></button>
            </form>
        <?php endif; ?>
    </section>
    <section class="card">
        <div class="row-between">
            <h2><?= e($t('existing_articles')) ?></h2>
            <span class="badge"><?= e($t('published')) ?>: <?= (int) $articleStatMap['published'] ?> · <?= e($t('scheduled', 'Programmée')) ?>: <?= (int) $articleStatMap['scheduled'] ?> · <?= e($t('draft')) ?>: <?= (int) $articleStatMap['draft'] ?></span>
        </div>
        <form method="get" class="stack">
            <input type="hidden" name="route" value="admin_articles">
            <div class="grid-3">
                <label><?= e($t('search', 'Recherche')) ?><input type="search" name="q" value="<?= e($adminSearch) ?>"></label>
                <label><?= e($t('status')) ?>
                    <select name="status">
                        <option value=""><?= e($t('all_statuses', 'Tous les statuts')) ?></option>
                        <option value="published" <?= $adminStatus === 'published' ? 'selected' : '' ?>><?= e($t('published')) ?></option>
                        <option value="scheduled" <?= $adminStatus === 'scheduled' ? 'selected' : '' ?>><?= e($t('scheduled', 'Programmée')) ?></option>
                        <option value="draft" <?= $adminStatus === 'draft' ? 'selected' : '' ?>><?= e($t('draft')) ?></option>
                    </select>
                </label>
                <label><?= e($t('category')) ?>
                    <select name="category">
                        <option value=""><?= e($t('all_categories', 'Toutes les catégories')) ?></option>
                        <?php foreach ($knownCategories as $categoryCode => $categoryLabel): ?>
                            <option value="<?= e($categoryCode) ?>" <?= $adminCategory === $categoryCode ? 'selected' : '' ?>><?= e($categoryLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <p><button class="button" type="submit"><?= e($t('filter', 'Filtrer')) ?></button> <a class="button secondary" href="<?= e(route_url('admin_articles')) ?>"><?= e($t('reset_filter', 'Réinitialiser')) ?></a></p>
        </form>
        <div class="stack">
            <?php foreach ($articles as $article): ?>
                <article class="article-item">
                    <div class="row-between"><h3><?= e((string) $article['title']) ?></h3><a class="button small" href="<?= e(route_url('admin_articles', ['id' => (int) $article['id']])) ?>"><?= e($t('edit')) ?></a></div>
                    <p><strong><?= e($t('category_label')) ?></strong>  <?= e((string) ($knownCategories[(string) ($article['category'] ?? '')] ?? ($article['category'] ?? 'autres'))) ?> · <span class="badge muted"><?= e($t((string) $article['status'], (string) $article['status'])) ?></span></p>
                    <p><?= e((string) $article['excerpt']) ?></p>
                </article>
            <?php endforeach; ?>
            <?php if ($articles === []): ?><p><?= e($t('no_articles')) ?></p><?php endif; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <nav class="actions mt-3">
                <?php if ($page > 1): ?><a class="button secondary" href="<?= e(route_url_clean('admin_articles', ['q' => $adminSearch, 'status' => $adminStatus, 'category' => $adminCategory, 'p' => $page - 1])) ?>">&larr; Prev</a><?php endif; ?>
                <span class="badge muted"><?= $page ?> / <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?><a class="button secondary" href="<?= e(route_url_clean('admin_articles', ['q' => $adminSearch, 'status' => $adminStatus, 'category' => $adminCategory, 'p' => $page + 1])) ?>">Next &rarr;</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
    <section class="card">
        <h2><?= e($t('category_edit')) ?></h2>
        <div class="stack">
            <?php foreach ($knownCategories as $categoryCode => $categoryLabel): ?>
                <article class="article-item">
                    <form method="post" class="row-between" style="gap:8px;align-items:end;">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="save_category">
                        <input type="hidden" name="old_code" value="<?= e($categoryCode) ?>">
                        <label style="flex:1;"><?= e($t('code')) ?>
                            <input type="text" name="new_code" value="<?= e($categoryCode) ?>" required>
                        </label>
                        <label style="flex:2;"><?= e($t('label')) ?>
                            <input type="text" value="<?= e($categoryLabel) ?>" disabled>
                        </label>
                        <button class="button small" type="submit"><?= e($t('rename_code')) ?></button>
                    </form>
                    <?php if ($categoryCode !== 'autres'): ?>
                        <form method="post" style="margin-top:8px;">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_category">
                            <input type="hidden" name="code" value="<?= e($categoryCode) ?>">
                            <button class="button small secondary" type="submit"><?= e($t('delete_to_other')) ?></button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if ($articles === []): ?><p><?= e($t('no_articles')) ?></p><?php endif; ?>
        </div>
    </section>
</div>
<script nonce="<?= e(csp_nonce()) ?>">
(() => {
    const titleInput = document.querySelector('input[name="title"]');
    const slugInput = document.querySelector('input[name="slug"]');
    const categorySelect = document.querySelector('#article-category');
    const customCategoryWrapper = document.querySelector('#article-category-custom');

    if (titleInput instanceof HTMLInputElement && slugInput instanceof HTMLInputElement) {
        let slugWasAuto = slugInput.value.trim() === '';
        const slugify = (value) => value
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .replace(/-{2,}/g, '-');

        const syncSlug = () => {
            if (!slugWasAuto) return;
            slugInput.value = slugify(titleInput.value);
        };

        slugInput.addEventListener('input', () => {
            slugWasAuto = slugInput.value.trim() === '';
        });
        titleInput.addEventListener('input', syncSlug);
        syncSlug();
    }

    if (categorySelect instanceof HTMLSelectElement && customCategoryWrapper instanceof HTMLElement) {
        const syncCategoryCustom = () => {
            customCategoryWrapper.hidden = categorySelect.value !== '__custom__';
        };
        categorySelect.addEventListener('change', syncCategoryCustom);
        syncCategoryCustom();
    }
})();
</script>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
