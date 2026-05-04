<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$titleMap = ['fr'=>'Paramètres du compte','en'=>'Account settings','de'=>'Kontoeinstellungen','nl'=>'Accountinstellingen'];
$introMap = ['fr'=>'Centralisez ici vos préférences de compte et options d’interface.','en'=>'Manage your account preferences and interface options here.','de'=>'Verwalten Sie hier Ihre Kontoeinstellungen und Oberflächenoptionen.','nl'=>'Beheer hier je accountvoorkeuren en interface-opties.'];
$links = [
    ['route' => 'code_q', 'label' => ['fr' => 'Code Q', 'en' => 'Q-code', 'de' => 'Q-Code', 'nl' => 'Q-code']],
    ['route' => 'code_cw', 'label' => ['fr' => 'Code CW', 'en' => 'CW code', 'de' => 'CW-Code', 'nl' => 'CW-code']],
    ['route' => 'bandplan_on3', 'label' => ['fr' => 'Band plan ON3', 'en' => 'ON3 band plan', 'de' => 'ON3-Bandplan', 'nl' => 'ON3-bandplan']],
    ['route' => 'bandplan_on2', 'label' => ['fr' => 'Band plan ON2', 'en' => 'ON2 band plan', 'de' => 'ON2-Bandplan', 'nl' => 'ON2-bandplan']],
    ['route' => 'bandplan_harec', 'label' => ['fr' => 'Band plan HAREC', 'en' => 'HAREC band plan', 'de' => 'HAREC-Bandplan', 'nl' => 'HAREC-bandplan']],
];
$pageTitle = $titleMap[$locale] ?? $titleMap['fr'];
ob_start();
?>
<section class="card">
  <h1><?= e($pageTitle) ?></h1>
  <p><?= e($introMap[$locale] ?? $introMap['fr']) ?></p>
  <ul>
    <?php foreach ($links as $link): ?>
      <li><a href="<?= e(route_url((string) $link['route'])) ?>"><?= e((string) ($link['label'][$locale] ?? $link['label']['fr'])) ?></a></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php
echo render_layout((string) ob_get_clean(), $pageTitle);
