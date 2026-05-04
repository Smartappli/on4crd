<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$titles = ['fr'=>'Band planning ON3','en'=>'ON3 band plan','de'=>'ON3-Bandplan','nl'=>'ON3-bandplan'];
$intro = ['fr'=>'Référence multilingue ON3. Vérifier la version officielle IBPT/BIPT.','en'=>'ON3 multilingual reference. Verify official BIPT release.','de'=>'Mehrsprachige ON3-Referenz. Offizielle BIPT-Version prüfen.','nl'=>'Meertalige ON3-referentie. Controleer officiële BIPT-versie.'];
$title = $titles[$locale] ?? $titles['fr'];
ob_start(); ?>
<section class="card"><h1><?= e($title) ?></h1><p class="help"><?= e($intro[$locale] ?? $intro['fr']) ?></p><p class="help"><a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">IBPT/BIPT - Freq-FR.pdf</a></p></section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
