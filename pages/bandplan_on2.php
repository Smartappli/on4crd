<?php
declare(strict_types=1);
require_login();
$locale=current_locale();
$title=['fr'=>'Band planning ON2','en'=>'ON2 band plan','de'=>'ON2-Bandplan','nl'=>'ON2-bandplan'][$locale] ?? 'Band planning ON2';
ob_start(); ?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <p class="help">Source IBPT/BIPT: <a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">Freq‑FR.pdf</a>.</p>
  <div class="table-wrap mt-3"><table><thead><tr><th>Bande</th><th>Fréquences (MHz)</th><th>Modes</th><th>Puissance max*</th><th>Notes</th></tr></thead><tbody>
    <tr><td>80 m</td><td>3.500–3.800</td><td>CW/SSB/Data</td><td>100 W PEP</td><td>Découpage IARU à respecter.</td></tr>
    <tr><td>40 m</td><td>7.000–7.200</td><td>CW/SSB/Data</td><td>100 W PEP</td><td>Trafic dense en concours.</td></tr>
    <tr><td>20 m</td><td>14.000–14.350</td><td>CW/SSB/Data</td><td>100 W PEP</td><td>14.074 MHz FT8 très utilisé.</td></tr>
    <tr><td>15 m</td><td>21.000–21.450</td><td>CW/SSB/Data</td><td>100 W PEP</td><td>Bonne propagation en cycle haut.</td></tr>
    <tr><td>10 m</td><td>28.000–29.700</td><td>CW/SSB/FM</td><td>100 W PEP</td><td>Propagation sporadique E.</td></tr>
    <tr><td>2 m</td><td>144.000–146.000</td><td>FM/SSB/CW</td><td>50 W</td><td>Simplex d’appel 145.500.</td></tr>
    <tr><td>70 cm</td><td>430.000–440.000</td><td>FM/Relais/numérique</td><td>50 W</td><td>Plan relais régional.</td></tr>
  </tbody></table></div>
  <p class="help mt-3">* Valeurs indicatives à confirmer via IBPT/BIPT selon votre autorisation ON2.</p>
</section>
<?php echo render_layout((string)ob_get_clean(),$title); ?>
