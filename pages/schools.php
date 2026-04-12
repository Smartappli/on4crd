<?php
declare(strict_types=1);

set_page_meta([
    'title' => 'Écoles et sensibilisation',
    'description' => 'Ateliers, démonstrations et ressources ON4CRD pour les écoles.',
    'schema_type' => 'WebPage',
]);

$resources = [
    ['title' => 'Découvrir la radio', 'text' => 'Présentation adaptée aux écoles et activités d’initiation.'],
    ['title' => 'Atelier antennes', 'text' => 'Explications concrètes sur la propagation, les antennes et la sécurité.'],
    ['title' => 'Numérique et expérimentations', 'text' => 'Découverte de la transmission numérique, APRS, satellites et objets connectés.'],
];

ob_start();
?>
<section class="hero hero-home">
    <div class="card hero-copy">
        <div class="badge">Pédagogie</div>
        <h1>Le radioamateurisme expliqué aux écoles, clairement et concrètement.</h1>
        <p class="hero-lead">Le club peut proposer des animations adaptées au niveau des élèves : découverte de la radio, démonstrations de liaisons, sécurité, électronique simple et ouverture sur les métiers techniques.</p>
        <div class="pill-row">
            <span class="pill">Découverte</span>
            <span class="pill">Démonstrations</span>
            <span class="pill">Technique</span>
        </div>
    </div>
    <aside class="hero-panel">
        <h2>Formats possibles</h2>
        <ul class="feature-list compact-feature-list">
            <li><strong>En classe</strong><span>Présentation, matériel et échanges.</span></li>
            <li><strong>Au club</strong><span>Visite, station, antennes et atelier.</span></li>
            <li><strong>Événementiel</strong><span>Stand, démonstration publique et sensibilisation.</span></li>
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
echo render_layout((string) ob_get_clean(), 'Écoles');
