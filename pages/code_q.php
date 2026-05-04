<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$titles = ['fr'=>'Code Q radioamateur','en'=>'Amateur radio Q-code','de'=>'Q-Code Funkamateur','nl'=>'Q-code radioamateur'];
$labels = ['fr'=>'Signification','en'=>'Meaning','de'=>'Bedeutung','nl'=>'Betekenis'];
$title = $titles[$locale] ?? $titles['fr'];
ob_start();
?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <div class="table-wrap mt-3">
    <table>
      <thead><tr><th>Code Q</th><th><?= e($labels[$locale] ?? $labels['fr']) ?></th></tr></thead>
      <tbody>
        <tr><td>QRA</td><td><?= e(['fr'=>'Nom de la station','en'=>'Station name','de'=>'Name der Station','nl'=>'Naam van station'][$locale] ?? 'Nom de la station') ?></td></tr>
        <tr><td>QRG</td><td><?= e(['fr'=>'Fréquence exacte','en'=>'Exact frequency','de'=>'Genaue Frequenz','nl'=>'Exacte frequentie'][$locale] ?? 'Fréquence exacte') ?></td></tr>
        <tr><td>QRM</td><td><?= e(['fr'=>'Interférences','en'=>'Interference','de'=>'Störungen','nl'=>'Interferentie'][$locale] ?? 'Interférences') ?></td></tr>
        <tr><td>QRZ</td><td><?= e(['fr'=>'Qui m’appelle ?','en'=>'Who is calling me?','de'=>'Wer ruft mich?','nl'=>'Wie roept mij?'][$locale] ?? 'Qui m’appelle ?') ?></td></tr>
        <tr><td>QSL</td><td><?= e(['fr'=>'Confirmez réception','en'=>'Confirm reception','de'=>'Empfang bestätigen','nl'=>'Ontvangst bevestigen'][$locale] ?? 'Confirmez réception') ?></td></tr>
        <tr><td>QSO</td><td><?= e(['fr'=>'Communication entre stations','en'=>'Communication between stations','de'=>'Verbindung zwischen Stationen','nl'=>'Verbinding tussen stations'][$locale] ?? 'Communication entre stations') ?></td></tr>
        <tr><td>QSY</td><td><?= e(['fr'=>'Changer de fréquence','en'=>'Change frequency','de'=>'Frequenz wechseln','nl'=>'Verander frequentie'][$locale] ?? 'Changer de fréquence') ?></td></tr>
        <tr><td>QTH</td><td><?= e(['fr'=>'Position géographique','en'=>'Location','de'=>'Standort','nl'=>'Locatie'][$locale] ?? 'Position géographique') ?></td></tr>
      </tbody>
    </table>
  </div>
</section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
