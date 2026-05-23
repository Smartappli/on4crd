<?php
declare(strict_types=1);

require_permission('wiki.edit');

$locale = current_locale();
$t = i18n_domain_translator('wiki_edit', $locale);

set_page_meta([
    'title' => $t('layout'),
    'description' => $t('meta_desc'),
    'robots' => 'noindex,nofollow',
]);

if (!ensure_wiki_tables()) {
    echo render_layout('<div class="card"><h1>' . e($t('layout')) . '</h1><p>' . e($t('meta_desc')) . '</p></div>', $t('layout'));
    return;
}

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);
$page = ['id' => 0, 'title' => '', 'slug' => '', 'content' => '<p></p>', 'updated_at' => null];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM wiki_pages WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $page = $stmt->fetch() ?: $page;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = sanitize_rich_html((string) ($_POST['content'] ?? ''));
        $slug = slugify((string) ($_POST['slug'] ?? $title));

        if ($title === '' || trim(strip_tags($content)) === '') {
            throw new RuntimeException($t('content_label'));
        }

        $slugStmt = db()->prepare('SELECT id FROM wiki_pages WHERE slug = ? AND id <> ? LIMIT 1');
        $slugStmt->execute([$slug, $id]);
        if ($slugStmt->fetch()) {
            throw new RuntimeException($t('slug_label'));
        }

        if ($id > 0) {
            db()->prepare('INSERT INTO wiki_revisions (wiki_page_id, member_id, content) VALUES (?, ?, ?)')->execute([$id, (int) $user['id'], (string) $page['content']]);
            db()->prepare('UPDATE wiki_pages SET title = ?, slug = ?, content = ?, author_id = ? WHERE id = ?')->execute([$title, $slug, $content, (int) $user['id'], $id]);
        } else {
            db()->prepare('INSERT INTO wiki_pages (title, slug, content, author_id) VALUES (?, ?, ?, ?)')->execute([$title, $slug, $content, (int) $user['id']]);
        }

        set_flash('success', $t('saved'));
        redirect_url(route_url('wiki_view', ['slug' => $slug]));
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect_url($id > 0 ? route_url('wiki_edit', ['id' => $id]) : route_url('wiki_edit'));
    }
}

ob_start();
?>
<div class="wiki-edit-page">
    <section class="wiki-edit-hero">
        <div>
            <p class="eyebrow"><?= e($t('layout')) ?></p>
            <h1><?= e(($id > 0 ? $t('edit') : $t('create')) . ' ' . $t('heading_suffix')) ?></h1>
        </div>
        <a class="button secondary" href="<?= e($id > 0 ? route_url('wiki_view', ['slug' => (string) $page['slug']]) : route_url('wiki')) ?>"><?= e($t('layout')) ?></a>
    </section>

    <form method="post" class="wiki-edit-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="wiki-edit-grid">
            <label><?= e($t('title_label')) ?><input type="text" name="title" value="<?= e((string) $page['title']) ?>" required></label>
            <label><?= e($t('slug_label')) ?><input type="text" name="slug" value="<?= e((string) $page['slug']) ?>"></label>
        </div>
        <label><?= e($t('content_label')) ?>
            <textarea name="content" rows="22"><?= e((string) $page['content']) ?></textarea>
        </label>
        <div class="actions">
            <button class="button" type="submit"><?= e($t('save')) ?></button>
            <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e($t('layout')) ?></a>
        </div>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
