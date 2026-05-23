<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/wiki_view.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

set_page_meta([
    'title' => $t('layout'),
    'description' => $t('meta_desc'),
    'robots' => 'index,follow',
]);

if (!ensure_wiki_tables()) {
    echo render_layout('<div class="card"><p>' . e($t('not_found')) . '</p></div>', $t('layout'));
    return;
}

$slug = trim((string) ($_GET['slug'] ?? ''));
$stmt = db()->prepare(
    'SELECT p.*, m.callsign
     FROM wiki_pages p
     LEFT JOIN members m ON m.id = p.author_id
     WHERE p.slug = ?
     LIMIT 1'
);
$stmt->execute([$slug]);
$row = $stmt->fetch();

if (!$row) {
    echo render_layout('<div class="card"><p>' . e($t('not_found')) . '</p></div>', $t('layout'));
    return;
}

$revisionStmt = db()->prepare(
    'SELECT r.id, r.created_at, m.callsign
     FROM wiki_revisions r
     LEFT JOIN members m ON m.id = r.member_id
     WHERE r.wiki_page_id = ?
     ORDER BY r.created_at DESC, r.id DESC
     LIMIT 10'
);
$revisionStmt->execute([(int) $row['id']]);
$revisions = $revisionStmt->fetchAll() ?: [];

$author = trim((string) ($row['callsign'] ?? ''));
$updatedAt = strtotime((string) ($row['updated_at'] ?? '')) ?: time();

ob_start();
?>
<div class="wiki-view-page">
    <section class="wiki-view-hero">
        <div>
            <p class="eyebrow">/<?= e((string) $row['slug']) ?></p>
            <h1><?= e((string) $row['title']) ?></h1>
            <p class="help">
                <?= e(date('d/m/Y H:i', $updatedAt)) ?>
                <?php if ($author !== ''): ?> · <?= e($author) ?><?php endif; ?>
            </p>
        </div>
        <div class="wiki-view-actions">
            <a class="button secondary" href="<?= e(route_url('wiki')) ?>"><?= e($t('layout')) ?></a>
            <?php if (has_permission('wiki.edit')): ?>
                <a class="button" href="<?= e(route_url('wiki_edit', ['id' => (int) $row['id']])) ?>"><?= e($t('edit')) ?></a>
            <?php endif; ?>
        </div>
    </section>

    <div class="wiki-view-layout">
        <article class="wiki-article">
            <?= sanitize_rich_html((string) $row['content']) ?>
        </article>

        <aside class="wiki-history-panel">
            <h2><?= e($t('history')) ?></h2>
            <?php if ($revisions === []): ?>
                <p class="help"><?= e($t('no_revisions')) ?></p>
            <?php else: ?>
                <ol>
                    <?php foreach ($revisions as $revision):
                        $revisionAuthor = trim((string) ($revision['callsign'] ?? ''));
                        $revisionDate = strtotime((string) ($revision['created_at'] ?? '')) ?: time();
                        ?>
                        <li>
                            <strong><?= e(date('d/m/Y H:i', $revisionDate)) ?></strong>
                            <?php if ($revisionAuthor !== ''): ?><span><?= e($revisionAuthor) ?></span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </aside>
    </div>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $row['title']);
