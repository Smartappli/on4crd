<?php
declare(strict_types=1);

$locale = current_locale();
$legacyStaticMessages = require __DIR__ . '/../app/i18n/static_pages_legacy.php';
$defaultNewsI18n = [
    'fr' => [
        'title' => 'Actualites',
        'unavailable' => 'Les actualites sont temporairement indisponibles.',
        'latest_news' => 'Dernieres actualites',
        'unknown_date' => 'Date inconnue',
        'latest_fallback_excerpt' => 'Plus de details seront publies prochainement.',
        'default_section' => 'Actualite',
        'published_on' => 'Publie le',
        'read_news' => 'Lire',
        'no_news_yet' => 'Aucune actualite publiee pour le moment.',
        'search_title' => 'Rechercher dans les actualites',
        'search_lead' => 'Filtrez les publications par mots-cles, periode ou categorie.',
        'active_filters' => 'filtre(s) actif(s)',
        'keywords' => 'Mots-cles',
        'keywords_placeholder' => 'Recherche...',
        'period' => 'Periode',
        'category' => 'Categorie',
        'all_categories' => 'Toutes les categories',
        'sort_by' => 'Tri',
        'sort_recent' => 'Plus recentes',
        'sort_oldest' => 'Plus anciennes',
        'sort_title' => 'Titre',
        'apply_filters' => 'Appliquer',
        'reset' => 'Reinitialiser',
        'applied_filters' => 'Filtres appliques',
        'search_filter' => 'Recherche',
        'month_filter' => 'Mois',
        'category_filter' => 'Categorie',
        'news_overview' => 'Toutes les actualites',
        'no_match' => 'Aucune actualite ne correspond a ces criteres.',
        'no_match_help' => 'Essayez de modifier ou supprimer un filtre.',
        'card_fallback_excerpt' => 'Cette actualite sera detaillee prochainement.',
        'view_article' => 'Voir l\'article',
        'page' => 'Page',
        'news_count' => 'actualite(s)',
        'previous' => 'Precedent',
        'next' => 'Suivant',
    ],
    'en' => [
        'title' => 'News',
        'unavailable' => 'News is temporarily unavailable.',
        'latest_news' => 'Latest news',
        'unknown_date' => 'Unknown date',
        'latest_fallback_excerpt' => 'More details will be published soon.',
        'default_section' => 'News',
        'published_on' => 'Published on',
        'read_news' => 'Read',
        'no_news_yet' => 'No news has been published yet.',
        'search_title' => 'Search news',
        'search_lead' => 'Filter posts by keywords, period or category.',
        'active_filters' => 'active filter(s)',
        'keywords' => 'Keywords',
        'keywords_placeholder' => 'Search...',
        'period' => 'Period',
        'category' => 'Category',
        'all_categories' => 'All categories',
        'sort_by' => 'Sort',
        'sort_recent' => 'Newest first',
        'sort_oldest' => 'Oldest first',
        'sort_title' => 'Title',
        'apply_filters' => 'Apply',
        'reset' => 'Reset',
        'applied_filters' => 'Applied filters',
        'search_filter' => 'Search',
        'month_filter' => 'Month',
        'category_filter' => 'Category',
        'news_overview' => 'All news',
        'no_match' => 'No news matches these filters.',
        'no_match_help' => 'Try changing or removing a filter.',
        'card_fallback_excerpt' => 'This news item will be detailed soon.',
        'view_article' => 'View article',
        'page' => 'Page',
        'news_count' => 'news item(s)',
        'previous' => 'Previous',
        'next' => 'Next',
    ],
];
$newsI18n = array_replace_recursive($defaultNewsI18n, (array) ($legacyStaticMessages['news_public'] ?? []));
$newsT = [];
foreach (array_keys($newsI18n['fr']) as $key) {
    $pool = [];
    foreach ($newsI18n as $lang => $translations) {
        if (isset($translations[$key]) && is_string($translations[$key])) {
            $pool[$lang] = $translations[$key];
        }
    }
    $newsT[$key] = i18n_localized_value($pool, $locale, 'fr');
}

