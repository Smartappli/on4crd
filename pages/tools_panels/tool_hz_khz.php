<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-hz-khz" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) $t['hz_khz_calc']) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-hz-khz-in"><?= e((string) $t['value_in']) ?></label>
        <input id="tool-hz-khz-in" type="text" data-step="any" inputmode="decimal" placeholder="1">

        <label for="tool-hz-khz-out"><?= e((string) $t['value_out']) ?></label>
        <output id="tool-hz-khz-out">-</output>
    </div>
</article>
