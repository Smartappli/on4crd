<?php
declare(strict_types=1);
/** @var string $widgetKey */
/** @var string $widgetTitle */
/** @var array $widgetConfig */
/** @var string $widgetBodyHtml */
?>
<article class="widget-card" draggable="true" aria-grabbed="false" data-widget="<?= e($widgetKey) ?>" data-widget-config='<?= e(json_encode($widgetConfig, JSON_UNESCAPED_SLASHES)) ?>'>
  <header>
    <strong><?= e($widgetTitle) ?></strong>
    <button class="ghost remove-widget" type="button">✕</button>
  </header>
  <div class="widget-body"><?= $widgetBodyHtml ?></div>
</article>
