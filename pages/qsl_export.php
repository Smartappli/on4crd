<?php
declare(strict_types=1);

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT title, svg_content FROM qsl_cards WHERE id = ? AND member_id = ?');
$stmt->execute([$id, (int) $user['id']]);
$row = $stmt->fetch();
if (!$row) {
    http_response_code(404);
    exit('QSL introuvable');
}
$filename = slugify((string) ($row['title'] ?? 'qsl-card')) ?: 'qsl-card';
header('Content-Type: image/svg+xml; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.svg"');
echo sanitize_svg_document((string) $row['svg_content']);
