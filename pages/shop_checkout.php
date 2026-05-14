<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['cart_empty' => 'Le panier est vide.', 'order_saved' => 'Commande enregistrée sous la référence ', 'meta_title' => 'Commande boutique', 'meta_desc' => 'Validation de commande boutique.', 'validate_order' => 'Valider la commande', 'your_cart_empty' => 'Votre panier est vide.', 'total' => 'Total :', 'payment_method' => 'Mode de paiement', 'on_site' => 'Paiement sur place', 'bank_transfer' => 'Virement bancaire', 'notes' => 'Notes', 'notes_placeholder' => 'Taille souhaitée, remarque logistique, retrait, etc.', 'confirm_order' => 'Confirmer la commande', 'recent_orders' => 'Mes dernières commandes', 'no_orders' => 'Aucune commande enregistrée.'],
    'en' => ['cart_empty' => 'Cart is empty.', 'order_saved' => 'Order saved with reference ', 'meta_title' => 'Shop checkout', 'meta_desc' => 'Shop order confirmation.', 'validate_order' => 'Confirm order', 'your_cart_empty' => 'Your cart is empty.', 'total' => 'Total:', 'payment_method' => 'Payment method', 'on_site' => 'Pay on site', 'bank_transfer' => 'Bank transfer', 'notes' => 'Notes', 'notes_placeholder' => 'Preferred size, logistics note, pickup details, etc.', 'confirm_order' => 'Confirm order', 'recent_orders' => 'My recent orders', 'no_orders' => 'No orders recorded.'],
    'de' => ['cart_empty' => 'Warenkorb ist leer.', 'order_saved' => 'Bestellung mit Referenz gespeichert ', 'meta_title' => 'Shop-Bestellung', 'meta_desc' => 'Bestätigung der Shop-Bestellung.', 'validate_order' => 'Bestellung bestätigen', 'your_cart_empty' => 'Ihr Warenkorb ist leer.', 'total' => 'Gesamt:', 'payment_method' => 'Zahlungsart', 'on_site' => 'Zahlung vor Ort', 'bank_transfer' => 'Banküberweisung', 'notes' => 'Notizen', 'notes_placeholder' => 'Gewünschte Größe, Logistikhinweis, Abholung usw.', 'confirm_order' => 'Bestellung bestätigen', 'recent_orders' => 'Meine letzten Bestellungen', 'no_orders' => 'Keine Bestellungen vorhanden.'],
    'es' => ['cart_empty' => 'El carrito está vacío.', 'order_saved' => 'Pedido registrado con la referencia ', 'meta_title' => 'Pedido tienda', 'meta_desc' => 'Validación de pedido de la tienda.', 'validate_order' => 'Validar pedido', 'your_cart_empty' => 'Su carrito está vacío.', 'total' => 'Total:', 'payment_method' => 'Método de pago', 'on_site' => 'Pago en el lugar', 'bank_transfer' => 'Transferencia bancaria', 'notes' => 'Notas', 'notes_placeholder' => 'Talla deseada, observación logística, recogida, etc.', 'confirm_order' => 'Confirmar pedido', 'recent_orders' => 'Mis pedidos recientes', 'no_orders' => 'No hay pedidos registrados.'],
    'it' => ['cart_empty' => 'Il carrello è vuoto.', 'order_saved' => 'Ordine registrato con riferimento ', 'meta_title' => 'Ordine negozio', 'meta_desc' => 'Conferma ordine del negozio.', 'validate_order' => 'Conferma ordine', 'your_cart_empty' => 'Il tuo carrello è vuoto.', 'total' => 'Totale:', 'payment_method' => 'Metodo di pagamento', 'on_site' => 'Pagamento in sede', 'bank_transfer' => 'Bonifico bancario', 'notes' => 'Note', 'notes_placeholder' => 'Taglia desiderata, nota logistica, ritiro, ecc.', 'confirm_order' => 'Conferma ordine', 'recent_orders' => 'I miei ordini recenti', 'no_orders' => 'Nessun ordine registrato.'],
    'pt' => ['cart_empty' => 'O carrinho está vazio.', 'order_saved' => 'Encomenda registada com a referência ', 'meta_title' => 'Encomenda loja', 'meta_desc' => 'Validação de encomenda da loja.', 'validate_order' => 'Validar encomenda', 'your_cart_empty' => 'O seu carrinho está vazio.', 'total' => 'Total:', 'payment_method' => 'Método de pagamento', 'on_site' => 'Pagamento no local', 'bank_transfer' => 'Transferência bancária', 'notes' => 'Notas', 'notes_placeholder' => 'Tamanho pretendido, nota logística, recolha, etc.', 'confirm_order' => 'Confirmar encomenda', 'recent_orders' => 'As minhas encomendas recentes', 'no_orders' => 'Sem encomendas registadas.'],
    'nl' => ['cart_empty' => 'Winkelwagen is leeg.', 'order_saved' => 'Bestelling geregistreerd onder referentie ', 'meta_title' => 'Winkelbestelling', 'meta_desc' => 'Bevestiging van winkelbestelling.', 'validate_order' => 'Bestelling bevestigen', 'your_cart_empty' => 'Je winkelwagen is leeg.', 'total' => 'Totaal:', 'payment_method' => 'Betaalmethode', 'on_site' => 'Betaling ter plaatse', 'bank_transfer' => 'Bankoverschrijving', 'notes' => 'Notities', 'notes_placeholder' => 'Gewenste maat, logistieke opmerking, afhaling, enz.', 'confirm_order' => 'Bestelling bevestigen', 'recent_orders' => 'Mijn recente bestellingen', 'no_orders' => 'Geen bestellingen geregistreerd.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

$user = require_login();
$cart = shop_cart_state();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if ($cart['items'] === []) {
            throw new RuntimeException((string) $t['cart_empty']);
        }
        $reference = place_shop_order((int) $user['id'], (string) ($_POST['payment_method'] ?? 'on_site'), (string) ($_POST['notes'] ?? ''));
        set_flash('success', (string) $t['order_saved'] . $reference . '.');
        redirect('shop_checkout');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('shop_checkout');
    }
}

