<?php
declare(strict_types=1);

$t = isset($t) && is_array($t) ? $t : [];
?><article class="card tool-panel" id="tool-filter" data-tool-panel>
    <h2><?= e((string) $t['filter_calc']) ?></h2>
    <label><?= e((string) $t['cutoff_freq']) ?>
        <input type="text" inputmode="decimal" id="filter-freq" data-min="0" data-step="0.001" placeholder="<?= e((string) $t['freq_ph']) ?>">
    </label>
    <label><?= e((string) $t['impedance']) ?>
        <input type="text" inputmode="decimal" id="filter-impedance" data-min="1" data-step="0.1" value="50">
    </label>
    <p class="help"><?= e((string) $t['inductance']) ?>: <strong id="filter-l">-</strong></p>
    <p class="help"><?= e((string) $t['capacitance']) ?>: <strong id="filter-c">-</strong></p>
</article>
