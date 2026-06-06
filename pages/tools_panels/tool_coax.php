<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article class="card tool-panel" id="tool-coax" data-tool-panel>
    <h2><?= e((string) $t['coax_calc']) ?></h2>
    <label><?= e((string) $t['length_m']) ?>
        <input type="text" inputmode="decimal" id="coax-length" data-min="0" data-step="0.1" value="20">
    </label>
    <label><?= e((string) $t['atten_100m']) ?>
        <input type="text" inputmode="decimal" id="coax-atten" data-min="0" data-step="0.01" value="6.7">
    </label>
    <p class="help"><?= e((string) $t['coax_loss']) ?>: <strong id="coax-loss">-</strong></p>
</article>
