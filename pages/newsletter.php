<?php
declare(strict_types=1);

$user = require_login();
newsletter_ensure_tables();

set_page_meta([
    'title' => 'Préférences newsletter',
    'description' => 'Gestion de votre abonnement newsletter ON4CRD.',
    'robots' => 'noindex,nofollow',
]);

$memberId = (int) ($user['id'] ?? 0);
$memberEmail = newsletter_normalize_email((string) ($user['email'] ?? ''));
$current = newsletter_subscriber_for_member($memberId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'subscribe') {
            $email = newsletter_normalize_email((string) ($_POST['email'] ?? $memberEmail));
            if ($email === '') {
                throw new RuntimeException('Adresse email invalide.');
            }
            newsletter_upsert_subscriber($email, $memberId, 'member');
            set_flash('success', 'Vous êtes abonné à la newsletter.');
        } elseif ($action === 'unsubscribe') {
            if ($current === null) {
                throw new RuntimeException('Aucun abonnement trouvé.');
            }
            newsletter_set_subscriber_status((int) $current['id'], 'unsubscribed');
            set_flash('success', 'Vous êtes désabonné de la newsletter.');
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }

    redirect('newsletter');
}

$current = newsletter_subscriber_for_member($memberId);
$isSubscribed = $current !== null && (string) ($current['status'] ?? '') === 'active';

ob_start();
?>
<div class="card">
    <h1>Préférences newsletter</h1>
    <p>Abonnez-vous pour recevoir les actualités du radio-club par email. Vous pouvez vous désabonner à tout moment.</p>

    <?php if ($isSubscribed): ?>
        <p><strong>Statut actuel :</strong> abonné (<?= e((string) $current['email']) ?>)</p>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="unsubscribe">
            <button class="button danger">Se désabonner</button>
        </form>
    <?php else: ?>
        <p><strong>Statut actuel :</strong> non abonné</p>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="subscribe">
            <label>Email de contact
                <input type="email" name="email" value="<?= e($memberEmail) ?>" required>
            </label>
            <button class="button">S'abonner</button>
        </form>
    <?php endif; ?>
</div>
<?php

echo render_layout((string) ob_get_clean(), 'Newsletter');
