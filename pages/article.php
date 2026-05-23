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
$i18n = require __DIR__ . '/../app/i18n/article.php';
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
$stmt = db()->prepare('SELECT * FROM articles WHERE slug = ? AND status = "published"');
$stmt->execute([$slug]);
$row = $stmt->fetch();

if (!$row) {
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

$category = slugify((string) ($row['category'] ?? 'autres'));
$categoryLabel = ucwords(str_replace('-', ' ', $category));
$readingMinutes = article_view_reading_minutes((string) ($row['content_localized'] ?? $row['content'] ?? ''));
set_page_meta([
    'title' => (string) $row['title_localized'],
    'description' => trim((string) ($row['excerpt_localized'] ?? '')) !== '' ? (string) $row['excerpt_localized'] : (string) $t['meta_fallback'],
    'schema_type' => 'Article',
    'published_time' => !empty($row['created_at']) ? date('c', strtotime((string) $row['created_at'])) : null,
    'modified_time' => !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : null,
]);
$relatedStmt = db()->prepare('SELECT id, slug, title, excerpt, content, updated_at FROM articles WHERE status = "published" AND category = ? AND id <> ? ORDER BY updated_at DESC, id DESC LIMIT 3');
$relatedStmt->execute([$category, (int) $row['id']]);
$relatedRows = $relatedStmt->fetchAll() ?: [];

$previousStmt = db()->prepare('SELECT slug, title FROM articles WHERE status = "published" AND updated_at > ? AND id <> ? ORDER BY updated_at ASC, id ASC LIMIT 1');
$previousStmt->execute([(string) $row['updated_at'], (int) $row['id']]);
$previous = $previousStmt->fetch() ?: null;

$nextStmt = db()->prepare('SELECT slug, title FROM articles WHERE status = "published" AND updated_at < ? AND id <> ? ORDER BY updated_at DESC, id DESC LIMIT 1');
$nextStmt->execute([(string) $row['updated_at'], (int) $row['id']]);
$next = $nextStmt->fetch() ?: null;

ob_start();
?>
<article class="card article-view">
    <p><a class="pill" href="<?= e(route_url('articles', ['theme' => $category])) ?>"><?= e((string) $t['back_to_articles']) ?></a></p>
    <?php if ($user !== null): ?>
        <form method="post" class="inline-form" style="margin-bottom:.7rem;">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="toggle_favorite">
            <button class="button secondary" type="submit"><?= $isFavorite ? '&#9733; ' . e((string) $t['favorite_label']) : '&#9734; ' . e((string) $t['favorite_label']) ?></button>
        </form>
    <?php endif; ?>
    <h1><?= e((string) $row['title_localized']) ?></h1>
    <p class="help">
        <?= e($categoryLabel) ?> ·
        <?= e(date('d/m/Y', strtotime((string) $row['updated_at']))) ?> ·
        <?= $readingMinutes ?> <?= e((string) $t['reading_minutes']) ?>
    </p>
    <?php if (trim((string) ($row['excerpt_localized'] ?? '')) !== ''): ?>
        <p class="lead"><?= e((string) $row['excerpt_localized']) ?></p>
    <?php endif; ?>
    <div class="article-content">
        <?= sanitize_rich_html((string) $row['content_localized']) ?>
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
            <article class="feature-card">
                <h3><a href="<?= e(route_url('article', ['slug' => (string) $related['slug']])) ?>"><?= e((string) $related['title_localized']) ?></a></h3>
                <p><?= e(trim((string) ($related['excerpt_localized'] ?? '')) !== '' ? (string) $related['excerpt_localized'] : mb_substr(article_view_plain_text((string) ($related['content_localized'] ?? '')), 0, 140)) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
echo render_layout($content, (string) $row['title_localized']);
