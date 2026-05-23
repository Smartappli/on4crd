<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'QSL introuvable.', 'layout_title' => 'QSL', 'front' => 'Recto', 'front_alt' => 'Prévisualisation QSL recto', 'front_download' => 'Télécharger le recto (SVG)', 'back' => 'Verso', 'back_alt' => 'Prévisualisation QSL verso', 'back_download' => 'Télécharger le verso (SVG)'],
    'en' => ['not_found' => 'QSL not found.', 'layout_title' => 'QSL', 'front' => 'Front', 'front_alt' => 'QSL front preview', 'front_download' => 'Download front (SVG)', 'back' => 'Back', 'back_alt' => 'QSL back preview', 'back_download' => 'Download back (SVG)'],
    'de' => ['not_found' => 'QSL nicht gefunden.', 'layout_title' => 'QSL', 'front' => 'Vorderseite', 'front_alt' => 'QSL-Vorderseitenvorschau', 'front_download' => 'Vorderseite herunterladen (SVG)', 'back' => 'Rückseite', 'back_alt' => 'QSL-Rückseitenvorschau', 'back_download' => 'Rückseite herunterladen (SVG)'],
    'es' => ['not_found' => 'QSL no encontrada.', 'layout_title' => 'QSL', 'front' => 'Anverso', 'front_alt' => 'Vista previa anverso QSL', 'front_download' => 'Descargar anverso (SVG)', 'back' => 'Reverso', 'back_alt' => 'Vista previa reverso QSL', 'back_download' => 'Descargar reverso (SVG)'],
    'it' => ['not_found' => 'QSL non trovata.', 'layout_title' => 'QSL', 'front' => 'Fronte', 'front_alt' => 'Anteprima fronte QSL', 'front_download' => 'Scarica fronte (SVG)', 'back' => 'Retro', 'back_alt' => 'Anteprima retro QSL', 'back_download' => 'Scarica retro (SVG)'],
    'pt' => ['not_found' => 'QSL não encontrada.', 'layout_title' => 'QSL', 'front' => 'Frente', 'front_alt' => 'Pré-visualização frente QSL', 'front_download' => 'Descarregar frente (SVG)', 'back' => 'Verso', 'back_alt' => 'Pré-visualização verso QSL', 'back_download' => 'Descarregar verso (SVG)'],
    'nl' => ['not_found' => 'QSL niet gevonden.', 'layout_title' => 'QSL', 'front' => 'Voorzijde', 'front_alt' => 'QSL-voorvertoning voorkant', 'front_download' => 'Voorzijde downloaden (SVG)', 'back' => 'Achterzijde', 'back_alt' => 'QSL-voorvertoning achterkant', 'back_download' => 'Achterzijde downloaden (SVG)'],
    'ar' => ['not_found' => 'لم يتم العثور على QSL.', 'layout_title' => 'QSL', 'front' => 'الوجه الأمامي', 'front_alt' => 'معاينة الوجه الأمامي لـ QSL', 'front_download' => 'تنزيل الوجه الأمامي (SVG)', 'back' => 'الوجه الخلفي', 'back_alt' => 'معاينة الوجه الخلفي لـ QSL', 'back_download' => 'تنزيل الوجه الخلفي (SVG)'],
    'bn' => ['not_found' => 'QSL পাওয়া যায়নি।', 'layout_title' => 'QSL', 'front' => 'সামনের দিক', 'front_alt' => 'QSL সামনের প্রিভিউ', 'front_download' => 'সামনের দিক ডাউনলোড করুন (SVG)', 'back' => 'পেছনের দিক', 'back_alt' => 'QSL পেছনের প্রিভিউ', 'back_download' => 'পেছনের দিক ডাউনলোড করুন (SVG)'],
    'hi' => ['not_found' => 'QSL नहीं मिला।', 'layout_title' => 'QSL', 'front' => 'सामने', 'front_alt' => 'QSL सामने का पूर्वावलोकन', 'front_download' => 'सामने डाउनलोड करें (SVG)', 'back' => 'पीछे', 'back_alt' => 'QSL पीछे का पूर्वावलोकन', 'back_download' => 'पीछे डाउनलोड करें (SVG)'],
    'id' => ['not_found' => 'QSL tidak ditemukan.', 'layout_title' => 'QSL', 'front' => 'Depan', 'front_alt' => 'Pratinjau depan QSL', 'front_download' => 'Unduh depan (SVG)', 'back' => 'Belakang', 'back_alt' => 'Pratinjau belakang QSL', 'back_download' => 'Unduh belakang (SVG)'],
    'ja' => ['not_found' => 'QSLが見つかりません。', 'layout_title' => 'QSL', 'front' => '表面', 'front_alt' => 'QSL表面プレビュー', 'front_download' => '表面をダウンロード (SVG)', 'back' => '裏面', 'back_alt' => 'QSL裏面プレビュー', 'back_download' => '裏面をダウンロード (SVG)'],
    'ru' => ['not_found' => 'QSL не найдена.', 'layout_title' => 'QSL', 'front' => 'Лицевая сторона', 'front_alt' => 'Предпросмотр лицевой стороны QSL', 'front_download' => 'Скачать лицевую сторону (SVG)', 'back' => 'Оборотная сторона', 'back_alt' => 'Предпросмотр оборотной стороны QSL', 'back_download' => 'Скачать оборотную сторону (SVG)'],
    'zh' => ['not_found' => '未找到 QSL。', 'layout_title' => 'QSL', 'front' => '正面', 'front_alt' => 'QSL 正面预览', 'front_download' => '下载正面 (SVG)', 'back' => '背面', 'back_alt' => 'QSL 背面预览', 'back_download' => '下载背面 (SVG)'],
];
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT title, template_name, svg_content FROM qsl_cards WHERE id = ? AND member_id = ?');
$stmt->execute([$id, (int) $user['id']]);
$row = $stmt->fetch();
if (!$row) {
    echo render_layout('<div class="card"><p>' . e($t('not_found')) . '</p></div>', $t('layout_title'));
    return;
}
$supportsBack = qsl_template_supports_back((string) ($row['template_name'] ?? 'classic'));
$frontPreviewUrl = base_url('index.php?route=qsl_export&id=' . $id);
$frontDownloadUrl = base_url('index.php?route=qsl_export&id=' . $id . '&download=1');
$content = '<div class="card qsl-preview"><h1>' . e((string) $row['title']) . ' — ' . e($t('front')) . '</h1><img src="' . e($frontPreviewUrl) . '" alt="' . e($t('front_alt')) . '"><p><a class="button" href="' . e($frontDownloadUrl) . '">' . e($t('front_download')) . '</a></p>';
if ($supportsBack) {
    $backPreviewUrl = base_url('index.php?route=qsl_export&id=' . $id . '&side=back');
    $backDownloadUrl = base_url('index.php?route=qsl_export&id=' . $id . '&download=1&side=back');
    $content .= '<hr><h2>' . e($t('back')) . '</h2><img src="' . e($backPreviewUrl) . '" alt="' . e($t('back_alt')) . '"><p><a class="button secondary" href="' . e($backDownloadUrl) . '">' . e($t('back_download')) . '</a></p>';
}
$content .= '</div>';
echo render_layout($content, (string) $row['title']);
