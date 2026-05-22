<?php
declare(strict_types=1);
require_login();
$locale=current_locale();
$i18nBandplan = require __DIR__ . '/../app/i18n/bandplan_on2.php';
$title=i18n_localized_value($i18nBandplan['title'],$locale,'fr');
$h=[];
foreach(array_keys($i18nBandplan['headers']['fr']) as $key){
    $pool=[];
    foreach($i18nBandplan['headers'] as $lang=>$items){
        $pool[$lang]=(string)($items[$key] ?? '');
    }
    $h[$key]=i18n_localized_value($pool,$locale,'fr');
}
$rows=$i18nBandplan['rows'];
ob_start(); ?>
<section class="card"><h1><?= e($title) ?></h1><p class="help">IBPT/BIPT: <a href="https://www.ibpt.be/file/cc73d96153bbd5448a56f19d925d05b1379c7f21/1891ad4029fa18396c037433ed4c2a063854f1b0/freq-fr.pdf?name=Freq-FR.pdf&type=application%2Fpdf" target="_blank" rel="noopener noreferrer">Freq‑FR.pdf</a></p><div class="table-wrap mt-3"><table><thead><tr><th><?=e($h['band'])?></th><th><?=e($h['freq'])?></th><th><?=e($h['modes'])?></th><th><?=e($h['pwr'])?></th><th><?=e($h['notes'])?></th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=e($r[0])?></td><td><?=e($r[1])?></td><td><?=e($r[2])?></td><td><?=e($r[4])?></td><td><?=e(i18n_localized_value($r[3],$locale,'fr'))?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php echo render_layout((string)ob_get_clean(),$title); ?>
