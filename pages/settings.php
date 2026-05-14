<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$titleMap = ['fr'=>'Paramètres du compte','en'=>'Account settings','de'=>'Kontoeinstellungen','es'=>'Configuración de la cuenta','it'=>'Impostazioni account','pt'=>'Definições da conta','nl'=>'Accountinstellingen'];
$introMap = ['fr'=>'Centralisez ici vos préférences de compte et options d’interface.','en'=>'Manage your account preferences and interface options here.','de'=>'Verwalten Sie hier Ihre Kontoeinstellungen und Oberflächenoptionen.','es'=>'Gestiona aquí tus preferencias de cuenta y opciones de interfaz.','it'=>'Gestisci qui le preferenze dell\'account e le opzioni dell\'interfaccia.','pt'=>'Gira aqui as preferências da conta e opções da interface.','nl'=>'Beheer hier je accountvoorkeuren en interface-opties.'];
$links = [
    ['route' => 'code_q', 'label' => ['fr' => 'Code Q', 'en' => 'Q-code', 'de' => 'Q-Code', 'es' => 'Código Q', 'it' => 'Codice Q', 'pt' => 'Código Q', 'nl' => 'Q-code']],
    ['route' => 'code_cw', 'label' => ['fr' => 'Code CW', 'en' => 'CW code', 'de' => 'CW-Code', 'es' => 'Código CW', 'it' => 'Codice CW', 'pt' => 'Código CW', 'nl' => 'CW-code']],
    ['route' => 'bandplan_on3', 'label' => ['fr' => 'Band plan ON3', 'en' => 'ON3 band plan', 'de' => 'ON3-Bandplan', 'es' => 'Plan de bandas ON3', 'it' => 'Band plan ON3', 'pt' => 'Plano de bandas ON3', 'nl' => 'ON3-bandplan']],
    ['route' => 'bandplan_on2', 'label' => ['fr' => 'Band plan ON2', 'en' => 'ON2 band plan', 'de' => 'ON2-Bandplan', 'es' => 'Plan de bandas ON2', 'it' => 'Band plan ON2', 'pt' => 'Plano de bandas ON2', 'nl' => 'ON2-bandplan']],
    ['route' => 'bandplan_harec', 'label' => ['fr' => 'Band plan HAREC', 'en' => 'HAREC band plan', 'de' => 'HAREC-Bandplan', 'es' => 'Plan de bandas HAREC', 'it' => 'Band plan HAREC', 'pt' => 'Plano de bandas HAREC', 'nl' => 'HAREC-bandplan']],
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
