<?php
declare(strict_types=1);

require_once __DIR__ . '/widget_radio_helpers.php';

if (!function_exists('member_country_options')) {
/**
 * @return array<string, string>
 */
function member_country_options(): array
{
    return [
        'BE' => 'Belgique',
        'FR' => 'France',
        'NL' => 'Pays-Bas',
        'LU' => 'Luxembourg',
        'DE' => 'Allemagne',
        'GB' => 'Royaume-Uni',
        'CH' => 'Suisse',
        'IT' => 'Italie',
        'ES' => 'Espagne',
        'PT' => 'Portugal',
        'IE' => 'Irlande',
        'DK' => 'Danemark',
        'SE' => 'Suede',
        'NO' => 'Norvege',
        'FI' => 'Finlande',
        'AT' => 'Autriche',
        'PL' => 'Pologne',
        'CZ' => 'Tchequie',
        'SK' => 'Slovaquie',
        'HU' => 'Hongrie',
        'RO' => 'Roumanie',
        'BG' => 'Bulgarie',
        'GR' => 'Grece',
        'US' => 'Etats-Unis',
        'CA' => 'Canada',
        'MA' => 'Maroc',
        'DZ' => 'Algerie',
        'TN' => 'Tunisie',
    ];
}
}

if (!function_exists('member_country_key')) {
function member_country_key(string $country): string
{
    $country = strtolower(trim($country));
    $country = strtr($country, [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a',
        'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
    ]);
    $country = preg_replace('/[^a-z0-9]+/', '-', $country) ?? $country;

    return trim($country, '-');
}
}

if (!function_exists('member_country_code_for')) {
function member_country_code_for(string $country): string
{
    $country = trim($country);
    if (preg_match('/^[A-Za-z]{2}$/', $country) === 1) {
        return strtoupper($country);
    }

    $options = member_country_options();
    $key = member_country_key($country);
    foreach ($options as $code => $label) {
        if ($key === member_country_key($label)) {
            return $code;
        }
    }

    $aliases = [
        'belgium' => 'BE',
        'belgie' => 'BE',
        'belgien' => 'BE',
        'netherlands' => 'NL',
        'nederland' => 'NL',
        'holland' => 'NL',
        'pays-bas' => 'NL',
        'germany' => 'DE',
        'deutschland' => 'DE',
        'united-kingdom' => 'GB',
        'uk' => 'GB',
        'great-britain' => 'GB',
        'switzerland' => 'CH',
        'spain' => 'ES',
        'italy' => 'IT',
        'ireland' => 'IE',
        'united-states' => 'US',
        'usa' => 'US',
        'canada' => 'CA',
    ];

    return (string) ($aliases[$key] ?? '');
}
}

if (!function_exists('member_country_code_to_flag')) {
function member_country_code_to_flag(string $countryCode): string
{
    $countryCode = strtoupper(trim($countryCode));
    if (preg_match('/^[A-Z]{2}$/', $countryCode) !== 1) {
        return '';
    }

    $flag = '';
    for ($i = 0; $i < 2; $i++) {
        $flag .= html_entity_decode('&#' . (127397 + ord($countryCode[$i])) . ';', ENT_NOQUOTES, 'UTF-8');
    }

    return $flag;
}
}

if (!function_exists('member_country_flag')) {
function member_country_flag(string $country): string
{
    return member_country_code_to_flag(member_country_code_for($country));
}
}

if (!function_exists('member_country_html')) {
function member_country_html(string $country): string
{
    $country = trim($country);
    if ($country === '') {
        return '';
    }

    $flag = member_country_flag($country);
    if ($flag === '') {
        return e($country);
    }

    return '<span class="country-with-flag"><span class="country-flag" aria-hidden="true">' . e($flag) . '</span><span>' . e($country) . '</span></span>';
}
}

