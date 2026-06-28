<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$i18n = i18n_load_array_file_once(__DIR__ . '/../app/i18n/admin_members.php');
$i18n = i18n_expand_supported_locales($i18n);
$t = [];
foreach (array_keys($i18n['fr']) as $key) {
    $pool = [];
    foreach ($i18n as $lang => $translations) {
        if (isset($translations[$key])) {
            $pool[$lang] = $translations[$key];
        }
    }
    $t[$key] = i18n_localized_value($pool, $locale, 'fr');
}
set_page_meta(['title' => (string) $t['layout'], 'description' => (string) $t['meta_desc'], 'robots' => 'noindex,nofollow']);

function admin_members_clean_input_text(string $value): string
{
    $normalized = preg_replace('/\s+/u', ' ', trim($value));

    return is_string($normalized) ? $normalized : trim($value);
}

/**
 * @return array{first_name:string, last_name:string, full_name:string}
 */
function admin_members_name_values_from_post(): array
{
    $firstName = admin_members_clean_input_text((string) ($_POST['first_name'] ?? ''));
    $lastName = admin_members_clean_input_text((string) ($_POST['last_name'] ?? ''));
    $fullNameInput = admin_members_clean_input_text((string) ($_POST['full_name'] ?? ''));

    if (($firstName === '' || $lastName === '') && $fullNameInput !== '') {
        $nameParts = member_name_parts_from_full_name($fullNameInput);
        if ($firstName === '') {
            $firstName = $nameParts['first_name'];
        }
        if ($lastName === '') {
            $lastName = $nameParts['last_name'];
        }
    }

    return [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'full_name' => member_full_name_from_parts($firstName, $lastName),
    ];
}

function admin_members_validate_name_values(array $nameValues, string $errorMessage): void
{
    $firstName = (string) ($nameValues['first_name'] ?? '');
    $lastName = (string) ($nameValues['last_name'] ?? '');
    $fullName = (string) ($nameValues['full_name'] ?? '');
    if (
        $firstName === ''
        || $lastName === ''
        || $fullName === ''
        || mb_strlen($firstName) > 95
        || mb_strlen($lastName) > 95
        || mb_strlen($fullName) > 190
    ) {
        throw new RuntimeException($errorMessage);
    }
}

