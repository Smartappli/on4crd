<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/qsl_preview.php';
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
