<?php
declare(strict_types=1);

$messages = i18n_domain_locale('mentions_legales', current_locale());

$text = static fn (string $key): string => (string) ($messages[$key] ?? $key);

$title = $text('title');
$summary = (string) ($messages['summary'] ?? $messages['body'] ?? '');
$sections = isset($messages['sections']) && is_array($messages['sections']) ? $messages['sections'] : [];
$updatedAtLabel = $text('updated_at_label');
$updatedAt = $text('updated_at');
$notProvided = $text('not_provided');

$clubName = trim((string) config('privacy.controller_name', 'Radio Club Durnal ON4CRD'));
$clubEmail = site_contact_email();
$clubAddress = trim((string) config('privacy.controller_postal_address', 'Rue des Ecoles, 5530 Purnode, Belgique'));
$publicationManager = trim((string) config('legal.publication_manager', $text('default_publication_manager')));
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
    $hostingUrl,
    $notProvided
): string {
    return strtr($value, [
        '{club_name}' => $clubName,
        '{contact_email}' => $clubEmail,
        '{postal_address}' => $clubAddress,
        '{publication_manager}' => $publicationManager,
        '{hosting_name}' => $hostingName,
        '{hosting_address}' => $hostingAddress,
        '{hosting_url}' => $hostingUrl !== '' ? $hostingUrl : $notProvided,
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
        <h2><?= e($text('identity_title')) ?></h2>
        <dl>
            <dt><?= e($text('identity_editor')) ?></dt>
            <dd><?= e($clubName) ?></dd>
            <dt><?= e($text('identity_address')) ?></dt>
            <dd><?= e($clubAddress) ?></dd>
            <dt><?= e($text('identity_contact')) ?></dt>
            <dd><a href="mailto:<?= e($clubEmail) ?>"><?= e($clubEmail) ?></a></dd>
            <dt><?= e($text('identity_publication_manager')) ?></dt>
            <dd><?= e($publicationManager) ?></dd>
            <dt><?= e($text('identity_hosting')) ?></dt>
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
        <h2><?= e($text('related_pages_title')) ?></h2>
        <div class="actions">
            <a class="button secondary" href="<?= e(route_url('conditions_utilisation')) ?>"><?= e($text('terms_link_label')) ?></a>
            <a class="button secondary" href="<?= e(route_url('gdpr')) ?>"><?= e($text('privacy_link_label')) ?></a>
        </div>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $title);
