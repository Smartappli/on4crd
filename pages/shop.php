<?php
declare(strict_types=1);

$products = cache_remember('shop_public_products_v1', 120, static fn(): array => shop_public_products());
$categories = cache_remember('shop_categories_v1', 600, static fn(): array => shop_categories());
$cart = shop_cart_state();
set_page_meta([
    'title' => 'Boutique club',
    'description' => 'Produits du club ON4CRD : textile, accessoires, documentation et réservations.',
    'schema_type' => 'CollectionPage',
]);

ob_start();
?>
<section class="hero hero-home">
    <div class="card hero-copy">
        <div class="badge">Boutique club</div>
        <h1>Une boutique simple pour les produits du club, séparée des enchères.</h1>
        <p class="hero-lead">La première itération privilégie la clarté : catalogue, stock, panier, commande et retrait / paiement sur place ou par virement. Pas de surcharge fonctionnelle inutile.</p>
        <div class="pill-row">
            <span class="pill">Catalogue</span>
            <span class="pill">Panier</span>
            <span class="pill">Commande</span>
        </div>
        <div class="actions">
            <a class="button" href="<?= e(route_url('shop_cart')) ?>">Voir le panier (<?= count($cart['items']) ?>)</a>
            <?php if (module_enabled('auctions')): ?><a class="button secondary" href="<?= e(route_url('auctions')) ?>">Voir les enchères</a><?php endif; ?>
        </div>
    </div>
    <aside class="hero-panel">
        <h2>Catégories</h2>
        <?php if ($categories === []): ?>
            <p class="help">Aucune catégorie configurée.</p>
        <?php else: ?>
            <ul class="feature-list compact-feature-list">
                <?php foreach ($categories as $category): ?>
                    <li><strong><?= e((string) $category['name']) ?></strong><span><?= e((string) ($category['description'] ?: '')) ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </aside>
</section>

<section class="inner-card">
    <?php if ($products === []): ?>
        <div class="card empty-state"><p>Aucun produit publié pour le moment.</p></div>
    <?php else: ?>
        <div class="grid-3">
            <?php foreach ($products as $product): ?>
                <article class="card feature-card">
                    <?php if (!empty($product['category_name'])): ?><div class="badge muted"><?= e((string) $product['category_name']) ?></div><?php endif; ?>
                    <h2><?= e((string) $product['title']) ?></h2>
                    <p><?= e((string) ($product['summary'] ?: 'Produit du club.')) ?></p>
                    <div class="catalog">
                        <span class="pill">Prix : <?= e(format_price_eur((int) $product['price_cents'])) ?></span>
                        <span class="pill">Stock : <?= e(format_integer_or_unlimited($product['stock_qty'] !== null ? (int) $product['stock_qty'] : null)) ?></span>
                    </div>
                    <div class="actions">
                        <a class="button" href="<?= e(route_url('shop_product', ['slug' => (string) $product['slug']])) ?>">Voir le produit</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), 'Boutique');