if (!function_exists('member_country_select_options_html')) {
function member_country_select_options_html(string $currentCountry = ''): string
{
    $currentCountry = trim($currentCountry);
    $selectedCode = member_country_code_for($currentCountry);
    $currentKey = member_country_key($currentCountry);
    $html = '<option value=""></option>';
    $options = member_country_options();

    if ($currentCountry !== '' && $selectedCode === '' && $currentKey !== '') {
        $html .= '<option value="' . e($currentCountry) . '" selected>' . member_country_flag($currentCountry) . ' ' . e($currentCountry) . '</option>';
    }

    foreach ($options as $code => $label) {
        $selected = ($selectedCode !== '' && $selectedCode === $code) || ($currentKey !== '' && $currentKey === member_country_key($label));
        $text = trim(member_country_code_to_flag($code) . ' ' . $label);
        $html .= '<option value="' . e($label) . '"' . ($selected ? ' selected' : '') . '>' . e($text) . '</option>';
    }

    return $html;
}
}

if (!function_exists('member_profile_postal_address_is_complete')) {
function member_profile_postal_address_is_complete(string $country, string $address, string $postalCode, string $qth): bool
{
    return trim($country) !== '' && trim($address) !== '' && trim($postalCode) !== '' && trim($qth) !== '';
}
}

if (!function_exists('member_profile_postal_address_query')) {
function member_profile_postal_address_query(string $country, string $address, string $postalCode, string $qth): string
{
    $parts = array_filter([
        trim($address),
        trim($postalCode),
        trim($qth),
        trim($country),
    ], static fn(string $part): bool => $part !== '');

    return implode(', ', $parts);
}
}

if (!function_exists('member_profile_geocode_postal_address')) {
/**
 * @return array{lat:float, lon:float, display_name:string, country_code:string}|null
 */
function member_profile_geocode_postal_address(string $country, string $address, string $postalCode, string $qth): ?array
{
    if (!member_profile_postal_address_is_complete($country, $address, $postalCode, $qth)) {
        return null;
    }

    $query = member_profile_postal_address_query($country, $address, $postalCode, $qth);
    if ($query === '') {
        return null;
    }

    $cacheKey = 'profile_geocode_' . sha1(mb_strtolower($query));

    return cache_remember($cacheKey, 30 * 24 * 60 * 60, static function () use ($query, $country): ?array {
        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=1&q=' . rawurlencode($query);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => "Accept: application/json\r\nUser-Agent: ON4CRD-Profile/1.0\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false || trim($response) === '') {
            return null;
        }

        $rows = json_decode($response, true);
        if (!is_array($rows) || $rows === []) {
            return null;
        }

        $row = $rows[0] ?? null;
        if (!is_array($row) || !isset($row['lat'], $row['lon']) || !is_numeric($row['lat']) || !is_numeric($row['lon'])) {
            return null;
        }

        $lat = (float) $row['lat'];
        $lon = (float) $row['lon'];
        if (!is_finite($lat) || !is_finite($lon) || $lat < -90.0 || $lat > 90.0 || $lon < -180.0 || $lon > 180.0) {
            return null;
        }

        $addressDetails = isset($row['address']) && is_array($row['address']) ? $row['address'] : [];
        $countryCode = strtoupper(trim((string) ($addressDetails['country_code'] ?? '')));
        if ($countryCode === '') {
            $countryCode = member_country_code_for($country);
        }

        return [
            'lat' => $lat,
            'lon' => $lon,
            'display_name' => trim((string) ($row['display_name'] ?? $query)),
            'country_code' => $countryCode,
        ];
    });
}
}

if (!function_exists('member_profile_zone_pair')) {
/**
 * @return array{cq_zone:string, itu_zone:string}
 */
function member_profile_zone_pair(string $cqZone, string $ituZone): array
{
    return ['cq_zone' => $cqZone, 'itu_zone' => $ituZone];
}
}

