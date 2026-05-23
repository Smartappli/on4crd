<article id="tool-ft-m" class="tool-panel card is-hidden" data-tool-panel>
    <h2><?= e((string) ($t['ft_m_calc'] ?? 'ft_m_calc')) ?></h2>
    <div class="tool-grid-form">
        <label for="tool-ft-m-in"><?= e((string) ($t['value_in'] ?? 'Input value')) ?></label>
        <input id="tool-ft-m-in" type="number" step="any" inputmode="decimal" placeholder="1">

        <label for="tool-ft-m-out"><?= e((string) ($t['value_out'] ?? 'Output value')) ?></label>
        <output id="tool-ft-m-out">-</output>
    </div>
</article>
