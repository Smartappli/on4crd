<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = require __DIR__ . '/../app/i18n/register.php';
$i18n = i18n_expand_supported_locales($i18n);
$t = static function (string $key) use ($locale, $i18n): string {
    return (string) (($i18n[$locale] ?? $i18n['fr'])[$key] ?? $key);
};

if (current_user() !== null) {
    redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $fullName = member_full_name_from_parts($firstName, $lastName);
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
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
        $ubaMemberNumber = $isUbaMember === 1 ? trim((string) ($_POST['uba_member_number'] ?? '')) : '';
        $antennas = trim((string) ($_POST['antennas'] ?? ''));
        $favouriteBands = member_profile_normalize_choice_post($_POST['favourite_bands'] ?? [], member_profile_favourite_band_choices());
        $favouriteModes = member_profile_normalize_choice_post($_POST['favourite_modes'] ?? [], member_profile_favourite_mode_choices());
        $interests = trim((string) ($_POST['interests'] ?? ''));
        $stationEquipment = trim((string) ($_POST['station_equipment'] ?? ''));

        if ($callsign === '' || $firstName === '' || $lastName === '' || $password === '') {
            throw new RuntimeException($t('required'));
        }
        if (mb_strlen($callsign) > 32 || mb_strlen($firstName) > 95 || mb_strlen($lastName) > 95 || mb_strlen($fullName) > 190 || mb_strlen($email) > 190 || mb_strlen($address) > 255 || mb_strlen($postalCode) > 32) {
            throw new RuntimeException($t('invalid_data'));
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException($t('invalid_data'));
        }
        if ($locator !== '' && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $locator) !== 1) {
            throw new RuntimeException($t('invalid_data'));
        }
        if ($website !== '' && filter_var($website, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException($t('invalid_data'));
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
            throw new RuntimeException($t('invalid_data'));
        }
        $qrzUrl = member_qrz_url_for_profile_save($callsign);
        $lotwUsername = (string) member_lotw_username_for_profile_save($callsign, $lotwUsername);

        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException($t('auth_unavailable'));
        }

        try {
            $authEmail = $email !== '' ? $email : strtolower($callsign) . '@local.invalid';
            $userId = $authClient->registerWithUniqueUsername($authEmail, $password, $callsign);
        } catch (\Delight\Auth\InvalidEmailException|\Delight\Auth\InvalidPasswordException|\Delight\Auth\InvalidUsernameException $exception) {
            throw new RuntimeException($t('invalid_data'));
        } catch (\Delight\Auth\UserAlreadyExistsException|\Delight\Auth\DuplicateUsernameException $exception) {
            throw new RuntimeException($t('already_exists'));
        } catch (\Delight\Auth\TooManyRequestsException $exception) {
            throw new RuntimeException($t('too_many'));
        }

        db()->prepare(
            'INSERT INTO members (
                 auth_user_id, callsign, first_name, last_name, full_name, email, password_hash, password_change_required,
                 country, address, postal_code, phone, qth, locator, licence_class, operator_since,
                 cq_zone, itu_zone, qsl_via, lotw_username, eqsl_username, qrz_url, website,
                 is_uba_member, uba_member_number, favourite_bands, favourite_modes, station_equipment,
                 antennas, interests,
                 visibility_first_name, visibility_last_name, visibility_full_name, visibility_email, visibility_country,
                 visibility_address, visibility_postal_code, visibility_phone, visibility_qth, visibility_locator,
                 visibility_licence_class, visibility_operator_since, visibility_qsl, visibility_qrz, visibility_uba, visibility_favourite_bands, visibility_favourite_modes, visibility_station, visibility_antennas, visibility_interests,
                 is_active
             )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "members", "private", "private", "members", "members", "private", "private", "private", "members", "members", "members", "members", "members", "members", "members", "members", "members", "members", "members", "members", 1)
             ON DUPLICATE KEY UPDATE
                 callsign = VALUES(callsign),
                 first_name = VALUES(first_name),
                 last_name = VALUES(last_name),
                 full_name = VALUES(full_name),
                 email = VALUES(email),
                 password_hash = VALUES(password_hash),
                 password_change_required = VALUES(password_change_required),
                 country = VALUES(country),
                 address = VALUES(address),
                 postal_code = VALUES(postal_code),
                 phone = VALUES(phone),
                 qth = VALUES(qth),
                 locator = VALUES(locator),
                 licence_class = VALUES(licence_class),
                 operator_since = VALUES(operator_since),
                 cq_zone = VALUES(cq_zone),
                 itu_zone = VALUES(itu_zone),
                 qsl_via = VALUES(qsl_via),
                 lotw_username = VALUES(lotw_username),
                 eqsl_username = VALUES(eqsl_username),
                 qrz_url = VALUES(qrz_url),
                 website = VALUES(website),
                 is_uba_member = VALUES(is_uba_member),
                 uba_member_number = VALUES(uba_member_number),
                 favourite_bands = VALUES(favourite_bands),
                 favourite_modes = VALUES(favourite_modes),
                 station_equipment = VALUES(station_equipment),
                 antennas = VALUES(antennas),
                 interests = VALUES(interests),
                 visibility_first_name = VALUES(visibility_first_name),
                 visibility_last_name = VALUES(visibility_last_name),
                 visibility_full_name = VALUES(visibility_full_name),
                 visibility_email = VALUES(visibility_email),
                 visibility_country = VALUES(visibility_country),
                 visibility_address = VALUES(visibility_address),
                 visibility_postal_code = VALUES(visibility_postal_code),
                 visibility_phone = VALUES(visibility_phone),
                 visibility_qth = VALUES(visibility_qth),
                 visibility_locator = VALUES(visibility_locator),
                 visibility_licence_class = VALUES(visibility_licence_class),
                 visibility_operator_since = VALUES(visibility_operator_since),
                 visibility_qsl = VALUES(visibility_qsl),
                 visibility_qrz = VALUES(visibility_qrz),
                 visibility_uba = VALUES(visibility_uba),
                 visibility_favourite_bands = VALUES(visibility_favourite_bands),
                 visibility_favourite_modes = VALUES(visibility_favourite_modes),
                 visibility_station = VALUES(visibility_station),
                 visibility_antennas = VALUES(visibility_antennas),
                 visibility_interests = VALUES(visibility_interests),
                 is_active = 1'
        )->execute([
            (int) $userId,
            $callsign,
            $firstName,
            $lastName,
            $fullName,
            $email !== '' ? $email : null,
            password_hash($password, PASSWORD_DEFAULT),
            1,
            $country !== '' ? $country : null,
            $address !== '' ? $address : null,
            $postalCode !== '' ? $postalCode : null,
            $phone !== '' ? $phone : null,
            $qth !== '' ? $qth : null,
            $locator !== '' ? $locator : null,
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
            $favouriteBands !== '' ? $favouriteBands : null,
            $favouriteModes !== '' ? $favouriteModes : null,
            $stationEquipment !== '' ? $stationEquipment : null,
            $antennas !== '' ? $antennas : null,
            $interests !== '' ? $interests : null,
        ]);

        if (in_array($callsign, configured_administrator_callsigns(), true)) {
            ensure_configured_administrator_roles([$callsign]);
        }

        $authClient->loginWithUsername($callsign, $password);
        session_regenerate_id(true);
        $_SESSION['member_id'] = (int) $authClient->getUserId();

        set_flash('success', $t('ok_created'));
        redirect('change_password');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('register');
    }
}

