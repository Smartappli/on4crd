<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-vrms-vpp" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['vrms_vpp_calc'] ?? 'vrms_vpp_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-vrms-vpp-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-vrms-vpp-in" type="text" data-step="any" inputmode="decimal" placeholder="1">

        <label for="tool-vrms-vpp-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-vrms-vpp-out">-</output>
    </div>
</article>
