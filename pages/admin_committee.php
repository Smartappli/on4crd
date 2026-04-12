<?php
declare(strict_types=1);

require_permission('admin.access');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $stmt = db()->prepare('UPDATE members SET is_committee = ?, committee_role = ?, committee_bio = ?, committee_sort_order = ? WHERE id = ?');
        foreach ((array) ($_POST['members'] ?? []) as $memberId => $payload) {
            $stmt->execute([
                !empty($payload['is_committee']) ? 1 : 0,
                trim((string) ($payload['committee_role'] ?? '')),
                trim((string) ($payload['committee_bio'] ?? '')),
                (int) ($payload['committee_sort_order'] ?? 100),
                (int) $memberId,
            ]);
        }
        set_flash('success', 'Comité mis à jour.');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_committee');
}

$rows = db()->query('SELECT id, callsign, full_name, is_committee, committee_role, committee_bio, committee_sort_order FROM members WHERE is_active = 1 ORDER BY callsign ASC')->fetchAll();

ob_start();
?>
<div class="card">
    <h1>Gestion du comité</h1>
    <p>Sélectionnez les membres qui doivent apparaître sur la page comité, définissez leur rôle, leur ordre et une courte biographie.</p>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="stack">
            <?php foreach ($rows as $row): ?>
                <section class="card muted-card">
                    <div class="row-between">
                        <strong><?= e((string) $row['callsign']) ?> — <?= e((string) $row['full_name']) ?></strong>
                        <label><input type="checkbox" name="members[<?= (int) $row['id'] ?>][is_committee]" value="1" <?= (int) $row['is_committee'] === 1 ? 'checked' : '' ?>> Afficher sur la page comité</label>
                    </div>
                    <div class="form-grid">
                        <label>Rôle au comité
                            <input type="text" name="members[<?= (int) $row['id'] ?>][committee_role]" value="<?= e((string) $row['committee_role']) ?>">
                        </label>
                        <label>Ordre d'affichage
                            <input type="number" name="members[<?= (int) $row['id'] ?>][committee_sort_order]" value="<?= e((string) $row['committee_sort_order']) ?>">
                        </label>
                        <label>Biographie courte
                            <textarea name="members[<?= (int) $row['id'] ?>][committee_bio]" rows="3"><?= e((string) $row['committee_bio']) ?></textarea>
                        </label>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <p><button class="button">Enregistrer</button></p>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Gestion du comité');
