<?php
declare(strict_types=1);

$user = require_login();
$memberId = (int) ($user['id'] ?? 0);
$locale = current_locale();
$t = i18n_domain_translator('profile', $locale);

set_page_meta([
    'title' => $t('meta_title'),
    'description' => $t('meta_desc'),
    'schema_type' => 'ProfilePage',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $fullName = member_full_name_from_parts($firstName, $lastName);
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $country = trim((string) ($_POST['country'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $postalCode = trim((string) ($_POST['postal_code'] ?? ''));
        $qth = trim((string) ($_POST['qth'] ?? ''));
        $locator = strtoupper(trim((string) ($_POST['locator'] ?? '')));
        $licenceClass = trim((string) ($_POST['licence_class'] ?? ''));
        $operatorSince = trim((string) ($_POST['operator_since'] ?? ''));
        $cqZone = trim((string) ($_POST['cq_zone'] ?? ''));
        $ituZone = trim((string) ($_POST['itu_zone'] ?? ''));
        $qslVia = trim((string) ($_POST['qsl_via'] ?? ''));
        $lotwUsername = trim((string) ($_POST['lotw_username'] ?? ''));
        $eqslUsername = trim((string) ($_POST['eqsl_username'] ?? ''));
        $website = trim((string) ($_POST['website'] ?? ''));
        $isUbaMember = isset($_POST['is_uba_member']) ? 1 : 0;
        $ubaMemberNumber = trim((string) ($_POST['uba_member_number'] ?? ''));
        $stationEquipment = trim((string) ($_POST['station_equipment'] ?? ''));
        $antennas = trim((string) ($_POST['antennas'] ?? ''));
        $maxPower = trim((string) ($_POST['max_power'] ?? ''));
        $favouriteBands = member_profile_normalize_choice_post($_POST['favourite_bands'] ?? [], member_profile_favourite_band_choices());
        $favouriteModes = member_profile_normalize_choice_post($_POST['favourite_modes'] ?? [], member_profile_favourite_mode_choices());
        $interests = trim((string) ($_POST['interests'] ?? ''));

        if ($callsign === '' || $firstName === '' || $lastName === '' || $email === '') {
            throw new RuntimeException($t('required'));
        }
        if (mb_strlen($callsign) > 32 || mb_strlen($firstName) > 95 || mb_strlen($lastName) > 95 || mb_strlen($fullName) > 190 || mb_strlen($email) > 190 || mb_strlen($address) > 255 || mb_strlen($postalCode) > 32) {
            throw new RuntimeException($t('too_long'));
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException($t('invalid_email'));
        }
        if ($locator !== '' && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $locator) !== 1) {
            throw new RuntimeException($t('invalid_locator'));
        }
        if ($website !== '' && filter_var($website, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException($t('invalid_url'));
        }

        if ($locator === '' || $cqZone === '' || $ituZone === '') {
            $computedRadioLocation = member_profile_radio_location_from_address($country, $address, $postalCode, $qth);
            if (is_array($computedRadioLocation)) {
                if ($locator === '') {
                    $locator = (string) ($computedRadioLocation['locator'] ?? '');
                }
                if ($cqZone === '') {
                    $cqZone = (string) ($computedRadioLocation['cq_zone'] ?? '');
                }
                if ($ituZone === '') {
                    $ituZone = (string) ($computedRadioLocation['itu_zone'] ?? '');
                }
            }
        }
        if ($locator !== '' && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $locator) !== 1) {
            throw new RuntimeException($t('invalid_locator'));
        }

        $currentStmt = db()->prepare('SELECT auth_user_id, callsign, email, photo_path, avatar_path, qrz_url FROM members WHERE id = ? LIMIT 1');
        $currentStmt->execute([$memberId]);
        $currentMember = $currentStmt->fetch() ?: [];

        $duplicateMemberStmt = db()->prepare('SELECT COUNT(*) FROM members WHERE callsign = ? AND id <> ?');
        $duplicateMemberStmt->execute([$callsign, $memberId]);
        if ((int) $duplicateMemberStmt->fetchColumn() > 0) {
            throw new RuntimeException($t('callsign_taken'));
        }

        $authUserId = (int) ($currentMember['auth_user_id'] ?? 0);
        if ($authUserId > 0 && table_exists('users')) {
            $duplicateUserStmt = db()->prepare('SELECT COUNT(*) FROM users WHERE (email = ? OR username = ?) AND id <> ?');
            $duplicateUserStmt->execute([$email, $callsign, $authUserId]);
            if ((int) $duplicateUserStmt->fetchColumn() > 0) {
                throw new RuntimeException($t('auth_identifier_taken'));
            }
        }

        $qrzUrl = member_qrz_url_for_profile_save(
            $callsign,
            (string) ($currentMember['callsign'] ?? ''),
            (string) ($currentMember['qrz_url'] ?? '')
        );
        $lotwUsername = (string) member_lotw_username_for_profile_save($callsign, $lotwUsername);

        $newPhotoPath = trim((string) ($currentMember['photo_path'] ?? ''));
        $newAvatarPath = trim((string) ($currentMember['avatar_path'] ?? ''));
        if (isset($_FILES['photo']) && is_array($_FILES['photo']) && (int) ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $savedFilename = secure_move_uploaded_file(
                $_FILES['photo'],
                dirname(__DIR__) . '/storage/uploads/members',
                'member_' . $memberId,
                ['jpg', 'jpeg', 'png', 'webp'],
                6 * 1024 * 1024
            );
            $newPhotoPath = 'storage/uploads/members/' . $savedFilename;
            $generatedAvatarPath = generate_member_avatar_from_photo($newPhotoPath, $memberId);
            if ($generatedAvatarPath !== null && $generatedAvatarPath !== '') {
                $newAvatarPath = $generatedAvatarPath;
            }
        }

        db()->beginTransaction();
        $stmt = db()->prepare(
            'UPDATE members
         SET callsign = ?,
             first_name = ?,
             last_name = ?,
             full_name = ?,
             email = ?,
             phone = ?,
             country = ?,
             address = ?,
             postal_code = ?,
             qth = ?,
             locator = ?,
             photo_path = ?,
             avatar_path = ?,
             licence_class = ?,
             operator_since = ?,
             cq_zone = ?,
             itu_zone = ?,
             qsl_via = ?,
             lotw_username = ?,
             eqsl_username = ?,
             qrz_url = ?,
             website = ?,
             is_uba_member = ?,
             uba_member_number = ?,
             station_equipment = ?,
             antennas = ?,
              max_power = ?,
              favourite_bands = ?,
              favourite_modes = ?,
              interests = ?
         WHERE id = ?'
        );
        $stmt->execute([
            $callsign,
            $firstName,
            $lastName,
            $fullName,
            $email,
            $phone !== '' ? $phone : null,
            $country !== '' ? $country : null,
            $address !== '' ? $address : null,
            $postalCode !== '' ? $postalCode : null,
            $qth !== '' ? $qth : null,
            $locator !== '' ? $locator : null,
            $newPhotoPath !== '' ? $newPhotoPath : null,
            $newAvatarPath !== '' ? $newAvatarPath : null,
            $licenceClass !== '' ? $licenceClass : null,
            $operatorSince !== '' ? $operatorSince : null,
            $cqZone !== '' ? $cqZone : null,
            $ituZone !== '' ? $ituZone : null,
            $qslVia !== '' ? $qslVia : null,
            $lotwUsername !== '' ? $lotwUsername : null,
            $eqslUsername !== '' ? $eqslUsername : null,
            $qrzUrl,
            $website !== '' ? $website : null,
            $isUbaMember,
            $ubaMemberNumber !== '' ? $ubaMemberNumber : null,
            $stationEquipment !== '' ? $stationEquipment : null,
            $antennas !== '' ? $antennas : null,
            $maxPower !== '' ? $maxPower : null,
            $favouriteBands !== '' ? $favouriteBands : null,
            $favouriteModes !== '' ? $favouriteModes : null,
            $interests !== '' ? $interests : null,
            $memberId,
        ]);

        if ($authUserId > 0 && table_exists('users')) {
            db()->prepare('UPDATE users SET email = ?, username = ? WHERE id = ? LIMIT 1')->execute([$email, $callsign, $authUserId]);
        }

        db()->commit();
        set_flash('success', $t('saved'));
        redirect('profile');
    } catch (Throwable $throwable) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        set_flash('error', $throwable->getMessage());
        redirect('profile');
    }
}

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
$profilePreviewRows = [];
foreach (array_keys($profileViews) as $viewer) {
    $profilePreviewRows[$viewer] = member_profile_preview_rows($member, (string) $viewer, $t);
}

$operatorSinceOptionsHtml = member_profile_operator_since_options_html((string) ($member['operator_since'] ?? ''));
$favouriteBandsOptionsHtml = member_profile_checkbox_group_html('favourite_bands', member_profile_favourite_band_choices(), (string) ($member['favourite_bands'] ?? ''));
$favouriteModesOptionsHtml = member_profile_checkbox_group_html('favourite_modes', member_profile_favourite_mode_choices(), (string) ($member['favourite_modes'] ?? ''));
$requiredFieldHelp = $locale === 'fr' ? 'Champ obligatoire.' : 'Required field.';
$requiredFieldLabel = static function (string $label, string $tooltipId) use ($requiredFieldHelp): string {
    return '<span class="profile-label-with-help">' . e($label)
        . '<span class="profile-help-tooltip">'
        . '<button type="button" class="profile-help-trigger profile-required-help-trigger" aria-label="' . e($requiredFieldHelp) . '" aria-describedby="' . e($tooltipId) . '">!</button>'
        . '<span id="' . e($tooltipId) . '" class="profile-help-bubble" role="tooltip">' . e($requiredFieldHelp) . '</span>'
        . '</span></span>';
};

ob_start();
?>
<div class="stack">
<div class="card profile-hero">
    <?php $avatarSrc = member_avatar_src($member); ?>
    <div class="profile-preview-views">
        <?php foreach ($profileViews as $viewer => $view): ?>
            <?php $canSeePhoto = member_profile_visibility_allows((string) $viewer, (string) ($member['visibility_photo'] ?? 'members')); ?>
            <section class="profile-preview-view">
                <header>
                    <?php if ($canSeePhoto): ?>
                        <img class="profile-preview-avatar" src="<?= e($avatarSrc) ?>" alt="<?= e($t('avatar_alt')) ?>">
                    <?php endif; ?>
                    <div>
                        <h2><?= e((string) $view['title']) ?></h2>
                        <p class="profile-preview-callsign"><?= e((string) ($member['callsign'] ?? '')) ?></p>
                    </div>
                </header>
                <?php if ($profilePreviewRows[(string) $viewer] === []): ?>
                    <p class="help">Aucune information visible.</p>
                <?php else: ?>
                    <dl class="profile-preview-summary">
                        <?php foreach ($profilePreviewRows[(string) $viewer] as $previewRow): ?>
                            <div>
                                <dt><?= e((string) $previewRow['label']) ?></dt>
                                <dd><?= (string) $previewRow['html'] ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</div>

<section class="card">
    <h2><?= e($t('profile_settings')) ?></h2>
    <form method="post" class="stack" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <fieldset class="profile-fieldset">
            <legend><?= e($t('photo_section')) ?></legend>
        <label>
            <?= e($t('change_photo')) ?>
            <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <small class="help"><?= e($t('photo_help')) ?></small>
        </label>
        </fieldset>

        <fieldset class="profile-fieldset">
            <legend><?= e($t('identity_section')) ?></legend>
            <div class="profile-form-grid">
                <label><?= $requiredFieldLabel($t('callsign'), 'profile-required-callsign') ?><input type="text" name="callsign" maxlength="32" required value="<?= e((string) ($member['callsign'] ?? '')) ?>"></label>
                <label><?= $requiredFieldLabel($t('last_name'), 'profile-required-last-name') ?><input type="text" name="last_name" maxlength="95" required value="<?= e((string) ($member['last_name'] ?? '')) ?>"></label>
                <label><?= $requiredFieldLabel($t('first_name'), 'profile-required-first-name') ?><input type="text" name="first_name" maxlength="95" required value="<?= e((string) ($member['first_name'] ?? '')) ?>"></label>
                <label><?= $requiredFieldLabel($t('email'), 'profile-required-email') ?><input type="email" name="email" maxlength="190" required value="<?= e((string) ($member['email'] ?? '')) ?>"></label>
                <label><?= e($t('phone')) ?><input type="tel" name="phone" maxlength="64" value="<?= e((string) ($member['phone'] ?? '')) ?>" autocomplete="tel"></label>
                <label><?= e($t('country')) ?><select name="country" class="country-select"><?= member_country_select_options_html((string) ($member['country'] ?? '')) ?></select></label>
                <label><?= e($t('address')) ?><input type="text" name="address" maxlength="255" value="<?= e((string) ($member['address'] ?? '')) ?>" autocomplete="street-address"></label>
                <label><?= e($t('postal_code')) ?><input type="text" name="postal_code" maxlength="32" value="<?= e((string) ($member['postal_code'] ?? '')) ?>" autocomplete="postal-code"></label>
                <label><?= e($t('qth')) ?><input type="text" name="qth" maxlength="190" value="<?= e((string) ($member['qth'] ?? '')) ?>"></label>
                <label><?= e($t('grid')) ?><input type="text" name="locator" maxlength="6" value="<?= e((string) ($member['locator'] ?? '')) ?>"></label>
            </div>
        </fieldset>

        <fieldset class="profile-fieldset">
            <legend><?= e($t('radio_section')) ?></legend>
            <div class="profile-form-grid">
                <label><?= e($t('licence')) ?><input type="text" name="licence_class" maxlength="64" value="<?= e((string) ($member['licence_class'] ?? '')) ?>"></label>
                <label><?= e($t('operator_since')) ?><select name="operator_since"><?= $operatorSinceOptionsHtml ?></select></label>
                <label><?= e($t('cq_zone')) ?><input type="text" name="cq_zone" maxlength="16" value="<?= e((string) ($member['cq_zone'] ?? '')) ?>"></label>
                <label><?= e($t('itu_zone')) ?><input type="text" name="itu_zone" maxlength="16" value="<?= e((string) ($member['itu_zone'] ?? '')) ?>"></label>
                <p class="help profile-form-wide"><?= e($t('auto_radio_location_help')) ?></p>
                <label><?= e($t('qsl_via')) ?><input type="text" name="qsl_via" maxlength="190" value="<?= e((string) ($member['qsl_via'] ?? '')) ?>"></label>
                <label><?= e($t('eqsl_username')) ?><input type="text" name="eqsl_username" maxlength="190" value="<?= e((string) ($member['eqsl_username'] ?? '')) ?>"></label>
                <label class="profile-qrz-field"><span class="profile-label-with-help"><?= e($t('qrz_url')) ?><span class="profile-help-tooltip"><button type="button" class="profile-help-trigger" aria-label="<?= e($t('qrz_help')) ?>" aria-describedby="profile-qrz-help">?</button><span id="profile-qrz-help" class="profile-help-bubble" role="tooltip"><?= e($t('qrz_help')) ?></span></span></span><input type="url" maxlength="255" readonly value="<?= e((string) ($member['qrz_url'] ?? '')) ?>"></label>
                <label><?= e($t('website')) ?><input type="url" name="website" maxlength="255" value="<?= e((string) ($member['website'] ?? '')) ?>"></label>
                <label class="profile-checkbox"><input type="checkbox" name="is_uba_member" value="1" <?= (int) ($member['is_uba_member'] ?? 0) === 1 ? 'checked' : '' ?>> <span><?= e($t('uba_member')) ?></span></label>
                <label><?= e($t('uba_member_number')) ?><input type="text" name="uba_member_number" maxlength="64" value="<?= e((string) ($member['uba_member_number'] ?? '')) ?>"></label>
                <label><?= e($t('max_power')) ?><input type="text" name="max_power" maxlength="64" value="<?= e((string) ($member['max_power'] ?? '')) ?>"></label>
                <fieldset class="profile-choice-fieldset profile-form-wide"><legend><?= e($t('bands')) ?></legend><?= $favouriteBandsOptionsHtml ?></fieldset>
                <fieldset class="profile-choice-fieldset profile-form-wide"><legend><?= e($t('favourite_modes')) ?></legend><?= $favouriteModesOptionsHtml ?></fieldset>
                <label class="profile-form-wide"><?= e($t('station')) ?><textarea name="station_equipment" rows="4" maxlength="4000"><?= e((string) ($member['station_equipment'] ?? '')) ?></textarea></label>
                <label class="profile-form-wide"><?= e($t('antennas')) ?><textarea name="antennas" rows="3" maxlength="4000"><?= e((string) ($member['antennas'] ?? '')) ?></textarea></label>
                <label class="profile-form-wide"><?= e($t('interests')) ?><textarea name="interests" rows="3" maxlength="4000"><?= e((string) ($member['interests'] ?? '')) ?></textarea></label>
            </div>
        </fieldset>

        <div class="profile-form-actions">
            <button type="submit" class="button"><?= e($t('save')) ?></button>
        </div>
    </form>
</section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $t('title'));
