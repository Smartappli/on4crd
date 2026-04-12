<?php
declare(strict_types=1);

require_permission('admin.access');

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
        set_flash('success', 'Flux live mis à jour.');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_live_feeds');
}

$rows = table_exists('live_feeds') ? db()->query('SELECT * FROM live_feeds ORDER BY code ASC')->fetchAll() : [];

ob_start();
?>
<div class="card">
    <h1>Administration des flux live</h1>
    <p>Les widgets temps réel sont confinés au tableau de bord membre. Ici vous gérez l’activation, l’URL, le parseur et les TTL sans exposer ces widgets sur les pages publiques.</p>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="stack">
            <?php foreach ($rows as $row): ?>
                <section class="card muted-card">
                    <div class="row-between">
                        <strong><?= e((string) $row['code']) ?></strong>
                        <label><input type="checkbox" name="feeds[<?= (int) $row['id'] ?>][is_enabled]" value="1" <?= (int) $row['is_enabled'] === 1 ? 'checked' : '' ?>> Actif</label>
                    </div>
                    <div class="form-grid">
                        <label>Libellé<input type="text" name="feeds[<?= (int) $row['id'] ?>][label]" value="<?= e((string) $row['label']) ?>"></label>
                        <label>URL<input type="text" name="feeds[<?= (int) $row['id'] ?>][url]" value="<?= e((string) $row['url']) ?>"></label>
                        <label>Parseur<input type="text" name="feeds[<?= (int) $row['id'] ?>][parser]" value="<?= e((string) $row['parser']) ?>"></label>
                        <label>Cache TTL<input type="number" name="feeds[<?= (int) $row['id'] ?>][cache_ttl]" value="<?= (int) $row['cache_ttl'] ?>"></label>
                        <label>Refresh navigateur<input type="number" name="feeds[<?= (int) $row['id'] ?>][refresh_seconds]" value="<?= (int) $row['refresh_seconds'] ?>"></label>
                        <label>Notes<textarea name="feeds[<?= (int) $row['id'] ?>][notes]" rows="3"><?= e((string) $row['notes']) ?></textarea></label>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <p><button class="button">Enregistrer</button></p>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Flux live');
