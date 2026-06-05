<?php
declare(strict_types=1);

$messages = i18n_domain_locale('reglement_interieur', current_locale());

$title = (string) ($messages['title'] ?? 'Règlement d\'ordre intérieur');
$summary = (string) ($messages['summary'] ?? $messages['body'] ?? '');
$sections = isset($messages['sections']) && is_array($messages['sections']) ? $messages['sections'] : [];
$updatedAtLabel = (string) ($messages['updated_at_label'] ?? 'Dernière mise à jour');
$updatedAt = (string) ($messages['updated_at'] ?? '05 juin 2026');
$statusLabel = (string) ($messages['status_label'] ?? 'Statut');
$status = (string) ($messages['status'] ?? 'Projet à valider par le comité');

$clubName = trim((string) config('privacy.controller_name', 'Radio Club Durnal ON4CRD'));
$clubEmail = site_contact_email();

$replacePlaceholders = static function (string $value) use ($clubName, $clubEmail): string {
    return strtr($value, [
        '{club_name}' => $clubName,
        '{contact_email}' => $clubEmail,
    ]);
};

$renderTextBlocks = static function (string $value) use ($replacePlaceholders): string {
    $blocks = preg_split('/\R{2,}/', trim($replacePlaceholders($value))) ?: [];
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
    'description' => mb_substr(trim($replacePlaceholders($summary)), 0, 160),
    'schema_type' => 'WebPage',
]);

ob_start();
?>
<div class="stack">
    <section class="card">
        <p class="help"><?= e($updatedAtLabel) ?> : <?= e($updatedAt) ?> · <?= e($statusLabel) ?> : <?= e($status) ?></p>
        <h1><?= e($title) ?></h1>
        <?= $renderTextBlocks($summary) ?>
        <p class="help">
            <?= e((string) ($messages['contact_label'] ?? 'Contact club')) ?> :
            <a href="mailto:<?= e($clubEmail) ?>"><?= e($clubEmail) ?></a>
        </p>
    </section>

    <?php foreach ($sections as $section): ?>
        <?php if (!is_array($section)) { continue; } ?>
        <section class="card">
            <h2><?= e($replacePlaceholders((string) ($section['title'] ?? ''))) ?></h2>
            <?= $renderTextBlocks((string) ($section['body'] ?? '')) ?>
            <?php $items = isset($section['items']) && is_array($section['items']) ? $section['items'] : []; ?>
            <?php if ($items !== []): ?>
                <ul>
                    <?php foreach ($items as $item): ?>
                        <li><?= e($replacePlaceholders((string) $item)) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <section class="card">
        <h2><?= e((string) ($messages['related_pages_title'] ?? 'Pages associées')) ?></h2>
        <div class="actions">
            <a class="button secondary" href="<?= e(route_url('membership')) ?>"><?= e((string) ($messages['membership_link_label'] ?? 'Devenir membre')) ?></a>
            <a class="button secondary" href="<?= e(route_url('conditions_utilisation')) ?>"><?= e((string) ($messages['terms_link_label'] ?? 'Conditions générales d\'utilisation')) ?></a>
            <a class="button secondary" href="<?= e(route_url('gdpr')) ?>"><?= e((string) ($messages['privacy_link_label'] ?? 'Vie privée et RGPD')) ?></a>
        </div>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $title);
