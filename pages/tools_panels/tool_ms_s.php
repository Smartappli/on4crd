<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-ms-s" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['ms_s_calc'] ?? 'ms_s_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-ms-s-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-ms-s-in" type="text" data-step="any" inputmode="decimal" placeholder="1">

        <label for="tool-ms-s-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-ms-s-out">-</output>
    </div>
</article>
