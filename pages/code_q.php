<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$titles = ['fr'=>'Code Q radioamateur','en'=>'Amateur radio Q-code','de'=>'Q-Code Funkamateur','nl'=>'Q-code radioamateur'];
$title = $titles[$locale] ?? $titles['fr'];
$qRows = [
['QRA','Nom de la station','Station name','Name der Station','Naam van station'],
['QRB','Distance entre stations','Distance between stations','Entfernung zwischen Stationen','Afstand tussen stations'],
['QRG','Fréquence exacte','Exact frequency','Genaue Frequenz','Exacte frequentie'],
['QRH','Variation de fréquence','Frequency drift','Frequenzdrift','Frequentiedrift'],
['QRI','Tonalité de l’émission','Tone of transmission','Tonqualität der Aussendung','Toon van de uitzending'],
['QRK','Lisibilité du signal','Signal readability','Lesbarkeit des Signals','Leesbaarheid van het signaal'],
['QRL','Fréquence occupée','Frequency in use','Frequenz belegt','Frequentie bezet'],
['QRM','Interférences humaines','Man-made interference','Menschengemachte Störungen','Door mensen veroorzaakte storing'],
['QRN','Parasites atmosphériques','Atmospheric noise','Atmosphärische Störungen','Atmosferische ruis'],
['QRO','Augmenter la puissance','Increase power','Leistung erhöhen','Vermogen verhogen'],
['QRP','Réduire la puissance','Reduce power','Leistung reduzieren','Vermogen verlagen'],
['QRQ','Émettre plus vite','Send faster','Schneller senden','Sneller seinen'],
['QRS','Émettre plus lentement','Send slower','Langsamer senden','Trager seinen'],
['QRT','Cesser l’émission','Stop transmitting','Aussendung beenden','Stoppen met uitzenden'],
['QRU','Avez-vous quelque chose pour moi ?','Anything for me?','Haben Sie etwas für mich?','Heeft u iets voor mij?'],
['QRV','Êtes-vous prêt ?','Are you ready?','Sind Sie bereit?','Bent u gereed?'],
['QRZ','Qui m’appelle ?','Who is calling me?','Wer ruft mich?','Wie roept mij?'],
['QSB','Variation du signal','Signal fading','Signalschwankung','Signaalvervaging'],
['QSL','Confirmez réception','Confirm reception','Empfang bestätigen','Ontvangst bevestigen'],
['QSO','Communication entre stations','Communication between stations','Verbindung zwischen Stationen','Verbinding tussen stations'],
['QSY','Changer de fréquence','Change frequency','Frequenz wechseln','Verander frequentie'],
['QTH','Position géographique','Location','Standort','Locatie'],
['QTR','Heure exacte','Exact time','Genaue Uhrzeit','Exacte tijd'],
];
ob_start();
?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <p class="help">Tableau multilingue FR/EN/DE/NL des principaux Q-codes.</p>
  <div class="table-wrap mt-3">
    <table>
      <thead><tr><th>Q</th><th>FR</th><th>EN</th><th>DE</th><th>NL</th></tr></thead>
      <tbody>
      <?php foreach ($qRows as $r): ?>
        <tr><td><?= e($r[0]) ?></td><td><?= e($r[1]) ?></td><td><?= e($r[2]) ?></td><td><?= e($r[3]) ?></td><td><?= e($r[4]) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
