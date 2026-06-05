<?php
declare(strict_types=1);

$user = current_user();
$locale = current_locale();
$t = i18n_domain_translator('profile', $locale);

set_page_meta([
    'title' => 'Vie privee et RGPD',
    'description' => 'Notice RGPD ON4CRD, droits des personnes et reglages de visibilite.',
    'schema_type' => 'WebPage',
]);

$noticeSections = privacy_notice_sections();
$privacyContact = privacy_contact_config();
$visibilityOptions = [
    'public' => $t('public'),
    'members' => $t('members'),
    'private' => $t('private'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_login();
    $memberId = (int) ($user['id'] ?? 0);
    verify_csrf();

    $action = (string) ($_POST['action'] ?? 'save_visibility');
    if ($action === 'export_data') {
        privacy_send_member_export($memberId);
    }

    if ($action === 'privacy_request') {
        $type = (string) ($_POST['request_type'] ?? 'access');
        $notes = (string) ($_POST['request_notes'] ?? '');
        privacy_create_request($memberId, $type, $notes);
        set_flash('success', 'Votre demande RGPD a ete enregistree.');
        redirect('gdpr');
    }

    if ($action === 'save_visibility') {
        $visibilityFields = array_filter(
            member_profile_visibility_fields($t),
            static fn(array $fieldMeta, string $fieldName): bool => table_has_column('members', $fieldName),
            ARRAY_FILTER_USE_BOTH
        );
        $allowedVisibilities = array_keys($visibilityOptions);
        $visibilityPayload = [];
        foreach ($visibilityFields as $field => $fieldMeta) {
            $defaultVisibility = (string) ($fieldMeta['default'] ?? 'members');
            $value = (string) ($_POST[$field] ?? $defaultVisibility);
            $visibilityPayload[$field] = in_array($value, $allowedVisibilities, true) ? $value : $defaultVisibility;
        }

        if ($visibilityPayload !== []) {
            $assignments = implode(', ', array_map(
                static fn(string $field): string => $field . ' = ?',
                array_keys($visibilityFields)
            ));
            $stmt = db()->prepare('UPDATE members SET ' . $assignments . ' WHERE id = ?');
            $stmt->execute([...array_values($visibilityPayload), $memberId]);
        }

        set_flash('success', $t('saved'));
        redirect('gdpr');
    }
}

$member = [];
$visibilityFields = [];
$profileViews = [];
$profileAllPreviewRows = [];
$profilePreviewRows = [];
$privacyRequests = [];

if ($user !== null) {
    $memberId = (int) ($user['id'] ?? 0);
    $visibilityFields = array_filter(
        member_profile_visibility_fields($t),
        static fn(array $fieldMeta, string $fieldName): bool => table_has_column('members', $fieldName),
        ARRAY_FILTER_USE_BOTH
    );

    $stmt = db()->prepare('SELECT ' . member_profile_select_columns_sql() . ' FROM members WHERE id = ? LIMIT 1');
    $stmt->execute([$memberId]);
    $member = $stmt->fetch() ?: [];
    $member = member_backfill_missing_qrz_url($memberId, is_array($member) ? $member : []);
    $member = member_with_name_parts($member);

    $profileViews = [
        'public' => ['title' => 'Vue ' . strtolower($t('public'))],
        'members' => ['title' => 'Vue ' . strtolower($t('members'))],
        'private' => ['title' => 'Vue ' . strtolower($t('private'))],
    ];
    foreach (array_keys($profileViews) as $viewer) {
        $profileAllPreviewRows[$viewer] = member_profile_preview_rows($member, (string) $viewer, $t, true);
        $profilePreviewRows[$viewer] = array_values(array_filter(
            $profileAllPreviewRows[$viewer],
            static fn(array $previewRow): bool => (bool) $previewRow['visible']
        ));
    }
    $privacyRequests = privacy_member_requests($memberId);
}

ob_start();
?>
<div class="gdpr-page stack">
    <section class="card gdpr-privacy-card">
        <h1>Vie privee et RGPD</h1>
        <p class="help">Version de notice: <?= e(privacy_current_notice_version()) ?></p>
        <div class="grid-2">
            <?php foreach ($noticeSections as $title => $body): ?>
                <article class="gdpr-notice-section">
                    <h2><?= e((string) $title) ?></h2>
                    <p><?= e((string) $body) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($user === null): ?>
        <section class="card gdpr-privacy-card">
            <h2>Droits des membres</h2>
            <p>Contact public pour toute demande donnees personnelles: <a href="mailto:<?= e($privacyContact['controller_email']) ?>"><?= e($privacyContact['controller_email']) ?></a>.</p>
            <p>Connectez-vous pour exporter vos donnees, deposer une demande RGPD et regler la visibilite de votre profil.</p>
            <p><a class="button" href="<?= e(route_url('login')) ?>">Se connecter</a></p>
        </section>
    <?php else: ?>
        <section class="card gdpr-privacy-card">
            <div class="gdpr-section-heading">
                <div>
                    <h2>Vos droits</h2>
                    <p class="help">Export direct et demandes tracees avec donnees techniques pseudonymisees.</p>
                </div>
            </div>
            <div class="grid-2">
                <form method="post" class="stack">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="export_data">
                    <p>Telechargez une copie JSON des donnees rattachees a votre compte, avec manifeste des fichiers personnels connus.</p>
                    <button type="submit" class="button">Exporter mes donnees</button>
                </form>
                <form method="post" class="stack">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="privacy_request">
                    <label>Type de demande
                        <select name="request_type" required>
                            <option value="access">Acces</option>
                            <option value="rectification">Rectification</option>
                            <option value="erasure">Suppression</option>
                            <option value="restriction">Limitation</option>
                            <option value="objection">Opposition</option>
                            <option value="portability">Portabilite</option>
                        </select>
                    </label>
                    <label>Precision utile
                        <textarea name="request_notes" rows="4" maxlength="2000"></textarea>
                    </label>
                    <button type="submit" class="button secondary">Enregistrer la demande</button>
                </form>
            </div>
            <?php if ($privacyRequests !== []): ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Demande</th><th>Statut</th><th>Date</th><th>Traitement</th><th>Resolution</th></tr></thead>
                        <tbody>
                            <?php foreach ($privacyRequests as $request): ?>
                                <tr>
                                    <td><?= e((string) $request['request_type']) ?></td>
                                    <td><?= e((string) $request['status']) ?></td>
                                    <td><?= e((string) $request['requested_at']) ?></td>
                                    <td>
                                        <?= e((string) ($request['processed_at'] ?? '')) ?>
                                        <?php if (!empty($request['erasure_completed_at'])): ?><div class="help">Anonymisation: <?= e((string) $request['erasure_completed_at']) ?></div><?php endif; ?>
                                    </td>
                                    <td><?= e((string) ($request['resolved_at'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <div class="card gdpr-hero">
            <?php $avatarSrc = member_avatar_src($member); ?>
            <div class="gdpr-profile-views">
                <?php foreach ($profileViews as $viewer => $view): ?>
                    <?php $canSeePhoto = member_profile_visibility_allows((string) $viewer, (string) ($member['visibility_photo'] ?? 'private')); ?>
                    <section class="gdpr-profile-view" data-gdpr-view="<?= e((string) $viewer) ?>">
                        <header>
                            <img class="gdpr-avatar" src="<?= e($avatarSrc) ?>" alt="<?= e($t('avatar_alt')) ?>" data-gdpr-photo data-gdpr-visibility-field="visibility_photo" <?= $canSeePhoto ? '' : 'hidden' ?>>
                            <div>
                                <h2><?= e((string) $view['title']) ?></h2>
                                <p class="gdpr-callsign"><?= e((string) ($member['callsign'] ?? '')) ?></p>
                            </div>
                        </header>
                        <p class="help" data-gdpr-empty <?= $profilePreviewRows[(string) $viewer] === [] ? '' : 'hidden' ?>>Aucune information visible.</p>
                        <dl class="gdpr-profile-summary">
                            <?php foreach ($profileAllPreviewRows[(string) $viewer] as $previewRow): ?>
                                <div data-gdpr-preview-row data-gdpr-visibility-field="<?= e((string) $previewRow['visibility_field']) ?>" <?= (bool) $previewRow['visible'] ? '' : 'hidden' ?>>
                                    <dt><?= e((string) $previewRow['label']) ?></dt>
                                    <dd><?= (string) $previewRow['html'] ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>

        <section class="card gdpr-privacy-card" id="privacy">
            <div class="gdpr-section-heading">
                <div>
                    <h2><?= e($t('directory_visibility')) ?></h2>
                    <p class="help"><?= e($t('visibility_help')) ?></p>
                </div>
            </div>
            <form method="post" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save_visibility">

                <div class="gdpr-visibility-table" role="table" aria-label="<?= e($t('directory_visibility')) ?>">
                    <div class="gdpr-visibility-header" role="row">
                        <span role="columnheader"><?= e($t('profile_settings')) ?></span>
                        <?php foreach ($visibilityOptions as $visibilityLabel): ?>
                            <span role="columnheader"><?= e($visibilityLabel) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php foreach ($visibilityFields as $fieldName => $fieldMeta): ?>
                        <?php $currentValue = (string) ($member[$fieldName] ?? (string) $fieldMeta['default']); ?>
                        <div class="gdpr-visibility-row" role="row">
                            <span class="gdpr-field-label" role="cell"><?= e((string) $fieldMeta['label']) ?></span>
                            <?php foreach ($visibilityOptions as $visibilityValue => $visibilityLabel): ?>
                                <label class="gdpr-choice" role="cell">
                                    <input type="radio" name="<?= e($fieldName) ?>" value="<?= e($visibilityValue) ?>" <?= $currentValue === $visibilityValue ? 'checked' : '' ?>>
                                    <span><?= e($visibilityLabel) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="gdpr-actions">
                    <button type="submit" class="button"><?= e($t('save')) ?></button>
                </div>
            </form>
        </section>
    <?php endif; ?>
</div>
<?php

echo render_layout((string) ob_get_clean(), 'Vie privee et RGPD');
