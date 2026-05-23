<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('albums', $locale);
set_page_meta(['title' => (string) $t['public_albums'], 'description' => (string) $t['meta_desc']]);
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'toggle_favorite_album') {
    $user = require_login();
    verify_csrf();
    $albumId = (int) ($_POST['album_id'] ?? 0);
    if ($albumId > 0) {
        $favStmt = db()->prepare('SELECT id, title FROM albums WHERE id = ? AND is_public = 1 LIMIT 1');
        $favStmt->execute([$albumId]);
        $favRow = $favStmt->fetch() ?: null;
        if ($favRow !== null) {
            $favTitle = trim((string) ($favRow['title'] ?? 'Album'));
            $favUrl = route_url('album', ['id' => (int) $favRow['id']]);
            $saved = favorite_toggle((int) $user['id'], 'album', (int) $favRow['id'], $favTitle, $favUrl);
            notify_member((int) $user['id'], 'favorite', $saved ? 'Favorite added' : 'Favorite removed', $favTitle, $favUrl);
            set_flash('success', $saved ? 'Album added to favorites.' : 'Album removed from favorites.');
        }
    }
    redirect_url(route_url_clean('albums', ['q' => (string) ($_GET['q'] ?? ''), 'p' => max(1, (int) ($_GET['p'] ?? 1))]));
}

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
        (SELECT p.file_path FROM album_photos p WHERE p.album_id = a.id ORDER BY p.sort_order ASC, p.id ASC LIMIT 1) AS cover_path
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
<div class="albums-page">
    <section class="albums-hero">
        <div>
            <p class="eyebrow"><?= e((string) $t['gallery']) ?></p>
            <h1><?= e((string) $t['public_albums']) ?></h1>
            <p><?= e((string) $t['intro']) ?></p>
        </div>
        <div class="albums-hero-stats">
            <article>
                <span><?= e((string) $t['albums']) ?></span>
                <strong><?= (int) $totalAlbums ?></strong>
            </article>
            <article>
                <span><?= e((string) $t['indexed_photos']) ?></span>
                <strong><?= (int) $photoTotal ?></strong>
            </article>
        </div>
    </section>

    <section class="albums-toolbar">
        <form method="get" class="albums-search-form">
            <input type="hidden" name="route" value="albums">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== ''): ?>
                <a class="button secondary" href="<?= e(route_url('albums')) ?>"><?= e((string) $t['reset']) ?></a>
            <?php endif; ?>
        </form>
    </section>

    <section class="albums-gallery">
        <?php if ($rows === []): ?>
            <article class="albums-empty">
                <h2><?= e((string) $t['none']) ?></h2>
                <p class="help"><?= $search !== '' ? e((string) $t['for_search']) : e((string) $t['intro']) ?></p>
            </article>
        <?php else: ?>
            <div class="albums-grid">
                <?php foreach ($rows as $row):
                    $coverPath = safe_storage_public_path_or_null((string) ($row['cover_path'] ?? ''), ['storage/uploads/albums/']);
                    $coverThumb = $coverPath !== null ? album_thumbnail_public_path($coverPath) : '';
                    $coverThumbAbs = $coverThumb !== '' ? dirname(__DIR__) . '/' . $coverThumb : '';
                    $coverSrc = $coverThumb !== '' && is_file($coverThumbAbs) ? $coverThumb : ($coverPath ?? '');
                    $photoCount = (int) ($row['photo_count'] ?? 0);
                    $description = trim((string) ($row['description'] ?? ''));
                    ?>
                    <article class="album-tile">
                        <a class="album-tile-media" href="<?= e(route_url('album', ['id' => (int) $row['id']])) ?>">
                            <?php if ($coverSrc !== ''): ?>
                                <img src="<?= e(base_url($coverSrc)) ?>" alt="<?= e((string) $t['cover_alt']) ?> <?= e((string) $row['title']) ?>">
                            <?php else: ?>
                                <span class="album-placeholder-mark" aria-hidden="true"></span>
                            <?php endif; ?>
                        </a>
                        <div class="album-tile-body">
                            <div>
                                <h2><a href="<?= e(route_url('album', ['id' => (int) $row['id']])) ?>"><?= e((string) $row['title']) ?></a></h2>
                                <?php if ($description !== ''): ?>
                                    <p><?= e(mb_safe_strimwidth($description, 0, 150, '...')) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="album-tile-footer">
                                <span class="badge muted"><?= $photoCount ?> <?= e((string) ($photoCount > 1 ? $t['photos'] : $t['photo'])) ?></span>
                                <?php if ($user !== null): ?>
                                    <?php $isFavorite = favorite_is_saved((int) $user['id'], 'album', (int) ($row['id'] ?? 0)); ?>
                                    <form method="post" class="album-favorite-form">
                                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="toggle_favorite_album">
                                        <input type="hidden" name="album_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                                        <button class="button secondary" type="submit"><?= $isFavorite ? '&#9733;' : '&#9734;' ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
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
