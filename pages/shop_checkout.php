<?php
declare(strict_types=1);

$user = require_login();
$cart = shop_cart_state();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if ($cart['items'] === []) {
            throw new RuntimeException('Le panier est vide.');
        }
        $reference = place_shop_order((int) $user['id'], (string) ($_POST['payment_method'] ?? 'on_site'), (string) ($_POST['notes'] ?? ''));
        set_flash('success', 'Commande enregistrée sous la référence ' . $reference . '.');
        redirect('shop_checkout');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('shop_checkout');
    }
}

$orders = shop_recent_orders((int) $user['id'], 10);
set_page_meta([
    'title' => 'Commande boutique',
    'description' => 'Validation de commande boutique.',
    'robots' => 'noindex,nofollow',
]);

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1>Valider la commande</h1>
        <?php if ($cart['items'] === []): ?>
            <div class="empty-state"><p>Votre panier est vide.</p></div>
        <?php else: ?>
            <ul class="list-clean list-spaced">
                <?php foreach ($cart['items'] as $item): $product = $item['product']; ?>
                    <li><strong><?= e((string) $product['title']) ?></strong><span class="help"><?= (int) $item['quantity'] ?> × <?= e(format_price_eur((int) $product['price_cents'])) ?> — <?= e(format_price_eur((int) $item['line_total_cents'])) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Total : <?= e(format_price_eur((int) $cart['total_cents'])) ?></strong></p>
            <form method="post" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <label>Mode de paiement
                    <select name="payment_method">
                        <option value="on_site">Paiement sur place</option>
                        <option value="bank_transfer">Virement bancaire</option>
                    </select>
                </label>
                <label>Notes<textarea name="notes" rows="4" placeholder="Taille souhaitée, remarque logistique, retrait, etc."></textarea></label>
                <button class="button">Confirmer la commande</button>
            </form>
        <?php endif; ?>
    </section>
    <section class="card">
        <h2>Mes dernières commandes</h2>
        <?php if ($orders === []): ?>
            <p class="help">Aucune commande enregistrée.</p>
        <?php else: ?>
            <ul class="list-clean list-spaced">
                <?php foreach ($orders as $order): ?>
                    <li>
                        <strong><?= e((string) $order['reference_code']) ?></strong>
                        <span class="help"><?= e(shop_order_status_label((string) $order['status'])) ?> — <?= e(format_price_eur((int) $order['total_cents'])) ?> — <?= e(date('d/m/Y H:i', strtotime((string) $order['created_at']))) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Commande boutique');
