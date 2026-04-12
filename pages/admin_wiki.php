<?php
declare(strict_types=1);

require_permission('admin.access');

$pages = db()->query('SELECT id, title, slug, updated_at FROM wiki_pages ORDER BY updated_at DESC')->fetchAll();

ob_start();
?>
<div class="card">
    <div class="row-between">
        <h1>Administration du wiki</h1>
        <?php if (has_permission('wiki.edit')): ?>
            <a class="button small" href="<?= e(base_url('index.php?route=wiki_edit')) ?>">Nouvelle page</a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Titre</th><th>Slug</th><th>Mise à jour</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($pages as $page): ?>
                <tr>
                    <td><?= e((string) $page['title']) ?></td>
                    <td><code><?= e((string) $page['slug']) ?></code></td>
                    <td><?= e((string) $page['updated_at']) ?></td>
                    <td><a href="<?= e(base_url('index.php?route=wiki_edit&id=' . (int) $page['id'])) ?>">Modifier</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($pages === []): ?><tr><td colspan="4">Aucune page.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Administration wiki');
