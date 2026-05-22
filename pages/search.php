<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('search', $locale);

$q = trim((string) ($_GET['q'] ?? ''));
$q = preg_replace('/\s+/u', ' ', $q) ?? '';
$q = mb_substr($q, 0, 120);
$tokens = preg_split('/[\s\p{P}]+/u', mb_strtolower($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];
$tokens = array_values(array_unique(array_filter($tokens, static fn(string $token): bool => mb_strlen($token) >= 2)));
$tokens = array_slice($tokens, 0, 5);
$hasQuery = $q !== '';
$isQueryLongEnough = mb_strlen($q) >= 2;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$results = [];
if ($hasQuery && $isQueryLongEnough) {
    $results = cache_remember('site_search_' . current_locale() . '_' . md5(mb_strtolower($q)), 120, static function () use ($q, $tokens): array {
        $like = '%' . $q . '%';
        $queryLikes = array_map(static fn(string $term): string => '%' . $term . '%', $tokens);
        $collected = [];
        if (table_exists('articles')) {
            $where = '(title LIKE ? OR excerpt LIKE ? OR content LIKE ?)';
            $params = [$like, $like, $like];
            foreach ($queryLikes as $termLike) {
                $where .= ' OR (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)';
                array_push($params, $termLike, $termLike, $termLike);
            }
            $stmt = db()->prepare('SELECT title, excerpt, slug, content FROM articles WHERE status = "published" AND (' . $where . ') ORDER BY updated_at DESC LIMIT 30');
            $stmt->execute($params);
            foreach ($stmt->fetchAll() as $row) {
                $title = (string) $row['title'];
                $summary = (string) ($row['excerpt'] ?? '');
                $contentSnippet = mb_substr(strip_tags((string) ($row['content'] ?? '')), 0, 240);
                $score = 0;
                if (stripos($title, $q) !== false) {
                    $score += 4;
                }
                if (stripos($summary, $q) !== false) {
                    $score += 2;
                }
                foreach ($tokens as $term) {
                    if (stripos($title, $term) !== false) {
                        $score += 2;
                    }
                    if (stripos($summary, $term) !== false || stripos($contentSnippet, $term) !== false) {
                        $score += 1;
                    }
                }
                $collected[] = ['title' => $title, 'summary' => $summary, 'url' => route_url('article', ['slug' => (string) $row['slug']]), 'score' => $score];
            }
        }
        if (table_exists('wiki_pages')) {
            $where = '(title LIKE ? OR content LIKE ?)';
            $params = [$like, $like];
            foreach ($queryLikes as $termLike) {
                $where .= ' OR (title LIKE ? OR content LIKE ?)';
                array_push($params, $termLike, $termLike);
            }
            $stmt = db()->prepare('SELECT title, content, slug FROM wiki_pages WHERE status = "published" AND (' . $where . ') ORDER BY updated_at DESC LIMIT 30');
            $stmt->execute($params);
            foreach ($stmt->fetchAll() as $row) {
                $title = (string) $row['title'];
                $summary = mb_substr(strip_tags((string) ($row['content'] ?? '')), 0, 180);
                $score = 0;
                if (stripos($title, $q) !== false) {
                    $score += 4;
                }
                if (stripos($summary, $q) !== false) {
                    $score += 1;
                }
                foreach ($tokens as $term) {
                    if (stripos($title, $term) !== false) {
                        $score += 2;
                    }
                    if (stripos($summary, $term) !== false) {
                        $score += 1;
                    }
                }
                $collected[] = ['title' => $title, 'summary' => $summary, 'url' => route_url('wiki_view', ['slug' => (string) $row['slug']]), 'score' => $score];
            }
        }
        return $collected;
    });
}
$uniqueResults = [];
foreach ($results as $result) {
    $urlKey = (string) ($result['url'] ?? '');
    if ($urlKey === '') {
        continue;
    }
    if (!isset($uniqueResults[$urlKey]) || (int) ($result['score'] ?? 0) > (int) ($uniqueResults[$urlKey]['score'] ?? 0)) {
        $uniqueResults[$urlKey] = $result;
    }
}
$results = array_values($uniqueResults);
usort($results, static fn(array $a, array $b): int => ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0)));
$totalResults = count($results);
$pagination = pagination_state($totalResults, $page, $perPage);
$page = $pagination['page'];
$offset = $pagination['offset'];
$pagedResults = array_slice($results, $offset, $perPage);
$hasPrev = $page > 1;
$hasNext = ($offset + $perPage) < $totalResults;
set_page_meta([
    'title' => (string) $t['title'],
    'description' => (string) $t['meta_desc'],
    'robots' => $q === '' ? 'noindex,follow' : 'noindex,nofollow',
]);
ob_start();
?>
<section class="search-page">
    <div class="card search-page-card narrow">
    <h1><?= e((string) $t['title']) ?></h1>
    <form method="get" class="search-page-form">
        <input type="hidden" name="route" value="search">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e((string) $t['placeholder']) ?>" required>
        <button class="button" type="submit"><?= e((string) $t['submit']) ?></button>
    </form>
    <p><?= $totalResults ?> <?= e((string) $t['count']) ?></p>
    <?php if ($hasQuery && !$isQueryLongEnough): ?>
        <p><?= e((string) $t['query_too_short']) ?></p>
    <?php elseif ($hasQuery && $totalResults === 0): ?>
        <p><?= e((string) $t['empty']) ?></p>
    <?php endif; ?>
    <?php foreach ($pagedResults as $item): ?>
        <article class="card" style="margin-top:12px;">
            <h3><a href="<?= e($item['url']) ?>"><?= e($item['title']) ?></a></h3>
            <p><?= e($item['summary']) ?></p>
        </article>
    <?php endforeach; ?>
    <?php if ($totalResults > $perPage): ?>
        <nav class="flex items-center gap-2" aria-label="Pagination">
            <?php if ($hasPrev): ?><a class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="<?= e(route_url_clean('search', ['q' => $q, 'page' => ($page - 1)])) ?>"><?= e((string) $t['previous']) ?></a><?php endif; ?>
            <?php if ($hasNext): ?><a class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="<?= e(route_url_clean('search', ['q' => $q, 'page' => ($page + 1)])) ?>"><?= e((string) $t['next']) ?></a><?php endif; ?>
        </nav>
    <?php endif; ?>
    </div>
</section>
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);
