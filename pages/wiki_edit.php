<?php
declare(strict_types=1);

require_permission('wiki.moderate');

$locale = current_locale();
$wikiMessages = i18n_domain_locale('wiki', $locale);
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
$wikiCategories = wiki_categories($wikiMessages);
$wikiThemeLabel = (string) ($wikiMessages['themes'] ?? 'Themes');
$page = ['id' => 0, 'title' => '', 'slug' => '', 'content' => '<p></p>', 'category' => 'general', 'updated_at' => null];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM wiki_pages WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $existingPage = $stmt->fetch();
    if (!$existingPage) {
        http_response_code(404);
        echo render_layout('<div class="card"><h1>' . e($t('layout')) . '</h1><p>' . e(t_page('wiki_view', 'not_found', $locale)) . '</p></div>', $t('layout'));
        return;
    }
    $page = $existingPage;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = sanitize_rich_html((string) ($_POST['content'] ?? ''));
        $slug = slugify((string) ($_POST['slug'] ?? $title));
        $category = wiki_category_from_input((string) ($_POST['category'] ?? 'general'), $wikiCategories);

        if ($title === '' || trim(strip_tags($content)) === '' || $slug === '') {
            throw new RuntimeException($t('content_label'));
        }
        if (mb_strlen($title) > 190 || mb_strlen($slug) > 190 || mb_strlen($category) > 120 || mb_strlen($content) > 50000) {
            throw new RuntimeException('Un des champs dépasse la longueur autorisée.');
        }

        $slugStmt = db()->prepare('SELECT id FROM wiki_pages WHERE slug = ? AND id <> ? LIMIT 1');
        $slugStmt->execute([$slug, $id]);
        if ($slugStmt->fetch()) {
            throw new RuntimeException($t('slug_label'));
        }

        if ($id > 0) {
            db()->prepare('INSERT INTO wiki_revisions (wiki_page_id, member_id, content) VALUES (?, ?, ?)')->execute([$id, (int) $user['id'], (string) $page['content']]);
            db()->prepare('UPDATE wiki_pages SET title = ?, slug = ?, content = ?, category = ?, author_id = ?, status = "published" WHERE id = ?')->execute([$title, $slug, $content, $category, (int) $user['id'], $id]);
        } else {
            db()->prepare('INSERT INTO wiki_pages (title, slug, content, category, author_id, status) VALUES (?, ?, ?, ?, ?, "published")')->execute([$title, $slug, $content, $category, (int) $user['id']]);
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
            <label><?= e($t('title_label')) ?><input type="text" name="title" value="<?= e((string) $page['title']) ?>" maxlength="190" required></label>
            <label><?= e($t('slug_label')) ?><input type="text" name="slug" value="<?= e((string) $page['slug']) ?>" maxlength="190"></label>
            <label><?= e($wikiThemeLabel) ?>
                <select name="category">
                    <?php $selectedCategory = wiki_category_code((string) ($page['category'] ?? 'general')); ?>
                    <?php foreach ($wikiCategories as $categoryCode => $categoryLabel): ?>
                        <option value="<?= e((string) $categoryCode) ?>" <?= $selectedCategory === $categoryCode ? 'selected' : '' ?>><?= e((string) $categoryLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label><?= e($t('content_label')) ?>
            <textarea name="content" rows="22" maxlength="50000" data-wysiwyg="full"><?= e((string) $page['content']) ?></textarea>
        </label>
        <div class="actions">
            <button class="button" type="submit"><?= e($t('save')) ?></button>
            <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e($t('layout')) ?></a>
        </div>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
