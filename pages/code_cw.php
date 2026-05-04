<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$titles = ['fr'=>'Code CW (Morse)','en'=>'CW code (Morse)','de'=>'CW-Code (Morse)','nl'=>'CW-code (Morse)'];
$charLabel = ['fr'=>'Caractère','en'=>'Character','de'=>'Zeichen','nl'=>'Teken'];
$title = $titles[$locale] ?? $titles['fr'];
ob_start();
?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <div class="table-wrap mt-3">
    <table>
      <thead><tr><th><?= e($charLabel[$locale] ?? $charLabel['fr']) ?></th><th>Code</th><th><?= e($charLabel[$locale] ?? $charLabel['fr']) ?></th><th>Code</th></tr></thead>
      <tbody>
        <tr><td>A</td><td>.-</td><td>N</td><td>-.</td></tr><tr><td>B</td><td>-...</td><td>O</td><td>---</td></tr><tr><td>C</td><td>-.-.</td><td>P</td><td>.--.</td></tr><tr><td>D</td><td>-..</td><td>Q</td><td>--.-</td></tr><tr><td>E</td><td>.</td><td>R</td><td>.-.</td></tr><tr><td>F</td><td>..-.</td><td>S</td><td>...</td></tr><tr><td>G</td><td>--.</td><td>T</td><td>-</td></tr><tr><td>H</td><td>....</td><td>U</td><td>..-</td></tr><tr><td>I</td><td>..</td><td>V</td><td>...-</td></tr><tr><td>J</td><td>.---</td><td>W</td><td>.--</td></tr><tr><td>K</td><td>-.-</td><td>X</td><td>-..-</td></tr><tr><td>L</td><td>.-..</td><td>Y</td><td>-.--</td></tr><tr><td>M</td><td>--</td><td>Z</td><td>--..</td></tr>
      </tbody>
    </table>
  </div>
</section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
