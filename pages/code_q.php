<?php
declare(strict_types=1);
require_login();
$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/code_q.php';
$i18n = i18n_expand_supported_locales($i18n);
$title = i18n_localized_value($i18n['title'] ?? [], $locale, 'fr');
$meaningLabel = i18n_localized_value($i18n['meaning_label'] ?? [], $locale, 'fr');
$qRows = [
['QRA','Nom de la station'],['QRB','Distance entre stations'],['QRG','Fréquence exacte'],['QRH','Variation de fréquence'],['QRI','Tonalité du signal'],['QRJ','Nombre de signaux reçus'],['QRK','Lisibilité du signal'],['QRL','Fréquence occupée'],['QRM','Interférences'],['QRN','Bruit atmosphérique'],['QRO','Augmenter la puissance'],['QRP','Réduire la puissance'],['QRQ','Émettre plus vite'],['QRR','Prêt pour trafic automatique'],['QRS','Émettre plus lentement'],['QRT','Cesser d’émettre'],['QRU','Avez-vous quelque chose pour moi ?'],['QRV','Êtes-vous prêt ?'],['QRW','Prévenez ... de m’appeler'],['QRX','Rappeler à ...'],['QRY','Quel est mon tour ?'],['QRZ','Qui m’appelle ?'],
['QSA','Force du signal'],['QSB','Variation du signal'],['QSD','Manipulation défectueuse'],['QSE','Décalage estimé de fréquence'],['QSF','Dois-je transmettre un message ?'],['QSG','Émettre x messages à la fois'],['QSH','Puis-je répéter ?'],['QSI','Je ne peux interrompre'],['QSJ','Montant à payer'],['QSK','Écoute entre signaux'],['QSL','Accusé de réception'],['QSM','Répéter dernier message'],['QSN','M’avez-vous entendu ?'],['QSO','Communication entre stations'],['QSP','Relayer un message'],['QSQ','Médecin à bord ?'],['QSR','Répéter sur fréquence'],['QSS','Quelle fréquence de travail ?'],['QST','Message général'],['QSU','Émettre sur fréquence actuelle'],['QSV','Série de V'],['QSW','Émettre sur cette fréquence'],['QSX','Écouter sur autre fréquence'],['QSY','Changer de fréquence'],['QSZ','Répéter chaque mot 2x'],['QTA','Annuler message'],['QTB','Compter les mots ?'],['QTC','Messages à transmettre'],['QTD','Jusqu’où va le trafic ?'],['QTE','Relèvement vrai'],['QTF','Position à partir relèvements'],['QTG','Émettre deux traits'],['QTH','Position géographique'],['QTI','Cap vrai'],['QTJ','Vitesse vraie'],['QTK','Vitesse appareil'],['QTL','Cap vrai à suivre'],['QTM','Vitesse indiquée'],['QTN','Départ à ...'],['QTO','Sortie de port à ...'],['QTP','Entrée de port à ...'],['QTQ','Communiquer via code international'],['QTR','Heure exacte'],['QTS','Conserver écoute sur ...'],['QTT','Signal d’identification'],['QTU','Heures d’exploitation'],['QTV','Veille pour moi sur ...'],['QTW','État des survivants'],['QTX','Station ouverte pour trafic'],['QTY','Route vers ma position'],['QTZ','Continuer recherche'],
];
ob_start();
?>
<section class="card">
  <h1><?= e($title) ?></h1>
  <p class="help"><?= e(i18n_localized_value($i18n['intro'] ?? [], $locale, 'fr')) ?></p>
  <div class="table-wrap mt-3"><table><thead><tr><th>Code Q</th><th><?= e($meaningLabel) ?></th></tr></thead><tbody>
  <?php foreach ($qRows as $row): ?>
    <tr><td><?= e($row[0]) ?></td><td><?= e($row[1]) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table></div>
</section>
<?php echo render_layout((string) ob_get_clean(), $title); ?>
