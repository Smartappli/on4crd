<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/wiki.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = $i18n[$locale] ?? $i18n['fr'];

if (!ensure_wiki_tables()) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['title']) . '</h1><p>' . e((string) $t['unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}

$rows = [];
$totalPagesCount = 0;
$updatedPagesCount = 0;
$revisionCount = 0;

try {
    $totalPagesCount = (int) db()->query('SELECT COUNT(*) FROM wiki_pages')->fetchColumn();
    $updatedPagesCount = (int) db()->query('SELECT COUNT(*) FROM wiki_pages WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)')->fetchColumn();
    $revisionCount = (int) db()->query('SELECT COUNT(*) FROM wiki_revisions')->fetchColumn();

    if ($search === '') {
        $stmt = db()->query(
            'SELECT p.slug, p.title, p.content, p.updated_at, p.author_id, m.callsign,
                (SELECT COUNT(*) FROM wiki_revisions r WHERE r.wiki_page_id = p.id) AS revision_count
             FROM wiki_pages p
             LEFT JOIN members m ON m.id = p.author_id
             ORDER BY p.updated_at DESC
             LIMIT 120'
        );
        $rows = $stmt->fetchAll() ?: [];
    } else {
        $stmt = db()->prepare(
            'SELECT p.slug, p.title, p.content, p.updated_at, p.author_id, m.callsign,
                (SELECT COUNT(*) FROM wiki_revisions r WHERE r.wiki_page_id = p.id) AS revision_count
             FROM wiki_pages p
             LEFT JOIN members m ON m.id = p.author_id
             WHERE p.title LIKE ? OR p.content LIKE ? OR p.slug LIKE ?
             ORDER BY p.updated_at DESC
             LIMIT 120'
        );
        $like = '%' . $search . '%';
        $stmt->execute([$like, $like, $like]);
        $rows = $stmt->fetchAll() ?: [];
    }
} catch (Throwable) {
    $rows = [];
}

ob_start();
?>
<div class="wiki-page">
    <section class="wiki-hero">
        <div>
            <p class="eyebrow"><?= e((string) $t['title']) ?></p>
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
            <?php if (has_permission('wiki.edit')): ?>
                <a class="button" href="<?= e(route_url('wiki_edit')) ?>"><?= e((string) $t['new_page']) ?></a>
            <?php endif; ?>
        </div>
    </section>

    <section class="wiki-search-panel">
        <form method="get" class="wiki-search-form">
            <input type="hidden" name="route" value="wiki">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== ''): ?>
                <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e((string) $t['reset']) ?></a>
            <?php endif; ?>
        </form>
    </section>

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
<?php
echo render_layout((string) ob_get_clean(), (string) $t['title']);
