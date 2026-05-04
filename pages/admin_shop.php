<?php
declare(strict_types=1);

require_permission('admin.access');
require_permission('shop.manage');
$locale = current_locale();
$i18n = [
    'fr' => ['layout' => 'Administration boutique', 'no_orders' => 'Aucune commande.'],
    'en' => ['layout' => 'Shop administration', 'no_orders' => 'No orders.'],
    'de' => ['layout' => 'Shop-Verwaltung', 'no_orders' => 'Keine Bestellungen.'],
    'nl' => ['layout' => 'Winkelbeheer', 'no_orders' => 'Geen bestellingen.'],
];
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'save_product');

        if ($action === 'save_category') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Nom de catégorie obligatoire.');
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
            set_flash('success', 'Catégorie enregistrée.');
        } elseif ($action === 'save_product') {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            if ($title === '') {
                throw new RuntimeException('Titre produit obligatoire.');
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
            set_flash('success', 'Produit enregistré.');
        } elseif ($action === 'save_order_status') {
            db()->prepare('UPDATE shop_orders SET status = ? WHERE id = ?')->execute([
                (string) ($_POST['status'] ?? 'pending'),
                (int) ($_POST['order_id'] ?? 0),
            ]);
            set_flash('success', 'Commande mise à jour.');
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
        <h1><?= $editCategory ? 'Modifier' : 'Créer' ?> une catégorie</h1>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="id" value="<?= (int) ($editCategory['id'] ?? 0) ?>">
            <label>Nom<input type="text" name="name" value="<?= e((string) ($editCategory['name'] ?? '')) ?>" required></label>
            <label>Slug<input type="text" name="slug" value="<?= e((string) ($editCategory['slug'] ?? '')) ?>"></label>
            <label>Description<textarea name="description" rows="3"><?= e((string) ($editCategory['description'] ?? '')) ?></textarea></label>
            <div class="grid-2">
                <label>Ordre<input type="number" name="sort_order" value="<?= (int) ($editCategory['sort_order'] ?? 0) ?>"></label>
                <label><input type="checkbox" name="is_active" value="1" <?= !empty($editCategory['is_active']) || $editCategory === null ? 'checked' : '' ?>> Active</label>
            </div>
            <button class="button">Enregistrer la catégorie</button>
        </form>
    </section>

    <section class="card">
        <h1><?= $editProduct ? 'Modifier' : 'Créer' ?> un produit</h1>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_product">
            <input type="hidden" name="id" value="<?= (int) ($editProduct['id'] ?? 0) ?>">
            <label>Titre<input type="text" name="title" value="<?= e((string) ($editProduct['title'] ?? '')) ?>" required></label>
            <label>Slug<input type="text" name="slug" value="<?= e((string) ($editProduct['slug'] ?? '')) ?>"></label>
            <label>Catégorie
                <select name="category_id">
                    <option value="0">Aucune</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= ((int) ($editProduct['category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>><?= e((string) $category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Résumé<textarea name="summary" rows="3"><?= e((string) ($editProduct['summary'] ?? '')) ?></textarea></label>
            <label>Description<textarea name="description" rows="5"><?= e((string) ($editProduct['description'] ?? '')) ?></textarea></label>
            <div class="grid-2">
                <label>Prix (€)<input type="text" name="price" value="<?= e(number_format(((int) ($editProduct['price_cents'] ?? 0)) / 100, 2, ',', '')) ?>"></label>
                <label>Stock (laisser vide pour illimité)<input type="text" name="stock_qty" value="<?= e(array_key_exists('stock_qty', $editProduct) ? (string) ($editProduct['stock_qty'] ?? '') : '') ?>"></label>
            </div>
            <label>Image URL<input type="text" name="image_url" value="<?= e((string) ($editProduct['image_url'] ?? '')) ?>"></label>
            <div class="grid-2">
                <label><input type="checkbox" name="is_featured" value="1" <?= !empty($editProduct['is_featured']) ? 'checked' : '' ?>> Produit mis en avant</label>
                <label>Statut
                    <select name="status">
                        <?php foreach (['draft','published','archived'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= (($editProduct['status'] ?? 'draft') === $status) ? 'selected' : '' ?>><?= e(shop_status_label($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <button class="button">Enregistrer le produit</button>
        </form>
    </section>
</div>

<div class="grid-2">
    <section class="card">
        <h2>Catalogue</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Statut</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= e((string) $product['title']) ?></td>
                        <td><?= e((string) ($product['category_name'] ?? '—')) ?></td>
                        <td><?= e(format_price_eur((int) $product['price_cents'])) ?></td>
                        <td><?= e(format_integer_or_unlimited($product['stock_qty'] !== null ? (int) $product['stock_qty'] : null)) ?></td>
                        <td><?= e(shop_status_label((string) $product['status'])) ?></td>
                        <td><a href="<?= e(route_url('admin_shop', ['edit_product' => (int) $product['id']])) ?>">Éditer</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($products === []): ?><tr><td colspan="6">Aucun produit.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Commandes</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Référence</th><th>Membre</th><th>Total</th><th>Statut</th><th>Action</th></tr></thead>
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
                                <button class="ghost">OK</button>
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
