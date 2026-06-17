<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/wiki.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = $i18n[$locale] ?? $i18n['fr'];
$tr = static function (string $key, string $fallback) use ($t): string {
    $value = trim((string) ($t[$key] ?? ''));

    return $value !== '' ? $value : $fallback;
};

if (!ensure_wiki_tables()) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['title']) . '</h1><p>' . e((string) $t['unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'propose_theme') {
            $user = require_login(route_url('wiki'));
            $autoAccept = has_permission('wiki.moderate');
            $proposalTitle = (string) ($_POST['proposal_theme'] ?? '');
            $proposalContact = (string) ($_POST['proposal_contact'] ?? '');
            $proposalSummary = content_proposal_details_text([
                $tr('propose_theme_reason', 'Reason') => (string) ($_POST['proposal_reason'] ?? ''),
            ]);
            $proposalStatus = $autoAccept ? 'accepted' : 'pending';
            $proposalId = content_proposal_create((int) $user['id'], 'wiki', 'category', $proposalTitle, $proposalSummary, $proposalContact, '', $proposalStatus);
            if (!$autoAccept) {
                content_proposal_notify_site($tr('propose_theme_subject', 'Wiki theme proposal'), [
                    'area' => 'wiki',
                    'proposal_type' => 'category',
                    'title' => content_proposal_clean_single_line($proposalTitle, 190),
                    'summary' => $proposalSummary,
                    'contact' => content_proposal_clean_single_line($proposalContact, 220),
                    'source_ref' => 'content_proposals#' . $proposalId,
                ]);
            }
            if ($autoAccept) {
                set_flash('success', $tr('category_accepted', 'Catégorie wiki créée.'));
                redirect_url(route_url_clean('wiki', ['theme' => wiki_category_code($proposalTitle)]));
            }
            set_flash('success', $tr('proposal_recorded', $locale === 'fr' ? 'Proposition enregistree dans vos contenus.' : 'Proposal saved in your content area.'));
            redirect('my_requests');
        }

        if ($action === 'toggle_favorite_page') {
            $user = require_login(route_url('wiki'));
            $pageId = (int) ($_POST['page_id'] ?? 0);
            if ($pageId > 0 && function_exists('favorite_toggle')) {
                $pageStmt = db()->prepare('SELECT id, slug, title FROM wiki_pages WHERE id = ? AND ' . wiki_public_page_where_sql() . ' LIMIT 1');
                $pageStmt->execute([$pageId]);
                $pageRow = $pageStmt->fetch() ?: null;
                if (is_array($pageRow)) {
                    $pageTitle = (string) ($pageRow['title'] ?? '');
                    $pageUrl = route_url('wiki_view', ['slug' => (string) ($pageRow['slug'] ?? '')]);
                    $saved = favorite_toggle((int) $user['id'], 'wiki_page', (int) $pageRow['id'], $pageTitle, $pageUrl);
                    notify_member((int) $user['id'], 'favorite', $saved ? 'Favorite added' : 'Favorite removed', $pageTitle, $pageUrl);
                    set_flash('success', $saved ? 'Page ajoutée aux favoris.' : 'Page retirée des favoris.');
                }
            }
            redirect_url(route_url_clean('wiki', [
                'theme' => (string) ($_POST['return_theme'] ?? $_GET['theme'] ?? ''),
                'subtheme' => (string) ($_POST['return_subtheme'] ?? $_GET['subtheme'] ?? ''),
                'favorites' => (string) ($_POST['return_favorites'] ?? $_GET['favorites'] ?? '') === '1' ? '1' : '',
                'q' => (string) ($_POST['return_q'] ?? $_GET['q'] ?? ''),
            ]));
        }

        throw new RuntimeException($tr('invalid', 'Invalid request.'));
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect_url(route_url('wiki'));
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}
$theme = slugify(trim((string) ($_GET['theme'] ?? '')));
if ($theme === 'n-a') {
    $theme = '';
}
$subtheme = wiki_subcategory_code((string) ($_GET['subtheme'] ?? ''));
$contactEmail = site_contact_email();
$themeProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode($tr('propose_theme_subject', 'Proposition de thématique wiki ON4CRD'))
    . '&body=' . rawurlencode($tr('propose_theme_body_intro', 'Proposition de thématique wiki :'));
