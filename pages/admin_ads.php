<?php
declare(strict_types=1);

require_permission('ads.moderate');
$user = require_login();
$locale = current_locale();
$i18n = [
    'fr' => ['invalid_status' => 'Statut invalide.', 'required_code_name' => 'Code et nom obligatoires.', 'ad_updated' => 'Publicité mise à jour.', 'placement_added' => 'Placement ajouté.', 'layout_title' => 'Administration publicités', 'moderation_queue' => 'File de modération publicitaire', 'no_pending' => 'Aucune publicité à modérer.', 'tracked_click' => 'Voir le clic tracké', 'tracked_click_aria' => 'Ouvrir le lien de clic tracké', 'campaigns' => 'Campagnes', 'impressions_total' => 'Affichages cumulés', 'ctr_global' => 'CTR global', 'latest_campaigns' => 'Dernières campagnes', 'no_campaigns' => 'Aucune campagne récente.', 'placements_title' => 'Placements publicitaires', 'col_title' => 'Titre', 'col_owner' => 'Dépositaire', 'col_placement' => 'Placement', 'col_status' => 'Statut', 'col_url' => 'URL', 'col_code' => 'Code', 'col_name' => 'Nom', 'col_description' => 'Description', 'label_code' => 'Code', 'label_name' => 'Nom', 'label_description' => 'Description', 'label_order' => 'Ordre', 'label_decision' => 'Décision', 'label_note' => 'Note de modération', 'save_decision' => 'Enregistrer la décision', 'status_active' => 'Activer', 'status_pending' => 'Laisser en attente', 'status_paused' => 'Mettre en pause', 'status_expired' => 'Expirer', 'status_rejected' => 'Refuser', 'placeholder_code' => 'club_sidebar', 'placeholder_name' => 'Colonne latérale club', 'add_placement' => 'Ajouter un placement', 'unknown_owner' => '—', 'meta_desc' => 'Gestion et modération des campagnes publicitaires.'],
    'en' => ['invalid_status' => 'Invalid status.', 'required_code_name' => 'Code and name are required.', 'ad_updated' => 'Advertisement updated.', 'placement_added' => 'Placement added.', 'layout_title' => 'Ads administration', 'moderation_queue' => 'Ad moderation queue', 'no_pending' => 'No ads to moderate.', 'tracked_click' => 'View tracked click', 'tracked_click_aria' => 'Open tracked click link', 'campaigns' => 'Campaigns', 'impressions_total' => 'Total impressions', 'ctr_global' => 'Global CTR', 'latest_campaigns' => 'Latest campaigns', 'no_campaigns' => 'No recent campaigns.', 'placements_title' => 'Ad placements', 'col_title' => 'Title', 'col_owner' => 'Owner', 'col_placement' => 'Placement', 'col_status' => 'Status', 'col_url' => 'URL', 'col_code' => 'Code', 'col_name' => 'Name', 'col_description' => 'Description', 'label_code' => 'Code', 'label_name' => 'Name', 'label_description' => 'Description', 'label_order' => 'Order', 'label_decision' => 'Decision', 'label_note' => 'Moderation note', 'save_decision' => 'Save decision', 'status_active' => 'Activate', 'status_pending' => 'Keep pending', 'status_paused' => 'Pause', 'status_expired' => 'Expire', 'status_rejected' => 'Reject', 'placeholder_code' => 'club_sidebar', 'placeholder_name' => 'Club sidebar', 'add_placement' => 'Add placement', 'unknown_owner' => '—', 'meta_desc' => 'Manage and moderate advertising campaigns.'],
    'de' => ['invalid_status' => 'Ungültiger Status.', 'required_code_name' => 'Code und Name sind erforderlich.', 'ad_updated' => 'Anzeige aktualisiert.', 'placement_added' => 'Platzierung hinzugefügt.', 'layout_title' => 'Werbeverwaltung', 'moderation_queue' => 'Werbe-Moderationswarteschlange', 'no_pending' => 'Keine Anzeigen zu moderieren.', 'tracked_click' => 'Getrackten Klick anzeigen', 'tracked_click_aria' => 'Getrackten Klick-Link öffnen', 'campaigns' => 'Kampagnen', 'impressions_total' => 'Kumulierte Impressionen', 'ctr_global' => 'Gesamt-CTR', 'latest_campaigns' => 'Neueste Kampagnen', 'no_campaigns' => 'Keine aktuellen Kampagnen.', 'placements_title' => 'Anzeigenplätze', 'col_title' => 'Titel', 'col_owner' => 'Anbieter', 'col_placement' => 'Platzierung', 'col_status' => 'Status', 'col_url' => 'URL', 'col_code' => 'Code', 'col_name' => 'Name', 'col_description' => 'Beschreibung', 'label_code' => 'Code', 'label_name' => 'Name', 'label_description' => 'Beschreibung', 'label_order' => 'Reihenfolge', 'label_decision' => 'Entscheidung', 'label_note' => 'Moderationsnotiz', 'save_decision' => 'Entscheidung speichern', 'status_active' => 'Aktivieren', 'status_pending' => 'Ausstehend lassen', 'status_paused' => 'Pausieren', 'status_expired' => 'Ablaufen lassen', 'status_rejected' => 'Ablehnen', 'placeholder_code' => 'club_sidebar', 'placeholder_name' => 'Club-Seitenleiste', 'add_placement' => 'Platzierung hinzufügen', 'unknown_owner' => '—', 'meta_desc' => 'Werbekampagnen verwalten und moderieren.'],
    'nl' => ['invalid_status' => 'Ongeldige status.', 'required_code_name' => 'Code en naam zijn verplicht.', 'ad_updated' => 'Advertentie bijgewerkt.', 'placement_added' => 'Plaatsing toegevoegd.', 'layout_title' => 'Advertentiebeheer', 'moderation_queue' => 'Moderatiequeue advertenties', 'no_pending' => 'Geen advertenties om te modereren.', 'tracked_click' => 'Getrackte klik bekijken', 'tracked_click_aria' => 'Getrackte kliklink openen', 'campaigns' => 'Campagnes', 'impressions_total' => 'Totaal vertoningen', 'ctr_global' => 'Totale CTR', 'latest_campaigns' => 'Laatste campagnes', 'no_campaigns' => 'Geen recente campagnes.', 'placements_title' => 'Advertentieplaatsingen', 'col_title' => 'Titel', 'col_owner' => 'Plaatser', 'col_placement' => 'Plaatsing', 'col_status' => 'Status', 'col_url' => 'URL', 'col_code' => 'Code', 'col_name' => 'Naam', 'col_description' => 'Beschrijving', 'label_code' => 'Code', 'label_name' => 'Naam', 'label_description' => 'Beschrijving', 'label_order' => 'Volgorde', 'label_decision' => 'Beslissing', 'label_note' => 'Moderatie-opmerking', 'save_decision' => 'Beslissing opslaan', 'status_active' => 'Activeren', 'status_pending' => 'In afwachting laten', 'status_paused' => 'Pauzeren', 'status_expired' => 'Laten verlopen', 'status_rejected' => 'Afwijzen', 'placeholder_code' => 'club_sidebar', 'placeholder_name' => 'Club-zijkolom', 'add_placement' => 'Plaatsing toevoegen', 'unknown_owner' => '—', 'meta_desc' => 'Beheer en moderatie van advertentiecampagnes.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

set_page_meta([
    'title' => (string) $t['layout_title'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'moderate_ad') {
            $adId = (int) ($_POST['ad_id'] ?? 0);
            $status = (string) ($_POST['status'] ?? 'pending');
            $note = trim((string) ($_POST['moderation_note'] ?? ''));
            if (!in_array($status, ['pending', 'active', 'paused', 'expired', 'rejected'], true)) {
                throw new RuntimeException((string) $t['invalid_status']);
            }
            db()->prepare('UPDATE ads SET status = ?, moderation_note = ? WHERE id = ?')->execute([$status, $note, $adId]);
            set_flash('success', (string) $t['ad_updated']);
            redirect('admin_ads', ['refresh' => '1']);
        }
        if ($action === 'add_placement') {
            $code = slugify((string) ($_POST['code'] ?? ''));
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $sortOrder = (int) ($_POST['sort_order'] ?? 100);
            if ($code === '' || $name === '') {
                throw new RuntimeException((string) $t['required_code_name']);
            }
            db()->prepare('INSERT INTO ad_placements (code, name, description, sort_order, is_active) VALUES (?, ?, ?, ?, 1)')->execute([$code, $name, $description, $sortOrder]);
            set_flash('success', (string) $t['placement_added']);
            redirect('admin_ads', ['refresh' => '1']);
        }
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('admin_ads', ['refresh' => '1']);
    }
}

$forceFresh = ((string) ($_GET['refresh'] ?? '') === '1');
$loadPendingAds = static function (): array {
    return db()->query('SELECT a.*, ap.name AS placement_name, ap.code AS placement_code, m.callsign AS owner_callsign
        FROM ads a
        INNER JOIN ad_placements ap ON ap.id = a.placement_id
        LEFT JOIN members m ON m.id = a.owner_member_id
        WHERE a.status IN ("pending", "rejected", "paused")
        ORDER BY a.updated_at DESC, a.id DESC')->fetchAll() ?: [];
};
$loadRecentAds = static function (): array {
    return db()->query('SELECT a.*, ap.name AS placement_name, ap.code AS placement_code, m.callsign AS owner_callsign
        FROM ads a
        INNER JOIN ad_placements ap ON ap.id = a.placement_id
        LEFT JOIN members m ON m.id = a.owner_member_id
        ORDER BY a.updated_at DESC, a.id DESC LIMIT 20')->fetchAll() ?: [];
};
if ($forceFresh) {
    $pendingAds = $loadPendingAds();
    $activeAds = $loadRecentAds();
    $placements = available_ad_placements();
} else {
    $pendingAds = cache_remember('admin_ads_pending_v1', 30, $loadPendingAds);
    $activeAds = cache_remember('admin_ads_recent_v1', 30, $loadRecentAds);
    $placements = cache_remember('admin_ads_placements_v1', 120, static function (): array {
        return available_ad_placements();
    });
}
$totals = $forceFresh
    ? (db()->query('SELECT COUNT(*) AS ads_count FROM ads')->fetch() ?: ['ads_count' => 0])
    : cache_remember('admin_ads_totals_v1', 30, static function (): array {
        return db()->query('SELECT COUNT(*) AS ads_count FROM ads')->fetch() ?: ['ads_count' => 0];
    });
$totalImpressions = $forceFresh
    ? (int) (db()->query('SELECT COUNT(*) FROM ad_events WHERE event_type = "impression"')->fetchColumn() ?: 0)
    : (int) cache_remember('admin_ads_impressions_total_v1', 30, static function (): int {
        return (int) (db()->query('SELECT COUNT(*) FROM ad_events WHERE event_type = "impression"')->fetchColumn() ?: 0);
    });
$totalClicks = $forceFresh
    ? (int) (db()->query('SELECT COUNT(*) FROM ad_events WHERE event_type = "click"')->fetchColumn() ?: 0)
    : (int) cache_remember('admin_ads_clicks_total_v1', 30, static function (): int {
        return (int) (db()->query('SELECT COUNT(*) FROM ad_events WHERE event_type = "click"')->fetchColumn() ?: 0);
    });
$ctr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.0;

ob_start();
?>
<div class="grid-3 stats-grid">
  <div class="stat-card"><strong><?= (int) $totals['ads_count'] ?></strong><span><?= e((string) $t['campaigns']) ?></span></div>
  <div class="stat-card"><strong><?= $totalImpressions ?></strong><span><?= e((string) $t['impressions_total']) ?></span></div>
  <div class="stat-card"><strong><?= e((string) $ctr) ?>%</strong><span><?= e((string) $t['ctr_global']) ?></span></div>
</div>

<div class="grid-2">
<section class="card">
  <h1><?= e((string) $t['moderation_queue']) ?></h1>
  <?php foreach ($pendingAds as $ad): ?>
    <article class="card inner-card">
      <div class="row-between">
        <div>
          <h3><?= e((string) $ad['title']) ?></h3>
          <p class="help"><?= e((string) $ad['owner_callsign']) ?> — <?= e((string) $ad['placement_name']) ?> — <?= e(ad_format_label((string) $ad['format_code'])) ?></p>
        </div>
        <span class="badge muted"><?= e(ad_status_label((string) ad_runtime_status($ad))) ?></span>
      </div>
      <p><?= e((string) $ad['description']) ?></p>
      <form method="post" class="stack">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="moderate_ad">
        <input type="hidden" name="ad_id" value="<?= (int) $ad['id'] ?>">
        <label><?= e((string) $t['label_decision']) ?>
          <select name="status">
            <?php $currentStatus = (string) ad_runtime_status($ad); ?>
            <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>><?= e((string) $t['status_active']) ?></option>
            <option value="pending" <?= $currentStatus === 'pending' ? 'selected' : '' ?>><?= e((string) $t['status_pending']) ?></option>
            <option value="paused" <?= $currentStatus === 'paused' ? 'selected' : '' ?>><?= e((string) $t['status_paused']) ?></option>
            <option value="expired" <?= $currentStatus === 'expired' ? 'selected' : '' ?>><?= e((string) $t['status_expired']) ?></option>
            <option value="rejected" <?= $currentStatus === 'rejected' ? 'selected' : '' ?>><?= e((string) $t['status_rejected']) ?></option>
          </select>
        </label>
        <label><?= e((string) $t['label_note']) ?>
          <textarea name="moderation_note" rows="3"><?= e((string) ($ad['moderation_note'] ?? '')) ?></textarea>
        </label>
        <p><button class="button"><?= e((string) $t['save_decision']) ?></button></p>
      </form>
    </article>
  <?php endforeach; ?>
  <?php if ($pendingAds === []): ?><p><?= e((string) $t['no_pending']) ?></p><?php endif; ?>
</section>

<section class="card">
  <h2><?= e((string) $t['placements_title']) ?></h2>
  <form method="post" class="stack">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add_placement">
    <label><?= e((string) $t['label_code']) ?>
      <input type="text" name="code" placeholder="<?= e((string) $t['placeholder_code']) ?>">
    </label>
    <label><?= e((string) $t['label_name']) ?>
      <input type="text" name="name" placeholder="<?= e((string) $t['placeholder_name']) ?>">
    </label>
    <label><?= e((string) $t['label_description']) ?>
      <textarea name="description" rows="3"></textarea>
    </label>
    <label><?= e((string) $t['label_order']) ?>
      <input type="text" name="sort_order" value="100">
    </label>
    <p><button class="button"><?= e((string) $t['add_placement']) ?></button></p>
  </form>
  <div class="table-wrap">
    <table>
      <thead><tr><th scope="col"><?= e((string) $t['col_code']) ?></th><th scope="col"><?= e((string) $t['col_name']) ?></th><th scope="col"><?= e((string) $t['col_description']) ?></th></tr></thead>
      <tbody>
      <?php foreach ($placements as $placement): ?>
        <tr><td><code><?= e((string) $placement['code']) ?></code></td><td><?= e((string) $placement['name']) ?></td><td><?= e((string) $placement['description']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
</div>

<section class="card">
  <h2><?= e((string) $t['latest_campaigns']) ?></h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th scope="col"><?= e((string) $t['col_title']) ?></th><th scope="col"><?= e((string) $t['col_owner']) ?></th><th scope="col"><?= e((string) $t['col_placement']) ?></th><th scope="col"><?= e((string) $t['col_status']) ?></th><th scope="col"><?= e((string) $t['col_url']) ?></th></tr></thead>
      <tbody>
      <?php foreach ($activeAds as $ad): ?>
        <tr>
          <td><?= e((string) $ad['title']) ?></td>
          <td><?= e((string) ($ad['owner_callsign'] ?: ($t['unknown_owner'] ?? '—'))) ?></td>
          <td><?= e((string) $ad['placement_name']) ?></td>
          <td><?= e(ad_status_label((string) ad_runtime_status($ad))) ?></td>
          <td><a href="<?= e(base_url('index.php?route=ad_click&id=' . (int) $ad['id'])) ?>" target="_blank" rel="noopener" aria-label="<?= e((string) ($t['tracked_click_aria'] ?? $t['tracked_click'])) ?>"><?= e((string) $t['tracked_click']) ?></a></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($activeAds === []): ?>
        <tr><td colspan="5"><?= e((string) $t['no_campaigns']) ?></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['layout_title']);