$orders = shop_recent_orders((int) $user['id'], 10);
set_page_meta([
    'title' => (string) $t['meta_title'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= e((string) $t['validate_order']) ?></h1>
        <?php if ($cart['items'] === []): ?>
            <div class="empty-state"><p><?= e((string) $t['your_cart_empty']) ?></p></div>
        <?php else: ?>
            <ul class="list-clean list-spaced">
                <?php foreach ($cart['items'] as $item): $product = $item['product']; ?>
                    <li><strong><?= e((string) $product['title']) ?></strong><span class="help"><?= (int) $item['quantity'] ?> × <?= e(format_price_eur((int) $product['price_cents'])) ?> — <?= e(format_price_eur((int) $item['line_total_cents'])) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <p><strong><?= e((string) $t['total']) ?> <?= e(format_price_eur((int) $cart['total_cents'])) ?></strong></p>
            <form method="post" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <label><?= e((string) $t['payment_method']) ?>
                    <select name="payment_method">
                        <option value="on_site"><?= e((string) $t['on_site']) ?></option>
                        <option value="bank_transfer"><?= e((string) $t['bank_transfer']) ?></option>
                    </select>
                </label>
                <label><?= e((string) $t['notes']) ?><textarea name="notes" rows="4" placeholder="<?= e((string) $t['notes_placeholder']) ?>"></textarea></label>
                <button class="button"><?= e((string) $t['confirm_order']) ?></button>
            </form>
        <?php endif; ?>
    </section>
    <section class="card">
        <h2><?= e((string) $t['recent_orders']) ?></h2>
        <?php if ($orders === []): ?>
            <p class="help"><?= e((string) $t['no_orders']) ?></p>
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
echo render_layout((string) ob_get_clean(), (string) $t['meta_title']);
