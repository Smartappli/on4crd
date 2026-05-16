<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['added' => 'Produit ajouté au panier.', 'updated' => 'Panier mis à jour.', 'removed' => 'Produit retiré du panier.', 'cleared' => 'Panier vidé.', 'meta_title' => 'Panier boutique', 'meta_desc' => 'Panier de la boutique du club.', 'title' => 'Panier', 'back_shop' => 'Retour boutique', 'empty' => 'Votre panier est vide.', 'product' => 'Produit', 'unit_price' => 'Prix unitaire', 'quantity' => 'Quantité', 'total' => 'Total', 'action' => 'Action', 'update_action' => 'Mettre à jour', 'remove' => 'Supprimer', 'summary' => 'Résumé', 'checkout' => 'Passer commande', 'clear_cart' => 'Vider le panier', 'layout_title' => 'Panier'],
    'en' => ['added' => 'Product added to cart.', 'updated' => 'Cart updated.', 'removed' => 'Product removed from cart.', 'cleared' => 'Cart cleared.', 'meta_title' => 'Shop cart', 'meta_desc' => 'Club shop cart.', 'title' => 'Cart', 'back_shop' => 'Back to shop', 'empty' => 'Your cart is empty.', 'product' => 'Product', 'unit_price' => 'Unit price', 'quantity' => 'Quantity', 'total' => 'Total', 'action' => 'Action', 'update_action' => 'Update', 'remove' => 'Remove', 'summary' => 'Summary', 'checkout' => 'Checkout', 'clear_cart' => 'Clear cart', 'layout_title' => 'Cart'],
    'de' => ['added' => 'Produkt zum Warenkorb hinzugefügt.', 'updated' => 'Warenkorb aktualisiert.', 'removed' => 'Produkt aus dem Warenkorb entfernt.', 'cleared' => 'Warenkorb geleert.', 'meta_title' => 'Warenkorb', 'meta_desc' => 'Warenkorb des Club-Shops.', 'title' => 'Warenkorb', 'back_shop' => 'Zurück zum Shop', 'empty' => 'Ihr Warenkorb ist leer.', 'product' => 'Produkt', 'unit_price' => 'Stückpreis', 'quantity' => 'Menge', 'total' => 'Gesamt', 'action' => 'Aktion', 'update_action' => 'Aktualisieren', 'remove' => 'Entfernen', 'summary' => 'Zusammenfassung', 'checkout' => 'Bestellung aufgeben', 'clear_cart' => 'Warenkorb leeren', 'layout_title' => 'Warenkorb'],
    'es' => ['added' => 'Producto añadido al carrito.', 'updated' => 'Carrito actualizado.', 'removed' => 'Producto eliminado del carrito.', 'cleared' => 'Carrito vaciado.', 'meta_title' => 'Carrito tienda', 'meta_desc' => 'Carrito de la tienda del club.', 'title' => 'Carrito', 'back_shop' => 'Volver a la tienda', 'empty' => 'Su carrito está vacío.', 'product' => 'Producto', 'unit_price' => 'Precio unitario', 'quantity' => 'Cantidad', 'total' => 'Total', 'action' => 'Acción', 'update_action' => 'Actualizar', 'remove' => 'Eliminar', 'summary' => 'Resumen', 'checkout' => 'Finalizar pedido', 'clear_cart' => 'Vaciar carrito', 'layout_title' => 'Carrito'],
    'it' => ['added' => 'Prodotto aggiunto al carrello.', 'updated' => 'Carrello aggiornato.', 'removed' => 'Prodotto rimosso dal carrello.', 'cleared' => 'Carrello svuotato.', 'meta_title' => 'Carrello negozio', 'meta_desc' => 'Carrello del negozio del club.', 'title' => 'Carrello', 'back_shop' => 'Torna al negozio', 'empty' => 'Il tuo carrello è vuoto.', 'product' => 'Prodotto', 'unit_price' => 'Prezzo unitario', 'quantity' => 'Quantità', 'total' => 'Totale', 'action' => 'Azione', 'update_action' => 'Aggiorna', 'remove' => 'Rimuovi', 'summary' => 'Riepilogo', 'checkout' => 'Conferma ordine', 'clear_cart' => 'Svuota carrello', 'layout_title' => 'Carrello'],
    'pt' => ['added' => 'Produto adicionado ao carrinho.', 'updated' => 'Carrinho atualizado.', 'removed' => 'Produto removido do carrinho.', 'cleared' => 'Carrinho esvaziado.', 'meta_title' => 'Carrinho da loja', 'meta_desc' => 'Carrinho da loja do clube.', 'title' => 'Carrinho', 'back_shop' => 'Voltar à loja', 'empty' => 'O seu carrinho está vazio.', 'product' => 'Produto', 'unit_price' => 'Preço unitário', 'quantity' => 'Quantidade', 'total' => 'Total', 'action' => 'Ação', 'update_action' => 'Atualizar', 'remove' => 'Remover', 'summary' => 'Resumo', 'checkout' => 'Finalizar encomenda', 'clear_cart' => 'Esvaziar carrinho', 'layout_title' => 'Carrinho'],
    'nl' => ['added' => 'Product toegevoegd aan winkelwagen.', 'updated' => 'Winkelwagen bijgewerkt.', 'removed' => 'Product verwijderd uit winkelwagen.', 'cleared' => 'Winkelwagen leeggemaakt.', 'meta_title' => 'Winkelwagen', 'meta_desc' => 'Winkelwagen van de clubwinkel.', 'title' => 'Winkelwagen', 'back_shop' => 'Terug naar winkel', 'empty' => 'Je winkelwagen is leeg.', 'product' => 'Product', 'unit_price' => 'Stukprijs', 'quantity' => 'Aantal', 'total' => 'Totaal', 'action' => 'Actie', 'update_action' => 'Bijwerken', 'remove' => 'Verwijderen', 'summary' => 'Samenvatting', 'checkout' => 'Bestelling plaatsen', 'clear_cart' => 'Winkelwagen legen', 'layout_title' => 'Winkelwagen'],
    'ar' => ['added' => 'تمت إضافة المنتج إلى السلة.', 'updated' => 'تم تحديث السلة.', 'removed' => 'تمت إزالة المنتج من السلة.', 'cleared' => 'تم تفريغ السلة.', 'meta_title' => 'سلة المتجر', 'meta_desc' => 'سلة متجر النادي.', 'title' => 'السلة', 'back_shop' => 'العودة إلى المتجر', 'empty' => 'سلتك فارغة.', 'product' => 'المنتج', 'unit_price' => 'سعر الوحدة', 'quantity' => 'الكمية', 'total' => 'الإجمالي', 'action' => 'الإجراء', 'update_action' => 'تحديث', 'remove' => 'حذف', 'summary' => 'الملخص', 'checkout' => 'إتمام الطلب', 'clear_cart' => 'تفريغ السلة', 'layout_title' => 'السلة'],
    'hi' => ['added' => 'उत्पाद कार्ट में जोड़ दिया गया।', 'updated' => 'कार्ट अपडेट हो गया।', 'removed' => 'उत्पाद कार्ट से हटा दिया गया।', 'cleared' => 'कार्ट खाली कर दिया गया।', 'meta_title' => 'शॉप कार्ट', 'meta_desc' => 'क्लब शॉप का कार्ट।', 'title' => 'कार्ट', 'back_shop' => 'दुकान पर वापस जाएँ', 'empty' => 'आपका कार्ट खाली है।', 'product' => 'उत्पाद', 'unit_price' => 'इकाई मूल्य', 'quantity' => 'मात्रा', 'total' => 'कुल', 'action' => 'कार्रवाई', 'update_action' => 'अपडेट करें', 'remove' => 'हटाएँ', 'summary' => 'सारांश', 'checkout' => 'चेकआउट', 'clear_cart' => 'कार्ट खाली करें', 'layout_title' => 'कार्ट'],
    'ja' => ['added' => '商品をカートに追加しました。', 'updated' => 'カートを更新しました。', 'removed' => '商品をカートから削除しました。', 'cleared' => 'カートを空にしました。', 'meta_title' => 'ショップカート', 'meta_desc' => 'クラブショップのカート。', 'title' => 'カート', 'back_shop' => 'ショップへ戻る', 'empty' => 'カートは空です。', 'product' => '商品', 'unit_price' => '単価', 'quantity' => '数量', 'total' => '合計', 'action' => '操作', 'update_action' => '更新', 'remove' => '削除', 'summary' => '概要', 'checkout' => '購入手続きへ', 'clear_cart' => 'カートを空にする', 'layout_title' => 'カート'],
    'zh' => ['added' => '商品已加入购物车。', 'updated' => '购物车已更新。', 'removed' => '商品已从购物车移除。', 'cleared' => '购物车已清空。', 'meta_title' => '商店购物车', 'meta_desc' => '俱乐部商店购物车。', 'title' => '购物车', 'back_shop' => '返回商店', 'empty' => '您的购物车为空。', 'product' => '商品', 'unit_price' => '单价', 'quantity' => '数量', 'total' => '总计', 'action' => '操作', 'update_action' => '更新', 'remove' => '移除', 'summary' => '汇总', 'checkout' => '去结算', 'clear_cart' => '清空购物车', 'layout_title' => '购物车'],
    'bn' => ['added' => 'পণ্য কার্টে যোগ করা হয়েছে।', 'updated' => 'কার্ট আপডেট হয়েছে।', 'removed' => 'পণ্য কার্ট থেকে সরানো হয়েছে।', 'cleared' => 'কার্ট খালি করা হয়েছে।', 'meta_title' => 'শপ কার্ট', 'meta_desc' => 'ক্লাব শপের কার্ট।', 'title' => 'কার্ট', 'back_shop' => 'দোকানে ফিরে যান', 'empty' => 'আপনার কার্ট খালি।', 'product' => 'পণ্য', 'unit_price' => 'একক মূল্য', 'quantity' => 'পরিমাণ', 'total' => 'মোট', 'action' => 'অ্যাকশন', 'update_action' => 'আপডেট করুন', 'remove' => 'সরান', 'summary' => 'সারাংশ', 'checkout' => 'চেকআউট', 'clear_cart' => 'কার্ট খালি করুন', 'layout_title' => 'কার্ট'],
    'ru' => ['added' => 'Товар добавлен в корзину.', 'updated' => 'Корзина обновлена.', 'removed' => 'Товар удалён из корзины.', 'cleared' => 'Корзина очищена.', 'meta_title' => 'Корзина магазина', 'meta_desc' => 'Корзина магазина клуба.', 'title' => 'Корзина', 'back_shop' => 'Назад в магазин', 'empty' => 'Ваша корзина пуста.', 'product' => 'Товар', 'unit_price' => 'Цена за единицу', 'quantity' => 'Количество', 'total' => 'Итого', 'action' => 'Действие', 'update_action' => 'Обновить', 'remove' => 'Удалить', 'summary' => 'Сводка', 'checkout' => 'Оформить заказ', 'clear_cart' => 'Очистить корзину', 'layout_title' => 'Корзина'],
    'id' => ['added' => 'Produk ditambahkan ke keranjang.', 'updated' => 'Keranjang diperbarui.', 'removed' => 'Produk dihapus dari keranjang.', 'cleared' => 'Keranjang dikosongkan.', 'meta_title' => 'Keranjang toko', 'meta_desc' => 'Keranjang toko klub.', 'title' => 'Keranjang', 'back_shop' => 'Kembali ke toko', 'empty' => 'Keranjang Anda kosong.', 'product' => 'Produk', 'unit_price' => 'Harga satuan', 'quantity' => 'Jumlah', 'total' => 'Total', 'action' => 'Aksi', 'update_action' => 'Perbarui', 'remove' => 'Hapus', 'summary' => 'Ringkasan', 'checkout' => 'Checkout', 'clear_cart' => 'Kosongkan keranjang', 'layout_title' => 'Keranjang'],
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
