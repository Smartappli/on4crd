<?php
declare(strict_types=1);

$lots = cache_remember('auction_public_lots_60_v1', 60, static fn(): array => auction_public_lots(60));
set_page_meta([
    'title' => 'Enchères',
    'description' => 'Lots proposés à l’enchère par le club.',
]);

ob_start();
?>
<section class="hero hero-home">
    <div class="card hero-copy">
        <div class="badge">Enchères en ligne</div>
        <h1>Des ventes ponctuelles, encadrées et séparées de la boutique.</h1>
        <p class="hero-lead">Le module d’enchères est pensé pour les lots spéciaux : matériel, dons, objets vintage ou séries limitées. Les règles restent simples : prix de départ, pas minimum, horaire de fin et prolongation de quelques minutes en cas d’offre tardive.</p>
        <div class="pill-row">
            <span class="pill">Lots programmés</span>
            <span class="pill">Pas minimal</span>
            <span class="pill">Anti-sniping</span>
        </div>
    </div>
    <aside class="hero-panel">
        <h2>Accès</h2>
        <p>La consultation est publique. Pour enchérir, il faut être membre connecté.</p>
        <div class="actions">
            <?php if (!current_user()): ?><a class="button" href="<?= e(route_url('login')) ?>">Connexion membre</a><?php endif; ?>
            <?php if (module_enabled('shop')): ?><a class="button secondary" href="<?= e(route_url('shop')) ?>">Voir la boutique</a><?php endif; ?>
        </div>
    </aside>
</section>

<section class="inner-card">
    <?php if ($lots === []): ?>
        <div class="card empty-state"><p>Aucun lot public pour le moment.</p></div>
    <?php else: ?>
        <div class="grid-3">
            <?php foreach ($lots as $lot): ?>
                <?php $runtime = auction_runtime_status($lot); ?>
                <article class="card feature-card">
                    <div class="section-header">
                        <div>
                            <div class="badge <?= $runtime === 'closed' ? 'muted' : '' ?>"><?= e(auction_status_label($runtime)) ?></div>
                            <h2><?= e((string) $lot['title']) ?></h2>
                        </div>
                        <strong class="price-tag"><?= e(format_price_eur(max((int) $lot['current_price_cents'], (int) $lot['starting_price_cents']))) ?></strong>
                    </div>
                    <p><?= e((string) ($lot['summary'] ?: 'Lot d’enchère du club.')) ?></p>
                    <ul class="list-clean list-spaced">
                        <li><span class="help">Début : <?= e(date('d/m/Y H:i', strtotime((string) $lot['starts_at']))) ?></span></li>
                        <li><span class="help">Fin : <?= e(date('d/m/Y H:i', strtotime((string) $lot['ends_at']))) ?></span></li>
                        <li><span class="help">Pas : <?= e(format_price_eur((int) $lot['min_increment_cents'])) ?></span></li>
                    </ul>
                    <div class="actions">
                        <a class="button" href="<?= e(route_url('auction_view', ['slug' => (string) $lot['slug']])) ?>">Voir le lot</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), 'Enchères');
