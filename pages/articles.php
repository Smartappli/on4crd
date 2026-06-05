<?php
declare(strict_types=1);

$GLOBALS['articles_i18n'] = [];

function article_plain_text(string $html): string
{
    return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

function article_reading_minutes(string $html): int
{
    $wordCount = str_word_count(article_plain_text($html));
    return max(1, (int) ceil($wordCount / 220));
}

function article_card_excerpt(array $row): string
{
    $excerpt = trim((string) ($row['excerpt_localized'] ?? $row['excerpt'] ?? ''));
    if ($excerpt !== '') {
        return $excerpt;
    }

    $plain = article_plain_text((string) ($row['content_localized'] ?? $row['content'] ?? ''));
    return mb_strlen($plain) > 180 ? mb_substr($plain, 0, 177) . '...' : $plain;
}

function article_category_logo(string $label): string
{
    $safeLabel = trim($label) !== '' ? $label : ((string) ($GLOBALS['articles_i18n']['default_category'] ?? 'Category'));
    $initial = strtoupper((string) mb_substr($safeLabel, 0, 1, 'UTF-8'));
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96" role="img" aria-label="' . htmlspecialchars($safeLabel, ENT_QUOTES, 'UTF-8') . '"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#1d4ed8"/><stop offset="100%" stop-color="#0f172a"/></linearGradient></defs><rect width="96" height="96" rx="18" fill="url(#g)"/><text x="48" y="56" text-anchor="middle" font-size="38" font-family="Arial, sans-serif" fill="#fff">' . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</text></svg>';

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}


$locale = current_locale();
$t = i18n_domain_locale('articles', $locale);
articles_sync_scheduled_publications();
$GLOBALS['articles_i18n'] = $t;
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'toggle_favorite_article') {
    $user = require_login();
    verify_csrf();
    $articleId = (int) ($_POST['article_id'] ?? 0);
    if ($articleId > 0) {
        $favStmt = db()->prepare('SELECT id, slug, title FROM articles WHERE id = ? AND status = "published" LIMIT 1');
        $favStmt->execute([$articleId]);
        $favRow = $favStmt->fetch() ?: null;
        if ($favRow !== null) {
            $favTitle = trim((string) ($favRow['title'] ?? (string) ($t['default_article_title'] ?? 'Article')));
            $favUrl = route_url('article', ['slug' => (string) ($favRow['slug'] ?? '')]);
            $saved = favorite_toggle((int) $user['id'], 'article', (int) $favRow['id'], $favTitle, $favUrl);
            notify_member((int) $user['id'], 'favorite', $saved ? (string) $t['favorite_added'] : (string) $t['favorite_removed'], $favTitle, $favUrl);
            set_flash('success', $saved ? (string) $t['favorite_added_msg'] : (string) $t['favorite_removed_msg']);
        }
    }
    redirect_url(route_url_clean('articles', ['theme' => (string) ($_GET['theme'] ?? ''), 'q' => (string) ($_GET['q'] ?? ''), 'page' => max(1, (int) ($_GET['page'] ?? 1))]));
}

$themeMeta = [
    'antennes' => ['label' => (string) $t['theme_antennes'], 'image' => null],
    'trafic' => ['label' => (string) $t['theme_trafic'], 'image' => null],
    'numerique' => ['label' => (string) $t['theme_numerique'], 'image' => null],
    'materiel' => ['label' => (string) $t['theme_materiel'], 'image' => null],
    'formation' => ['label' => (string) $t['theme_formation'], 'image' => null],
    'autres' => ['label' => (string) $t['theme_autres'], 'image' => null],
];

$themeFilter = slugify(trim((string) ($_GET['theme'] ?? '')));
if ($themeFilter === 'n-a') {
    $themeFilter = '';
}
$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$themeCounts = cache_remember('articles_theme_counts_v1', 180, static function (): array {
    $counts = [];
    $rows = db()->query('SELECT category, COUNT(*) AS total FROM articles WHERE status = "published" GROUP BY category')->fetchAll() ?: [];
    foreach ($rows as $row) {
        $theme = slugify((string) ($row['category'] ?? 'autres'));
        if ($theme === '') {
            $theme = 'autres';
        }
        $counts[$theme] = (int) ($row['total'] ?? 0);
    }
    return $counts;
});
foreach (array_keys($themeCounts) as $themeCode) {
    if (!isset($themeMeta[$themeCode])) {
        $themeMeta[$themeCode] = ['label' => ucwords(str_replace('-', ' ', $themeCode)), 'image' => null];
    }
}
if ($themeFilter !== '' && !isset($themeMeta[$themeFilter])) {
    $themeFilter = '';
}

