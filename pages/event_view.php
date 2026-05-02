<?php
declare(strict_types=1);

$locale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
$i18n = [
    'fr' => ['not_found' => 'Événement introuvable', 'not_found_msg' => "L'événement demandé est indisponible.", 'back' => '← Retour au calendrier', 'summary_fallback' => 'Retrouvez toutes les informations utiles sur cet événement.', 'start' => 'Début', 'end' => 'Fin', 'location' => 'Lieu', 'tbd' => 'À confirmer', 'site' => "Site de l'événement", 'title' => 'Événement'],
    'en' => ['not_found' => 'Event not found', 'not_found_msg' => 'The requested event is unavailable.', 'back' => '← Back to calendar', 'summary_fallback' => 'Find all useful information about this event.', 'start' => 'Start', 'end' => 'End', 'location' => 'Location', 'tbd' => 'To be confirmed', 'site' => 'Event website', 'title' => 'Event'],
    'de' => ['not_found' => 'Veranstaltung nicht gefunden', 'not_found_msg' => 'Die angeforderte Veranstaltung ist nicht verfügbar.', 'back' => '← Zurück zum Kalender', 'summary_fallback' => 'Hier finden Sie alle wichtigen Informationen zu dieser Veranstaltung.', 'start' => 'Beginn', 'end' => 'Ende', 'location' => 'Ort', 'tbd' => 'Noch offen', 'site' => 'Veranstaltungsseite', 'title' => 'Veranstaltung'],
    'nl' => ['not_found' => 'Evenement niet gevonden', 'not_found_msg' => 'Het gevraagde evenement is niet beschikbaar.', 'back' => '← Terug naar kalender', 'summary_fallback' => 'Vind alle nuttige informatie over dit evenement.', 'start' => 'Start', 'end' => 'Einde', 'location' => 'Locatie', 'tbd' => 'Nog te bevestigen', 'site' => 'Evenementwebsite', 'title' => 'Evenement'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '' || !table_exists('events')) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e($t['not_found']) . '</h1><p>' . e($t['not_found_msg']) . '</p></div>', $t['title']);
    return;
}

$stmt = db()->prepare('SELECT * FROM events WHERE slug = ? AND status = "published" LIMIT 1');
$stmt->execute([$slug]);
$event = $stmt->fetch();

if (!is_array($event)) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e($t['not_found']) . '</h1><p>' . e($t['not_found_msg']) . '</p></div>', $t['title']);
    return;
}

$startAt = new DateTimeImmutable((string) $event['start_at']);
$endAt = new DateTimeImmutable((string) $event['end_at']);
$summary = trim((string) ($event['summary'] ?? ''));
$description = trim((string) ($event['description'] ?? ''));
$location = trim((string) ($event['location'] ?? ''));
$externalUrl = trim((string) ($event['external_url'] ?? ''));

if ($summary === '') {
    $summary = $t['summary_fallback'];
}

ob_start();
?>
<article class="card events-single-card">
    <p><a href="<?= e(route_url('events')) ?>"><?= e($t['back']) ?></a></p>
    <h1><?= e((string) $event['title']) ?></h1>
    <p class="help"><?= e($summary) ?></p>

    <dl class="events-single-meta">
        <dt><?= e($t['start']) ?></dt><dd><?= e($startAt->format('d/m/Y H:i')) ?></dd>
        <dt><?= e($t['end']) ?></dt><dd><?= e($endAt->format('d/m/Y H:i')) ?></dd>
        <dt><?= e($t['location']) ?></dt><dd><?= e($location !== '' ? $location : $t['tbd']) ?></dd>
    </dl>

    <?php if ($description !== ''): ?>
        <section class="events-single-description">
            <?= $description ?>
        </section>
    <?php endif; ?>

    <?php if ($externalUrl !== ''): ?>
        <p><a class="button" href="<?= e($externalUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($t['site']) ?></a></p>
    <?php endif; ?>
</article>
<?php
echo render_layout((string) ob_get_clean(), (string) $event['title']);