function admin_members_ensure_related_tables(): void
{
    if (!table_exists('members')) {
        return;
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS member_grade_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NOT NULL,
            grade_label VARCHAR(120) NOT NULL,
            obtained_on DATE DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_member_grade_history_member_date (member_id, obtained_on),
            CONSTRAINT member_grade_history_member_fk FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    db()->exec(
        'CREATE TABLE IF NOT EXISTS member_payment_statuses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NOT NULL,
            period_type ENUM("month","year") NOT NULL DEFAULT "year",
            period_key VARCHAR(7) NOT NULL,
            status ENUM("paid","pending","unpaid") NOT NULL DEFAULT "unpaid",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_member_payment_period (member_id, period_type, period_key),
            KEY idx_member_payment_statuses_member_period (member_id, period_type, period_key),
            CONSTRAINT member_payment_statuses_member_fk FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function admin_members_date_or_null_from_post(string $fieldName, string $errorMessage): ?string
{
    $value = trim((string) ($_POST[$fieldName] ?? ''));
    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $value) {
        throw new RuntimeException($errorMessage);
    }

    return $value;
}

function admin_members_payment_period_from_post(string $errorMessage): array
{
    $periodType = (string) ($_POST['payment_period_type'] ?? 'year');
    if (!in_array($periodType, ['month', 'year'], true)) {
        throw new RuntimeException($errorMessage);
    }

    if ($periodType === 'month') {
        $periodKey = trim((string) ($_POST['payment_month'] ?? ''));
        $periodDate = DateTimeImmutable::createFromFormat('!Y-m', $periodKey);
        if (!$periodDate instanceof DateTimeImmutable || $periodDate->format('Y-m') !== $periodKey) {
            throw new RuntimeException($errorMessage);
        }

        return ['type' => $periodType, 'key' => $periodKey];
    }

    $periodKey = trim((string) ($_POST['payment_year'] ?? ''));
    if (preg_match('/^\d{4}$/', $periodKey) !== 1) {
        throw new RuntimeException($errorMessage);
    }

    return ['type' => $periodType, 'key' => $periodKey];
}

function admin_members_redirect_from_post_return(): void
{
    $postReturnParams = [];
    parse_str((string) ($_POST['return_query'] ?? ''), $postReturnParams);
    $postReturnSort = (string) ($postReturnParams['sort'] ?? 'callsign');
    $postReturnDir = strtolower((string) ($postReturnParams['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
    $postReturnPage = max(1, (int) ($postReturnParams['page'] ?? 1));
    redirect_url(route_url_clean('admin_members', [
        'member_q' => trim((string) ($postReturnParams['member_q'] ?? '')),
        'sort' => in_array($postReturnSort, ['callsign', 'first_name', 'last_name', 'full_name', 'email', 'locator', 'is_active', 'is_committee'], true) ? $postReturnSort : 'callsign',
        'dir' => $postReturnDir,
        'page' => (string) $postReturnPage,
    ]));
}

/**
 * @param list<int> $memberIds
 * @return array<int, list<array<string, mixed>>>
 */
function admin_members_grade_history_by_member_ids(array $memberIds): array
{
    $memberIds = array_values(array_unique(array_filter(array_map('intval', $memberIds), static fn(int $memberId): bool => $memberId > 0)));
    if ($memberIds === [] || !table_exists('member_grade_history')) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
    $stmt = db()->prepare(
        'SELECT id, member_id, grade_label, obtained_on
         FROM member_grade_history
         WHERE member_id IN (' . $placeholders . ')
         ORDER BY member_id ASC, obtained_on DESC, id DESC'
    );
    $stmt->execute($memberIds);

    $grouped = [];
    foreach ($stmt->fetchAll() ?: [] as $row) {
        $memberId = (int) ($row['member_id'] ?? 0);
        if ($memberId <= 0) {
            continue;
        }
        $grouped[$memberId][] = $row;
    }

    return $grouped;
}

/**
 * @param list<int> $memberIds
 * @return array<int, list<array<string, mixed>>>
 */
function admin_members_payment_statuses_by_member_ids(array $memberIds): array
{
    $memberIds = array_values(array_unique(array_filter(array_map('intval', $memberIds), static fn(int $memberId): bool => $memberId > 0)));
    if ($memberIds === [] || !table_exists('member_payment_statuses')) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
    $stmt = db()->prepare(
        'SELECT id, member_id, period_type, period_key, status
         FROM member_payment_statuses
         WHERE member_id IN (' . $placeholders . ')
         ORDER BY member_id ASC, period_key DESC, id DESC'
    );
    $stmt->execute($memberIds);

    $grouped = [];
    foreach ($stmt->fetchAll() ?: [] as $row) {
        $memberId = (int) ($row['member_id'] ?? 0);
        if ($memberId <= 0) {
            continue;
        }
        $grouped[$memberId][] = $row;
    }

    return $grouped;
}

function admin_members_payment_status_label(string $status, array $labels): string
{
    return (string) ($labels['payment_status_' . $status] ?? $status);
}

/**
 * @param list<array<string, mixed>> $payments
 * @return list<string>
 */
function admin_members_mutual_form_years_from_payments(array $payments): array
{
    $years = [];
    $paidMonthsByYear = [];
    foreach ($payments as $payment) {
        if ((string) ($payment['status'] ?? '') !== 'paid') {
            continue;
        }

        $periodType = (string) ($payment['period_type'] ?? '');
        $periodKey = (string) ($payment['period_key'] ?? '');
        if ($periodType === 'year' && preg_match('/^\d{4}$/', $periodKey) === 1) {
            $years[$periodKey] = true;
            continue;
        }

        if ($periodType === 'month' && preg_match('/^(\d{4})-(\d{2})$/', $periodKey, $matches) === 1) {
            $year = (string) $matches[1];
            $paidMonthsByYear[$year][$periodKey] = true;
        }
    }

    foreach ($paidMonthsByYear as $year => $months) {
        if (count($months) >= 12) {
            $years[$year] = true;
        }
    }

    $yearList = array_keys($years);
    rsort($yearList, SORT_STRING);

    return array_values($yearList);
}

function admin_members_membership_year_is_paid(int $memberId, string $year): bool
{
    if ($memberId <= 0 || preg_match('/^\d{4}$/', $year) !== 1 || !table_exists('member_payment_statuses')) {
        return false;
    }

    $yearStmt = db()->prepare(
        'SELECT COUNT(*)
         FROM member_payment_statuses
         WHERE member_id = ? AND period_type = "year" AND period_key = ? AND status = "paid"'
    );
    $yearStmt->execute([$memberId, $year]);
    if ((int) $yearStmt->fetchColumn() > 0) {
        return true;
    }

    $monthStmt = db()->prepare(
        'SELECT COUNT(DISTINCT period_key)
         FROM member_payment_statuses
         WHERE member_id = ? AND period_type = "month" AND period_key LIKE ? AND status = "paid"'
    );
    $monthStmt->execute([$memberId, $year . '-%']);

    return (int) $monthStmt->fetchColumn() >= 12;
}

function admin_members_mutual_form_filename(array $member, string $year): string
{
    $callsign = strtoupper(preg_replace('/[^A-Z0-9-]+/i', '-', (string) ($member['callsign'] ?? 'member')) ?: 'member');
    $callsign = trim($callsign, '-');
    if ($callsign === '') {
        $callsign = 'member';
    }

    return 'formulaire-mutuelle-' . $callsign . '-' . $year . '.html';
}

function admin_members_generate_mutual_form_response(int $memberId, string $year, array $labels): void
{
    if ($memberId <= 0 || preg_match('/^\d{4}$/', $year) !== 1 || !admin_members_membership_year_is_paid($memberId, $year)) {
        set_flash('error', (string) $labels['mutual_form_denied']);
        redirect('admin_members');
    }

    $stmt = db()->prepare(
        'SELECT id, callsign, first_name, last_name, full_name, email, country, address, postal_code, qth
         FROM members
         WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$memberId]);
    $member = $stmt->fetch();
    if (!is_array($member)) {
        set_flash('error', (string) $labels['mutual_form_denied']);
        redirect('admin_members');
    }

    $clubName = trim((string) config('privacy.controller_name', 'Radio Club Durnal ON4CRD'));
    $clubEmail = trim((string) config('privacy.controller_email', 'crdurnal@gmail.com'));
    $clubAddress = trim((string) config('privacy.controller_postal_address', 'Rue des Ecoles, 5530 Purnode, Belgique'));
    $memberAddress = trim(implode(' ', array_filter([
        trim((string) ($member['address'] ?? '')),
        trim((string) ($member['postal_code'] ?? '')),
        trim((string) ($member['qth'] ?? '')),
        trim((string) ($member['country'] ?? '')),
    ], static fn(string $part): bool => $part !== '')));
    $generatedOn = (new DateTimeImmutable())->format('d/m/Y');
    $filename = admin_members_mutual_form_filename($member, $year);

    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>' . e((string) $labels['mutual_form_title']) . ' ' . e($year) . '</title>';
    echo '<style>
        :root{color:#111827;font-family:Arial,sans-serif;font-size:14px}
        body{margin:0;background:#f8fafc}
        .sheet{width:210mm;min-height:297mm;margin:0 auto;padding:18mm;background:#fff;box-sizing:border-box}
        h1{font-size:22px;margin:0 0 6mm;text-transform:uppercase}
        h2{font-size:15px;margin:8mm 0 3mm;border-bottom:1px solid #111827;padding-bottom:2mm}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:4mm 8mm}
        .field{min-height:12mm;border-bottom:1px solid #9ca3af;padding-bottom:2mm}
        .label{display:block;color:#4b5563;font-size:11px;text-transform:uppercase}
        .value{display:block;font-size:15px;margin-top:1mm}
        .wide{grid-column:1/-1}
        .muted{color:#4b5563;font-size:12px}
        .signature{height:25mm;border:1px solid #9ca3af;margin-top:4mm;padding:3mm}
        @media print{body{background:#fff}.sheet{margin:0;box-shadow:none}.no-print{display:none}}
    </style></head><body><main class="sheet">';
    echo '<h1>' . e((string) $labels['mutual_form_title']) . '</h1>';
    echo '<p class="muted">' . e(str_replace('{year}', $year, (string) $labels['mutual_form_intro'])) . '</p>';
    echo '<p class="no-print"><button onclick="window.print()">' . e((string) $labels['mutual_form_print']) . '</button></p>';
    echo '<h2>' . e((string) $labels['mutual_form_member_section']) . '</h2><div class="grid">';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_last_name']) . '</span><span class="value">' . e((string) ($member['last_name'] ?? '')) . '</span></div>';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_first_name']) . '</span><span class="value">' . e((string) ($member['first_name'] ?? '')) . '</span></div>';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_callsign']) . '</span><span class="value">' . e((string) ($member['callsign'] ?? '')) . '</span></div>';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_email']) . '</span><span class="value">' . e((string) ($member['email'] ?? '')) . '</span></div>';
    echo '<div class="field wide"><span class="label">' . e((string) $labels['mutual_form_address']) . '</span><span class="value">' . e($memberAddress) . '</span></div>';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_birth_date']) . '</span><span class="value">&nbsp;</span></div>';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_national_number']) . '</span><span class="value">&nbsp;</span></div>';
    echo '</div><h2>' . e((string) $labels['mutual_form_club_section']) . '</h2><div class="grid">';
    echo '<div class="field wide"><span class="label">' . e((string) $labels['mutual_form_club_name']) . '</span><span class="value">' . e($clubName) . '</span></div>';
    echo '<div class="field wide"><span class="label">' . e((string) $labels['mutual_form_club_address']) . '</span><span class="value">' . e($clubAddress) . '</span></div>';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_club_email']) . '</span><span class="value">' . e($clubEmail) . '</span></div>';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_activity']) . '</span><span class="value">' . e((string) $labels['mutual_form_activity_value']) . '</span></div>';
    echo '</div><h2>' . e((string) $labels['mutual_form_payment_section']) . '</h2><div class="grid">';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_year']) . '</span><span class="value">' . e($year) . '</span></div>';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_paid_status']) . '</span><span class="value">' . e((string) $labels['mutual_form_paid_status_value']) . '</span></div>';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_amount']) . '</span><span class="value">&nbsp;</span></div>';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_generated_on']) . '</span><span class="value">' . e($generatedOn) . '</span></div>';
    echo '</div><h2>' . e((string) $labels['mutual_form_mutual_section']) . '</h2><div class="grid">';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_mutual_name']) . '</span><span class="value">&nbsp;</span></div>';
    echo '<div class="field"><span class="label">' . e((string) $labels['mutual_form_affiliation_number']) . '</span><span class="value">&nbsp;</span></div>';
    echo '</div><h2>' . e((string) $labels['mutual_form_signature']) . '</h2>';
    echo '<div class="signature">' . e((string) $labels['mutual_form_signature_place']) . '</div>';
    echo '<p class="muted">' . e((string) $labels['mutual_form_note']) . '</p>';
    echo '</main></body></html>';
    exit;
}

$returnQuery = http_build_query([
    'member_q' => (string) ($_GET['member_q'] ?? ''),
    'sort' => (string) ($_GET['sort'] ?? 'callsign'),
    'dir' => (string) ($_GET['dir'] ?? 'asc'),
    'page' => (string) ($_GET['page'] ?? '1'),
]);
$passwordChangeColumnAvailable = table_has_column('members', 'password_change_required');
$passwordResetMarkerColumnAvailable = table_has_column('members', 'password_reset_forced_at');
$passwordResetForceAvailable = $passwordChangeColumnAvailable && $passwordResetMarkerColumnAvailable;
admin_members_ensure_related_tables();
if ((string) ($_GET['mutual_form'] ?? '') === '1') {
    admin_members_generate_mutual_form_response((int) ($_GET['member_id'] ?? 0), trim((string) ($_GET['year'] ?? '')), $t);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'update_member');

    if ($action === 'create_member') {
        try {
            $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
            $nameValues = admin_members_name_values_from_post();
            admin_members_validate_name_values($nameValues, (string) $t['err_name']);
            $fullName = $nameValues['full_name'];
            $emailInput = trim((string) ($_POST['email'] ?? ''));
            $email = member_contact_email_from_input($emailInput);
            $password = (string) ($_POST['password'] ?? '');
            $locator = strtoupper(trim((string) ($_POST['locator'] ?? '')));

            if ($callsign === '' || mb_strlen($callsign) > 32 || preg_match('/^[A-Z0-9\/-]{2,32}$/', $callsign) !== 1) {
                throw new RuntimeException((string) $t['err_callsign']);
            }
            if ($emailInput !== '' && filter_var($emailInput, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException((string) $t['err_email']);
            }
            if ($locator !== '' && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $locator) !== 1) {
                throw new RuntimeException((string) $t['err_locator']);
            }
            if (strlen($password) < 8) {
                throw new RuntimeException((string) $t['err_password']);
            }

            $existsStmt = db()->prepare('SELECT COUNT(*) FROM members WHERE UPPER(callsign) = ?');
            $existsStmt->execute([$callsign]);
            if ((int) $existsStmt->fetchColumn() > 0) {
                throw new RuntimeException((string) $t['err_exists']);
            }

            $authClient = auth();
            if ($authClient === null) {
                throw new RuntimeException((string) $t['auth_unavailable']);
            }

            $authEmail = member_auth_email_for_contact_email($email, $callsign);
            member_cleanup_registration_auth_orphan($authEmail, $callsign);
            $authUserId = 0;
            try {
                $authUserId = (int) $authClient->admin()->createUserWithUniqueUsername($authEmail, $password, $callsign);
            } catch (\Delight\Auth\InvalidEmailException|\Delight\Auth\InvalidPasswordException $exception) {
                throw new RuntimeException((string) $t['err_password']);
            } catch (\Delight\Auth\UserAlreadyExistsException|\Delight\Auth\DuplicateUsernameException $exception) {
                throw new RuntimeException((string) $t['err_exists']);
            }

            $passwordChangeRequired = isset($_POST['password_change_required']) ? 1 : 0;
            $columns = ['auth_user_id', 'callsign', 'first_name', 'last_name', 'full_name', 'email', 'password_hash', 'locator', 'is_active', 'is_committee'];
            $values = [
                $authUserId,
                $callsign,
                $nameValues['first_name'],
                $nameValues['last_name'],
                $fullName,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $locator !== '' ? $locator : null,
                isset($_POST['is_active']) ? 1 : 0,
                isset($_POST['is_committee']) ? 1 : 0,
            ];
            if ($passwordChangeColumnAvailable) {
                $columns[] = 'password_change_required';
                $values[] = $passwordChangeRequired;
            }
            if ($passwordResetMarkerColumnAvailable) {
                $columns[] = 'password_reset_forced_at';
                $values[] = $passwordChangeRequired === 1 ? date('Y-m-d H:i:s') : null;
            }

            try {
                db()->prepare('INSERT INTO members (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')')
                    ->execute($values);
            } catch (Throwable $exception) {
                member_delete_unlinked_auth_user($authUserId);
                throw $exception;
            }

            set_flash('success', (string) $t['member_created']);
        } catch (Throwable $throwable) {
            set_flash('error', $throwable->getMessage());
        }
        redirect('admin_members');
    }

    if ($action === 'add_member_grade') {
        try {
            $memberId = (int) ($_POST['member_id'] ?? 0);
            $gradeLabel = admin_members_clean_input_text((string) ($_POST['grade_label'] ?? ''));
            if ($memberId <= 0 || $gradeLabel === '' || mb_strlen($gradeLabel) > 120) {
                throw new RuntimeException((string) $t['err_grade']);
            }
            $obtainedOn = admin_members_date_or_null_from_post('obtained_on', (string) $t['err_grade_date']);
            db()->prepare('INSERT INTO member_grade_history (member_id, grade_label, obtained_on) VALUES (?, ?, ?)')
                ->execute([$memberId, $gradeLabel, $obtainedOn]);
            set_flash('success', (string) $t['member_updated']);
        } catch (Throwable $throwable) {
            set_flash('error', $throwable->getMessage());
        }
        admin_members_redirect_from_post_return();
    }

    if ($action === 'delete_member_grade') {
        $memberId = (int) ($_POST['member_id'] ?? 0);
        $gradeId = (int) ($_POST['grade_id'] ?? 0);
        if ($memberId > 0 && $gradeId > 0) {
            db()->prepare('DELETE FROM member_grade_history WHERE id = ? AND member_id = ? LIMIT 1')->execute([$gradeId, $memberId]);
        }
        set_flash('success', (string) $t['member_updated']);
        admin_members_redirect_from_post_return();
    }

    if ($action === 'save_member_payment') {
        try {
            $memberId = (int) ($_POST['member_id'] ?? 0);
            if ($memberId <= 0) {
                throw new RuntimeException((string) $t['err_payment_period']);
            }
            $period = admin_members_payment_period_from_post((string) $t['err_payment_period']);
            $paymentStatus = (string) ($_POST['payment_status'] ?? 'unpaid');
            if (!in_array($paymentStatus, ['paid', 'pending', 'unpaid'], true)) {
                throw new RuntimeException((string) $t['err_payment_status']);
            }

            $existingPaymentStmt = db()->prepare('SELECT id FROM member_payment_statuses WHERE member_id = ? AND period_type = ? AND period_key = ? LIMIT 1');
            $existingPaymentStmt->execute([$memberId, $period['type'], $period['key']]);
            $paymentId = (int) ($existingPaymentStmt->fetchColumn() ?: 0);
            if ($paymentId > 0) {
                db()->prepare('UPDATE member_payment_statuses SET status = ? WHERE id = ? AND member_id = ? LIMIT 1')
                    ->execute([$paymentStatus, $paymentId, $memberId]);
            } else {
                db()->prepare('INSERT INTO member_payment_statuses (member_id, period_type, period_key, status) VALUES (?, ?, ?, ?)')
                    ->execute([$memberId, $period['type'], $period['key'], $paymentStatus]);
            }
            set_flash('success', (string) $t['member_updated']);
        } catch (Throwable $throwable) {
            set_flash('error', $throwable->getMessage());
        }
        admin_members_redirect_from_post_return();
    }

    if ($action === 'delete_member_payment') {
        $memberId = (int) ($_POST['member_id'] ?? 0);
        $paymentId = (int) ($_POST['payment_id'] ?? 0);
        if ($memberId > 0 && $paymentId > 0) {
            db()->prepare('DELETE FROM member_payment_statuses WHERE id = ? AND member_id = ? LIMIT 1')->execute([$paymentId, $memberId]);
        }
        set_flash('success', (string) $t['member_updated']);
        admin_members_redirect_from_post_return();
    }

    $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
    $nameValues = admin_members_name_values_from_post();
    $email = trim((string) ($_POST['email'] ?? ''));
    $locator = strtoupper(trim((string) ($_POST['locator'] ?? '')));
    try {
        admin_members_validate_name_values($nameValues, (string) $t['err_name']);
    } catch (Throwable $throwable) {
        set_flash('error', $throwable->getMessage());
        admin_members_redirect_from_post_return();
    }
    if ($callsign === '') { set_flash('error', (string) $t['err_callsign']); admin_members_redirect_from_post_return(); }
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) { set_flash('error', (string) $t['err_email']); admin_members_redirect_from_post_return(); }
    if ($locator !== '' && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $locator) !== 1) { set_flash('error', (string) $t['err_locator']); admin_members_redirect_from_post_return(); }
    $updates = ['callsign = ?', 'first_name = ?', 'last_name = ?', 'full_name = ?', 'email = ?', 'locator = ?', 'is_active = ?', 'is_committee = ?'];
    $params = [$callsign, $nameValues['first_name'], $nameValues['last_name'], $nameValues['full_name'], $email, $locator, isset($_POST['is_active']) ? 1 : 0, isset($_POST['is_committee']) ? 1 : 0];
    if ($passwordResetForceAvailable) {
        $forcePasswordReset = isset($_POST['password_change_required']);
        $updates[] = 'password_change_required = ?';
        $updates[] = 'password_reset_forced_at = ?';
        $params[] = $forcePasswordReset ? 1 : 0;
        $params[] = $forcePasswordReset ? date('Y-m-d H:i:s') : null;
    }
    $params[] = (int) ($_POST['member_id'] ?? 0);
    db()->prepare('UPDATE members SET ' . implode(', ', $updates) . ' WHERE id = ? LIMIT 1')->execute($params);
    set_flash('success', (string) $t['member_updated']);
    admin_members_redirect_from_post_return();
}

