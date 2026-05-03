<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'Page wiki introuvable.', 'edit' => 'Modifier', 'layout' => 'Wiki'],
    'en' => ['not_found' => 'Wiki page not found.', 'edit' => 'Edit', 'layout' => 'Wiki'],
    'de' => ['not_found' => 'Wiki-Seite nicht gefunden.', 'edit' => 'Bearbeiten', 'layout' => 'Wiki'],
    'nl' => ['not_found' => 'Wiki-pagina niet gevonden.', 'edit' => 'Bewerken', 'layout' => 'Wiki'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

$slug = (string) ($_GET['slug'] ?? '');
$stmt = db()->prepare('SELECT * FROM wiki_pages WHERE slug = ?');
$stmt->execute([$slug]);
$row = $stmt->fetch();

if (!$row) {
    echo render_layout('<div class="card"><p>' . e($t('not_found')) . '</p></div>', $t('layout'));
    return;
}

$actions = '';
if (has_permission('wiki.edit')) {
    $actions = '<a class="button small" href="' . e(base_url('index.php?route=wiki_edit&id=' . (int) $row['id'])) . '">' . e($t('edit')) . '</a>';
}
$content = '<article class="card"><div class="row-between"><h1>' . e((string) $row['title']) . '</h1>' . $actions . '</div>' . sanitize_rich_html((string) $row['content']) . '</article>';
echo render_layout($content, (string) $row['title']);
