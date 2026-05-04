<?php
declare(strict_types=1);
require_login();
$locale=current_locale();
$title=['fr'=>'Band planning ON3','en'=>'ON3 band plan','de'=>'ON3-Bandplan','nl'=>'ON3-bandplan'][$locale] ?? 'Band planning ON3';
ob_start(); ?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <p class="help">Source IBPT/BIPT: <a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">Freq‑FR.pdf</a>.</p>
  <div class="table-wrap mt-3"><table><thead><tr><th>Bande</th><th>Fréquences (MHz)</th><th>Modes</th><th>Puissance max*</th><th>Notes</th></tr></thead><tbody>
    <tr><td>2 m</td><td>144.000–146.000</td><td>FM/SSB/CW</td><td>10 W</td><td>145.500 MHz appel simplex.</td></tr>
    <tr><td>70 cm</td><td>430.000–440.000</td><td>FM/Relais/numérique</td><td>10 W</td><td>Respecter le plan relais local.</td></tr>
    <tr><td>23 cm</td><td>1240–1300</td><td>FM/SSB/Data</td><td>10 W</td><td>Portions partagées, filtrage conseillé.</td></tr>
  </tbody></table></div>
  <p class="help mt-3">* Vérifier les limites légales exactes ON3 publiées par l’IBPT/BIPT (mise à jour réglementaire prioritaire).</p>
</section>
<?php echo render_layout((string)ob_get_clean(),$title); ?>
