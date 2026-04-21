<?php
declare(strict_types=1);

if (!table_exists('albums') || !table_exists('album_photos')) {
    echo render_layout('<div class="card"><h1>Album</h1><p>La galerie est indisponible pour le moment.</p></div>', 'Album');
    return;
}

$stmt = db()->prepare('SELECT * FROM albums WHERE id = ? AND is_public = 1');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$album = $stmt->fetch();

if (!$album) {
    echo render_layout('<div class="card"><p>Album introuvable.</p></div>', 'Album');
    return;
}

$photosStmt = db()->prepare('SELECT * FROM album_photos WHERE album_id = ? ORDER BY id DESC');
$photosStmt->execute([(int) $album['id']]);
$photos = $photosStmt->fetchAll();

ob_start();
?>
<section class="card gallery-header">
    <p><a href="<?= e(route_url('albums')) ?>">← Retour à la galerie</a></p>
    <h1><?= e((string) $album['title']) ?></h1>
    <?php if (trim((string) ($album['description'] ?? '')) !== ''): ?>
        <p><?= e((string) $album['description']) ?></p>
    <?php endif; ?>
    <div class="stats-grid">
        <article class="stat-card">
            <span class="help">Photos</span>
            <strong><?= (int) count($photos) ?></strong>
        </article>
    </div>
</section>

<section class="card">
    <h2>Photos de l’album</h2>
    <?php if ($photos === []): ?>
        <p>Aucune photo disponible dans cet album.</p>
    <?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($photos as $photo): ?>
            <figure class="gallery-item">
                <img src="<?= e(base_url((string) $photo['file_path'])) ?>" alt="<?= e((string) ($photo['title'] ?: 'Photo de l’album')) ?>">
                <figcaption><strong><?= e((string) $photo['title']) ?></strong><br><?= e((string) $photo['caption']) ?></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $album['title']);
