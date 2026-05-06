<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$i18n = [
    'fr' => ['updated' => 'Flux live mis à jour.', 'title' => 'Administration des flux live', 'intro' => 'Les widgets temps réel sont confinés au tableau de bord membre. Ici vous gérez l’activation, l’URL, le parseur et les TTL sans exposer ces widgets sur les pages publiques.', 'active' => 'Actif', 'label' => 'Libellé', 'url' => 'URL', 'parser' => 'Parseur', 'cache_ttl' => 'Cache TTL', 'refresh' => 'Rafraîchissement navigateur', 'notes' => 'Notes', 'save' => 'Enregistrer', 'layout' => 'Flux live', 'meta_desc' => 'Configuration des flux live affichés dans l’espace membre.'],
    'en' => ['updated' => 'Live feeds updated.', 'title' => 'Live feeds administration', 'intro' => 'Real-time widgets are limited to the member dashboard. Manage activation, URL, parser and TTL without exposing widgets on public pages.', 'active' => 'Active', 'label' => 'Label', 'url' => 'URL', 'parser' => 'Parser', 'cache_ttl' => 'Cache TTL', 'refresh' => 'Browser refresh', 'notes' => 'Notes', 'save' => 'Save', 'layout' => 'Live feeds', 'meta_desc' => 'Configure live feeds shown in the members area.'],
    'de' => ['updated' => 'Live-Feeds aktualisiert.', 'title' => 'Verwaltung der Live-Feeds', 'intro' => 'Echtzeit-Widgets sind auf das Mitglieder-Dashboard beschränkt. Verwalten Sie Aktivierung, URL, Parser und TTL ohne öffentliche Anzeige.', 'active' => 'Aktiv', 'label' => 'Bezeichnung', 'url' => 'URL', 'parser' => 'Parser', 'cache_ttl' => 'Cache-TTL', 'refresh' => 'Browser-Aktualisierung', 'notes' => 'Notizen', 'save' => 'Speichern', 'layout' => 'Live-Feeds', 'meta_desc' => 'Konfiguration der Live-Feeds im Mitgliederbereich.'],
    'nl' => ['updated' => 'Livefeeds bijgewerkt.', 'title' => 'Beheer van livefeeds', 'intro' => 'Realtime widgets zijn beperkt tot het ledendashboard. Beheer hier activatie, URL, parser en TTL zonder ze publiek te tonen.', 'active' => 'Actief', 'label' => 'Label', 'url' => 'URL', 'parser' => 'Parser', 'cache_ttl' => 'Cache-TTL', 'refresh' => 'Browserverversing', 'notes' => 'Notities', 'save' => 'Opslaan', 'layout' => 'Livefeeds', 'meta_desc' => 'Configuratie van livefeeds die in de ledenruimte worden getoond.'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        foreach ((array) ($_POST['feeds'] ?? []) as $id => $payload) {
            $url = trim((string) ($payload['url'] ?? ''));
            $validatedUrl = $url !== '' ? validate_remote_feed_url($url) : null;
            db()->prepare('UPDATE live_feeds SET label = ?, url = ?, parser = ?, cache_ttl = ?, refresh_seconds = ?, is_enabled = ?, notes = ? WHERE id = ?')->execute([
                trim((string) ($payload['label'] ?? '')),
                $validatedUrl,
                trim((string) ($payload['parser'] ?? 'json')),
                max(60, (int) ($payload['cache_ttl'] ?? 900)),
                max(60, (int) ($payload['refresh_seconds'] ?? 900)),
                !empty($payload['is_enabled']) ? 1 : 0,
                trim((string) ($payload['notes'] ?? '')),
                (int) $id,
            ]);
        }
        set_flash('success', (string) $t['updated']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_live_feeds');
}

$rows = table_exists('live_feeds') ? db()->query('SELECT * FROM live_feeds ORDER BY code ASC')->fetchAll() : [];

ob_start();
?>
<div class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <p><?= e((string) $t['intro']) ?></p>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="stack">
            <?php foreach ($rows as $row): ?>
                <section class="card muted-card">
                    <div class="row-between">
                        <strong><?= e((string) $row['code']) ?></strong>
                        <label><input type="checkbox" name="feeds[<?= (int) $row['id'] ?>][is_enabled]" value="1" <?= (int) $row['is_enabled'] === 1 ? 'checked' : '' ?>> <?= e((string) $t['active']) ?></label>
                    </div>
                    <div class="form-grid">
                        <label><?= e((string) $t['label']) ?><input type="text" name="feeds[<?= (int) $row['id'] ?>][label]" value="<?= e((string) $row['label']) ?>"></label>
                        <label><?= e((string) $t['url']) ?><input type="text" name="feeds[<?= (int) $row['id'] ?>][url]" value="<?= e((string) $row['url']) ?>"></label>
                        <label><?= e((string) $t['parser']) ?><input type="text" name="feeds[<?= (int) $row['id'] ?>][parser]" value="<?= e((string) $row['parser']) ?>"></label>
                        <label><?= e((string) $t['cache_ttl']) ?><input type="number" name="feeds[<?= (int) $row['id'] ?>][cache_ttl]" value="<?= (int) $row['cache_ttl'] ?>"></label>
                        <label><?= e((string) $t['refresh']) ?><input type="number" name="feeds[<?= (int) $row['id'] ?>][refresh_seconds]" value="<?= (int) $row['refresh_seconds'] ?>"></label>
                        <label><?= e((string) $t['notes']) ?><textarea name="feeds[<?= (int) $row['id'] ?>][notes]" rows="3"><?= e((string) $row['notes']) ?></textarea></label>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <p><button class="button"><?= e((string) $t['save']) ?></button></p>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
