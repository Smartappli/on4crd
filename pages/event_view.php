<?php
declare(strict_types=1);

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '' || !table_exists('events')) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>Événement introuvable</h1><p>L\'événement demandé est indisponible.</p></div>', 'Événement');
    return;
}

$stmt = db()->prepare('SELECT * FROM events WHERE slug = ? AND status = "published" LIMIT 1');
$stmt->execute([$slug]);
$event = $stmt->fetch();

if (!is_array($event)) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>Événement introuvable</h1><p>L\'événement demandé est indisponible.</p></div>', 'Événement');
    return;
}

$startAt = new DateTimeImmutable((string) $event['start_at']);
$endAt = new DateTimeImmutable((string) $event['end_at']);
$summary = trim((string) ($event['summary'] ?? ''));
$description = trim((string) ($event['description'] ?? ''));
$location = trim((string) ($event['location'] ?? ''));
$externalUrl = trim((string) ($event['external_url'] ?? ''));

if ($summary === '') {
    $summary = 'Retrouvez toutes les informations utiles sur cet événement.';
}

ob_start();
?>
<article class="card events-single-card">
    <p><a href="<?= e(route_url('events')) ?>">← Retour au calendrier</a></p>
    <h1><?= e((string) $event['title']) ?></h1>
    <p class="help"><?= e($summary) ?></p>

    <dl class="events-single-meta">
        <dt>Début</dt><dd><?= e($startAt->format('d/m/Y H:i')) ?></dd>
        <dt>Fin</dt><dd><?= e($endAt->format('d/m/Y H:i')) ?></dd>
        <dt>Lieu</dt><dd><?= e($location !== '' ? $location : 'À confirmer') ?></dd>
    </dl>

    <?php if ($description !== ''): ?>
        <section class="events-single-description">
            <?= $description ?>
        </section>
    <?php endif; ?>

    <?php if ($externalUrl !== ''): ?>
        <p><a class="button" href="<?= e($externalUrl) ?>" target="_blank" rel="noopener noreferrer">Site de l'événement</a></p>
    <?php endif; ?>
</article>
<?php
echo render_layout((string) ob_get_clean(), (string) $event['title']);