if (!function_exists('member_profile_radio_zones_for_us')) {
/**
 * @return array{cq_zone:string, itu_zone:string}
 */
function member_profile_radio_zones_for_us(float $latitude, float $longitude): array
{
    if ($latitude >= 51.0 && $longitude <= -130.0) {
        return member_profile_zone_pair('1', '1');
    }
    if ($latitude >= 18.0 && $latitude <= 23.0 && $longitude >= -162.0 && $longitude <= -154.0) {
        return member_profile_zone_pair('31', '61');
    }
    if ($longitude < -110.0) {
        return member_profile_zone_pair('3', '6');
    }
    if ($longitude < -90.0) {
        return member_profile_zone_pair('4', '7');
    }

    return member_profile_zone_pair('5', '8');
}
}

if (!function_exists('member_profile_radio_zones_for_canada')) {
/**
 * @return array{cq_zone:string, itu_zone:string}
 */
function member_profile_radio_zones_for_canada(float $latitude, float $longitude): array
{
    if ($longitude >= -70.0 && $latitude >= 50.0) {
        return member_profile_zone_pair('2', '9');
    }
    if ($longitude >= -70.0) {
        return member_profile_zone_pair('5', '9');
    }
    if ($longitude >= -90.0) {
        return member_profile_zone_pair('4', '4');
    }
    if ($longitude >= -110.0) {
        return member_profile_zone_pair('4', '3');
    }
    if ($longitude >= -141.5) {
        return member_profile_zone_pair('3', '2');
    }

    return member_profile_zone_pair('1', '2');
}
}

if (!function_exists('member_profile_radio_zones_for_coordinates')) {
/**
 * @return array{cq_zone:string, itu_zone:string}
 */
function member_profile_radio_zones_for_coordinates(float $latitude, float $longitude, string $country): array
{
    $countryCode = member_country_code_for($country);
    if ($countryCode === '' && preg_match('/^[A-Za-z]{2}$/', trim($country)) === 1) {
        $countryCode = strtoupper(trim($country));
    }

    if ($countryCode === 'US') {
        return member_profile_radio_zones_for_us($latitude, $longitude);
    }
    if ($countryCode === 'CA') {
        return member_profile_radio_zones_for_canada($latitude, $longitude);
    }
    if ($countryCode === 'FR') {
        if ($latitude >= 41.0 && $latitude <= 43.2 && $longitude >= 8.4 && $longitude <= 9.8) {
            return member_profile_zone_pair('15', '28');
        }

        return member_profile_zone_pair('14', '27');
    }
    if ($countryCode === 'ES') {
        if ($latitude >= 27.0 && $latitude <= 30.5 && $longitude >= -19.0 && $longitude <= -13.0) {
            return member_profile_zone_pair('33', '36');
        }
        if ($latitude >= 35.0 && $latitude <= 36.5 && $longitude >= -6.5 && $longitude <= -1.0) {
            return member_profile_zone_pair('33', '37');
        }

        return member_profile_zone_pair('14', '37');
    }
    if ($countryCode === 'PT') {
        if ($latitude >= 30.0 && $latitude <= 34.0 && $longitude >= -18.0 && $longitude <= -15.0) {
            return member_profile_zone_pair('33', '36');
        }
        if ($latitude >= 36.0 && $latitude <= 40.5 && $longitude >= -32.0 && $longitude <= -24.0) {
            return member_profile_zone_pair('14', '36');
        }

        return member_profile_zone_pair('14', '37');
    }
    if ($countryCode === 'IT') {
        if ($latitude < 36.8 && $longitude >= 11.5 && $longitude <= 13.5) {
            return member_profile_zone_pair('33', '37');
        }

        return member_profile_zone_pair('15', '28');
    }

    $singleCountryZones = [
        'BE' => ['14', '27'],
        'NL' => ['14', '27'],
        'LU' => ['14', '27'],
        'DE' => ['14', '28'],
        'GB' => ['14', '27'],
        'CH' => ['14', '28'],
        'IE' => ['14', '27'],
        'DK' => ['14', '18'],
        'SE' => ['14', '18'],
        'NO' => ['14', '18'],
        'FI' => ['15', '18'],
        'AT' => ['15', '28'],
        'PL' => ['15', '28'],
        'CZ' => ['15', '28'],
        'SK' => ['15', '28'],
        'HU' => ['15', '28'],
        'RO' => ['20', '28'],
        'BG' => ['20', '28'],
        'GR' => ['20', '28'],
        'MA' => ['33', '37'],
        'DZ' => ['33', '37'],
        'TN' => ['33', '37'],
    ];

    if (!isset($singleCountryZones[$countryCode])) {
        return member_profile_zone_pair('', '');
    }

    return member_profile_zone_pair($singleCountryZones[$countryCode][0], $singleCountryZones[$countryCode][1]);
}
}

