<?php
declare(strict_types=1);

require_permission('admin.access');
require_permission('auctions.manage');

$t = i18n_domain_locale('admin_auctions');

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException((string) $t['err_title_required']);
        }
        if (mb_strlen($title) > 190 || mb_strlen(trim((string) ($_POST['summary'] ?? ''))) > 10000) {
            throw new RuntimeException((string) $t['err_title_required']);
        }
        if ($id > 0 && auction_lot_by_id($id) === null) {
            throw new RuntimeException((string) $t['err_title_required']);
        }
        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $slug = auction_unique_slug($slugInput !== '' ? $slugInput : $title, $id);
        $startsAtRaw = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAtRaw = trim((string) ($_POST['ends_at'] ?? ''));
        $startsAtTs = $startsAtRaw !== '' ? strtotime($startsAtRaw) : time();
        $endsAtTs = $endsAtRaw !== '' ? strtotime($endsAtRaw) : strtotime('+7 days');
        if ($startsAtTs === false || $endsAtTs === false || $endsAtTs <= $startsAtTs) {
            throw new RuntimeException((string) $t['err_title_required']);
        }
        $status = (string) ($_POST['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'scheduled', 'active', 'closed', 'cancelled'], true)) {
            throw new RuntimeException((string) $t['err_title_required']);
        }
        $startingPrice = max(0, parse_price_to_cents((string) ($_POST['starting_price'] ?? '0')));
        $reservePrice = trim((string) ($_POST['reserve_price'] ?? '')) !== '' ? max(0, parse_price_to_cents((string) $_POST['reserve_price'])) : null;
        $minIncrement = max(1, parse_price_to_cents((string) ($_POST['min_increment'] ?? '0')));
        $buyNowPrice = trim((string) ($_POST['buy_now_price'] ?? '')) !== '' ? max(0, parse_price_to_cents((string) $_POST['buy_now_price'])) : null;
        $params = [
            $slug,
            $title,
            trim((string) ($_POST['summary'] ?? '')),
            sanitize_rich_html((string) ($_POST['description'] ?? '')),
            sanitize_image_src_attribute((string) ($_POST['image_url'] ?? '')) ?? '',
            $startingPrice,
            $reservePrice,
            $minIncrement,
            $buyNowPrice,
            date('Y-m-d H:i:s', $startsAtTs),
            date('Y-m-d H:i:s', $endsAtTs),
            $status,
        ];
        if ($id > 0) {
            db()->prepare('UPDATE auction_lots SET slug = ?, title = ?, summary = ?, description = ?, image_url = ?, starting_price_cents = ?, reserve_price_cents = ?, min_increment_cents = ?, buy_now_price_cents = ?, starts_at = ?, ends_at = ?, status = ? WHERE id = ?')
                ->execute([...$params, $id]);
        } else {
            db()->prepare('INSERT INTO auction_lots (slug, title, summary, description, image_url, starting_price_cents, reserve_price_cents, min_increment_cents, buy_now_price_cents, starts_at, ends_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute($params);
        }
        set_flash('success', (string) $t['ok_saved']);
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
        <h1><?= $edit ? e((string) $t['edit']) : e((string) $t['create']) ?> <?= e((string) $t['a_lot']) ?></h1>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
            <label><?= e((string) $t['title']) ?><input type="text" name="title" value="<?= e((string) ($edit['title'] ?? '')) ?>" required></label>
            <label><?= e((string) $t['slug']) ?><input type="text" name="slug" value="<?= e((string) ($edit['slug'] ?? '')) ?>"></label>
            <label><?= e((string) $t['summary']) ?><textarea name="summary" rows="3"><?= e((string) ($edit['summary'] ?? '')) ?></textarea></label>
            <label><?= e((string) $t['description']) ?><textarea name="description" rows="6"><?= e((string) ($edit['description'] ?? '')) ?></textarea></label>
            <label><?= e((string) $t['image_url']) ?><input type="text" name="image_url" value="<?= e((string) ($edit['image_url'] ?? '')) ?>"></label>
            <div class="grid-2">
                <label><?= e((string) $t['starting_price']) ?><input type="text" name="starting_price" value="<?= e(number_format(((int) ($edit['starting_price_cents'] ?? 0)) / 100, 2, ',', '')) ?>"></label>
                <label><?= e((string) $t['reserve_price']) ?><input type="text" name="reserve_price" value="<?= e(!empty($edit['reserve_price_cents']) ? number_format(((int) $edit['reserve_price_cents']) / 100, 2, ',', '') : '') ?>"></label>
                <label><?= e((string) $t['min_increment']) ?><input type="text" name="min_increment" value="<?= e(number_format(((int) ($edit['min_increment_cents'] ?? 100)) / 100, 2, ',', '')) ?>"></label>
                <label><?= e((string) $t['buy_now']) ?><input type="text" name="buy_now_price" value="<?= e(!empty($edit['buy_now_price_cents']) ? number_format(((int) $edit['buy_now_price_cents']) / 100, 2, ',', '') : '') ?>"></label>
            </div>
            <div class="grid-2">
                <label><?= e((string) $t['start']) ?><input type="datetime-local" name="starts_at" value="<?= !empty($edit['starts_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $edit['starts_at']))) : '' ?>"></label>
                <label><?= e((string) $t['end']) ?><input type="datetime-local" name="ends_at" value="<?= !empty($edit['ends_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $edit['ends_at']))) : '' ?>"></label>
            </div>
            <label><?= e((string) $t['status']) ?>
                <select name="status">
                    <?php foreach (['draft','scheduled','active','closed','cancelled'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= ((string) ($edit['status'] ?? 'draft') === $status) ? 'selected' : '' ?>><?= e(auction_status_label($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button"><?= e((string) $t['save_lot']) ?></button>
        </form>
    </section>
    <section class="card">
        <h2><?= e((string) $t['saved_lots']) ?></h2>
        <?php if ($rows === []): ?><p><?= e((string) $t['no_lot']) ?></p><?php else: ?><ul class="list-clean list-spaced"><?php foreach ($rows as $row): ?><li><a href="<?= e(route_url('admin_auctions', ['edit' => (int) $row['id']])) ?>"><?= e((string) $row['title']) ?></a><span class="help"><?= e(auction_status_label(auction_runtime_status($row))) ?> — <?= e(format_price_eur(max((int) $row['current_price_cents'], (int) $row['starting_price_cents']))) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
    </section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
