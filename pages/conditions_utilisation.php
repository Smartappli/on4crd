<?php
declare(strict_types=1);

$messages = i18n_domain_locale('conditions_utilisation', current_locale());

$title = (string) ($messages['title'] ?? 'Conditions générales d\'utilisation');
$summary = (string) ($messages['summary'] ?? $messages['body'] ?? '');
$sections = isset($messages['sections']) && is_array($messages['sections']) ? $messages['sections'] : [];
$updatedAtLabel = (string) ($messages['updated_at_label'] ?? 'Dernière mise à jour');
$updatedAt = (string) ($messages['updated_at'] ?? '05 juin 2026');

$clubName = trim((string) config('privacy.controller_name', 'Radio Club Durnal ON4CRD'));
$clubEmail = site_contact_email();
$clubAddress = trim((string) config('privacy.controller_postal_address', 'Rue des Ecoles, 5530 Purnode, Belgique'));

$replaceLegalPlaceholders = static function (string $value) use ($clubName, $clubEmail, $clubAddress): string {
    return strtr($value, [
        '{club_name}' => $clubName,
        '{contact_email}' => $clubEmail,
        '{postal_address}' => $clubAddress,
    ]);
};

$renderTextBlocks = static function (string $value) use ($replaceLegalPlaceholders): string {
    $blocks = preg_split('/\R{2,}/', trim($replaceLegalPlaceholders($value))) ?: [];
    $html = '';
    foreach ($blocks as $block) {
        $block = trim((string) $block);
        if ($block !== '') {
            $html .= '<p>' . e($block) . '</p>';
        }
    }

    return $html;
};

set_page_meta([
    'title' => $title,
    'description' => mb_substr(trim($replaceLegalPlaceholders($summary)), 0, 160),
    'schema_type' => 'WebPage',
]);

ob_start();
?>
<div class="stack">
    <section class="card">
        <p class="help"><?= e($updatedAtLabel) ?> : <?= e($updatedAt) ?></p>
        <h1><?= e($title) ?></h1>
        <?= $renderTextBlocks($summary) ?>
        <p class="help">
            <?= e((string) ($messages['service_reference'] ?? 'Service édité par')) ?> :
            <?= e($clubName) ?>,
            <a href="mailto:<?= e($clubEmail) ?>"><?= e($clubEmail) ?></a>
        </p>
    </section>

    <section class="card">
        <h2><?= e((string) ($messages['identity_title'] ?? 'Référence du service')) ?></h2>
        <dl>
            <dt><?= e((string) ($messages['identity_editor'] ?? 'Éditeur')) ?></dt>
            <dd><?= e($clubName) ?></dd>
            <dt><?= e((string) ($messages['identity_contact'] ?? 'Contact')) ?></dt>
            <dd><a href="mailto:<?= e($clubEmail) ?>"><?= e($clubEmail) ?></a></dd>
            <dt><?= e((string) ($messages['identity_address'] ?? 'Adresse')) ?></dt>
            <dd><?= e($clubAddress) ?></dd>
        </dl>
    </section>

    <?php foreach ($sections as $section): ?>
        <?php if (!is_array($section)) { continue; } ?>
        <section class="card">
            <h2><?= e($replaceLegalPlaceholders((string) ($section['title'] ?? ''))) ?></h2>
            <?= $renderTextBlocks((string) ($section['body'] ?? '')) ?>
            <?php $items = isset($section['items']) && is_array($section['items']) ? $section['items'] : []; ?>
            <?php if ($items !== []): ?>
                <ul>
                    <?php foreach ($items as $item): ?>
                        <li><?= e($replaceLegalPlaceholders((string) $item)) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <section class="card">
        <h2><?= e((string) ($messages['related_pages_title'] ?? 'Pages associées')) ?></h2>
        <p>
            <a class="button secondary" href="<?= e(route_url('mentions_legales')) ?>"><?= e((string) ($messages['legal_link_label'] ?? 'Mentions légales')) ?></a>
            <a class="button secondary" href="<?= e(route_url('gdpr')) ?>"><?= e((string) ($messages['privacy_link_label'] ?? 'Vie privée et RGPD')) ?></a>
        </p>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $title);
