<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article class="card tool-panel" id="tool-divider" data-tool-panel>
    <h2><?= e((string) $t['divider_calc']) ?></h2>
    <label><?= e((string) $t['vin_volts']) ?>
        <input type="text" inputmode="decimal" id="divider-vin" data-min="0" data-step="0.01" value="13.8">
    </label>
    <label><?= e((string) $t['r1_ohm']) ?>
        <input type="text" inputmode="decimal" id="divider-r1" data-min="0" data-step="1" value="10000">
    </label>
    <label><?= e((string) $t['r2_ohm']) ?>
        <input type="text" inputmode="decimal" id="divider-r2" data-min="0" data-step="1" value="2200">
    </label>
    <p class="help"><?= e((string) $t['vout_volts']) ?>: <strong id="divider-vout">-</strong></p>
</article>
