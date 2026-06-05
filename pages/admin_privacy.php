<?php
declare(strict_types=1);

require_permission('privacy.manage');
$adminUser = require_login();
privacy_ensure_tables();

set_page_meta([
    'title' => 'Administration RGPD',
    'description' => 'Traitement des demandes RGPD ON4CRD.',
    'robots' => 'noindex,nofollow',
]);

$statuses = privacy_request_statuses();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $id = (int) ($_POST['request_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'pending');
        $adminNotes = trim((string) ($_POST['admin_notes'] ?? ''));
        $applyErasure = (string) ($_POST['apply_erasure'] ?? '') === '1';

        $result = privacy_update_request_status($id, $status, $adminNotes, (int) ($adminUser['id'] ?? 0), $applyErasure);
        $fileStats = (array) ($result['files'] ?? []);
        $message = 'Demande RGPD mise a jour.';
        if ($applyErasure) {
            $message .= ' Fichiers traites: ' . (int) ($fileStats['deleted'] ?? 0) . ' supprimes, ' . (int) ($fileStats['missing'] ?? 0) . ' deja absents, ' . (int) ($fileStats['failed'] ?? 0) . ' en echec.';
        }
        set_flash('success', $message);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }

    redirect('admin_privacy');
}

$requests = [];
try {
    $requests = db()->query(
        'SELECT pr.*, m.callsign, m.full_name, m.email, admin_m.callsign AS processed_by_callsign
         FROM privacy_requests pr
         LEFT JOIN members m ON m.id = pr.member_id
         LEFT JOIN members admin_m ON admin_m.id = pr.processed_by_member_id
         ORDER BY FIELD(pr.status, "pending", "in_progress", "resolved", "rejected"), pr.requested_at DESC
         LIMIT 500'
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $exception) {
    privacy_log_internal('privacy_admin_list_failed', ['message' => $exception->getMessage()]);
    $requests = [];
}

$requestIds = array_map(static fn(array $request): int => (int) ($request['id'] ?? 0), $requests);
$eventsByRequest = privacy_request_events_for_request_ids($requestIds);

ob_start();
?>
<section class="card">
    <h1>Administration RGPD</h1>
    <p class="help">Acces reserve a la permission privacy.manage. Les changements de statut, anonymisations et traitements de fichiers sont journalises.</p>
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
                    <th>Historique</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <?php
                    $requestId = (int) $request['id'];
                    $requestType = (string) $request['request_type'];
                    $requestEvents = $eventsByRequest[$requestId] ?? [];
                ?>
                <tr>
                    <td><?= $requestId ?></td>
                    <td>
                        <?= e((string) ($request['callsign'] ?? '')) ?>
                        <div class="help"><?= e((string) ($request['full_name'] ?? '')) ?> <?= e((string) ($request['email'] ?? '')) ?></div>
                    </td>
                    <td><?= e($requestType) ?></td>
                    <td><?= e((string) $request['status']) ?></td>
                    <td>
                        <?= e((string) $request['requested_at']) ?>
                        <?php if (!empty($request['processed_at'])): ?><div class="help">Traite: <?= e((string) $request['processed_at']) ?> <?= e((string) ($request['processed_by_callsign'] ?? '')) ?></div><?php endif; ?>
                        <?php if (!empty($request['resolved_at'])): ?><div class="help">Resolution: <?= e((string) $request['resolved_at']) ?></div><?php endif; ?>
                        <?php if (!empty($request['erasure_completed_at'])): ?><div class="help">Anonymisation: <?= e((string) $request['erasure_completed_at']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <div><?= nl2br(e((string) ($request['notes'] ?? ''))) ?></div>
                        <?php if (!empty($request['admin_notes'])): ?><div class="help"><?= nl2br(e((string) $request['admin_notes'])) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($requestEvents === []): ?>
                            <span class="help">Aucun evenement.</span>
                        <?php else: ?>
                            <details>
                                <summary><?= count($requestEvents) ?> evenement(s)</summary>
                                <ul class="list-clean list-spaced">
                                    <?php foreach ($requestEvents as $event): ?>
                                        <li>
                                            <strong><?= e((string) ($event['event_type'] ?? '')) ?></strong>
                                            <span class="help"><?= e((string) ($event['created_at'] ?? '')) ?> <?= e((string) ($event['admin_callsign'] ?? '')) ?></span>
                                            <?php if (!empty($event['from_status']) || !empty($event['to_status'])): ?>
                                                <div class="help"><?= e((string) ($event['from_status'] ?? '')) ?> -> <?= e((string) ($event['to_status'] ?? '')) ?></div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" class="stack">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="request_id" value="<?= $requestId ?>">
                            <label>Statut
                                <select name="status">
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= e($status) ?>" <?= (string) $request['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <?php if ($requestType === 'erasure'): ?>
                                <label class="checkbox">
                                    <input type="checkbox" name="apply_erasure" value="1">
                                    <span>Appliquer l anonymisation automatique si le statut passe a resolved.</span>
                                </label>
                            <?php endif; ?>
                            <label>Notes administrateur
                                <textarea name="admin_notes" rows="3" maxlength="2000"><?= e((string) ($request['admin_notes'] ?? '')) ?></textarea>
                            </label>
                            <button class="button small" type="submit">Mettre a jour</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($requests === []): ?><tr><td colspan="8">Aucune demande RGPD.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php

echo render_layout((string) ob_get_clean(), 'Administration RGPD');
