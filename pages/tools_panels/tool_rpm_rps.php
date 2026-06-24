<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-rpm-rps" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) $t['rpm_rps_calc']) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-rpm-rps-in"><?= e((string) $t['value_in']) ?></label>
        <input id="tool-rpm-rps-in" type="text" data-step="any" inputmode="decimal" placeholder="1">

        <label for="tool-rpm-rps-out"><?= e((string) $t['value_out']) ?></label>
        <output id="tool-rpm-rps-out">-</output>
    </div>
</article>
