<?php
declare(strict_types=1);

require_permission('admin.access');
require_permission('auctions.manage');

$locale = current_locale();
$i18n = [
    'fr' => ['err_title_required' => 'Titre du lot obligatoire.', 'ok_saved' => 'Lot enregistré.', 'edit' => 'Modifier', 'create' => 'Créer', 'a_lot' => 'un lot', 'title' => 'Titre', 'slug' => 'Slug', 'summary' => 'Résumé', 'description' => 'Description', 'image_url' => 'Image URL', 'starting_price' => 'Prix de départ', 'reserve_price' => 'Prix de réserve', 'min_increment' => 'Pas minimal', 'buy_now' => 'Achat immédiat', 'start' => 'Début', 'end' => 'Fin', 'status' => 'Statut', 'save_lot' => 'Enregistrer le lot', 'saved_lots' => 'Lots enregistrés', 'no_lot' => 'Aucun lot.', 'layout' => 'Administration enchères'],
    'en' => ['err_title_required' => 'Lot title is required.', 'ok_saved' => 'Lot saved.', 'edit' => 'Edit', 'create' => 'Create', 'a_lot' => 'a lot', 'title' => 'Title', 'slug' => 'Slug', 'summary' => 'Summary', 'description' => 'Description', 'image_url' => 'Image URL', 'starting_price' => 'Starting price', 'reserve_price' => 'Reserve price', 'min_increment' => 'Minimum increment', 'buy_now' => 'Buy now', 'start' => 'Start', 'end' => 'End', 'status' => 'Status', 'save_lot' => 'Save lot', 'saved_lots' => 'Saved lots', 'no_lot' => 'No lot.', 'layout' => 'Auctions administration'],
    'de' => ['err_title_required' => 'Los-Titel ist erforderlich.', 'ok_saved' => 'Los gespeichert.', 'edit' => 'Bearbeiten', 'create' => 'Erstellen', 'a_lot' => 'ein Los', 'title' => 'Titel', 'slug' => 'Slug', 'summary' => 'Zusammenfassung', 'description' => 'Beschreibung', 'image_url' => 'Bild-URL', 'starting_price' => 'Startpreis', 'reserve_price' => 'Mindestpreis', 'min_increment' => 'Mindestschritt', 'buy_now' => 'Sofortkauf', 'start' => 'Beginn', 'end' => 'Ende', 'status' => 'Status', 'save_lot' => 'Los speichern', 'saved_lots' => 'Gespeicherte Lose', 'no_lot' => 'Kein Los.', 'layout' => 'Auktionsverwaltung'],
    'nl' => ['err_title_required' => 'Titel van lot is verplicht.', 'ok_saved' => 'Lot opgeslagen.', 'edit' => 'Bewerken', 'create' => 'Aanmaken', 'a_lot' => 'een lot', 'title' => 'Titel', 'slug' => 'Slug', 'summary' => 'Samenvatting', 'description' => 'Beschrijving', 'image_url' => 'Afbeeldings-URL', 'starting_price' => 'Startprijs', 'reserve_price' => 'Minimumprijs', 'min_increment' => 'Minimale stap', 'buy_now' => 'Direct kopen', 'start' => 'Start', 'end' => 'Einde', 'status' => 'Status', 'save_lot' => 'Lot opslaan', 'saved_lots' => 'Opgeslagen loten', 'no_lot' => 'Geen lot.', 'layout' => 'Veilingbeheer'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException((string) $t['err_title_required']);
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
            <label>Slug<input type="text" name="slug" value="<?= e((string) ($edit['slug'] ?? '')) ?>"></label>
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
