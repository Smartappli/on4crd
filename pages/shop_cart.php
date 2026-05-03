<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['added' => 'Produit ajouté au panier.', 'updated' => 'Panier mis à jour.', 'removed' => 'Produit retiré du panier.', 'cleared' => 'Panier vidé.', 'meta_title' => 'Panier boutique', 'meta_desc' => 'Panier de la boutique du club.', 'title' => 'Panier', 'back_shop' => 'Retour boutique', 'empty' => 'Votre panier est vide.', 'product' => 'Produit', 'unit_price' => 'Prix unitaire', 'quantity' => 'Quantité', 'total' => 'Total', 'action' => 'Action', 'update_action' => 'Mettre à jour', 'remove' => 'Supprimer', 'summary' => 'Résumé', 'checkout' => 'Passer commande', 'clear_cart' => 'Vider le panier', 'layout_title' => 'Panier'],
    'en' => ['added' => 'Product added to cart.', 'updated' => 'Cart updated.', 'removed' => 'Product removed from cart.', 'cleared' => 'Cart cleared.', 'meta_title' => 'Shop cart', 'meta_desc' => 'Club shop cart.', 'title' => 'Cart', 'back_shop' => 'Back to shop', 'empty' => 'Your cart is empty.', 'product' => 'Product', 'unit_price' => 'Unit price', 'quantity' => 'Quantity', 'total' => 'Total', 'action' => 'Action', 'update_action' => 'Update', 'remove' => 'Remove', 'summary' => 'Summary', 'checkout' => 'Checkout', 'clear_cart' => 'Clear cart', 'layout_title' => 'Cart'],
    'de' => ['added' => 'Produkt zum Warenkorb hinzugefügt.', 'updated' => 'Warenkorb aktualisiert.', 'removed' => 'Produkt aus dem Warenkorb entfernt.', 'cleared' => 'Warenkorb geleert.', 'meta_title' => 'Warenkorb', 'meta_desc' => 'Warenkorb des Club-Shops.', 'title' => 'Warenkorb', 'back_shop' => 'Zurück zum Shop', 'empty' => 'Ihr Warenkorb ist leer.', 'product' => 'Produkt', 'unit_price' => 'Stückpreis', 'quantity' => 'Menge', 'total' => 'Gesamt', 'action' => 'Aktion', 'update_action' => 'Aktualisieren', 'remove' => 'Entfernen', 'summary' => 'Zusammenfassung', 'checkout' => 'Bestellung aufgeben', 'clear_cart' => 'Warenkorb leeren', 'layout_title' => 'Warenkorb'],
    'nl' => ['added' => 'Product toegevoegd aan winkelwagen.', 'updated' => 'Winkelwagen bijgewerkt.', 'removed' => 'Product verwijderd uit winkelwagen.', 'cleared' => 'Winkelwagen leeggemaakt.', 'meta_title' => 'Winkelwagen', 'meta_desc' => 'Winkelwagen van de clubwinkel.', 'title' => 'Winkelwagen', 'back_shop' => 'Terug naar winkel', 'empty' => 'Je winkelwagen is leeg.', 'product' => 'Product', 'unit_price' => 'Stukprijs', 'quantity' => 'Aantal', 'total' => 'Totaal', 'action' => 'Actie', 'update_action' => 'Bijwerken', 'remove' => 'Verwijderen', 'summary' => 'Samenvatting', 'checkout' => 'Bestelling plaatsen', 'clear_cart' => 'Winkelwagen legen', 'layout_title' => 'Winkelwagen'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        if ($action === 'add') {
            shop_cart_add($productId, $quantity);
            set_flash('success', (string) $t['added']);
        } elseif ($action === 'update') {
            shop_cart_update($productId, $quantity);
            set_flash('success', (string) $t['updated']);
        } elseif ($action === 'remove') {
            shop_cart_remove($productId);
            set_flash('success', (string) $t['removed']);
        } elseif ($action === 'clear') {
            shop_cart_clear();
            set_flash('success', (string) $t['cleared']);
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('shop_cart');
}

$cart = shop_cart_state();
set_page_meta([
    'title' => (string) $t['meta_title'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

ob_start();
?>
<div class="split">
    <section class="card">
        <div class="row-between">
            <h1><?= e((string) $t['title']) ?></h1>
            <div class="actions">
                <a class="button secondary" href="<?= e(route_url('shop')) ?>"><?= e((string) $t['back_shop']) ?></a>
            </div>
        </div>
        <?php if ($cart['items'] === []): ?>
            <div class="empty-state"><p><?= e((string) $t['empty']) ?></p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th><?= e((string) $t['product']) ?></th><th><?= e((string) $t['unit_price']) ?></th><th><?= e((string) $t['quantity']) ?></th><th><?= e((string) $t['total']) ?></th><th><?= e((string) $t['action']) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($cart['items'] as $item): $product = $item['product']; ?>
                        <tr>
                            <td><strong><?= e((string) $product['title']) ?></strong><div class="help"><?= e((string) ($product['summary'] ?: '')) ?></div></td>
                            <td><?= e(format_price_eur((int) $product['price_cents'])) ?></td>
                            <td>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                    <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="1" <?= $product['stock_qty'] !== null ? "max=\"" . e((string) max(0, (int) $product['stock_qty'])) . "\"" : "" ?>>
                                    <button class="ghost"><?= e((string) $t['update_action']) ?></button>
                                </form>
                            </td>
                            <td><?= e(format_price_eur((int) $item['line_total_cents'])) ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                    <button class="ghost"><?= e((string) $t['remove']) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <aside class="card">
        <h2><?= e((string) $t['summary']) ?></h2>
        <p><strong><?= e((string) $t['total']) ?> : <?= e(format_price_eur((int) $cart['total_cents'])) ?></strong></p>
        <div class="actions">
            <?php if ($cart['items'] !== []): ?>
                <a class="button" href="<?= e(route_url('shop_checkout')) ?>"><?= e((string) $t['checkout']) ?></a>
                <form method="post">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="clear">
                    <button class="button secondary"><?= e((string) $t['clear_cart']) ?></button>
                </form>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout_title']);
