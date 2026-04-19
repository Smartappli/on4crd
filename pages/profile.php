<?php
declare(strict_types=1);

$user = require_login();

$content = '<div class="card"><h1>Profil</h1>'
    . '<p><strong>Indicatif:</strong> ' . e((string) ($user['callsign'] ?? '')) . '</p>'
    . '<p><strong>Nom:</strong> ' . e((string) ($user['full_name'] ?? '')) . '</p>'
    . '<p><strong>Email:</strong> ' . e((string) ($user['email'] ?? '')) . '</p></div>';

echo render_layout($content, 'Profil');
