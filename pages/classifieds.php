<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$i18n = i18n_domain_messages('classifieds');
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $i18n['fr'][$key] ?? $key);
};

if (!module_enabled('classifieds')) {
    echo render_layout('<div class="card"><p>Module disabled.</p></div>', $t('title'));
    return;
}

if (!table_exists('classified_ads')) {
    $message = '<section class="card"><h1>' . e($t('title')) . '</h1><p class="help">Module temporairement indisponible : table <code>classified_ads</code> manquante.</p></section>';
    echo render_layout($message, $t('title'));
    return;
}

$categories = ['gear' => 'Matériel', 'wanted' => 'Recherche', 'service' => 'Service'];
$editing = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM classified_ads WHERE id = ? AND owner_member_id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit'], (int) $user['id']]);
    $editing = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'save');

        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $payload = [
                'category_code' => (string) ($_POST['category_code'] ?? 'gear'),
                'title' => trim((string) ($_POST['title'] ?? '')),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'location' => trim((string) ($_POST['location'] ?? '')),
                'contact' => trim((string) ($_POST['contact'] ?? '')),
                'price_cents' => (int) round(((float) str_replace(',', '.', (string) ($_POST['price'] ?? '0'))) * 100),
            ];
            if ($payload['title'] === '' || !isset($categories[$payload['category_code']])) {
                throw new RuntimeException($t('invalid'));
            }

            if ($id > 0) {
                $stmt = db()->prepare('UPDATE classified_ads SET category_code = ?, title = ?, description = ?, location = ?, contact = ?, price_cents = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
                $stmt->execute([$payload['category_code'], $payload['title'], $payload['description'], $payload['location'], $payload['contact'], max(0, $payload['price_cents']), $id, (int) $user['id']]);
                set_flash('success', $t('updated_ok'));
            } else {
                $stmt = db()->prepare('INSERT INTO classified_ads (owner_member_id, category_code, title, description, location, contact, price_cents) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([(int) $user['id'], $payload['category_code'], $payload['title'], $payload['description'], $payload['location'], $payload['contact'], max(0, $payload['price_cents'])]);
                set_flash('success', $t('created_ok'));
            }
        }

        if ($action === 'set_status') {
            $id = (int) ($_POST['id'] ?? 0);
            $status = (string) ($_POST['status'] ?? 'active');
            if (!in_array($status, ['active', 'sold'], true)) {
                throw new RuntimeException($t('invalid'));
            }
            $stmt = db()->prepare('UPDATE classified_ads SET status = ?, updated_at = NOW() WHERE id = ? AND owner_member_id = ?');
            $stmt->execute([$status, $id, (int) $user['id']]);
            set_flash('success', $t('status_ok'));
        }

        redirect('classifieds');
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
        redirect('classifieds');
    }
}

$myStmt = db()->prepare('SELECT * FROM classified_ads WHERE owner_member_id = ? ORDER BY created_at DESC');
$myStmt->execute([(int) $user['id']]);
$myAds = $myStmt->fetchAll();

$allAds = db()->query("SELECT ca.*, m.callsign FROM classified_ads ca LEFT JOIN members m ON m.id = ca.owner_member_id WHERE ca.status = 'active' ORDER BY ca.created_at DESC LIMIT 60")->fetchAll();

set_page_meta(['title' => $t('title'), 'description' => $t('lead')]);
ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1><?= e($editing ? $t('edit') : $t('new_ad')) ?></h1>
        <p class="help"><?= e($t('lead')) ?></p>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
            <label><?= e($t('category_label')) ?>
                <select name="category_code">
                    <?php foreach ($categories as $code => $label): ?>
                    <option value="<?= e($code) ?>" <?= (($editing['category_code'] ?? 'gear') === $code) ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e($t('title_label')) ?><input type="text" name="title" required value="<?= e((string) ($editing['title'] ?? '')) ?>"></label>
            <label><?= e($t('description_label')) ?><textarea name="description" rows="5"><?= e((string) ($editing['description'] ?? '')) ?></textarea></label>
            <label><?= e($t('price_label')) ?><input type="text" name="price" value="<?= e(number_format(((int) ($editing['price_cents'] ?? 0)) / 100, 2, '.', '')) ?>"></label>
            <label><?= e($t('location_label')) ?><input type="text" name="location" value="<?= e((string) ($editing['location'] ?? '')) ?>"></label>
            <label><?= e($t('contact_label')) ?><input type="text" name="contact" value="<?= e((string) ($editing['contact'] ?? ((string) ($user['callsign'] ?? '')))) ?>"></label>
            <p><button class="button"><?= e($t('save')) ?></button></p>
        </form>
    </section>

    <section class="card">
        <h2><?= e($t('my_ads')) ?></h2>
        <?php if ($myAds === []): ?><p class="help"><?= e($t('none')) ?></p><?php else: ?>
            <div class="table-wrap"><table><thead><tr><th><?= e($t('title_label')) ?></th><th><?= e($t('status_label')) ?></th><th><?= e($t('actions')) ?></th></tr></thead><tbody>
            <?php foreach ($myAds as $ad): ?>
                <tr>
                    <td><strong><?= e((string) $ad['title']) ?></strong><div class="help"><?= e((string) $ad['location']) ?> · <?= e(number_format(((int) $ad['price_cents']) / 100, 2, ',', ' ')) ?> €</div></td>
                    <td><span class="badge muted"><?= e((string) $ad['status']) ?></span></td>
                    <td>
                        <a href="<?= e(route_url('classifieds', ['edit' => (int) $ad['id']])) ?>"><?= e($t('edit')) ?></a>
                        <form method="post" class="inline-form" style="display:inline-flex;gap:.4rem;margin-left:.5rem;">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
                            <button class="button ghost" name="status" value="sold"><?= e($t('mark_sold')) ?></button>
                            <button class="button ghost" name="status" value="active"><?= e($t('reactivate')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </section>
</div>

<section class="card" style="margin-top:1rem;">
    <h2><?= e($t('all_ads')) ?></h2>
    <?php if ($allAds === []): ?><p class="help"><?= e($t('none')) ?></p><?php else: ?>
        <div class="stack">
            <?php foreach ($allAds as $ad): ?>
            <article class="card" style="margin:0;">
                <h3 style="margin:0;"><?= e((string) $ad['title']) ?></h3>
                <p class="help"><?= e((string) ($categories[$ad['category_code']] ?? $ad['category_code'])) ?> · <?= e((string) ($ad['callsign'] ?? 'N/A')) ?> · <?= e((string) $ad['location']) ?></p>
                <p><?= nl2br(e((string) $ad['description'])) ?></p>
                <p><strong><?= e(number_format(((int) $ad['price_cents']) / 100, 2, ',', ' ')) ?> €</strong> — <?= e((string) $ad['contact']) ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php

echo render_layout((string) ob_get_clean(), $t('title'));
