<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'QSL introuvable.', 'layout_title' => 'QSL', 'front' => 'Recto', 'front_alt' => 'Prévisualisation QSL recto', 'front_download' => 'Télécharger le recto (SVG)', 'back' => 'Verso', 'back_alt' => 'Prévisualisation QSL verso', 'back_download' => 'Télécharger le verso (SVG)'],
    'en' => ['not_found' => 'QSL not found.', 'layout_title' => 'QSL', 'front' => 'Front', 'front_alt' => 'QSL front preview', 'front_download' => 'Download front (SVG)', 'back' => 'Back', 'back_alt' => 'QSL back preview', 'back_download' => 'Download back (SVG)'],
    'de' => ['not_found' => 'QSL nicht gefunden.', 'layout_title' => 'QSL', 'front' => 'Vorderseite', 'front_alt' => 'QSL-Vorderseitenvorschau', 'front_download' => 'Vorderseite herunterladen (SVG)', 'back' => 'Rückseite', 'back_alt' => 'QSL-Rückseitenvorschau', 'back_download' => 'Rückseite herunterladen (SVG)'],
    'nl' => ['not_found' => 'QSL niet gevonden.', 'layout_title' => 'QSL', 'front' => 'Voorzijde', 'front_alt' => 'QSL-voorvertoning voorkant', 'front_download' => 'Voorzijde downloaden (SVG)', 'back' => 'Achterzijde', 'back_alt' => 'QSL-voorvertoning achterkant', 'back_download' => 'Achterzijde downloaden (SVG)'],
];
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
