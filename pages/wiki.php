<?php
declare(strict_types=1);

$rows = db()->query('SELECT slug, title, updated_at FROM wiki_pages ORDER BY updated_at DESC')->fetchAll();

ob_start();
?>
<div class="card">
    <div class="row-between">
        <h1>Wiki technique</h1>
        <?php if (has_permission('wiki.edit')): ?>
            <a class="button small" href="<?= e(base_url('index.php?route=wiki_edit')) ?>">Nouvelle page</a>
        <?php endif; ?>
    </div>
    <div class="stack">
        <?php foreach ($rows as $row): ?>
            <article class="article-item">
                <h2><a href="<?= e(base_url('index.php?route=wiki_view&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $row['title']) ?></a></h2>
                <p>Mise à jour : <?= e((string) $row['updated_at']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Wiki');
