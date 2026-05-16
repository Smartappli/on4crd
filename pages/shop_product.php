<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['not_found' => 'Produit introuvable', 'not_exists' => 'Ce produit n’existe pas.', 'not_published' => 'Ce produit n’est pas publié.', 'meta_desc' => 'Produit du club ON4CRD.', 'summary_default' => 'Produit du club.', 'price' => 'Prix :', 'stock' => 'Stock :', 'status' => 'Statut :', 'order' => 'Commander', 'out_of_stock' => 'Rupture de stock momentanée.', 'quantity' => 'Quantité', 'add_to_cart' => 'Ajouter au panier', 'view_cart' => 'Voir le panier', 'back_shop' => 'Retour boutique'],
    'en' => ['not_found' => 'Product not found', 'not_exists' => 'This product does not exist.', 'not_published' => 'This product is not published.', 'meta_desc' => 'ON4CRD club product.', 'summary_default' => 'Club product.', 'price' => 'Price:', 'stock' => 'Stock:', 'status' => 'Status:', 'order' => 'Order', 'out_of_stock' => 'Temporarily out of stock.', 'quantity' => 'Quantity', 'add_to_cart' => 'Add to cart', 'view_cart' => 'View cart', 'back_shop' => 'Back to shop'],
    'de' => ['not_found' => 'Produkt nicht gefunden', 'not_exists' => 'Dieses Produkt existiert nicht.', 'not_published' => 'Dieses Produkt ist nicht veröffentlicht.', 'meta_desc' => 'Produkt des ON4CRD-Clubs.', 'summary_default' => 'Clubprodukt.', 'price' => 'Preis:', 'stock' => 'Bestand:', 'status' => 'Status:', 'order' => 'Bestellen', 'out_of_stock' => 'Vorübergehend nicht auf Lager.', 'quantity' => 'Menge', 'add_to_cart' => 'In den Warenkorb', 'view_cart' => 'Warenkorb anzeigen', 'back_shop' => 'Zurück zum Shop'],
    'es' => ['not_found' => 'Producto no encontrado', 'not_exists' => 'Este producto no existe.', 'not_published' => 'Este producto no está publicado.', 'meta_desc' => 'Producto del club ON4CRD.', 'summary_default' => 'Producto del club.', 'price' => 'Precio:', 'stock' => 'Stock:', 'status' => 'Estado:', 'order' => 'Pedir', 'out_of_stock' => 'Temporalmente sin stock.', 'quantity' => 'Cantidad', 'add_to_cart' => 'Añadir al carrito', 'view_cart' => 'Ver carrito', 'back_shop' => 'Volver a la tienda'],
    'it' => ['not_found' => 'Prodotto non trovato', 'not_exists' => 'Questo prodotto non esiste.', 'not_published' => 'Questo prodotto non è pubblicato.', 'meta_desc' => 'Prodotto del club ON4CRD.', 'summary_default' => 'Prodotto del club.', 'price' => 'Prezzo:', 'stock' => 'Stock:', 'status' => 'Stato:', 'order' => 'Ordina', 'out_of_stock' => 'Temporaneamente esaurito.', 'quantity' => 'Quantità', 'add_to_cart' => 'Aggiungi al carrello', 'view_cart' => 'Vedi carrello', 'back_shop' => 'Torna al negozio'],
    'pt' => ['not_found' => 'Produto não encontrado', 'not_exists' => 'Este produto não existe.', 'not_published' => 'Este produto não está publicado.', 'meta_desc' => 'Produto do clube ON4CRD.', 'summary_default' => 'Produto do clube.', 'price' => 'Preço:', 'stock' => 'Stock:', 'status' => 'Estado:', 'order' => 'Encomendar', 'out_of_stock' => 'Temporariamente sem stock.', 'quantity' => 'Quantidade', 'add_to_cart' => 'Adicionar ao carrinho', 'view_cart' => 'Ver carrinho', 'back_shop' => 'Voltar à loja'],
    'nl' => ['not_found' => 'Product niet gevonden', 'not_exists' => 'Dit product bestaat niet.', 'not_published' => 'Dit product is niet gepubliceerd.', 'meta_desc' => 'Product van de ON4CRD-club.', 'summary_default' => 'Clubproduct.', 'price' => 'Prijs:', 'stock' => 'Voorraad:', 'status' => 'Status:', 'order' => 'Bestellen', 'out_of_stock' => 'Tijdelijk niet op voorraad.', 'quantity' => 'Aantal', 'add_to_cart' => 'Toevoegen aan winkelwagen', 'view_cart' => 'Bekijk winkelwagen', 'back_shop' => 'Terug naar winkel'],
    'ar' => ['not_found' => 'المنتج غير موجود', 'not_exists' => 'هذا المنتج غير موجود.', 'not_published' => 'هذا المنتج غير منشور.', 'meta_desc' => 'منتج من نادي ON4CRD.', 'summary_default' => 'منتج النادي.', 'price' => 'السعر:', 'stock' => 'المخزون:', 'status' => 'الحالة:', 'order' => 'طلب', 'out_of_stock' => 'غير متوفر مؤقتًا.', 'quantity' => 'الكمية', 'add_to_cart' => 'أضف إلى السلة', 'view_cart' => 'عرض السلة', 'back_shop' => 'العودة إلى المتجر'],
    'hi' => ['not_found' => 'उत्पाद नहीं मिला', 'not_exists' => 'यह उत्पाद मौजूद नहीं है।', 'not_published' => 'यह उत्पाद प्रकाशित नहीं है।', 'meta_desc' => 'ON4CRD क्लब का उत्पाद।', 'summary_default' => 'क्लब उत्पाद।', 'price' => 'कीमत:', 'stock' => 'स्टॉक:', 'status' => 'स्थिति:', 'order' => 'ऑर्डर करें', 'out_of_stock' => 'फिलहाल स्टॉक में नहीं है।', 'quantity' => 'मात्रा', 'add_to_cart' => 'कार्ट में जोड़ें', 'view_cart' => 'कार्ट देखें', 'back_shop' => 'दुकान पर वापस जाएँ'],
    'ja' => ['not_found' => '商品が見つかりません', 'not_exists' => 'この商品は存在しません。', 'not_published' => 'この商品は公開されていません。', 'meta_desc' => 'ON4CRDクラブの商品。', 'summary_default' => 'クラブ商品。', 'price' => '価格:', 'stock' => '在庫:', 'status' => '状態:', 'order' => '注文', 'out_of_stock' => '現在在庫切れです。', 'quantity' => '数量', 'add_to_cart' => 'カートに追加', 'view_cart' => 'カートを見る', 'back_shop' => 'ショップに戻る'],
    'zh' => ['not_found' => '未找到商品', 'not_exists' => '该商品不存在。', 'not_published' => '该商品未发布。', 'meta_desc' => 'ON4CRD 俱乐部商品。', 'summary_default' => '俱乐部商品。', 'price' => '价格：', 'stock' => '库存：', 'status' => '状态：', 'order' => '下单', 'out_of_stock' => '暂时缺货。', 'quantity' => '数量', 'add_to_cart' => '加入购物车', 'view_cart' => '查看购物车', 'back_shop' => '返回商店'],
    'bn' => ['not_found' => 'পণ্য পাওয়া যায়নি', 'not_exists' => 'এই পণ্যটি বিদ্যমান নয়।', 'not_published' => 'এই পণ্যটি প্রকাশিত নয়।', 'meta_desc' => 'ON4CRD ক্লাবের পণ্য।', 'summary_default' => 'ক্লাব পণ্য।', 'price' => 'মূল্য:', 'stock' => 'স্টক:', 'status' => 'অবস্থা:', 'order' => 'অর্ডার', 'out_of_stock' => 'সাময়িকভাবে স্টকে নেই।', 'quantity' => 'পরিমাণ', 'add_to_cart' => 'কার্টে যোগ করুন', 'view_cart' => 'কার্ট দেখুন', 'back_shop' => 'দোকানে ফিরে যান'],
    'ru' => ['not_found' => 'Товар не найден', 'not_exists' => 'Этот товар не существует.', 'not_published' => 'Этот товар не опубликован.', 'meta_desc' => 'Товар клуба ON4CRD.', 'summary_default' => 'Товар клуба.', 'price' => 'Цена:', 'stock' => 'Наличие:', 'status' => 'Статус:', 'order' => 'Заказать', 'out_of_stock' => 'Временно нет в наличии.', 'quantity' => 'Количество', 'add_to_cart' => 'Добавить в корзину', 'view_cart' => 'Просмотреть корзину', 'back_shop' => 'Назад в магазин'],
    'id' => ['not_found' => 'Produk tidak ditemukan', 'not_exists' => 'Produk ini tidak ada.', 'not_published' => 'Produk ini tidak dipublikasikan.', 'meta_desc' => 'Produk klub ON4CRD.', 'summary_default' => 'Produk klub.', 'price' => 'Harga:', 'stock' => 'Stok:', 'status' => 'Status:', 'order' => 'Pesan', 'out_of_stock' => 'Stok habis sementara.', 'quantity' => 'Jumlah', 'add_to_cart' => 'Tambah ke keranjang', 'view_cart' => 'Lihat keranjang', 'back_shop' => 'Kembali ke toko'],
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
