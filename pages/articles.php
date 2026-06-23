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
article_ensure_taxonomy_schema($t);
$GLOBALS['articles_i18n'] = $t;
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'toggle_favorite_article') {
            $user = require_login(route_url('articles'));
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
            redirect_url(route_url_clean('articles', [
                'theme' => (string) ($_GET['theme'] ?? ''),
                'subcategory' => (string) ($_GET['subcategory'] ?? ''),
                'favorites' => (string) ($_POST['return_favorites'] ?? $_GET['favorites'] ?? '') === '1' ? '1' : '',
                'q' => (string) ($_GET['q'] ?? ''),
                'page' => max(1, (int) ($_GET['page'] ?? 1)),
            ]));
        }

        if ($action === 'propose_category') {
            $user = require_login(route_url('articles'));
            $proposalTitle = (string) ($_POST['proposal_category'] ?? '');
            $proposalContact = (string) ($_POST['proposal_contact'] ?? '');
            $proposalSummary = content_proposal_details_text([
                (string) ($t['propose_category_reason_label'] ?? 'Reason') => (string) ($_POST['proposal_reason'] ?? ''),
            ]);
            $proposalId = content_proposal_create((int) $user['id'], 'articles', 'category', $proposalTitle, $proposalSummary, $proposalContact);
            content_proposal_notify_site((string) ($t['propose_category_subject'] ?? 'Category proposal'), [
                'area' => 'articles',
                'proposal_type' => 'category',
                'title' => content_proposal_clean_single_line($proposalTitle, 190),
                'summary' => $proposalSummary,
                'contact' => content_proposal_clean_single_line($proposalContact, 220),
                'source_ref' => 'content_proposals#' . $proposalId,
            ]);
            set_flash('success', (string) $t['proposal_recorded']);
            redirect('my_requests');
        }

        if ($action === 'propose_tag') {
            $user = require_login(route_url('articles'));
            $proposalTitle = (string) ($_POST['proposal_tag'] ?? '');
            $proposalContact = (string) ($_POST['proposal_contact'] ?? '');
            $proposalSummary = content_proposal_details_text([
                (string) ($t['propose_tag_reason_label'] ?? 'Reason') => (string) ($_POST['proposal_reason'] ?? ''),
            ]);
            $proposalId = content_proposal_create((int) $user['id'], 'articles', 'tag', $proposalTitle, $proposalSummary, $proposalContact);
            content_proposal_notify_site((string) ($t['propose_tag_subject'] ?? 'Keyword proposal'), [
                'area' => 'articles',
                'proposal_type' => 'tag',
                'title' => content_proposal_clean_single_line($proposalTitle, 190),
                'summary' => $proposalSummary,
                'contact' => content_proposal_clean_single_line($proposalContact, 220),
                'source_ref' => 'content_proposals#' . $proposalId,
            ]);
            set_flash('success', (string) $t['proposal_recorded']);
            redirect('my_requests');
        }

        throw new RuntimeException((string) $t['invalid']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect_url(route_url('articles'));
    }
}

