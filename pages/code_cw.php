<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$titles = ['fr'=>'Code CW (Morse)','en'=>'CW code (Morse)','de'=>'CW-Code (Morse)','nl'=>'CW-code (Morse)'];
$intro = ['fr'=>'Tableau complet alphabet + chiffres + ponctuation utile.','en'=>'Complete table with alphabet, digits and useful punctuation.','de'=>'Vollständige Tabelle mit Alphabet, Zahlen und nützlichen Satzzeichen.','nl'=>'Volledige tabel met alfabet, cijfers en nuttige leestekens.'];
$charLabel = ['fr'=>'Caractère','en'=>'Character','de'=>'Zeichen','nl'=>'Teken'];
$title = $titles[$locale] ?? $titles['fr'];
$rows = [
['A','.-','N','-.'],['B','-...','O','---'],['C','-.-.','P','.--.'],['D','-..','Q','--.-'],['E','.','R','.-.'],['F','..-.','S','...'],['G','--.','T','-'],['H','....','U','..-'],['I','..','V','...-'],['J','.---','W','.--'],['K','-.-','X','-..-'],['L','.-..','Y','-.--'],['M','--','Z','--..'],
['1','.----','6','-....'],['2','..---','7','--...'],['3','...--','8','---..'],['4','....-','9','----.'],['5','.....','0','-----'],
['.','.-.-.-',',','--..--'],['?','..--..','/','-..-.'],['=','-...-','+','.-.-.']
];
ob_start();
?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <p class="help"><?= e($intro[$locale] ?? $intro['fr']) ?></p>
  <div class="table-wrap mt-3">
    <table>
      <thead><tr><th><?= e($charLabel[$locale] ?? $charLabel['fr']) ?></th><th>Code</th><th><?= e($charLabel[$locale] ?? $charLabel['fr']) ?></th><th>Code</th></tr></thead>
      <tbody><?php foreach ($rows as $r): ?><tr><td><?= e($r[0]) ?></td><td><?= e($r[1]) ?></td><td><?= e($r[2]) ?></td><td><?= e($r[3]) ?></td></tr><?php endforeach; ?></tbody>
    </table>
  </div>
  <h2 class="mt-4">Prosigns</h2>
  <div class="table-wrap mt-2"><table><thead><tr><th>Prosign</th><th>Code</th><th><?= e(['fr'=>'Usage','en'=>'Usage','de'=>'Verwendung','nl'=>'Gebruik'][$locale] ?? 'Usage') ?></th></tr></thead><tbody><tr><td>AR</td><td>.-.-.</td><td><?= e(['fr'=>'Fin de message','en'=>'End of message','de'=>'Ende der Nachricht','nl'=>'Einde bericht'][$locale] ?? 'Fin de message') ?></td></tr><tr><td>SK</td><td>...-.-</td><td><?= e(['fr'=>'Fin de contact','en'=>'End of contact','de'=>'Ende der Verbindung','nl'=>'Einde contact'][$locale] ?? 'Fin de contact') ?></td></tr><tr><td>BT</td><td>-...-</td><td><?= e(['fr'=>'Séparateur','en'=>'Separator','de'=>'Trenner','nl'=>'Scheiding'][$locale] ?? 'Séparateur') ?></td></tr><tr><td>AS</td><td>.-...</td><td><?= e(['fr'=>'Attendez','en'=>'Wait','de'=>'Warten','nl'=>'Wacht'][$locale] ?? 'Attendez') ?></td></tr></tbody></table></div>
</section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
