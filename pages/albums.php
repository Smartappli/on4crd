<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('albums', $locale);
set_page_meta(['title' => (string) $t['public_albums'], 'description' => (string) $t['meta_desc']]);

if (!table_exists('albums') || !table_exists('album_photos')) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['public_albums']) . '</h1><p>' . e((string) $t['gallery_unavailable']) . '</p></div>', (string) $t['albums']);
    return;
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 12;

$params = [];
$where = 'a.is_public = 1';
if ($search !== '') {
    $where .= ' AND (a.title LIKE ? OR a.description LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$countStmt = db()->prepare('SELECT COUNT(*) FROM albums a WHERE ' . $where);
$countStmt->execute($params);
$totalAlbums = (int) $countStmt->fetchColumn();
$pagination = pagination_state($totalAlbums, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];

$stmt = db()->prepare(
    'SELECT a.*,
        (SELECT COUNT(*) FROM album_photos p WHERE p.album_id = a.id) AS photo_count,
        (SELECT p.file_path FROM album_photos p WHERE p.album_id = a.id ORDER BY p.id DESC LIMIT 1) AS cover_path
     FROM albums a
     WHERE ' . $where . '
     ORDER BY a.id DESC
     LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
);
$stmt->execute($params);
$rows = $stmt->fetchAll() ?: [];

$photoTotalStmt = db()->prepare(
    'SELECT COUNT(*)
     FROM album_photos p
     INNER JOIN albums a ON a.id = p.album_id
     WHERE ' . $where
);
$photoTotalStmt->execute($params);
$photoTotal = (int) $photoTotalStmt->fetchColumn();

ob_start();
?>
<div class="stack">
    <section class="card gallery-header">
        <div class="row-between">
            <div>
                <h1 class="album-heading-font"><?= e((string) $t['public_albums']) ?></h1>
                <p class="help"><?= e((string) $t['intro']) ?></p>
            </div>
        </div>
        <div class="stats-grid">
            <article class="stat-card">
                <span class="help album-heading-font"><?= e((string) $t['albums']) ?></span>
                <strong><?= (int) $totalAlbums ?></strong>
            </article>
            <article class="stat-card">
                <span class="help album-heading-font"><?= e((string) $t['indexed_photos']) ?></span>
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
        <h2 class="album-heading-font"><?= e((string) $t['gallery']) ?></h2>
        <?php if ($rows === []): ?>
            <p class="help"><?= e((string) $t['none']) ?><?= $search !== '' ? e((string) $t['for_search']) : '' ?>.</p>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($rows as $row):
                    $coverPath = safe_storage_public_path_or_null((string) ($row['cover_path'] ?? ''), ['storage/uploads/albums/']);
                    $coverThumb = $coverPath !== null ? album_thumbnail_public_path($coverPath) : '';
                    $coverThumbAbs = $coverThumb !== '' ? dirname(__DIR__) . '/' . $coverThumb : '';
                    $coverSrc = $coverThumb !== '' && is_file($coverThumbAbs) ? $coverThumb : ($coverPath ?? '');
                    $photoCount = (int) ($row['photo_count'] ?? 0);
                    ?>
                    <article class="gallery-item album-card">
                        <a class="album-card-link" href="<?= e(route_url('album', ['id' => (int) $row['id']])) ?>">
                            <?php if ($coverSrc !== ''): ?>
                                <img src="<?= e(base_url($coverSrc)) ?>" alt="<?= e((string) $t['cover_alt']) ?> <?= e((string) $row['title']) ?>">
                            <?php else: ?>
                                <div class="album-card-placeholder" aria-hidden="true">📷</div>
                            <?php endif; ?>
                            <h2><?= e((string) $row['title']) ?></h2>
                            <?php if (trim((string) ($row['description'] ?? '')) !== ''): ?>
                                <p class="help"><?= e(mb_safe_strimwidth((string) $row['description'], 0, 150, '...')) ?></p>
                            <?php endif; ?>
                            <p><span class="badge muted"><?= $photoCount ?> <?= e((string) ($photoCount > 1 ? $t['photos'] : $t['photo'])) ?></span></p>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <nav class="actions mt-3" aria-label="<?= e((string) $t['pagination']) ?>">
                    <?php if ($page > 1): ?>
                        <a class="button secondary" href="<?= e(route_url_clean('albums', ['q' => $search, 'p' => $page - 1])) ?>"><?= e((string) $t['previous']) ?></a>
                    <?php endif; ?>
                    <span class="pill"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a class="button secondary" href="<?= e(route_url_clean('albums', ['q' => $search, 'p' => $page + 1])) ?>"><?= e((string) $t['next']) ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['albums']);
