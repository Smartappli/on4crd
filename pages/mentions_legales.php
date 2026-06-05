<?php
declare(strict_types=1);

$messages = i18n_domain_locale('mentions_legales', current_locale());

$title = (string) ($messages['title'] ?? 'Mentions légales');
$summary = (string) ($messages['summary'] ?? $messages['body'] ?? '');
$sections = isset($messages['sections']) && is_array($messages['sections']) ? $messages['sections'] : [];
$updatedAtLabel = (string) ($messages['updated_at_label'] ?? 'Dernière mise à jour');
$updatedAt = (string) ($messages['updated_at'] ?? '05 juin 2026');

$clubName = trim((string) config('privacy.controller_name', 'Radio Club Durnal ON4CRD'));
$clubEmail = site_contact_email();
$clubAddress = trim((string) config('privacy.controller_postal_address', 'Rue des Ecoles, 5530 Purnode, Belgique'));
$publicationManager = trim((string) config('legal.publication_manager', 'Comité du Radio Club Durnal ON4CRD'));
$hostingName = trim((string) config('legal.hosting_name', 'OVH SAS (OVHcloud)'));
$hostingAddress = trim((string) config('legal.hosting_address', '2 rue Kellermann, 59100 Roubaix, France'));
$hostingUrl = trim((string) config('legal.hosting_url', 'https://www.ovhcloud.com/'));

$replaceLegalPlaceholders = static function (string $value) use (
    $clubName,
    $clubEmail,
    $clubAddress,
    $publicationManager,
    $hostingName,
    $hostingAddress,
    $hostingUrl
): string {
    return strtr($value, [
        '{club_name}' => $clubName,
        '{contact_email}' => $clubEmail,
        '{postal_address}' => $clubAddress,
        '{publication_manager}' => $publicationManager,
        '{hosting_name}' => $hostingName,
        '{hosting_address}' => $hostingAddress,
        '{hosting_url}' => $hostingUrl !== '' ? $hostingUrl : 'non renseigné',
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
    </section>

    <section class="card">
        <h2><?= e((string) ($messages['identity_title'] ?? 'Éditeur et hébergement')) ?></h2>
        <dl>
            <dt><?= e((string) ($messages['identity_editor'] ?? 'Éditeur du site')) ?></dt>
            <dd><?= e($clubName) ?></dd>
            <dt><?= e((string) ($messages['identity_address'] ?? 'Adresse')) ?></dt>
            <dd><?= e($clubAddress) ?></dd>
            <dt><?= e((string) ($messages['identity_contact'] ?? 'Contact')) ?></dt>
            <dd><a href="mailto:<?= e($clubEmail) ?>"><?= e($clubEmail) ?></a></dd>
            <dt><?= e((string) ($messages['identity_publication_manager'] ?? 'Responsable de publication')) ?></dt>
            <dd><?= e($publicationManager) ?></dd>
            <dt><?= e((string) ($messages['identity_hosting'] ?? 'Hébergement')) ?></dt>
            <dd>
                <?= e($hostingName) ?><br>
                <?= e($hostingAddress) ?>
                <?php if ($hostingUrl !== ''): ?><br><a href="<?= e($hostingUrl) ?>" rel="noopener noreferrer"><?= e($hostingUrl) ?></a><?php endif; ?>
            </dd>
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
        <div class="actions">
            <a class="button secondary" href="<?= e(route_url('conditions_utilisation')) ?>"><?= e((string) ($messages['terms_link_label'] ?? 'Conditions générales d\'utilisation')) ?></a>
            <a class="button secondary" href="<?= e(route_url('gdpr')) ?>"><?= e((string) ($messages['privacy_link_label'] ?? 'Vie privée et RGPD')) ?></a>
        </div>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $title);
