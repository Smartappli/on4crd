<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['public_albums' => 'Albums publics', 'gallery_unavailable' => 'La galerie sera disponible après initialisation.', 'manage' => 'Gérer', 'intro' => 'Explorez les activités du club en images : ateliers, sorties, contests et moments de vie associative.', 'albums' => 'Albums', 'indexed_photos' => 'Photos indexées', 'search_placeholder' => 'Rechercher un album (titre, description)', 'search' => 'Rechercher', 'reset' => 'Réinitialiser', 'gallery' => 'Galerie', 'none' => 'Aucun album public disponible', 'for_search' => ' pour cette recherche', 'cover_alt' => 'Couverture de l’album', 'photo' => 'photo'],
    'en' => ['public_albums' => 'Public albums', 'gallery_unavailable' => 'The gallery will be available after initialization.', 'manage' => 'Manage', 'intro' => 'Explore club activities in pictures: workshops, outings, contests and community moments.', 'albums' => 'Albums', 'indexed_photos' => 'Indexed photos', 'search_placeholder' => 'Search an album (title, description)', 'search' => 'Search', 'reset' => 'Reset', 'gallery' => 'Gallery', 'none' => 'No public album available', 'for_search' => ' for this search', 'cover_alt' => 'Album cover', 'photo' => 'photo'],
    'de' => ['public_albums' => 'Öffentliche Alben', 'gallery_unavailable' => 'Die Galerie ist nach der Initialisierung verfügbar.', 'manage' => 'Verwalten', 'intro' => 'Entdecken Sie Clubaktivitäten in Bildern: Workshops, Ausflüge, Contests und Vereinsmomente.', 'albums' => 'Alben', 'indexed_photos' => 'Indizierte Fotos', 'search_placeholder' => 'Album suchen (Titel, Beschreibung)', 'search' => 'Suchen', 'reset' => 'Zurücksetzen', 'gallery' => 'Galerie', 'none' => 'Kein öffentliches Album verfügbar', 'for_search' => ' für diese Suche', 'cover_alt' => 'Albumcover', 'photo' => 'Foto'],
    'nl' => ['public_albums' => 'Openbare albums', 'gallery_unavailable' => 'De galerij is beschikbaar na initialisatie.', 'manage' => 'Beheren', 'intro' => 'Ontdek clubactiviteiten in beeld: workshops, uitstappen, contests en verenigingsmomenten.', 'albums' => 'Albums', 'indexed_photos' => "Geïndexeerde foto's", 'search_placeholder' => 'Zoek een album (titel, beschrijving)', 'search' => 'Zoeken', 'reset' => 'Reset', 'gallery' => 'Galerij', 'none' => 'Geen openbaar album beschikbaar', 'for_search' => ' voor deze zoekopdracht', 'cover_alt' => 'Albumcover', 'photo' => 'foto'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

if (!table_exists('albums') || !table_exists('album_photos')) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['public_albums']) . '</h1><p>' . e((string) $t['gallery_unavailable']) . '</p></div>', (string) $t['albums']);
    return;
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}

$params = [];
$where = 'a.is_public = 1';
if ($search !== '') {
    $where .= ' AND (a.title LIKE ? OR a.description LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$rows = cache_remember('albums_public_' . md5($where . '|' . json_encode($params)), 90, static function () use ($where, $params): array {
    $stmt = db()->prepare(
        'SELECT a.*, 
            (SELECT COUNT(*) FROM album_photos p WHERE p.album_id = a.id) AS photo_count,
            (SELECT p.file_path FROM album_photos p WHERE p.album_id = a.id ORDER BY p.id DESC LIMIT 1) AS cover_path
         FROM albums a
         WHERE ' . $where . '
         ORDER BY a.id DESC
         LIMIT 120'
    );
    $stmt->execute($params);
    return $stmt->fetchAll() ?: [];
});

$photoTotal = 0;
foreach ($rows as $row) {
    $photoTotal += (int) ($row['photo_count'] ?? 0);
}

ob_start();
?>
<div class="stack">
    <section class="card gallery-header">
        <div class="row-between">
            <h1><?= e((string) $t['public_albums']) ?></h1>
            <?php if (has_permission('albums.manage')): ?>
                <a class="button small" href="<?= e(base_url('index.php?route=admin_albums')) ?>"><?= e((string) $t['manage']) ?></a>
            <?php endif; ?>
        </div>
        <p class="help"><?= e((string) $t['intro']) ?></p>
        <div class="stats-grid">
            <article class="stat-card">
                <span class="help"><?= e((string) $t['albums']) ?></span>
                <strong><?= (int) count($rows) ?></strong>
            </article>
            <article class="stat-card">
                <span class="help"><?= e((string) $t['indexed_photos']) ?></span>
                <strong><?= (int) $photoTotal ?></strong>
            </article>
        </div>
        <form method="get" class="inline-form">
            <input type="hidden" name="route" value="albums">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== ''): ?>
                <a class="button secondary" href="<?= e(route_url('albums')) ?>"><?= e((string) $t['reset']) ?></a>
            <?php endif; ?>
        </form>
    </section>

    <section class="card">
        <h2><?= e((string) $t['gallery']) ?></h2>
        <?php if ($rows === []): ?>
            <p class="help"><?= e((string) $t['none']) ?><?= $search !== '' ? e((string) $t['for_search']) : '' ?>.</p>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($rows as $row):
                    $coverPath = trim((string) ($row['cover_path'] ?? ''));
                    $coverThumb = $coverPath !== '' ? album_thumbnail_public_path($coverPath) : '';
                    $coverThumbAbs = $coverThumb !== '' ? dirname(__DIR__) . '/' . ltrim($coverThumb, '/') : '';
                    $coverSrc = $coverPath !== '' && is_file($coverThumbAbs) ? $coverThumb : $coverPath;
                    $photoCount = (int) ($row['photo_count'] ?? 0);
                    ?>
                    <article class="gallery-item album-card">
                        <a class="album-card-link" href="<?= e(base_url('index.php?route=album&id=' . (int) $row['id'])) ?>">
                            <?php if ($coverPath !== ''): ?>
                                <img src="<?= e(base_url($coverSrc)) ?>" alt="<?= e((string) $t['cover_alt']) ?> <?= e((string) $row['title']) ?>">
                            <?php else: ?>
                                <div class="album-card-placeholder">📷</div>
                            <?php endif; ?>
                            <h2><?= e((string) $row['title']) ?></h2>
                            <p class="help"><?= e((string) $row['description']) ?></p>
                            <p><span class="badge muted"><?= $photoCount ?> <?= e((string) $t['photo']) ?><?= $photoCount > 1 ? 's' : '' ?></span></p>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['albums']);
