<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-vpk-vrms" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['vpk_vrms_calc'] ?? 'vpk_vrms_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-vpk-vrms-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-vpk-vrms-in" type="text" data-step="any" inputmode="decimal" placeholder="1">

        <label for="tool-vpk-vrms-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-vpk-vrms-out">-</output>
    </div>
</article>
