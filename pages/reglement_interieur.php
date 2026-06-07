<?php
declare(strict_types=1);

$messages = i18n_domain_locale('reglement_interieur', current_locale());

$text = static fn (string $key): string => (string) ($messages[$key] ?? $key);

$title = $text('title');
$summary = (string) ($messages['summary'] ?? $messages['body'] ?? '');
$sections = isset($messages['sections']) && is_array($messages['sections']) ? $messages['sections'] : [];
$updatedAtLabel = $text('updated_at_label');
$updatedAt = $text('updated_at');
$statusLabel = $text('status_label');
$status = $text('status');
$roiImagePath = 'assets/roi/roi.jpg';
$roiImageAbsolutePath = dirname(__DIR__) . '/' . $roiImagePath;
$roiImageUrl = is_file($roiImageAbsolutePath) ? asset_url($roiImagePath) : '';

$clubName = trim((string) config('privacy.controller_name', 'Radio Club Durnal ON4CRD'));
$clubEmail = site_contact_email();
$introSummary = str_contains($summary, '{club_name}.')
    ? str_replace('{club_name}.', "\n\n{club_name}.\n\n", $summary)
    : str_replace('{club_name}', "\n\n{club_name}\n\n", $summary);

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
    'image' => $roiImageUrl,
    'schema_type' => 'WebPage',
]);

ob_start();
?>
<div class="stack reglement-interieur-module">
    <section class="card reglement-interieur-hero">
        <div class="reglement-interieur-hero-copy">
            <p class="help"><?= e($updatedAtLabel) ?> : <?= e($updatedAt) ?> · <?= e($statusLabel) ?> : <?= e($status) ?></p>
            <h1><?= e($title) ?></h1>
            <?= $renderTextBlocks($introSummary) ?>
            <p class="help">
                <?= e($text('contact_label')) ?> :
                <a href="mailto:<?= e($clubEmail) ?>"><?= e($clubEmail) ?></a>
            </p>
        </div>
        <?php if ($roiImageUrl !== ''): ?>
            <figure class="reglement-interieur-illustration">
                <img src="<?= e($roiImageUrl) ?>" alt="<?= e($title) ?>" loading="lazy" decoding="async">
            </figure>
        <?php endif; ?>
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
        <h2><?= e($text('related_pages_title')) ?></h2>
        <div class="actions">
            <a class="button secondary" href="<?= e(route_url('membership')) ?>"><?= e($text('membership_link_label')) ?></a>
            <a class="button secondary" href="<?= e(route_url('conditions_utilisation')) ?>"><?= e($text('terms_link_label')) ?></a>
            <a class="button secondary" href="<?= e(route_url('gdpr')) ?>"><?= e($text('privacy_link_label')) ?></a>
        </div>
    </section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $title);