$operatorSinceOptionsHtml = member_profile_operator_since_options_html('');
$favouriteBandsOptionsHtml = member_profile_checkbox_group_html('favourite_bands', member_profile_favourite_band_choices());
$favouriteModesOptionsHtml = member_profile_checkbox_group_html('favourite_modes', member_profile_favourite_mode_choices());

$content = '<div class="card narrow login-card register-card"><h1>' . e($t('title')) . '</h1>'
    . '<form method="post" class="register-form"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>' . e($t('callsign')) . '<input type="text" name="callsign" maxlength="32" required></label>'
    . '<label>' . e($t('last_name')) . '<input type="text" name="last_name" maxlength="95" required></label>'
    . '<label>' . e($t('first_name')) . '<input type="text" name="first_name" maxlength="95" required></label>'
    . '<label>' . e($t('email')) . '<input type="email" name="email" maxlength="190"></label>'
    . '<label>' . e($t('phone')) . '<input type="tel" name="phone" maxlength="64" autocomplete="tel"></label>'
    . '<label>' . e($t('country')) . '<select name="country" class="country-select">' . member_country_select_options_html('Belgique') . '</select></label>'
    . '<label>' . e($t('address')) . '<input type="text" name="address" maxlength="255" autocomplete="street-address"></label>'
    . '<label>' . e($t('postal_code')) . '<input type="text" name="postal_code" maxlength="32" autocomplete="postal-code"></label>'
    . '<label>' . e($t('qth')) . '<input type="text" name="qth" maxlength="190" autocomplete="address-level2"></label>'
    . '<label>' . e($t('grid')) . '<input type="text" name="locator" maxlength="6"></label>'
    . '<label>' . e($t('licence_class')) . '<select name="licence_class"><option value="Aucune">Aucune</option><option value="ONL">ONL</option><option value="ON3">ON3</option><option value="ON2">ON2</option><option value="HAREC">HAREC</option><option value="Autre">Autre</option></select></label>'
    . '<label>' . e($t('operator_since')) . '<select name="operator_since">' . $operatorSinceOptionsHtml . '</select></label>'
    . '<label>' . e($t('cq_zone')) . '<input type="text" name="cq_zone" maxlength="16"></label>'
    . '<label>' . e($t('itu_zone')) . '<input type="text" name="itu_zone" maxlength="16"></label>'
    . '<p class="help register-form-full">' . e($t('auto_radio_location_help')) . '</p>'
    . '<label>' . e($t('qsl_via')) . '<input type="text" name="qsl_via" maxlength="190"></label>'
    . '<label>' . e($t('eqsl_username')) . '<input type="text" name="eqsl_username" maxlength="190"></label>'
    . '<label>' . e($t('website')) . '<input type="url" name="website" maxlength="255"></label>'
    . '<label class="profile-checkbox"><input type="checkbox" name="is_uba_member" value="1" data-uba-member-toggle> <span>' . e($t('uba_member')) . '</span></label>'
    . '<label>' . e($t('uba_member_number')) . '<input type="text" name="uba_member_number" maxlength="64" data-uba-member-number disabled></label>'
    . '<label class="register-form-full">' . e($t('station_equipment')) . '<textarea name="station_equipment" rows="3" maxlength="4000"></textarea></label>'
    . '<label class="register-form-full">' . e($t('antennas')) . '<textarea name="antennas" rows="3" maxlength="4000"></textarea></label>'
    . '<fieldset class="profile-choice-fieldset register-form-full"><legend>' . e($t('favourite_bands')) . '</legend>' . $favouriteBandsOptionsHtml . '</fieldset>'
    . '<fieldset class="profile-choice-fieldset register-form-full"><legend>' . e($t('favourite_modes')) . '</legend>' . $favouriteModesOptionsHtml . '</fieldset>'
    . '<label class="register-form-full">' . e($t('interests')) . '<textarea name="interests" rows="3" maxlength="4000"></textarea></label>'
    . '<label>' . e($t('password')) . '<input type="password" name="password" minlength="8" required></label>'
    . '<p class="help register-form-full">' . e($t('directory_help')) . '</p>'
    . '<div class="register-form-full"><button class="button">' . e($t('submit')) . '</button></div></form>'
    . '<p>' . e($t('already_registered')) . ' <a href="' . e(route_url('login')) . '">' . e($t('login')) . '</a></p>'
    . '</div>';

echo render_layout($content, $t('layout_title'));
