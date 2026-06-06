<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article class="card tool-panel" id="tool-quarter-wave" data-tool-panel>
    <h2><?= e((string) $t['quarter_wave_calc']) ?></h2>
    <label><?= e((string) $t['frequency_mhz']) ?>
        <input type="text" inputmode="decimal" id="quarter-wave-frequency" data-min="0" data-step="0.001" value="145.5">
    </label>
    <label><?= e((string) $t['velocity_factor']) ?>
        <input type="text" inputmode="decimal" id="quarter-wave-vf" data-min="0" data-max="1" data-step="0.01" value="0.95">
    </label>
    <p class="help"><?= e((string) $t['quarter_wave_result']) ?>: <strong id="quarter-wave-length">-</strong></p>
</article>
