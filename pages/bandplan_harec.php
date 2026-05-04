<?php
declare(strict_types=1);
require_login();
$locale=current_locale();
$titleMap=['fr'=>'Band planning HAREC','en'=>'HAREC band plan','de'=>'HAREC-Bandplan','nl'=>'HAREC-bandplan'];$title=$titleMap[$locale] ?? $titleMap['fr'];
$h=['fr'=>['band'=>'Bande','freq'=>'Fréquences (MHz)','modes'=>'Modes','pwr'=>'Puissance max*','notes'=>'Notes'],'en'=>['band'=>'Band','freq'=>'Frequencies (MHz)','modes'=>'Modes','pwr'=>'Max power*','notes'=>'Notes'],'de'=>['band'=>'Band','freq'=>'Frequenzen (MHz)','modes'=>'Betriebsarten','pwr'=>'Max. Leistung*','notes'=>'Hinweise'],'nl'=>['band'=>'Band','freq'=>'Frequenties (MHz)','modes'=>'Modes','pwr'=>'Max vermogen*','notes'=>'Notities']][$locale] ?? ['band'=>'Bande','freq'=>'Fréquences (MHz)','modes'=>'Modes','pwr'=>'Puissance max*','notes'=>'Notes'];
$rows=[
['160 m','1.810–2.000','CW/SSB',['fr'=>'Bas de bande majoritairement CW.','en'=>'Lower segment mainly CW.','de'=>'Unteres Segment meist CW.','nl'=>'Onderste segment vooral CW.'],'1500 W PEP'],
['80 m','3.500–3.800','CW/SSB/Data',['fr'=>'NVIS local/régional.','en'=>'Local/regional NVIS.','de'=>'Lokales/regionales NVIS.','nl'=>'Lokaal/regionaal NVIS.'],'1500 W PEP'],
['60 m','5.3515–5.3665','SSB/Data',['fr'=>'Canaux/segments selon publication IBPT.','en'=>'Channels/segments per IBPT publication.','de'=>'Kanäle/Segmente gemäß IBPT-Veröffentlichung.','nl'=>'Kanalen/segmenten volgens IBPT-publicatie.'],'100 W PEP'],
['40 m','7.000–7.200','CW/SSB/Data',['fr'=>'Forte activité internationale.','en'=>'High international activity.','de'=>'Hohe internationale Aktivität.','nl'=>'Hoge internationale activiteit.'],'1500 W PEP'],
['30 m','10.100–10.150','CW/Data',['fr'=>'Pas de téléphonie en général.','en'=>'Generally no phone operation.','de'=>'Im Allgemeinen keine Telefonie.','nl'=>'Meestal geen telefonie.'],'500 W PEP'],
['20 m','14.000–14.350','CW/SSB/Data',['fr'=>'14.074 FT8.','en'=>'14.074 FT8.','de'=>'14,074 FT8.','nl'=>'14.074 FT8.'],'1500 W PEP'],
['17 m','18.068–18.168','CW/SSB/Data',['fr'=>'Bande WARC.','en'=>'WARC band.','de'=>'WARC-Band.','nl'=>'WARC-band.'],'1500 W PEP'],
['15 m','21.000–21.450','CW/SSB/Data',['fr'=>'Propagation souvent favorable en journée.','en'=>'Propagation often favorable by day.','de'=>'Ausbreitung tagsüber oft günstig.','nl'=>'Propagatie vaak gunstig overdag.'],'1500 W PEP'],
['12 m','24.890–24.990','CW/SSB/Data',['fr'=>'Bande WARC.','en'=>'WARC band.','de'=>'WARC-Band.','nl'=>'WARC-band.'],'1500 W PEP'],
['10 m','28.000–29.700','CW/SSB/FM/Data',['fr'=>'Inclut balises et FM locale.','en'=>'Includes beacons and local FM.','de'=>'Enthält Baken und lokalen FM-Betrieb.','nl'=>'Inclusief bakens en lokale FM.'],'1500 W PEP'],
['6 m','50.000–52.000','CW/SSB/FM/Data',['fr'=>'Ouvertures sporadiques E.','en'=>'Sporadic-E openings.','de'=>'Sporadic-E-Öffnungen.','nl'=>'Sporadic-E-openingen.'],'400 W PEP'],
['2 m','144.000–146.000','FM/SSB/CW',['fr'=>'145.500 appel simplex.','en'=>'145.500 simplex calling.','de'=>'145,500 Simplex-Anruf.','nl'=>'145.500 simplex-oproep.'],'120 W PEP'],
['70 cm','430.000–440.000','FM/Relais/Numérique',['fr'=>'Relais analogiques et numériques.','en'=>'Analog and digital repeaters.','de'=>'Analoge und digitale Relais.','nl'=>'Analoge en digitale relais.'],'120 W PEP'],
['23 cm','1240–1300','FM/SSB/CW/Data',['fr'=>'Usages partagés selon zones.','en'=>'Shared usage depending on areas.','de'=>'Geteilte Nutzung je nach Region.','nl'=>'Gedeeld gebruik afhankelijk van regio.'],'120 W PEP'],
];
ob_start(); ?>
<section class="card"><h1><?= e($title) ?></h1><p class="help">IBPT/BIPT: <a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">Freq‑FR.pdf</a></p><div class="table-wrap mt-3"><table><thead><tr><th><?=e($h['band'])?></th><th><?=e($h['freq'])?></th><th><?=e($h['modes'])?></th><th><?=e($h['pwr'])?></th><th><?=e($h['notes'])?></th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=e($r[0])?></td><td><?=e($r[1])?></td><td><?=e($r[2])?></td><td><?=e($r[4])?></td><td><?=e($r[3][$locale] ?? $r[3]['fr'])?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php echo render_layout((string)ob_get_clean(),$title); ?>
