<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article class="card tool-panel" id="tool-fspl" data-tool-panel>
    <h2><?= e((string) $t['fspl_calc']) ?></h2>
    <label><?= e((string) $t['distance_km']) ?>
        <input type="text" inputmode="decimal" id="fspl-distance" data-min="0" data-step="0.01" value="10">
    </label>
    <label><?= e((string) $t['frequency_mhz']) ?>
        <input type="text" inputmode="decimal" id="fspl-frequency" data-min="0" data-step="0.001" value="145.5">
    </label>
    <p class="help"><?= e((string) $t['fspl_result']) ?>: <strong id="fspl-loss">-</strong></p>
</article>
