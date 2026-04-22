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
$sort = (string) ($_GET['sort'] ?? 'recent');
$page = max(1, (int) ($_GET['p'] ?? 1));
if (!in_array($sort, ['recent', 'oldest', 'title'], true)) {
    $sort = 'recent';
}
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
$orderBy = match ($sort) {
    'oldest' => 'COALESCE(p.published_at, p.updated_at) ASC',
    'title' => 'p.title ASC',
    default => 'COALESCE(p.published_at, p.updated_at) DESC',
};
$perPage = 18;
$totalPosts = 0;
try {
    $countSql = 'SELECT COUNT(*) FROM news_posts p LEFT JOIN news_sections s ON s.id = p.section_id WHERE ' . implode(' AND ', $where);
    $countStmt = db()->prepare($countSql);
    $countStmt->execute($params);
    $totalPosts = (int) $countStmt->fetchColumn();
} catch (Throwable) {
    $totalPosts = 0;
}
$totalPages = max(1, (int) ceil($totalPosts / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;
$activeFiltersCount = 0;
if ($search !== '') {
    $activeFiltersCount++;
}
if ($monthFilter !== '') {
    $activeFiltersCount++;
}
if ($categoryFilter !== '') {
    $activeFiltersCount++;
}
$resultStart = $totalPosts > 0 ? ($offset + 1) : 0;
$resultEnd = min($offset + $perPage, $totalPosts);

try {
    $sql = 'SELECT p.slug, p.title, p.excerpt, p.published_at, p.updated_at, s.slug AS section_slug, s.name AS section_name, m.callsign AS author_callsign
        FROM news_posts p
        LEFT JOIN news_sections s ON s.id = p.section_id
        LEFT JOIN members m ON m.id = p.author_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY ' . $orderBy . '
        LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
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

$latestNews = null;
try {
    $latestNewsStmt = db()->query('SELECT p.slug, p.title, p.excerpt, p.published_at, p.updated_at, s.name AS section_name
        FROM news_posts p
        LEFT JOIN news_sections s ON s.id = p.section_id
        WHERE p.status = "published"
        ORDER BY COALESCE(p.published_at, p.updated_at) DESC
        LIMIT 1');
    $latestNews = $latestNewsStmt ? $latestNewsStmt->fetch() : null;
} catch (Throwable) {
    $latestNews = null;
}

ob_start();
?>
<section class="card">
    <h2>Dernière actualité</h2>
    <?php if (is_array($latestNews)): ?>
        <?php
        $latestDateRaw = (string) ($latestNews['published_at'] ?? $latestNews['updated_at'] ?? '');
        $latestDate = $latestDateRaw !== '' ? date('d/m/Y', strtotime($latestDateRaw)) : 'Date non définie';
        $latestExcerpt = trim((string) ($latestNews['excerpt'] ?? ''));
        if ($latestExcerpt === '') {
            $latestExcerpt = 'Consultez la dernière publication du club.';
        }
        ?>
        <article class="news-card feature-card">
            <a class="news-card-link" href="<?= e(route_url('news_view', ['slug' => (string) ($latestNews['slug'] ?? '')])) ?>">
                <span class="badge muted"><?= e((string) ($latestNews['section_name'] ?? 'Actualité')) ?></span>
                <h3><?= e((string) ($latestNews['title'] ?? 'Actualité')) ?></h3>
                <p class="help">Publié le <?= e($latestDate) ?></p>
                <p><?= e($latestExcerpt) ?></p>
                <span class="news-card-cta">Lire l’actualité →</span>
            </a>
        </article>
    <?php else: ?>
        <div class="news-empty-state">
            <p>Aucune actualité publiée pour le moment.</p>
        </div>
    <?php endif; ?>
</section>

<section class="card news-filters mt-4">
    <h1>Recherche d’actualités</h1>
    <?php if ($activeFiltersCount > 0): ?>
        <div class="news-meta-row">
            <span class="badge muted"><?= $activeFiltersCount ?> filtre<?= $activeFiltersCount > 1 ? 's actifs' : ' actif' ?></span>
        </div>
    <?php endif; ?>
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
        <select name="sort">
            <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Plus récentes</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Plus anciennes</option>
            <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Titre (A→Z)</option>
        </select>
        <button class="button" type="submit">Filtrer</button>
        <?php if ($search !== '' || $monthFilter !== '' || $categoryFilter !== ''): ?>
            <a class="button secondary" href="<?= e(route_url('news')) ?>">Réinitialiser</a>
        <?php endif; ?>
    </form>
    <div class="news-quick-links">
        <span class="help">Accès rapide :</span>
        <a class="pill" href="#news-list">Liste des actualités</a>
        <a class="pill" href="#news-categories">Catégories</a>
        <a class="pill" href="#news-archives">Archives</a>
    </div>
    <?php if ($activeFiltersCount > 0): ?>
        <div class="news-active-filters">
            <strong>Filtres appliqués :</strong>
            <?php if ($search !== ''): ?>
                <span class="pill">Recherche : “<?= e($search) ?>”</span>
            <?php endif; ?>
            <?php if ($monthFilter !== ''): ?>
                <span class="pill">Mois : <?= e($monthFilter) ?></span>
            <?php endif; ?>
            <?php if ($categoryFilter !== ''): ?>
                <span class="pill">Catégorie : <?= e($categoryFilter) ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($categories !== []): ?>
        <div class="news-archives" id="news-categories">
            <?php foreach ($categories as $category): ?>
                <?php $slug = (string) ($category['slug'] ?? ''); ?>
                <a class="pill" href="<?= e(route_url('news', ['category' => $slug])) ?>"<?= $categoryFilter === $slug ? ' aria-current="page"' : '' ?>>
                    <?= e((string) ($category['name'] ?? 'Catégorie')) ?> · <?= (int) ($category['total'] ?? 0) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($archives !== []): ?>
        <div class="news-archives" id="news-archives">
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

<section class="news-layout mt-4">
    <aside class="card news-categories">
        <h2>Catégories</h2>
        <?php if ($categories === []): ?>
            <p class="help">Aucune catégorie publiée pour le moment.</p>
        <?php else: ?>
            <nav class="news-category-list" aria-label="Filtrer par catégorie">
                <a class="news-category-item" href="<?= e(route_url('news')) ?>"<?= $categoryFilter === '' ? ' aria-current="page"' : '' ?>>
                    <span>Toutes les catégories</span>
                    <span class="badge muted"><?= (int) $totalPosts ?></span>
                </a>
                <?php foreach ($categories as $category): ?>
                    <?php
                    $slug = (string) ($category['slug'] ?? '');
                    $name = (string) ($category['name'] ?? 'Catégorie');
                    $total = (int) ($category['total'] ?? 0);
                    ?>
                    <a class="news-category-item" href="<?= e(route_url('news', ['category' => $slug])) ?>"<?= $categoryFilter === $slug ? ' aria-current="page"' : '' ?>>
                        <span><?= e($name) ?></span>
                        <span class="badge muted"><?= $total ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
        <?php if ($archives !== []): ?>
            <h3>Archives</h3>
            <div class="news-category-list">
                <?php foreach ($archives as $archive):
                    $ym = (string) ($archive['ym'] ?? '');
                    if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
                        continue;
                    }
                    ?>
                    <a class="news-category-item" href="<?= e(route_url('news', ['ym' => $ym])) ?>"<?= $monthFilter === $ym ? ' aria-current="page"' : '' ?>>
                        <span><?= e($ym) ?></span>
                        <span class="badge muted"><?= (int) ($archive['total'] ?? 0) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>

    <div class="card" id="news-list">
        <h2>Aperçu des actualités</h2>
        <?php if ($posts === []): ?>
            <div class="news-empty-state">
                <p>Aucune actualité ne correspond à vos filtres.</p>
                <p class="help">Essayez de supprimer un filtre ou de lancer une recherche plus large.</p>
            </div>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach ($posts as $post):
                $publishedAtRaw = (string) ($post['published_at'] ?? $post['updated_at'] ?? '');
                $publishedAt = $publishedAtRaw !== '' ? date('d/m/Y', strtotime($publishedAtRaw)) : 'Date non définie';
                $excerpt = trim((string) ($post['excerpt'] ?? ''));
                if ($excerpt === '') {
                    $excerpt = 'Lire cette actualité pour découvrir les informations complètes.';
                }
                ?>
                <article class="news-card feature-card">
                    <a class="news-card-link" href="<?= e(route_url('news_view', ['slug' => (string) $post['slug']])) ?>">
                        <span class="badge muted"><?= e((string) ($post['section_name'] ?? 'Actualité')) ?></span>
                        <h3><?= e((string) $post['title']) ?></h3>
                        <p class="help">
                            Publié le <?= e($publishedAt) ?>
                            <?php if (trim((string) ($post['author_callsign'] ?? '')) !== ''): ?>
                                · <?= e((string) $post['author_callsign']) ?>
                            <?php endif; ?>
                        </p>
                        <p><?= e($excerpt) ?></p>
                        <span class="news-card-cta">Voir l’article →</span>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($totalPosts > $perPage): ?>
    <section class="card mt-4">
        <div class="row-between">
            <p class="help">Page <?= (int) $page ?> / <?= (int) $totalPages ?> — <?= (int) $totalPosts ?> actualité<?= $totalPosts > 1 ? 's' : '' ?></p>
            <p class="actions">
                <?php if ($page > 1): ?>
                    <a class="button secondary" href="<?= e(route_url('news', ['q' => $search, 'ym' => $monthFilter, 'category' => $categoryFilter, 'sort' => $sort, 'p' => $page - 1])) ?>">← Précédent</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="button secondary" href="<?= e(route_url('news', ['q' => $search, 'ym' => $monthFilter, 'category' => $categoryFilter, 'sort' => $sort, 'p' => $page + 1])) ?>">Suivant →</a>
                <?php endif; ?>
            </p>
        </div>
    </section>
<?php endif; ?>
<?php
echo render_layout((string) ob_get_clean(), 'Actualités');
