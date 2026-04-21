<?php
declare(strict_types=1);

$rows = db()->query(
    'SELECT a.*, 
        (SELECT COUNT(*) FROM album_photos p WHERE p.album_id = a.id) AS photo_count,
        (SELECT p.file_path FROM album_photos p WHERE p.album_id = a.id ORDER BY p.id DESC LIMIT 1) AS cover_path
     FROM albums a
     WHERE a.is_public = 1
     ORDER BY a.id DESC'
)->fetchAll();

ob_start();
?>
<div class="card">
    <div class="row-between">
        <h1>Albums publics</h1>
        <?php if (has_permission('albums.manage')): ?>
            <a class="button small" href="<?= e(base_url('index.php?route=admin_albums')) ?>">Gérer</a>
        <?php endif; ?>
    </div>

    <?php if ($rows === []): ?>
        <p class="help">Aucun album public disponible pour le moment.</p>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($rows as $row):
                $coverPath = trim((string) ($row['cover_path'] ?? ''));
                $photoCount = (int) ($row['photo_count'] ?? 0);
                ?>
                <article class="gallery-item album-card">
                    <a class="album-card-link" href="<?= e(base_url('index.php?route=album&id=' . (int) $row['id'])) ?>">
                        <?php if ($coverPath !== ''): ?>
                            <img src="<?= e(base_url($coverPath)) ?>" alt="Couverture de l’album <?= e((string) $row['title']) ?>">
                        <?php else: ?>
                            <div class="album-card-placeholder">📷</div>
                        <?php endif; ?>
                        <h2><?= e((string) $row['title']) ?></h2>
                        <p class="help"><?= e((string) $row['description']) ?></p>
                        <p><span class="badge muted"><?= $photoCount ?> photo<?= $photoCount > 1 ? 's' : '' ?></span></p>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Albums');
