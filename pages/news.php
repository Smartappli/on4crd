<?php
declare(strict_types=1);

if (!table_exists('news_posts')) {
    echo render_layout('<div class="card"><h1>Actualités</h1><p>Le flux actualités sera disponible après publication des premiers contenus.</p></div>', 'Actualités');
    return;
}

$posts = [];
$search = trim((string) ($_GET['q'] ?? ''));
$monthFilter = trim((string) ($_GET['ym'] ?? ''));
$categoryFilter = trim((string) ($_GET['category'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}$/', $monthFilter)) {
    $monthFilter = '';
}
if (!preg_match('/^[a-z0-9-]{1,100}$/', $categoryFilter)) {
    $categoryFilter = '';
}

$where = ['p.status = "published"'];
$params = [];
if ($search !== '') {
    $where[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
    $searchLike = '%' . $search . '%';
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
}
if ($monthFilter !== '') {
    $where[] = 'DATE_FORMAT(COALESCE(p.published_at, p.updated_at), "%Y-%m") = ?';
    $params[] = $monthFilter;
}
if ($categoryFilter !== '') {
    $where[] = 's.slug = ?';
    $params[] = $categoryFilter;
}

try {
    $sql = 'SELECT p.slug, p.title, p.excerpt, p.published_at, p.updated_at, s.slug AS section_slug, s.name AS section_name, m.callsign AS author_callsign
        FROM news_posts p
        LEFT JOIN news_sections s ON s.id = p.section_id
        LEFT JOIN members m ON m.id = p.author_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY COALESCE(p.published_at, p.updated_at) DESC
        LIMIT 48';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll() ?: [];
} catch (Throwable) {
    $posts = [];
}

$categories = [];
try {
    $categories = db()->query('SELECT s.slug, s.name, COUNT(p.id) AS total
        FROM news_sections s
        INNER JOIN news_posts p ON p.section_id = s.id AND p.status = "published"
        GROUP BY s.id, s.slug, s.name
        ORDER BY s.sort_order ASC, s.name ASC')->fetchAll() ?: [];
} catch (Throwable) {
    $categories = [];
}

$archives = [];
try {
    $archives = db()->query('SELECT DATE_FORMAT(COALESCE(published_at, updated_at), "%Y-%m") AS ym, COUNT(*) AS total
        FROM news_posts
        WHERE status = "published"
        GROUP BY ym
        ORDER BY ym DESC
        LIMIT 18')->fetchAll() ?: [];
} catch (Throwable) {
    $archives = [];
}

$latestRaw = (string) (($posts[0]['published_at'] ?? $posts[0]['updated_at'] ?? ''));
$latestDate = $latestRaw !== '' ? date('d/m/Y', strtotime($latestRaw)) : '—';
$sections = [];
foreach ($posts as $post) {
    $section = trim((string) ($post['section_name'] ?? ''));
    if ($section !== '') {
        $sections[$section] = true;
    }
}
$postsByCategory = [];
foreach ($posts as $post) {
    $sectionName = trim((string) ($post['section_name'] ?? ''));
    $key = $sectionName !== '' ? $sectionName : 'Sans catégorie';
    $postsByCategory[$key][] = $post;
}

ob_start();
?>
<section class="card news-page-header">
    <h1>Actualités</h1>
    <p class="help">Suivez la vie du radio-club : annonces, compte-rendus, résultats et nouvelles techniques.</p>
    <div class="stats-grid">
        <article class="stat-card">
            <span class="help">Articles publiés</span>
            <strong><?= (int) count($posts) ?></strong>
        </article>
        <article class="stat-card">
            <span class="help">Dernière publication</span>
            <strong><?= e($latestDate) ?></strong>
        </article>
        <article class="stat-card">
            <span class="help">Sections actives</span>
            <strong><?= (int) count($sections) ?></strong>
        </article>
    </div>
</section>

<section class="card news-filters">
    <h2>Rechercher et filtrer</h2>
    <form method="get" class="inline-form">
        <input type="hidden" name="route" value="news">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher une actualité (titre, extrait, contenu)">
        <input type="month" name="ym" value="<?= e($monthFilter) ?>">
        <select name="category">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $category): ?>
                <?php $slug = (string) ($category['slug'] ?? ''); ?>
                <option value="<?= e($slug) ?>" <?= $categoryFilter === $slug ? 'selected' : '' ?>><?= e((string) ($category['name'] ?? 'Catégorie')) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button" type="submit">Filtrer</button>
        <?php if ($search !== '' || $monthFilter !== '' || $categoryFilter !== ''): ?>
            <a class="button secondary" href="<?= e(route_url('news')) ?>">Réinitialiser</a>
        <?php endif; ?>
    </form>
    <?php if ($categories !== []): ?>
        <div class="news-archives">
            <?php foreach ($categories as $category): ?>
                <?php $slug = (string) ($category['slug'] ?? ''); ?>
                <a class="pill" href="<?= e(route_url('news', ['category' => $slug])) ?>"<?= $categoryFilter === $slug ? ' aria-current="page"' : '' ?>>
                    <?= e((string) ($category['name'] ?? 'Catégorie')) ?> · <?= (int) ($category['total'] ?? 0) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($archives !== []): ?>
        <div class="news-archives">
            <?php foreach ($archives as $archive):
                $ym = (string) ($archive['ym'] ?? '');
                if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
                    continue;
                }
                [$year, $month] = explode('-', $ym);
                $label = $month . '/' . $year;
                ?>
                <a class="pill" href="<?= e(route_url('news', ['ym' => $ym])) ?>"<?= $monthFilter === $ym ? ' aria-current="page"' : '' ?>>
                    <?= e($label) ?> · <?= (int) ($archive['total'] ?? 0) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Publications par catégorie</h2>
    <?php if ($posts === []): ?>
        <p>Aucune actualité publiée pour le moment.</p>
    <?php else: ?>
        <?php foreach ($postsByCategory as $categoryName => $items): ?>
            <h3><?= e((string) $categoryName) ?></h3>
            <div class="news-grid">
                <?php foreach ($items as $post):
                $publishedAtRaw = (string) ($post['published_at'] ?? $post['updated_at'] ?? '');
                $publishedAt = $publishedAtRaw !== '' ? date('d/m/Y', strtotime($publishedAtRaw)) : 'Date non définie';
                $excerpt = trim((string) ($post['excerpt'] ?? ''));
                if ($excerpt === '') {
                    $excerpt = 'Lire cette actualité pour découvrir les informations complètes.';
                }
                ?>
                <article class="news-card feature-card">
                    <span class="badge muted"><?= e((string) ($post['section_name'] ?? 'Actualité')) ?></span>
                    <h4><?= e((string) $post['title']) ?></h4>
                    <p class="help">
                        Publié le <?= e($publishedAt) ?>
                        <?php if (trim((string) ($post['author_callsign'] ?? '')) !== ''): ?>
                            · <?= e((string) $post['author_callsign']) ?>
                        <?php endif; ?>
                    </p>
                    <p><?= e($excerpt) ?></p>
                    <p><a class="button secondary" href="<?= e(route_url('news_view', ['slug' => (string) $post['slug']])) ?>">Lire l’actualité</a></p>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), 'Actualités');
