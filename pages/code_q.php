<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$titles = ['fr'=>'Code Q radioamateur','en'=>'Amateur radio Q-code','de'=>'Q-Code Funkamateur','nl'=>'Q-code radioamateur'];
$labels = ['fr'=>'Signification','en'=>'Meaning','de'=>'Bedeutung','nl'=>'Betekenis'];
$title = $titles[$locale] ?? $titles['fr'];
$qRows = [
['QRA','Nom de la station'],['QRB','Distance entre stations'],['QRG','Fréquence exacte'],['QRH','Variation de fréquence'],['QRI','Tonalité de l’émission'],['QRK','Lisibilité du signal'],['QRL','Fréquence occupée'],['QRM','Interférences humaines'],['QRN','Parasites atmosphériques'],['QRO','Augmenter la puissance'],['QRP','Réduire la puissance'],['QRQ','Émettre plus vite'],['QRS','Émettre plus lentement'],['QRT','Cesser l’émission'],['QRU','Avez-vous quelque chose pour moi ?'],['QRV','Êtes-vous prêt ?'],['QRW','Prévenez ... de m’appeler'],['QRX','Rappeler à ...'],['QRY','Quel est mon tour ?'],['QRZ','Qui m’appelle ?'],['QSA','Force des signaux'],['QSB','Variation de force du signal'],['QSD','Manipulation défectueuse'],['QSG','Émettre ... télégrammes à la fois'],['QSK','Puis-je vous entendre entre mes signaux ?'],['QSL','Confirmez réception'],['QSM','Répéter le dernier message'],['QSN','M’avez-vous entendu ?'],['QSO','Communication directe'],['QSP','Relayer un message'],['QSR','Répéter sur fréquence indiquée'],['QST','Message général à toutes stations'],['QSU','Émettre sur fréquence actuelle'],['QSV','Émettre une série de V'],['QSW','Émettre sur cette fréquence'],['QSX','Écouter sur autre fréquence'],['QSY','Changer de fréquence'],['QSZ','Émettre chaque mot/groupe deux fois'],['QTA','Annuler le message'],['QTB','Êtes-vous d’accord avec comptage de mots ?'],['QTC','Message(s) à transmettre'],['QTH','Position géographique'],['QTR','Heure exacte']
];
ob_start();
?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <p class="help">Liste étendue des Q-codes usuels en trafic radioamateur.</p>
  <div class="table-wrap mt-3">
    <table>
      <thead><tr><th>Code Q</th><th><?= e($labels[$locale] ?? $labels['fr']) ?></th></tr></thead>
      <tbody>
      <?php foreach ($qRows as $row): ?>
        <tr><td><?= e($row[0]) ?></td><td><?= e($row[1]) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
