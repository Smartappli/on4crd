<?php
declare(strict_types=1);


$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'Article introuvable.', 'layout_article' => 'Article', 'meta_fallback' => 'Article technique ON4CRD'],
    'en' => ['not_found' => 'Article not found.', 'layout_article' => 'Article', 'meta_fallback' => 'ON4CRD technical article'],
    'de' => ['not_found' => 'Artikel nicht gefunden.', 'layout_article' => 'Artikel', 'meta_fallback' => 'Technischer ON4CRD-Artikel'],
    'nl' => ['not_found' => 'Artikel niet gevonden.', 'layout_article' => 'Artikel', 'meta_fallback' => 'Technisch ON4CRD-artikel'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

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