if (!function_exists('member_profile_radio_location_from_address')) {
/**
 * @return array{locator:string, cq_zone:string, itu_zone:string, lat:float, lon:float, display_name:string}|null
 */
function member_profile_radio_location_from_address(string $country, string $address, string $postalCode, string $qth): ?array
{
    $geocoded = member_profile_geocode_postal_address($country, $address, $postalCode, $qth);
    if ($geocoded === null) {
        return null;
    }

    $locator = coordinates_to_maidenhead((float) $geocoded['lat'], (float) $geocoded['lon'], 6);
    if ($locator === null || $locator === '') {
        return null;
    }

    $zoneCountry = (string) ($geocoded['country_code'] !== '' ? $geocoded['country_code'] : $country);
    $zones = member_profile_radio_zones_for_coordinates((float) $geocoded['lat'], (float) $geocoded['lon'], $zoneCountry);

    return [
        'locator' => $locator,
        'cq_zone' => $zones['cq_zone'],
        'itu_zone' => $zones['itu_zone'],
        'lat' => (float) $geocoded['lat'],
        'lon' => (float) $geocoded['lon'],
        'display_name' => (string) $geocoded['display_name'],
    ];
}
}

if (!function_exists('member_profile_visibility_allows')) {
function member_profile_visibility_allows(string $viewer, string $visibility): bool
{
    if ($viewer === 'private') {
        return true;
    }
    if ($viewer === 'members') {
        return in_array($visibility, ['public', 'members'], true);
    }

    return $visibility === 'public';
}
}

if (!function_exists('member_profile_allowed_visibility_levels')) {
/**
 * @param array<string, mixed>|null $viewer
 * @return list<string>
 */
function member_profile_allowed_visibility_levels(?array $viewer): array
{
    if ($viewer === null) {
        return ['public'];
    }

    $levels = ['public', 'members'];
    if ((int) ($viewer['is_committee'] ?? 0) === 1) {
        $levels[] = 'private';
    }

    return $levels;
}
}

if (!function_exists('member_profile_operator_since_options_html')) {
function member_profile_operator_since_options_html(string $currentValue = ''): string
{
    $currentValue = trim($currentValue);
    $html = '<option value=""></option>';
    $currentYear = (int) date('Y');

    if ($currentValue !== '' && preg_match('/^\d{4}$/', $currentValue) !== 1) {
        $html .= '<option value="' . e($currentValue) . '" selected>' . e($currentValue) . '</option>';
    }

    for ($year = $currentYear; $year >= 1900; $year--) {
        $yearValue = (string) $year;
        $selected = $currentValue === $yearValue || ($currentValue === '' && $year === $currentYear);
        $html .= '<option value="' . e($yearValue) . '"' . ($selected ? ' selected' : '') . '>' . e($yearValue) . '</option>';
    }

    return $html;
}
}

if (!function_exists('member_profile_favourite_band_choices')) {
/**
 * @return list<string>
 */
function member_profile_favourite_band_choices(): array
{
    return ['160m', '80m', '60m', '40m', '30m', '20m', '17m', '15m', '12m', '10m', '6m', '4m', '2m', '70cm', '23cm', '13cm', 'QO-100', 'Satellite'];
}
}

