<?php
declare(strict_types=1);

$content = '<div class="card"><h1>Bienvenue sur ON4CRD</h1><p>Le portail est opérationnel. Utilisez le menu/routage pour accéder aux modules actifs.</p>'
    . '<p><a class="button" href="' . e(route_url('articles')) . '">Articles</a> '
    . '<a class="button" href="' . e(route_url('wiki')) . '">Wiki</a> '
    . '<a class="button" href="' . e(route_url('albums')) . '">Albums</a></p></div>';

echo render_layout($content, 'Accueil');
