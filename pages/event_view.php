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

$eventUrl = route_url_with_locale('event_view', $locale, ['slug' => $slug]);
$eventStatus = $endAt < new DateTimeImmutable('now') ? 'https://schema.org/EventCompleted' : 'https://schema.org/EventScheduled';
$eventJsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Event',
    'name' => (string) $event['title'],
    'description' => $summary,
    'url' => $eventUrl,
    'startDate' => $startAt->format(DATE_ATOM),
    'endDate' => $endAt->format(DATE_ATOM),
    'eventStatus' => $eventStatus,
    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
    'inLanguage' => $locale,
    'organizer' => [
        '@type' => 'Organization',
        'name' => 'Radio Club Durnal ON4CRD',
        'url' => route_url_with_locale('home', $locale),
    ],
];
if ($location !== '') {
    $eventJsonLd['location'] = [
        '@type' => 'Place',
        'name' => $location,
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $location,
            'addressCountry' => 'BE',
        ],
    ];
}

set_page_meta([
    'title' => (string) $event['title'],
    'description' => $summary,
    'ai_summary' => $summary,
    'canonical' => $eventUrl,
    'schema_type' => 'Event',
    'modified_time' => !empty($event['updated_at']) ? date('c', strtotime((string) $event['updated_at'])) : $startAt->format(DATE_ATOM),
    'section' => (string) ($t['title'] ?? 'Evenement'),
    'tags' => ['ON4CRD', 'Radio Club Durnal', 'evenement radioamateur'],
    'keywords' => ['ON4CRD', 'Radio Club Durnal', 'evenement radioamateur', 'agenda radioamateur', 'Namur'],
    'citation_author' => 'Radio Club Durnal ON4CRD',
    'json_ld' => $eventJsonLd,
]);

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
