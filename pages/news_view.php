<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/news_view.php');
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

$stmt = db()->prepare('SELECT id, title, excerpt, content, published_at, updated_at FROM news_posts WHERE slug = ? AND status = "published" LIMIT 1');
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!is_array($post)) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['not_found']) . '</h1><p>' . e((string) $t['not_found_msg']) . '</p></div>', (string) $t['not_found']);
    return;
}
$post = localized_news_row($post);

$publishedAtRaw = (string) ($post['published_at'] ?? $post['updated_at'] ?? '');
$publishedAt = $publishedAtRaw !== '' ? date('d/m/Y H:i', strtotime($publishedAtRaw)) : (string) $t['date_unknown'];
$excerpt = trim((string) ($post['excerpt'] ?? ''));
$content = trim((string) ($post['content'] ?? ''));
$newsDescription = $excerpt !== '' ? $excerpt : trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
if ($newsDescription === '') {
    $newsDescription = (string) $t['content_soon'];
}
$newsDescription = mb_substr($newsDescription, 0, 220);
$newsUrl = route_url_with_locale('news_view', $locale, ['slug' => $slug]);
$newsPublishedAt = $publishedAtRaw !== '' ? date('c', strtotime($publishedAtRaw)) : null;
$newsModifiedAt = !empty($post['updated_at']) ? date('c', strtotime((string) $post['updated_at'])) : $newsPublishedAt;

set_page_meta([
    'title' => (string) $post['title'],
    'description' => $newsDescription,
    'ai_summary' => $newsDescription,
    'canonical' => $newsUrl,
    'og_type' => 'article',
    'schema_type' => 'NewsArticle',
    'published_time' => $newsPublishedAt,
    'modified_time' => $newsModifiedAt,
    'section' => (string) ($t['published_on'] ?? 'Actualite'),
    'tags' => ['ON4CRD', 'Radio Club Durnal', 'actualite radioamateur'],
    'keywords' => ['ON4CRD', 'Radio Club Durnal', 'actualite radioamateur', 'Belgique', 'Namur'],
    'citation_author' => 'Radio Club Durnal ON4CRD',
    'json_ld' => [
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => (string) $post['title'],
        'description' => $newsDescription,
        'abstract' => $newsDescription,
        'url' => $newsUrl,
        'datePublished' => $newsPublishedAt,
        'dateModified' => $newsModifiedAt,
        'inLanguage' => $locale,
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Radio Club Durnal ON4CRD',
            'url' => route_url_with_locale('home', $locale),
        ],
        'author' => [
            '@type' => 'Organization',
            'name' => 'Radio Club Durnal ON4CRD',
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $newsUrl,
        ],
    ],
]);

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
            <?= sanitize_rich_html($content) ?>
        </section>
    <?php else: ?>
        <p><?= e((string) $t['content_soon']) ?></p>
    <?php endif; ?>
</article>
<?php
echo render_layout((string) ob_get_clean(), (string) $post['title']);
