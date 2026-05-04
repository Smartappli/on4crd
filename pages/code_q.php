<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$titles = ['fr'=>'Code Q radioamateur','en'=>'Amateur radio Q-code','de'=>'Q-Code Funkamateur','nl'=>'Q-code radioamateur'];
$title = $titles[$locale] ?? $titles['fr'];
$meaningLabel = ['fr'=>'Signification','en'=>'Meaning','de'=>'Bedeutung','nl'=>'Betekenis'][$locale] ?? 'Signification';
$qRows = [
['QRA',['fr'=>'Nom de la station','en'=>'Station name']],['QRB',['fr'=>'Distance entre stations','en'=>'Distance between stations']],['QRG',['fr'=>'Fréquence exacte','en'=>'Exact frequency']],['QRH',['fr'=>'Variation de fréquence','en'=>'Frequency drift']],['QRI',['fr'=>'Tonalité de l’émission','en'=>'Tone of transmission']],['QRK',['fr'=>'Lisibilité du signal','en'=>'Signal readability']],['QRL',['fr'=>'Fréquence occupée','en'=>'Frequency in use']],['QRM',['fr'=>'Interférences humaines','en'=>'Man-made interference']],['QRN',['fr'=>'Parasites atmosphériques','en'=>'Atmospheric noise']],['QRO',['fr'=>'Augmenter la puissance','en'=>'Increase power']],['QRP',['fr'=>'Réduire la puissance','en'=>'Reduce power']],['QRQ',['fr'=>'Émettre plus vite','en'=>'Send faster']],['QRS',['fr'=>'Émettre plus lentement','en'=>'Send slower']],['QRT',['fr'=>'Cesser l’émission','en'=>'Stop transmitting']],['QRU',['fr'=>'Avez-vous quelque chose pour moi ?','en'=>'Do you have anything for me?']],['QRV',['fr'=>'Êtes-vous prêt ?','en'=>'Are you ready?']],['QRW',['fr'=>'Prévenez ... de m’appeler','en'=>'Inform ... to call me']],['QRX',['fr'=>'Rappeler à ...','en'=>'Call me again at ...']],['QRY',['fr'=>'Quel est mon tour ?','en'=>'What is my turn?']],['QRZ',['fr'=>'Qui m’appelle ?','en'=>'Who is calling me?']],['QSA',['fr'=>'Force du signal','en'=>'Signal strength']],['QSB',['fr'=>'Variation du signal','en'=>'Signal fading']],['QSD',['fr'=>'Manipulation défectueuse','en'=>'Defective keying']],['QSK',['fr'=>'M’entendez-vous entre mes signaux ?','en'=>'Can you hear me between your signals?']],['QSL',['fr'=>'Confirmez réception','en'=>'Confirm reception']],['QSO',['fr'=>'Communication entre stations','en'=>'Communication between stations']],['QSP',['fr'=>'Relayer un message','en'=>'Relay a message']],['QST',['fr'=>'Message général à toutes stations','en'=>'General call to all stations']],['QSX',['fr'=>'Écouter sur autre fréquence','en'=>'Listen on another frequency']],['QSY',['fr'=>'Changer de fréquence','en'=>'Change frequency']],['QSZ',['fr'=>'Émettre deux fois chaque mot','en'=>'Send each word twice']],['QTA',['fr'=>'Annuler le message','en'=>'Cancel message']],['QTC',['fr'=>'Message(s) à transmettre','en'=>'Messages to transmit']],['QTH',['fr'=>'Position géographique','en'=>'Location']],['QTR',['fr'=>'Heure exacte','en'=>'Exact time']]
];
ob_start();
?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <p class="help"><?= e(['fr'=>'Liste étendue des Q-codes usuels en trafic radioamateur.','en'=>'Extended list of common Q-codes in amateur radio traffic.','de'=>'Erweiterte Liste gängiger Q-Codes im Amateurfunkverkehr.','nl'=>'Uitgebreide lijst van veelgebruikte Q-codes in radioamateurverkeer.'][$locale] ?? 'Liste étendue des Q-codes usuels en trafic radioamateur.') ?></p>
  <div class="table-wrap mt-3"><table><thead><tr><th>Code Q</th><th><?= e($meaningLabel) ?></th></tr></thead><tbody>
  <?php foreach ($qRows as $row): $m = $row[1]; $text = (string) ($m[$locale] ?? $m['en'] ?? $m['fr']); ?>
    <tr><td><?= e($row[0]) ?></td><td><?= e($text) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table></div>
</section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
