<?php
declare(strict_types=1);

require_permission('wiki.edit');

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);
$page = ['id' => 0, 'title' => '', 'slug' => '', 'content' => '<p></p>'];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM wiki_pages WHERE id = ?');
    $stmt->execute([$id]);
    $page = $stmt->fetch() ?: $page;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = sanitize_rich_html((string) ($_POST['content'] ?? ''));
        $slug = slugify((string) ($_POST['slug'] ?? $title));

        if ($id > 0) {
            db()->prepare('INSERT INTO wiki_revisions (wiki_page_id, member_id, content) VALUES (?, ?, ?)')->execute([$id, (int) $user['id'], (string) $page['content']]);
            db()->prepare('UPDATE wiki_pages SET title = ?, slug = ?, content = ?, author_id = ? WHERE id = ?')->execute([$title, $slug, $content, (int) $user['id'], $id]);
        } else {
            db()->prepare('INSERT INTO wiki_pages (title, slug, content, author_id) VALUES (?, ?, ?, ?)')->execute([$title, $slug, $content, (int) $user['id']]);
        }

        set_flash('success', 'Page wiki enregistrée.');
        redirect('wiki');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect($id > 0 ? 'wiki_edit&id=' . $id : 'wiki_edit');
    }
}

ob_start();
?>
<div class="card">
    <h1><?= $id > 0 ? 'Modifier' : 'Créer' ?> une page wiki</h1>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <label>Titre<input type="text" name="title" value="<?= e((string) $page['title']) ?>" required></label>
        <label>Slug<input type="text" name="slug" value="<?= e((string) $page['slug']) ?>"></label>
        <label>Contenu (HTML simple)
            <textarea name="content" rows="18"><?= e((string) $page['content']) ?></textarea>
        </label>
        <p><button class="button">Enregistrer</button></p>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Éditer wiki');
