<?php
declare(strict_types=1);

$user = require_login();
if (!module_enabled('advertising')) {
    echo render_layout('<div class="card"><p>Module publicitaire désactivé.</p></div>', 'Publicités');
    return;
}

$canModerate = has_permission('ads.moderate') || has_permission('ads.manage_all');
$ads = member_ads((int) $user['id'], !$canModerate);
$placements = available_ad_placements();
$placementOptions = ad_placements_for_member((int) $user['id']);
$statsAd = null;
$statsDaily = [];

if (!empty($_GET['stats'])) {
    $requestedAd = ad_fetch_by_id((int) $_GET['stats']);
    if ($requestedAd !== null && ($canModerate || (int) $requestedAd['owner_member_id'] === (int) $user['id'])) {
        $statsAd = $requestedAd;
        $statsDaily = ad_daily_stats((int) $requestedAd['id']);
    }
}

$editing = null;
if (!empty($_GET['edit'])) {
    $requestedAd = ad_fetch_by_id((int) $_GET['edit']);
    if ($requestedAd !== null && ($canModerate || (int) $requestedAd['owner_member_id'] === (int) $user['id'])) {
        $editing = $requestedAd;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? 'save_ad');

        if ($action === 'save_ad') {
            $adId = (int) ($_POST['ad_id'] ?? 0);
            $existing = $adId > 0 ? ad_fetch_by_id($adId) : null;
            if ($adId > 0 && ($existing === null || (!$canModerate && (int) $existing['owner_member_id'] !== (int) $user['id']))) {
                throw new RuntimeException('Publicité introuvable ou inaccessible.');
            }

            $placementId = (int) ($_POST['placement_id'] ?? 0);
            if ($placementId <= 0) {
                throw new RuntimeException('Placement obligatoire.');
            }

            $targetUrl = normalize_http_url((string) ($_POST['target_url'] ?? ''));
            $imagePath = handle_ad_image_upload($_FILES['image'] ?? null, (string) $user['callsign'], (string) ($existing['image_path'] ?? ''))
                ?? ($existing['image_path'] ?? null);

            $startsAtRaw = trim((string) ($_POST['start_at'] ?? ''));
            $startsAt = $startsAtRaw !== '' ? str_replace('T', ' ', $startsAtRaw) . ':00' : date('Y-m-d H:i:s');
            $durationDays = max(1, (int) ($_POST['duration_days'] ?? 30));
            $endsAt = date('Y-m-d H:i:s', strtotime($startsAt . ' +' . $durationDays . ' days'));
            $maxImpressions = trim((string) ($_POST['max_impressions'] ?? ''));
            $maxImpressionsValue = $maxImpressions !== '' ? max(1, (int) $maxImpressions) : null;
            $requestedStatus = (string) ($_POST['status'] ?? 'draft');
            $status = $canModerate ? $requestedStatus : ($adId > 0 ? (string) ($existing['status'] ?? 'pending') : 'pending');

            $payload = [
                'owner_member_id' => (int) $user['id'],
                'placement_id' => $placementId,
                'format_code' => (string) ($_POST['format_code'] ?? 'leaderboard'),
                'title' => trim((string) ($_POST['title'] ?? '')),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'image_path' => $imagePath,
                'target_url' => $targetUrl,
                'start_at' => $startsAt,
                'duration_days' => $durationDays,
                'end_at' => $endsAt,
                'max_impressions' => $maxImpressionsValue,
                'weight' => max(1, (int) ($_POST['weight'] ?? 100)),
                'status' => $status,
            ];

            if ($payload['title'] === '') {
                throw new RuntimeException('Titre obligatoire.');
            }
            if (!isset(ad_format_catalog()[$payload['format_code']])) {
                throw new RuntimeException('Format publicitaire invalide.');
            }

            if ($adId > 0) {
                $stmt = db()->prepare('UPDATE ads SET placement_id = ?, format_code = ?, title = ?, description = ?, image_path = ?, target_url = ?, start_at = ?, duration_days = ?, end_at = ?, max_impressions = ?, weight = ?, status = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([
                    $payload['placement_id'],
                    $payload['format_code'],
                    $payload['title'],
                    $payload['description'],
                    $payload['image_path'],
                    $payload['target_url'],
                    $payload['start_at'],
                    $payload['duration_days'],
                    $payload['end_at'],
                    $payload['max_impressions'],
                    $payload['weight'],
                    $payload['status'],
                    $adId,
                ]);
                set_flash('success', 'Publicité mise à jour.');
            } else {
                $stmt = db()->prepare('INSERT INTO ads (owner_member_id, placement_id, format_code, title, description, image_path, target_url, start_at, duration_days, end_at, max_impressions, weight, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $payload['owner_member_id'],
                    $payload['placement_id'],
                    $payload['format_code'],
                    $payload['title'],
                    $payload['description'],
                    $payload['image_path'],
                    $payload['target_url'],
                    $payload['start_at'],
                    $payload['duration_days'],
                    $payload['end_at'],
                    $payload['max_impressions'],
                    $payload['weight'],
                    $payload['status'],
                ]);
                set_flash('success', 'Publicité créée.');
            }
        }

        if ($action === 'change_status') {
            $adId = (int) ($_POST['ad_id'] ?? 0);
            $newStatus = (string) ($_POST['status'] ?? 'paused');
            $existing = ad_fetch_by_id($adId);
            if ($existing === null || (!$canModerate && (int) $existing['owner_member_id'] !== (int) $user['id'])) {
                throw new RuntimeException('Publicité introuvable.');
            }
            if (!in_array($newStatus, ['draft', 'pending', 'active', 'paused', 'expired'], true)) {
                throw new RuntimeException('Statut invalide.');
            }
            db()->prepare('UPDATE ads SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$newStatus, $adId]);
            set_flash('success', 'Statut publicitaire mis à jour.');
        }

        redirect('ads');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('ads');
    }
}

