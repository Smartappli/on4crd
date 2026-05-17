<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['title' => 'Petites annonces', 'lead' => 'Accédez au module annonces du club.'],
    'en' => ['title' => 'Classifieds', 'lead' => 'Access the club classifieds module.'],
    'de' => ['title' => 'Kleinanzeigen', 'lead' => 'Zugriff auf das Kleinanzeigen-Modul des Clubs.'],
    'nl' => ['title' => 'Kleine advertenties', 'lead' => 'Toegang tot de kleine-advertentiemodule van de club.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];
set_page_meta(['title' => (string) $t['title'], 'description' => (string) $t['lead']]);
ob_start();
?>
<section class="card">
  <h1><?= e((string) $t['title']) ?></h1>
  <p><?= e((string) $t['lead']) ?></p>
</section>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['title']);