$articleCategories = article_categories($t);
$articleSubcategoriesByCategory = article_subcategories_by_category();
$themeFilterRaw = trim((string) ($_GET['theme'] ?? ''));
$themeFilter = $themeFilterRaw !== '' ? article_category_code($themeFilterRaw) : '';
if ($themeFilter === 'n-a') {
    $themeFilter = '';
}
$subcategoryFilter = article_subcategory_code(trim((string) ($_GET['subcategory'] ?? '')));
$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$articlesTableAvailable = table_exists('articles');
$themeCounts = [];
$subcategoryCounts = [];
if ($articlesTableAvailable) {
    try {
        $themeCounts = cache_remember('articles_theme_counts_v2', 180, static function (): array {
            $counts = [];
            $rows = db()->query('SELECT category, COUNT(*) AS total FROM articles WHERE status = "published" GROUP BY category')->fetchAll() ?: [];
            foreach ($rows as $row) {
                $theme = article_category_code((string) ($row['category'] ?? 'autres'));
                if ($theme === '') {
                    $theme = 'autres';
                }
                $counts[$theme] = (int) ($row['total'] ?? 0);
            }
            return $counts;
        });
        $themeCounts = is_array($themeCounts) ? $themeCounts : [];
        $subcategoryRows = db()->query('SELECT category, subcategory, COUNT(*) AS total FROM articles WHERE status = "published" AND subcategory IS NOT NULL AND subcategory <> "" GROUP BY category, subcategory ORDER BY category ASC, subcategory ASC')->fetchAll() ?: [];
        foreach ($subcategoryRows as $subcategoryRow) {
            $themeCode = article_category_code((string) ($subcategoryRow['category'] ?? 'autres'));
            $subCode = article_subcategory_code((string) ($subcategoryRow['subcategory'] ?? ''));
            if ($themeCode === '' || $subCode === '') {
                continue;
            }
            $subcategoryCounts[$themeCode . ':' . $subCode] = (int) ($subcategoryRow['total'] ?? 0);
            $known = false;
            foreach ($articleSubcategoriesByCategory[$themeCode] ?? [] as $subcategoryOption) {
                if (article_subcategory_code((string) ($subcategoryOption['code'] ?? '')) === $subCode) {
                    $known = true;
                    break;
                }
            }
            if (!$known) {
                $articleSubcategoriesByCategory[$themeCode][] = [
                    'category_code' => $themeCode,
                    'code' => $subCode,
                    'label' => article_category_label_from_code($subCode),
                ];
            }
        }
    } catch (Throwable) {
        $themeCounts = [];
        $subcategoryCounts = [];
        $articlesTableAvailable = false;
    }
}
$visibleArticleCategories = article_visible_categories($articleCategories, $themeCounts);
$visibleArticleSubcategoriesByCategory = article_visible_subcategories_by_category($articleSubcategoriesByCategory, $subcategoryCounts);
if ($themeFilter !== '' && !isset($visibleArticleCategories[$themeFilter])) {
    $themeFilter = '';
}
if ($subcategoryFilter !== '') {
    $candidateTheme = $themeFilter;
    if ($candidateTheme === '') {
        foreach ($visibleArticleSubcategoriesByCategory as $parentCode => $subcategories) {
            foreach ($subcategories as $subcategoryInfo) {
                if (article_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $subcategoryFilter) {
                    $candidateTheme = (string) $parentCode;
                    break 2;
                }
            }
        }
    }
    $hasVisibleSubcategory = false;
    foreach ($visibleArticleSubcategoriesByCategory[$candidateTheme] ?? [] as $subcategoryInfo) {
        if (article_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $subcategoryFilter) {
            $hasVisibleSubcategory = true;
            break;
        }
    }
    if ($hasVisibleSubcategory) {
        $themeFilter = $candidateTheme;
    } else {
        $subcategoryFilter = '';
    }
}
$favoriteArticleIds = $user !== null ? article_favorite_article_ids((int) ($user['id'] ?? 0)) : [];
$favoriteArticleCount = count($favoriteArticleIds);
$favoritesOnly = (string) ($_GET['favorites'] ?? '') === '1' && $favoriteArticleCount > 0;
$favoritesLabel = article_favorites_label($t, $locale);

