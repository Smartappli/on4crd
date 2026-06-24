<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article id="tool-xc" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) $t['xc_calc']) ?></h2>
    <div class="tool-grid-form">
        <label for="xc-freq"><?= e((string) $t['frequency_mhz']) ?></label>
        <input id="xc-freq" type="text" data-step="0.001" inputmode="decimal" placeholder="<?= e((string) $t['freq_ph']) ?>">

        <label for="xc-capacitance"><?= e((string) $t['capacitance_pf']) ?></label>
        <input id="xc-capacitance" type="text" data-step="0.1" inputmode="decimal" placeholder="<?= e((string) $t['capacitance_pf_ph']) ?>">

        <label for="xc-result"><?= e((string) $t['reactance_result_ohm']) ?></label>
        <output id="xc-result" aria-live="polite">-</output>
    </div>
</article>
