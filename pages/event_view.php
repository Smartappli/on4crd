<?php
declare(strict_types=1);

http_response_code(404);
echo render_layout('<div class="card"><h1>Événement introuvable</h1><p>L\'événement demandé est indisponible.</p></div>', 'Événement');
