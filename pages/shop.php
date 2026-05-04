<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['meta_title' => 'Boutique club', 'meta_desc' => 'Produits du club ON4CRD : textile, accessoires, documentation et réservations.', 'badge' => 'Boutique club', 'hero_title' => 'Une boutique simple pour les produits du club, séparée des enchères.', 'hero_lead' => 'La première itération privilégie la clarté : catalogue, stock, panier, commande et retrait / paiement sur place ou par virement. Pas de surcharge fonctionnelle inutile.', 'catalog' => 'Catalogue', 'cart' => 'Panier', 'order' => 'Commande', 'view_cart' => 'Voir le panier', 'view_auctions' => 'Voir les enchères', 'categories' => 'Catégories', 'no_categories' => 'Aucune catégorie configurée.', 'filter_catalog' => 'Filtrer le catalogue', 'category' => 'Catégorie', 'all' => 'Toutes', 'search' => 'Recherche', 'search_placeholder' => 'Titre, résumé...', 'filter' => 'Filtrer', 'reset' => 'Réinitialiser', 'no_products' => 'Aucun produit ne correspond à votre filtre.', 'featured' => 'Mis en avant', 'default_summary' => 'Produit du club.', 'price' => 'Prix :', 'stock' => 'Stock :', 'view_product' => 'Voir le produit', 'page' => 'Page', 'previous' => 'Précédent', 'next' => 'Suivant', 'hero_featured_title' => 'Produit à la une', 'hero_featured_cta' => 'Voir le produit vedette', 'hero_no_featured' => 'Aucun produit vedette pour le moment.', 'layout_title' => 'Boutique'],
    'en' => ['meta_title' => 'Club shop', 'meta_desc' => 'ON4CRD club products: clothing, accessories, documentation and bookings.', 'badge' => 'Club shop', 'hero_title' => 'A simple shop for club products, separate from auctions.', 'hero_lead' => 'This first iteration focuses on clarity: catalog, stock, cart, order and on-site pickup/payment or bank transfer.', 'catalog' => 'Catalog', 'cart' => 'Cart', 'order' => 'Order', 'view_cart' => 'View cart', 'view_auctions' => 'View auctions', 'categories' => 'Categories', 'no_categories' => 'No categories configured.', 'filter_catalog' => 'Filter catalog', 'category' => 'Category', 'all' => 'All', 'search' => 'Search', 'search_placeholder' => 'Title, summary...', 'filter' => 'Filter', 'reset' => 'Reset', 'no_products' => 'No products match your filter.', 'featured' => 'Featured', 'default_summary' => 'Club product.', 'price' => 'Price:', 'stock' => 'Stock:', 'view_product' => 'View product', 'page' => 'Page', 'previous' => 'Previous', 'next' => 'Next', 'hero_featured_title' => 'Featured product', 'hero_featured_cta' => 'View featured product', 'hero_no_featured' => 'No featured product right now.', 'layout_title' => 'Shop'],
    'de' => ['meta_title' => 'Club-Shop', 'meta_desc' => 'ON4CRD-Clubprodukte: Textilien, Zubehör, Dokumentation und Reservierungen.', 'badge' => 'Club-Shop', 'hero_title' => 'Ein einfacher Shop für Clubprodukte, getrennt von Auktionen.', 'hero_lead' => 'Diese erste Version setzt auf Klarheit: Katalog, Bestand, Warenkorb, Bestellung und Abholung/Zahlung vor Ort oder per Überweisung.', 'catalog' => 'Katalog', 'cart' => 'Warenkorb', 'order' => 'Bestellung', 'view_cart' => 'Warenkorb anzeigen', 'view_auctions' => 'Auktionen ansehen', 'categories' => 'Kategorien', 'no_categories' => 'Keine Kategorien konfiguriert.', 'filter_catalog' => 'Katalog filtern', 'category' => 'Kategorie', 'all' => 'Alle', 'search' => 'Suche', 'search_placeholder' => 'Titel, Zusammenfassung...', 'filter' => 'Filtern', 'reset' => 'Zurücksetzen', 'no_products' => 'Keine Produkte passen zu Ihrem Filter.', 'featured' => 'Hervorgehoben', 'default_summary' => 'Clubprodukt.', 'price' => 'Preis:', 'stock' => 'Bestand:', 'view_product' => 'Produkt ansehen', 'page' => 'Seite', 'previous' => 'Zurück', 'next' => 'Weiter', 'hero_featured_title' => 'Top-Produkt', 'hero_featured_cta' => 'Top-Produkt ansehen', 'hero_no_featured' => 'Derzeit kein Top-Produkt.', 'layout_title' => 'Shop'],
    'nl' => ['meta_title' => 'Clubwinkel', 'meta_desc' => 'ON4CRD-clubproducten: textiel, accessoires, documentatie en reservaties.', 'badge' => 'Clubwinkel', 'hero_title' => 'Een eenvoudige winkel voor clubproducten, apart van veilingen.', 'hero_lead' => 'Deze eerste versie focust op duidelijkheid: catalogus, voorraad, winkelwagen, bestelling en afhaling/betaling ter plaatse of via overschrijving.', 'catalog' => 'Catalogus', 'cart' => 'Winkelwagen', 'order' => 'Bestelling', 'view_cart' => 'Bekijk winkelwagen', 'view_auctions' => 'Bekijk veilingen', 'categories' => 'Categorieën', 'no_categories' => 'Geen categorieën geconfigureerd.', 'filter_catalog' => 'Catalogus filteren', 'category' => 'Categorie', 'all' => 'Alle', 'search' => 'Zoeken', 'search_placeholder' => 'Titel, samenvatting...', 'filter' => 'Filteren', 'reset' => 'Reset', 'no_products' => 'Geen producten komen overeen met je filter.', 'featured' => 'Uitgelicht', 'default_summary' => 'Clubproduct.', 'price' => 'Prijs:', 'stock' => 'Voorraad:', 'view_product' => 'Bekijk product', 'page' => 'Pagina', 'previous' => 'Vorige', 'next' => 'Volgende', 'hero_featured_title' => 'Uitgelicht product', 'hero_featured_cta' => 'Bekijk uitgelicht product', 'hero_no_featured' => 'Momenteel geen uitgelicht product.', 'layout_title' => 'Winkel'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

$categories = cache_remember('shop_categories_v1', 600, static fn(): array => shop_categories());
$selectedCategory = trim((string) ($_GET['category'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$allProducts = cache_remember('shop_public_products_v1', 120, static fn(): array => shop_public_products());
$products = $allProducts;

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
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$totalProducts = count($products);
$maxPage = max(1, (int) ceil($totalProducts / $perPage));
if ($page > $maxPage) {
    $page = $maxPage;
}
$offset = ($page - 1) * $perPage;
$pagedProducts = array_slice($products, $offset, $perPage);
$queryBase = [];
if ($selectedCategory !== '') {
    $queryBase['category'] = $selectedCategory;
}
if ($search !== '') {
    $queryBase['q'] = $search;
}


$featuredProduct = null;
foreach ($allProducts as $candidate) {
    if (!empty($candidate['is_featured'])) {
        $featuredProduct = $candidate;
        break;
    }
}

$cart = shop_cart_state();
set_page_meta([
    'title' => (string) $t['meta_title'],
    'description' => (string) $t['meta_desc'],
    'schema_type' => 'CollectionPage',
]);

ob_start();
?>
<section class="hero hero-home">
    <div class="card hero-copy">
        <div class="badge"><?= e((string) $t['badge']) ?></div>
        <h1><?= e((string) $t['hero_title']) ?></h1>
        <p class="hero-lead"><?= e((string) $t['hero_lead']) ?></p>
        <div class="pill-row">
            <span class="pill"><?= e((string) $t['catalog']) ?></span>
            <span class="pill"><?= e((string) $t['cart']) ?></span>
            <span class="pill"><?= e((string) $t['order']) ?></span>
        </div>
        <div class="actions">
            <a class="button" href="<?= e(route_url('shop_cart')) ?>"><?= e((string) $t['view_cart']) ?> (<?= count($cart['items']) ?>)</a>
            <?php if (module_enabled('auctions')): ?><a class="button secondary" href="<?= e(route_url('auctions')) ?>"><?= e((string) $t['view_auctions']) ?></a><?php endif; ?>
        </div>
    </div>
    <aside class="hero-panel">
        <h2><?= e((string) $t['hero_featured_title']) ?></h2>
        <?php if ($featuredProduct !== null): ?>
            <article class="card feature-card">
                <h3><?= e((string) $featuredProduct['title']) ?></h3>
                <p><?= e((string) ($featuredProduct['summary'] ?: (string) $t['default_summary'])) ?></p>
                <a class="button" href="<?= e(route_url('shop_product', ['slug' => (string) $featuredProduct['slug']])) ?>"><?= e((string) $t['hero_featured_cta']) ?></a>
            </article>
        <?php else: ?>
            <p class="help"><?= e((string) $t['hero_no_featured']) ?></p>
        <?php endif; ?>

        <h2 class="mt-3"><?= e((string) $t['categories']) ?></h2>
        <?php if ($categories === []): ?>
            <p class="help"><?= e((string) $t['no_categories']) ?></p>
        <?php else: ?>
            <ul class="feature-list compact-feature-list">
                <?php foreach ($categories as $category): ?>
                    <li><strong><?= e((string) $category['name']) ?></strong><span><?= e((string) ($category['description'] ?: '')) ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </aside>
</section>

<section class="card stack mt-4">
    <h2><?= e((string) $t['filter_catalog']) ?></h2>
    <form method="get" action="<?= e(route_url('shop')) ?>" class="grid-3">
        <label><?= e((string) $t['category']) ?>
            <select name="category">
                <option value=""><?= e((string) $t['all']) ?></option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e((string) $category['slug']) ?>" <?= $selectedCategory === (string) $category['slug'] ? 'selected' : '' ?>><?= e((string) $category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><?= e((string) $t['search']) ?>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="<?= e((string) $t['search_placeholder']) ?>">
        </label>
        <div class="actions">
            <button class="button" type="submit"><?= e((string) $t['filter']) ?></button>
            <a class="button secondary" href="<?= e(route_url('shop')) ?>"><?= e((string) $t['reset']) ?></a>
        </div>
    </form>
</section>

<section class="inner-card mt-4">
    <?php if ($products === []): ?>
        <div class="card empty-state"><p><?= e((string) $t['no_products']) ?></p></div>
    <?php else: ?>
        <div class="grid-3">
            <?php foreach ($pagedProducts as $product): ?>
                <article class="card feature-card">
                    <?php if (!empty($product['category_name'])): ?><div class="badge muted"><?= e((string) $product['category_name']) ?></div><?php endif; ?>
                    <?php if (!empty($product['is_featured'])): ?><div class="badge"><?= e((string) $t['featured']) ?></div><?php endif; ?>
                    <?php if (!empty($product['image_url'])): ?><img src="<?= e((string) $product['image_url']) ?>" alt="<?= e((string) $product['title']) ?>" loading="lazy"><?php endif; ?>
                    <h2><?= e((string) $product['title']) ?></h2>
                    <p><?= e((string) ($product['summary'] ?: (string) $t['default_summary'])) ?></p>
                    <div class="catalog">
                        <span class="pill"><?= e((string) $t['price']) ?> <?= e(format_price_eur((int) $product['price_cents'])) ?></span>
                        <span class="pill"><?= e((string) $t['stock']) ?> <?= e(format_integer_or_unlimited($product['stock_qty'] !== null ? (int) $product['stock_qty'] : null)) ?></span>
                    </div>
                    <div class="actions">
                        <a class="button" href="<?= e(route_url('shop_product', ['slug' => (string) $product['slug']])) ?>"><?= e((string) $t['view_product']) ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if ($maxPage > 1): ?>
            <div class="actions mt-3">
                <?php if ($page > 1): ?>
                    <a class="button secondary" href="<?= e(route_url('shop', $queryBase + ['page' => $page - 1])) ?>"><?= e((string) $t['previous']) ?></a>
                <?php endif; ?>
                <span class="pill"><?= e((string) $t['page']) ?> <?= $page ?> / <?= $maxPage ?></span>
                <?php if ($page < $maxPage): ?>
                    <a class="button secondary" href="<?= e(route_url('shop', $queryBase + ['page' => $page + 1])) ?>"><?= e((string) $t['next']) ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout_title']);