if (!function_exists('member_profile_favourite_mode_choices')) {
/**
 * @return list<string>
 */
function member_profile_favourite_mode_choices(): array
{
    return ['AM', 'FM', 'SSB', 'CW', 'FT8', 'FT4', 'RTTY', 'PSK31', 'JS8Call', 'SSTV', 'DMR', 'D-STAR', 'C4FM', 'EchoLink'];
}
}

if (!function_exists('member_profile_parse_choice_list')) {
/**
 * @return list<string>
 */
function member_profile_parse_choice_list(string $value): array
{
    $choices = [];
    foreach (preg_split('/\s*,\s*/', trim($value)) ?: [] as $choice) {
        $choice = trim($choice);
        if ($choice !== '' && !in_array($choice, $choices, true)) {
            $choices[] = $choice;
        }
    }

    return $choices;
}
}

if (!function_exists('member_profile_normalize_choice_post')) {
/**
 * @param mixed $value
 * @param list<string> $allowedChoices
 */
function member_profile_normalize_choice_post(mixed $value, array $allowedChoices): string
{
    $postedChoices = is_array($value) ? $value : member_profile_parse_choice_list((string) $value);
    $selectedChoices = [];
    foreach ($postedChoices as $choice) {
        $choice = trim((string) $choice);
        if (in_array($choice, $allowedChoices, true) && !in_array($choice, $selectedChoices, true)) {
            $selectedChoices[] = $choice;
        }
    }

    return implode(', ', $selectedChoices);
}
}

if (!function_exists('member_profile_checkbox_group_html')) {
/**
 * @param list<string> $choices
 */
function member_profile_checkbox_group_html(string $name, array $choices, string $currentValue = ''): string
{
    $selectedChoices = member_profile_parse_choice_list($currentValue);
    $html = '<div class="profile-choice-group">';
    foreach ($choices as $choice) {
        $isSelected = in_array($choice, $selectedChoices, true);
        $html .= '<label class="profile-choice"><input type="checkbox" name="' . e($name) . '[]" value="' . e($choice) . '"' . ($isSelected ? ' checked' : '') . '> <span>' . e($choice) . '</span></label>';
    }

    return $html . '</div>';
}
}

if (!function_exists('member_name_parts_from_full_name')) {
/**
 * @return array{first_name:string, last_name:string}
 */
function member_name_parts_from_full_name(string $fullName): array
{
    $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? $fullName);
    if ($fullName === '') {
        return ['first_name' => '', 'last_name' => ''];
    }

    $parts = preg_split('/\s+/', $fullName, 2) ?: [];

    return [
        'first_name' => trim((string) ($parts[0] ?? '')),
        'last_name' => trim((string) ($parts[1] ?? '')),
    ];
}
}

if (!function_exists('member_full_name_from_parts')) {
function member_full_name_from_parts(string $firstName, string $lastName): string
{
    return trim(preg_replace('/\s+/', ' ', trim($firstName) . ' ' . trim($lastName)) ?? '');
}
}

if (!function_exists('member_with_name_parts')) {
/**
 * @param array<string, mixed> $member
 * @return array<string, mixed>
 */
function member_with_name_parts(array $member): array
{
    $firstName = trim((string) ($member['first_name'] ?? ''));
    $lastName = trim((string) ($member['last_name'] ?? ''));
    if ($firstName !== '' || $lastName !== '') {
        $member['first_name'] = $firstName;
        $member['last_name'] = $lastName;

        return $member;
    }

    $parts = member_name_parts_from_full_name((string) ($member['full_name'] ?? ''));
    $member['first_name'] = $parts['first_name'];
    $member['last_name'] = $parts['last_name'];

    return $member;
}
}

