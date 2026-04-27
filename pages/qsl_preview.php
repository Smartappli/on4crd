<?php
declare(strict_types=1);

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT title, template_name, svg_content FROM qsl_cards WHERE id = ? AND member_id = ?');
$stmt->execute([$id, (int) $user['id']]);
$row = $stmt->fetch();
if (!$row) {
    echo render_layout('<div class="card"><p>QSL introuvable.</p></div>', 'QSL');
    return;
}
$supportsBack = qsl_template_supports_back((string) ($row['template_name'] ?? 'classic'));
$frontPreviewUrl = base_url('index.php?route=qsl_export&id=' . $id);
$frontDownloadUrl = base_url('index.php?route=qsl_export&id=' . $id . '&download=1');
$content = '<div class="card qsl-preview"><h1>' . e((string) $row['title']) . ' — Recto</h1><img src="' . e($frontPreviewUrl) . '" alt="Prévisualisation QSL recto"><p><a class="button" href="' . e($frontDownloadUrl) . '">Télécharger le recto (SVG)</a></p>';
if ($supportsBack) {
    $backPreviewUrl = base_url('index.php?route=qsl_export&id=' . $id . '&side=back');
    $backDownloadUrl = base_url('index.php?route=qsl_export&id=' . $id . '&download=1&side=back');
    $content .= '<hr><h2>Verso</h2><img src="' . e($backPreviewUrl) . '" alt="Prévisualisation QSL verso"><p><a class="button secondary" href="' . e($backDownloadUrl) . '">Télécharger le verso (SVG)</a></p>';
}
$content .= '</div>';
echo render_layout($content, (string) $row['title']);
