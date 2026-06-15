<?php
declare(strict_types=1);

$locale = current_locale();
$newsFallback = i18n_load_array_file_once(__DIR__ . '/../app/i18n/news/fr.php');
$newsT = array_replace(
    is_array($newsFallback) ? $newsFallback : [],
    i18n_domain_locale('news', $locale)
);
foreach ($newsFallback as $key => $value) {
    if (!isset($newsT[$key]) || !is_string($newsT[$key]) || $newsT[$key] === '') {
        $newsT[$key] = (string) $value;
    }
}

if (!table_exists('news_posts')) {
    echo render_layout(
        '<section class="page-hero">'
            . '<div>'
            . '<p class="eyebrow">' . e((string) $newsT['latest_news']) . '</p>'
            . '<h1>' . e((string) $newsT['title']) . '</h1>'
            . '<p class="help">' . e((string) $newsT['search_lead']) . '</p>'
            . '</div>'
        . '</section>'
        . '<div class="card mt-4"><p>' . e((string) $newsT['unavailable']) . '</p></div>',
        (string) $newsT['title']
    );
    return;
}

$user = current_user();
$canModerateNews = has_permission('news.moderate');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $user = require_login(route_url('news'));

        if ($action === 'propose_news') {
            $proposalTitle = (string) ($_POST['proposal_title'] ?? '');
            $proposalSource = (string) ($_POST['proposal_source'] ?? '');
            $proposalContact = (string) ($_POST['proposal_contact'] ?? '');
            $proposalSummaryRaw = (string) ($_POST['proposal_summary'] ?? '');
            $proposalSummary = content_proposal_details_text([
                (string) $newsT['propose_news_summary_label'] => $proposalSummaryRaw,
                (string) $newsT['propose_news_source_label'] => $proposalSource,
            ]);
            $autoPublish = has_permission('news.moderate');
            if ($autoPublish) {
                $title = content_proposal_clean_single_line($proposalTitle, 190);
                $summaryText = content_proposal_clean_multiline($proposalSummaryRaw, 1800);
                $sourceText = content_proposal_clean_single_line($proposalSource, 500);
                if ($title === '' || $summaryText === '') {
                    throw new RuntimeException((string) ($newsT['invalid'] ?? 'Invalid request.'));
                }
                $sectionId = news_default_section_id();
                if ($sectionId <= 0) {
                    throw new RuntimeException((string) ($newsT['unavailable'] ?? 'News storage unavailable.'));
                }
                $slug = news_unique_slug($title);
                $paragraphs = array_values(array_filter(array_map(
                    static fn(string $line): string => trim($line),
                    preg_split('/\R{2,}/u', $summaryText) ?: []
                )));
                if ($paragraphs === []) {
                    $paragraphs = [$summaryText];
                }
                $content = '';
                foreach ($paragraphs as $paragraph) {
                    $content .= '<p>' . nl2br(e($paragraph), false) . '</p>';
                }
                if ($sourceText !== '') {
                    $sourceUrl = sanitize_href_attribute($sourceText);
                    $content .= '<p><strong>Source:</strong> ';
                    $content .= $sourceUrl !== null
                        ? '<a href="' . e($sourceUrl) . '" target="_blank" rel="noopener noreferrer">' . e($sourceText) . '</a>'
                        : e($sourceText);
                    $content .= '</p>';
                }
                $excerpt = mb_safe_strimwidth($summaryText, 0, 280, '...');
                db()->prepare('INSERT INTO news_posts (section_id, author_id, slug, title, excerpt, content, status, published_at) VALUES (?, ?, ?, ?, ?, ?, "published", NOW())')
                    ->execute([$sectionId, (int) $user['id'], $slug, $title, $excerpt, sanitize_rich_html($content)]);
                $postId = (int) db()->lastInsertId();
                news_translations_sync_all($postId);
                cache_forget('news_published_count_v1');
                cache_forget('news_categories_v2');
                cache_forget('news_archives_v1');
                cache_forget('home_latest_news_v1');
                set_flash('success', (string) ($newsT['published'] ?? 'Actualite publiee.'));
                redirect_url(route_url('news_view', ['slug' => $slug]));
            }
            $proposalId = content_proposal_create((int) $user['id'], 'news', 'content', $proposalTitle, $proposalSummary, $proposalContact, $proposalSource);
            content_proposal_notify_site((string) $newsT['propose_news_subject'], [
                'area' => 'news',
                'proposal_type' => 'content',
                'title' => content_proposal_clean_single_line($proposalTitle, 190),
                'summary' => $proposalSummary,
                'contact' => content_proposal_clean_single_line($proposalContact, 220),
                'source_ref' => 'content_proposals#' . $proposalId,
            ]);
            set_flash('success', (string) ($newsT['proposal_recorded'] ?? ($locale === 'fr' ? 'Proposition enregistree dans vos contenus.' : 'Proposal saved in your content area.')));
            redirect('my_requests');
        }

        if ($action === 'propose_category') {
            $proposalTitle = (string) ($_POST['proposal_category'] ?? '');
            $proposalContact = (string) ($_POST['proposal_contact'] ?? '');
            $proposalSummary = content_proposal_details_text([
                (string) $newsT['propose_category_reason'] => (string) ($_POST['proposal_reason'] ?? ''),
            ]);
            $autoAccept = has_permission('news.moderate');
            if ($autoAccept) {
                $categoryName = content_proposal_clean_single_line($proposalTitle, 160);
                if ($categoryName === '') {
                    throw new RuntimeException((string) ($newsT['invalid'] ?? 'Invalid request.'));
                }
                $slug = news_slug_base($categoryName, 100);
                db()->prepare(
                    'INSERT INTO news_sections (slug, name, sort_order)
                     VALUES (?, ?, 100)
                     ON DUPLICATE KEY UPDATE name = VALUES(name)'
                )->execute([$slug, $categoryName]);
                cache_forget('news_categories_v2');
                set_flash('success', 'Categorie creee et validee directement.');
                redirect_url(route_url_clean('news', ['category' => $slug]));
            }
            $proposalId = content_proposal_create((int) $user['id'], 'news', 'category', $proposalTitle, $proposalSummary, $proposalContact);
            content_proposal_notify_site((string) $newsT['propose_category_subject'], [
                'area' => 'news',
                'proposal_type' => 'category',
                'title' => content_proposal_clean_single_line($proposalTitle, 190),
                'summary' => $proposalSummary,
                'contact' => content_proposal_clean_single_line($proposalContact, 220),
                'source_ref' => 'content_proposals#' . $proposalId,
            ]);
            set_flash('success', (string) ($newsT['proposal_recorded'] ?? ($locale === 'fr' ? 'Proposition enregistree dans vos contenus.' : 'Proposal saved in your content area.')));
            redirect('my_requests');
        }

        throw new RuntimeException((string) ($newsT['invalid'] ?? 'Invalid request.'));
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect_url(route_url('news'));
    }
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
$cacheBase = 'news_list_' . hash('sha256', json_encode([$where, $params, $sort]));
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
$publishedNewsCount = cache_remember('news_published_count_v1', 120, static function (): int {
    try {
        $stmt = db()->query('SELECT COUNT(*) FROM news_posts WHERE status = "published"');
        return $stmt !== false ? (int) $stmt->fetchColumn() : 0;
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
        $sql = 'SELECT p.id, p.slug, p.title, p.excerpt, p.published_at, p.updated_at, s.slug AS section_slug, s.name AS section_name, m.callsign AS author_callsign
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

$categories = cache_remember('news_categories_v2', 120, static function (): array {
    try {
        return db()->query('SELECT s.slug, s.name, COUNT(p.id) AS total
            FROM news_sections s
            LEFT JOIN news_posts p ON p.section_id = s.id AND p.status = "published"
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
$posts = array_map('localized_news_row', $posts);

$contactEmail = site_contact_email();
$newsProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode((string) $newsT['propose_news_subject'])
    . '&body=' . rawurlencode((string) $newsT['propose_news_body_intro']);
$categoryProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode((string) $newsT['propose_category_subject'])
    . '&body=' . rawurlencode((string) $newsT['propose_category_body_intro']);
$proposalContactDefault = '';
if ($user !== null) {
    $proposalContactDefault = trim((string) ($user['email'] ?? ''));
    if ($proposalContactDefault === '') {
        $proposalContactDefault = trim((string) ($user['callsign'] ?? ''));
    }
}

ob_start();
?>
<section class="page-hero news-hero">
    <div>
        <p class="eyebrow news-hero-title"><?= e((string) $newsT['latest_news']) ?></p>
        <h1><?= e((string) $newsT['title']) ?></h1>
        <p class="help"><?= e((string) $newsT['search_lead']) ?></p>
    </div>
    <div class="news-hero-side">
        <div class="news-hero-stats">
            <article class="news-hero-stat">
                <strong><?= (int) $publishedNewsCount ?></strong>
                <span><?= e((string) ($newsT['news_count_label'] ?? "Nombre d'actualit?s")) ?></span>
            </article>
        </div>
        <div class="news-hero-actions" aria-label="<?= e((string) $newsT['news_actions']) ?>">
            <a class="button" href="<?= e($newsProposalUrl) ?>" data-news-proposal-open="news-proposal-dialog" aria-haspopup="dialog" aria-controls="news-proposal-dialog"><?= e($canModerateNews ? 'Créer une actualite' : (string) $newsT['propose_news']) ?></a>
            <a class="button secondary" href="<?= e($categoryProposalUrl) ?>" data-news-proposal-open="news-category-proposal-dialog" aria-haspopup="dialog" aria-controls="news-category-proposal-dialog"><?= e($canModerateNews ? 'Créer une rubrique' : (string) $newsT['propose_category']) ?></a>
        </div>
    </div>
</section>

<dialog class="news-proposal-dialog" id="news-proposal-dialog" aria-labelledby="news-proposal-title">
    <div class="news-proposal-dialog-card">
        <div class="news-proposal-dialog-header module-dialog-header">
            <div>
                <p class="news-hero-title module-dialog-eyebrow"><?= e((string) $newsT['latest_news']) ?></p>
                <h2 id="news-proposal-title"><?= e($canModerateNews ? 'Créer une actualite' : (string) $newsT['propose_news']) ?></h2>
                <p class="help"><?= e($canModerateNews ? 'Votre actualite sera publiee directement.' : (string) $newsT['propose_news_intro']) ?></p>
            </div>
            <button class="news-proposal-dialog-close module-dialog-close" type="button" data-news-proposal-close aria-label="<?= e((string) $newsT['proposal_close']) ?>">&times;</button>
        </div>
        <form class="news-proposal-form module-dialog-form" method="<?= $user !== null ? 'post' : 'dialog' ?>" data-news-proposal-form data-news-proposal-recipient="<?= e($contactEmail) ?>" data-news-proposal-subject="<?= e((string) $newsT['propose_news_subject']) ?>" data-news-proposal-intro="<?= e((string) $newsT['propose_news_body_intro']) ?>">
            <?php if ($user !== null): ?>
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="propose_news">
            <?php endif; ?>
            <label>
                <span><?= e((string) $newsT['propose_news_title_label']) ?></span>
                <input type="text" name="proposal_title" maxlength="190" required>
            </label>
            <label>
                <span><?= e((string) $newsT['propose_news_summary_label']) ?></span>
                <textarea name="proposal_summary" rows="5" maxlength="1800" required></textarea>
            </label>
            <div class="news-proposal-form-grid">
                <label>
                    <span><?= e((string) $newsT['propose_news_source_label']) ?></span>
                    <input type="text" name="proposal_source" maxlength="500">
                </label>
                <label>
                    <span><?= e((string) $newsT['propose_news_contact_label']) ?></span>
                    <input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required>
                </label>
            </div>
            <div class="news-proposal-dialog-actions module-dialog-actions">
                <button class="button" type="submit"><?= e($canModerateNews ? 'Publier' : (string) $newsT['proposal_submit']) ?></button>
                <button class="button secondary" type="button" data-news-proposal-close><?= e((string) $newsT['proposal_cancel']) ?></button>
            </div>
        </form>
    </div>
</dialog>

<dialog class="news-proposal-dialog" id="news-category-proposal-dialog" aria-labelledby="news-category-proposal-title">
    <div class="news-proposal-dialog-card">
        <div class="news-proposal-dialog-header module-dialog-header">
            <div>
                <p class="news-hero-title module-dialog-eyebrow"><?= e((string) $newsT['category']) ?></p>
                <h2 id="news-category-proposal-title"><?= e($canModerateNews ? 'Créer une rubrique' : (string) $newsT['propose_category']) ?></h2>
                <p class="help"><?= e($canModerateNews ? 'La rubrique sera validee directement.' : (string) $newsT['propose_category_intro']) ?></p>
            </div>
            <button class="news-proposal-dialog-close module-dialog-close" type="button" data-news-proposal-close aria-label="<?= e((string) $newsT['proposal_close']) ?>">&times;</button>
        </div>
        <form class="news-proposal-form module-dialog-form" method="<?= $user !== null ? 'post' : 'dialog' ?>" data-news-proposal-form data-news-proposal-recipient="<?= e($contactEmail) ?>" data-news-proposal-subject="<?= e((string) $newsT['propose_category_subject']) ?>" data-news-proposal-intro="<?= e((string) $newsT['propose_category_body_intro']) ?>">
            <?php if ($user !== null): ?>
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="propose_category">
            <?php endif; ?>
            <label>
                <span><?= e((string) $newsT['propose_category_name']) ?></span>
                <input type="text" name="proposal_category" maxlength="160" required>
            </label>
            <label>
                <span><?= e((string) $newsT['propose_category_reason']) ?></span>
                <textarea name="proposal_reason" rows="5" maxlength="1600"></textarea>
            </label>
            <label>
                <span><?= e((string) $newsT['proposal_contact']) ?></span>
                <input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required>
            </label>
            <div class="news-proposal-dialog-actions module-dialog-actions">
                <button class="button" type="submit"><?= e($canModerateNews ? 'Créer' : (string) $newsT['proposal_submit']) ?></button>
                <button class="button secondary" type="button" data-news-proposal-close><?= e((string) $newsT['proposal_cancel']) ?></button>
            </div>
        </form>
    </div>
</dialog>

<section class="wiki-search-panel mt-4">
    <form method="get" class="wiki-search-form">
        <input type="hidden" name="route" value="news">
        <?php if ($categoryFilter !== ''): ?>
            <input type="hidden" name="category" value="<?= e($categoryFilter) ?>">
        <?php endif; ?>
        <?php if ($monthFilter !== ''): ?>
            <input type="hidden" name="ym" value="<?= e($monthFilter) ?>">
        <?php endif; ?>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $newsT['keywords_placeholder']) ?>">
        <button class="button" type="submit"><?= e((string) ($newsT['search'] ?? $newsT['apply_filters'])) ?></button>
        <?php if ($search !== '' || $monthFilter !== '' || $categoryFilter !== ''): ?>
            <a class="button secondary" href="<?= e(route_url('news')) ?>"><?= e((string) $newsT['reset']) ?></a>
        <?php endif; ?>
    </form>
</section>

<section class="news-layout mt-4">
    <aside class="news-categories card">
        <p class="news-ui-heading"><?= e((string) ($newsT['categories'] ?? $newsT['default_section'])) ?></p>
        <nav class="news-category-list" aria-label="<?= e((string) ($newsT['categories'] ?? $newsT['default_section'])) ?>">
            <a class="news-category-item" href="<?= e(route_url_clean('news', ['q' => $search, 'ym' => $monthFilter])) ?>"<?= $categoryFilter === '' ? ' aria-current="page"' : '' ?>>
                <span><?= e((string) ($newsT['all_categories'] ?? $newsT['default_section'])) ?></span>
                <strong><?= (int) $publishedNewsCount ?></strong>
            </a>
            <?php foreach ($categories as $cat): ?>
                <?php $catSlug = trim((string) ($cat['slug'] ?? '')); if ($catSlug === '') { continue; } ?>
                <a class="news-category-item" href="<?= e(route_url_clean('news', ['category' => $catSlug, 'q' => $search, 'ym' => $monthFilter])) ?>"<?= $categoryFilter === $catSlug ? ' aria-current="page"' : '' ?>>
                    <span><?= e((string) ($cat['name'] ?? $catSlug)) ?></span>
                    <strong><?= (int) ($cat['total'] ?? 0) ?></strong>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php if ($categories === []): ?>
            <p class="help"><?= e((string) ($newsT['no_news_yet'] ?? 'Aucune actualité.')) ?></p>
        <?php endif; ?>
    </aside>

    <div class="news-content">
<section class="card" id="news-list">
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
    <section class="card">
        <div class="row-between">
            <p class="help"><?= e((string) $newsT['page']) ?> <?= (int) $page ?> / <?= (int) $totalPages ?> — <?= e(sprintf((string) $newsT['news_count'], (int) $totalPosts)) ?></p>
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
    </div>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $newsT['title']);