if (!function_exists('member_profile_visibility_fields')) {
/**
 * @return array<string, array{label:string, default:string}>
 */
function member_profile_visibility_fields(callable $t): array
{
    return [
        'visibility_photo' => ['label' => (string) $t('photo'), 'default' => 'members'],
        'visibility_first_name' => ['label' => (string) $t('first_name'), 'default' => 'members'],
        'visibility_last_name' => ['label' => (string) $t('last_name'), 'default' => 'private'],
        'visibility_email' => ['label' => (string) $t('email'), 'default' => 'members'],
        'visibility_phone' => ['label' => (string) $t('phone'), 'default' => 'private'],
        'visibility_country' => ['label' => (string) $t('country'), 'default' => 'members'],
        'visibility_address' => ['label' => (string) $t('address'), 'default' => 'private'],
        'visibility_postal_code' => ['label' => (string) $t('postal_code'), 'default' => 'private'],
        'visibility_qth' => ['label' => (string) $t('qth'), 'default' => 'members'],
        'visibility_locator' => ['label' => (string) $t('grid'), 'default' => 'members'],
        'visibility_bio' => ['label' => (string) $t('bio'), 'default' => 'members'],
        'visibility_licence_class' => ['label' => (string) $t('licence'), 'default' => 'members'],
        'visibility_operator_since' => ['label' => (string) $t('operator_since'), 'default' => 'members'],
        'visibility_qsl' => ['label' => (string) $t('qsl_info'), 'default' => 'members'],
        'visibility_qrz' => ['label' => (string) $t('qrz_url'), 'default' => 'members'],
        'visibility_uba' => ['label' => (string) $t('uba_member'), 'default' => 'members'],
        'visibility_favourite_bands' => ['label' => (string) $t('bands'), 'default' => 'members'],
        'visibility_favourite_modes' => ['label' => (string) $t('favourite_modes'), 'default' => 'members'],
        'visibility_station' => ['label' => (string) $t('station'), 'default' => 'members'],
        'visibility_antennas' => ['label' => (string) $t('antennas'), 'default' => 'members'],
        'visibility_interests' => ['label' => (string) $t('interests'), 'default' => 'members'],
    ];
}
}

if (!function_exists('member_profile_preview_fields')) {
/**
 * @return array<string, array{label:string, visibility:string, type?:string}>
 */
function member_profile_preview_fields(callable $t): array
{
    return [
        'first_name' => ['label' => (string) $t('first_name'), 'visibility' => 'visibility_first_name'],
        'last_name' => ['label' => (string) $t('last_name'), 'visibility' => 'visibility_last_name'],
        'email' => ['label' => (string) $t('email'), 'visibility' => 'visibility_email'],
        'phone' => ['label' => (string) $t('phone'), 'visibility' => 'visibility_phone'],
        'country' => ['label' => (string) $t('country'), 'visibility' => 'visibility_country', 'type' => 'country'],
        'address' => ['label' => (string) $t('address'), 'visibility' => 'visibility_address'],
        'postal_code' => ['label' => (string) $t('postal_code'), 'visibility' => 'visibility_postal_code'],
        'qth' => ['label' => (string) $t('qth'), 'visibility' => 'visibility_qth'],
        'locator' => ['label' => (string) $t('grid'), 'visibility' => 'visibility_locator'],
        'bio' => ['label' => (string) $t('bio'), 'visibility' => 'visibility_bio'],
        'licence_class' => ['label' => (string) $t('licence'), 'visibility' => 'visibility_licence_class'],
        'operator_since' => ['label' => (string) $t('operator_since'), 'visibility' => 'visibility_operator_since'],
        'cq_zone' => ['label' => (string) $t('cq_zone'), 'visibility' => 'visibility_licence_class'],
        'itu_zone' => ['label' => (string) $t('itu_zone'), 'visibility' => 'visibility_licence_class'],
        'qsl_via' => ['label' => (string) $t('qsl_via'), 'visibility' => 'visibility_qsl'],
        'lotw_username' => ['label' => (string) $t('lotw_username'), 'visibility' => 'visibility_qsl'],
        'eqsl_username' => ['label' => (string) $t('eqsl_username'), 'visibility' => 'visibility_qsl'],
        'qrz_url' => ['label' => (string) $t('qrz_url'), 'visibility' => 'visibility_qrz', 'type' => 'url'],
        'website' => ['label' => (string) $t('website'), 'visibility' => 'visibility_qrz', 'type' => 'url'],
        'is_uba_member' => ['label' => (string) $t('uba_member'), 'visibility' => 'visibility_uba', 'type' => 'bool'],
        'uba_member_number' => ['label' => (string) $t('uba_member_number'), 'visibility' => 'visibility_uba'],
        'favourite_bands' => ['label' => (string) $t('bands'), 'visibility' => 'visibility_favourite_bands'],
        'favourite_modes' => ['label' => (string) $t('favourite_modes'), 'visibility' => 'visibility_favourite_modes'],
        'station_equipment' => ['label' => (string) $t('station'), 'visibility' => 'visibility_station'],
        'antennas' => ['label' => (string) $t('antennas'), 'visibility' => 'visibility_antennas'],
        'max_power' => ['label' => (string) $t('max_power'), 'visibility' => 'visibility_station'],
        'interests' => ['label' => (string) $t('interests'), 'visibility' => 'visibility_interests'],
    ];
}
}

