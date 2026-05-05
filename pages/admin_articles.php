<?php
declare(strict_types=1);

require_permission('articles.manage');
$locale = current_locale();
$i18n = [
    'fr' => ['layout' => 'Articles', 'meta_desc' => 'Administration et publication des articles du site.', 'ok_saved' => 'Article enregistré.', 'err_invalid_category' => 'Catégorie invalide.', 'ok_category_updated' => 'Catégorie mise à jour.', 'err_delete_category' => 'Suppression impossible pour cette catégorie.', 'ok_category_deleted' => 'Catégorie supprimée (articles déplacés vers "autres").', 'edit' => 'Modifier', 'create' => 'Créer', 'an_article' => 'un article', 'title' => 'Titre', 'slug' => 'Slug', 'category' => 'Catégorie', 'new_category' => 'Nouvelle catégorie…', 'new_category_id' => 'Nouvelle catégorie (identifiant)', 'import_document' => 'Importer un document (PDF, DOCX, TXT, MD, HTML)', 'excerpt' => 'Résumé', 'content_simple_html' => 'Contenu (HTML simple)', 'status' => 'Statut', 'draft' => 'Brouillon', 'published' => 'Publié', 'save' => 'Enregistrer', 'existing_articles' => 'Articles existants', 'category_label' => 'Catégorie :', 'category_edit' => 'Édition des catégories', 'code' => 'Code', 'label' => 'Libellé', 'rename_code' => 'Renommer code', 'delete_to_other' => 'Supprimer (vers autres)'],
    'en' => ['ok_saved' => 'Article saved.', 'err_invalid_category' => 'Invalid category.', 'ok_category_updated' => 'Category updated.', 'err_delete_category' => 'Cannot delete this category.', 'ok_category_deleted' => 'Category deleted (articles moved to "other").', 'edit' => 'Edit', 'create' => 'Create', 'an_article' => 'an article', 'title' => 'Title', 'slug' => 'Slug', 'category' => 'Category', 'new_category' => 'New category…', 'new_category_id' => 'New category (identifier)', 'import_document' => 'Import a document (PDF, DOCX, TXT, MD, HTML)', 'excerpt' => 'Summary', 'content_simple_html' => 'Content (simple HTML)', 'status' => 'Status', 'draft' => 'Draft', 'published' => 'Published', 'save' => 'Save', 'existing_articles' => 'Existing articles', 'category_label' => 'Category:', 'category_edit' => 'Category editing', 'code' => 'Code', 'label' => 'Label', 'rename_code' => 'Rename code', 'delete_to_other' => 'Delete (to other)', 'layout' => 'Articles', 'meta_desc' => 'Administration and publishing of site articles.'],
    'de' => ['ok_saved' => 'Article saved.', 'err_invalid_category' => 'Invalid category.', 'ok_category_updated' => 'Category updated.', 'err_delete_category' => 'Cannot delete this category.', 'ok_category_deleted' => 'Category deleted (articles moved to "other").', 'edit' => 'Edit', 'create' => 'Create', 'an_article' => 'an article', 'title' => 'Title', 'slug' => 'Slug', 'category' => 'Category', 'new_category' => 'New category…', 'new_category_id' => 'New category (identifier)', 'import_document' => 'Import a document (PDF, DOCX, TXT, MD, HTML)', 'excerpt' => 'Summary', 'content_simple_html' => 'Content (simple HTML)', 'status' => 'Status', 'draft' => 'Draft', 'published' => 'Published', 'save' => 'Save', 'existing_articles' => 'Existing articles', 'category_label' => 'Category:', 'category_edit' => 'Category editing', 'code' => 'Code', 'label' => 'Label', 'rename_code' => 'Rename code', 'delete_to_other' => 'Delete (to other)', 'layout' => 'Artikel', 'meta_desc' => 'Verwaltung und Veröffentlichung von Website-Artikeln.'],
    'nl' => ['ok_saved' => 'Article saved.', 'err_invalid_category' => 'Invalid category.', 'ok_category_updated' => 'Category updated.', 'err_delete_category' => 'Cannot delete this category.', 'ok_category_deleted' => 'Category deleted (articles moved to "other").', 'edit' => 'Edit', 'create' => 'Create', 'an_article' => 'an article', 'title' => 'Title', 'slug' => 'Slug', 'category' => 'Category', 'new_category' => 'New category…', 'new_category_id' => 'New category (identifier)', 'import_document' => 'Import a document (PDF, DOCX, TXT, MD, HTML)', 'excerpt' => 'Summary', 'content_simple_html' => 'Content (simple HTML)', 'status' => 'Status', 'draft' => 'Draft', 'published' => 'Published', 'save' => 'Save', 'existing_articles' => 'Existing articles', 'category_label' => 'Category:', 'category_edit' => 'Category editing', 'code' => 'Code', 'label' => 'Label', 'rename_code' => 'Rename code', 'delete_to_other' => 'Delete (to other)', 'layout' => 'Artikelen', 'meta_desc' => 'Beheer en publicatie van siteartikelen.'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};
set_page_meta([
    'title' => $t('layout'),
    'description' => $t('meta_desc'),
    'schema_type' => 'WebPage',
]);

/**
 * @return array{excerpt:string,content:string}
 */
function import_article_document(array $file): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['excerpt' => '', 'content' => ''];
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Le téléversement du document a échoué.');
    }

    $originalName = trim((string) ($file['name'] ?? 'document'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'docx', 'txt', 'md', 'html', 'htm'], true)) {
        throw new RuntimeException('Formats autorisés : PDF, DOCX, TXT, MD ou HTML.');
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Document importé invalide.');
    }

    $targetDir = __DIR__ . '/../storage/uploads/articles';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Impossible de créer le répertoire de stockage des articles.');
    }

    $basename = slugify(pathinfo($originalName, PATHINFO_FILENAME));
    if ($basename === '') {
        $basename = 'article';
    }
    $filename = $basename . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $absolutePath = $targetDir . '/' . $filename;
    if (!move_uploaded_file($tmpPath, $absolutePath)) {
        throw new RuntimeException('Impossible d’enregistrer le document importé.');
    }

    $publicPath = 'storage/uploads/articles/' . $filename;
    $publicUrl = base_url($publicPath);
    $safeTitle = e(pathinfo($originalName, PATHINFO_FILENAME));
    if (in_array($extension, ['txt', 'md'], true)) {
        $rawText = (string) file_get_contents($absolutePath);
        $paragraphs = preg_split('/\R{2,}/u', trim($rawText)) ?: [];
        $htmlParts = [];
        foreach ($paragraphs as $paragraph) {
            $line = trim($paragraph);
            if ($line === '') {
                continue;
            }
            if ($extension === 'md' && preg_match('/^#{1,6}\s+(.+)$/u', $line, $matches)) {
                $htmlParts[] = '<h3>' . e($matches[1]) . '</h3>';
            } else {
                $htmlParts[] = '<p>' . nl2br(e($line)) . '</p>';
            }
        }
        $content = implode("\n", $htmlParts);
    } elseif (in_array($extension, ['html', 'htm'], true)) {
        $rawHtml = (string) file_get_contents($absolutePath);
        $content = sanitize_rich_html($rawHtml);
    } else {
        $content = $extension === 'pdf'
            ? '<div class="article-document"><p><strong>Document importé :</strong> ' . $safeTitle . '</p><iframe src="' . e($publicUrl) . '" title="' . $safeTitle . '" style="width:100%;min-height:70vh;border:1px solid #cbd5e1;border-radius:12px;" loading="lazy"></iframe></div>'
            : '<div class="article-document"><p><strong>Document DOCX importé :</strong> ' . $safeTitle . '</p><iframe src="https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($publicUrl) . '" title="' . $safeTitle . '" style="width:100%;min-height:70vh;border:1px solid #cbd5e1;border-radius:12px;" loading="lazy"></iframe></div>';
    }

    return [
        'excerpt' => 'Document importé : ' . pathinfo($originalName, PATHINFO_FILENAME),
        'content' => $content,
    ];
}

