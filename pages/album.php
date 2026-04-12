<?php
declare(strict_types=1);

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
<div class="card">
    <h1><?= e((string) $album['title']) ?></h1>
    <p><?= e((string) $album['description']) ?></p>
    <div class="gallery-grid">
        <?php foreach ($photos as $photo): ?>
            <figure class="gallery-item">
                <img src="<?= e(base_url((string) $photo['file_path'])) ?>" alt="">
                <figcaption><strong><?= e((string) $photo['title']) ?></strong><br><?= e((string) $photo['caption']) ?></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $album['title']);