if (!function_exists('member_profile_display_row')) {
/**
 * @param array<string, mixed> $member
 * @param array{label:string, visibility:string, type?:string} $fieldMeta
 * @return array{label:string, text:string, html:string}|null
 */
function member_profile_display_row(array $member, string $fieldName, array $fieldMeta): ?array
{
    $type = (string) ($fieldMeta['type'] ?? 'text');
    if ($type === 'bool') {
        if ((int) ($member[$fieldName] ?? 0) !== 1) {
            return null;
        }
        $text = 'Oui';
        return ['label' => (string) $fieldMeta['label'], 'text' => $text, 'html' => e($text)];
    }

    $text = trim((string) ($member[$fieldName] ?? ''));
    if ($text === '') {
        return null;
    }

    if ($type === 'country') {
        return ['label' => (string) $fieldMeta['label'], 'text' => $text, 'html' => member_country_html($text)];
    }

    if ($type === 'url') {
        $safeUrl = sanitize_href_attribute($text);
        if ($safeUrl === '') {
            return null;
        }
        return [
            'label' => (string) $fieldMeta['label'],
            'text' => $text,
            'html' => '<a href="' . e($safeUrl) . '" target="_blank" rel="noopener noreferrer">' . e($text) . '</a>',
        ];
    }

    return ['label' => (string) $fieldMeta['label'], 'text' => $text, 'html' => e($text)];
}
}

if (!function_exists('member_profile_preview_rows')) {
/**
 * @param array<string, mixed> $member
 * @return list<array{label:string, text:string, html:string, visibility_field:string, visible:bool}>
 */
function member_profile_preview_rows(array $member, string $viewer, callable $t, bool $includeHiddenRows = false): array
{
    $rows = [];
    $visibilityDefaults = member_profile_visibility_fields($t);
    foreach (member_profile_preview_fields($t) as $fieldName => $fieldMeta) {
        $row = member_profile_display_row($member, $fieldName, $fieldMeta);
        if ($row === null) {
            continue;
        }

        $visibilityField = (string) $fieldMeta['visibility'];
        $defaultVisibility = (string) ($visibilityDefaults[$visibilityField]['default'] ?? 'members');
        $visible = member_profile_visibility_allows($viewer, (string) ($member[$visibilityField] ?? $defaultVisibility));
        if (!$visible && !$includeHiddenRows) {
            continue;
        }

        $rows[] = [
            'label' => $row['label'],
            'text' => $row['text'],
            'html' => $row['html'],
            'visibility_field' => $visibilityField,
            'visible' => $visible,
        ];
    }

    return $rows;
}
}

