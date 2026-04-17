<?php
declare(strict_types=1);

$rows = cache_remember('articles_published_v1', 180, static fn(): array => db()->query('SELECT id, slug, title, excerpt, content, updated_at FROM articles WHERE status = "published" ORDER BY updated_at DESC')->fetchAll());

set_page_meta([
    'title' => 'Articles techniques',
    'description' => 'Articles techniques ON4CRD sur le radioamateurisme, le matériel et la pratique.',
    'schema_type' => 'CollectionPage',
]);

ob_start();
?>
<div class="card">
    <div class="row-between">
        <h1>Articles techniques</h1>
        <?php if (has_permission('articles.manage')): ?>
            <a class="button small" href="<?= e(base_url('index.php?route=admin_articles')) ?>">Gérer</a>
        <?php endif; ?>
    </div>
    <p class="help">Les articles sont écrits en français. Les autres langues sont générées automatiquement à partir de cette version source.</p>
    <div class="stack">
        <?php foreach ($rows as $row): ?>
            <?php $row = localized_article_row($row); ?>
            <article class="article-item">
                <h2><a href="<?= e(base_url('index.php?route=article&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $row['title_localized']) ?></a></h2>
                <p><?= e((string) $row['excerpt_localized']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Articles');
