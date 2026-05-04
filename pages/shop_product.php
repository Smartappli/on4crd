<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'Produit introuvable', 'not_exists' => 'Ce produit n’existe pas.', 'not_published' => 'Ce produit n’est pas publié.', 'meta_desc' => 'Produit du club ON4CRD.', 'summary_default' => 'Produit du club.', 'price' => 'Prix :', 'stock' => 'Stock :', 'status' => 'Statut :', 'order' => 'Commander', 'out_of_stock' => 'Rupture de stock momentanée.', 'quantity' => 'Quantité', 'add_to_cart' => 'Ajouter au panier', 'view_cart' => 'Voir le panier', 'back_shop' => 'Retour boutique'],
    'en' => ['not_found' => 'Product not found', 'not_exists' => 'This product does not exist.', 'not_published' => 'This product is not published.', 'meta_desc' => 'ON4CRD club product.', 'summary_default' => 'Club product.', 'price' => 'Price:', 'stock' => 'Stock:', 'status' => 'Status:', 'order' => 'Order', 'out_of_stock' => 'Temporarily out of stock.', 'quantity' => 'Quantity', 'add_to_cart' => 'Add to cart', 'view_cart' => 'View cart', 'back_shop' => 'Back to shop'],
    'de' => ['not_found' => 'Produkt nicht gefunden', 'not_exists' => 'Dieses Produkt existiert nicht.', 'not_published' => 'Dieses Produkt ist nicht veröffentlicht.', 'meta_desc' => 'Produkt des ON4CRD-Clubs.', 'summary_default' => 'Clubprodukt.', 'price' => 'Preis:', 'stock' => 'Bestand:', 'status' => 'Status:', 'order' => 'Bestellen', 'out_of_stock' => 'Vorübergehend nicht auf Lager.', 'quantity' => 'Menge', 'add_to_cart' => 'In den Warenkorb', 'view_cart' => 'Warenkorb anzeigen', 'back_shop' => 'Zurück zum Shop'],
    'nl' => ['not_found' => 'Product niet gevonden', 'not_exists' => 'Dit product bestaat niet.', 'not_published' => 'Dit product is niet gepubliceerd.', 'meta_desc' => 'Product van de ON4CRD-club.', 'summary_default' => 'Clubproduct.', 'price' => 'Prijs:', 'stock' => 'Voorraad:', 'status' => 'Status:', 'order' => 'Bestellen', 'out_of_stock' => 'Tijdelijk niet op voorraad.', 'quantity' => 'Aantal', 'add_to_cart' => 'Toevoegen aan winkelwagen', 'view_cart' => 'Bekijk winkelwagen', 'back_shop' => 'Terug naar winkel'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

$slug = trim((string) ($_GET['slug'] ?? ''));
$product = $slug !== '' ? shop_product_by_slug($slug) : null;
if ($product === null || !in_array((string) $product['status'], ['published', 'draft', 'archived'], true)) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['not_found']) . '</h1><p>' . e((string) $t['not_exists']) . '</p></div>', (string) $t['not_found']);
    return;
}
if ((string) $product['status'] !== 'published' && !has_permission('shop.manage')) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>' . e((string) $t['not_found']) . '</h1><p>' . e((string) $t['not_published']) . '</p></div>', (string) $t['not_found']);
    return;
}

set_page_meta([
    'title' => (string) $product['title'],
    'description' => (string) ($product['summary'] ?: (string) $t['meta_desc']),
    'schema_type' => 'Product',
]);

ob_start();
?>
<div class="split">
    <article class="card">
        <?php if (!empty($product['category_name'])): ?><div class="badge muted"><?= e((string) $product['category_name']) ?></div><?php endif; ?>
        <h1><?= e((string) $product['title']) ?></h1>
        <p class="hero-lead"><?= e((string) ($product['summary'] ?: (string) $t['summary_default'])) ?></p>
        <?php if (!empty($product['image_url'])): ?><img src="<?= e((string) $product['image_url']) ?>" alt="<?= e((string) $product['title']) ?>" loading="lazy"><?php endif; ?>
        <div class="catalog">
            <span class="pill"><?= e((string) $t['price']) ?> <?= e(format_price_eur((int) $product['price_cents'])) ?></span>
            <span class="pill"><?= e((string) $t['stock']) ?> <?= e(format_integer_or_unlimited($product['stock_qty'] !== null ? (int) $product['stock_qty'] : null)) ?></span>
            <span class="pill"><?= e((string) $t['status']) ?> <?= e(shop_status_label((string) $product['status'])) ?></span>
        </div>
        <div class="inner-card"><?= sanitize_rich_html((string) ($product['description'] ?? '')) ?></div>
    </article>
    <aside class="card">
        <h2><?= e((string) $t['order']) ?></h2>
        <?php $availableStock = $product['stock_qty'] !== null ? max(0, (int) $product['stock_qty']) : null; ?>
        <?php if ($availableStock === 0): ?>
            <p class="help"><?= e((string) $t['out_of_stock']) ?></p>
        <?php else: ?>
        <form method="post" action="<?= e(route_url('shop_cart')) ?>" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <label><?= e((string) $t['quantity']) ?>
                <input type="number" min="1" <?= $availableStock !== null ? 'max="' . e((string) $availableStock) . '"' : '' ?> name="quantity" value="1">
            </label>
            <button class="button"><?= e((string) $t['add_to_cart']) ?></button>
        </form>
        <?php endif; ?>
        <div class="actions">
            <a class="button secondary" href="<?= e(route_url('shop_cart')) ?>"><?= e((string) $t['view_cart']) ?></a>
            <a class="button secondary" href="<?= e(route_url('shop')) ?>"><?= e((string) $t['back_shop']) ?></a>
        </div>
    </aside>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $product['title']);
