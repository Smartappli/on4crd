<?php
declare(strict_types=1);
require_login();
$locale=current_locale();
$titleMap=['fr'=>'Band planning ON3','en'=>'ON3 band plan','de'=>'ON3-Bandplan','nl'=>'ON3-bandplan'];
$title=$titleMap[$locale] ?? $titleMap['fr'];
$h=['fr'=>['band'=>'Bande','freq'=>'Fréquences (MHz)','modes'=>'Modes','pwr'=>'Puissance max*','notes'=>'Notes'],'en'=>['band'=>'Band','freq'=>'Frequencies (MHz)','modes'=>'Modes','pwr'=>'Max power*','notes'=>'Notes'],'de'=>['band'=>'Band','freq'=>'Frequenzen (MHz)','modes'=>'Betriebsarten','pwr'=>'Max. Leistung*','notes'=>'Hinweise'],'nl'=>['band'=>'Band','freq'=>'Frequenties (MHz)','modes'=>'Modes','pwr'=>'Max vermogen*','notes'=>'Notities']][$locale] ?? ['band'=>'Bande','freq'=>'Fréquences (MHz)','modes'=>'Modes','pwr'=>'Puissance max*','notes'=>'Notes'];
$rows=[
['80 m','3.500–3.600','CW/SSB',['fr'=>'Segment ON3 limité selon autorisation.','en'=>'ON3 limited segment per authorization.','de'=>'ON3-Teilbereich gemäß Genehmigung.','nl'=>'ON3-beperkt segment volgens vergunning.'],'10 W PEP'],
['40 m','7.000–7.100','CW/SSB',['fr'=>'Utilisation partielle selon réglementation.','en'=>'Partial use per regulation.','de'=>'Teilnutzung gemäß Vorschriften.','nl'=>'Gedeeltelijk gebruik volgens regelgeving.'],'10 W PEP'],
['15 m','21.100–21.200','SSB/Data',['fr'=>'Ouverture surtout diurne.','en'=>'Mostly daytime openings.','de'=>'Öffnungen meist tagsüber.','nl'=>'Openingen vooral overdag.'],'10 W PEP'],
['10 m','28.000–29.700','CW/SSB/FM/Data',['fr'=>'Sous-bandes selon plan IARU.','en'=>'Sub-bands per IARU plan.','de'=>'Teilbänder nach IARU-Plan.','nl'=>'Subbanden volgens IARU-plan.'],'10 W PEP'],
['2 m','144.000–146.000','FM/SSB/CW',['fr'=>'145.500 MHz appel simplex.','en'=>'145.500 MHz simplex calling.','de'=>'145,500 MHz Simplex-Anruf.','nl'=>'145.500 MHz simplex-oproep.'],'10 W PEP'],
['70 cm','430.000–440.000','FM/Relais/numérique',['fr'=>'Respecter le plan relais local.','en'=>'Follow local repeater plan.','de'=>'Lokalen Relaisplan beachten.','nl'=>'Volg het lokale relaisplan.'],'10 W PEP'],
['23 cm','1240–1300','FM/SSB/Data',['fr'=>'Portions partagées, filtrage conseillé.','en'=>'Shared segments, filtering recommended.','de'=>'Geteilte Segmente, Filter empfohlen.','nl'=>'Gedeelde segmenten, filtering aanbevolen.'],'10 W PEP'],
];
ob_start(); ?>
<section class="card"><h1><?= e($title) ?></h1><p class="help">IBPT/BIPT: <a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">Freq‑FR.pdf</a></p><div class="table-wrap mt-3"><table><thead><tr><th><?=e($h['band'])?></th><th><?=e($h['freq'])?></th><th><?=e($h['modes'])?></th><th><?=e($h['pwr'])?></th><th><?=e($h['notes'])?></th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=e($r[0])?></td><td><?=e($r[1])?></td><td><?=e($r[2])?></td><td><?=e($r[4])?></td><td><?=e($r[3][$locale] ?? $r[3]['fr'])?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php echo render_layout((string)ob_get_clean(),$title); ?>
