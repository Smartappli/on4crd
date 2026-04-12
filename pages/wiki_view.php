<?php
declare(strict_types=1);

$slug = (string) ($_GET['slug'] ?? '');
$stmt = db()->prepare('SELECT * FROM wiki_pages WHERE slug = ?');
$stmt->execute([$slug]);
$row = $stmt->fetch();

if (!$row) {
    echo render_layout('<div class="card"><p>Page wiki introuvable.</p></div>', 'Wiki');
    return;
}

$actions = '';
if (has_permission('wiki.edit')) {
    $actions = '<a class="button small" href="' . e(base_url('index.php?route=wiki_edit&id=' . (int) $row['id'])) . '">Modifier</a>';
}
$content = '<article class="card"><div class="row-between"><h1>' . e((string) $row['title']) . '</h1>' . $actions . '</div>' . sanitize_rich_html((string) $row['content']) . '</article>';
echo render_layout($content, (string) $row['title']);
