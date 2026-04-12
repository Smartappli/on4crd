<?php
declare(strict_types=1);

$rows = db()->query('SELECT * FROM albums WHERE is_public = 1 ORDER BY id DESC')->fetchAll();

ob_start();
?>
<div class="card">
    <div class="row-between">
        <h1>Albums publics</h1>
        <?php if (has_permission('albums.manage')): ?>
            <a class="button small" href="<?= e(base_url('index.php?route=admin_albums')) ?>">Gérer</a>
        <?php endif; ?>
    </div>
    <div class="stack">
        <?php foreach ($rows as $row): ?>
            <article class="article-item">
                <h2><a href="<?= e(base_url('index.php?route=album&id=' . (int) $row['id'])) ?>"><?= e((string) $row['title']) ?></a></h2>
                <p><?= e((string) $row['description']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Albums');
