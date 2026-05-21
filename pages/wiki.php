<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/wiki.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = $i18n[$locale] ?? $i18n['fr'];

if (!table_exists('wiki_pages')) {
    echo render_layout('<div class="card"><h1>' . e((string) $t['title']) . '</h1><p>' . e((string) $t['unavailable']) . '</p></div>', (string) $t['title']);
    return;
}

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 120) {
    $search = mb_substr($search, 0, 120);
}

$rows = [];
try {
    if ($search === '') {
        $stmt = db()->query('SELECT slug, title, content, updated_at FROM wiki_pages ORDER BY updated_at DESC LIMIT 120');
        $rows = $stmt->fetchAll() ?: [];
    } else {
        $stmt = db()->prepare('SELECT slug, title, content, updated_at FROM wiki_pages WHERE title LIKE ? OR content LIKE ? ORDER BY updated_at DESC LIMIT 120');
        $like = '%' . $search . '%';
        $stmt->execute([$like, $like]);
        $rows = $stmt->fetchAll() ?: [];
    }
} catch (Throwable) {
    $rows = [];
}

ob_start();
?>
<div class="stack">
    <section class="card wiki-header">
        <div class="stats-grid">
            <article class="stat-card">
                <span class="stat-card-label"><?= e((string) $t['new_pages']) ?></span>
            </article>
            <article class="stat-card">
                <span class="stat-card-label"><?= e((string) $t['updated_pages']) ?></span>
            </article>
            <article class="stat-card">
                <span class="stat-card-label"><?= e((string) $t['most_read']) ?></span>
            </article>
        </div>
        <form method="get" class="inline-form">
            <input type="hidden" name="route" value="wiki">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
            <button class="button" type="submit"><?= e((string) $t['search']) ?></button>
            <?php if ($search !== ''): ?>
                <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e((string) $t['reset']) ?></a>
            <?php endif; ?>
        </form>
    </section>

    <section class="card">
        <h2 class="wiki-section-title"><?= e((string) $t['wiki_pages']) ?></h2>
        <?php if ($rows === []): ?>
            <p><?= e((string) $t['no_page']) ?><?= $search !== '' ? e((string) $t['for_search']) : '' ?>.</p>
        <?php else: ?>
            <div class="wiki-grid">
                <?php foreach ($rows as $row):
                    $summary = trim(strip_tags((string) ($row['content'] ?? '')));
                    if ($summary === '') {
                        $summary = (string) $t['summary_fallback'];
                    }
                    if (mb_strlen($summary) > 190) {
                        $summary = mb_substr($summary, 0, 187) . '…';
                    }
                    ?>
                    <article class="wiki-card">
                        <h3><a href="<?= e(base_url('index.php?route=wiki_view&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $row['title']) ?></a></h3>
                        <p class="help"><?= e((string) $t['updated_at']) ?> <?= e(date('d/m/Y H:i', strtotime((string) $row['updated_at']))) ?></p>
                        <p><?= e($summary) ?></p>
                        <p><a class="button secondary" href="<?= e(base_url('index.php?route=wiki_view&slug=' . urlencode((string) $row['slug']))) ?>"><?= e((string) $t['open_page']) ?></a></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['title']);
