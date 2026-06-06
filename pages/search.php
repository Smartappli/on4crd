<?php
declare(strict_types=1);

if (!function_exists('search_text_score')) {
    /**
     * @param array<int, string> $tokens
     */
    function search_text_score(string $query, array $tokens, string $title, string $summary, string $body = ''): int
    {
        $score = 0;
        if ($query !== '' && mb_stripos($title, $query) !== false) {
            $score += 8;
        }
        if ($query !== '' && mb_stripos($summary, $query) !== false) {
            $score += 4;
        }
        if ($query !== '' && $body !== '' && mb_stripos($body, $query) !== false) {
            $score += 2;
        }

        foreach ($tokens as $token) {
            if (mb_stripos($title, $token) !== false) {
                $score += 3;
            }
            if (mb_stripos($summary, $token) !== false) {
                $score += 2;
            }
            if ($body !== '' && mb_stripos($body, $token) !== false) {
                $score += 1;
            }
        }

        return $score;
    }
}

if (!function_exists('search_snippet')) {
    function search_snippet(string $text, int $limit = 220): string
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
        return mb_safe_strimwidth($plain, 0, $limit, '...');
    }
}

$locale = current_locale();
$t = i18n_domain_locale('search', $locale);

$q = trim((string) ($_GET['q'] ?? ''));
$q = preg_replace('/\s+/u', ' ', $q) ?? '';
$q = mb_substr($q, 0, 120);
$tokens = preg_split('/[\s\p{P}]+/u', mb_strtolower($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];
$tokens = array_values(array_unique(array_filter($tokens, static fn(string $token): bool => mb_strlen($token) >= 2)));
$tokens = array_slice($tokens, 0, 6);

$sourceDefinitions = [
    'all' => ['label' => (string) ($t['source_all'] ?? 'All')],
    'news' => ['label' => (string) ($t['source_news'] ?? 'News')],
    'articles' => ['label' => (string) ($t['source_articles'] ?? 'Articles')],
    'wiki' => ['label' => (string) ($t['source_wiki'] ?? 'Wiki')],
    'library' => ['label' => (string) ($t['source_library'] ?? 'Library')],
    'classifieds' => ['label' => (string) ($t['source_classifieds'] ?? 'Classifieds')],
    'albums' => ['label' => (string) ($t['source_albums'] ?? 'Albums')],
];
$classifiedsSearchVisible = module_enabled('classifieds') && module_visible_for_current_user('classifieds');
if (!$classifiedsSearchVisible) {
    unset($sourceDefinitions['classifieds']);
}
$source = strtolower(trim((string) ($_GET['source'] ?? 'all')));
if (!isset($sourceDefinitions[$source])) {
    $source = 'all';
}

$hasQuery = $q !== '';
$isQueryLongEnough = mb_strlen($q) >= 2;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$results = [];

if ($hasQuery && $isQueryLongEnough) {
    $cacheKey = 'site_search_v3_' . current_locale() . '_' . ($classifiedsSearchVisible ? 'classifieds_visible' : 'classifieds_hidden') . '_' . md5(mb_strtolower($q));
    $results = cache_remember($cacheKey, 120, static function () use ($q, $tokens, $locale): array {
        $like = '%' . $q . '%';
        $queryLikes = array_map(static fn(string $term): string => '%' . $term . '%', $tokens);
        $collected = [];

        $shouldSearch = static fn(string $name): bool => true;

        if ($shouldSearch('news') && table_exists('news_posts')) {
            try {
                $where = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
                $params = [$like, $like, $like];
                foreach ($queryLikes as $termLike) {
                    $where .= ' OR (p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
                    array_push($params, $termLike, $termLike, $termLike);
                }
                $stmt = db()->prepare('SELECT p.slug, p.title, p.excerpt, p.content, p.published_at, p.updated_at, s.name AS section_name
                    FROM news_posts p
                    LEFT JOIN news_sections s ON s.id = p.section_id
                    WHERE p.status = "published" AND (' . $where . ')
                    ORDER BY COALESCE(p.published_at, p.updated_at) DESC
                    LIMIT 35');
                $stmt->execute($params);
                foreach ($stmt->fetchAll() ?: [] as $row) {
                    $title = trim((string) ($row['title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }
                    $summary = search_snippet((string) ($row['excerpt'] ?? ''));
                    $body = search_snippet((string) ($row['content'] ?? ''), 360);
                    if ($summary === '') {
                        $summary = $body;
                    }
                    $collected[] = [
                        'source' => 'news',
                        'title' => $title,
                        'summary' => $summary,
                        'meta' => trim((string) ($row['section_name'] ?? '')),
                        'url' => route_url('news_view', ['slug' => (string) ($row['slug'] ?? '')]),
                        'score' => search_text_score($q, $tokens, $title, $summary, $body),
                    ];
                }
            } catch (Throwable) {
            }
        }

        if ($shouldSearch('articles') && table_exists('articles')) {
            try {
                $translationJoin = '';
                $translationWhere = '';
                $params = [];
                if ($locale !== 'fr' && table_exists('article_translations')) {
                    $publicStatuses = article_translation_public_statuses();
                    $statusPlaceholders = implode(',', array_fill(0, count($publicStatuses), '?'));
                    $translationJoin = ' LEFT JOIN article_translations tr ON tr.article_id = a.id AND tr.locale = ? AND tr.status IN (' . $statusPlaceholders . ')';
                    array_push($params, $locale, ...$publicStatuses);
                    $translationWhere = ' OR tr.title LIKE ? OR tr.excerpt LIKE ? OR tr.content LIKE ?';
                }

                $where = '(a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ?' . $translationWhere . ')';
                array_push($params, $like, $like, $like);
                if ($translationWhere !== '') {
                    array_push($params, $like, $like, $like);
                }
                foreach ($queryLikes as $termLike) {
                    $where .= ' OR (a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ?' . $translationWhere . ')';
                    array_push($params, $termLike, $termLike, $termLike);
                    if ($translationWhere !== '') {
                        array_push($params, $termLike, $termLike, $termLike);
                    }
                }
                $stmt = db()->prepare('SELECT a.id, a.title, a.excerpt, a.slug, a.content, a.category, a.published_at, a.created_at, a.updated_at
                    FROM articles a' . $translationJoin . '
                    WHERE a.status = "published" AND (' . $where . ')
                    ORDER BY ' . article_publication_sort_expression_for_alias('a') . ' DESC, a.id DESC
                    LIMIT 35');
                $stmt->execute($params);
                foreach ($stmt->fetchAll() ?: [] as $row) {
                    $row = localized_article_row($row);
                    $title = trim((string) ($row['title_localized'] ?? $row['title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }
                    $summary = search_snippet((string) ($row['excerpt_localized'] ?? $row['excerpt'] ?? ''));
                    $body = search_snippet((string) ($row['content_localized'] ?? $row['content'] ?? ''), 360);
                    if ($summary === '') {
                        $summary = $body;
                    }
                    $collected[] = [
                        'source' => 'articles',
                        'title' => $title,
                        'summary' => $summary,
                        'meta' => trim((string) ($row['category'] ?? '')),
                        'url' => route_url('article', ['slug' => (string) ($row['slug'] ?? '')]),
                        'score' => search_text_score($q, $tokens, $title, $summary, $body),
                    ];
                }
            } catch (Throwable) {
            }
        }

        if ($shouldSearch('wiki') && table_exists('wiki_pages')) {
            try {
                $where = '(title LIKE ? OR content LIKE ?)';
                $params = [$like, $like];
                foreach ($queryLikes as $termLike) {
                    $where .= ' OR (title LIKE ? OR content LIKE ?)';
                    array_push($params, $termLike, $termLike);
                }
                $stmt = db()->prepare('SELECT title, content, slug FROM wiki_pages WHERE status = "published" AND (' . $where . ') ORDER BY updated_at DESC LIMIT 35');
                $stmt->execute($params);
                foreach ($stmt->fetchAll() ?: [] as $row) {
                    $title = trim((string) ($row['title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }
                    $summary = search_snippet((string) ($row['content'] ?? ''));
                    $collected[] = [
                        'source' => 'wiki',
                        'title' => $title,
                        'summary' => $summary,
                        'meta' => '',
                        'url' => route_url('wiki_view', ['slug' => (string) ($row['slug'] ?? '')]),
                        'score' => search_text_score($q, $tokens, $title, $summary),
                    ];
                }
            } catch (Throwable) {
            }
        }

        if ($shouldSearch('library') && table_exists('member_library_documents')) {
            try {
                $where = '(title LIKE ? OR description LIKE ? OR extracted_text LIKE ? OR tags LIKE ?)';
                $params = [$like, $like, $like, $like];
                foreach ($queryLikes as $termLike) {
                    $where .= ' OR (title LIKE ? OR description LIKE ? OR extracted_text LIKE ? OR tags LIKE ?)';
                    array_push($params, $termLike, $termLike, $termLike, $termLike);
                }
                $stmt = db()->prepare('SELECT title, description, extracted_text, category, tags FROM member_library_documents WHERE ' . $where . ' ORDER BY uploaded_at DESC LIMIT 35');
                $stmt->execute($params);
                foreach ($stmt->fetchAll() ?: [] as $row) {
                    $title = trim((string) ($row['title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }
                    $summary = search_snippet((string) ($row['description'] ?? ''));
                    $body = search_snippet((string) ($row['extracted_text'] ?? ''), 360);
                    if ($summary === '') {
                        $summary = $body;
                    }
                    $category = trim((string) ($row['category'] ?? ''));
                    $urlQuery = ['q' => $title];
                    if ($category !== '') {
                        $urlQuery['category'] = $category;
                    }
                    $collected[] = [
                        'source' => 'library',
                        'title' => $title,
                        'summary' => $summary,
                        'meta' => trim($category . ' ' . (string) ($row['tags'] ?? '')),
                        'url' => route_url('members_library', $urlQuery),
                        'score' => search_text_score($q, $tokens, $title, $summary, $body),
                    ];
                }
            } catch (Throwable) {
            }
        }

        if ($shouldSearch('classifieds') && module_enabled('classifieds') && module_visible_for_current_user('classifieds') && table_exists('classified_ads')) {
            try {
                $where = '(title LIKE ? OR description LIKE ? OR location LIKE ?)';
                $params = [$like, $like, $like];
                foreach ($queryLikes as $termLike) {
                    $where .= ' OR (title LIKE ? OR description LIKE ? OR location LIKE ?)';
                    array_push($params, $termLike, $termLike, $termLike);
                }
                $stmt = db()->prepare('SELECT title, description, location, price_cents FROM classified_ads WHERE ' . classifieds_active_where_sql() . ' AND (' . $where . ') ORDER BY updated_at DESC LIMIT 35');
                $stmt->execute($params);
                foreach ($stmt->fetchAll() ?: [] as $row) {
                    $title = trim((string) ($row['title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }
                    $summaryParts = array_filter([
                        search_snippet((string) ($row['description'] ?? '')),
                        trim((string) ($row['location'] ?? '')),
                        ((int) ($row['price_cents'] ?? 0) > 0 ? format_price_eur((int) $row['price_cents']) : ''),
                    ], static fn(string $value): bool => $value !== '');
                    $summary = implode(' · ', $summaryParts);
                    $collected[] = [
                        'source' => 'classifieds',
                        'title' => $title,
                        'summary' => $summary,
                        'meta' => trim((string) ($row['location'] ?? '')),
                        'url' => route_url('classifieds', ['q' => $title]),
                        'score' => search_text_score($q, $tokens, $title, $summary),
                    ];
                }
            } catch (Throwable) {
            }
        }

        if ($shouldSearch('albums') && table_exists('albums')) {
            try {
                $where = '(title LIKE ? OR description LIKE ?)';
                $params = [$like, $like];
                foreach ($queryLikes as $termLike) {
                    $where .= ' OR (title LIKE ? OR description LIKE ?)';
                    array_push($params, $termLike, $termLike);
                }
                $stmt = db()->prepare('SELECT id, title, description FROM albums WHERE is_public = 1 AND (' . $where . ') ORDER BY id DESC LIMIT 35');
                $stmt->execute($params);
                foreach ($stmt->fetchAll() ?: [] as $row) {
                    $title = trim((string) ($row['title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }
                    $summary = search_snippet((string) ($row['description'] ?? ''));
                    $collected[] = [
                        'source' => 'albums',
                        'title' => $title,
                        'summary' => $summary,
                        'meta' => '',
                        'url' => route_url('album', ['id' => (int) ($row['id'] ?? 0)]),
                        'score' => search_text_score($q, $tokens, $title, $summary),
                    ];
                }
            } catch (Throwable) {
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
usort($results, static function (array $a, array $b): int {
    $scoreCompare = ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
    return $scoreCompare !== 0 ? $scoreCompare : strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
});

$sourceCounts = array_fill_keys(array_keys($sourceDefinitions), 0);
foreach ($results as $result) {
    $resultSource = (string) ($result['source'] ?? '');
    if (isset($sourceCounts[$resultSource])) {
        $sourceCounts[$resultSource]++;
    }
    $sourceCounts['all']++;
}
if ($source !== 'all') {
    $results = array_values(array_filter($results, static fn(array $result): bool => (string) ($result['source'] ?? '') === $source));
}

$totalResults = count($results);
$pagination = pagination_state($totalResults, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
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
<section class="site-search-page">
    <header class="site-search-hero">
        <div class="site-search-copy">
            <p class="directory-eyebrow"><?= e((string) ($t['eyebrow'] ?? $t['title'])) ?></p>
            <h1><?= e((string) $t['title']) ?></h1>
            <p class="directory-lead"><?= e((string) ($t['lead'] ?? $t['meta_desc'])) ?></p>
        </div>
        <form method="get" class="site-search-box">
            <input type="hidden" name="route" value="search">
            <label>
                <span><?= e((string) ($t['query_label'] ?? $t['title'])) ?></span>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e((string) $t['placeholder']) ?>" required>
            </label>
            <label>
                <span><?= e((string) ($t['source_label'] ?? 'Source')) ?></span>
                <select name="source">
                    <?php foreach ($sourceDefinitions as $sourceKey => $definition): ?>
                        <option value="<?= e($sourceKey) ?>" <?= $source === $sourceKey ? 'selected' : '' ?>><?= e((string) $definition['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button" type="submit"><?= e((string) $t['submit']) ?></button>
        </form>
    </header>

    <section class="site-search-results">
        <div class="site-search-summary">
            <div>
                <h2><?= e((string) ($t['results_title'] ?? $t['title'])) ?></h2>
                <?php if ($hasQuery && $isQueryLongEnough): ?>
                    <p class="help"><?= e(sprintf((string) ($t['results_for'] ?? '%d results for "%s"'), $totalResults, $q)) ?></p>
                <?php else: ?>
                    <p class="help"><?= e((string) ($t['start_hint'] ?? $t['placeholder'])) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($hasQuery && $isQueryLongEnough): ?>
                <span class="badge muted"><?= (int) $totalResults ?> <?= e((string) $t['count']) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($hasQuery && $isQueryLongEnough): ?>
            <nav class="site-search-source-tabs" aria-label="<?= e((string) ($t['source_label'] ?? 'Source')) ?>">
                <?php foreach ($sourceDefinitions as $sourceKey => $definition): ?>
                    <a class="site-search-source-tab<?= $source === $sourceKey ? ' is-active' : '' ?>" href="<?= e(route_url_clean('search', ['q' => $q, 'source' => $sourceKey])) ?>">
                        <span><?= e((string) $definition['label']) ?></span>
                        <strong><?= (int) ($sourceCounts[$sourceKey] ?? 0) ?></strong>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if ($hasQuery && !$isQueryLongEnough): ?>
            <div class="site-search-empty">
                <h3><?= e((string) $t['query_too_short']) ?></h3>
            </div>
        <?php elseif ($hasQuery && $isQueryLongEnough && $totalResults === 0): ?>
            <div class="site-search-empty">
                <h3><?= e((string) $t['empty']) ?></h3>
                <p><?= e((string) ($t['empty_help'] ?? '')) ?></p>
            </div>
        <?php elseif (!$hasQuery): ?>
            <div class="site-search-empty">
                <h3><?= e((string) ($t['start_title'] ?? $t['title'])) ?></h3>
                <p><?= e((string) ($t['start_hint'] ?? $t['placeholder'])) ?></p>
            </div>
        <?php else: ?>
            <div class="site-search-list">
                <?php foreach ($pagedResults as $item): ?>
                    <?php $itemSource = (string) ($item['source'] ?? 'all'); ?>
                    <article class="site-search-result-card">
                        <div class="site-search-result-main">
                            <div class="site-search-result-meta">
                                <span><?= e((string) ($sourceDefinitions[$itemSource]['label'] ?? $itemSource)) ?></span>
                                <?php if (trim((string) ($item['meta'] ?? '')) !== ''): ?>
                                    <span><?= e((string) $item['meta']) ?></span>
                                <?php endif; ?>
                            </div>
                            <h3><a href="<?= e((string) $item['url']) ?>"><?= e((string) $item['title']) ?></a></h3>
                            <?php if (trim((string) ($item['summary'] ?? '')) !== ''): ?>
                                <p><?= e((string) $item['summary']) ?></p>
                            <?php endif; ?>
                        </div>
                        <a class="button secondary small" href="<?= e((string) $item['url']) ?>"><?= e((string) ($t['open_result'] ?? 'Open')) ?></a>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($totalResults > $perPage): ?>
                <nav class="site-search-pagination" aria-label="<?= e((string) ($t['pagination'] ?? 'Pagination')) ?>">
                    <?php if ($hasPrev): ?>
                        <a class="button secondary" href="<?= e(route_url_clean('search', ['q' => $q, 'source' => $source, 'page' => ($page - 1)])) ?>"><?= e((string) $t['previous']) ?></a>
                    <?php endif; ?>
                    <span class="pill"><?= e((string) ($t['page'] ?? 'Page')) ?> <?= (int) $page ?> / <?= (int) $totalPages ?></span>
                    <?php if ($hasNext): ?>
                        <a class="button secondary" href="<?= e(route_url_clean('search', ['q' => $q, 'source' => $source, 'page' => ($page + 1)])) ?>"><?= e((string) $t['next']) ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</section>
<?php echo render_layout((string) ob_get_clean(), (string) $t['title']);
