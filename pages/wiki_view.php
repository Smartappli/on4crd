<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'Page wiki introuvable.', 'edit' => 'Modifier', 'layout' => 'Wiki', 'meta_desc' => 'Consultation d\'une page wiki.'],
    'en' => ['not_found' => 'Wiki page not found.', 'edit' => 'Edit', 'layout' => 'Wiki', 'meta_desc' => 'View a wiki page.'],
    'de' => ['not_found' => 'Wiki-Seite nicht gefunden.', 'edit' => 'Bearbeiten', 'layout' => 'Wiki', 'meta_desc' => 'Wiki-Seite anzeigen.'],
    'es' => ['not_found' => 'Página wiki no encontrada.', 'edit' => 'Editar', 'layout' => 'Wiki', 'meta_desc' => 'Ver una página wiki.'],
    'it' => ['not_found' => 'Pagina wiki non trovata.', 'edit' => 'Modifica', 'layout' => 'Wiki', 'meta_desc' => 'Visualizza una pagina wiki.'],
    'pt' => ['not_found' => 'Página wiki não encontrada.', 'edit' => 'Editar', 'layout' => 'Wiki', 'meta_desc' => 'Visualizar uma página wiki.'],
    'nl' => ['not_found' => 'Wiki-pagina niet gevonden.', 'edit' => 'Bewerken', 'layout' => 'Wiki', 'meta_desc' => 'Een wiki-pagina bekijken.'],
    'ar' => ['not_found' => 'صفحة الويكي غير موجودة.', 'edit' => 'تعديل', 'layout' => 'ويكي', 'meta_desc' => 'عرض صفحة ويكي.'],
    'hi' => ['not_found' => 'विकी पृष्ठ नहीं मिला।', 'edit' => 'संपादित करें', 'layout' => 'विकी', 'meta_desc' => 'एक विकी पृष्ठ देखें।'],
    'ja' => ['not_found' => 'Wikiページが見つかりません。', 'edit' => '編集', 'layout' => 'Wiki', 'meta_desc' => 'Wikiページを表示します。'],
    'zh' => ['not_found' => '未找到 Wiki 页面。', 'edit' => '编辑', 'layout' => 'Wiki', 'meta_desc' => '查看 Wiki 页面。'],
    'bn' => ['not_found' => 'উইকি পৃষ্ঠা পাওয়া যায়নি।', 'edit' => 'সম্পাদনা', 'layout' => 'উইকি', 'meta_desc' => 'একটি উইকি পৃষ্ঠা দেখুন।'],
    'ru' => ['not_found' => 'Страница wiki не найдена.', 'edit' => 'Редактировать', 'layout' => 'Wiki', 'meta_desc' => 'Просмотр страницы wiki.'],
    'id' => ['not_found' => 'Halaman wiki tidak ditemukan.', 'edit' => 'Ubah', 'layout' => 'Wiki', 'meta_desc' => 'Lihat halaman wiki.'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};


set_page_meta([
    'title' => $t('layout'),
    'description' => $t('meta_desc'),
    'robots' => 'index,follow',
]);

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
