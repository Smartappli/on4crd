<?php
declare(strict_types=1);

require_permission('admin.access');

$keys = [
    'committee.title' => 'Comité — titre',
    'committee.intro' => 'Comité — introduction',
    'committee.mission' => 'Comité — mission',
    'press.title' => 'Presse — titre',
    'press.intro' => 'Presse — introduction',
    'press.contact' => 'Presse — contact',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        foreach ($keys as $contentKey => $_label) {
            save_editorial_content(
                $contentKey,
                (string) ($_POST[$contentKey]['fr'] ?? ''),
                (string) ($_POST[$contentKey]['en'] ?? ''),
                (string) ($_POST[$contentKey]['de'] ?? ''),
                (string) ($_POST[$contentKey]['nl'] ?? '')
            );
        }
        set_flash('success', 'Contenus éditoriaux mis à jour.');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_editorial');
}

ob_start();
?>
<div class="card">
    <h1>Éditorial multilingue</h1>
    <p>Le français reste la source. Les autres langues peuvent être générées automatiquement puis relues.</p>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="stack">
            <?php foreach ($keys as $contentKey => $label): ?>
                <?php $entry = editorial_content_row($contentKey); ?>
                <section class="card muted-card">
                    <h2><?= e($label) ?></h2>
                    <div class="grid-2">
                        <label>Français (source)
                            <textarea name="<?= e($contentKey) ?>[fr]" rows="4"><?= e((string) ($entry['fr_text'] ?? '')) ?></textarea>
                        </label>
                        <label>English
                            <textarea name="<?= e($contentKey) ?>[en]" rows="4"><?= e((string) ($entry['en_text'] ?? '')) ?></textarea>
                        </label>
                        <label>Deutsch
                            <textarea name="<?= e($contentKey) ?>[de]" rows="4"><?= e((string) ($entry['de_text'] ?? '')) ?></textarea>
                        </label>
                        <label>Nederlands
                            <textarea name="<?= e($contentKey) ?>[nl]" rows="4"><?= e((string) ($entry['nl_text'] ?? '')) ?></textarea>
                        </label>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <p><button class="button">Enregistrer</button></p>
    </form>
</div>
<?php
echo render_layout((string) ob_get_clean(), 'Éditorial multilingue');
