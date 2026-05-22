<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = i18n_domain_messages('event_view');
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, $key);
}

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
