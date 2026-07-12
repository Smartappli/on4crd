<?php
declare(strict_types=1);

require_permission('privacy.manage');
$adminUser = require_login();
privacy_ensure_tables();
$locale = current_locale();
$t = i18n_domain_translator('admin_privacy', $locale);
$requestTypeLabels = [
    'access' => $t('type_access'),
    'rectification' => $t('type_rectification'),
    'erasure' => $t('type_erasure'),
    'restriction' => $t('type_restriction'),
    'objection' => $t('type_objection'),
    'portability' => $t('type_portability'),
];
$requestStatusLabels = [
    'pending' => $t('status_pending'),
    'in_progress' => $t('status_in_progress'),
    'resolved' => $t('status_resolved'),
    'rejected' => $t('status_rejected'),
];

set_page_meta([
    'title' => $t('title'),
    'description' => $t('meta_desc'),
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
        $message = $t('request_updated');
        if ($applyErasure) {
            $message .= ' ' . sprintf(
                $t('files_processed_summary'),
                (int) ($fileStats['deleted'] ?? 0),
                (int) ($fileStats['missing'] ?? 0),
                (int) ($fileStats['failed'] ?? 0)
            );
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
    <h1><?= e($t('title')) ?></h1>
    <p class="help"><?= e($t('intro')) ?></p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th><?= e($t('id')) ?></th>
                    <th><?= e($t('member')) ?></th>
                    <th><?= e($t('type')) ?></th>
                    <th><?= e($t('status')) ?></th>
                    <th><?= e($t('dates')) ?></th>
                    <th><?= e($t('notes')) ?></th>
                    <th><?= e($t('history')) ?></th>
                    <th><?= e($t('action')) ?></th>
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
                    <td><?= e($requestTypeLabels[$requestType] ?? $requestType) ?></td>
                    <td><?= e($requestStatusLabels[(string) $request['status']] ?? (string) $request['status']) ?></td>
                    <td>
                        <?= e((string) $request['requested_at']) ?>
                        <?php if (!empty($request['processed_at'])): ?><div class="help"><?= e($t('processed')) ?>: <?= e((string) $request['processed_at']) ?> <?= e((string) ($request['processed_by_callsign'] ?? '')) ?></div><?php endif; ?>
                        <?php if (!empty($request['resolved_at'])): ?><div class="help"><?= e($t('resolution')) ?>: <?= e((string) $request['resolved_at']) ?></div><?php endif; ?>
                        <?php if (!empty($request['erasure_completed_at'])): ?><div class="help"><?= e($t('anonymization')) ?>: <?= e((string) $request['erasure_completed_at']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <div><?= nl2br(e((string) ($request['notes'] ?? ''))) ?></div>
                        <?php if (!empty($request['admin_notes'])): ?><div class="help"><?= nl2br(e((string) $request['admin_notes'])) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($requestEvents === []): ?>
                            <span class="help"><?= e($t('no_events')) ?></span>
                        <?php else: ?>
                            <details>
                                <summary><?= e(sprintf($t('events_count'), count($requestEvents))) ?></summary>
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
                        <form method="post" class="stack" data-admin-dirty-track data-confirm-message="<?= e($t('confirm_apply_erasure')) ?>" data-confirm-when-checked="apply_erasure">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="request_id" value="<?= $requestId ?>">
                            <label><?= e($t('status')) ?>
                                <select name="status">
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= e($status) ?>" <?= (string) $request['status'] === $status ? 'selected' : '' ?>><?= e($requestStatusLabels[$status] ?? $status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <?php if ($requestType === 'erasure'): ?>
                                <label class="checkbox">
                                    <input type="checkbox" name="apply_erasure" value="1">
                                    <span><?= e($t('apply_erasure')) ?></span>
                                </label>
                            <?php endif; ?>
                            <label><?= e($t('admin_notes')) ?>
                                <textarea name="admin_notes" rows="3" maxlength="2000"><?= e((string) ($request['admin_notes'] ?? '')) ?></textarea>
                            </label>
                            <button class="button small" type="submit"><?= e($t('update')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($requests === []): ?><tr><td colspan="8"><?= e($t('none')) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php

echo render_layout((string) ob_get_clean(), $t('title'));
