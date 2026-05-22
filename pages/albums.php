<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = i18n_domain_messages('albums');
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $pool = [];
    foreach ($i18n as $lang => $translations) {
        if (isset($translations[$key]) && is_string($translations[$key])) {
            $pool[$lang] = $translations[$key];
        }
    }
    $t[$key] = i18n_localized_value($pool, $locale, 'fr');
}

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
            <h1 class="album-heading-font"><?= e((string) $t['public_albums']) ?></h1>
        </div>
        <p class="help"><?= e((string) $t['intro']) ?></p>
        <div class="stats-grid">
            <article class="stat-card">
                <span class="help album-heading-font"><?= e((string) $t['albums']) ?></span>
                <strong><?= (int) count($rows) ?></strong>
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
