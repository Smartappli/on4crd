<?php
declare(strict_types=1);

require_module_enabled('admin');
require_permission('admin.access');

$locale = current_locale();
$i18n = [
    'fr' => ['ok_saved' => 'Contenus éditoriaux mis à jour.', 'layout' => 'Éditorial multilingue', 'title' => 'Éditorial multilingue', 'intro' => 'Le français reste la source. Les autres langues peuvent être générées automatiquement puis relues.', 'fr_source' => 'Français (source)', 'english' => 'English', 'deutsch' => 'Deutsch', 'dutch' => 'Nederlands', 'save' => 'Enregistrer', 'committee_title' => 'Comité — titre', 'committee_intro' => 'Comité — introduction', 'committee_mission' => 'Comité — mission', 'press_title' => 'Presse — titre', 'press_intro' => 'Presse — introduction', 'press_contact' => 'Presse — contact'],
    'en' => ['ok_saved' => 'Editorial content updated.', 'layout' => 'Multilingual editorial', 'title' => 'Multilingual editorial', 'intro' => 'French remains the source language. Other languages can be generated automatically and then reviewed.', 'fr_source' => 'French (source)', 'english' => 'English', 'deutsch' => 'German', 'dutch' => 'Dutch', 'save' => 'Save', 'committee_title' => 'Committee — title', 'committee_intro' => 'Committee — introduction', 'committee_mission' => 'Committee — mission', 'press_title' => 'Press — title', 'press_intro' => 'Press — introduction', 'press_contact' => 'Press — contact'],
    'de' => ['ok_saved' => 'Redaktionelle Inhalte aktualisiert.', 'layout' => 'Mehrsprachiges Editorial', 'title' => 'Mehrsprachiges Editorial', 'intro' => 'Französisch bleibt die Quellsprache. Andere Sprachen können automatisch erzeugt und anschließend geprüft werden.', 'fr_source' => 'Französisch (Quelle)', 'english' => 'Englisch', 'deutsch' => 'Deutsch', 'dutch' => 'Niederländisch', 'save' => 'Speichern', 'committee_title' => 'Komitee — Titel', 'committee_intro' => 'Komitee — Einleitung', 'committee_mission' => 'Komitee — Mission', 'press_title' => 'Presse — Titel', 'press_intro' => 'Presse — Einleitung', 'press_contact' => 'Presse — Kontakt'],
    'nl' => ['ok_saved' => 'Redactionele inhoud bijgewerkt.', 'layout' => 'Meertalige redactie', 'title' => 'Meertalige redactie', 'intro' => 'Frans blijft de brontaal. Andere talen kunnen automatisch worden gegenereerd en daarna nagekeken.', 'fr_source' => 'Frans (bron)', 'english' => 'Engels', 'deutsch' => 'Duits', 'dutch' => 'Nederlands', 'save' => 'Opslaan', 'committee_title' => 'Comité — titel', 'committee_intro' => 'Comité — inleiding', 'committee_mission' => 'Comité — missie', 'press_title' => 'Pers — titel', 'press_intro' => 'Pers — inleiding', 'press_contact' => 'Pers — contact'],
];
$t = $i18n[$locale] ?? $i18n['fr'];

$keys = [
    'committee.title' => (string) $t['committee_title'],
    'committee.intro' => (string) $t['committee_intro'],
    'committee.mission' => (string) $t['committee_mission'],
    'press.title' => (string) $t['press_title'],
    'press.intro' => (string) $t['press_intro'],
    'press.contact' => (string) $t['press_contact'],
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
        set_flash('success', (string) $t['ok_saved']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
    }
    redirect('admin_editorial');
}

ob_start();
?>
<div class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <p><?= e((string) $t['intro']) ?></p>
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
                        <label><?= e((string) $t['fr_source']) ?>
                            <textarea name="content[<?= e((string) $fieldKey) ?>][fr]" rows="4"><?= e($frValue) ?></textarea>
                        </label>
                        <label><?= e((string) $t['english']) ?>
                            <textarea name="content[<?= e((string) $fieldKey) ?>][en]" rows="4"><?= e($enValue) ?></textarea>
                        </label>
                        <label><?= e((string) $t['deutsch']) ?>
                            <textarea name="content[<?= e((string) $fieldKey) ?>][de]" rows="4"><?= e($deValue) ?></textarea>
                        </label>
                        <label><?= e((string) $t['dutch']) ?>
                            <textarea name="content[<?= e((string) $fieldKey) ?>][nl]" rows="4"><?= e($nlValue) ?></textarea>
                        </label>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <p><button class="button"><?= e((string) $t['save']) ?></button></p>
    </form>
</div>
<?php

echo render_layout((string) ob_get_clean(), (string) $t['layout']);