$memberSearch = trim((string) ($_GET['member_q'] ?? ''));
$memberSort = (string) ($_GET['sort'] ?? 'callsign');
$memberPage = max(1, (int) ($_GET['page'] ?? 1));
$memberPerPage = 25;
$memberDir = strtolower((string) ($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
$allowedSort = ['callsign', 'first_name', 'last_name', 'full_name', 'email', 'locator', 'is_active', 'is_committee'];
if (!in_array($memberSort, $allowedSort, true)) { $memberSort = 'callsign'; }
$memberColumns = 'id, callsign, first_name, last_name, full_name, email, locator, is_active, is_committee';
if ($passwordChangeColumnAvailable) {
    $memberColumns .= ', password_change_required';
}
if ($passwordResetMarkerColumnAvailable) {
    $memberColumns .= ', password_reset_forced_at';
}
$members = db()->query('SELECT ' . $memberColumns . ' FROM members ORDER BY callsign')->fetchAll();
usort($members, static function (array $a, array $b) use ($memberSort, $memberDir): int { $cmp = strnatcasecmp((string) ($a[$memberSort] ?? ''), (string) ($b[$memberSort] ?? '')); return $memberDir === 'desc' ? -$cmp : $cmp; });
if ($memberSearch !== '') {
    $needle = mb_safe_strtolower($memberSearch);
    $members = array_values(array_filter($members, static function (array $m) use ($needle): bool {
        $hay = mb_safe_strtolower((string) ($m['callsign'] ?? '') . ' ' . (string) ($m['first_name'] ?? '') . ' ' . (string) ($m['last_name'] ?? '') . ' ' . (string) ($m['full_name'] ?? '') . ' ' . (string) ($m['email'] ?? ''));
        return str_contains($hay, $needle);
    }));
}
$memberTotal = count($members);
$memberPages = max(1, (int) ceil($memberTotal / $memberPerPage));
if ($memberPage > $memberPages) { $memberPage = $memberPages; }
$members = array_slice($members, ($memberPage - 1) * $memberPerPage, $memberPerPage);
$memberIds = array_map(static fn(array $member): int => (int) ($member['id'] ?? 0), $members);
$memberGrades = admin_members_grade_history_by_member_ids($memberIds);
$memberPayments = admin_members_payment_statuses_by_member_ids($memberIds);
$currentYear = date('Y');
$currentMonth = date('Y-m');

ob_start();
?>
<section class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <section class="stack" style="margin:1rem 0;">
        <h2><?= e((string) $t['create_title']) ?></h2>
        <form method="post" class="grid" style="grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap:.75rem; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_member">
            <label><?= e((string) $t['th_callsign']) ?><input type="text" name="callsign" maxlength="32" required></label>
            <label><?= e((string) $t['th_first_name']) ?><input type="text" name="first_name" maxlength="95" required></label>
            <label><?= e((string) $t['th_last_name']) ?><input type="text" name="last_name" maxlength="95" required></label>
            <label><?= e((string) $t['th_email']) ?><input type="email" name="email" maxlength="190" placeholder="<?= e(member_default_contact_email()) ?>"></label>
            <label><?= e((string) $t['th_locator']) ?><input type="text" name="locator" maxlength="6"></label>
            <label><?= e((string) $t['temporary_password']) ?><input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
            <label><input type="checkbox" name="is_active" value="1" checked> <?= e((string) $t['th_active']) ?></label>
            <label><input type="checkbox" name="is_committee" value="1"> <?= e((string) $t['th_committee']) ?></label>
            <?php if ($passwordChangeColumnAvailable): ?>
                <label><input type="checkbox" name="password_change_required" value="1" checked> <?= e((string) $t['password_reset_force']) ?></label>
            <?php endif; ?>
            <button class="button" type="submit"><?= e((string) $t['create_submit']) ?></button>
        </form>
    </section>
    <form method="get" style="margin:.5rem 0 1rem;">
        <label><?= e((string) $t['search']) ?>
            <input type="text" name="member_q" value="<?= e($memberSearch) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
        </label>
        <input type="hidden" name="sort" value="<?= e($memberSort) ?>"><input type="hidden" name="dir" value="<?= e($memberDir) ?>"><button class="button secondary" type="submit"><?= e((string) $t['search_btn']) ?></button>
    </form>
    <div class="table-wrap"><table><thead><tr>
        <th><?= e((string) $t['th_callsign']) ?></th><th><?= e((string) $t['th_first_name']) ?></th><th><?= e((string) $t['th_last_name']) ?></th><th><?= e((string) $t['th_email']) ?></th><th><?= e((string) $t['th_locator']) ?></th><th><?= e((string) $t['grades_title']) ?></th><th><?= e((string) $t['payments_title']) ?></th><th><?= e((string) $t['th_active']) ?></th><th><?= e((string) $t['th_committee']) ?></th><th><?= e((string) $t['th_actions']) ?></th>
    </tr></thead><tbody>
    <?php foreach ($members as $member): ?>
        <?php
            $memberId = (int) $member['id'];
            $gradesForMember = $memberGrades[$memberId] ?? [];
            $paymentsForMember = $memberPayments[$memberId] ?? [];
            $mutualFormYears = admin_members_mutual_form_years_from_payments($paymentsForMember);
        ?>
        <tr><td colspan="10"><form method="post" class="grid" style="grid-template-columns: repeat(auto-fit, minmax(8rem, 1fr)); gap:.5rem; align-items:center;">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update_member"><input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>"><input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
            <input type="text" name="callsign" value="<?= e((string) $member['callsign']) ?>" aria-label="<?= e((string) $t['th_callsign']) ?>"><input type="text" name="first_name" value="<?= e((string) $member['first_name']) ?>" maxlength="95" required aria-label="<?= e((string) $t['th_first_name']) ?>"><input type="text" name="last_name" value="<?= e((string) $member['last_name']) ?>" maxlength="95" required aria-label="<?= e((string) $t['th_last_name']) ?>"><input type="hidden" name="full_name" value="<?= e((string) $member['full_name']) ?>"><input type="email" name="email" value="<?= e((string) $member['email']) ?>" aria-label="<?= e((string) $t['th_email']) ?>"><input type="text" name="locator" value="<?= e((string) $member['locator']) ?>" maxlength="6" aria-label="<?= e((string) $t['th_locator']) ?>">
            <label><input type="checkbox" name="is_active" value="1" <?= (int) $member['is_active'] === 1 ? 'checked' : '' ?>></label>
            <label><input type="checkbox" name="is_committee" value="1" <?= (int) $member['is_committee'] === 1 ? 'checked' : '' ?>></label>
            <?php if ($passwordResetForceAvailable): ?>
                <?php $passwordResetForced = (int) ($member['password_change_required'] ?? 0) === 1 && trim((string) ($member['password_reset_forced_at'] ?? '')) !== ''; ?>
                <label><input type="checkbox" name="password_change_required" value="1" <?= $passwordResetForced ? 'checked' : '' ?>> <?= e((string) $t['password_reset_force']) ?></label>
            <?php else: ?>
                <span class="help"><?= e((string) $t['password_reset_unavailable']) ?></span>
            <?php endif; ?>
            <button class="button" type="submit"><?= e((string) $t['save']) ?></button>
        </form>
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr)); gap:1rem; margin-top:.75rem;">
            <section class="stack" data-admin-member-grades="<?= $memberId ?>">
                <h3><?= e((string) $t['grades_title']) ?></h3>
                <?php if ($gradesForMember === []): ?>
                    <p class="help"><?= e((string) $t['grades_empty']) ?></p>
                <?php else: ?>
                    <ul class="stack" style="list-style:none; padding:0; margin:0;">
                        <?php foreach ($gradesForMember as $grade): ?>
                            <li class="grid" style="grid-template-columns: 1fr auto; gap:.5rem; align-items:center;">
                                <span><strong><?= e((string) $grade['grade_label']) ?></strong><?php if (trim((string) ($grade['obtained_on'] ?? '')) !== ''): ?> <span class="help"><?= e((string) $grade['obtained_on']) ?></span><?php endif; ?></span>
                                <form method="post">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_member_grade">
                                    <input type="hidden" name="member_id" value="<?= $memberId ?>">
                                    <input type="hidden" name="grade_id" value="<?= (int) $grade['id'] ?>">
                                    <input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
                                    <button class="button secondary" type="submit"><?= e((string) $t['grade_delete']) ?></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <form method="post" class="grid" style="grid-template-columns: minmax(8rem, 1fr) minmax(8rem, 1fr) auto; gap:.5rem; align-items:end;">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="add_member_grade">
                    <input type="hidden" name="member_id" value="<?= $memberId ?>">
                    <input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
                    <label><?= e((string) $t['grade_label']) ?><input type="text" name="grade_label" maxlength="120" required></label>
                    <label><?= e((string) $t['grade_obtained_on']) ?><input type="date" name="obtained_on"></label>
                    <button class="button secondary" type="submit"><?= e((string) $t['grade_add']) ?></button>
                </form>
            </section>
            <section class="stack" data-admin-member-payments="<?= $memberId ?>">
                <h3><?= e((string) $t['payments_title']) ?></h3>
                <?php if ($paymentsForMember === []): ?>
                    <p class="help"><?= e((string) $t['payments_empty']) ?></p>
                <?php else: ?>
                    <ul class="stack" style="list-style:none; padding:0; margin:0;">
                        <?php foreach ($paymentsForMember as $payment): ?>
                            <li class="grid" style="grid-template-columns: 1fr auto; gap:.5rem; align-items:center;">
                                <span><strong><?= e((string) $payment['period_key']) ?></strong> <span class="help"><?= e((string) $t['payment_type_' . (string) $payment['period_type']]) ?> - <?= e(admin_members_payment_status_label((string) $payment['status'], $t)) ?></span></span>
                                <form method="post">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_member_payment">
                                    <input type="hidden" name="member_id" value="<?= $memberId ?>">
                                    <input type="hidden" name="payment_id" value="<?= (int) $payment['id'] ?>">
                                    <input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
                                    <button class="button secondary" type="submit"><?= e((string) $t['payment_delete']) ?></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($mutualFormYears !== []): ?>
                        <p>
                            <?php foreach ($mutualFormYears as $mutualFormYear): ?>
                                <a class="button secondary" href="<?= e(route_url('admin_members', ['mutual_form' => '1', 'member_id' => (string) $memberId, 'year' => $mutualFormYear])) ?>"><?= e(str_replace('{year}', $mutualFormYear, (string) $t['mutual_form_generate'])) ?></a>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
                <form method="post" class="grid" style="grid-template-columns: repeat(auto-fit, minmax(8rem, 1fr)); gap:.5rem; align-items:end;">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_member_payment">
                    <input type="hidden" name="member_id" value="<?= $memberId ?>">
                    <input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
                    <label><?= e((string) $t['payment_type']) ?><select name="payment_period_type">
                        <option value="year"><?= e((string) $t['payment_type_year']) ?></option>
                        <option value="month"><?= e((string) $t['payment_type_month']) ?></option>
                    </select></label>
                    <label><?= e((string) $t['payment_period_year']) ?><input type="number" name="payment_year" value="<?= e($currentYear) ?>" min="1900" max="2100"></label>
                    <label><?= e((string) $t['payment_period_month']) ?><input type="month" name="payment_month" value="<?= e($currentMonth) ?>"></label>
                    <label><?= e((string) $t['payment_status']) ?><select name="payment_status">
                        <option value="paid"><?= e((string) $t['payment_status_paid']) ?></option>
                        <option value="pending"><?= e((string) $t['payment_status_pending']) ?></option>
                        <option value="unpaid"><?= e((string) $t['payment_status_unpaid']) ?></option>
                    </select></label>
                    <button class="button secondary" type="submit"><?= e((string) $t['payment_save']) ?></button>
                </form>
            </section>
        </div></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
