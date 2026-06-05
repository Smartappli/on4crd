<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/wiki.php';
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

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}
$theme = slugify(trim((string) ($_GET['theme'] ?? '')));
if ($theme === 'n-a') {
    $theme = '';
}
$contactEmail = site_contact_email();
$themeProposalUrl = 'mailto:' . rawurlencode($contactEmail) . '?subject=' . rawurlencode($tr('propose_theme_subject', 'Proposition de thématique wiki ON4CRD'));

$rows = [];
$wikiThemes = [];
$totalPagesCount = 0;
$updatedPagesCount = 0;
$revisionCount = 0;

try {
    $totalPagesCount = (int) db()->query('SELECT COUNT(*) FROM wiki_pages WHERE status = "published"')->fetchColumn();
    $updatedPagesCount = (int) db()->query('SELECT COUNT(*) FROM wiki_pages WHERE status = "published" AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)')->fetchColumn();
    $revisionCount = (int) db()->query('SELECT COUNT(*) FROM wiki_revisions')->fetchColumn();

    $themeRows = db()->query('SELECT slug FROM wiki_pages WHERE status = "published" ORDER BY slug ASC')->fetchAll() ?: [];
    foreach ($themeRows as $themeRow) {
        $slug = trim((string) ($themeRow['slug'] ?? ''));
        $themeSeed = $slug !== '' ? explode('-', $slug, 2)[0] : '';
        $themeCode = slugify($themeSeed);
        if ($themeCode === '' || $themeCode === 'n-a') {
            continue;
        }
        $wikiThemes[$themeCode] = ($wikiThemes[$themeCode] ?? 0) + 1;
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
    array_unshift($where, 'p.status = "published"');
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
            <div class="wiki-dashboard wiki-hero-dashboard">
                <article class="wiki-stat">
                    <span><?= e((string) $t['wiki_pages']) ?></span>
                    <strong><?= $totalPagesCount ?></strong>
                </article>
                <article class="wiki-stat">
                    <span><?= e((string) $t['updated_pages']) ?></span>
                    <strong><?= $updatedPagesCount ?></strong>
                </article>
                <article class="wiki-stat">
                    <span><?= e((string) $t['revisions']) ?></span>
                    <strong><?= $revisionCount ?></strong>
                </article>
            </div>
            <div class="wiki-hero-actions">
                <button class="button secondary" type="button" data-wiki-theme-open data-wiki-theme-fallback="<?= e($themeProposalUrl) ?>" aria-haspopup="dialog" aria-controls="wiki-theme-dialog"><?= e($tr('propose_theme', 'Proposer une thématique')) ?></button>
                <a class="button" href="<?= e(route_url('wiki_propose')) ?>"><?= e($tr('propose_page', 'Proposer une page')) ?></a>
                <?php if (has_permission('wiki.moderate')): ?>
                    <a class="button secondary" href="<?= e(route_url('wiki_edit')) ?>"><?= e((string) $t['new_page']) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <dialog class="wiki-theme-dialog" id="wiki-theme-dialog" aria-labelledby="wiki-theme-dialog-title">
        <div class="wiki-theme-dialog-card">
            <div class="wiki-theme-dialog-header">
                <div>
                    <p class="wiki-theme-dialog-eyebrow"><?= e($tr('themes', 'Thématiques')) ?></p>
                    <h2 id="wiki-theme-dialog-title"><?= e($tr('propose_theme', 'Proposer une thématique')) ?></h2>
                    <p class="help"><?= e($tr('propose_theme_intro', 'Indiquez la thématique à ajouter et les pages qui devraient y être liées.')) ?></p>
                </div>
                <button class="wiki-theme-dialog-close" type="button" data-wiki-theme-close aria-label="<?= e($tr('close', 'Fermer')) ?>">&times;</button>
            </div>
            <form class="wiki-theme-form" method="dialog" data-wiki-theme-form data-wiki-theme-recipient="<?= e($contactEmail) ?>" data-wiki-theme-subject="<?= e($tr('propose_theme_subject', 'Proposition de thématique wiki ON4CRD')) ?>" data-wiki-theme-intro="<?= e($tr('propose_theme_body_intro', 'Proposition de thématique wiki :')) ?>">
                <label><span><?= e($tr('propose_theme_name', 'Nom de la thématique')) ?></span><input type="text" name="proposal_theme" maxlength="160" required></label>
                <label><span><?= e($tr('propose_theme_reason', 'Pourquoi l\'ajouter ?')) ?></span><textarea name="proposal_reason" rows="5" maxlength="1600"></textarea></label>
                <label><span><?= e($tr('propose_theme_contact', 'Votre contact')) ?></span><input type="text" name="proposal_contact" maxlength="220" required></label>
                <div class="wiki-theme-dialog-actions">
                    <button class="button" type="submit"><?= e($tr('propose_theme_submit', 'Envoyer la proposition')) ?></button>
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

    <section class="wiki-layout">
        <aside class="wiki-themes card">
            <p class="wiki-themes-title"><?= e($tr('themes', 'Thématiques')) ?></p>
            <nav class="wiki-theme-list" aria-label="<?= e($tr('themes', 'Thématiques')) ?>">
                <a class="wiki-theme-item<?= $theme === '' ? ' is-active' : '' ?>" href="<?= e(route_url_clean('wiki', ['q' => $search])) ?>">
                    <span><?= e($tr('all_themes', 'Toutes les thématiques')) ?></span>
                    <strong><?= (int) ($wikiThemes !== [] ? array_sum($wikiThemes) : $totalPagesCount) ?></strong>
                </a>
                <?php foreach ($wikiThemes as $themeCode => $themeTotal): ?>
                    <a class="wiki-theme-item<?= $themeCode === $theme ? ' is-active' : '' ?>" href="<?= e(route_url_clean('wiki', ['theme' => $themeCode, 'q' => $search])) ?>"<?= $themeCode === $theme ? ' aria-current="page"' : '' ?>>
                        <span><?= e(ucfirst(str_replace('-', ' ', $themeCode))) ?></span>
                        <strong><?= (int) $themeTotal ?></strong>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="wiki-content">
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
