<?php
declare(strict_types=1);

http_response_code(404);
echo render_layout('<div class="card"><h1>Actualité introuvable</h1><p>Cette actualité n\'existe pas ou n\'est pas publiée.</p></div>', 'Actualité');
