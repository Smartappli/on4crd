<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Album', 'gallery_unavailable' => 'La galerie est indisponible pour le moment.', 'not_found' => 'Album introuvable.', 'back' => '← Retour à la galerie', 'photos' => 'Photos', 'album_photos' => 'Photos de l’album', 'none' => 'Aucune photo disponible dans cet album.', 'photo_alt' => 'Photo de l’album'],
    'en' => ['title' => 'Album', 'gallery_unavailable' => 'The gallery is currently unavailable.', 'not_found' => 'Album not found.', 'back' => '← Back to gallery', 'photos' => 'Photos', 'album_photos' => 'Album photos', 'none' => 'No photos available in this album.', 'photo_alt' => 'Album photo'],
    'de' => ['title' => 'Album', 'gallery_unavailable' => 'Die Galerie ist derzeit nicht verfügbar.', 'not_found' => 'Album nicht gefunden.', 'back' => '← Zurück zur Galerie', 'photos' => 'Fotos', 'album_photos' => 'Albumfotos', 'none' => 'Keine Fotos in diesem Album verfügbar.', 'photo_alt' => 'Albumfoto'],
    'nl' => ['title' => 'Album', 'gallery_unavailable' => 'De galerij is momenteel niet beschikbaar.', 'not_found' => 'Album niet gevonden.', 'back' => '← Terug naar galerij', 'photos' => "Foto's", 'album_photos' => "Foto's van het album", 'none' => "Geen foto's beschikbaar in dit album.", 'photo_alt' => 'Albumfoto'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

if (!table_exists('albums') || !table_exists('album_photos')) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['title']) . '</h1><p>' . e((string) $t['gallery_unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

$albumId = (int) ($_GET['id'] ?? 0);
$album = cache_remember('album_public_' . $albumId, 90, static function () use ($albumId) {
    $stmt = db()->prepare('SELECT * FROM albums WHERE id = ? AND is_public = 1');
    $stmt->execute([$albumId]);
    return $stmt->fetch() ?: null;
});

if (!$album) {
    echo render_layout('<div class="card"><p>' . e((string) $t['not_found']) . '</p></div>', (string) $t['title']);
    return;
}

$photos = cache_remember('album_photos_public_' . (int) $album['id'], 90, static function () use ($album): array {
    $photosStmt = db()->prepare('SELECT * FROM album_photos WHERE album_id = ? ORDER BY id DESC');
    $photosStmt->execute([(int) $album['id']]);
    return $photosStmt->fetchAll() ?: [];
});

ob_start();
?>
<section class="card gallery-header">
    <p><a href="<?= e(route_url('albums')) ?>"><?= e((string) $t['back']) ?></a></p>
    <h1><?= e((string) $album['title']) ?></h1>
    <?php if (trim((string) ($album['description'] ?? '')) !== ''): ?>
        <p><?= e((string) $album['description']) ?></p>
    <?php endif; ?>
    <div class="stats-grid">
        <article class="stat-card">
            <span class="help"><?= e((string) $t['photos']) ?></span>
            <strong><?= (int) count($photos) ?></strong>
        </article>
    </div>
</section>

<section class="card">
    <h2><?= e((string) $t['album_photos']) ?></h2>
    <?php if ($photos === []): ?>
        <p><?= e((string) $t['none']) ?></p>
    <?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($photos as $photo): ?>
            <?php
            $filePath = (string) $photo['file_path'];
            $thumbPath = album_thumbnail_public_path($filePath);
            $thumbAbs = dirname(__DIR__) . '/' . ltrim($thumbPath, '/');
            $imageSrc = is_file($thumbAbs) ? $thumbPath : $filePath;
            ?>
            <figure class="gallery-item">
                <img src="<?= e(base_url($imageSrc)) ?>" alt="<?= e((string) ($photo['title'] ?: (string) $t['photo_alt'])) ?>">
                <figcaption><strong><?= e((string) $photo['title']) ?></strong><br><?= e((string) $photo['caption']) ?></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $album['title']);
