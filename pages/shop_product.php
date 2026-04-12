<?php
declare(strict_types=1);

$slug = trim((string) ($_GET['slug'] ?? ''));
$product = $slug !== '' ? shop_product_by_slug($slug) : null;
if ($product === null || !in_array((string) $product['status'], ['published', 'draft', 'archived'], true)) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>Produit introuvable</h1><p>Ce produit n’existe pas.</p></div>', 'Produit introuvable');
    return;
}
if ((string) $product['status'] !== 'published' && !has_permission('shop.manage')) {
    http_response_code(404);
    echo render_layout('<div class="card"><h1>Produit introuvable</h1><p>Ce produit n’est pas publié.</p></div>', 'Produit introuvable');
    return;
}

set_page_meta([
    'title' => (string) $product['title'],
    'description' => (string) ($product['summary'] ?: 'Produit du club ON4CRD.'),
    'schema_type' => 'Product',
]);

ob_start();
?>
<div class="split">
    <article class="card">
        <?php if (!empty($product['category_name'])): ?><div class="badge muted"><?= e((string) $product['category_name']) ?></div><?php endif; ?>
        <h1><?= e((string) $product['title']) ?></h1>
        <p class="hero-lead"><?= e((string) ($product['summary'] ?: 'Produit du club.')) ?></p>
        <div class="catalog">
            <span class="pill">Prix : <?= e(format_price_eur((int) $product['price_cents'])) ?></span>
            <span class="pill">Stock : <?= e(format_integer_or_unlimited($product['stock_qty'] !== null ? (int) $product['stock_qty'] : null)) ?></span>
            <span class="pill">Statut : <?= e(shop_status_label((string) $product['status'])) ?></span>
        </div>
        <div class="inner-card"><?= sanitize_rich_html((string) ($product['description'] ?? '')) ?></div>
    </article>
    <aside class="card">
        <h2>Commander</h2>
        <form method="post" action="<?= e(route_url('shop_cart')) ?>" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <label>Quantité
                <input type="number" min="1" max="<?= e((string) max(1, (int) ($product['stock_qty'] ?? 99))) ?>" name="quantity" value="1">
            </label>
            <button class="button">Ajouter au panier</button>
        </form>
        <div class="actions">
            <a class="button secondary" href="<?= e(route_url('shop_cart')) ?>">Voir le panier</a>
            <a class="button secondary" href="<?= e(route_url('shop')) ?>">Retour boutique</a>
        </div>
    </aside>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $product['title']);
