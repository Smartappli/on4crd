<article id="tool-ohm-law" data-tool-panel class="card tool-panel is-hidden">
    <h2><?= e((string) ($t['ohm_law_calc'] ?? "Loi d'Ohm")) ?></h2>
    <p class="help"><?= e((string) ($t['ohm_law_help'] ?? 'Enter two values, or edit one of three values, to compute the missing or dependent value.')) ?></p>
    <div class="grid-auto">
        <label><?= e((string) ($t['voltage_v'] ?? 'Tension (V)')) ?>
            <input id="ohm-voltage" type="text" inputmode="decimal" pattern="[0-9]+([\.,][0-9]{1,2})?" placeholder="12.00">
        </label>
        <label><?= e((string) ($t['current_a'] ?? 'Courant (A)')) ?>
            <input id="ohm-current" type="text" inputmode="decimal" pattern="[0-9]+([\.,][0-9]{1,2})?" placeholder="2.00">
        </label>
        <label><?= e((string) ($t['resistance_ohm'] ?? 'Resistance (Ω)')) ?>
            <input id="ohm-resistance" type="text" inputmode="decimal" pattern="[0-9]+([\.,][0-9]{1,2})?" placeholder="6.00">
        </label>
    </div>
    <p class="help"><?= e((string) ($t['ohm_law_hint'] ?? 'When all three fields are filled, the two last edited fields are kept and the third is recalculated.')) ?></p>
</article>
