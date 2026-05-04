<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$titles = ['fr'=>'Band planning HAREC','en'=>'HAREC band plan','de'=>'HAREC-Bandplan','nl'=>'HAREC-bandplan'];
$intro = ['fr'=>'Référence multilingue HAREC. Vérifier la version officielle IBPT/BIPT.','en'=>'HAREC multilingual reference. Verify official BIPT release.','de'=>'Mehrsprachige HAREC-Referenz. Offizielle BIPT-Version prüfen.','nl'=>'Meertalige HAREC-referentie. Controleer officiële BIPT-versie.'];
$title = $titles[$locale] ?? $titles['fr'];
ob_start(); ?>
<section class="card"><h1><?= e($title) ?></h1><p class="help"><?= e($intro[$locale] ?? $intro['fr']) ?></p><p class="help"><a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">IBPT/BIPT - Freq-FR.pdf</a></p></section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
