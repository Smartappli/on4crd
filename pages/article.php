<?php
declare(strict_types=1);


$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/article.php';
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
set_page_meta([
    'title' => (string) $row['title_localized'],
    'description' => trim((string) ($row['excerpt_localized'] ?? '')) !== '' ? (string) $row['excerpt_localized'] : (string) $t['meta_fallback'],
    'schema_type' => 'Article',
    'published_time' => !empty($row['created_at']) ? date('c', strtotime((string) $row['created_at'])) : null,
    'modified_time' => !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : null,
]);
$content = '<article class="card"><h1>' . e((string) $row['title_localized']) . '</h1>' . sanitize_rich_html((string) $row['content_localized']) . '</article>';
echo render_layout($content, (string) $row['title_localized']);
