<?php
declare(strict_types=1);

require_module_enabled('admin');
require_permission('admin.access');

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/admin_editorial.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $t[$key] = i18n_localized_value($i18n, $locale, $key);
}

set_page_meta([
    'title' => (string) $t['layout'],
    'description' => (string) $t['meta_desc'],
    'robots' => 'noindex,nofollow',
]);

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
