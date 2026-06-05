<?php
declare(strict_types=1);

require_permission('admin.access');
require_login();
privacy_ensure_tables();

set_page_meta([
    'title' => 'Administration RGPD',
    'description' => 'Traitement des demandes RGPD ON4CRD.',
    'robots' => 'noindex,nofollow',
]);

$statuses = ['pending', 'in_progress', 'resolved', 'rejected'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $id = (int) ($_POST['request_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'pending');
        $adminNotes = trim((string) ($_POST['admin_notes'] ?? ''));
        if ($id <= 0 || !in_array($status, $statuses, true)) {
            throw new RuntimeException('Demande invalide.');
        }

        $stmt = db()->prepare(
            'UPDATE privacy_requests
             SET status = ?,
                 admin_notes = ?,
                 resolved_at = CASE WHEN ? IN ("resolved", "rejected") THEN COALESCE(resolved_at, NOW()) ELSE NULL END
             WHERE id = ?'
        );
        $stmt->execute([$status, $adminNotes !== '' ? $adminNotes : null, $status, $id]);
        set_flash('success', 'Demande RGPD mise a jour.');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }

    redirect('admin_privacy');
}

$requests = [];
try {
    $requests = db()->query(
        'SELECT pr.*, m.callsign, m.full_name, m.email
         FROM privacy_requests pr
         LEFT JOIN members m ON m.id = pr.member_id
         ORDER BY FIELD(pr.status, "pending", "in_progress", "resolved", "rejected"), pr.requested_at DESC
         LIMIT 500'
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $exception) {
    $requests = [];
}

ob_start();
?>
<section class="card">
    <h1>Administration RGPD</h1>
    <p class="help">Les demandes sont conservees avec IP et user-agent pseudonymises. Traitez les demandes hors site si une verification d identite complementaire est necessaire.</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Membre</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Dates</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?= (int) $request['id'] ?></td>
                    <td>
                        <?= e((string) ($request['callsign'] ?? '')) ?>
                        <div class="help"><?= e((string) ($request['full_name'] ?? '')) ?> <?= e((string) ($request['email'] ?? '')) ?></div>
                    </td>
                    <td><?= e((string) $request['request_type']) ?></td>
                    <td><?= e((string) $request['status']) ?></td>
                    <td>
                        <?= e((string) $request['requested_at']) ?>
                        <?php if (!empty($request['resolved_at'])): ?><div class="help"><?= e((string) $request['resolved_at']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <div><?= nl2br(e((string) ($request['notes'] ?? ''))) ?></div>
                        <?php if (!empty($request['admin_notes'])): ?><div class="help"><?= nl2br(e((string) $request['admin_notes'])) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <form method="post" class="stack">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                            <select name="status">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= e($status) ?>" <?= (string) $request['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="admin_notes" rows="3" maxlength="2000"><?= e((string) ($request['admin_notes'] ?? '')) ?></textarea>
                            <button class="button small" type="submit">Mettre a jour</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($requests === []): ?><tr><td colspan="7">Aucune demande RGPD.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php

echo render_layout((string) ob_get_clean(), 'Administration RGPD');