$whereParts = ['status = "published"'];
$whereParams = [];
if ($themeFilter !== '') {
    $whereParts[] = 'category = ?';
    $whereParams[] = $themeFilter;
}
if ($subcategoryFilter !== '') {
    $whereParts[] = 'subcategory = ?';
    $whereParams[] = $subcategoryFilter;
}
if ($search !== '') {
    $whereParts[] = '(title LIKE ? OR excerpt LIKE ? OR content LIKE ? OR category LIKE ? OR subcategory LIKE ?)';
    $like = '%' . $search . '%';
    $whereParams[] = $like;
    $whereParams[] = $like;
    $whereParams[] = $like;
    $whereParams[] = $like;
    $whereParams[] = $like;
}
if ($favoritesOnly) {
    $whereParts[] = 'id IN (' . implode(',', array_fill(0, $favoriteArticleCount, '?')) . ')';
    array_push($whereParams, ...$favoriteArticleIds);
}
$whereSql = 'WHERE ' . implode(' AND ', $whereParts);
$totalArticles = 0;
$pagedRows = [];
$latestArticleDate = '';
if ($articlesTableAvailable) {
    try {
        $countStmt = db()->prepare('SELECT COUNT(*) FROM articles ' . $whereSql);
        $countStmt->execute($whereParams);
        $totalArticles = (int) $countStmt->fetchColumn();
    } catch (Throwable) {
        $articlesTableAvailable = false;
    }
}
$pagination = pagination_state($totalArticles, $page, $perPage);
$page = $pagination['page'];
$maxPage = $pagination['total_pages'];
$offset = $pagination['offset'];
$articlePublicationSort = article_publication_sort_expression();
if ($articlesTableAvailable) {
    try {
        $dataStmt = db()->prepare('SELECT id, slug, title, excerpt, content, category, subcategory, published_at, created_at, updated_at FROM articles ' . $whereSql . ' ORDER BY ' . $articlePublicationSort . ' DESC, id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
        $dataStmt->execute($whereParams);
        $pagedRows = $dataStmt->fetchAll() ?: [];

        $latestArticleStmt = db()->query('SELECT published_at, created_at, updated_at FROM articles WHERE status = "published" ORDER BY ' . $articlePublicationSort . ' DESC, id DESC LIMIT 1');
        $latestArticleRow = $latestArticleStmt ? ($latestArticleStmt->fetch() ?: null) : null;
        if (is_array($latestArticleRow)) {
            $latestArticleDate = (string) (article_publication_datetime($latestArticleRow) ?? '');
        }
    } catch (Throwable) {
        $pagedRows = [];
        $latestArticleDate = '';
    }
}
$latestArticleLabel = module_hero_latest_stat_date_label($latestArticleDate, $locale);
$groupedArticles = [];
foreach ($pagedRows as $row) {
    $theme = article_category_code((string) ($row['category'] ?? 'autres'));
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
$proposalContactDefault = '';
if ($user !== null) {
    $proposalContactDefault = trim((string) ($user['email'] ?? ''));
    if ($proposalContactDefault === '') {
        $proposalContactDefault = trim((string) ($user['callsign'] ?? ''));
    }
}
$canAdminArticles = $user !== null && has_permission('admin.access');
$articlesAdminUrl = route_url_clean('admin_articles', ['status' => 'pending']) . '#pending-proposals';
$articlesProposeLabel = (string) ($t['propose_menu'] ?? 'Proposer');
$articlesProposeCategoryLabel = (string) ($t['propose_category_item'] ?? $t['theme_default'] ?? 'Une thématique');
$articlesProposeTagLabel = (string) ($t['propose_tag_item'] ?? 'Un mot clé');
$articlesProposeArticleLabel = (string) ($t['propose_article_item'] ?? $t['propose_article'] ?? 'Un article');
$articlesAdminLabel = (string) ($t['administer'] ?? ($locale === 'fr' ? 'Administrer' : 'Administer'));

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
                    <strong><?= (int) count($visibleArticleCategories) ?></strong>
                </article>
                <article>
                    <span><?= e(module_hero_latest_stat_text('latest', $locale)) ?></span>
                    <strong><?= e($latestArticleLabel) ?></strong>
                </article>
            </div>
            <div class="articles-hero-actions">
                <details class="articles-propose-menu">
                    <summary class="button" aria-haspopup="menu"><?= e($articlesProposeLabel) ?></summary>
                    <div class="articles-propose-menu-panel" role="menu">
                        <a class="articles-propose-menu-item" role="menuitem" href="<?= e($categoryProposalUrl) ?>" data-articles-category-open data-articles-dialog-open="articles-category-dialog" aria-haspopup="dialog" aria-controls="articles-category-dialog"><?= e($articlesProposeCategoryLabel) ?></a>
                        <a class="articles-propose-menu-item" role="menuitem" href="#articles-tag-dialog" data-articles-dialog-open="articles-tag-dialog" aria-haspopup="dialog" aria-controls="articles-tag-dialog"><?= e($articlesProposeTagLabel) ?></a>
                        <a class="articles-propose-menu-item" role="menuitem" href="<?= e(route_url('article_propose')) ?>"><?= e($articlesProposeArticleLabel) ?></a>
                    </div>
                </details>
                <?php if ($canAdminArticles): ?>
                    <a class="button secondary" href="<?= e($articlesAdminUrl) ?>"><?= e($articlesAdminLabel) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <dialog class="articles-category-dialog" id="articles-category-dialog" aria-labelledby="articles-category-title">
        <div class="articles-category-dialog-card">
            <div class="articles-category-dialog-header module-dialog-header">
                <div>
                    <p class="articles-category-dialog-eyebrow module-dialog-eyebrow"><?= e((string) $t['theme_default']) ?></p>
                    <h2 id="articles-category-title"><?= e((string) $t['propose_category']) ?></h2>
                    <p class="help"><?= e((string) $t['propose_category_intro']) ?></p>
                </div>
                <button class="articles-category-dialog-close module-dialog-close" type="button" data-articles-category-close aria-label="<?= e((string) $t['propose_category_close']) ?>">&times;</button>
            </div>
            <form class="articles-category-form module-dialog-form" method="<?= $user !== null ? 'post' : 'dialog' ?>" data-articles-category-form data-articles-category-recipient="<?= e($contactEmail) ?>" data-articles-category-subject="<?= e((string) $t['propose_category_subject']) ?>" data-articles-category-intro="<?= e((string) $t['propose_category_body_intro']) ?>">
                <?php if ($user !== null): ?>
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="propose_category">
                <?php endif; ?>
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
                    <input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required>
                </label>
                <div class="articles-category-dialog-actions module-dialog-actions">
                    <button class="button" type="submit"><?= e((string) $t['propose_category_submit']) ?></button>
                    <button class="button secondary" type="button" data-articles-category-close><?= e((string) $t['propose_category_cancel']) ?></button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog class="articles-category-dialog articles-proposal-dialog" id="articles-tag-dialog" aria-labelledby="articles-tag-title">
        <div class="articles-category-dialog-card">
            <div class="articles-category-dialog-header module-dialog-header">
                <div>
                    <p class="articles-category-dialog-eyebrow module-dialog-eyebrow"><?= e((string) ($t['propose_tag_eyebrow'] ?? $t['theme_default'])) ?></p>
                    <h2 id="articles-tag-title"><?= e((string) ($t['propose_tag'] ?? 'Proposer un mot clé')) ?></h2>
                    <p class="help"><?= e((string) ($t['propose_tag_intro'] ?? 'Indiquez le mot clé à ajouter aux articles.')) ?></p>
                </div>
                <button class="articles-category-dialog-close module-dialog-close" type="button" data-articles-dialog-close aria-label="<?= e((string) ($t['propose_tag_close'] ?? $t['propose_category_close'])) ?>">&times;</button>
            </div>
            <form class="articles-category-form module-dialog-form" method="<?= $user !== null ? 'post' : 'dialog' ?>" data-articles-proposal-form data-articles-proposal-recipient="<?= e($contactEmail) ?>" data-articles-proposal-subject="<?= e((string) ($t['propose_tag_subject'] ?? 'Proposition de mot clé article ON4CRD')) ?>" data-articles-proposal-intro="<?= e((string) ($t['propose_tag_body_intro'] ?? 'Bonjour, je souhaite proposer un nouveau mot clé d’article.')) ?>">
                <?php if ($user !== null): ?>
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="propose_tag">
                <?php endif; ?>
                <label>
                    <span><?= e((string) ($t['propose_tag_name_label'] ?? 'Mot clé')) ?></span>
                    <input type="text" name="proposal_tag" maxlength="160" required>
                </label>
                <label>
                    <span><?= e((string) ($t['propose_tag_reason_label'] ?? 'Motivation')) ?></span>
                    <textarea name="proposal_reason" rows="5" maxlength="1600"></textarea>
                </label>
                <label>
                    <span><?= e((string) ($t['propose_tag_contact_label'] ?? $t['propose_category_contact_label'])) ?></span>
                    <input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required>
                </label>
                <div class="articles-category-dialog-actions module-dialog-actions">
                    <button class="button" type="submit"><?= e((string) ($t['propose_tag_submit'] ?? $t['propose_category_submit'])) ?></button>
                    <button class="button secondary" type="button" data-articles-dialog-close><?= e((string) ($t['propose_tag_cancel'] ?? $t['propose_category_cancel'])) ?></button>
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
            <?php if ($subcategoryFilter !== ''): ?>
                <input type="hidden" name="subcategory" value="<?= e($subcategoryFilter) ?>">
            <?php endif; ?>
            <?php if ($favoritesOnly): ?>
                <input type="hidden" name="favorites" value="1">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== '' || $themeFilter !== '' || $subcategoryFilter !== '' || $favoritesOnly): ?>
                <a class="button secondary" href="<?= e(route_url('articles')) ?>"><?= e((string) $t['reset_search']) ?></a>
            <?php endif; ?>
        </form>
        <?php if ($search !== '' || $themeFilter !== '' || $subcategoryFilter !== '' || $favoritesOnly): ?>
            <p class="help"><?= e((string) $t['results']) ?> : <?= $totalArticles ?></p>
        <?php endif; ?>
    </section>

    <section class="articles-layout module-taxonomy-layout">
        <aside class="articles-index module-taxonomy-index card">
            <p class="articles-index-title module-taxonomy-title"><?= e((string) $t['theme_default']) ?></p>
            <nav class="articles-category-list module-taxonomy-list" aria-label="<?= e((string) $t['theme_default']) ?>">
                <?php if ($favoriteArticleCount > 0): ?>
                <a class="articles-category-item module-taxonomy-item<?= $favoritesOnly ? ' is-active' : '' ?>" href="<?= e(route_url_clean('articles', ['favorites' => '1', 'q' => $search])) ?>"<?= $favoritesOnly ? ' aria-current="page"' : '' ?>>
                    <span><?= e($favoritesLabel) ?></span>
                    <strong><?= (int) $favoriteArticleCount ?></strong>
                </a>
                <?php endif; ?>
                <a class="articles-category-item module-taxonomy-item<?= !$favoritesOnly && $themeFilter === '' && $subcategoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('articles', ['q' => $search])) ?>"<?= !$favoritesOnly && $themeFilter === '' && $subcategoryFilter === '' ? ' aria-current="page"' : '' ?>>
                    <span><?= e((string) ($t['all_categories'] ?? 'Toutes les thématiques')) ?></span>
                    <strong><?= (int) array_sum($themeCounts) ?></strong>
                </a>
            <?php foreach ($visibleArticleCategories as $themeCode => $themeLabel): ?>
                <a class="articles-category-item module-taxonomy-item<?= !$favoritesOnly && $themeFilter === $themeCode && $subcategoryFilter === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('articles', ['theme' => $themeCode, 'q' => $search])) ?>"<?= !$favoritesOnly && $themeFilter === $themeCode && $subcategoryFilter === '' ? ' aria-current="page"' : '' ?>>
                    <span><?= e((string) $themeLabel) ?></span>
                    <strong><?= (int) ($themeCounts[$themeCode] ?? 0) ?></strong>
                </a>
                <?php if (($visibleArticleSubcategoriesByCategory[(string) $themeCode] ?? []) !== []): ?>
                    <div class="module-taxonomy-children">
                        <?php foreach ($visibleArticleSubcategoriesByCategory[(string) $themeCode] as $subcategoryInfo): ?>
                            <?php $subCode = article_subcategory_code((string) ($subcategoryInfo['code'] ?? '')); ?>
                            <a class="articles-category-item module-taxonomy-item module-taxonomy-subitem<?= !$favoritesOnly && $themeFilter === $themeCode && $subcategoryFilter === $subCode ? ' is-active' : '' ?>" href="<?= e(route_url_clean('articles', ['theme' => $themeCode, 'subcategory' => $subCode, 'q' => $search])) ?>"<?= !$favoritesOnly && $themeFilter === $themeCode && $subcategoryFilter === $subCode ? ' aria-current="page"' : '' ?>>
                                <span><?= e((string) ($subcategoryInfo['label'] ?? $subCode)) ?></span>
                                <strong><?= (int) ($subcategoryInfo['total'] ?? 0) ?></strong>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            </nav>
        </aside>

        <div class="articles-content module-taxonomy-content">
    <?php if ($themeFilter !== '' || $subcategoryFilter !== '' || $favoritesOnly): ?>
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
                <h2><?= e((string) ($articleCategories[$themeCode] ?? (string) $t['theme_default'])) ?></h2>
                <div class="news-grid">
                    <?php foreach ($themeRows as $row): ?>
                        <?php $row = localized_article_row($row); ?>
                        <?php $articleDate = article_publication_datetime($row); ?>
                        <?php $isFavorite = $user !== null ? favorite_is_saved((int) $user['id'], 'article', (int) ($row['id'] ?? 0)) : false; ?>
                        <?php $rowSubcategory = article_subcategory_code((string) ($row['subcategory'] ?? '')); ?>
                        <?php $rowSubcategoryLabel = ''; ?>
                        <?php foreach ($articleSubcategoriesByCategory[$themeCode] ?? [] as $subcategoryInfo) {
                            if (article_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $rowSubcategory) {
                                $rowSubcategoryLabel = (string) ($subcategoryInfo['label'] ?? $rowSubcategory);
                                break;
                            }
                        } ?>
                        <article class="news-card feature-card">
                            <span class="badge muted"><?= e((string) ($articleCategories[$themeCode] ?? (string) $t['theme_default'])) ?><?= $rowSubcategoryLabel !== '' ? ' / ' . e($rowSubcategoryLabel) : '' ?></span>
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
                                        <input type="hidden" name="return_favorites" value="<?= $favoritesOnly ? '1' : '' ?>">
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
                <a class="button secondary" href="<?= e(route_url_clean('articles', ['theme' => $themeFilter, 'subcategory' => $subcategoryFilter, 'favorites' => $favoritesOnly ? '1' : '', 'q' => $search, 'page' => $page - 1])) ?>"><?= e((string) $t['previous']) ?></a>
            <?php endif; ?>
            <span class="pill"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $maxPage ?></span>
            <?php if ($page < $maxPage): ?>
                <a class="button secondary" href="<?= e(route_url_clean('articles', ['theme' => $themeFilter, 'subcategory' => $subcategoryFilter, 'favorites' => $favoritesOnly ? '1' : '', 'q' => $search, 'page' => $page + 1])) ?>"><?= e((string) $t['next']) ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
        </div>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout_title']);
