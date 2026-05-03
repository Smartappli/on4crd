<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => [
        'meta_title' => 'Écoles et sensibilisation', 'meta_desc' => 'Ateliers, démonstrations et ressources ON4CRD pour les écoles.',
        'badge' => 'Pédagogie', 'title' => 'Le radioamateurisme expliqué aux écoles, clairement et concrètement.',
        'lead' => 'Le club peut proposer des animations adaptées au niveau des élèves : découverte de la radio, démonstrations de liaisons, sécurité, électronique simple et ouverture sur les métiers techniques.',
        'pill_discovery' => 'Découverte', 'pill_demos' => 'Démonstrations', 'pill_tech' => 'Technique',
        'formats' => 'Formats possibles',
        'class_title' => 'En classe', 'class_text' => 'Présentation, matériel et échanges.',
        'club_title' => 'Au club', 'club_text' => 'Visite, station, antennes et atelier.',
        'event_title' => 'Événementiel', 'event_text' => 'Stand, démonstration publique et sensibilisation.',
        'r1_t' => 'Découvrir la radio', 'r1_x' => 'Présentation adaptée aux écoles et activités d’initiation.',
        'r2_t' => 'Atelier antennes', 'r2_x' => 'Explications concrètes sur la propagation, les antennes et la sécurité.',
        'r3_t' => 'Numérique et expérimentations', 'r3_x' => 'Découverte de la transmission numérique, APRS, satellites et objets connectés.',
        'layout' => 'Écoles',
    ],
    'en' => [
        'meta_title' => 'Schools and outreach', 'meta_desc' => 'ON4CRD workshops, demonstrations and resources for schools.',
        'badge' => 'Education', 'title' => 'Amateur radio explained to schools, clearly and practically.',
        'lead' => 'The club can offer activities adapted to students\' level: radio discovery, communication demonstrations, safety, basic electronics, and insight into technical careers.',
        'pill_discovery' => 'Discovery', 'pill_demos' => 'Demonstrations', 'pill_tech' => 'Technology',
        'formats' => 'Available formats',
        'class_title' => 'In class', 'class_text' => 'Presentation, equipment, and discussion.',
        'club_title' => 'At the club', 'club_text' => 'Visit, station, antennas, and workshop.',
        'event_title' => 'Events', 'event_text' => 'Booth, public demonstration, and outreach.',
        'r1_t' => 'Discover radio', 'r1_x' => 'School-friendly introduction and beginner activities.',
        'r2_t' => 'Antenna workshop', 'r2_x' => 'Practical explanations about propagation, antennas, and safety.',
        'r3_t' => 'Digital and experimentation', 'r3_x' => 'Explore digital transmission, APRS, satellites, and connected devices.',
        'layout' => 'Schools',
    ],
    'de' => [
        'meta_title' => 'Schulen und Aufklärung', 'meta_desc' => 'ON4CRD-Workshops, Vorführungen und Ressourcen für Schulen.',
        'badge' => 'Pädagogik', 'title' => 'Amateurfunk für Schulen klar und praxisnah erklärt.',
        'lead' => 'Der Club kann Angebote passend zum Niveau der Schüler bereitstellen: Funk entdecken, Verbindungsdemos, Sicherheit, einfache Elektronik und Einblicke in technische Berufe.',
        'pill_discovery' => 'Entdecken', 'pill_demos' => 'Vorführungen', 'pill_tech' => 'Technik',
        'formats' => 'Mögliche Formate',
        'class_title' => 'Im Unterricht', 'class_text' => 'Präsentation, Material und Austausch.',
        'club_title' => 'Im Club', 'club_text' => 'Besuch, Funkstation, Antennen und Workshop.',
        'event_title' => 'Veranstaltungen', 'event_text' => 'Stand, öffentliche Vorführung und Aufklärung.',
        'r1_t' => 'Funk entdecken', 'r1_x' => 'Schulgerechte Einführung und Einstiegsaktivitäten.',
        'r2_t' => 'Antennen-Workshop', 'r2_x' => 'Konkrete Erklärungen zu Ausbreitung, Antennen und Sicherheit.',
        'r3_t' => 'Digital und Experimente', 'r3_x' => 'Einblick in digitale Übertragung, APRS, Satelliten und vernetzte Geräte.',
        'layout' => 'Schulen',
    ],
    'nl' => [
        'meta_title' => 'Scholen en sensibilisering', 'meta_desc' => 'ON4CRD-workshops, demonstraties en middelen voor scholen.',
        'badge' => 'Educatie', 'title' => 'Amateurradio helder en concreet uitgelegd aan scholen.',
        'lead' => 'De club kan activiteiten aanbieden aangepast aan het niveau van leerlingen: kennismaking met radio, verbindingsdemo\'s, veiligheid, eenvoudige elektronica en inkijk in technische beroepen.',
        'pill_discovery' => 'Ontdekking', 'pill_demos' => 'Demonstraties', 'pill_tech' => 'Techniek',
        'formats' => 'Mogelijke formats',
        'class_title' => 'In de klas', 'class_text' => 'Presentatie, materiaal en uitwisseling.',
        'club_title' => 'In de club', 'club_text' => 'Bezoek, station, antennes en workshop.',
        'event_title' => 'Evenementen', 'event_text' => 'Stand, publieke demonstratie en sensibilisering.',
        'r1_t' => 'Radio ontdekken', 'r1_x' => 'Schoolgerichte introductie en startactiviteiten.',
        'r2_t' => 'Antenneworkshop', 'r2_x' => 'Praktische uitleg over propagatie, antennes en veiligheid.',
        'r3_t' => 'Digitaal en experimenten', 'r3_x' => 'Ontdek digitale transmissie, APRS, satellieten en verbonden toestellen.',
        'layout' => 'Scholen',
    ],
];
$t = $i18n[$locale] ?? $i18n['fr'];