if (!function_exists('member_profile_select_columns_sql')) {
function member_profile_select_columns_sql(): string
{
    $visibilityColumnSql = static function (string $column, string $default = 'members'): string {
        return table_has_column('members', $column) ? $column : "'" . $default . "' AS " . $column;
    };

    $visibilityColumns = [
        $visibilityColumnSql('visibility_photo'),
        $visibilityColumnSql('visibility_full_name', 'private'),
        $visibilityColumnSql('visibility_first_name'),
        $visibilityColumnSql('visibility_last_name', 'private'),
        $visibilityColumnSql('visibility_email'),
        $visibilityColumnSql('visibility_phone', 'private'),
        $visibilityColumnSql('visibility_country'),
        $visibilityColumnSql('visibility_address', 'private'),
        $visibilityColumnSql('visibility_postal_code', 'private'),
        $visibilityColumnSql('visibility_qth'),
        $visibilityColumnSql('visibility_locator'),
        $visibilityColumnSql('visibility_bio'),
        $visibilityColumnSql('visibility_licence_class'),
        $visibilityColumnSql('visibility_operator_since'),
        $visibilityColumnSql('visibility_qsl'),
        $visibilityColumnSql('visibility_qrz'),
        $visibilityColumnSql('visibility_uba'),
        $visibilityColumnSql('visibility_favourite_bands'),
        $visibilityColumnSql('visibility_favourite_modes'),
        $visibilityColumnSql('visibility_station'),
        $visibilityColumnSql('visibility_antennas'),
        $visibilityColumnSql('visibility_interests'),
    ];

    return 'callsign, first_name, last_name, full_name, email, phone, country, address, postal_code, qth, locator, bio, licence_class, operator_since, cq_zone, itu_zone,
            qsl_via, lotw_username, eqsl_username, qrz_url, website, is_uba_member, uba_member_number, station_equipment, antennas, max_power,
            favourite_bands, favourite_modes, interests, photo_path, avatar_path, '
            . implode(', ', $visibilityColumns);
}
}

if (!function_exists('member_qrz_url_for_profile_save')) {
function member_qrz_url_for_profile_save(string $newCallsign, string $previousCallsign = '', string $existingQrzUrl = ''): ?string
{
    $newCallsign = strtoupper(trim($newCallsign));
    $previousCallsign = strtoupper(trim($previousCallsign));
    $existingQrzUrl = trim($existingQrzUrl);

    if ($newCallsign === '') {
        return $existingQrzUrl !== '' ? $existingQrzUrl : null;
    }

    if ($existingQrzUrl !== '' && ($previousCallsign === '' || $previousCallsign === $newCallsign)) {
        return $existingQrzUrl;
    }

    $verifiedUrl = qrz_profile_url_for_callsign($newCallsign);
    if ($verifiedUrl !== null && $verifiedUrl !== '') {
        return $verifiedUrl;
    }

    return ($previousCallsign === $newCallsign && $existingQrzUrl !== '') ? $existingQrzUrl : null;
}
}

if (!function_exists('member_lotw_username_for_profile_save')) {
function member_lotw_username_for_profile_save(string $callsign, string $lotwUsername): ?string
{
    $lotwUsername = trim($lotwUsername);
    if ($lotwUsername !== '') {
        return $lotwUsername;
    }

    $callsign = strtoupper(trim($callsign));

    return $callsign !== '' ? $callsign : null;
}
}

if (!function_exists('member_backfill_missing_qrz_url')) {
/**
 * @param array<string, mixed> $member
 * @return array<string, mixed>
 */
function member_backfill_missing_qrz_url(int $memberId, array $member): array
{
    $callsign = strtoupper(trim((string) ($member['callsign'] ?? '')));
    $existingQrzUrl = trim((string) ($member['qrz_url'] ?? ''));
    if ($memberId <= 0 || $callsign === '' || $existingQrzUrl !== '' || !table_exists('members')) {
        return $member;
    }

    $verifiedUrl = qrz_profile_url_for_callsign($callsign);
    if ($verifiedUrl === null || $verifiedUrl === '') {
        return $member;
    }

    db()->prepare('UPDATE members SET qrz_url = ? WHERE id = ? AND (qrz_url IS NULL OR qrz_url = "") LIMIT 1')
        ->execute([$verifiedUrl, $memberId]);
    $member['qrz_url'] = $verifiedUrl;

    return $member;
}
}
