<?php
declare(strict_types=1);


$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'Article introuvable.', 'layout_article' => 'Article', 'meta_fallback' => 'Article technique ON4CRD'],
    'en' => ['not_found' => 'Article not found.', 'layout_article' => 'Article', 'meta_fallback' => 'ON4CRD technical article'],
    'de' => ['not_found' => 'Artikel nicht gefunden.', 'layout_article' => 'Artikel', 'meta_fallback' => 'Technischer ON4CRD-Artikel'],
    'es' => ['not_found' => 'Artículo no encontrado.', 'layout_article' => 'Artículo', 'meta_fallback' => 'Artículo técnico ON4CRD'],
    'it' => ['not_found' => 'Articolo non trovato.', 'layout_article' => 'Articolo', 'meta_fallback' => 'Articolo tecnico ON4CRD'],
    'pt' => ['not_found' => 'Artigo não encontrado.', 'layout_article' => 'Artigo', 'meta_fallback' => 'Artigo técnico ON4CRD'],
    'nl' => ['not_found' => 'Artikel niet gevonden.', 'layout_article' => 'Artikel', 'meta_fallback' => 'Technisch ON4CRD-artikel'],
    'ar' => ['not_found' => 'المقال غير موجود.', 'layout_article' => 'مقال', 'meta_fallback' => 'مقال تقني ON4CRD'],
    'hi' => ['not_found' => 'लेख नहीं मिला।', 'layout_article' => 'लेख', 'meta_fallback' => 'ON4CRD तकनीकी लेख'],
    'ja' => ['not_found' => '記事が見つかりません。', 'layout_article' => '記事', 'meta_fallback' => 'ON4CRD 技術記事'],
    'zh' => ['not_found' => '未找到文章。', 'layout_article' => '文章', 'meta_fallback' => 'ON4CRD 技术文章'],
    'bn' => ['not_found' => 'প্রবন্ধ পাওয়া যায়নি।', 'layout_article' => 'প্রবন্ধ', 'meta_fallback' => 'ON4CRD প্রযুক্তিগত প্রবন্ধ'],
    'ru' => ['not_found' => 'Статья не найдена.', 'layout_article' => 'Статья', 'meta_fallback' => 'Техническая статья ON4CRD'],
    'id' => ['not_found' => 'Artikel tidak ditemukan.', 'layout_article' => 'Artikel', 'meta_fallback' => 'Artikel teknis ON4CRD'],
];
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
