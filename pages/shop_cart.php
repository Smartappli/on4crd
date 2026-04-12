<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        if ($action === 'add') {
            shop_cart_add($productId, $quantity);
            set_flash('success', 'Produit ajouté au panier.');
        } elseif ($action === 'update') {
            shop_cart_update($productId, $quantity);
            set_flash('success', 'Panier mis à jour.');
        } elseif ($action === 'remove') {
            shop_cart_remove($productId);
            set_flash('success', 'Produit retiré du panier.');
        } elseif ($action === 'clear') {
            shop_cart_clear();
            set_flash('success', 'Panier vidé.');
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('shop_cart');
}

$cart = shop_cart_state();
set_page_meta([
    'title' => 'Panier boutique',
    'description' => 'Panier de la boutique du club.',
    'robots' => 'noindex,nofollow',
]);

ob_start();
?>
<div class="split">
    <section class="card">
        <div class="row-between">
            <h1>Panier</h1>
            <div class="actions">
                <a class="button secondary" href="<?= e(route_url('shop')) ?>">Retour boutique</a>
            </div>
        </div>
        <?php if ($cart['items'] === []): ?>
            <div class="empty-state"><p>Votre panier est vide.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Produit</th><th>Prix unitaire</th><th>Quantité</th><th>Total</th><th>Action</th></tr></thead>
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
                                    <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="1" max="<?= e((string) max(1, (int) ($product['stock_qty'] ?? 99))) ?>">
                                    <button class="ghost">Mettre à jour</button>
                                </form>
                            </td>
                            <td><?= e(format_price_eur((int) $item['line_total_cents'])) ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                    <button class="ghost">Supprimer</button>
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
        <h2>Résumé</h2>
        <p><strong>Total : <?= e(format_price_eur((int) $cart['total_cents'])) ?></strong></p>
        <div class="actions">
            <?php if ($cart['items'] !== []): ?>
                <a class="button" href="<?= e(route_url('shop_checkout')) ?>">Passer commande</a>
                <form method="post">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="clear">
                    <button class="button secondary">Vider le panier</button>
                </form>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Panier');
