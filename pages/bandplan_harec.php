<?php
declare(strict_types=1);
require_login();
$locale=current_locale();
$title=['fr'=>'Band planning HAREC','en'=>'HAREC band plan','de'=>'HAREC-Bandplan','nl'=>'HAREC-bandplan'][$locale] ?? 'Band planning HAREC';
ob_start(); ?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <p class="help">Source IBPT/BIPT: <a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">Freq‑FR.pdf</a>.</p>
  <div class="table-wrap mt-3"><table><thead><tr><th><?= e(['fr'=>'Bande','en'=>'Band','de'=>'Band','nl'=>'Band'][$locale] ?? "Bande") ?></th><th><?= e(['fr'=>'Fréquences (MHz)','en'=>'Frequencies (MHz)','de'=>'Frequenzen (MHz)','nl'=>'Frequenties (MHz)'][$locale] ?? "Fréquences (MHz)") ?></th><th><?= e(['fr'=>'Modes','en'=>'Modes','de'=>'Betriebsarten','nl'=>'Modes'][$locale] ?? "Modes") ?></th><th><?= e(['fr'=>'Puissance max*','en'=>'Max power*','de'=>'Max. Leistung*','nl'=>'Max vermogen*'][$locale] ?? "Puissance max*") ?></th><th><?= e(['fr'=>'Notes','en'=>'Notes','de'=>'Hinweise','nl'=>'Notities'][$locale] ?? "Notes") ?></th></tr></thead><tbody>
    <tr><td>160 m</td><td>1.810–2.000</td><td>CW/SSB</td><td>1500 W PEP</td><td>Bas de bande majoritairement CW.</td></tr>
    <tr><td>80 m</td><td>3.500–3.800</td><td>CW/SSB/Data</td><td>1500 W PEP</td><td>NVIS local/régional.</td></tr>
    <tr><td>40 m</td><td>7.000–7.200</td><td>CW/SSB/Data</td><td>1500 W PEP</td><td>Très active en DX.</td></tr>
    <tr><td>30 m</td><td>10.100–10.150</td><td>CW/Data</td><td>200 W PEP</td><td>Bande WARC, pas de phonie.</td></tr>
    <tr><td>20 m</td><td>14.000–14.350</td><td>CW/SSB/Data</td><td>1500 W PEP</td><td>14.074 FT8.</td></tr>
    <tr><td>17 m</td><td>18.068–18.168</td><td>CW/SSB/Data</td><td>1500 W PEP</td><td>Bande WARC.</td></tr>
    <tr><td>15 m</td><td>21.000–21.450</td><td>CW/SSB/Data</td><td>1500 W PEP</td><td>Propagation solaire.</td></tr>
    <tr><td>12 m</td><td>24.890–24.990</td><td>CW/SSB/Data</td><td>1500 W PEP</td><td>Bande WARC.</td></tr>
    <tr><td>10 m</td><td>28.000–29.700</td><td>CW/SSB/FM</td><td>1500 W PEP</td><td>Ouvertures sporadiques.</td></tr>
    <tr><td>6 m</td><td>50.000–52.000</td><td>CW/SSB/FM/Data</td><td>500 W PEP</td><td>DX saisonnier.</td></tr>
    <tr><td>2 m</td><td>144.000–146.000</td><td>FM/SSB/CW</td><td>120 W</td><td>145.500 appel simplex.</td></tr>
    <tr><td>70 cm</td><td>430.000–440.000</td><td>FM/Relais/Numérique</td><td>120 W</td><td>Relais analogiques et numériques.</td></tr>
  </tbody></table></div>
  <p class="help mt-3">* Vérifier systématiquement les limites officielles IBPT/BIPT selon la dernière publication.</p>
</section>
<?php echo render_layout((string)ob_get_clean(),$title); ?>
