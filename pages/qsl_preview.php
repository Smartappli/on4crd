<?php
declare(strict_types=1);

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);
$side = ((string) ($_GET['side'] ?? 'front')) === 'back' ? 'back' : 'front';
$stmt = db()->prepare('SELECT title, template_name, svg_content FROM qsl_cards WHERE id = ? AND member_id = ?');
$stmt->execute([$id, (int) $user['id']]);
$row = $stmt->fetch();
if (!$row) {
    echo render_layout('<div class="card"><p>QSL introuvable.</p></div>', 'QSL');
    return;
}
$supportsBack = qsl_template_supports_back((string) ($row['template_name'] ?? 'classic'));
if ($side === 'back' && !$supportsBack) {
    $side = 'front';
}
$previewUrl = base_url('index.php?route=qsl_export&id=' . $id . ($side === 'back' ? '&side=back' : ''));
$downloadUrl = base_url('index.php?route=qsl_export&id=' . $id . '&download=1' . ($side === 'back' ? '&side=back' : ''));
$switchLinks = '<p><a class="button secondary" href="' . e(base_url('index.php?route=qsl_preview&id=' . $id)) . '">Voir recto</a>';
if ($supportsBack) {
    $switchLinks .= ' <a class="button secondary" href="' . e(base_url('index.php?route=qsl_preview&id=' . $id . '&side=back')) . '">Voir verso</a>';
}
$switchLinks .= '</p>';
$content = '<div class="card qsl-preview"><h1>' . e((string) $row['title']) . ' — ' . ($side === 'back' ? 'Verso' : 'Recto') . '</h1>' . $switchLinks . '<img src="' . e($previewUrl) . '" alt="Prévisualisation QSL"><p><a class="button" href="' . e($downloadUrl) . '">Télécharger le SVG</a></p></div>';
echo render_layout($content, (string) $row['title']);
