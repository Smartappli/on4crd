<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/code_cw.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (['title','intro','char_label','prosign_title','usage_label','prosign_ar','prosign_sk','prosign_bt','prosign_as'] as $key) {
    $t[$key] = i18n_localized_value($i18n[$key] ?? [], $locale, 'fr');
}
$title = $t['title'];
$rows = [
['A','.-','N','-.'],['B','-...','O','---'],['C','-.-.','P','.--.'],['D','-..','Q','--.-'],['E','.','R','.-.'],['F','..-.','S','...'],['G','--.','T','-'],['H','....','U','..-'],['I','..','V','...-'],['J','.---','W','.--'],['K','-.-','X','-..-'],['L','.-..','Y','-.--'],['M','--','Z','--..'],
['1','.----','6','-....'],['2','..---','7','--...'],['3','...--','8','---..'],['4','....-','9','----.'],['5','.....','0','-----'],
['.','.-.-.-',',','--..--'],['?','..--..','/','-..-.'],['=','-...-','+','.-.-.']
];
ob_start();
?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <p class="help"><?= e($t['intro']) ?></p>
  <div class="table-wrap mt-3">
    <table>
      <thead><tr><th><?= e($t['char_label']) ?></th><th>Code</th><th><?= e($t['char_label']) ?></th><th>Code</th></tr></thead>
      <tbody><?php foreach ($rows as $r): ?><tr><td><?= e($r[0]) ?></td><td><?= e($r[1]) ?></td><td><?= e($r[2]) ?></td><td><?= e($r[3]) ?></td></tr><?php endforeach; ?></tbody>
    </table>
  </div>
  <h2 class="mt-4"><?= e($t['prosign_title']) ?></h2>
  <div class="table-wrap mt-2"><table><thead><tr><th>Prosign</th><th>Code</th><th><?= e($t['usage_label']) ?></th></tr></thead><tbody><tr><td>AR</td><td>.-.-.</td><td><?= e($t['prosign_ar']) ?></td></tr><tr><td>SK</td><td>...-.-</td><td><?= e($t['prosign_sk']) ?></td></tr><tr><td>BT</td><td>-...-</td><td><?= e($t['prosign_bt']) ?></td></tr><tr><td>AS</td><td>.-...</td><td><?= e($t['prosign_as']) ?></td></tr></tbody></table></div>
</section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
