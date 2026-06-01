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

$visibilityOptions = [
    'public' => $t('public'),
    'members' => $t('members'),
    'private' => $t('private'),
];
$visibilityFields = [
    'visibility_photo' => ['label' => $t('photo'), 'default' => 'members'],
    'visibility_full_name' => ['label' => $t('full_name'), 'default' => 'members'],
    'visibility_email' => ['label' => $t('email'), 'default' => 'members'],
    'visibility_phone' => ['label' => $t('phone'), 'default' => 'private'],
    'visibility_qth' => ['label' => $t('qth'), 'default' => 'members'],
    'visibility_licence_class' => ['label' => $t('licence'), 'default' => 'members'],
    'visibility_favourite_bands' => ['label' => $t('bands'), 'default' => 'members'],
    'visibility_station' => ['label' => $t('station'), 'default' => 'members'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $qth = trim((string) ($_POST['qth'] ?? ''));
        $locator = strtoupper(trim((string) ($_POST['locator'] ?? '')));
        $bio = trim((string) ($_POST['bio'] ?? ''));
        $licenceClass = trim((string) ($_POST['licence_class'] ?? ''));
        $operatorSince = trim((string) ($_POST['operator_since'] ?? ''));
        $cqZone = trim((string) ($_POST['cq_zone'] ?? ''));
        $ituZone = trim((string) ($_POST['itu_zone'] ?? ''));
        $qslVia = trim((string) ($_POST['qsl_via'] ?? ''));
        $lotwUsername = trim((string) ($_POST['lotw_username'] ?? ''));
        $eqslUsername = trim((string) ($_POST['eqsl_username'] ?? ''));
        $qrzUrl = trim((string) ($_POST['qrz_url'] ?? ''));
        $website = trim((string) ($_POST['website'] ?? ''));
        $stationEquipment = trim((string) ($_POST['station_equipment'] ?? ''));
        $antennas = trim((string) ($_POST['antennas'] ?? ''));
        $maxPower = trim((string) ($_POST['max_power'] ?? ''));
        $favouriteBands = trim((string) ($_POST['favourite_bands'] ?? ''));
        $favouriteModes = trim((string) ($_POST['favourite_modes'] ?? ''));
        $interests = trim((string) ($_POST['interests'] ?? ''));

        if ($callsign === '' || $fullName === '' || $email === '') {
            throw new RuntimeException($t('required'));
        }
        if (mb_strlen($callsign) > 32 || mb_strlen($fullName) > 190 || mb_strlen($email) > 190) {
            throw new RuntimeException($t('too_long'));
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException($t('invalid_email'));
        }
        if ($locator !== '' && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $locator) !== 1) {
            throw new RuntimeException($t('invalid_locator'));
        }
        foreach (['qrz_url' => $qrzUrl, 'website' => $website] as $urlValue) {
            if ($urlValue !== '' && filter_var($urlValue, FILTER_VALIDATE_URL) === false) {
                throw new RuntimeException($t('invalid_url'));
            }
        }

        $currentStmt = db()->prepare('SELECT auth_user_id, callsign, email, photo_path, avatar_path FROM members WHERE id = ? LIMIT 1');
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

        $allowedVisibilities = array_keys($visibilityOptions);
        $visibilityPayload = [];
        foreach (array_keys($visibilityFields) as $field) {
            $value = (string) ($_POST[$field] ?? 'members');
            $visibilityPayload[$field] = in_array($value, $allowedVisibilities, true) ? $value : 'members';
        }

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
             full_name = ?,
             email = ?,
             phone = ?,
             qth = ?,
             locator = ?,
             bio = ?,
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
             station_equipment = ?,
             antennas = ?,
             max_power = ?,
             favourite_bands = ?,
             favourite_modes = ?,
             interests = ?,
             visibility_photo = ?,
             visibility_full_name = ?,
             visibility_email = ?,
             visibility_phone = ?,
             visibility_qth = ?,
             visibility_licence_class = ?,
             visibility_favourite_bands = ?,
             visibility_station = ?
         WHERE id = ?'
        );
        $stmt->execute([
            $callsign,
            $fullName,
            $email,
            $phone !== '' ? $phone : null,
            $qth !== '' ? $qth : null,
            $locator !== '' ? $locator : null,
            $bio !== '' ? $bio : null,
            $newPhotoPath !== '' ? $newPhotoPath : null,
            $newAvatarPath !== '' ? $newAvatarPath : null,
            $licenceClass !== '' ? $licenceClass : null,
            $operatorSince !== '' ? $operatorSince : null,
            $cqZone !== '' ? $cqZone : null,
            $ituZone !== '' ? $ituZone : null,
            $qslVia !== '' ? $qslVia : null,
            $lotwUsername !== '' ? $lotwUsername : null,
            $eqslUsername !== '' ? $eqslUsername : null,
            $qrzUrl !== '' ? $qrzUrl : null,
            $website !== '' ? $website : null,
            $stationEquipment !== '' ? $stationEquipment : null,
            $antennas !== '' ? $antennas : null,
            $maxPower !== '' ? $maxPower : null,
            $favouriteBands !== '' ? $favouriteBands : null,
            $favouriteModes !== '' ? $favouriteModes : null,
            $interests !== '' ? $interests : null,
            $visibilityPayload['visibility_photo'],
            $visibilityPayload['visibility_full_name'],
            $visibilityPayload['visibility_email'],
            $visibilityPayload['visibility_phone'],
            $visibilityPayload['visibility_qth'],
            $visibilityPayload['visibility_licence_class'],
            $visibilityPayload['visibility_favourite_bands'],
            $visibilityPayload['visibility_station'],
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

$stmt = db()->prepare(
    'SELECT callsign, full_name, email, phone, qth, locator, bio, licence_class, operator_since, cq_zone, itu_zone,
            qsl_via, lotw_username, eqsl_username, qrz_url, website, station_equipment, antennas, max_power,
            favourite_bands, favourite_modes, interests, photo_path, avatar_path,
            visibility_photo, visibility_full_name, visibility_email, visibility_phone, visibility_qth, visibility_licence_class, visibility_favourite_bands, visibility_station
     FROM members
     WHERE id = ? LIMIT 1'
);
$stmt->execute([$memberId]);
$member = $stmt->fetch() ?: [];

ob_start();
?>
<div class="stack">
<div class="card">
    <h1><?= e($t('title')) ?></h1>
    <?php $avatarSrc = member_avatar_src($member); ?>
    <p><img src="<?= e($avatarSrc) ?>" alt="<?= e($t('avatar_alt')) ?>" style="max-width:180px;border-radius:12px;"></p>
    <p><strong><?= e($t('callsign')) ?> :</strong> <?= e((string) ($member['callsign'] ?? '')) ?></p>
    <p><strong><?= e($t('name')) ?> :</strong> <?= e((string) ($member['full_name'] ?? '')) ?></p>
    <p><strong><?= e($t('email')) ?> :</strong> <?= e((string) ($member['email'] ?? '')) ?></p>
</div>

<section class="card">
    <h2><?= e($t('profile_settings')) ?></h2>
    <form method="post" class="stack" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <fieldset class="profile-fieldset">
            <legend><?= e($t('identity_section')) ?></legend>
            <div class="profile-form-grid">
                <label><?= e($t('callsign')) ?><input type="text" name="callsign" maxlength="32" required value="<?= e((string) ($member['callsign'] ?? '')) ?>"></label>
                <label><?= e($t('full_name')) ?><input type="text" name="full_name" maxlength="190" required value="<?= e((string) ($member['full_name'] ?? '')) ?>"></label>
                <label><?= e($t('email')) ?><input type="email" name="email" maxlength="190" required value="<?= e((string) ($member['email'] ?? '')) ?>"></label>
                <label><?= e($t('phone')) ?><input type="tel" name="phone" maxlength="64" value="<?= e((string) ($member['phone'] ?? '')) ?>" autocomplete="tel"></label>
                <label><?= e($t('qth')) ?><input type="text" name="qth" maxlength="190" value="<?= e((string) ($member['qth'] ?? '')) ?>"></label>
                <label><?= e($t('locator')) ?><input type="text" name="locator" maxlength="6" value="<?= e((string) ($member['locator'] ?? '')) ?>"></label>
                <label class="profile-form-wide"><?= e($t('bio')) ?><textarea name="bio" rows="4" maxlength="4000"><?= e((string) ($member['bio'] ?? '')) ?></textarea></label>
            </div>
        </fieldset>

        <fieldset class="profile-fieldset">
            <legend><?= e($t('radio_section')) ?></legend>
            <div class="profile-form-grid">
                <label><?= e($t('licence')) ?><input type="text" name="licence_class" maxlength="64" value="<?= e((string) ($member['licence_class'] ?? '')) ?>"></label>
                <label><?= e($t('operator_since')) ?><input type="text" name="operator_since" maxlength="32" value="<?= e((string) ($member['operator_since'] ?? '')) ?>"></label>
                <label><?= e($t('cq_zone')) ?><input type="text" name="cq_zone" maxlength="16" value="<?= e((string) ($member['cq_zone'] ?? '')) ?>"></label>
                <label><?= e($t('itu_zone')) ?><input type="text" name="itu_zone" maxlength="16" value="<?= e((string) ($member['itu_zone'] ?? '')) ?>"></label>
                <label><?= e($t('qsl_via')) ?><input type="text" name="qsl_via" maxlength="190" value="<?= e((string) ($member['qsl_via'] ?? '')) ?>"></label>
                <label><?= e($t('lotw_username')) ?><input type="text" name="lotw_username" maxlength="190" value="<?= e((string) ($member['lotw_username'] ?? '')) ?>"></label>
                <label><?= e($t('eqsl_username')) ?><input type="text" name="eqsl_username" maxlength="190" value="<?= e((string) ($member['eqsl_username'] ?? '')) ?>"></label>
                <label><?= e($t('qrz_url')) ?><input type="url" name="qrz_url" maxlength="255" value="<?= e((string) ($member['qrz_url'] ?? '')) ?>"></label>
                <label><?= e($t('website')) ?><input type="url" name="website" maxlength="255" value="<?= e((string) ($member['website'] ?? '')) ?>"></label>
                <label><?= e($t('max_power')) ?><input type="text" name="max_power" maxlength="64" value="<?= e((string) ($member['max_power'] ?? '')) ?>"></label>
                <label><?= e($t('bands')) ?><input type="text" name="favourite_bands" maxlength="190" value="<?= e((string) ($member['favourite_bands'] ?? '')) ?>"></label>
                <label><?= e($t('favourite_modes')) ?><input type="text" name="favourite_modes" maxlength="190" value="<?= e((string) ($member['favourite_modes'] ?? '')) ?>"></label>
                <label class="profile-form-wide"><?= e($t('station')) ?><textarea name="station_equipment" rows="4" maxlength="4000"><?= e((string) ($member['station_equipment'] ?? '')) ?></textarea></label>
                <label class="profile-form-wide"><?= e($t('antennas')) ?><textarea name="antennas" rows="3" maxlength="4000"><?= e((string) ($member['antennas'] ?? '')) ?></textarea></label>
                <label class="profile-form-wide"><?= e($t('interests')) ?><textarea name="interests" rows="3" maxlength="4000"><?= e((string) ($member['interests'] ?? '')) ?></textarea></label>
            </div>
        </fieldset>

        <fieldset class="profile-fieldset">
            <legend><?= e($t('photo_section')) ?></legend>
        <label>
            <?= e($t('change_photo')) ?>
            <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <small class="help"><?= e($t('photo_help')) ?></small>
        </label>
        </fieldset>

        <fieldset class="profile-fieldset" id="privacy">
            <legend><?= e($t('directory_visibility')) ?></legend>
            <p class="help"><?= e($t('visibility_help')) ?></p>

        <div class="profile-visibility-grid">
            <?php foreach ($visibilityOptions as $visibilityValue => $visibilityLabel): ?>
                <section class="profile-visibility-panel">
                    <h3><?= e($visibilityLabel) ?></h3>
                    <div class="profile-visibility-options">
                        <?php foreach ($visibilityFields as $fieldName => $fieldMeta): ?>
                            <?php $currentValue = (string) ($member[$fieldName] ?? (string) $fieldMeta['default']); ?>
                            <label class="profile-visibility-option">
                                <input type="radio" name="<?= e($fieldName) ?>" value="<?= e($visibilityValue) ?>" <?= $currentValue === $visibilityValue ? 'checked' : '' ?>>
                                <span><?= e((string) $fieldMeta['label']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        </fieldset>

        <button type="submit" class="button"><?= e($t('save')) ?></button>
    </form>
</section>
</div>
<?php

echo render_layout((string) ob_get_clean(), $t('title'));
