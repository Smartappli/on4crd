<?php
$i18n = [
    'fr' => ['title' => 'Recherche', 'placeholder' => 'Recherche globale', 'submit' => 'Rechercher', 'count' => 'résultat(s)', 'empty' => 'Aucun résultat pour cette recherche.', 'query_too_short' => 'Veuillez saisir au moins 2 caractères.', 'previous' => 'Précédent', 'next' => 'Suivant', 'meta_desc' => 'Recherche globale ON4CRD sur les contenus Articles et Wiki.'],
    'en' => ['title' => 'Search', 'placeholder' => 'Global search', 'submit' => 'Search', 'count' => 'result(s)', 'empty' => 'No results found for this query.', 'query_too_short' => 'Please enter at least 2 characters.', 'previous' => 'Previous', 'next' => 'Next', 'meta_desc' => 'ON4CRD global search across Articles and Wiki content.'],
    'de' => ['title' => 'Suche', 'placeholder' => 'Globale Suche', 'submit' => 'Suchen', 'count' => 'Ergebnis(se)', 'empty' => 'Keine Ergebnisse für diese Suche gefunden.', 'query_too_short' => 'Bitte mindestens 2 Zeichen eingeben.', 'previous' => 'Zurück', 'next' => 'Weiter', 'meta_desc' => 'Globale ON4CRD-Suche über Artikel- und Wiki-Inhalte.'],
    'nl' => ['title' => 'Zoeken', 'placeholder' => 'Globale zoekopdracht', 'submit' => 'Zoeken', 'count' => 'resulta(a)t(en)', 'empty' => 'Geen resultaten gevonden voor deze zoekopdracht.', 'query_too_short' => 'Voer minstens 2 tekens in.', 'previous' => 'Vorige', 'next' => 'Volgende', 'meta_desc' => 'ON4CRD globale zoekfunctie over Artikels- en Wiki-inhoud.'],
];
$locale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
$t = $i18n[$locale] ?? $i18n['fr'];

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
$offset = ($page - 1) * $perPage;
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
            <?php if ($hasPrev): ?><a class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="<?= e(route_url('search', ['q' => $q, 'page' => ($page - 1)])) ?>"><?= e((string) $t['previous']) ?></a><?php endif; ?>
            <?php if ($hasNext): ?><a class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="<?= e(route_url('search', ['q' => $q, 'page' => ($page + 1)])) ?>"><?= e((string) $t['next']) ?></a><?php endif; ?>
        </nav>
    <?php endif; ?>
    </div>
</section>
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);
