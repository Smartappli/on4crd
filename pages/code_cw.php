<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('code_cw', $locale);
$title = (string) ($t['title'] ?? 'CW code (Morse)');
$rows = [
    ['A', '.-', 'N', '-.'], ['B', '-...', 'O', '---'], ['C', '-.-.', 'P', '.--.'],
    ['D', '-..', 'Q', '--.-'], ['E', '.', 'R', '.-.'], ['F', '..-.', 'S', '...'],
    ['G', '--.', 'T', '-'], ['H', '....', 'U', '..-'], ['I', '..', 'V', '...-'],
    ['J', '.---', 'W', '.--'], ['K', '-.-', 'X', '-..-'], ['L', '.-..', 'Y', '-.--'],
    ['M', '--', 'Z', '--..'], ['1', '.----', '6', '-....'], ['2', '..---', '7', '--...'],
    ['3', '...--', '8', '---..'], ['4', '....-', '9', '----.'], ['5', '.....', '0', '-----'],
    ['.', '.-.-.-', ',', '--..--'], ['?', '..--..', '/', '-..-.'], ['=', '-...-', '+', '.-.-.'],
];
$prosigns = [
    ['AR', '.-.-.', 'prosign_ar'],
    ['SK', '...-.-', 'prosign_sk'],
    ['BT', '-...-', 'prosign_bt'],
    ['AS', '.-...', 'prosign_as'],
];

ob_start();
?>
<section class="card code-cw-module">
  <h1><?= e($title) ?></h1>
  <p class="help"><?= e((string) ($t['intro'] ?? 'Complete table with alphabet, digits and useful punctuation.')) ?></p>
  <div class="table-wrap mt-3">
    <table class="code-cw-chart">
      <thead><tr><th><?= e((string) ($t['character'] ?? 'Character')) ?></th><th>Code</th><th><?= e((string) ($t['character'] ?? 'Character')) ?></th><th>Code</th></tr></thead>
      <tbody><?php foreach ($rows as $row): ?><tr><td class="code-cw-symbol"><?= e($row[0]) ?></td><td><span class="code-cw-sequence"><?= e($row[1]) ?></span></td><td class="code-cw-symbol"><?= e($row[2]) ?></td><td><span class="code-cw-sequence"><?= e($row[3]) ?></span></td></tr><?php endforeach; ?></tbody>
    </table>
  </div>
  <h2 class="mt-4"><?= e((string) ($t['prosigns'] ?? 'Prosigns')) ?></h2>
  <div class="table-wrap mt-2">
    <table class="code-cw-prosigns">
      <thead><tr><th>Prosign</th><th>Code</th><th><?= e((string) ($t['usage'] ?? 'Usage')) ?></th></tr></thead>
      <tbody><?php foreach ($prosigns as $row): ?><tr><td class="code-cw-symbol"><?= e($row[0]) ?></td><td><span class="code-cw-sequence"><?= e($row[1]) ?></span></td><td><?= e((string) ($t[$row[2]] ?? $row[2])) ?></td></tr><?php endforeach; ?></tbody>
    </table>
  </div>
</section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