$defaultCategories = [
    'antennes' => 'Antennes',
    'trafic' => 'Trafic & DX',
    'numerique' => 'Modes numériques',
    'materiel' => 'Matériel & station',
    'formation' => 'Formation',
    'autres' => 'Autres',
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
            $slug = slugify($title);
            $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
            $content = sanitize_rich_html((string) ($_POST['content'] ?? ''));
            $status = (string) ($_POST['status'] ?? 'draft');
            $categoryChoice = trim((string) ($_POST['category'] ?? 'autres'));
            $customCategory = slugify(trim((string) ($_POST['category_custom'] ?? '')));
            $category = $categoryChoice === '__custom__' ? $customCategory : slugify($categoryChoice);
            if ($category === '') {
                $category = 'autres';
            }
            $imported = import_article_document($_FILES['article_document'] ?? []);
            if ($imported['content'] !== '') {
                $content = $imported['content'];
                if ($excerpt === '') {
                    $excerpt = $imported['excerpt'];
                }
            }
            if ($id > 0) {
                db()->prepare('UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, status = ?, category = ? WHERE id = ?')->execute([$title, $slug, $excerpt, $content, $status, $category, $id]);
            } else {
                db()->prepare('INSERT INTO articles (title, slug, excerpt, content, status, category, author_id) VALUES (?, ?, ?, ?, ?, ?, ?)')->execute([$title, $slug, $excerpt, $content, $status, $category, (int) current_user()['id']]);
                $id = (int) db()->lastInsertId();
            }
            article_translation_upsert($id, 'en');
            article_translation_upsert($id, 'de');
            article_translation_upsert($id, 'nl');
            set_flash('success', $t('ok_saved'));
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

$articles = db()->query('SELECT * FROM articles ORDER BY updated_at DESC')->fetchAll();
$editingId = (int) ($_GET['id'] ?? 0);
$editing = ['id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '<p></p>', 'status' => 'draft', 'category' => 'autres'];
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
            <label>Slug<input type="text" name="slug" value="<?= e((string) $editing['slug']) ?>" readonly aria-readonly="true" tabindex="-1"></label>
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
                <input type="text" name="category_custom" value="" placeholder="ex: propagation-vhf">
            </label>
            <label><?= e($t('import_document')) ?><input type="file" name="article_document" accept=".pdf,.docx,.txt,.md,.html,.htm,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,text/markdown,text/html"></label>
            <label><?= e($t('excerpt')) ?><textarea name="excerpt" rows="4"><?= e((string) $editing['excerpt']) ?></textarea></label>
            <label><?= e($t('content_simple_html')) ?><textarea name="content" rows="16"><?= e((string) $editing['content']) ?></textarea></label>
            <label><?= e($t('status')) ?>
                <select name="status">
                    <option value="draft" <?= (string) $editing['status'] === 'draft' ? 'selected' : '' ?>><?= e($t('draft')) ?></option>
                    <option value="published" <?= (string) $editing['status'] === 'published' ? 'selected' : '' ?>><?= e($t('published')) ?></option>
                </select>
            </label>
            <button class="button"><?= e($t('save')) ?></button>
        </form>
    </section>
    <section class="card">
        <h2><?= e($t('existing_articles')) ?></h2>
        <div class="stack">
            <?php foreach ($articles as $article): ?>
                <article class="article-item">
                    <div class="row-between"><h3><?= e((string) $article['title']) ?></h3><a class="button small" href="<?= e(base_url('index.php?route=admin_articles&id=' . (int) $article['id'])) ?>"><?= e(('edit')) ?></a></div>
                    <p><strong><?= e($t('category_label')) ?></strong>  <?= e((string) ($knownCategories[(string) ($article['category'] ?? '')] ?? ($article['category'] ?? 'autres'))) ?></p>
                    <p><?= e((string) $article['excerpt']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
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
        const slugify = (value) => value
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .replace(/-{2,}/g, '-');

        const syncSlug = () => {
            slugInput.value = slugify(titleInput.value);
        };

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
