<?php
declare(strict_types=1);

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT title, svg_content FROM qsl_cards WHERE id = ? AND member_id = ?');
$stmt->execute([$id, (int) $user['id']]);
$row = $stmt->fetch();
if (!$row) {
    echo render_layout('<div class="card"><p>QSL introuvable.</p></div>', 'QSL');
    return;
}
$previewUrl = base_url('index.php?route=qsl_export&id=' . $id);
$downloadUrl = base_url('index.php?route=qsl_export&id=' . $id . '&download=1');
$content = '<div class="card qsl-preview"><h1>' . e((string) $row['title']) . '</h1><img src="' . e($previewUrl) . '" alt="Prévisualisation QSL"><p><a class="button" href="' . e($downloadUrl) . '">Télécharger le SVG</a></p></div>';
echo render_layout($content, (string) $row['title']);