$proposalContactDefault = '';
if ($user !== null) {
    $proposalContactDefault = trim((string) ($user['email'] ?? ''));
    if ($proposalContactDefault === '') {
        $proposalContactDefault = trim((string) ($user['callsign'] ?? ''));
    }
}
$canAutoAcceptTheme = $user !== null && has_permission('wiki.moderate');
$canAdminWiki = $user !== null && has_permission('admin.access');
$wikiProposalMenuLabel = $tr('propose_menu', 'Proposer');
$wikiNewPageLabel = $tr('propose_new_page', 'Une nouvelle page');
$wikiModificationLabel = $tr('propose_modification', 'Une modification');
$wikiNewThemeLabel = $tr('propose_new_theme', 'Une nouvelle thématique');
$wikiAdminLabel = $tr('administer', $locale === 'fr' ? 'Administrer' : 'Administer');
$wikiCategoryLabels = wiki_categories($t);
$wikiSubcategoriesByCategory = wiki_subcategories_by_category();
$favoriteWikiPageIds = $user !== null ? wiki_favorite_page_ids((int) ($user['id'] ?? 0)) : [];
$favoriteWikiPageCount = count($favoriteWikiPageIds);
$favoritesOnly = (string) ($_GET['favorites'] ?? '') === '1' && $favoriteWikiPageCount > 0;
$favoritesLabel = wiki_favorites_label($t, $locale);

$rows = [];
$wikiThemes = [];
$wikiSubthemes = [];
$visibleWikiThemes = [];
$visibleWikiSubthemes = [];
$totalPagesCount = 0;
$updatedPagesCount = 0;
$revisionCount = 0;
$latestWikiDate = '';

