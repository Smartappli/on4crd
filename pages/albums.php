<?php
declare(strict_types=1);

if (!table_exists('albums') || !table_exists('album_photos')) {
    echo render_layout('<div class="card"><h1>Albums publics</h1><p>La galerie sera disponible après initialisation.</p></div>', 'Albums');
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
$rows = $stmt->fetchAll() ?: [];

$photoTotal = 0;
foreach ($rows as $row) {
    $photoTotal += (int) ($row['photo_count'] ?? 0);
}

ob_start();
?>
<section class="card gallery-header">
    <div class="row-between">
        <h1>Albums publics</h1>
        <?php if (has_permission('albums.manage')): ?>
            <a class="button small" href="<?= e(base_url('index.php?route=admin_albums')) ?>">Gérer</a>
        <?php endif; ?>
    </div>
    <p class="help">Explorez les activités du club en images : ateliers, sorties, contests et moments de vie associative.</p>
    <div class="stats-grid">
        <article class="stat-card">
            <span class="help">Albums</span>
            <strong><?= (int) count($rows) ?></strong>
        </article>
        <article class="stat-card">
            <span class="help">Photos indexées</span>
            <strong><?= (int) $photoTotal ?></strong>
        </article>
    </div>
    <form method="get" class="inline-form">
        <input type="hidden" name="route" value="albums">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher un album (titre, description)">
        <button class="button" type="submit">Rechercher</button>
        <?php if ($search !== ''): ?>
            <a class="button secondary" href="<?= e(route_url('albums')) ?>">Réinitialiser</a>
        <?php endif; ?>
    </form>
</section>

<section class="card">
    <h2>Galerie</h2>
    <?php if ($rows === []): ?>
        <p class="help">Aucun album public disponible<?= $search !== '' ? ' pour cette recherche' : '' ?>.</p>
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
</section>
<?php
echo render_layout((string) ob_get_clean(), 'Albums');
