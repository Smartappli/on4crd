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
        $qth = trim((string) ($_POST['qth'] ?? ''));
        $licenceClass = trim((string) ($_POST['licence_class'] ?? ''));
        $favouriteBands = trim((string) ($_POST['favourite_bands'] ?? ''));
        $stationEquipment = trim((string) ($_POST['station_equipment'] ?? ''));

        if ($callsign === '' || $firstName === '' || $lastName === '' || $email === '' || $password === '') {
            throw new RuntimeException($t('required'));
        }

        $authClient = auth();
        if ($authClient === null) {
            throw new RuntimeException($t('auth_unavailable'));
        }

        try {
            $userId = $authClient->registerWithUniqueUsername($email, $password, $callsign);
        } catch (\Delight\Auth\InvalidEmailException|\Delight\Auth\InvalidPasswordException|\Delight\Auth\InvalidUsernameException $exception) {
            throw new RuntimeException($t('invalid_data'));
        } catch (\Delight\Auth\UserAlreadyExistsException|\Delight\Auth\DuplicateUsernameException $exception) {
            throw new RuntimeException($t('already_exists'));
        } catch (\Delight\Auth\TooManyRequestsException $exception) {
            throw new RuntimeException($t('too_many'));
        }

        db()->prepare(
            'INSERT INTO members (
                 auth_user_id, callsign, first_name, last_name, full_name, email, password_hash,
                 country, phone, qth, licence_class, favourite_bands, station_equipment,
                 visibility_full_name, visibility_email, visibility_country, visibility_phone, visibility_qth,
                 visibility_licence_class, visibility_favourite_bands, visibility_station,
                 is_active
             )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "members", "members", "members", "private", "members", "members", "members", "members", 1)
             ON DUPLICATE KEY UPDATE
                 callsign = VALUES(callsign),
                 first_name = VALUES(first_name),
                 last_name = VALUES(last_name),
                 full_name = VALUES(full_name),
                 email = VALUES(email),
                 password_hash = VALUES(password_hash),
                 country = VALUES(country),
                 phone = VALUES(phone),
                 qth = VALUES(qth),
                 licence_class = VALUES(licence_class),
                 favourite_bands = VALUES(favourite_bands),
                 station_equipment = VALUES(station_equipment),
                 visibility_full_name = VALUES(visibility_full_name),
                 visibility_email = VALUES(visibility_email),
                 visibility_country = VALUES(visibility_country),
                 visibility_phone = VALUES(visibility_phone),
                 visibility_qth = VALUES(visibility_qth),
                 visibility_licence_class = VALUES(visibility_licence_class),
                 visibility_favourite_bands = VALUES(visibility_favourite_bands),
                 visibility_station = VALUES(visibility_station),
                 is_active = 1'
        )->execute([
            (int) $userId,
            $callsign,
            $firstName,
            $lastName,
            $fullName,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $country !== '' ? $country : null,
            $phone !== '' ? $phone : null,
            $qth !== '' ? $qth : null,
            $licenceClass !== '' ? $licenceClass : null,
            $favouriteBands !== '' ? $favouriteBands : null,
            $stationEquipment !== '' ? $stationEquipment : null,
        ]);

        if (in_array($callsign, configured_administrator_callsigns(), true)) {
            ensure_configured_administrator_roles([$callsign]);
        }

        $authClient->loginWithUsername($callsign, $password);
        session_regenerate_id(true);
        $_SESSION['member_id'] = (int) $authClient->getUserId();

        set_flash('success', $t('ok_created'));
        redirect(module_enabled('dashboard') ? 'dashboard' : 'home');
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        redirect('register');
    }
}

$content = '<div class="card narrow login-card register-card"><h1>' . e($t('title')) . '</h1>'
    . '<form method="post" class="register-form"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<label>' . e($t('callsign')) . '<input type="text" name="callsign" maxlength="32" required></label>'
    . '<label>' . e($t('first_name')) . '<input type="text" name="first_name" maxlength="95" required></label>'
    . '<label>' . e($t('last_name')) . '<input type="text" name="last_name" maxlength="95" required></label>'
    . '<label>' . e($t('email')) . '<input type="email" name="email" maxlength="190" required></label>'
    . '<label>' . e($t('phone')) . '<input type="tel" name="phone" maxlength="64" autocomplete="tel"></label>'
    . '<label>' . e($t('country')) . '<select name="country" class="country-select">' . member_country_select_options_html('Belgique') . '</select></label>'
    . '<label>' . e($t('qth')) . '<input type="text" name="qth" maxlength="190" autocomplete="address-level2"></label>'
    . '<label>' . e($t('licence_class')) . '<select name="licence_class"><option value="Aucune">Aucune</option><option value="ONL">ONL</option><option value="ON3">ON3</option><option value="ON2">ON2</option><option value="HAREC">HAREC</option><option value="Autre">Autre</option></select></label>'
    . '<label class="register-form-full">' . e($t('station_equipment')) . '<textarea name="station_equipment" rows="3" maxlength="2000"></textarea></label>'
    . '<label>' . e($t('favourite_bands')) . '<input type="text" name="favourite_bands" maxlength="190" placeholder="' . e($t('favourite_bands_placeholder')) . '"></label>'
    . '<label>' . e($t('password')) . '<input type="password" name="password" minlength="8" required></label>'
    . '<p class="help register-form-full">' . e($t('directory_help')) . '</p>'
    . '<div class="register-form-full"><button class="button">' . e($t('submit')) . '</button></div></form>'
    . '<p>' . e($t('already_registered')) . ' <a href="' . e(route_url('login')) . '">' . e($t('login')) . '</a></p>'
    . '</div>';

echo render_layout($content, $t('layout_title'));
