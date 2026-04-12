<?php
declare(strict_types=1);

require_permission('admin.access');
require_permission('auctions.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException('Titre du lot obligatoire.');
        }
        $slug = trim((string) ($_POST['slug'] ?? ''));
        if ($slug === '') {
            $slug = slugify($title);
        }
        $startsAtRaw = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAtRaw = trim((string) ($_POST['ends_at'] ?? ''));
        $startsAt = $startsAtRaw !== '' ? str_replace('T', ' ', $startsAtRaw) . ':00' : date('Y-m-d H:i:s');
        $endsAt = $endsAtRaw !== '' ? str_replace('T', ' ', $endsAtRaw) . ':00' : date('Y-m-d H:i:s', strtotime('+7 days'));
        $params = [
            $slug,
            $title,
            trim((string) ($_POST['summary'] ?? '')),
            sanitize_rich_html((string) ($_POST['description'] ?? '')),
            sanitize_image_src_attribute((string) ($_POST['image_url'] ?? '')) ?? '',
            parse_price_to_cents((string) ($_POST['starting_price'] ?? '0')),
            trim((string) ($_POST['reserve_price'] ?? '')) !== '' ? parse_price_to_cents((string) $_POST['reserve_price']) : null,
            parse_price_to_cents((string) ($_POST['min_increment'] ?? '0')),
            trim((string) ($_POST['buy_now_price'] ?? '')) !== '' ? parse_price_to_cents((string) $_POST['buy_now_price']) : null,
            $startsAt,
            $endsAt,
            (string) ($_POST['status'] ?? 'draft'),
        ];
        if ($id > 0) {
            db()->prepare('UPDATE auction_lots SET slug = ?, title = ?, summary = ?, description = ?, image_url = ?, starting_price_cents = ?, reserve_price_cents = ?, min_increment_cents = ?, buy_now_price_cents = ?, starts_at = ?, ends_at = ?, status = ? WHERE id = ?')
                ->execute([...$params, $id]);
        } else {
            db()->prepare('INSERT INTO auction_lots (slug, title, summary, description, image_url, starting_price_cents, reserve_price_cents, min_increment_cents, buy_now_price_cents, starts_at, ends_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute($params);
        }
        set_flash('success', 'Lot enregistré.');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_auctions');
}

$edit = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM auction_lots WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}
$rows = table_exists('auction_lots') ? db()->query('SELECT * FROM auction_lots ORDER BY updated_at DESC, id DESC')->fetchAll() : [];

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= $edit ? 'Modifier' : 'Créer' ?> un lot</h1>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
            <label>Titre<input type="text" name="title" value="<?= e((string) ($edit['title'] ?? '')) ?>" required></label>
            <label>Slug<input type="text" name="slug" value="<?= e((string) ($edit['slug'] ?? '')) ?>"></label>
            <label>Résumé<textarea name="summary" rows="3"><?= e((string) ($edit['summary'] ?? '')) ?></textarea></label>
            <label>Description<textarea name="description" rows="6"><?= e((string) ($edit['description'] ?? '')) ?></textarea></label>
            <label>Image URL<input type="text" name="image_url" value="<?= e((string) ($edit['image_url'] ?? '')) ?>"></label>
            <div class="grid-2">
                <label>Prix de départ<input type="text" name="starting_price" value="<?= e(number_format(((int) ($edit['starting_price_cents'] ?? 0)) / 100, 2, ',', '')) ?>"></label>
                <label>Prix de réserve<input type="text" name="reserve_price" value="<?= e(!empty($edit['reserve_price_cents']) ? number_format(((int) $edit['reserve_price_cents']) / 100, 2, ',', '') : '') ?>"></label>
                <label>Pas minimal<input type="text" name="min_increment" value="<?= e(number_format(((int) ($edit['min_increment_cents'] ?? 100)) / 100, 2, ',', '')) ?>"></label>
                <label>Achat immédiat<input type="text" name="buy_now_price" value="<?= e(!empty($edit['buy_now_price_cents']) ? number_format(((int) $edit['buy_now_price_cents']) / 100, 2, ',', '') : '') ?>"></label>
            </div>
            <div class="grid-2">
                <label>Début<input type="datetime-local" name="starts_at" value="<?= !empty($edit['starts_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $edit['starts_at']))) : '' ?>"></label>
                <label>Fin<input type="datetime-local" name="ends_at" value="<?= !empty($edit['ends_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $edit['ends_at']))) : '' ?>"></label>
            </div>
            <label>Statut
                <select name="status">
                    <?php foreach (['draft','scheduled','active','closed','cancelled'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= ((string) ($edit['status'] ?? 'draft') === $status) ? 'selected' : '' ?>><?= e(auction_status_label($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button">Enregistrer le lot</button>
        </form>
    </section>
    <section class="card">
        <h2>Lots enregistrés</h2>
        <?php if ($rows === []): ?><p>Aucun lot.</p><?php else: ?><ul class="list-clean list-spaced"><?php foreach ($rows as $row): ?><li><a href="<?= e(route_url('admin_auctions', ['edit' => (int) $row['id']])) ?>"><?= e((string) $row['title']) ?></a><span class="help"><?= e(auction_status_label(auction_runtime_status($row))) ?> — <?= e(format_price_eur(max((int) $row['current_price_cents'], (int) $row['starting_price_cents']))) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Administration enchères');
