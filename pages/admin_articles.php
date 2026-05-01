<?php
declare(strict_types=1);

require_permission('articles.manage');

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
    if (!in_array($extension, ['pdf', 'docx'], true)) {
        throw new RuntimeException('Formats autorisés : PDF ou DOCX.');
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
    $content = $extension === 'pdf'
        ? '<div class="article-document"><p><strong>Document importé :</strong> ' . $safeTitle . '</p><iframe src="' . e($publicUrl) . '" title="' . $safeTitle . '" style="width:100%;min-height:70vh;border:1px solid #cbd5e1;border-radius:12px;" loading="lazy"></iframe></div>'
        : '<div class="article-document"><p><strong>Document DOCX importé :</strong> ' . $safeTitle . '</p><iframe src="https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($publicUrl) . '" title="' . $safeTitle . '" style="width:100%;min-height:70vh;border:1px solid #cbd5e1;border-radius:12px;" loading="lazy"></iframe></div>';

    return [
        'excerpt' => 'Document importé : ' . pathinfo($originalName, PATHINFO_FILENAME),
        'content' => $content,
    ];
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
            $imported = import_article_document($_FILES['article_document'] ?? []);
            if ($imported['content'] !== '') {
                $content = sanitize_rich_html($imported['content']);
                if ($excerpt === '') {
                    $excerpt = $imported['excerpt'];
                }
            }
            if ($id > 0) {
                db()->prepare('UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, status = ? WHERE id = ?')->execute([$title, $slug, $excerpt, $content, $status, $id]);
            } else {
                db()->prepare('INSERT INTO articles (title, slug, excerpt, content, status, author_id) VALUES (?, ?, ?, ?, ?, ?)')->execute([$title, $slug, $excerpt, $content, $status, (int) current_user()['id']]);
                $id = (int) db()->lastInsertId();
            }
            article_translation_upsert($id, 'en');
            article_translation_upsert($id, 'de');
            article_translation_upsert($id, 'nl');
            set_flash('success', 'Article enregistré.');
            redirect('admin_articles');
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('admin_articles');
    }
}

$articles = db()->query('SELECT * FROM articles ORDER BY updated_at DESC')->fetchAll();
$editingId = (int) ($_GET['id'] ?? 0);
$editing = ['id' => 0, 'title' => '', 'slug' => '', 'excerpt' => '', 'content' => '<p></p>', 'status' => 'draft'];
if ($editingId > 0) {
    $stmt = db()->prepare('SELECT * FROM articles WHERE id = ?');
    $stmt->execute([$editingId]);
    $editing = $stmt->fetch() ?: $editing;
}

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= $editingId > 0 ? 'Modifier' : 'Créer' ?> un article</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_article">
            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
            <label>Titre<input type="text" name="title" value="<?= e((string) $editing['title']) ?>" required></label>
            <label>Slug<input type="text" name="slug" value="<?= e((string) $editing['slug']) ?>" readonly aria-readonly="true" tabindex="-1"></label>
            <label>Importer un document (PDF ou DOCX)<input type="file" name="article_document" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"></label>
            <label>Résumé<textarea name="excerpt" rows="4"><?= e((string) $editing['excerpt']) ?></textarea></label>
            <label>Contenu (HTML simple)<textarea name="content" rows="16"><?= e((string) $editing['content']) ?></textarea></label>
            <label>Statut
                <select name="status">
                    <option value="draft" <?= (string) $editing['status'] === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                    <option value="published" <?= (string) $editing['status'] === 'published' ? 'selected' : '' ?>>Publié</option>
                </select>
            </label>
            <button class="button">Enregistrer</button>
        </form>
    </section>
    <section class="card">
        <h2>Articles existants</h2>
        <div class="stack">
            <?php foreach ($articles as $article): ?>
                <article class="article-item">
                    <div class="row-between"><h3><?= e((string) $article['title']) ?></h3><a class="button small" href="<?= e(base_url('index.php?route=admin_articles&id=' . (int) $article['id'])) ?>">Modifier</a></div>
                    <p><?= e((string) $article['excerpt']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<script nonce="<?= e(csp_nonce()) ?>">
(() => {
    const titleInput = document.querySelector('input[name="title"]');
    const slugInput = document.querySelector('input[name="slug"]');
    if (!(titleInput instanceof HTMLInputElement) || !(slugInput instanceof HTMLInputElement)) {
        return;
    }

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
})();
</script>
<?php
echo render_layout((string) ob_get_clean(), 'Articles');