try {
    $publicWikiWhere = wiki_public_page_where_sql();
    $totalPagesCount = (int) db()->query('SELECT COUNT(*) FROM wiki_pages WHERE ' . $publicWikiWhere)->fetchColumn();
    $updatedPagesCount = (int) db()->query('SELECT COUNT(*) FROM wiki_pages WHERE ' . $publicWikiWhere . ' AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)')->fetchColumn();
    $revisionCount = (int) db()->query('SELECT COUNT(*) FROM wiki_revisions')->fetchColumn();
    $latestWikiDate = trim((string) (db()->query('SELECT updated_at FROM wiki_pages WHERE ' . $publicWikiWhere . ' ORDER BY updated_at DESC, id DESC LIMIT 1')->fetchColumn() ?: ''));

    $themeRows = db()->query('SELECT category, COUNT(*) AS total FROM wiki_pages WHERE ' . $publicWikiWhere . ' GROUP BY category ORDER BY category ASC')->fetchAll() ?: [];
    foreach ($themeRows as $themeRow) {
        $themeCode = wiki_category_code((string) ($themeRow['category'] ?? ''));
        if ($themeCode === '' || $themeCode === 'n-a') {
            continue;
        }
        if (!isset($wikiCategoryLabels[$themeCode])) {
            $wikiCategoryLabels[$themeCode] = wiki_category_label_from_code($themeCode);
        }
        $wikiThemes[$themeCode] = ($wikiThemes[$themeCode] ?? 0) + (int) ($themeRow['total'] ?? 0);
    }
    $subthemeRows = db()->query('SELECT category, subcategory, COUNT(*) AS total FROM wiki_pages WHERE ' . $publicWikiWhere . ' AND subcategory IS NOT NULL AND subcategory <> "" GROUP BY category, subcategory ORDER BY category ASC, subcategory ASC')->fetchAll() ?: [];
    foreach ($subthemeRows as $subthemeRow) {
        $themeCode = wiki_category_code((string) ($subthemeRow['category'] ?? 'general'));
        $subthemeCode = wiki_subcategory_code((string) ($subthemeRow['subcategory'] ?? ''));
        if ($themeCode === '' || $subthemeCode === '') {
            continue;
        }
        $wikiSubthemes[$themeCode . ':' . $subthemeCode] = (int) ($subthemeRow['total'] ?? 0);
        $known = false;
        foreach ($wikiSubcategoriesByCategory[$themeCode] ?? [] as $subcategoryOption) {
            if (wiki_subcategory_code((string) ($subcategoryOption['code'] ?? '')) === $subthemeCode) {
                $known = true;
                break;
            }
        }
        if (!$known) {
            $wikiSubcategoriesByCategory[$themeCode][] = [
                'category_code' => $themeCode,
                'code' => $subthemeCode,
                'label' => wiki_category_label_from_code($subthemeCode),
            ];
        }
    }
    $visibleWikiThemes = wiki_visible_categories($wikiCategoryLabels, $wikiThemes);
    $visibleWikiSubthemes = wiki_visible_subcategories_by_category($wikiSubcategoriesByCategory, $wikiSubthemes);
    if (table_exists('content_proposals')) {
        $categoryRows = db()->query('SELECT title FROM content_proposals WHERE area = "wiki" AND proposal_type = "category" AND status = "accepted" ORDER BY title ASC')->fetchAll() ?: [];
        foreach ($categoryRows as $categoryRow) {
            $themeCode = wiki_category_code((string) ($categoryRow['title'] ?? ''));
            if ($themeCode === '' || $themeCode === 'n-a') {
                continue;
            }
            $wikiCategoryLabels[$themeCode] = content_proposal_clean_single_line((string) ($categoryRow['title'] ?? $themeCode), 190);
        }
    }
    if ($theme !== '' && !isset($wikiThemes[$theme])) {
        $theme = '';
    }
    if ($subtheme !== '') {
        $candidateTheme = $theme;
        if ($candidateTheme === '') {
            foreach ($visibleWikiSubthemes as $parentCode => $subthemes) {
                foreach ($subthemes as $subthemeInfo) {
                    if (wiki_subcategory_code((string) ($subthemeInfo['code'] ?? '')) === $subtheme) {
                        $candidateTheme = (string) $parentCode;
                        break 2;
                    }
                }
            }
        }
        if ($candidateTheme !== '' && (int) ($wikiSubthemes[$candidateTheme . ':' . $subtheme] ?? 0) > 0) {
            $theme = $candidateTheme;
        } else {
            $subtheme = '';
        }
    }

    $where = [];
    $params = [];
    if ($theme !== '') {
        $where[] = 'p.category = ?';
        $params[] = $theme;
    }
    if ($subtheme !== '') {
        $where[] = 'p.subcategory = ?';
        $params[] = $subtheme;
    }
    if ($favoritesOnly) {
        $where[] = 'p.id IN (' . implode(',', array_fill(0, $favoriteWikiPageCount, '?')) . ')';
        array_push($params, ...$favoriteWikiPageIds);
    }
    if ($search !== '') {
        $where[] = '(p.title LIKE ? OR p.content LIKE ? OR p.slug LIKE ? OR p.category LIKE ? OR p.subcategory LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    array_unshift($where, wiki_public_page_where_sql('p'));
    $whereSql = ' WHERE ' . implode(' AND ', $where);
    $stmt = db()->prepare(
        'SELECT p.id, p.slug, p.title, p.content, p.category, p.subcategory, p.updated_at, p.author_id, m.callsign,
            (SELECT COUNT(*) FROM wiki_revisions r WHERE r.wiki_page_id = p.id) AS revision_count
         FROM wiki_pages p
         LEFT JOIN members m ON m.id = p.author_id
         ' . $whereSql . '
         ORDER BY p.updated_at DESC
         LIMIT 120'
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll() ?: [];
} catch (Throwable) {
    $rows = [];
}
$latestWikiLabel = module_hero_latest_stat_date_label($latestWikiDate, $locale);

ob_start();
?>
<div class="wiki-page">
    <section class="wiki-hero">
        <div>
            <p class="eyebrow wiki-hero-title"><?= e((string) $t['title']) ?></p>
            <h1><?= e((string) $t['wiki_pages']) ?></h1>
            <p class="help"><?= e((string) $t['summary_fallback']) ?></p>
        </div>
        <div class="wiki-hero-side">
            <div class="wiki-hero-stats">
                <article>
                    <span><?= e((string) $t['wiki_pages']) ?></span>
                    <strong><?= $totalPagesCount ?></strong>
                </article>
                <article>
                    <span><?= e((string) $t['updated_pages']) ?></span>
                    <strong><?= $updatedPagesCount ?></strong>
                </article>
                <article>
                    <span><?= e((string) $t['revisions']) ?></span>
                    <strong><?= $revisionCount ?></strong>
                </article>
                <article>
                    <span><?= e(module_hero_latest_stat_text('latest', $locale)) ?></span>
                    <strong><?= e($latestWikiLabel) ?></strong>
                </article>
            </div>
            <div class="actions wiki-hero-actions">
                <details class="wiki-propose-menu">
                    <summary class="button" aria-haspopup="menu"><?= e($wikiProposalMenuLabel) ?></summary>
                    <div class="wiki-propose-menu-panel" role="menu">
                        <a class="wiki-propose-menu-item" role="menuitem" href="<?= e(route_url('wiki_propose')) ?>"><?= e($wikiNewPageLabel) ?></a>
                        <a class="wiki-propose-menu-item" role="menuitem" href="<?= e(route_url('wiki_propose', ['mode' => 'modify'])) ?>"><?= e($wikiModificationLabel) ?></a>
                        <a class="wiki-propose-menu-item" role="menuitem" href="<?= e($themeProposalUrl) ?>" data-wiki-theme-open aria-haspopup="dialog" aria-controls="wiki-theme-dialog"><?= e($wikiNewThemeLabel) ?></a>
                    </div>
                </details>
                <?php if ($canAdminWiki): ?>
                    <a class="button secondary" href="<?= e(route_url_clean('admin_wiki', ['status' => 'pending'])) ?>"><?= e($wikiAdminLabel) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <dialog class="wiki-theme-dialog" id="wiki-theme-dialog" aria-labelledby="wiki-theme-dialog-title">
        <div class="wiki-theme-dialog-card">
            <div class="wiki-theme-dialog-header module-dialog-header">
                <div>
                    <p class="wiki-theme-dialog-eyebrow"><?= e($tr('themes', 'Thématiques')) ?></p>
                    <h2 id="wiki-theme-dialog-title"><?= e($tr('propose_theme', 'Proposer une thématique')) ?></h2>
                    <p class="help"><?= e($canAutoAcceptTheme
                        ? $tr('create_theme_intro', 'Avec vos droits de modération, la thématique sera validée directement.')
                        : $tr('propose_theme_intro', 'Indiquez la thématique à ajouter et les pages qui devraient y être liées.')) ?></p>
                </div>
                <button class="wiki-theme-dialog-close module-dialog-close" type="button" data-wiki-theme-close aria-label="<?= e($tr('close', 'Fermer')) ?>">&times;</button>
            </div>
            <form class="wiki-theme-form" method="<?= $user !== null ? 'post' : 'dialog' ?>" data-wiki-theme-form data-wiki-theme-recipient="<?= e($contactEmail) ?>" data-wiki-theme-subject="<?= e($tr('propose_theme_subject', 'Proposition de thématique wiki ON4CRD')) ?>" data-wiki-theme-intro="<?= e($tr('propose_theme_body_intro', 'Proposition de thématique wiki :')) ?>">
                <?php if ($user !== null): ?>
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="propose_theme">
                <?php endif; ?>
                <label><span><?= e($tr('propose_theme_name', 'Nom de la thématique')) ?></span><input type="text" name="proposal_theme" maxlength="160" required></label>
                <label><span><?= e($tr('propose_theme_reason', 'Pourquoi l\'ajouter ?')) ?></span><textarea name="proposal_reason" rows="5" maxlength="1600"></textarea></label>
                <label><span><?= e($tr('propose_theme_contact', 'Votre contact')) ?></span><input type="text" name="proposal_contact" maxlength="220" value="<?= e($proposalContactDefault) ?>" required></label>
                <div class="wiki-theme-dialog-actions module-dialog-actions">
                    <button class="button" type="submit"><?= e($canAutoAcceptTheme ? $tr('create_theme_submit', 'Proposer la thématique') : $tr('propose_theme_submit', 'Envoyer la proposition')) ?></button>
                    <button class="button secondary" type="button" data-wiki-theme-close><?= e($tr('cancel', 'Annuler')) ?></button>
                </div>
            </form>
        </div>
    </dialog>

    <section class="card wiki-search-panel">
        <form method="get" class="inline-form wiki-search-form">
            <input type="hidden" name="route" value="wiki">
            <?php if ($theme !== '' || $subtheme !== '' || $favoritesOnly): ?>
                <input type="hidden" name="theme" value="<?= e($theme) ?>">
            <?php endif; ?>
            <?php if ($subtheme !== ''): ?>
                <input type="hidden" name="subtheme" value="<?= e($subtheme) ?>">
            <?php endif; ?>
            <?php if ($favoritesOnly): ?>
                <input type="hidden" name="favorites" value="1">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== '' || $theme !== '' || $subtheme !== '' || $favoritesOnly): ?>
                <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e((string) $t['reset']) ?></a>
            <?php endif; ?>
        </form>
        <?php if ($search !== '' || $theme !== '' || $subtheme !== '' || $favoritesOnly): ?>
            <p class="help"><?= e((string) $t['wiki_pages']) ?> : <?= (int) count($rows) ?></p>
        <?php endif; ?>
    </section>

    <section class="wiki-layout module-taxonomy-layout">
        <aside class="wiki-themes module-taxonomy-index card">
            <p class="wiki-themes-title"><?= e($tr('themes', 'Thématiques')) ?></p>
            <nav class="wiki-theme-list" aria-label="<?= e($tr('themes', 'Thématiques')) ?>">
                <?php if ($favoriteWikiPageCount > 0): ?>
                    <a class="wiki-theme-item module-taxonomy-item<?= $favoritesOnly ? ' is-active' : '' ?>" href="<?= e(route_url_clean('wiki', ['favorites' => '1', 'q' => $search])) ?>"<?= $favoritesOnly ? ' aria-current="page"' : '' ?>>
                        <span><?= e($favoritesLabel) ?></span>
                        <strong><?= (int) $favoriteWikiPageCount ?></strong>
                    </a>
                <?php endif; ?>
                <a class="wiki-theme-item module-taxonomy-item<?= !$favoritesOnly && $theme === '' && $subtheme === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('wiki', ['q' => $search])) ?>">
                    <span><?= e($tr('all_themes', 'Toutes les thématiques')) ?></span>
                    <strong><?= (int) ($wikiThemes !== [] ? array_sum($wikiThemes) : $totalPagesCount) ?></strong>
                </a>
                <?php foreach ($visibleWikiThemes as $themeCode => $themeLabel): ?>
                    <a class="wiki-theme-item module-taxonomy-item<?= !$favoritesOnly && $themeCode === $theme && $subtheme === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('wiki', ['theme' => $themeCode, 'q' => $search])) ?>"<?= !$favoritesOnly && $themeCode === $theme && $subtheme === '' ? ' aria-current="page"' : '' ?>>
                        <span><?= e((string) ($wikiCategoryLabels[$themeCode] ?? wiki_category_label_from_code($themeCode))) ?></span>
                        <strong><?= (int) ($wikiThemes[$themeCode] ?? 0) ?></strong>
                    </a>
                    <?php if (($visibleWikiSubthemes[(string) $themeCode] ?? []) !== []): ?>
                        <div class="module-taxonomy-children">
                            <?php foreach ($visibleWikiSubthemes[(string) $themeCode] as $subthemeInfo): ?>
                                <?php $subCode = wiki_subcategory_code((string) ($subthemeInfo['code'] ?? '')); ?>
                                <a class="wiki-theme-item module-taxonomy-item module-taxonomy-subitem<?= !$favoritesOnly && $themeCode === $theme && $subtheme === $subCode ? ' is-active' : '' ?>" href="<?= e(route_url_clean('wiki', ['theme' => $themeCode, 'subtheme' => $subCode, 'q' => $search])) ?>"<?= !$favoritesOnly && $themeCode === $theme && $subtheme === $subCode ? ' aria-current="page"' : '' ?>>
                                    <span><?= e((string) ($subthemeInfo['label'] ?? $subCode)) ?></span>
                                    <strong><?= (int) ($subthemeInfo['total'] ?? 0) ?></strong>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="wiki-content module-taxonomy-content">
            <?php if ($theme !== ''): ?>
                <div class="card">
                    <p><a class="pill" href="<?= e(route_url_clean('wiki', ['q' => $search])) ?>"><?= e((string) $t['reset']) ?></a></p>
                </div>
            <?php endif; ?>

            <section class="wiki-directory">
        <?php if ($rows === []): ?>
            <article class="wiki-empty">
                <h2><?= e((string) $t['no_page']) ?></h2>
                <p class="help"><?= ($search !== '' || $theme !== '' || $subtheme !== '' || $favoritesOnly) ? e((string) $t['for_search']) : e((string) $t['summary_fallback']) ?></p>
            </article>
        <?php else: ?>
            <div class="wiki-grid">
                <?php foreach ($rows as $row):
                    $summary = trim(strip_tags((string) ($row['content'] ?? '')));
                    if ($summary === '') {
                        $summary = (string) $t['summary_fallback'];
                    }
                    $summary = mb_safe_strimwidth($summary, 0, 220, '...');
                    $author = trim((string) ($row['callsign'] ?? ''));
                    $revisionTotal = (int) ($row['revision_count'] ?? 0);
                    $categoryCode = wiki_category_code((string) ($row['category'] ?? 'general'));
                    $categoryLabel = (string) ($wikiCategoryLabels[$categoryCode] ?? wiki_category_label_from_code($categoryCode));
                    $rowSubtheme = wiki_subcategory_code((string) ($row['subcategory'] ?? ''));
                    $rowSubthemeLabel = $rowSubtheme !== '' ? wiki_category_label_from_code($rowSubtheme) : '';
                    $rowId = (int) ($row['id'] ?? 0);
                    $rowIsFavorite = $user !== null && $rowId > 0 && function_exists('favorite_is_saved') && favorite_is_saved((int) ($user['id'] ?? 0), 'wiki_page', $rowId);
                    ?>
                    <article class="wiki-card">
                        <div class="wiki-card-main">
                            <div class="wiki-card-kicker">
                                <span class="wiki-slug">/<?= e((string) $row['slug']) ?></span>
                                <a class="wiki-category-badge" href="<?= e(route_url_clean('wiki', ['theme' => $categoryCode])) ?>"><?= e($categoryLabel) ?></a>
                                <?php if ($rowSubtheme !== ''): ?>
                                    <a class="wiki-category-badge" href="<?= e(route_url_clean('wiki', ['theme' => $categoryCode, 'subtheme' => $rowSubtheme])) ?>"><?= e($rowSubthemeLabel) ?></a>
                                <?php endif; ?>
                            </div>
                            <h2><a href="<?= e(route_url('wiki_view', ['slug' => (string) $row['slug']])) ?>"><?= e((string) $row['title']) ?></a></h2>
                            <p><?= e($summary) ?></p>
                        </div>
                        <div class="wiki-card-meta">
                            <span><?= e((string) $t['updated_at']) ?> <?= e(date('d/m/Y H:i', strtotime((string) $row['updated_at']))) ?></span>
                            <?php if ($author !== ''): ?><span><?= e($author) ?></span><?php endif; ?>
                            <span><?= $revisionTotal ?> <?= e((string) $t['revisions']) ?></span>
                        </div>
                        <div class="wiki-card-actions">
                            <a class="button secondary" href="<?= e(route_url('wiki_view', ['slug' => (string) $row['slug']])) ?>"><?= e((string) $t['open_page']) ?></a>
                            <?php if ($user !== null && $rowId > 0): ?>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="toggle_favorite_page">
                                    <input type="hidden" name="page_id" value="<?= $rowId ?>">
                                    <input type="hidden" name="return_q" value="<?= e($search) ?>">
                                    <input type="hidden" name="return_theme" value="<?= e($theme) ?>">
                                    <input type="hidden" name="return_subtheme" value="<?= e($subtheme) ?>">
                                    <input type="hidden" name="return_favorites" value="<?= $favoritesOnly ? '1' : '' ?>">
                                    <button class="button secondary" type="submit"><?= $rowIsFavorite ? '&#9733;' : '&#9734;' ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
            </section>
        </div>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['title']);
