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
    'ar' => ['cart_empty' => 'السلة فارغة.', 'order_saved' => 'تم تسجيل الطلب بالمرجع ', 'meta_title' => 'إتمام طلب المتجر', 'meta_desc' => 'تأكيد طلب متجر النادي.', 'validate_order' => 'تأكيد الطلب', 'your_cart_empty' => 'سلتك فارغة.', 'total' => 'الإجمالي:', 'payment_method' => 'طريقة الدفع', 'on_site' => 'الدفع في المكان', 'bank_transfer' => 'تحويل بنكي', 'notes' => 'ملاحظات', 'notes_placeholder' => 'المقاس المطلوب، ملاحظة لوجستية، تفاصيل الاستلام، إلخ.', 'confirm_order' => 'تأكيد الطلب', 'recent_orders' => 'طلباتي الأخيرة', 'no_orders' => 'لا توجد طلبات مسجلة.'],
    'hi' => ['cart_empty' => 'कार्ट खाली है।', 'order_saved' => 'ऑर्डर इस संदर्भ के साथ सहेजा गया ', 'meta_title' => 'शॉप चेकआउट', 'meta_desc' => 'क्लब शॉप ऑर्डर पुष्टि।', 'validate_order' => 'ऑर्डर की पुष्टि करें', 'your_cart_empty' => 'आपका कार्ट खाली है।', 'total' => 'कुल:', 'payment_method' => 'भुगतान विधि', 'on_site' => 'स्थान पर भुगतान', 'bank_transfer' => 'बैंक ट्रांसफर', 'notes' => 'नोट्स', 'notes_placeholder' => 'पसंदीदा आकार, लॉजिस्टिक्स नोट, पिकअप विवरण आदि।', 'confirm_order' => 'ऑर्डर की पुष्टि करें', 'recent_orders' => 'मेरे हाल के ऑर्डर', 'no_orders' => 'कोई ऑर्डर दर्ज नहीं है।'],
    'ja' => ['cart_empty' => 'カートは空です。', 'order_saved' => '注文を次の参照番号で保存しました ', 'meta_title' => 'ショップ注文', 'meta_desc' => 'クラブショップの注文確認。', 'validate_order' => '注文を確認', 'your_cart_empty' => 'カートは空です。', 'total' => '合計:', 'payment_method' => '支払い方法', 'on_site' => '現地支払い', 'bank_transfer' => '銀行振込', 'notes' => '備考', 'notes_placeholder' => '希望サイズ、物流メモ、受け取り情報など。', 'confirm_order' => '注文を確定', 'recent_orders' => '最近の注文', 'no_orders' => '注文履歴がありません。'],
    'zh' => ['cart_empty' => '购物车为空。', 'order_saved' => '订单已保存，参考号为 ', 'meta_title' => '商店结账', 'meta_desc' => '俱乐部商店订单确认。', 'validate_order' => '确认订单', 'your_cart_empty' => '您的购物车为空。', 'total' => '总计：', 'payment_method' => '支付方式', 'on_site' => '现场支付', 'bank_transfer' => '银行转账', 'notes' => '备注', 'notes_placeholder' => '期望尺码、物流备注、自提信息等。', 'confirm_order' => '确认订单', 'recent_orders' => '我的最近订单', 'no_orders' => '暂无订单记录。'],
    'bn' => ['cart_empty' => 'কার্ট খালি।', 'order_saved' => 'অর্ডার এই রেফারেন্সে সংরক্ষিত হয়েছে ', 'meta_title' => 'শপ চেকআউট', 'meta_desc' => 'ক্লাব শপ অর্ডার নিশ্চিতকরণ।', 'validate_order' => 'অর্ডার নিশ্চিত করুন', 'your_cart_empty' => 'আপনার কার্ট খালি।', 'total' => 'মোট:', 'payment_method' => 'পেমেন্ট পদ্ধতি', 'on_site' => 'স্থানে পেমেন্ট', 'bank_transfer' => 'ব্যাংক ট্রান্সফার', 'notes' => 'নোট', 'notes_placeholder' => 'পছন্দের সাইজ, লজিস্টিক নোট, পিকআপ তথ্য ইত্যাদি।', 'confirm_order' => 'অর্ডার নিশ্চিত করুন', 'recent_orders' => 'আমার সাম্প্রতিক অর্ডার', 'no_orders' => 'কোনো অর্ডার রেকর্ড নেই।'],
    'ru' => ['cart_empty' => 'Корзина пуста.', 'order_saved' => 'Заказ сохранён с номером ', 'meta_title' => 'Оформление заказа', 'meta_desc' => 'Подтверждение заказа в магазине клуба.', 'validate_order' => 'Подтвердить заказ', 'your_cart_empty' => 'Ваша корзина пуста.', 'total' => 'Итого:', 'payment_method' => 'Способ оплаты', 'on_site' => 'Оплата на месте', 'bank_transfer' => 'Банковский перевод', 'notes' => 'Примечания', 'notes_placeholder' => 'Предпочтительный размер, логистическая заметка, детали самовывоза и т. д.', 'confirm_order' => 'Подтвердить заказ', 'recent_orders' => 'Мои последние заказы', 'no_orders' => 'Заказов нет.'],
    'id' => ['cart_empty' => 'Keranjang kosong.', 'order_saved' => 'Pesanan disimpan dengan referensi ', 'meta_title' => 'Checkout toko', 'meta_desc' => 'Konfirmasi pesanan toko klub.', 'validate_order' => 'Konfirmasi pesanan', 'your_cart_empty' => 'Keranjang Anda kosong.', 'total' => 'Total:', 'payment_method' => 'Metode pembayaran', 'on_site' => 'Bayar di tempat', 'bank_transfer' => 'Transfer bank', 'notes' => 'Catatan', 'notes_placeholder' => 'Ukuran pilihan, catatan logistik, detail pengambilan, dll.', 'confirm_order' => 'Konfirmasi pesanan', 'recent_orders' => 'Pesanan terbaru saya', 'no_orders' => 'Belum ada pesanan tercatat.'],
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
