<?php
declare(strict_types=1);
require_login();
$locale=current_locale();
$titleMap=['fr'=>'Band planning ON2','en'=>'ON2 band plan','de'=>'ON2-Bandplan','nl'=>'ON2-bandplan'];$title=$titleMap[$locale] ?? $titleMap['fr'];
$h=['fr'=>['band'=>'Bande','freq'=>'Fréquences (MHz)','modes'=>'Modes','pwr'=>'Puissance max*','notes'=>'Notes'],'en'=>['band'=>'Band','freq'=>'Frequencies (MHz)','modes'=>'Modes','pwr'=>'Max power*','notes'=>'Notes'],'de'=>['band'=>'Band','freq'=>'Frequenzen (MHz)','modes'=>'Betriebsarten','pwr'=>'Max. Leistung*','notes'=>'Hinweise'],'nl'=>['band'=>'Band','freq'=>'Frequenties (MHz)','modes'=>'Modes','pwr'=>'Max vermogen*','notes'=>'Notities']][$locale] ?? ['band'=>'Bande','freq'=>'Fréquences (MHz)','modes'=>'Modes','pwr'=>'Puissance max*','notes'=>'Notes'];
$rows=[
['160 m','1.810–2.000','CW/SSB/Data',['fr'=>'Plan IARU R1 à respecter.','en'=>'Follow IARU R1 segmentation.','de'=>'IARU-R1-Segmentierung beachten.','nl'=>'Volg IARU R1-segmentering.'],'100 W PEP'],
['80 m','3.500–3.800','CW/SSB/Data',['fr'=>'Portion téléphonie en haut de bande.','en'=>'Phone segment in upper band.','de'=>'Telefonie im oberen Bandteil.','nl'=>'Telefonie in het bovenste banddeel.'],'100 W PEP'],
['40 m','7.000–7.200','CW/SSB/Data',['fr'=>'Usage concours fréquent.','en'=>'Frequent contest usage.','de'=>'Häufiger Contestbetrieb.','nl'=>'Vaak contestverkeer.'],'100 W PEP'],
['20 m','14.000–14.350','CW/SSB/Data',['fr'=>'FT8 usuel sur 14.074 MHz.','en'=>'FT8 commonly on 14.074 MHz.','de'=>'FT8 üblicherweise auf 14,074 MHz.','nl'=>'FT8 meestal op 14.074 MHz.'],'100 W PEP'],
['15 m','21.000–21.450','CW/SSB/Data',['fr'=>'Propagation diurne variable.','en'=>'Variable daytime propagation.','de'=>'Variable Tagesausbreitung.','nl'=>'Variabele dagpropagatie.'],'100 W PEP'],
['10 m','28.000–29.700','CW/SSB/FM/Data',['fr'=>'Inclut balises et sous-bande FM.','en'=>'Includes beacons and FM sub-band.','de'=>'Enthält Baken und FM-Teilband.','nl'=>'Inclusief bakens en FM-subband.'],'100 W PEP'],
['6 m','50.000–52.000','CW/SSB/FM/Data',['fr'=>'Ouvertures sporadiques E possibles.','en'=>'Sporadic-E openings possible.','de'=>'Sporadic-E-Öffnungen möglich.','nl'=>'Sporadic-E-openingen mogelijk.'],'100 W PEP'],
['2 m','144.000–146.000','FM/SSB/CW/Data',['fr'=>'Simplex d’appel 145.500 MHz.','en'=>'Simplex calling on 145.500 MHz.','de'=>'Simplex-Anruf auf 145,500 MHz.','nl'=>'Simplex-oproep op 145.500 MHz.'],'50 W PEP'],
['70 cm','430.000–440.000','FM/SSB/CW/DV',['fr'=>'Respecter la coordination relais locale.','en'=>'Respect local repeater coordination.','de'=>'Lokale Relaiskoordination beachten.','nl'=>'Respecteer lokale relaiscoördinatie.'],'50 W PEP'],
['23 cm','1240–1300','FM/SSB/CW/Data',['fr'=>'Bande partagée selon usages locaux.','en'=>'Shared band depending on local usage.','de'=>'Geteiltes Band je nach lokaler Nutzung.','nl'=>'Gedeelde band volgens lokaal gebruik.'],'50 W PEP'],
];
ob_start(); ?>
<section class="card"><h1><?= e($title) ?></h1><p class="help">IBPT/BIPT: <a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">Freq‑FR.pdf</a></p><div class="table-wrap mt-3"><table><thead><tr><th><?=e($h['band'])?></th><th><?=e($h['freq'])?></th><th><?=e($h['modes'])?></th><th><?=e($h['pwr'])?></th><th><?=e($h['notes'])?></th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=e($r[0])?></td><td><?=e($r[1])?></td><td><?=e($r[2])?></td><td><?=e($r[4])?></td><td><?=e($r[3][$locale] ?? $r[3]['fr'])?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php echo render_layout((string)ob_get_clean(),$title); ?>
