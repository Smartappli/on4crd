<?php
declare(strict_types=1);

$user = require_login();
$locale = current_locale();
$t = i18n_domain_locale('notifications', $locale);
set_page_meta([
    'title' => (string) ($t['title'] ?? 'Notifications'),
    'description' => (string) ($t['meta_desc'] ?? 'Member notifications center.'),
    'robots' => 'noindex,follow',
]);

$memberId = (int) ($user['id'] ?? 0);
if (!ensure_member_notifications_table()) {
    echo render_layout('<div class="card"><p>' . e((string) ($t['storage_unavailable'] ?? 'Notifications are temporarily unavailable.')) . '</p></div>', (string) ($t['title'] ?? 'Notifications'));
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'mark_all_read') {
        member_notifications_mark_all_read($memberId);
        set_flash('success', (string) ($t['all_marked_read'] ?? 'All notifications marked as read.'));
    } elseif ($action === 'mark_read') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId > 0) {
            member_notification_mark_read($memberId, $notificationId);
            set_flash('success', (string) ($t['marked_read'] ?? 'Notification marked as read.'));
        }
    }
    redirect_url(route_url_clean('notifications', [
        'filter' => (string) ($_GET['filter'] ?? ''),
        'p' => max(1, (int) ($_GET['p'] ?? 1)),
    ]));
}

$filter = trim((string) ($_GET['filter'] ?? ''));
$filter = in_array($filter, ['all', 'unread'], true) ? $filter : 'all';
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 20;

$where = ' WHERE member_id = ?';
$params = [$memberId];
if ($filter === 'unread') {
    $where .= ' AND is_read = 0';
}

$countStmt = db()->prepare('SELECT COUNT(*) FROM member_notifications' . $where);
$countStmt->execute($params);
$totalNotifications = (int) ($countStmt->fetchColumn() ?: 0);
$pagination = pagination_state($totalNotifications, $page, $perPage);
$page = $pagination['page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];

$stmt = db()->prepare('SELECT id, type, title, body, url, is_read, created_at FROM member_notifications' . $where . ' ORDER BY created_at DESC, id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
$stmt->execute($params);
$notifications = $stmt->fetchAll() ?: [];
$unreadCount = member_notifications_unread_count($memberId);

ob_start();
?>
<div class="notifications-page stack">
<section class="card notifications-hero member-module-hero">
    <div class="row-between">
        <div>
            <p class="eyebrow"><?= e(member_area_eyebrow_label($locale)) ?></p>
            <h1><?= e((string) ($t['title'] ?? 'Notifications')) ?></h1>
            <p><?= e((string) ($t['intro'] ?? 'Track publication, moderation and import updates in one place.')) ?></p>
        </div>
        <span class="badge muted"><?= $unreadCount ?> <?= e((string) ($t['unread'] ?? 'unread')) ?></span>
    </div>
</section>

<section class="card notifications-list-card">
    <form method="get" class="inline-form" style="margin-bottom:.8rem;flex-wrap:wrap;">
        <input type="hidden" name="route" value="notifications">
        <select name="filter">
            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>><?= e((string) ($t['filter_all'] ?? 'All notifications')) ?></option>
            <option value="unread" <?= $filter === 'unread' ? 'selected' : '' ?>><?= e((string) ($t['filter_unread'] ?? 'Unread only')) ?></option>
        </select>
        <button class="button" type="submit"><?= e((string) ($t['filter'] ?? 'Filter')) ?></button>
    </form>

    <form method="post" class="inline-form" style="margin-bottom:1rem;">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="mark_all_read">
        <button class="button secondary" type="submit"><?= e((string) ($t['mark_all_read'] ?? 'Mark all as read')) ?></button>
    </form>

    <?php if ($notifications === []): ?>
        <p class="help"><?= e((string) ($t['empty'] ?? 'No notifications for this filter.')) ?></p>
    <?php else: ?>
        <ul class="stack" style="list-style:none;padding:0;margin:0;">
            <?php foreach ($notifications as $item): ?>
                <?php $isRead = (int) ($item['is_read'] ?? 0) === 1; ?>
                <li class="card" style="margin:0;<?= $isRead ? '' : 'border-color:var(--accent-500);' ?>">
                    <div class="row-between" style="gap:.8rem;">
                        <strong><?= e((string) ($item['title'] ?? '')) ?></strong>
                        <span class="badge muted"><?= e($isRead ? (string) ($t['read'] ?? 'Read') : (string) ($t['unread'] ?? 'Unread')) ?></span>
                    </div>
                    <?php if (trim((string) ($item['body'] ?? '')) !== ''): ?><p class="help" style="margin:.35rem 0;"><?= e((string) $item['body']) ?></p><?php endif; ?>
                    <p class="help" style="margin:.2rem 0;"><?= e((string) ($item['created_at'] ?? '')) ?></p>
                    <p class="actions">
                        <?php if (trim((string) ($item['url'] ?? '')) !== ''): ?><a class="button secondary" href="<?= e((string) $item['url']) ?>"><?= e((string) ($t['open'] ?? 'Open')) ?></a><?php endif; ?>
                        <?php if (!$isRead): ?>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="notification_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                                <button class="button secondary" type="submit"><?= e((string) ($t['mark_read'] ?? 'Mark as read')) ?></button>
                            </form>
                        <?php endif; ?>
                    </p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="admin-library-pagination" aria-label="Pagination notifications">
            <?php if ($page > 1): ?><a class="button secondary" href="<?= e(route_url_clean('notifications', ['filter' => $filter, 'p' => $page - 1])) ?>">&larr; <?= e((string) ($t['prev'] ?? 'Previous')) ?></a><?php endif; ?>
            <span class="badge muted"><?= e((string) ($t['page'] ?? 'Page')) ?> <?= $page ?> / <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?><a class="button secondary" href="<?= e(route_url_clean('notifications', ['filter' => $filter, 'p' => $page + 1])) ?>"><?= e((string) ($t['next'] ?? 'Next')) ?> &rarr;</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) ($t['title'] ?? 'Notifications'));
