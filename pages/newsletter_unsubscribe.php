<?php
declare(strict_types=1);

newsletter_ensure_tables();

set_page_meta([
    'title' => 'Désabonnement newsletter',
    'description' => 'Confirmation de désabonnement à la newsletter ON4CRD.',
    'robots' => 'noindex,nofollow',
]);
$token = (string) ($_GET['token'] ?? '');
$ok = newsletter_unsubscribe_by_token($token);

$message = $ok
    ? '<div class="card"><h1>Newsletter</h1><p>Votre désabonnement a été pris en compte.</p></div>'
    : '<div class="card"><h1>Newsletter</h1><p>Lien invalide ou déjà traité.</p></div>';

echo render_layout($message, 'Désabonnement newsletter');
