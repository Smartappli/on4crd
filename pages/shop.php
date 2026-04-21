<?php
declare(strict_types=1);

$categories = cache_remember('shop_categories_v1', 600, static fn(): array => shop_categories());
$selectedCategory = trim((string) ($_GET['category'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$products = cache_remember('shop_public_products_v1', 120, static fn(): array => shop_public_products());

if ($selectedCategory !== '') {
    $products = array_values(array_filter($products, static fn (array $product): bool => (string) ($product['category_slug'] ?? '') === $selectedCategory));
}
if ($search !== '') {
    $needle = mb_safe_strtolower($search);
    $products = array_values(array_filter($products, static function (array $product) use ($needle): bool {
        $haystack = mb_safe_strtolower(trim((string) (($product['title'] ?? '') . ' ' . ($product['summary'] ?? ''))));
        return $haystack !== '' && str_contains($haystack, $needle);
    }));
}

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

<section class="card stack">
    <h2>Filtrer le catalogue</h2>
    <form method="get" action="<?= e(route_url('shop')) ?>" class="grid-3">
        <label>Catégorie
            <select name="category">
                <option value="">Toutes</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e((string) $category['slug']) ?>" <?= $selectedCategory === (string) $category['slug'] ? 'selected' : '' ?>><?= e((string) $category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Recherche
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Titre, résumé...">
        </label>
        <div class="actions">
            <button class="button" type="submit">Filtrer</button>
            <a class="button secondary" href="<?= e(route_url('shop')) ?>">Réinitialiser</a>
        </div>
    </form>
</section>

<section class="inner-card">
    <?php if ($products === []): ?>
        <div class="card empty-state"><p>Aucun produit ne correspond à votre filtre.</p></div>
    <?php else: ?>
        <div class="grid-3">
            <?php foreach ($products as $product): ?>
                <article class="card feature-card">
                    <?php if (!empty($product['category_name'])): ?><div class="badge muted"><?= e((string) $product['category_name']) ?></div><?php endif; ?>
                    <?php if (!empty($product['is_featured'])): ?><div class="badge">Mis en avant</div><?php endif; ?>
                    <?php if (!empty($product['image_url'])): ?><img src="<?= e((string) $product['image_url']) ?>" alt="<?= e((string) $product['title']) ?>" loading="lazy"><?php endif; ?>
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
