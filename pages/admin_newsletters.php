<?php
declare(strict_types=1);

require_permission('admin.access');
$user = require_login();
newsletter_ensure_tables();
$locale = current_locale();
$i18n = [
    'fr' => ['meta_title' => 'Administration newsletter', 'meta_desc' => 'Gestion des abonnés et campagnes newsletter.', 'layout' => 'Administration newsletter'],
    'en' => ['meta_title' => 'Newsletter administration', 'meta_desc' => 'Manage subscribers and newsletter campaigns.', 'layout' => 'Newsletter administration'],
    'de' => ['meta_title' => 'Newsletter-Verwaltung', 'meta_desc' => 'Verwalten Sie Abonnenten und Newsletter-Kampagnen.', 'layout' => 'Newsletter-Verwaltung'],
    'nl' => ['meta_title' => 'Nieuwsbriefbeheer', 'meta_desc' => 'Beheer abonnees en nieuwsbriefcampagnes.', 'layout' => 'Nieuwsbriefbeheer'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

set_page_meta([
    'title' => (string) $t['meta_title'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'add_subscriber') {
            $email = (string) ($_POST['email'] ?? '');
            if (!newsletter_upsert_subscriber($email, null, 'admin')) {
                throw new RuntimeException('Adresse email invalide.');
            }
            set_flash('success', 'Abonné ajouté ou réactivé.');
        } elseif ($action === 'import_csv') {
            $content = trim((string) ($_POST['csv_content'] ?? ''));
            if ($content === '') {
                throw new RuntimeException('Collez le contenu CSV avant import.');
            }
            $count = newsletter_import_csv($content);
            set_flash('success', $count . ' adresse(s) traitée(s).');
        } elseif ($action === 'set_status') {
            $id = (int) ($_POST['subscriber_id'] ?? 0);
            $status = (string) ($_POST['status'] ?? '');
            if (!newsletter_set_subscriber_status($id, $status)) {
                throw new RuntimeException('Mise à jour du statut impossible.');
            }
            set_flash('success', 'Statut mis à jour.');
        } elseif ($action === 'delete_subscriber') {
            $id = (int) ($_POST['subscriber_id'] ?? 0);
            if (!newsletter_delete_subscriber($id)) {
                throw new RuntimeException('Suppression impossible.');
            }
            set_flash('success', 'Abonné supprimé.');
        } elseif ($action === 'create_campaign') {
            $title = (string) ($_POST['title'] ?? '');
            $subject = (string) ($_POST['subject'] ?? '');
            $content = (string) ($_POST['content'] ?? '');
            if (trim($title) === '' || trim($subject) === '' || trim($content) === '') {
                throw new RuntimeException('Titre, sujet et contenu sont requis.');
            }
            newsletter_create_campaign($title, $subject, $content, (int) ($user['id'] ?? 0));
            set_flash('success', 'Campagne créée.');
        } elseif ($action === 'send_campaign') {
            $campaignId = (int) ($_POST['campaign_id'] ?? 0);
            $stats = newsletter_send_campaign($campaignId);
            set_flash('success', sprintf('Campagne envoyée: %d succès / %d échecs (%d total).', (int) $stats['sent'], (int) $stats['failed'], (int) $stats['total']));
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }

    redirect('admin_newsletters');
}

$subscribers = db()->query('SELECT ns.*, m.callsign, m.full_name FROM newsletter_subscribers ns LEFT JOIN members m ON m.id = ns.member_id ORDER BY ns.updated_at DESC LIMIT 500')->fetchAll();
$campaigns = db()->query('SELECT c.*, (SELECT COUNT(*) FROM newsletter_deliveries d WHERE d.campaign_id = c.id AND d.status = "sent") AS sent_count, (SELECT COUNT(*) FROM newsletter_deliveries d WHERE d.campaign_id = c.id AND d.status = "failed") AS failed_count FROM newsletter_campaigns c ORDER BY c.id DESC LIMIT 100')->fetchAll();

ob_start();
?>
<div class="grid-2">
    <section class="card">
        <h1>Newsletter — abonnés</h1>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_subscriber">
            <label>Email à ajouter
                <input type="email" name="email" required>
            </label>
            <p><button class="button">Ajouter / réactiver</button></p>
        </form>

        <h2>Import CSV</h2>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="import_csv">
            <label>Contenu CSV/Excel (copier-coller)
                <textarea name="csv_content" rows="8" placeholder="email\nfoo@example.org\nbar@example.org" required></textarea>
            </label>
            <p><button class="button secondary">Importer</button></p>
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Email</th><th>Membre</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($subscribers as $subscriber): ?>
                    <tr>
                        <td><?= e((string) $subscriber['email']) ?></td>
                        <td><?= e((string) ($subscriber['callsign'] ?? '')) ?> <?= e((string) ($subscriber['full_name'] ?? '')) ?></td>
                        <td><?= e((string) $subscriber['status']) ?></td>
                        <td>
                            <form method="post" style="display:inline-block">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="set_status">
                                <input type="hidden" name="subscriber_id" value="<?= (int) $subscriber['id'] ?>">
                                <input type="hidden" name="status" value="<?= (string) $subscriber['status'] === 'active' ? 'unsubscribed' : 'active' ?>">
                                <button class="button small secondary" type="submit"><?= (string) $subscriber['status'] === 'active' ? 'Désabonner' : 'Réactiver' ?></button>
                            </form>
                            <form method="post" style="display:inline-block" onsubmit="return confirm('Supprimer cet abonné ?');">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_subscriber">
                                <input type="hidden" name="subscriber_id" value="<?= (int) $subscriber['id'] ?>">
                                <button class="button small danger" type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Campagnes</h2>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_campaign">
            <label>Titre
                <input type="text" name="title" required>
            </label>
            <label>Sujet email
                <input type="text" name="subject" required>
            </label>
            <label>Contenu
                <textarea name="content" rows="10" required></textarea>
            </label>
            <p><button class="button">Créer la campagne</button></p>
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Titre</th><th>Statut</th><th>Résultats</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($campaigns as $campaign): ?>
                    <tr>
                        <td><?= e((string) $campaign['title']) ?><div class="help"><?= e((string) $campaign['subject']) ?></div></td>
                        <td><?= e((string) $campaign['status']) ?></td>
                        <td><?= (int) $campaign['sent_count'] ?> envoyés / <?= (int) $campaign['failed_count'] ?> échecs</td>
                        <td>
                            <?php if ((string) $campaign['status'] !== 'sent'): ?>
                                <form method="post">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="send_campaign">
                                    <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                                    <button class="button small">Envoyer</button>
                                </form>
                            <?php else: ?>
                                <span class="help">Déjà envoyée</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['layout']);
