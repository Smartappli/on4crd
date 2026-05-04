<?php
declare(strict_types=1);

require_module_enabled('admin');
require_permission('admin.access');

$keys = [
    'committee.title' => 'Comité — titre',
    'committee.intro' => 'Comité — introduction',
    'committee.mission' => 'Comité — mission',
    'press.title' => 'Presse — titre',
    'press.intro' => 'Presse — introduction',
    'press.contact' => 'Presse — contact',
];

$fieldMap = [
    'committee_title' => 'committee.title',
    'committee_intro' => 'committee.intro',
    'committee_mission' => 'committee.mission',
    'press_title' => 'press.title',
    'press_intro' => 'press.intro',
    'press_contact' => 'press.contact',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $payload = is_array($_POST['content'] ?? null) ? $_POST['content'] : [];
        foreach ($fieldMap as $fieldKey => $contentKey) {
            $row = is_array($payload[$fieldKey] ?? null) ? $payload[$fieldKey] : [];
            save_editorial_content(
                $contentKey,
                (string) ($row['fr'] ?? ''),
                (string) ($row['en'] ?? ''),
                (string) ($row['de'] ?? ''),
                (string) ($row['nl'] ?? '')
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
                <?php
                    try {
                        $entry = editorial_content_row($contentKey) ?? [];
                    } catch (Throwable $_editorialReadError) {
                        $entry = [];
                    }
                    $fieldKey = (string) (array_search($contentKey, $fieldMap, true) ?: $contentKey);
                    $frValue = (string) ($entry['fr'] ?? $entry['fr_text'] ?? '');
                    $enValue = (string) ($entry['en'] ?? $entry['en_text'] ?? '');
                    $deValue = (string) ($entry['de'] ?? $entry['de_text'] ?? '');
                    $nlValue = (string) ($entry['nl'] ?? $entry['nl_text'] ?? '');
                ?>
                <section class="card muted-card">
                    <h2><?= e($label) ?></h2>
                    <div class="grid-2">
                        <label>Français (source)
                            <textarea name="content[<?= e((string) $fieldKey) ?>][fr]" rows="4"><?= e($frValue) ?></textarea>
                        </label>
                        <label>English
                            <textarea name="content[<?= e((string) $fieldKey) ?>][en]" rows="4"><?= e($enValue) ?></textarea>
                        </label>
                        <label>Deutsch
                            <textarea name="content[<?= e((string) $fieldKey) ?>][de]" rows="4"><?= e($deValue) ?></textarea>
                        </label>
                        <label>Nederlands
                            <textarea name="content[<?= e((string) $fieldKey) ?>][nl]" rows="4"><?= e($nlValue) ?></textarea>
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