$whereParts = ['status = "published"'];
$whereParams = [];
if ($themeFilter !== '') {
    $whereParts[] = 'category = ?';
    $whereParams[] = $themeFilter;
}
if ($search !== '') {
    $whereParts[] = '(title LIKE ? OR excerpt LIKE ? OR content LIKE ?)';
    $like = '%' . $search . '%';
    $whereParams[] = $like;
    $whereParams[] = $like;
    $whereParams[] = $like;
}
$whereSql = 'WHERE ' . implode(' AND ', $whereParts);
$countStmt = db()->prepare('SELECT COUNT(*) FROM articles ' . $whereSql);
$countStmt->execute($whereParams);
$totalArticles = (int) $countStmt->fetchColumn();
$pagination = pagination_state($totalArticles, $page, $perPage);
$page = $pagination['page'];
$maxPage = $pagination['total_pages'];
$offset = $pagination['offset'];
$articlePublicationSort = article_publication_sort_expression();
$dataStmt = db()->prepare('SELECT id, slug, title, excerpt, content, category, published_at, created_at, updated_at FROM articles ' . $whereSql . ' ORDER BY ' . $articlePublicationSort . ' DESC, id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
$dataStmt->execute($whereParams);
$pagedRows = $dataStmt->fetchAll() ?: [];
$groupedArticles = [];
foreach ($pagedRows as $row) {
    $theme = slugify((string) ($row['category'] ?? 'autres'));
    if ($theme === '') {
        $theme = 'autres';
    }
    $groupedArticles[$theme][] = $row;
}

set_page_meta([
    'title' => (string) $t['page_title'],
    'description' => (string) $t['page_description'],
    'schema_type' => 'CollectionPage',
]);
$contactEmail = site_contact_email();
$categoryProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode((string) $t['propose_category_subject'])
    . '&body=' . rawurlencode((string) $t['propose_category_body']);

ob_start();
?>
<div class="stack">
    <section class="page-hero">
        <div>
            <p class="eyebrow"><?= e((string) $t['layout_title']) ?></p>
            <h1 class="articles-hero-title"><?= e((string) $t['page_title']) ?></h1>
            <p class="help"><?= e((string) $t['page_description']) ?></p>
        </div>
        <div class="articles-hero-side">
            <div class="articles-hero-stats">
                <article>
                    <span><?= e((string) $t['article_count']) ?></span>
                    <strong><?= (int) $totalArticles ?></strong>
                </article>
                <article>
                    <span><?= e((string) $t['theme_default']) ?></span>
                    <strong><?= (int) count($themeMeta) ?></strong>
                </article>
            </div>
            <div class="articles-hero-actions">
                <button class="button secondary" type="button" data-articles-category-open data-articles-category-fallback="<?= e($categoryProposalUrl) ?>" aria-haspopup="dialog" aria-controls="articles-category-dialog"><?= e((string) $t['propose_category']) ?></button>
                <a class="button" href="<?= e(route_url('article_propose')) ?>"><?= e((string) $t['propose_article']) ?></a>
            </div>
        </div>
    </section>

    <dialog class="articles-category-dialog" id="articles-category-dialog" aria-labelledby="articles-category-title">
        <div class="articles-category-dialog-card">
            <div class="articles-category-dialog-header">
                <div>
                    <p class="articles-category-dialog-eyebrow"><?= e((string) $t['theme_default']) ?></p>
                    <h2 id="articles-category-title"><?= e((string) $t['propose_category']) ?></h2>
                    <p class="help"><?= e((string) $t['propose_category_intro']) ?></p>
                </div>
                <button class="articles-category-dialog-close" type="button" data-articles-category-close aria-label="<?= e((string) $t['propose_category_close']) ?>">&times;</button>
            </div>
            <form class="articles-category-form" method="dialog" data-articles-category-form data-articles-category-recipient="<?= e($contactEmail) ?>" data-articles-category-subject="<?= e((string) $t['propose_category_subject']) ?>" data-articles-category-intro="<?= e((string) $t['propose_category_body_intro']) ?>">
                <label>
                    <span><?= e((string) $t['propose_category_name_label']) ?></span>
                    <input type="text" name="proposal_category" maxlength="160" required>
                </label>
                <label>
                    <span><?= e((string) $t['propose_category_reason_label']) ?></span>
                    <textarea name="proposal_reason" rows="5" maxlength="1600"></textarea>
                </label>
                <label>
                    <span><?= e((string) $t['propose_category_contact_label']) ?></span>
                    <input type="text" name="proposal_contact" maxlength="220" required>
                </label>
                <div class="articles-category-dialog-actions">
                    <button class="button" type="submit"><?= e((string) $t['propose_category_submit']) ?></button>
                    <button class="button secondary" type="button" data-articles-category-close><?= e((string) $t['propose_category_cancel']) ?></button>
                </div>
            </form>
        </div>
    </dialog>

    <section class="card articles-search-panel">
        <form method="get" class="inline-form articles-search-form">
            <input type="hidden" name="route" value="articles">
            <?php if ($themeFilter !== ''): ?>
                <input type="hidden" name="theme" value="<?= e($themeFilter) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== ''): ?>
                <a class="button secondary" href="<?= e(route_url_clean('articles', ['theme' => $themeFilter])) ?>"><?= e((string) $t['reset_search']) ?></a>
            <?php endif; ?>
        </form>
        <?php if ($search !== '' || $themeFilter !== ''): ?>
            <p class="help"><?= e((string) $t['results']) ?> : <?= $totalArticles ?></p>
        <?php endif; ?>
    </section>

    <section class="articles-layout">
        <aside class="articles-index card">
            <p class="articles-index-title"><?= e((string) $t['theme_default']) ?></p>
            <nav class="articles-category-list" aria-label="<?= e((string) $t['theme_default']) ?>">
            <?php foreach ($themeMeta as $themeCode => $theme): ?>
                <a class="articles-category-item<?= $themeFilter === $themeCode ? ' is-active' : '' ?>" href="<?= e(route_url_clean('articles', ['theme' => $themeCode, 'q' => $search])) ?>"<?= $themeFilter === $themeCode ? ' aria-current="page"' : '' ?>>
                    <span><?= e((string) $theme['label']) ?></span>
                    <strong><?= (int) ($themeCounts[$themeCode] ?? 0) ?></strong>
                </a>
            <?php endforeach; ?>
            </nav>
        </aside>

        <div class="articles-content">
    <?php if ($themeFilter !== ''): ?>
        <div class="card">
            <p><a class="pill" href="<?= e(route_url_clean('articles', ['q' => $search])) ?>"><?= e((string) $t['reset_filter']) ?></a></p>
        </div>
    <?php endif; ?>

    <?php if ($groupedArticles === []): ?>
        <div class="card">
            <p><?= e((string) $t['no_article_for_theme']) ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($groupedArticles as $themeCode => $themeRows): ?>
            <section class="card">
                <h2><?= e((string) ($themeMeta[$themeCode]['label'] ?? (string) $t['theme_default'])) ?></h2>
                <div class="news-grid">
                    <?php foreach ($themeRows as $row): ?>
                        <?php $row = localized_article_row($row); ?>
                        <?php $articleDate = article_publication_datetime($row); ?>
                        <?php $isFavorite = $user !== null ? favorite_is_saved((int) $user['id'], 'article', (int) ($row['id'] ?? 0)) : false; ?>
                        <article class="news-card feature-card">
                            <span class="badge muted"><?= e((string) ($themeMeta[$themeCode]['label'] ?? (string) $t['theme_default'])) ?></span>
                            <h3><a href="<?= e(route_url('article', ['slug' => (string) $row['slug']])) ?>"><?= e((string) $row['title_localized']) ?></a></h3>
                            <p class="help"><?= $articleDate !== null ? e(date('d/m/Y', strtotime($articleDate))) . ' · ' : '' ?><?= article_reading_minutes((string) ($row['content_localized'] ?? $row['content'] ?? '')) ?> <?= e((string) $t['reading_minutes']) ?></p>
                            <p><?= e(article_card_excerpt($row)) ?></p>
                            <p class="actions">
                                <a class="button secondary" href="<?= e(route_url('article', ['slug' => (string) $row['slug']])) ?>"><?= e((string) $t['read_article']) ?></a>
                                <?php if ($user !== null): ?>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="toggle_favorite_article">
                                        <input type="hidden" name="article_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                                        <button class="button secondary" type="submit"><?= $isFavorite ? '&#9733; ' . e((string) $t['favorite_label']) : '&#9734; ' . e((string) $t['favorite_label']) ?></button>
                                    </form>
                                <?php endif; ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($maxPage > 1): ?>
        <div class="card actions">
            <?php if ($page > 1): ?>
                <a class="button secondary" href="<?= e(route_url_clean('articles', ['theme' => $themeFilter, 'q' => $search, 'page' => $page - 1])) ?>"><?= e((string) $t['previous']) ?></a>
            <?php endif; ?>
            <span class="pill"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $maxPage ?></span>
            <?php if ($page < $maxPage): ?>
                <a class="button secondary" href="<?= e(route_url_clean('articles', ['theme' => $themeFilter, 'q' => $search, 'page' => $page + 1])) ?>"><?= e((string) $t['next']) ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
        </div>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout_title']);
