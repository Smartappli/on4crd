<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$t = i18n_domain_locale('settings', $locale);
$links = [
    ['route' => 'code_q', 'label_key' => 'link_code_q'],
    ['route' => 'code_cw', 'label_key' => 'link_code_cw'],
    ['route' => 'bandplan_on3', 'label_key' => 'link_bandplan_on3'],
    ['route' => 'bandplan_on2', 'label_key' => 'link_bandplan_on2'],
    ['route' => 'bandplan_harec', 'label_key' => 'link_bandplan_harec'],
];
$pageTitle = (string) ($t['title'] ?? 'Account settings');
ob_start();
?>
<section class="card">
  <h1><?= e($pageTitle) ?></h1>
  <p><?= e((string) ($t['intro'] ?? 'Manage your account preferences and interface options here.')) ?></p>
  <ul>
    <?php foreach ($links as $link): ?>
      <li><a href="<?= e(route_url((string) $link['route'])) ?>"><?= e((string) ($t[(string) $link['label_key']] ?? $link['route'])) ?></a></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php
echo render_layout((string) ob_get_clean(), $pageTitle);
