<?php
declare(strict_types=1);
require_login();
ob_start();
?>
<section class="card">
  <h1>Paramètres du compte</h1><p>Centralisez ici vos préférences de compte et options d'interface.</p><ul><li><a href="<?= e(route_url('code_q')) ?>">Code Q</a></li><li><a href="<?= e(route_url('code_cw')) ?>">Code CW</a></li><li><a href="<?= e(route_url('bandplan_on3')) ?>">Band plan ON3</a></li><li><a href="<?= e(route_url('bandplan_on2')) ?>">Band plan ON2</a></li><li><a href="<?= e(route_url('bandplan_harec')) ?>">Band plan HAREC</a></li></ul>
</section>
<?php
echo render_layout((string) ob_get_clean(), 'Paramètres du compte');
