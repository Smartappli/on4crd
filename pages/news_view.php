<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/news_view.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $pool = [];
    foreach ($i18n as $lang => $translations) {
        if (isset($translations[$key]) && is_string($translations[$key])) {
            $pool[$lang] = $translations[$key];
        }
    }
    $t[$key] = i18n_localized_value($pool, $locale, 'fr');
}

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '' || !table_exists('news_posts')) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['not_found']) . '</h1><p>' . e((string) $t['not_found_msg']) . '</p></div>', (string) $t['not_found']);
    return;
}

$stmt = db()->prepare('SELECT title, excerpt, content, published_at, updated_at FROM news_posts WHERE slug = ? AND status = "published" LIMIT 1');
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!is_array($post)) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['not_found']) . '</h1><p>' . e((string) $t['not_found_msg']) . '</p></div>', (string) $t['not_found']);
    return;
}

$publishedAtRaw = (string) ($post['published_at'] ?? $post['updated_at'] ?? '');
$publishedAt = $publishedAtRaw !== '' ? date('d/m/Y H:i', strtotime($publishedAtRaw)) : (string) $t['date_unknown'];
$excerpt = trim((string) ($post['excerpt'] ?? ''));
$content = trim((string) ($post['content'] ?? ''));

ob_start();
?>
<article class="card">
    <p><a href="<?= e(route_url('news')) ?>"><?= e((string) $t['back']) ?></a></p>
    <h1><?= e((string) $post['title']) ?></h1>
    <p class="help"><?= e((string) $t['published_on']) ?> <?= e($publishedAt) ?></p>

    <?php if ($excerpt !== ''): ?>
        <p><strong><?= e($excerpt) ?></strong></p>
    <?php endif; ?>

    <?php if ($content !== ''): ?>
        <section class="inner-card">
            <?= $content ?>
        </section>
    <?php else: ?>
        <p><?= e((string) $t['content_soon']) ?></p>
    <?php endif; ?>
</article>
<?php
echo render_layout((string) ob_get_clean(), (string) $post['title']);
