<?php
declare(strict_types=1);

function article_view_plain_text(string $html): string
{
    return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

function article_view_reading_minutes(string $html): int
{
    return max(1, (int) ceil(str_word_count(article_view_plain_text($html)) / 220));
}


$locale = current_locale();
$articleMessages = i18n_domain_locale('articles', $locale);
article_ensure_taxonomy_schema($articleMessages);
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/article.php');
articles_sync_scheduled_publications();
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

$slug = (string) ($_GET['slug'] ?? '');
if ($slug === '' || !table_exists('articles')) {
    http_response_code(404);
    echo render_layout('<div class="card"><p>' . e((string) $t['not_found']) . '</p></div>', (string) $t['layout_article']);
    return;
}

$stmt = db()->prepare('SELECT * FROM articles WHERE slug = ? AND status = "published"');
$stmt->execute([$slug]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo render_layout('<div class="card"><p>' . e((string) $t['not_found']) . '</p></div>', (string) $t['layout_article']);
    return;
}

$row = localized_article_row($row);
$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'toggle_favorite') {
    $user = require_login();
    verify_csrf();
    $saved = favorite_toggle(
        (int) $user['id'],
        'article',
        (int) $row['id'],
        (string) ($row['title_localized'] ?? $row['title'] ?? ''),
        route_url('article', ['slug' => (string) $row['slug']])
    );
    notify_member((int) $user['id'], 'favorite', $saved ? (string) $t['favorite_added'] : (string) $t['favorite_removed'], (string) ($row['title_localized'] ?? $row['title'] ?? ''), route_url('article', ['slug' => (string) $row['slug']]));
    set_flash('success', $saved ? (string) $t['favorite_added_msg'] : (string) $t['favorite_removed_msg']);
    redirect_url(route_url('article', ['slug' => (string) $row['slug']]));
}
$isFavorite = $user !== null ? favorite_is_saved((int) $user['id'], 'article', (int) $row['id']) : false;

$articleCategories = article_categories($articleMessages);
$articleSubcategoriesByCategory = article_subcategories_by_category();
$articleSubsubcategoriesByParent = article_subsubcategories_by_parent();
$category = article_category_code((string) ($row['category'] ?? 'autres'));
$categoryLabel = (string) ($articleCategories[$category] ?? article_category_label_from_code($category));
$subcategory = article_subcategory_code((string) ($row['subcategory'] ?? ''));
$subcategoryLabel = '';
foreach ($articleSubcategoriesByCategory[$category] ?? [] as $subcategoryInfo) {
    if (article_subcategory_code((string) ($subcategoryInfo['code'] ?? '')) === $subcategory) {
        $subcategoryLabel = (string) ($subcategoryInfo['label'] ?? $subcategory);
        break;
    }
}
$subsubcategory = article_subsubcategory_code((string) ($row['subsubcategory'] ?? ''));
$subsubcategoryLabel = '';
foreach ($articleSubsubcategoriesByParent[$category . ':' . $subcategory] ?? [] as $subsubcategoryInfo) {
    if (article_subsubcategory_code((string) ($subsubcategoryInfo['code'] ?? '')) === $subsubcategory) {
        $subsubcategoryLabel = (string) ($subsubcategoryInfo['label'] ?? $subsubcategory);
        break;
    }
}
$readingMinutes = article_view_reading_minutes((string) ($row['content_localized'] ?? $row['content'] ?? ''));
$articlePlainText = article_view_plain_text((string) ($row['content_localized'] ?? $row['content'] ?? ''));
$articleExcerpt = article_excerpt_from_input((string) ($row['excerpt_localized'] ?? ''));
$articleDescription = $articleExcerpt !== '' ? $articleExcerpt : (string) $t['meta_fallback'];
$articleUrl = route_url_with_locale('article', $locale, ['slug' => (string) $row['slug']]);
$articlePublishedRaw = article_publication_datetime($row);
$articlePublishedAt = $articlePublishedRaw !== null ? date('c', strtotime($articlePublishedRaw)) : null;
$articleModifiedAt = !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : null;
$articleDisplayDate = $articlePublishedRaw !== null ? date('d/m/Y', strtotime($articlePublishedRaw)) : '';
set_page_meta([
    'title' => (string) $row['title_localized'],
    'description' => $articleDescription,
    'ai_summary' => mb_safe_strimwidth($articlePlainText !== '' ? $articlePlainText : $articleDescription, 0, 280, '...'),
    'canonical' => $articleUrl,
    'og_type' => 'article',
    'schema_type' => 'Article',
    'published_time' => $articlePublishedAt,
    'modified_time' => $articleModifiedAt,
    'section' => $categoryLabel,
    'tags' => array_filter([$categoryLabel, $subcategoryLabel, $subsubcategoryLabel, 'radioamateur', 'ON4CRD']),
    'keywords' => array_filter([$categoryLabel, $subcategoryLabel, $subsubcategoryLabel, 'radioamateur', 'article technique', 'Radio Club Durnal', 'ON4CRD']),
    'citation_author' => 'Radio Club Durnal ON4CRD',
    'json_ld' => [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => (string) $row['title_localized'],
        'description' => $articleDescription,
        'abstract' => mb_safe_strimwidth($articlePlainText !== '' ? $articlePlainText : $articleDescription, 0, 280, '...'),
        'url' => $articleUrl,
        'datePublished' => $articlePublishedAt,
        'dateModified' => $articleModifiedAt,
        'articleSection' => $categoryLabel,
        'wordCount' => str_word_count($articlePlainText),
        'inLanguage' => $locale,
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => (string) config('app.site_name', 'ON4CRD'),
            'url' => route_url_with_locale('home', $locale),
        ],
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
            '@id' => $articleUrl,
        ],
    ],
]);
$articlePublicationSort = article_publication_sort_expression();
$articleOrderDate = $articlePublishedRaw ?? (string) ($row['updated_at'] ?? '');
$relatedStmt = db()->prepare('SELECT id, slug, title, excerpt, content, published_at, created_at, updated_at FROM articles WHERE status = "published" AND category = ? AND id <> ? ORDER BY ' . $articlePublicationSort . ' DESC, id DESC LIMIT 3');
$relatedStmt->execute([$category, (int) $row['id']]);
$relatedRows = $relatedStmt->fetchAll() ?: [];

