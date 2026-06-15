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
                redirect_url(route_url_clean('wiki', ['theme' => slugify($proposalTitle)]));
            }
            set_flash('success', $tr('proposal_recorded', $locale === 'fr' ? 'Proposition enregistree dans vos contenus.' : 'Proposal saved in your content area.'));
            redirect('my_requests');
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

$rows = [];
$wikiThemes = [];
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

    $themeRows = db()->query('SELECT slug FROM wiki_pages WHERE ' . $publicWikiWhere . ' ORDER BY slug ASC')->fetchAll() ?: [];
    foreach ($themeRows as $themeRow) {
        $slug = trim((string) ($themeRow['slug'] ?? ''));
        $themeSeed = $slug !== '' ? explode('-', $slug, 2)[0] : '';
        $themeCode = slugify($themeSeed);
        if ($themeCode === '' || $themeCode === 'n-a') {
            continue;
        }
        $wikiThemes[$themeCode] = ($wikiThemes[$themeCode] ?? 0) + 1;
    }
    if (table_exists('content_proposals')) {
        $categoryRows = db()->query('SELECT title FROM content_proposals WHERE area = "wiki" AND proposal_type = "category" AND status = "accepted" ORDER BY title ASC')->fetchAll() ?: [];
        foreach ($categoryRows as $categoryRow) {
            $themeCode = slugify((string) ($categoryRow['title'] ?? ''));
            if ($themeCode === '' || $themeCode === 'n-a') {
                continue;
            }
            $wikiThemes[$themeCode] = $wikiThemes[$themeCode] ?? 0;
        }
    }
    if ($theme !== '' && !isset($wikiThemes[$theme])) {
        $theme = '';
    }

    $where = [];
    $params = [];
    if ($theme !== '') {
        $where[] = '(p.slug = ? OR p.slug LIKE ?)';
        $params[] = $theme;
        $params[] = $theme . '-%';
    }
    if ($search !== '') {
        $where[] = '(p.title LIKE ? OR p.content LIKE ? OR p.slug LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    array_unshift($where, wiki_public_page_where_sql('p'));
    $whereSql = ' WHERE ' . implode(' AND ', $where);
    $stmt = db()->prepare(
        'SELECT p.slug, p.title, p.content, p.updated_at, p.author_id, m.callsign,
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
            <?php if ($theme !== ''): ?>
                <input type="hidden" name="theme" value="<?= e($theme) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== ''): ?>
                <a class="button secondary" href="<?= e(route_url_clean('wiki', ['theme' => $theme])) ?>"><?= e((string) $t['reset']) ?></a>
            <?php endif; ?>
        </form>
        <?php if ($search !== '' || $theme !== ''): ?>
            <p class="help"><?= e((string) $t['wiki_pages']) ?> : <?= (int) count($rows) ?></p>
        <?php endif; ?>
    </section>

    <section class="wiki-layout module-taxonomy-layout">
        <aside class="wiki-themes module-taxonomy-index card">
            <p class="wiki-themes-title"><?= e($tr('themes', 'Thématiques')) ?></p>
            <nav class="wiki-theme-list" aria-label="<?= e($tr('themes', 'Thématiques')) ?>">
                <a class="wiki-theme-item module-taxonomy-item<?= $theme === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('wiki', ['q' => $search])) ?>">
                    <span><?= e($tr('all_themes', 'Toutes les thématiques')) ?></span>
                    <strong><?= (int) ($wikiThemes !== [] ? array_sum($wikiThemes) : $totalPagesCount) ?></strong>
                </a>
                <?php foreach ($wikiThemes as $themeCode => $themeTotal): ?>
                    <a class="wiki-theme-item module-taxonomy-item<?= $themeCode === $theme ? ' is-active' : '' ?>" href="<?= e(route_url_clean('wiki', ['theme' => $themeCode, 'q' => $search])) ?>"<?= $themeCode === $theme ? ' aria-current="page"' : '' ?>>
                        <span><?= e(ucfirst(str_replace('-', ' ', $themeCode))) ?></span>
                        <strong><?= (int) $themeTotal ?></strong>
                    </a>
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
                <p class="help"><?= $search !== '' ? e((string) $t['for_search']) : e((string) $t['summary_fallback']) ?></p>
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
                    ?>
                    <article class="wiki-card">
                        <div class="wiki-card-main">
                            <span class="wiki-slug">/<?= e((string) $row['slug']) ?></span>
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