set_page_meta([
    'title' => (string) ($t['meta_title'] ?? $i18n['fr']['meta_title']),
    'description' => (string) ($t['meta_desc'] ?? $i18n['fr']['meta_desc']),
    'schema_type' => 'WebPage',
]);

$resources = [
    ['title' => (string) ($t['r1_t'] ?? ''), 'text' => (string) ($t['r1_x'] ?? '')],
    ['title' => (string) ($t['r2_t'] ?? ''), 'text' => (string) ($t['r2_x'] ?? '')],
    ['title' => (string) ($t['r3_t'] ?? ''), 'text' => (string) ($t['r3_x'] ?? '')],
];

ob_start();
?>
<section class="hero hero-home">
    <div class="card hero-copy">
        <div class="badge"><?= e((string) $t['badge']) ?></div>
        <h1><?= e((string) $t['title']) ?></h1>
        <p class="hero-lead"><?= e((string) $t['lead']) ?></p>
        <div class="pill-row">
            <span class="pill"><?= e((string) $t['pill_discovery']) ?></span>
            <span class="pill"><?= e((string) $t['pill_demos']) ?></span>
            <span class="pill"><?= e((string) $t['pill_tech']) ?></span>
        </div>
    </div>
    <aside class="hero-panel">
        <h2><?= e((string) $t['formats']) ?></h2>
        <ul class="feature-list compact-feature-list">
            <li><strong><?= e((string) $t['class_title']) ?></strong><span><?= e((string) $t['class_text']) ?></span></li>
            <li><strong><?= e((string) $t['club_title']) ?></strong><span><?= e((string) $t['club_text']) ?></span></li>
            <li><strong><?= e((string) $t['event_title']) ?></strong><span><?= e((string) $t['event_text']) ?></span></li>
        </ul>
    </aside>
</section>

<section class="grid-3 inner-card">
    <?php foreach ($resources as $resource): ?>
        <article class="card feature-card">
            <h2><?= e($resource['title']) ?></h2>
            <p><?= e($resource['text']) ?></p>
        </article>
    <?php endforeach; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) ($t['layout'] ?? $i18n['fr']['layout']));