$previousStmt = db()->prepare('SELECT slug, title FROM articles WHERE status = "published" AND ' . $articlePublicationSort . ' > ? AND id <> ? ORDER BY ' . $articlePublicationSort . ' ASC, id ASC LIMIT 1');
$previousStmt->execute([$articleOrderDate, (int) $row['id']]);
$previous = $previousStmt->fetch() ?: null;

$nextStmt = db()->prepare('SELECT slug, title FROM articles WHERE status = "published" AND ' . $articlePublicationSort . ' < ? AND id <> ? ORDER BY ' . $articlePublicationSort . ' DESC, id DESC LIMIT 1');
$nextStmt->execute([$articleOrderDate, (int) $row['id']]);
$next = $nextStmt->fetch() ?: null;

ob_start();
?>
<article class="card article-view">
    <p><a class="pill" href="<?= e(route_url('articles', ['theme' => $category, 'subcategory' => $subcategory, 'subsubcategory' => $subsubcategory])) ?>"><?= e((string) $t['back_to_articles']) ?></a></p>
    <?php if ($user !== null): ?>
        <form method="post" class="inline-form" style="margin-bottom:.7rem;">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="toggle_favorite">
            <button class="button secondary" type="submit"><?= $isFavorite ? '&#9733; ' . e((string) $t['favorite_label']) : '&#9734; ' . e((string) $t['favorite_label']) ?></button>
        </form>
    <?php endif; ?>
    <h1><?= e((string) $row['title_localized']) ?></h1>
    <p class="help taxonomy-badge-row">
        <a class="badge muted taxonomy-pill-category" href="<?= e(route_url_clean('articles', ['theme' => $category])) ?>"><?= e($categoryLabel) ?></a>
        <?php if ($subcategoryLabel !== ''): ?><a class="badge muted taxonomy-pill-subcategory" href="<?= e(route_url_clean('articles', ['theme' => $category, 'subcategory' => $subcategory])) ?>"><?= e($subcategoryLabel) ?></a><?php endif; ?>
        <?php if ($subsubcategoryLabel !== ''): ?><a class="badge muted taxonomy-pill-subsubcategory" href="<?= e(route_url_clean('articles', ['theme' => $category, 'subcategory' => $subcategory, 'subsubcategory' => $subsubcategory])) ?>"><?= e($subsubcategoryLabel) ?></a><?php endif; ?>
        <span><?= e($articleDisplayDate) ?></span>
        <span><?= $readingMinutes ?> <?= e((string) $t['reading_minutes']) ?></span>
    </p>
    <?php if ($articleExcerpt !== ''): ?>
        <p class="lead"><?= e($articleExcerpt) ?></p>
    <?php endif; ?>
    <div class="article-content">
        <?= article_sanitize_content((string) $row['content_localized']) ?>
    </div>
</article>

<?php if ($previous !== null || $next !== null): ?>
<section class="card">
    <div class="row-between">
        <div><?php if ($previous !== null): ?><a class="button secondary" href="<?= e(route_url('article', ['slug' => (string) $previous['slug']])) ?>"><?= e((string) $t['previous_article']) ?></a><?php endif; ?></div>
        <div><?php if ($next !== null): ?><a class="button secondary" href="<?= e(route_url('article', ['slug' => (string) $next['slug']])) ?>"><?= e((string) $t['next_article']) ?></a><?php endif; ?></div>
    </div>
</section>
<?php endif; ?>

<?php if ($relatedRows !== []): ?>
<section class="card">
    <h2><?= e((string) $t['related_articles']) ?></h2>
    <div class="news-grid">
        <?php foreach ($relatedRows as $related): ?>
            <?php $related = localized_article_row($related); ?>
            <?php $relatedExcerpt = article_excerpt_from_input((string) ($related['excerpt_localized'] ?? '')); ?>
            <article class="feature-card">
                <h3><a href="<?= e(route_url('article', ['slug' => (string) $related['slug']])) ?>"><?= e((string) $related['title_localized']) ?></a></h3>
                <?php if ($relatedExcerpt !== ''): ?>
                    <p><?= e($relatedExcerpt) ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
echo render_layout($content, (string) $row['title_localized']);