if (!table_exists('news_posts')) {
    echo render_layout('<div class="card"><h1>' . e((string) $newsT['title']) . '</h1><p>' . e((string) $newsT['unavailable']) . '</p></div>', (string) $newsT['title']);
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
$cacheBase = 'news_list_' . md5(json_encode([$where, $params, $sort]));
$totalPosts = cache_remember($cacheBase . '_count', 45, static function () use ($where, $params): int {
    try {
        $countSql = 'SELECT COUNT(*) FROM news_posts p LEFT JOIN news_sections s ON s.id = p.section_id WHERE ' . implode(' AND ', $where);
        $countStmt = db()->prepare($countSql);
        $countStmt->execute($params);
        return (int) $countStmt->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
});
$pagination = pagination_state($totalPosts, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];
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

$posts = cache_remember($cacheBase . '_page_' . $page, 45, static function () use ($where, $orderBy, $perPage, $offset, $params): array {
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
        return $stmt->fetchAll() ?: [];
    } catch (Throwable) {
        return [];
    }
});

$categories = cache_remember('news_categories_v1', 120, static function (): array {
    try {
        return db()->query('SELECT s.slug, s.name, COUNT(p.id) AS total
            FROM news_sections s
            INNER JOIN news_posts p ON p.section_id = s.id AND p.status = "published"
            GROUP BY s.id, s.slug, s.name
            ORDER BY s.sort_order ASC, s.name ASC')->fetchAll() ?: [];
    } catch (Throwable) {
        return [];
    }
});

$archives = cache_remember('news_archives_v1', 120, static function (): array {
    try {
        return db()->query('SELECT DATE_FORMAT(COALESCE(published_at, updated_at), "%Y-%m") AS ym, COUNT(*) AS total
            FROM news_posts
            WHERE status = "published"
            GROUP BY ym
            ORDER BY ym DESC
            LIMIT 18')->fetchAll() ?: [];
    } catch (Throwable) {
        return [];
    }
});

$latestNews = cache_remember('news_latest_v1', 60, static function (): array {
    try {
        $latestNewsStmt = db()->query('SELECT p.slug, p.title, p.excerpt, p.published_at, p.updated_at, s.name AS section_name
            FROM news_posts p
            LEFT JOIN news_sections s ON s.id = p.section_id
            WHERE p.status = "published"
            ORDER BY COALESCE(p.published_at, p.updated_at) DESC
            LIMIT 2');
        return $latestNewsStmt ? ($latestNewsStmt->fetchAll() ?: []) : [];
    } catch (Throwable) {
        return [];
    }
});

ob_start();
?>
<section class="card">
    <h2 class="news-ui-heading"><?= e((string) $newsT['latest_news']) ?></h2>
    <?php if ($latestNews !== []): ?>
        <div class="news-grid latest-news-grid">
            <?php foreach ($latestNews as $latestPost): ?>
                <?php
                $latestDateRaw = (string) ($latestPost['published_at'] ?? $latestPost['updated_at'] ?? '');
                $latestDate = $latestDateRaw !== '' ? date('d/m/Y', strtotime($latestDateRaw)) : (string) $newsT['unknown_date'];
                $latestExcerpt = trim((string) ($latestPost['excerpt'] ?? ''));
                if ($latestExcerpt === '') {
                    $latestExcerpt = (string) $newsT['latest_fallback_excerpt'];
                }
                ?>
                <article class="news-card feature-card">
                    <a class="news-card-link" href="<?= e(route_url('news_view', ['slug' => (string) ($latestPost['slug'] ?? '')])) ?>">
                        <span class="badge muted"><?= e((string) ($latestPost['section_name'] ?? (string) $newsT['default_section'])) ?></span>
                        <h3><?= e((string) ($latestPost['title'] ?? (string) $newsT['default_section'])) ?></h3>
                        <p class="help"><?= e((string) $newsT['published_on']) ?> <?= e($latestDate) ?></p>
                        <p><?= e($latestExcerpt) ?></p>
                        <span class="news-card-cta"><?= e((string) $newsT['read_news']) ?></span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="news-empty-state">
            <p><?= e((string) $newsT['no_news_yet']) ?></p>
        </div>
    <?php endif; ?>
</section>

<section class="card news-filters mt-4">
    <div class="news-search-header">
        <h1 class="news-ui-heading"><?= e((string) $newsT['search_title']) ?></h1>
        <p class="help"><?= e((string) $newsT['search_lead']) ?></p>
    </div>
    <?php if ($activeFiltersCount > 0): ?>
        <div class="news-meta-row">
            <span class="badge muted"><?= $activeFiltersCount ?> <?= e((string) $newsT['active_filters']) ?></span>
        </div>
    <?php endif; ?>
    <form method="get" class="inline-form news-search-form">
        <input type="hidden" name="route" value="news">
        <label class="news-search-field news-search-field--query">
            <span><?= e((string) $newsT['keywords']) ?></span>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $newsT['keywords_placeholder']) ?>">
        </label>
        <label class="news-search-field">
            <span><?= e((string) $newsT['period']) ?></span>
            <input type="month" name="ym" value="<?= e($monthFilter) ?>">
        </label>
        <label class="news-search-field">
            <span><?= e((string) $newsT['category']) ?></span>
            <select name="category">
                <option value=""><?= e((string) $newsT['all_categories']) ?></option>
                <?php foreach ($categories as $category): ?>
                    <?php $slug = (string) ($category['slug'] ?? ''); ?>
                    <?php $categoryName = trim((string) ($category['name'] ?? '')); if ($categoryName === '') { $categoryName = (string) $newsT['category']; } ?>
                    <option value="<?= e($slug) ?>" <?= $categoryFilter === $slug ? 'selected' : '' ?>><?= e($categoryName) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="news-search-field">
            <span><?= e((string) $newsT['sort_by']) ?></span>
            <select name="sort">
                <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>><?= e((string) $newsT['sort_recent']) ?></option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>><?= e((string) $newsT['sort_oldest']) ?></option>
                <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>><?= e((string) $newsT['sort_title']) ?></option>
            </select>
        </label>
        <div class="news-search-actions">
            <button class="button" type="submit"><?= e((string) $newsT['apply_filters']) ?></button>
            <?php if ($search !== '' || $monthFilter !== '' || $categoryFilter !== ''): ?>
                <a class="button secondary" href="<?= e(route_url('news')) ?>"><?= e((string) $newsT['reset']) ?></a>
            <?php endif; ?>
        </div>
    </form>
    <?php if ($activeFiltersCount > 0): ?>
        <div class="news-active-filters">
            <strong><?= e((string) $newsT['applied_filters']) ?></strong>
            <?php if ($search !== ''): ?>
                <span class="pill"><?= e((string) $newsT['search_filter']) ?> : “<?= e($search) ?>”</span>
            <?php endif; ?>
            <?php if ($monthFilter !== ''): ?>
                <span class="pill"><?= e((string) $newsT['month_filter']) ?> : <?= e($monthFilter) ?></span>
            <?php endif; ?>
            <?php if ($categoryFilter !== ''): ?>
                <span class="pill"><?= e((string) $newsT['category_filter']) ?> : <?= e($categoryFilter) ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($categories !== []): ?>
        <div class="news-archives" id="news-categories">
            <?php foreach ($categories as $category): ?>
                <?php $slug = (string) ($category['slug'] ?? ''); ?>
                <a class="pill" href="<?= e(route_url('news', ['category' => $slug])) ?>"<?= $categoryFilter === $slug ? ' aria-current="page"' : '' ?>>
                    <?= e((string) ($category['name'] ?? (string) $newsT['category'])) ?> · <?= (int) ($category['total'] ?? 0) ?>
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

<section class="card mt-4" id="news-list">
        <h2 class="news-ui-heading"><?= e((string) $newsT['news_overview']) ?></h2>
        <?php if ($posts === []): ?>
            <div class="news-empty-state">
                <p><?= e((string) $newsT['no_match']) ?></p>
                <p class="help"><?= e((string) $newsT['no_match_help']) ?></p>
            </div>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach ($posts as $post):
                $publishedAtRaw = (string) ($post['published_at'] ?? $post['updated_at'] ?? '');
                $publishedAt = $publishedAtRaw !== '' ? date('d/m/Y', strtotime($publishedAtRaw)) : (string) $newsT['unknown_date'];
                $excerpt = trim((string) ($post['excerpt'] ?? ''));
                if ($excerpt === '') {
                    $excerpt = (string) $newsT['card_fallback_excerpt'];
                }
                ?>
                <article class="news-card feature-card">
                    <a class="news-card-link" href="<?= e(route_url('news_view', ['slug' => (string) $post['slug']])) ?>">
                        <span class="badge muted"><?= e((string) ($post['section_name'] ?? (string) $newsT['default_section'])) ?></span>
                        <h3><?= e((string) $post['title']) ?></h3>
                        <p class="help">
                            <?= e((string) $newsT['published_on']) ?> <?= e($publishedAt) ?>
                            <?php if (trim((string) ($post['author_callsign'] ?? '')) !== ''): ?>
                                · <?= e((string) $post['author_callsign']) ?>
                            <?php endif; ?>
                        </p>
                        <p><?= e($excerpt) ?></p>
                        <span class="news-card-cta"><?= e((string) $newsT['view_article']) ?></span>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
</section>

<?php if ($totalPosts > $perPage): ?>
    <section class="card mt-4">
        <div class="row-between">
            <p class="help"><?= e((string) $newsT['page']) ?> <?= (int) $page ?> / <?= (int) $totalPages ?> — <?= (int) $totalPosts ?> <?= e((string) $newsT['news_count']) ?></p>
            <p class="actions">
                <?php if ($page > 1): ?>
                    <a class="button secondary" href="<?= e(route_url_clean('news', ['q' => $search, 'ym' => $monthFilter, 'category' => $categoryFilter, 'sort' => $sort, 'p' => $page - 1])) ?>">← <?= e((string) $newsT['previous']) ?></a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="button secondary" href="<?= e(route_url_clean('news', ['q' => $search, 'ym' => $monthFilter, 'category' => $categoryFilter, 'sort' => $sort, 'p' => $page + 1])) ?>"><?= e((string) $newsT['next']) ?> →</a>
                <?php endif; ?>
            </p>
        </div>
    </section>
<?php endif; ?>
<?php
echo render_layout((string) ob_get_clean(), (string) $newsT['title']);

