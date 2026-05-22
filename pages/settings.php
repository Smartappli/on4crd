<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/settings.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (['title', 'intro', 'link_code_q', 'link_code_cw', 'link_bandplan_on3', 'link_bandplan_on2', 'link_bandplan_harec'] as $key) {
    $t[$key] = i18n_localized_value($i18n[$key] ?? [], $locale, 'fr');
}
$links = [
    ['route' => 'code_q', 'label' => $t['link_code_q']],
    ['route' => 'code_cw', 'label' => $t['link_code_cw']],
    ['route' => 'bandplan_on3', 'label' => $t['link_bandplan_on3']],
    ['route' => 'bandplan_on2', 'label' => $t['link_bandplan_on2']],
    ['route' => 'bandplan_harec', 'label' => $t['link_bandplan_harec']],
];
$pageTitle = $t['title'];
ob_start();
?>
<section class="card">
  <h1><?= e($pageTitle) ?></h1>
  <p><?= e($t['intro']) ?></p>
  <ul>
    <?php foreach ($links as $link): ?>
      <li><a href="<?= e(route_url((string) $link['route'])) ?>"><?= e((string) $link['label']) ?></a></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php
echo render_layout((string) ob_get_clean(), $pageTitle);
