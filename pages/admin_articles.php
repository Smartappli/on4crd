<?php
declare(strict_types=1);

require_permission('articles.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save_article') {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $slug = slugify((string) ($_POST['slug'] ?? $title));
            $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
            $content = sanitize_rich_html((string) ($_POST['content'] ?? ''));
            $status = (string) ($_POST['status'] ?? 'draft');
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
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_article">
            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
            <label>Titre<input type="text" name="title" value="<?= e((string) $editing['title']) ?>" required></label>
            <label>Slug<input type="text" name="slug" value="<?= e((string) $editing['slug']) ?>"></label>
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
<?php
echo render_layout((string) ob_get_clean(), 'Articles');
