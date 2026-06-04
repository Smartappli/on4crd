<article id="tool-dbuv-sunit" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['dbuv_sunit_calc'] ?? 'dbuv_sunit_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-dbuv-sunit-in"><?= e((string) ($t['dbuv_label'] ?? 'dBuV')) ?></label>
        <input id="tool-dbuv-sunit-in" type="text" data-step="0.1" inputmode="decimal" placeholder="1">

        <label for="tool-dbuv-sunit-out"><?= e((string) ($t['sunit_label'] ?? 'S-unit')) ?></label>
        <output id="tool-dbuv-sunit-out">-</output>
    </div>
</article>
