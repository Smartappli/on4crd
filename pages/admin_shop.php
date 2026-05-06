<?php
declare(strict_types=1);

require_module_enabled('shop');
require_permission('admin.access');
require_permission('shop.manage');
$locale = current_locale();
$i18n = [
    'fr' => ['layout' => 'Administration boutique', 'meta_desc' => 'Gestion des produits, catégories et commandes de la boutique.', 'no_orders' => 'Aucune commande.', 'edit' => 'Modifier', 'create' => 'Créer', 'a_category' => 'une catégorie', 'a_product' => 'un produit', 'err_category_required' => 'Nom de catégorie obligatoire.', 'ok_category_saved' => 'Catégorie enregistrée.', 'err_product_title_required' => 'Titre produit obligatoire.', 'ok_product_saved' => 'Produit enregistré.', 'ok_order_updated' => 'Commande mise à jour.', 'name' => 'Nom', 'slug' => 'Slug', 'description' => 'Description', 'order' => 'Ordre', 'active' => 'Actif', 'save_category' => 'Enregistrer la catégorie', 'title' => 'Titre', 'category' => 'Catégorie', 'none' => 'Aucune', 'summary' => 'Résumé', 'price' => 'Prix (€)', 'stock' => 'Stock (laisser vide pour illimité)', 'image_url' => 'URL de l\'image', 'featured' => 'Produit mis en avant', 'status' => 'Statut', 'save_product' => 'Enregistrer le produit', 'catalog' => 'Catalogue', 'th_product' => 'Produit', 'th_category' => 'Catégorie', 'th_price' => 'Prix', 'th_stock' => 'Stock', 'th_status' => 'Statut', 'th_action' => 'Action', 'no_products' => 'Aucun produit.', 'orders' => 'Commandes', 'th_reference' => 'Référence', 'th_member' => 'Membre', 'th_total' => 'Total', 'ok' => 'OK'],
    'en' => ['layout' => 'Shop administration', 'meta_desc' => 'Manage shop products, categories, and orders.', 'no_orders' => 'No orders.', 'edit' => 'Edit', 'create' => 'Create', 'a_category' => 'a category', 'a_product' => 'a product', 'err_category_required' => 'Category name is required.', 'ok_category_saved' => 'Category saved.', 'err_product_title_required' => 'Product title is required.', 'ok_product_saved' => 'Product saved.', 'ok_order_updated' => 'Order updated.', 'name' => 'Name', 'slug' => 'Slug', 'description' => 'Description', 'order' => 'Order', 'active' => 'Active', 'save_category' => 'Save category', 'title' => 'Title', 'category' => 'Category', 'none' => 'None', 'summary' => 'Summary', 'price' => 'Price (€)', 'stock' => 'Stock (leave empty for unlimited)', 'image_url' => 'Image URL', 'featured' => 'Featured product', 'status' => 'Status', 'save_product' => 'Save product', 'catalog' => 'Catalog', 'th_product' => 'Product', 'th_category' => 'Category', 'th_price' => 'Price', 'th_stock' => 'Stock', 'th_status' => 'Status', 'th_action' => 'Action', 'no_products' => 'No products.', 'orders' => 'Orders', 'th_reference' => 'Reference', 'th_member' => 'Member', 'th_total' => 'Total', 'ok' => 'OK'],
    'de' => ['layout' => 'Shop-Verwaltung', 'meta_desc' => 'Produkte, Kategorien und Bestellungen des Shops verwalten.', 'no_orders' => 'Keine Bestellungen.', 'edit' => 'Bearbeiten', 'create' => 'Erstellen', 'a_category' => 'eine Kategorie', 'a_product' => 'ein Produkt', 'err_category_required' => 'Kategoriename ist erforderlich.', 'ok_category_saved' => 'Kategorie gespeichert.', 'err_product_title_required' => 'Produkttitel ist erforderlich.', 'ok_product_saved' => 'Produkt gespeichert.', 'ok_order_updated' => 'Bestellung aktualisiert.', 'name' => 'Name', 'slug' => 'Slug', 'description' => 'Beschreibung', 'order' => 'Reihenfolge', 'active' => 'Aktiv', 'save_category' => 'Kategorie speichern', 'title' => 'Titel', 'category' => 'Kategorie', 'none' => 'Keine', 'summary' => 'Zusammenfassung', 'price' => 'Preis (€)', 'stock' => 'Bestand (leer für unbegrenzt)', 'image_url' => 'Bild-URL', 'featured' => 'Hervorgehobenes Produkt', 'status' => 'Status', 'save_product' => 'Produkt speichern', 'catalog' => 'Katalog', 'th_product' => 'Produkt', 'th_category' => 'Kategorie', 'th_price' => 'Preis', 'th_stock' => 'Bestand', 'th_status' => 'Status', 'th_action' => 'Aktion', 'no_products' => 'Keine Produkte.', 'orders' => 'Bestellungen', 'th_reference' => 'Referenz', 'th_member' => 'Mitglied', 'th_total' => 'Gesamt', 'ok' => 'OK'],
    'nl' => ['layout' => 'Winkelbeheer', 'meta_desc' => 'Beheer producten, categorieën en bestellingen van de winkel.', 'no_orders' => 'Geen bestellingen.', 'edit' => 'Bewerken', 'create' => 'Aanmaken', 'a_category' => 'een categorie', 'a_product' => 'een product', 'err_category_required' => 'Categorienaam is verplicht.', 'ok_category_saved' => 'Categorie opgeslagen.', 'err_product_title_required' => 'Producttitel is verplicht.', 'ok_product_saved' => 'Product opgeslagen.', 'ok_order_updated' => 'Bestelling bijgewerkt.', 'name' => 'Naam', 'slug' => 'Slug', 'description' => 'Beschrijving', 'order' => 'Volgorde', 'active' => 'Actief', 'save_category' => 'Categorie opslaan', 'title' => 'Titel', 'category' => 'Categorie', 'none' => 'Geen', 'summary' => 'Samenvatting', 'price' => 'Prijs (€)', 'stock' => 'Voorraad (leeg laten voor onbeperkt)', 'image_url' => 'Afbeeldings-URL', 'featured' => 'Uitgelicht product', 'status' => 'Status', 'save_product' => 'Product opslaan', 'catalog' => 'Catalogus', 'th_product' => 'Product', 'th_category' => 'Categorie', 'th_price' => 'Prijs', 'th_stock' => 'Voorraad', 'th_status' => 'Status', 'th_action' => 'Actie', 'no_products' => 'Geen producten.', 'orders' => 'Bestellingen', 'th_reference' => 'Referentie', 'th_member' => 'Lid', 'th_total' => 'Totaal', 'ok' => 'OK'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};
set_page_meta([
    'title' => $t('layout'),
    'description' => $t('meta_desc'),
    'schema_type' => 'WebPage',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'save_product');

        if ($action === 'save_category') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '') {
                throw new RuntimeException($t('err_category_required'));
            }
            $slug = trim((string) ($_POST['slug'] ?? ''));
            if ($slug === '') {
                $slug = slugify($name);
            }
            $params = [
                $slug,
                $name,
                trim((string) ($_POST['description'] ?? '')),
                (int) ($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0,
            ];
            if ($id > 0) {
                db()->prepare('UPDATE shop_categories SET slug = ?, name = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?')->execute([...$params, $id]);
            } else {
                db()->prepare('INSERT INTO shop_categories (slug, name, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)')->execute($params);
            }
            cache_forget('shop_categories_v1');
            cache_forget('shop_public_products_v1');
            set_flash('success', $t('ok_category_saved'));
        } elseif ($action === 'save_product') {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            if ($title === '') {
                throw new RuntimeException($t('err_product_title_required'));
            }
            $slug = trim((string) ($_POST['slug'] ?? ''));
            if ($slug === '') {
                $slug = slugify($title);
            }
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $stockRaw = trim((string) ($_POST['stock_qty'] ?? ''));
            $stockQty = $stockRaw === '' ? null : max(0, (int) $stockRaw);
            $params = [
                $categoryId > 0 ? $categoryId : null,
                $slug,
                $title,
                trim((string) ($_POST['summary'] ?? '')),
                sanitize_rich_html((string) ($_POST['description'] ?? '')),
                parse_price_to_cents((string) ($_POST['price'] ?? '0')),
                $stockQty,
                sanitize_image_src_attribute((string) ($_POST['image_url'] ?? '')) ?? '',
                isset($_POST['is_featured']) ? 1 : 0,
                (string) ($_POST['status'] ?? 'draft'),
            ];
            if ($id > 0) {
                db()->prepare('UPDATE shop_products SET category_id = ?, slug = ?, title = ?, summary = ?, description = ?, price_cents = ?, stock_qty = ?, image_url = ?, is_featured = ?, status = ? WHERE id = ?')->execute([...$params, $id]);
            } else {
                db()->prepare('INSERT INTO shop_products (category_id, slug, title, summary, description, price_cents, stock_qty, image_url, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute($params);
            }
            cache_forget('shop_public_products_v1');
            set_flash('success', $t('ok_product_saved'));
        } elseif ($action === 'save_order_status') {
            db()->prepare('UPDATE shop_orders SET status = ? WHERE id = ?')->execute([
                (string) ($_POST['status'] ?? 'pending'),
                (int) ($_POST['order_id'] ?? 0),
            ]);
            set_flash('success', $t('ok_order_updated'));
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_shop');
}

$categories = table_exists('shop_categories') ? db()->query('SELECT * FROM shop_categories ORDER BY sort_order ASC, name ASC, id ASC')->fetchAll() : [];
$products = table_exists('shop_products') ? db()->query('SELECT p.*, c.name AS category_name FROM shop_products p LEFT JOIN shop_categories c ON c.id = p.category_id ORDER BY p.updated_at DESC, p.id DESC')->fetchAll() : [];
$orders = shop_recent_orders(null, 40);

$editCategory = null;
if (!empty($_GET['edit_category'])) {
    $stmt = db()->prepare('SELECT * FROM shop_categories WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit_category']]);
    $editCategory = $stmt->fetch() ?: null;
}
$editProduct = null;
if (!empty($_GET['edit_product'])) {
    $stmt = db()->prepare('SELECT * FROM shop_products WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit_product']]);
    $editProduct = $stmt->fetch() ?: null;
}

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= e($editCategory ? $t('edit') : $t('create')) ?> <?= e($t('a_category')) ?></h1>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="id" value="<?= (int) ($editCategory['id'] ?? 0) ?>">
            <label><?= e($t('name')) ?><input type="text" name="name" value="<?= e((string) ($editCategory['name'] ?? '')) ?>" required></label>
            <label><?= e($t('slug')) ?><input type="text" name="slug" value="<?= e((string) ($editCategory['slug'] ?? '')) ?>"></label>
            <label><?= e($t('description')) ?><textarea name="description" rows="3"><?= e((string) ($editCategory['description'] ?? '')) ?></textarea></label>
            <div class="grid-2">
                <label><?= e($t('order')) ?><input type="number" name="sort_order" value="<?= (int) ($editCategory['sort_order'] ?? 0) ?>"></label>
                <label><input type="checkbox" name="is_active" value="1" <?= !empty($editCategory['is_active']) || $editCategory === null ? 'checked' : '' ?>> <?= e($t('active')) ?></label>
            </div>
            <button class="button"><?= e($t('save_category')) ?></button>
        </form>
    </section>

    <section class="card">
        <h1><?= e($editProduct ? $t('edit') : $t('create')) ?> <?= e($t('a_product')) ?></h1>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_product">
            <input type="hidden" name="id" value="<?= (int) ($editProduct['id'] ?? 0) ?>">
            <label><?= e($t('title')) ?><input type="text" name="title" value="<?= e((string) ($editProduct['title'] ?? '')) ?>" required></label>
            <label><?= e($t('slug')) ?><input type="text" name="slug" value="<?= e((string) ($editProduct['slug'] ?? '')) ?>"></label>
            <label><?= e($t('category')) ?>
                <select name="category_id">
                    <option value="0"><?= e($t('none')) ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= ((int) ($editProduct['category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>><?= e((string) $category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e($t('summary')) ?><textarea name="summary" rows="3"><?= e((string) ($editProduct['summary'] ?? '')) ?></textarea></label>
            <label><?= e($t('description')) ?><textarea name="description" rows="5"><?= e((string) ($editProduct['description'] ?? '')) ?></textarea></label>
            <div class="grid-2">
                <label><?= e($t('price')) ?><input type="text" name="price" value="<?= e(number_format(((int) ($editProduct['price_cents'] ?? 0)) / 100, 2, ',', '')) ?>"></label>
                <label><?= e($t('stock')) ?><input type="text" name="stock_qty" value="<?= e(is_array($editProduct) && array_key_exists('stock_qty', $editProduct) ? (string) ($editProduct['stock_qty'] ?? '') : '') ?>"></label>
            </div>
            <label><?= e($t('image_url')) ?><input type="text" name="image_url" value="<?= e((string) ($editProduct['image_url'] ?? '')) ?>"></label>
            <div class="grid-2">
                <label><input type="checkbox" name="is_featured" value="1" <?= !empty($editProduct['is_featured']) ? 'checked' : '' ?>> <?= e($t('featured')) ?></label>
                <label><?= e($t('status')) ?>
                    <select name="status">
                        <?php foreach (['draft','published','archived'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= (($editProduct['status'] ?? 'draft') === $status) ? 'selected' : '' ?>><?= e(shop_status_label($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <button class="button"><?= e($t('save_product')) ?></button>
        </form>
    </section>
</div>

<div class="grid-2">
    <section class="card">
        <h2><?= e($t('catalog')) ?></h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th><?= e($t('th_product')) ?></th><th><?= e($t('th_category')) ?></th><th><?= e($t('th_price')) ?></th><th><?= e($t('th_stock')) ?></th><th><?= e($t('th_status')) ?></th><th><?= e($t('th_action')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= e((string) $product['title']) ?></td>
                        <td><?= e((string) ($product['category_name'] ?? '—')) ?></td>
                        <td><?= e(format_price_eur((int) $product['price_cents'])) ?></td>
                        <td><?= e(format_integer_or_unlimited($product['stock_qty'] !== null ? (int) $product['stock_qty'] : null)) ?></td>
                        <td><?= e(shop_status_label((string) $product['status'])) ?></td>
                        <td><a href="<?= e(route_url('admin_shop', ['edit_product' => (int) $product['id']])) ?>"><?= e($t('edit')) ?></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($products === []): ?><tr><td colspan="6"><?= e($t('no_products')) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2><?= e($t('orders')) ?></h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th><?= e($t('th_reference')) ?></th><th><?= e($t('th_member')) ?></th><th><?= e($t('th_total')) ?></th><th><?= e($t('th_status')) ?></th><th><?= e($t('th_action')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= e((string) $order['reference_code']) ?></td>
                        <td><?= e((string) ($order['callsign'] ?? '—')) ?></td>
                        <td><?= e(format_price_eur((int) $order['total_cents'])) ?></td>
                        <td><?= e(shop_order_status_label((string) $order['status'])) ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="save_order_status">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                <select name="status">
                                    <?php foreach (['pending','confirmed','ready','completed','cancelled'] as $status): ?>
                                        <option value="<?= e($status) ?>" <?= ((string) $order['status'] === $status) ? 'selected' : '' ?>><?= e(shop_order_status_label($status)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="ghost"><?= e($t('ok')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($orders === []): ?><tr><td colspan="5"><?= e($t('no_orders')) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), $t('layout'));
