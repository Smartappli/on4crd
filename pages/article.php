<?php
declare(strict_types=1);

$slug = (string) ($_GET['slug'] ?? '');
$stmt = db()->prepare('SELECT * FROM articles WHERE slug = ? AND status = "published"');
$stmt->execute([$slug]);
$row = $stmt->fetch();

if (!$row) {
    echo render_layout('<div class="card"><p>Article introuvable.</p></div>', 'Article');
    return;
}

$row = localized_article_row($row);
set_page_meta([
    'title' => (string) $row['title_localized'],
    'description' => trim((string) ($row['excerpt_localized'] ?? '')) !== '' ? (string) $row['excerpt_localized'] : 'Article technique ON4CRD',
    'schema_type' => 'Article',
    'published_time' => !empty($row['created_at']) ? date('c', strtotime((string) $row['created_at'])) : null,
    'modified_time' => !empty($row['updated_at']) ? date('c', strtotime((string) $row['updated_at'])) : null,
]);
$content = '<article class="card"><h1>' . e((string) $row['title_localized']) . '</h1>' . sanitize_rich_html((string) $row['content_localized']) . '</article>';
echo render_layout($content, (string) $row['title_localized']);
