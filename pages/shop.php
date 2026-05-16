<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['meta_title' => 'Boutique club', 'meta_desc' => 'Produits du club ON4CRD : textile, accessoires, documentation et réservations.', 'badge' => 'Boutique club', 'hero_title' => 'Une boutique simple pour les produits du club, séparée des enchères.', 'hero_lead' => 'La première itération privilégie la clarté : catalogue, stock, panier, commande et retrait / paiement sur place ou par virement. Pas de surcharge fonctionnelle inutile.', 'catalog' => 'Catalogue', 'cart' => 'Panier', 'order' => 'Commande', 'view_cart' => 'Voir le panier', 'view_auctions' => 'Voir les enchères', 'categories' => 'Catégories', 'no_categories' => 'Aucune catégorie configurée.', 'filter_catalog' => 'Filtrer le catalogue', 'category' => 'Catégorie', 'all' => 'Toutes', 'search' => 'Recherche', 'search_placeholder' => 'Titre, résumé...', 'filter' => 'Filtrer', 'reset' => 'Réinitialiser', 'no_products' => 'Aucun produit ne correspond à votre filtre.', 'featured' => 'Mis en avant', 'default_summary' => 'Produit du club.', 'price' => 'Prix :', 'stock' => 'Stock :', 'view_product' => 'Voir le produit', 'page' => 'Page', 'previous' => 'Précédent', 'next' => 'Suivant', 'hero_featured_title' => 'Produit à la une', 'hero_featured_cta' => 'Voir le produit vedette', 'hero_no_featured' => 'Aucun produit vedette pour le moment.', 'layout_title' => 'Boutique'],
    'en' => ['meta_title' => 'Club shop', 'meta_desc' => 'ON4CRD club products: clothing, accessories, documentation and bookings.', 'badge' => 'Club shop', 'hero_title' => 'A simple shop for club products, separate from auctions.', 'hero_lead' => 'This first iteration focuses on clarity: catalog, stock, cart, order and on-site pickup/payment or bank transfer.', 'catalog' => 'Catalog', 'cart' => 'Cart', 'order' => 'Order', 'view_cart' => 'View cart', 'view_auctions' => 'View auctions', 'categories' => 'Categories', 'no_categories' => 'No categories configured.', 'filter_catalog' => 'Filter catalog', 'category' => 'Category', 'all' => 'All', 'search' => 'Search', 'search_placeholder' => 'Title, summary...', 'filter' => 'Filter', 'reset' => 'Reset', 'no_products' => 'No products match your filter.', 'featured' => 'Featured', 'default_summary' => 'Club product.', 'price' => 'Price:', 'stock' => 'Stock:', 'view_product' => 'View product', 'page' => 'Page', 'previous' => 'Previous', 'next' => 'Next', 'hero_featured_title' => 'Featured product', 'hero_featured_cta' => 'View featured product', 'hero_no_featured' => 'No featured product right now.', 'layout_title' => 'Shop'],
    'de' => ['meta_title' => 'Club-Shop', 'meta_desc' => 'ON4CRD-Clubprodukte: Textilien, Zubehör, Dokumentation und Reservierungen.', 'badge' => 'Club-Shop', 'hero_title' => 'Ein einfacher Shop für Clubprodukte, getrennt von Auktionen.', 'hero_lead' => 'Diese erste Version setzt auf Klarheit: Katalog, Bestand, Warenkorb, Bestellung und Abholung/Zahlung vor Ort oder per Überweisung.', 'catalog' => 'Katalog', 'cart' => 'Warenkorb', 'order' => 'Bestellung', 'view_cart' => 'Warenkorb anzeigen', 'view_auctions' => 'Auktionen ansehen', 'categories' => 'Kategorien', 'no_categories' => 'Keine Kategorien konfiguriert.', 'filter_catalog' => 'Katalog filtern', 'category' => 'Kategorie', 'all' => 'Alle', 'search' => 'Suche', 'search_placeholder' => 'Titel, Zusammenfassung...', 'filter' => 'Filtern', 'reset' => 'Zurücksetzen', 'no_products' => 'Keine Produkte passen zu Ihrem Filter.', 'featured' => 'Hervorgehoben', 'default_summary' => 'Clubprodukt.', 'price' => 'Preis:', 'stock' => 'Bestand:', 'view_product' => 'Produkt ansehen', 'page' => 'Seite', 'previous' => 'Zurück', 'next' => 'Weiter', 'hero_featured_title' => 'Top-Produkt', 'hero_featured_cta' => 'Top-Produkt ansehen', 'hero_no_featured' => 'Derzeit kein Top-Produkt.', 'layout_title' => 'Shop'],
    'es' => ['meta_title' => 'Tienda del club', 'meta_desc' => 'Productos del club ON4CRD: textil, accesorios, documentación y reservas.', 'badge' => 'Tienda del club', 'hero_title' => 'Una tienda simple para los productos del club, separada de las subastas.', 'hero_lead' => 'Esta primera versión prioriza la claridad: catálogo, stock, carrito, pedido y recogida/pago in situ o por transferencia.', 'catalog' => 'Catálogo', 'cart' => 'Carrito', 'order' => 'Pedido', 'view_cart' => 'Ver carrito', 'view_auctions' => 'Ver subastas', 'categories' => 'Categorías', 'no_categories' => 'No hay categorías configuradas.', 'filter_catalog' => 'Filtrar catálogo', 'category' => 'Categoría', 'all' => 'Todas', 'search' => 'Buscar', 'search_placeholder' => 'Título, resumen...', 'filter' => 'Filtrar', 'reset' => 'Restablecer', 'no_products' => 'Ningún producto coincide con su filtro.', 'featured' => 'Destacado', 'default_summary' => 'Producto del club.', 'price' => 'Precio:', 'stock' => 'Stock:', 'view_product' => 'Ver producto', 'page' => 'Página', 'previous' => 'Anterior', 'next' => 'Siguiente', 'hero_featured_title' => 'Producto destacado', 'hero_featured_cta' => 'Ver producto destacado', 'hero_no_featured' => 'No hay producto destacado por ahora.', 'layout_title' => 'Tienda'],
    'it' => ['meta_title' => 'Negozio del club', 'meta_desc' => 'Prodotti del club ON4CRD: abbigliamento, accessori, documentazione e prenotazioni.', 'badge' => 'Negozio del club', 'hero_title' => 'Un negozio semplice per i prodotti del club, separato dalle aste.', 'hero_lead' => 'Questa prima versione punta alla chiarezza: catalogo, stock, carrello, ordine e ritiro/pagamento in sede o bonifico.', 'catalog' => 'Catalogo', 'cart' => 'Carrello', 'order' => 'Ordine', 'view_cart' => 'Vedi carrello', 'view_auctions' => 'Vedi aste', 'categories' => 'Categorie', 'no_categories' => 'Nessuna categoria configurata.', 'filter_catalog' => 'Filtra catalogo', 'category' => 'Categoria', 'all' => 'Tutte', 'search' => 'Cerca', 'search_placeholder' => 'Titolo, riepilogo...', 'filter' => 'Filtra', 'reset' => 'Reimposta', 'no_products' => 'Nessun prodotto corrisponde al filtro.', 'featured' => 'In evidenza', 'default_summary' => 'Prodotto del club.', 'price' => 'Prezzo:', 'stock' => 'Stock:', 'view_product' => 'Vedi prodotto', 'page' => 'Pagina', 'previous' => 'Precedente', 'next' => 'Successiva', 'hero_featured_title' => 'Prodotto in evidenza', 'hero_featured_cta' => 'Vedi prodotto in evidenza', 'hero_no_featured' => 'Nessun prodotto in evidenza al momento.', 'layout_title' => 'Negozio'],
    'pt' => ['meta_title' => 'Loja do clube', 'meta_desc' => 'Produtos do clube ON4CRD: têxtil, acessórios, documentação e reservas.', 'badge' => 'Loja do clube', 'hero_title' => 'Uma loja simples para produtos do clube, separada dos leilões.', 'hero_lead' => 'Esta primeira iteração privilegia a clareza: catálogo, stock, carrinho, encomenda e recolha/pagamento no local ou por transferência.', 'catalog' => 'Catálogo', 'cart' => 'Carrinho', 'order' => 'Encomenda', 'view_cart' => 'Ver carrinho', 'view_auctions' => 'Ver leilões', 'categories' => 'Categorias', 'no_categories' => 'Sem categorias configuradas.', 'filter_catalog' => 'Filtrar catálogo', 'category' => 'Categoria', 'all' => 'Todas', 'search' => 'Pesquisar', 'search_placeholder' => 'Título, resumo...', 'filter' => 'Filtrar', 'reset' => 'Repor', 'no_products' => 'Nenhum produto corresponde ao seu filtro.', 'featured' => 'Em destaque', 'default_summary' => 'Produto do clube.', 'price' => 'Preço:', 'stock' => 'Stock:', 'view_product' => 'Ver produto', 'page' => 'Página', 'previous' => 'Anterior', 'next' => 'Seguinte', 'hero_featured_title' => 'Produto em destaque', 'hero_featured_cta' => 'Ver produto em destaque', 'hero_no_featured' => 'Sem produto em destaque neste momento.', 'layout_title' => 'Loja'],
    'nl' => ['meta_title' => 'Clubwinkel', 'meta_desc' => 'ON4CRD-clubproducten: textiel, accessoires, documentatie en reservaties.', 'badge' => 'Clubwinkel', 'hero_title' => 'Een eenvoudige winkel voor clubproducten, apart van veilingen.', 'hero_lead' => 'Deze eerste versie focust op duidelijkheid: catalogus, voorraad, winkelwagen, bestelling en afhaling/betaling ter plaatse of via overschrijving.', 'catalog' => 'Catalogus', 'cart' => 'Winkelwagen', 'order' => 'Bestelling', 'view_cart' => 'Bekijk winkelwagen', 'view_auctions' => 'Bekijk veilingen', 'categories' => 'Categorieën', 'no_categories' => 'Geen categorieën geconfigureerd.', 'filter_catalog' => 'Catalogus filteren', 'category' => 'Categorie', 'all' => 'Alle', 'search' => 'Zoeken', 'search_placeholder' => 'Titel, samenvatting...', 'filter' => 'Filteren', 'reset' => 'Reset', 'no_products' => 'Geen producten komen overeen met je filter.', 'featured' => 'Uitgelicht', 'default_summary' => 'Clubproduct.', 'price' => 'Prijs:', 'stock' => 'Voorraad:', 'view_product' => 'Bekijk product', 'page' => 'Pagina', 'previous' => 'Vorige', 'next' => 'Volgende', 'hero_featured_title' => 'Uitgelicht product', 'hero_featured_cta' => 'Bekijk uitgelicht product', 'hero_no_featured' => 'Momenteel geen uitgelicht product.', 'layout_title' => 'Winkel'],
    'ar' => ['meta_title' => 'متجر النادي', 'meta_desc' => 'منتجات نادي ON4CRD: منسوجات، ملحقات، وثائق وحجوزات.', 'badge' => 'متجر النادي', 'hero_title' => 'متجر بسيط لمنتجات النادي، منفصل عن المزادات.', 'hero_lead' => 'تركّز هذه النسخة الأولى على الوضوح: الكتالوج، المخزون، السلة، الطلب، والاستلام/الدفع في المكان أو بالتحويل البنكي.', 'catalog' => 'الكتالوج', 'cart' => 'السلة', 'order' => 'الطلب', 'view_cart' => 'عرض السلة', 'view_auctions' => 'عرض المزادات', 'categories' => 'الفئات', 'no_categories' => 'لا توجد فئات مُعدّة.', 'filter_catalog' => 'تصفية الكتالوج', 'category' => 'الفئة', 'all' => 'الكل', 'search' => 'بحث', 'search_placeholder' => 'العنوان، الملخص...', 'filter' => 'تصفية', 'reset' => 'إعادة تعيين', 'no_products' => 'لا توجد منتجات تطابق الفلتر.', 'featured' => 'مميز', 'default_summary' => 'منتج النادي.', 'price' => 'السعر:', 'stock' => 'المخزون:', 'view_product' => 'عرض المنتج', 'page' => 'صفحة', 'previous' => 'السابق', 'next' => 'التالي', 'hero_featured_title' => 'المنتج المميز', 'hero_featured_cta' => 'عرض المنتج المميز', 'hero_no_featured' => 'لا يوجد منتج مميز حاليًا.', 'layout_title' => 'المتجر'],
    'hi' => ['meta_title' => 'क्लब शॉप', 'meta_desc' => 'ON4CRD क्लब उत्पाद: वस्त्र, एक्सेसरीज़, दस्तावेज़ और बुकिंग।', 'badge' => 'क्लब शॉप', 'hero_title' => 'क्लब उत्पादों के लिए एक सरल शॉप, नीलामी से अलग।', 'hero_lead' => 'यह पहला संस्करण स्पष्टता पर केंद्रित है: कैटलॉग, स्टॉक, कार्ट, ऑर्डर और ऑन-साइट पिकअप/भुगतान या बैंक ट्रांसफर।', 'catalog' => 'कैटलॉग', 'cart' => 'कार्ट', 'order' => 'ऑर्डर', 'view_cart' => 'कार्ट देखें', 'view_auctions' => 'नीलामी देखें', 'categories' => 'श्रेणियाँ', 'no_categories' => 'कोई श्रेणी कॉन्फ़िगर नहीं है।', 'filter_catalog' => 'कैटलॉग फ़िल्टर करें', 'category' => 'श्रेणी', 'all' => 'सभी', 'search' => 'खोजें', 'search_placeholder' => 'शीर्षक, सारांश...', 'filter' => 'फ़िल्टर', 'reset' => 'रीसेट', 'no_products' => 'आपके फ़िल्टर से कोई उत्पाद मेल नहीं खाता।', 'featured' => 'फ़ीचर्ड', 'default_summary' => 'क्लब उत्पाद।', 'price' => 'कीमत:', 'stock' => 'स्टॉक:', 'view_product' => 'उत्पाद देखें', 'page' => 'पेज', 'previous' => 'पिछला', 'next' => 'अगला', 'hero_featured_title' => 'फ़ीचर्ड उत्पाद', 'hero_featured_cta' => 'फ़ीचर्ड उत्पाद देखें', 'hero_no_featured' => 'अभी कोई फ़ीचर्ड उत्पाद नहीं।', 'layout_title' => 'शॉप'],
    'ja' => ['meta_title' => 'クラブショップ', 'meta_desc' => 'ON4CRDクラブ商品: 衣類、アクセサリ、資料、予約。', 'badge' => 'クラブショップ', 'hero_title' => 'オークションとは別の、クラブ商品向けシンプルショップ。', 'hero_lead' => '初期版は分かりやすさ重視: カタログ、在庫、カート、注文、現地受取/支払いまたは振込。', 'catalog' => 'カタログ', 'cart' => 'カート', 'order' => '注文', 'view_cart' => 'カートを見る', 'view_auctions' => 'オークションを見る', 'categories' => 'カテゴリ', 'no_categories' => 'カテゴリが設定されていません。', 'filter_catalog' => 'カタログを絞り込む', 'category' => 'カテゴリ', 'all' => 'すべて', 'search' => '検索', 'search_placeholder' => 'タイトル、概要...', 'filter' => '絞り込み', 'reset' => 'リセット', 'no_products' => '条件に一致する商品がありません。', 'featured' => '注目', 'default_summary' => 'クラブ商品。', 'price' => '価格:', 'stock' => '在庫:', 'view_product' => '商品を見る', 'page' => 'ページ', 'previous' => '前へ', 'next' => '次へ', 'hero_featured_title' => '注目商品', 'hero_featured_cta' => '注目商品を見る', 'hero_no_featured' => '現在注目商品はありません。', 'layout_title' => 'ショップ'],
    'zh' => ['meta_title' => '俱乐部商店', 'meta_desc' => 'ON4CRD 俱乐部商品：服饰、配件、文档与预订。', 'badge' => '俱乐部商店', 'hero_title' => '一个简单的俱乐部商品商店，与拍卖分离。', 'hero_lead' => '首个版本强调清晰：目录、库存、购物车、下单以及现场自提/支付或银行转账。', 'catalog' => '目录', 'cart' => '购物车', 'order' => '订单', 'view_cart' => '查看购物车', 'view_auctions' => '查看拍卖', 'categories' => '分类', 'no_categories' => '尚未配置分类。', 'filter_catalog' => '筛选目录', 'category' => '分类', 'all' => '全部', 'search' => '搜索', 'search_placeholder' => '标题、摘要...', 'filter' => '筛选', 'reset' => '重置', 'no_products' => '没有商品符合筛选条件。', 'featured' => '精选', 'default_summary' => '俱乐部商品。', 'price' => '价格：', 'stock' => '库存：', 'view_product' => '查看商品', 'page' => '页', 'previous' => '上一页', 'next' => '下一页', 'hero_featured_title' => '精选商品', 'hero_featured_cta' => '查看精选商品', 'hero_no_featured' => '当前没有精选商品。', 'layout_title' => '商店'],
    'bn' => ['meta_title' => 'ক্লাব শপ', 'meta_desc' => 'ON4CRD ক্লাব পণ্য: পোশাক, আনুষঙ্গিক, ডকুমেন্টেশন ও বুকিং।', 'badge' => 'ক্লাব শপ', 'hero_title' => 'ক্লাব পণ্যের জন্য একটি সহজ শপ, নিলাম থেকে আলাদা।', 'hero_lead' => 'এই প্রথম সংস্করণে স্পষ্টতাই মুখ্য: ক্যাটালগ, স্টক, কার্ট, অর্ডার এবং অন-সাইট সংগ্রহ/পেমেন্ট বা ব্যাংক ট্রান্সফার।', 'catalog' => 'ক্যাটালগ', 'cart' => 'কার্ট', 'order' => 'অর্ডার', 'view_cart' => 'কার্ট দেখুন', 'view_auctions' => 'নিলাম দেখুন', 'categories' => 'ক্যাটাগরি', 'no_categories' => 'কোনো ক্যাটাগরি কনফিগার করা নেই।', 'filter_catalog' => 'ক্যাটালগ ফিল্টার করুন', 'category' => 'ক্যাটাগরি', 'all' => 'সব', 'search' => 'খুঁজুন', 'search_placeholder' => 'শিরোনাম, সারাংশ...', 'filter' => 'ফিল্টার', 'reset' => 'রিসেট', 'no_products' => 'আপনার ফিল্টারের সাথে কোনো পণ্য মেলেনি।', 'featured' => 'ফিচার্ড', 'default_summary' => 'ক্লাব পণ্য।', 'price' => 'মূল্য:', 'stock' => 'স্টক:', 'view_product' => 'পণ্য দেখুন', 'page' => 'পৃষ্ঠা', 'previous' => 'পূর্ববর্তী', 'next' => 'পরবর্তী', 'hero_featured_title' => 'ফিচার্ড পণ্য', 'hero_featured_cta' => 'ফিচার্ড পণ্য দেখুন', 'hero_no_featured' => 'এখন কোনো ফিচার্ড পণ্য নেই।', 'layout_title' => 'শপ'],
    'ru' => ['meta_title' => 'Магазин клуба', 'meta_desc' => 'Товары клуба ON4CRD: текстиль, аксессуары, документация и бронирование.', 'badge' => 'Магазин клуба', 'hero_title' => 'Простой магазин клубных товаров, отдельно от аукционов.', 'hero_lead' => 'Первая версия ориентирована на ясность: каталог, наличие, корзина, заказ и самовывоз/оплата на месте или банковским переводом.', 'catalog' => 'Каталог', 'cart' => 'Корзина', 'order' => 'Заказ', 'view_cart' => 'Открыть корзину', 'view_auctions' => 'Смотреть аукционы', 'categories' => 'Категории', 'no_categories' => 'Категории не настроены.', 'filter_catalog' => 'Фильтр каталога', 'category' => 'Категория', 'all' => 'Все', 'search' => 'Поиск', 'search_placeholder' => 'Название, описание...', 'filter' => 'Фильтровать', 'reset' => 'Сбросить', 'no_products' => 'Товары по вашему фильтру не найдены.', 'featured' => 'Рекомендуемое', 'default_summary' => 'Товар клуба.', 'price' => 'Цена:', 'stock' => 'Наличие:', 'view_product' => 'Открыть товар', 'page' => 'Страница', 'previous' => 'Назад', 'next' => 'Далее', 'hero_featured_title' => 'Рекомендуемый товар', 'hero_featured_cta' => 'Смотреть рекомендуемый товар', 'hero_no_featured' => 'Сейчас нет рекомендуемого товара.', 'layout_title' => 'Магазин'],
    'id' => ['meta_title' => 'Toko klub', 'meta_desc' => 'Produk klub ON4CRD: tekstil, aksesori, dokumentasi, dan reservasi.', 'badge' => 'Toko klub', 'hero_title' => 'Toko sederhana untuk produk klub, terpisah dari lelang.', 'hero_lead' => 'Iterasi pertama ini fokus pada kejelasan: katalog, stok, keranjang, pesanan, serta pengambilan/pembayaran di tempat atau transfer bank.', 'catalog' => 'Katalog', 'cart' => 'Keranjang', 'order' => 'Pesanan', 'view_cart' => 'Lihat keranjang', 'view_auctions' => 'Lihat lelang', 'categories' => 'Kategori', 'no_categories' => 'Belum ada kategori yang dikonfigurasi.', 'filter_catalog' => 'Filter katalog', 'category' => 'Kategori', 'all' => 'Semua', 'search' => 'Cari', 'search_placeholder' => 'Judul, ringkasan...', 'filter' => 'Filter', 'reset' => 'Reset', 'no_products' => 'Tidak ada produk yang cocok dengan filter Anda.', 'featured' => 'Unggulan', 'default_summary' => 'Produk klub.', 'price' => 'Harga:', 'stock' => 'Stok:', 'view_product' => 'Lihat produk', 'page' => 'Halaman', 'previous' => 'Sebelumnya', 'next' => 'Berikutnya', 'hero_featured_title' => 'Produk unggulan', 'hero_featured_cta' => 'Lihat produk unggulan', 'hero_no_featured' => 'Tidak ada produk unggulan saat ini.', 'layout_title' => 'Toko'],
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