ob_start();
?>
<div class="grid-2">
  <section class="card">
    <h1><?= $editing ? 'Modifier une publicité' : 'Déposer une publicité' ?></h1>
    <p class="help">Le module sert à gérer les encarts sponsorisés du club. Les campagnes sont visibles seulement sur les emplacements dédiés et peuvent être modérées par l’administration.</p>
    <form method="post" enctype="multipart/form-data" class="stack">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_ad">
      <input type="hidden" name="ad_id" value="<?= (int) ($editing['id'] ?? 0) ?>">
      <label>Titre
        <input type="text" name="title" value="<?= e((string) ($editing['title'] ?? '')) ?>" required>
      </label>
      <label>Description
        <textarea name="description" rows="4"><?= e((string) ($editing['description'] ?? '')) ?></textarea>
      </label>
      <div class="form-grid">
        <label>Placement
          <select name="placement_id">
            <?php foreach ($placementOptions as $placement): ?>
              <option value="<?= (int) $placement['id'] ?>" <?= ((int) ($editing['placement_id'] ?? 0) === (int) $placement['id']) ? 'selected' : '' ?>><?= e((string) $placement['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Format
          <select name="format_code">
            <?php foreach (ad_format_catalog() as $formatCode => $format): ?>
              <option value="<?= e($formatCode) ?>" <?= (($editing['format_code'] ?? 'leaderboard') === $formatCode) ? 'selected' : '' ?>><?= e((string) $format['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <label>URL cible
        <input type="url" name="target_url" value="<?= e((string) ($editing['target_url'] ?? 'https://')) ?>" inputmode="url" placeholder="https://exemple.tld">
      </label>
      <div class="form-grid">
        <label>Début
          <input type="datetime-local" name="start_at" value="<?= e(isset($editing['start_at']) && $editing['start_at'] ? date('Y-m-d\TH:i', strtotime((string) $editing['start_at'])) : date('Y-m-d\TH:i')) ?>">
        </label>
        <label>Durée (jours)
          <input type="text" name="duration_days" value="<?= e((string) ($editing['duration_days'] ?? '30')) ?>">
        </label>
      </div>
      <div class="form-grid">
        <label>Nombre maximal d’affichages
          <input type="text" name="max_impressions" value="<?= e((string) ($editing['max_impressions'] ?? '10000')) ?>">
        </label>
        <label>Poids / priorité
          <input type="text" name="weight" value="<?= e((string) ($editing['weight'] ?? '100')) ?>">
        </label>
      </div>
      <?php if (has_permission('ads.moderate') || has_permission('ads.manage_all')): ?>
      <label>Statut
        <select name="status">
          <?php foreach (['draft','pending','active','paused','expired','rejected'] as $status): ?>
            <option value="<?= e($status) ?>" <?= (($editing['status'] ?? 'active') === $status) ? 'selected' : '' ?>><?= e(ad_status_label($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php endif; ?>
      <label>Visuel
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
      </label>
      <?php if (!empty($editing['image_path'])): ?>
        <p><img class="ad-preview-image" src="<?= e(base_url((string) $editing['image_path'])) ?>" alt=""></p>
      <?php endif; ?>
      <p class="help">La date de fin sera calculée automatiquement à l’enregistrement. Si le nombre maximal d’affichages est atteint avant la fin, la campagne expirera automatiquement.</p>
      <p><button class="button">Enregistrer</button></p>
    </form>
  </section>

  <section class="card">
    <h2>Mes publicités</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Titre</th><th>Placement</th><th>Statut</th><th>Impr.</th><th>Clics</th><th>CTR</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($ads as $ad): ?>
            <tr>
              <td>
                <strong><?= e((string) $ad['title']) ?></strong>
                <div class="help"><?= e((string) $ad['owner_callsign']) ?> — <?= e(ad_format_label((string) $ad['format_code'])) ?></div>
                <div class="help">Fin calculée : <?= e((string) ($ad['end_at'] ?: 'sans limite de date')) ?></div>
              </td>
              <td><?= e((string) $ad['placement_name']) ?></td>
              <td><span class="badge muted"><?= e(ad_status_label((string) $ad['runtime_status'])) ?></span></td>
              <td><?= (int) $ad['stats']['impressions'] ?></td>
              <td><?= (int) $ad['stats']['clicks'] ?></td>
              <td><?= e((string) $ad['stats']['ctr']) ?>%</td>
              <td>
                <div class="member-links">
                  <a href="<?= e(route_url('ads', ['edit' => (int) $ad['id']])) ?>">Éditer</a>
                  <a href="<?= e(route_url('ads', ['stats' => (int) $ad['id']])) ?>">Stats</a>
                </div>
                <form method="post" class="inline-form">
                  <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="change_status">
                  <input type="hidden" name="ad_id" value="<?= (int) $ad['id'] ?>">
                  <select name="status">
                    <option value="paused">Pause</option>
                    <option value="pending">Repasse en attente</option>
                    <option value="draft">Brouillon</option>
                  </select>
                  <button class="ghost">OK</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if ($ads === []): ?>
            <tr><td colspan="7">Aucune publicité enregistrée.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<?php if ($statsAd): ?>
<section class="card">
  <div class="row-between">
    <h2>Statistiques détaillées — <?= e((string) $statsAd['title']) ?></h2>
    <span class="badge muted"><?= e(ad_status_label((string) ad_runtime_status($statsAd))) ?></span>
  </div>
  <div class="stats-grid">
    <div class="stat-card"><strong><?= (int) $statsAd['summary']['impressions'] ?></strong><span>Affichages</span></div>
    <div class="stat-card"><strong><?= (int) $statsAd['summary']['clicks'] ?></strong><span>Clics</span></div>
    <div class="stat-card"><strong><?= e((string) $statsAd['summary']['ctr']) ?>%</strong><span>CTR</span></div>
    <div class="stat-card"><strong><?= (int) $statsAd['summary']['unique_viewers'] ?></strong><span>Visiteurs uniques approx.</span></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Placement</th><th>Affichages</th><th>Clics</th><th>CTR</th></tr></thead>
      <tbody>
      <?php foreach ($statsDaily as $row): ?>
        <tr>
          <td><?= e((string) $row['event_date']) ?></td>
          <td><?= e((string) $row['placement_code']) ?></td>
          <td><?= (int) $row['impressions'] ?></td>
          <td><?= (int) $row['clicks'] ?></td>
          <td><?= e((string) $row['ctr']) ?>%</td>
        </tr>
      <?php endforeach; ?>
      <?php if ($statsDaily === []): ?><tr><td colspan="5">Aucune statistique enregistrée pour le moment.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>
<?php
echo render_layout((string) ob_get_clean(), 'Publicités');
