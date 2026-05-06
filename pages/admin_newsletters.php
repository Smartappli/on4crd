<?php
declare(strict_types=1);

require_permission('admin.access');
$user = require_login();
newsletter_ensure_tables();
$locale = current_locale();
$i18n = [
    'fr' => ['meta_title' => 'Administration newsletter', 'meta_desc' => 'Gestion des abonnés et campagnes newsletter.', 'layout' => 'Administration newsletter', 'err_invalid_email' => 'Adresse email invalide.', 'ok_subscriber_saved' => 'Abonné ajouté ou réactivé.', 'err_csv_required' => 'Collez le contenu CSV avant import.', 'ok_csv_import' => 'adresse(s) traitée(s).', 'err_status_update' => 'Mise à jour du statut impossible.', 'ok_status_updated' => 'Statut mis à jour.', 'err_delete_subscriber' => 'Suppression impossible.', 'ok_subscriber_deleted' => 'Abonné supprimé.', 'err_campaign_required' => 'Titre, sujet et contenu sont requis.', 'ok_campaign_created' => 'Campagne créée.', 'ok_campaign_sent' => 'Campagne envoyée: %d succès / %d échecs (%d total).', 'title_subscribers' => 'Newsletter — abonnés', 'email_to_add' => 'Email à ajouter', 'add_or_reactivate' => 'Ajouter / réactiver', 'csv_import' => 'Import CSV', 'csv_content' => 'Contenu CSV/Excel (copier-coller)', 'import' => 'Importer', 'member' => 'Membre', 'status' => 'Statut', 'actions' => 'Actions', 'unsubscribe' => 'Désabonner', 'reactivate' => 'Réactiver', 'confirm_delete_subscriber' => 'Supprimer cet abonné ?', 'delete' => 'Supprimer', 'campaigns' => 'Campagnes', 'title' => 'Titre', 'email_subject' => 'Sujet email', 'content' => 'Contenu', 'create_campaign' => 'Créer la campagne', 'results' => 'Résultats', 'action' => 'Action', 'sent_results' => '%d envoyés / %d échecs', 'send' => 'Envoyer', 'already_sent' => 'Déjà envoyée', 'email' => 'Email', 'csv_ph' => 'email\nfoo@example.org\nbar@example.org', 'no_subscribers' => 'Aucun abonné.', 'no_campaigns' => 'Aucune campagne.'],
    'en' => ['meta_title' => 'Newsletter administration', 'meta_desc' => 'Manage subscribers and newsletter campaigns.', 'layout' => 'Newsletter administration', 'err_invalid_email' => 'Invalid email address.', 'ok_subscriber_saved' => 'Subscriber added or reactivated.', 'err_csv_required' => 'Paste CSV content before importing.', 'ok_csv_import' => 'address(es) processed.', 'err_status_update' => 'Unable to update status.', 'ok_status_updated' => 'Status updated.', 'err_delete_subscriber' => 'Unable to delete subscriber.', 'ok_subscriber_deleted' => 'Subscriber deleted.', 'err_campaign_required' => 'Title, subject and content are required.', 'ok_campaign_created' => 'Campaign created.', 'ok_campaign_sent' => 'Campaign sent: %d success / %d failed (%d total).', 'title_subscribers' => 'Newsletter — subscribers', 'email_to_add' => 'Email to add', 'add_or_reactivate' => 'Add / reactivate', 'csv_import' => 'CSV import', 'csv_content' => 'CSV/Excel content (copy-paste)', 'import' => 'Import', 'member' => 'Member', 'status' => 'Status', 'actions' => 'Actions', 'unsubscribe' => 'Unsubscribe', 'reactivate' => 'Reactivate', 'confirm_delete_subscriber' => 'Delete this subscriber?', 'delete' => 'Delete', 'campaigns' => 'Campaigns', 'title' => 'Title', 'email_subject' => 'Email subject', 'content' => 'Content', 'create_campaign' => 'Create campaign', 'results' => 'Results', 'action' => 'Action', 'sent_results' => '%d sent / %d failed', 'send' => 'Send', 'already_sent' => 'Already sent', 'email' => 'Email', 'csv_ph' => 'email\nfoo@example.org\nbar@example.org', 'no_subscribers' => 'No subscribers.', 'no_campaigns' => 'No campaigns.'],
    'de' => ['meta_title' => 'Newsletter-Verwaltung', 'meta_desc' => 'Verwalten Sie Abonnenten und Newsletter-Kampagnen.', 'layout' => 'Newsletter-Verwaltung', 'err_invalid_email' => 'Ungültige E-Mail-Adresse.', 'ok_subscriber_saved' => 'Abonnent hinzugefügt oder reaktiviert.', 'err_csv_required' => 'CSV-Inhalt vor dem Import einfügen.', 'ok_csv_import' => 'Adresse(n) verarbeitet.', 'err_status_update' => 'Status konnte nicht aktualisiert werden.', 'ok_status_updated' => 'Status aktualisiert.', 'err_delete_subscriber' => 'Löschen nicht möglich.', 'ok_subscriber_deleted' => 'Abonnent gelöscht.', 'err_campaign_required' => 'Titel, Betreff und Inhalt sind erforderlich.', 'ok_campaign_created' => 'Kampagne erstellt.', 'ok_campaign_sent' => 'Kampagne gesendet: %d Erfolg / %d Fehler (%d gesamt).', 'title_subscribers' => 'Newsletter — Abonnenten', 'email_to_add' => 'E-Mail hinzufügen', 'add_or_reactivate' => 'Hinzufügen / reaktivieren', 'csv_import' => 'CSV-Import', 'csv_content' => 'CSV/Excel-Inhalt (Kopieren/Einfügen)', 'import' => 'Importieren', 'member' => 'Mitglied', 'status' => 'Status', 'actions' => 'Aktionen', 'unsubscribe' => 'Abmelden', 'reactivate' => 'Reaktivieren', 'confirm_delete_subscriber' => 'Diesen Abonnenten löschen?', 'delete' => 'Löschen', 'campaigns' => 'Kampagnen', 'title' => 'Titel', 'email_subject' => 'E-Mail-Betreff', 'content' => 'Inhalt', 'create_campaign' => 'Kampagne erstellen', 'results' => 'Ergebnisse', 'action' => 'Aktion', 'sent_results' => '%d gesendet / %d fehlgeschlagen', 'send' => 'Senden', 'already_sent' => 'Bereits gesendet', 'email' => 'E-Mail', 'csv_ph' => 'email\nfoo@example.org\nbar@example.org', 'no_subscribers' => 'Keine Abonnenten.', 'no_campaigns' => 'Keine Kampagnen.'],
    'nl' => ['meta_title' => 'Nieuwsbriefbeheer', 'meta_desc' => 'Beheer abonnees en nieuwsbriefcampagnes.', 'layout' => 'Nieuwsbriefbeheer', 'err_invalid_email' => 'Ongeldig e-mailadres.', 'ok_subscriber_saved' => 'Abonnee toegevoegd of opnieuw geactiveerd.', 'err_csv_required' => 'Plak CSV-inhoud vóór import.', 'ok_csv_import' => 'adres(sen) verwerkt.', 'err_status_update' => 'Status bijwerken mislukt.', 'ok_status_updated' => 'Status bijgewerkt.', 'err_delete_subscriber' => 'Verwijderen mislukt.', 'ok_subscriber_deleted' => 'Abonnee verwijderd.', 'err_campaign_required' => 'Titel, onderwerp en inhoud zijn verplicht.', 'ok_campaign_created' => 'Campagne aangemaakt.', 'ok_campaign_sent' => 'Campagne verzonden: %d succes / %d mislukt (%d totaal).', 'title_subscribers' => 'Nieuwsbrief — abonnees', 'email_to_add' => 'E-mail toevoegen', 'add_or_reactivate' => 'Toevoegen / heractiveren', 'csv_import' => 'CSV-import', 'csv_content' => 'CSV/Excel-inhoud (kopiëren-plakken)', 'import' => 'Importeren', 'member' => 'Lid', 'status' => 'Status', 'actions' => 'Acties', 'unsubscribe' => 'Uitschrijven', 'reactivate' => 'Heractiveren', 'confirm_delete_subscriber' => 'Deze abonnee verwijderen?', 'delete' => 'Verwijderen', 'campaigns' => 'Campagnes', 'title' => 'Titel', 'email_subject' => 'E-mailonderwerp', 'content' => 'Inhoud', 'create_campaign' => 'Campagne maken', 'results' => 'Resultaten', 'action' => 'Actie', 'sent_results' => '%d verzonden / %d mislukt', 'send' => 'Verzenden', 'already_sent' => 'Al verzonden', 'email' => 'E-mail', 'csv_ph' => 'email\nfoo@example.org\nbar@example.org', 'no_subscribers' => 'Geen abonnees.', 'no_campaigns' => 'Geen campagnes.'],
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
                throw new RuntimeException((string) $t['err_invalid_email']);
            }
            set_flash('success', (string) $t['ok_subscriber_saved']);
        } elseif ($action === 'import_csv') {
            $content = trim((string) ($_POST['csv_content'] ?? ''));
            if ($content === '') {
                throw new RuntimeException((string) $t['err_csv_required']);
            }
            $count = newsletter_import_csv($content);
            set_flash('success', $count . ' ' . (string) $t['ok_csv_import']);
        } elseif ($action === 'set_status') {
            $id = (int) ($_POST['subscriber_id'] ?? 0);
            $status = (string) ($_POST['status'] ?? '');
            if (!newsletter_set_subscriber_status($id, $status)) {
                throw new RuntimeException((string) $t['err_status_update']);
            }
            set_flash('success', (string) $t['ok_status_updated']);
        } elseif ($action === 'delete_subscriber') {
            $id = (int) ($_POST['subscriber_id'] ?? 0);
            if (!newsletter_delete_subscriber($id)) {
                throw new RuntimeException((string) $t['err_delete_subscriber']);
            }
            set_flash('success', (string) $t['ok_subscriber_deleted']);
        } elseif ($action === 'create_campaign') {
            $title = (string) ($_POST['title'] ?? '');
            $subject = (string) ($_POST['subject'] ?? '');
            $content = (string) ($_POST['content'] ?? '');
            if (trim($title) === '' || trim($subject) === '' || trim($content) === '') {
                throw new RuntimeException((string) $t['err_campaign_required']);
            }
            newsletter_create_campaign($title, $subject, $content, (int) ($user['id'] ?? 0));
            set_flash('success', (string) $t['ok_campaign_created']);
        } elseif ($action === 'send_campaign') {
            $campaignId = (int) ($_POST['campaign_id'] ?? 0);
            $stats = newsletter_send_campaign($campaignId);
            set_flash('success', sprintf((string) $t['ok_campaign_sent'], (int) $stats['sent'], (int) $stats['failed'], (int) $stats['total']));
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
        <h1><?= e((string) $t['title_subscribers']) ?></h1>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_subscriber">
            <label><?= e((string) $t['email_to_add']) ?>
                <input type="email" name="email" required>
            </label>
            <p><button class="button"><?= e((string) $t['add_or_reactivate']) ?></button></p>
        </form>

        <h2><?= e((string) $t['csv_import']) ?></h2>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="import_csv">
            <label><?= e((string) $t['csv_content']) ?>
                <textarea name="csv_content" rows="8" placeholder="<?= e((string) $t['csv_ph']) ?>" required></textarea>
            </label>
            <p><button class="button secondary"><?= e((string) $t['import']) ?></button></p>
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th><?= e((string) $t['email']) ?></th><th><?= e((string) $t['member']) ?></th><th><?= e((string) $t['status']) ?></th><th><?= e((string) $t['actions']) ?></th></tr></thead>
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
                                <button class="button small secondary" type="submit"><?= (string) $subscriber['status'] === 'active' ? e((string) $t['unsubscribe']) : e((string) $t['reactivate']) ?></button>
                            </form>
                            <form method="post" style="display:inline-block" onsubmit="return confirm('<?= e((string) $t['confirm_delete_subscriber']) ?>');">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_subscriber">
                                <input type="hidden" name="subscriber_id" value="<?= (int) $subscriber['id'] ?>">
                                <button class="button small danger" type="submit"><?= e((string) $t['delete']) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($subscribers === []): ?><tr><td colspan="4"><?= e((string) $t['no_subscribers']) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2><?= e((string) $t['campaigns']) ?></h2>
        <form method="post" class="stack">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_campaign">
            <label><?= e((string) $t['title']) ?>
                <input type="text" name="title" required>
            </label>
            <label><?= e((string) $t['email_subject']) ?>
                <input type="text" name="subject" required>
            </label>
            <label><?= e((string) $t['content']) ?>
                <textarea name="content" rows="10" required></textarea>
            </label>
            <p><button class="button"><?= e((string) $t['create_campaign']) ?></button></p>
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th><?= e((string) $t['title']) ?></th><th><?= e((string) $t['status']) ?></th><th><?= e((string) $t['results']) ?></th><th><?= e((string) $t['action']) ?></th></tr></thead>
                <tbody>
                <?php foreach ($campaigns as $campaign): ?>
                    <tr>
                        <td><?= e((string) $campaign['title']) ?><div class="help"><?= e((string) $campaign['subject']) ?></div></td>
                        <td><?= e((string) $campaign['status']) ?></td>
                        <td><?= sprintf((string) $t['sent_results'], (int) $campaign['sent_count'], (int) $campaign['failed_count']) ?></td>
                        <td>
                            <?php if ((string) $campaign['status'] !== 'sent'): ?>
                                <form method="post">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="send_campaign">
                                    <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                                    <button class="button small"><?= e((string) $t['send']) ?></button>
                                </form>
                            <?php else: ?>
                                <span class="help"><?= e((string) $t['already_sent']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($campaigns === []): ?><tr><td colspan="4"><?= e((string) $t['no_campaigns']) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['layout']);
