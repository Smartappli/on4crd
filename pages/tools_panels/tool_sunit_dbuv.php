<article id="tool-sunit-dbuv" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['sunit_dbuv_calc'] ?? 'sunit_dbuv_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-sunit-dbuv-in"><?= e((string) ($t['sunit_label'] ?? 'S-unit')) ?></label>
        <input id="tool-sunit-dbuv-in" type="text" step="0.1" inputmode="decimal" placeholder="1">

        <label for="tool-sunit-dbuv-out"><?= e((string) ($t['dbuv_label'] ?? 'dBuV')) ?></label>
        <output id="tool-sunit-dbuv-out">-</output>
    </div>
</article>
