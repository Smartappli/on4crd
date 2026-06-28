const test = require('node:test');
const {
  By,
  assert,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  pagePlainText,
  loginAsAdmin,
  requireAdminCredentials,
  ensureSeleniumRunnable,
  runSeleniumPhp,
} = require('./helpers');

async function submitForm(driver, form) {
  await driver.executeScript(`
    const form = arguments[0];
    const submitter = form.querySelector('button[type="submit"], button:not([type="button"]), input[type="submit"]');
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit(submitter || undefined);
    } else {
      form.submit();
    }
  `, form);
  await waitForDocumentReady(driver);
  await assertNoServerError(driver);
}

async function setFieldValue(driver, element, value) {
  await driver.executeScript(`
    const element = arguments[0];
    const value = arguments[1];
    if (element.tagName && element.tagName.toLowerCase() === 'select') {
      for (const option of element.options) {
        option.selected = option.value === value;
      }
    }
    element.value = value;
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
    const label = element.closest('label');
    const editor = label ? label.querySelector('.wysiwyg-editor[contenteditable="true"]') : null;
    if (editor) {
      editor.innerHTML = value;
      editor.dispatchEvent(new Event('input', { bubbles: true }));
      editor.dispatchEvent(new Event('change', { bubbles: true }));
    }
  `, element, value);
}

async function setCheckbox(driver, element, checked) {
  await driver.executeScript(`
    const element = arguments[0];
    element.checked = Boolean(arguments[1]);
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
  `, element, checked);
}

function seleniumJson(source, env = {}) {
  return JSON.parse(runSeleniumPhp(source, env) || 'null');
}

function adminMemberState(callsign) {
  const state = seleniumJson(`
require_once 'app/bootstrap.php';
$callsign = strtoupper((string) getenv('SELENIUM_TARGET_CALLSIGN'));
$columns = ['id', 'callsign', 'first_name', 'last_name', 'full_name', 'email', 'locator', 'is_active', 'is_committee'];
if (table_has_column('members', 'auth_user_id')) {
    $columns[] = 'auth_user_id';
}
foreach (['password_change_required', 'password_reset_forced_at'] as $column) {
    if (table_has_column('members', $column)) {
        $columns[] = $column;
    }
}
$stmt = db()->prepare('SELECT ' . implode(', ', $columns) . ' FROM members WHERE callsign = ? LIMIT 1');
$stmt->execute([$callsign]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TARGET_CALLSIGN: callsign });
  assert.ok(state && Number(state.id) > 0, `Membre Selenium introuvable pour ${callsign}.`);
  return state;
}

function adminMemberRelatedState(memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$grades = [];
$payments = [];
if ($memberId > 0 && table_exists('member_grade_history')) {
    $stmt = db()->prepare('SELECT id, grade_label, obtained_on FROM member_grade_history WHERE member_id = ? ORDER BY id ASC');
    $stmt->execute([$memberId]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
if ($memberId > 0 && table_exists('member_payment_statuses')) {
    $stmt = db()->prepare('SELECT id, period_type, period_key, status FROM member_payment_statuses WHERE member_id = ? ORDER BY id ASC');
    $stmt->execute([$memberId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
echo json_encode(['grades' => $grades, 'payments' => $payments], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_MEMBER_ID: String(memberId) });
}

function restoreAdminMember(state) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$state = json_decode((string) getenv('SELENIUM_MEMBER_STATE'), true);
if (is_array($state) && (int) ($state['id'] ?? 0) > 0) {
    $columns = ['callsign = ?', 'first_name = ?', 'last_name = ?', 'full_name = ?', 'email = ?', 'locator = ?', 'is_active = ?', 'is_committee = ?'];
    $params = [
        (string) $state['callsign'],
        (string) ($state['first_name'] ?? ''),
        (string) ($state['last_name'] ?? ''),
        (string) $state['full_name'],
        ($state['email'] ?? null) !== '' ? $state['email'] : null,
        ($state['locator'] ?? null) !== '' ? $state['locator'] : null,
        (int) ($state['is_active'] ?? 1),
        (int) ($state['is_committee'] ?? 0),
    ];
    if (table_has_column('members', 'password_change_required')) {
        $columns[] = 'password_change_required = ?';
        $params[] = (int) ($state['password_change_required'] ?? 0);
    }
    if (table_has_column('members', 'password_reset_forced_at')) {
        $columns[] = 'password_reset_forced_at = ?';
        $params[] = ($state['password_reset_forced_at'] ?? null) !== '' ? $state['password_reset_forced_at'] : null;
    }
    $params[] = (int) $state['id'];
    db()->prepare('UPDATE members SET ' . implode(', ', $columns) . ' WHERE id = ? LIMIT 1')->execute($params);
}
`, { SELENIUM_MEMBER_STATE: JSON.stringify(state) });
}

function cleanupCreatedAdminMember(callsign, email) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$callsign = strtoupper(trim((string) getenv('SELENIUM_CREATED_CALLSIGN')));
$email = trim((string) getenv('SELENIUM_CREATED_EMAIL'));
if ($callsign === '') {
    return;
}
$memberIds = [];
$authUserIds = [];
if (table_exists('members')) {
    $stmt = db()->prepare('SELECT id, auth_user_id FROM members WHERE UPPER(callsign) = ? OR email = ?');
    $stmt->execute([$callsign, $email]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $memberId = (int) ($row['id'] ?? 0);
        if ($memberId > 0) {
            $memberIds[] = $memberId;
        }
        $authUserId = (int) ($row['auth_user_id'] ?? 0);
        if ($authUserId > 0) {
            $authUserIds[] = $authUserId;
        }
    }
}
if (table_exists('users')) {
    $stmt = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$callsign, $email]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $id) {
        $authUserIds[] = (int) $id;
    }
}
$memberIds = array_values(array_unique(array_filter($memberIds, static fn(int $id): bool => $id > 0)));
$authUserIds = array_values(array_unique(array_filter($authUserIds, static fn(int $id): bool => $id > 0)));
if ($memberIds !== []) {
    $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
    foreach (['member_grade_history', 'member_payment_statuses', 'member_roles', 'member_permissions', 'member_notifications', 'member_favorites'] as $table) {
        if (table_exists($table)) {
            db()->prepare('DELETE FROM ' . $table . ' WHERE member_id IN (' . $placeholders . ')')->execute($memberIds);
        }
    }
    db()->prepare('DELETE FROM members WHERE id IN (' . $placeholders . ')')->execute($memberIds);
}
if ($authUserIds !== []) {
    $placeholders = implode(',', array_fill(0, count($authUserIds), '?'));
    foreach (['users_2fa', 'users_remembered', 'users_confirmations', 'users_resets'] as $table) {
        if (table_exists($table)) {
            $column = $table === 'users_confirmations' || $table === 'users_resets' ? 'user_id' : 'user';
            if ($table === 'users_2fa') {
                $column = 'user_id';
            }
            db()->prepare('DELETE FROM ' . $table . ' WHERE ' . $column . ' IN (' . $placeholders . ')')->execute($authUserIds);
        }
    }
    if (table_exists('users')) {
        db()->prepare('DELETE FROM users WHERE id IN (' . $placeholders . ')')->execute($authUserIds);
    }
}
`, { SELENIUM_CREATED_CALLSIGN: callsign, SELENIUM_CREATED_EMAIL: email });
}

function moduleState(code, seed = false) {
  const state = seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/runtime_schema.php';
if ((string) getenv('SELENIUM_SEED_MODULES') === '1') {
    seed_modules();
}
$stmt = db()->prepare('SELECT id, code, is_enabled, visibility FROM modules WHERE code = ? LIMIT 1');
$stmt->execute([(string) getenv('SELENIUM_MODULE_CODE')]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_MODULE_CODE: code, SELENIUM_SEED_MODULES: seed ? '1' : '0' });
  assert.ok(state && Number(state.id) > 0, `Module ${code} introuvable.`);
  return state;
}

function restoreModule(state) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$state = json_decode((string) getenv('SELENIUM_MODULE_STATE'), true);
if (is_array($state) && (int) ($state['id'] ?? 0) > 0) {
    db()->prepare('UPDATE modules SET is_enabled = ?, visibility = ? WHERE id = ?')
        ->execute([(int) ($state['is_enabled'] ?? 1), (string) ($state['visibility'] ?? 'public'), (int) $state['id']]);
}
`, { SELENIUM_MODULE_STATE: JSON.stringify(state) });
}

function createTemporaryRole(label) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$label = (string) getenv('SELENIUM_ROLE_LABEL');
$code = strtolower(preg_replace('/[^a-z0-9]+/', '-', $label));
if ($code === '') {
    $code = 'selenium-role';
}
db()->prepare('INSERT INTO roles (code, label) VALUES (?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')->execute([$code, $label]);
$roleId = (int) db()->lastInsertId();
if ($roleId <= 0) {
    $stmt = db()->prepare('SELECT id FROM roles WHERE code = ? LIMIT 1');
    $stmt->execute([$code]);
    $roleId = (int) ($stmt->fetchColumn() ?: 0);
}
echo json_encode(['id' => $roleId, 'code' => $code, 'label' => $label], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_ROLE_LABEL: label });
}

function cleanupTemporaryRole(role) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$roleId = (int) getenv('SELENIUM_ROLE_ID');
if ($roleId > 0) {
    db()->prepare('DELETE FROM member_roles WHERE role_id = ?')->execute([$roleId]);
    db()->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$roleId]);
    db()->prepare('DELETE FROM roles WHERE id = ? LIMIT 1')->execute([$roleId]);
}
`, { SELENIUM_ROLE_ID: String(role.id || 0) });
}

function cleanupAdminContent(token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$token = (string) getenv('SELENIUM_TOKEN');
if ($token !== '') {
    if (table_exists('press_contacts')) {
        db()->prepare('DELETE FROM press_contacts WHERE full_name LIKE ? OR notes LIKE ?')->execute(['%' . $token . '%', '%' . $token . '%']);
    }
    if (table_exists('press_releases')) {
        db()->prepare('DELETE FROM press_releases WHERE title LIKE ? OR summary LIKE ?')->execute(['%' . $token . '%', '%' . $token . '%']);
    }
    if (table_exists('dinner_reservation_lines') && table_exists('dinner_reservations')) {
        db()->prepare('DELETE l FROM dinner_reservation_lines l INNER JOIN dinner_reservations r ON r.id = l.reservation_id WHERE r.reserved_by LIKE ? OR r.notes LIKE ?')
            ->execute(['%' . $token . '%', '%' . $token . '%']);
        db()->prepare('DELETE FROM dinner_reservations WHERE reserved_by LIKE ? OR notes LIKE ?')->execute(['%' . $token . '%', '%' . $token . '%']);
    }
}
`, { SELENIUM_TOKEN: token });
}

function liveFeedState(code, label) {
  const state = seleniumJson(`
require_once 'app/bootstrap.php';
$code = (string) getenv('SELENIUM_FEED_CODE');
$label = (string) getenv('SELENIUM_FEED_LABEL');
db()->exec("DELETE FROM live_feeds WHERE code LIKE 'selenium-feed-%'");
db()->prepare('INSERT INTO live_feeds (code, label, url, parser, cache_ttl, refresh_seconds, is_enabled, notes) VALUES (?, ?, "https://example.com/selenium-feed.json", "json", 120, 180, 1, "Selenium baseline") ON DUPLICATE KEY UPDATE code = VALUES(code)')
    ->execute([$code, $label]);
$stmt = db()->prepare('SELECT * FROM live_feeds WHERE code = ? LIMIT 1');
$stmt->execute([$code]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_FEED_CODE: code, SELENIUM_FEED_LABEL: label });
  assert.ok(state && Number(state.id) > 0, `Flux live ${code} introuvable.`);
  return state;
}

function liveFeedRecord(feedId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$id = (int) getenv('SELENIUM_FEED_ID');
$stmt = db()->prepare('SELECT id, code, label, url, parser, cache_ttl, refresh_seconds, is_enabled, notes FROM live_feeds WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_FEED_ID: String(feedId) });
}

function restoreLiveFeed(state) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$state = json_decode((string) getenv('SELENIUM_FEED_STATE'), true);
if (is_array($state) && (int) ($state['id'] ?? 0) > 0) {
    if (str_starts_with((string) ($state['code'] ?? ''), 'selenium-feed-')) {
        db()->prepare('DELETE FROM live_feeds WHERE id = ? LIMIT 1')->execute([(int) $state['id']]);
    } else {
        db()->prepare('UPDATE live_feeds SET label = ?, url = ?, parser = ?, cache_ttl = ?, refresh_seconds = ?, is_enabled = ?, notes = ? WHERE id = ?')
            ->execute([
                (string) ($state['label'] ?? ''),
                ($state['url'] ?? null) !== '' ? $state['url'] : null,
                (string) ($state['parser'] ?? 'json'),
                (int) ($state['cache_ttl'] ?? 900),
                (int) ($state['refresh_seconds'] ?? 900),
                (int) ($state['is_enabled'] ?? 1),
                ($state['notes'] ?? null) !== '' ? $state['notes'] : null,
                (int) $state['id'],
            ]);
    }
}
`, { SELENIUM_FEED_STATE: JSON.stringify(state) });
}

function pressContactState(fullName) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$fullName = (string) getenv('SELENIUM_PRESS_CONTACT');
$stmt = db()->prepare('SELECT full_name, role_label, email, phone, notes, is_active FROM press_contacts WHERE full_name = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$fullName]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_PRESS_CONTACT: fullName });
}

function pressReleaseState(title) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$title = (string) getenv('SELENIUM_PRESS_RELEASE');
$stmt = db()->prepare('SELECT title, summary, published_on, file_path, is_published FROM press_releases WHERE title = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$title]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_PRESS_RELEASE: title });
}

function dinnerReservationState(reservedBy) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$reservedBy = (string) getenv('SELENIUM_DINNER_RESERVED_BY');
$stmt = db()->prepare('SELECT id, reserved_by, total_cents, notes FROM dinner_reservations WHERE reserved_by = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$reservedBy]);
$reservation = $stmt->fetch() ?: null;
$line = null;
if ($reservation) {
    $lineStmt = db()->prepare('SELECT starter_code, meal_code, dessert_code, starter_enabled, meal_enabled, dessert_enabled, quantity, line_total_cents FROM dinner_reservation_lines WHERE reservation_id = ? ORDER BY id ASC LIMIT 1');
    $lineStmt->execute([(int) $reservation['id']]);
    $line = $lineStmt->fetch() ?: null;
}
echo json_encode(['reservation' => $reservation, 'line' => $line], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_DINNER_RESERVED_BY: reservedBy });
}

test('Selenium admin configuration: modules, membres et roles restent modifiables', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const callsign = credentials.username.toUpperCase();
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  const memberState = adminMemberState(callsign);
  const moduleOriginal = moduleState('press', true);
  const role = createTemporaryRole(`Selenium role ${Date.now()}`);
  const updatedFirstName = 'Selenium';
  const updatedLastName = `Admin ${Date.now()}`;
  const updatedName = `${updatedFirstName} ${updatedLastName}`;
  const updatedLocator = 'JO20AA';
  const newVisibility = moduleOriginal.visibility === 'members' ? 'public' : 'members';
  const createdCallsign = `T${String(Date.now()).slice(-7)}`;
  const createdEmail = `${createdCallsign.toLowerCase()}@example.test`;
  const createdFirstName = 'Membre';
  const createdLastName = `Selenium ${createdCallsign}`;
  cleanupCreatedAdminMember(createdCallsign, createdEmail);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);

      await visit(driver, 'admin_modules');
      const moduleInput = await driver.findElement(By.css(`input[name="module_${moduleOriginal.id}"]`));
      const moduleForm = await moduleInput.findElement(By.xpath('ancestor::form[1]'));
      await setCheckbox(driver, moduleInput, Number(moduleOriginal.is_enabled) === 1);
      const visibilitySelect = await moduleForm.findElement(By.css(`select[name="visibility_${moduleOriginal.id}"]`));
      await setFieldValue(driver, visibilitySelect, newVisibility);
      const selectedVisibility = await driver.executeScript('return arguments[0].value;', visibilitySelect);
      assert.equal(selectedVisibility, newVisibility, 'Le select de visibilite doit etre positionne avant soumission.');
      await submitForm(driver, moduleForm);
      const moduleAfter = moduleState('press');
      assert.equal(Number(moduleAfter.is_enabled), Number(moduleOriginal.is_enabled), 'La sauvegarde module ne doit pas changer l etat active si la case reste identique.');
      assert.equal(moduleAfter.visibility, newVisibility, 'La visibilite du module doit etre persistee.');

      await visit(driver, 'admin_members', { member_q: callsign });
      const memberForm = await driver.findElement(By.xpath(`//input[@name="member_id" and @value="${memberState.id}"]/ancestor::form[1]`));
      await setFieldValue(driver, await memberForm.findElement(By.css('input[name="first_name"]')), updatedFirstName);
      await setFieldValue(driver, await memberForm.findElement(By.css('input[name="last_name"]')), updatedLastName);
      await setFieldValue(driver, await memberForm.findElement(By.css('input[name="locator"]')), updatedLocator);
      await submitForm(driver, memberForm);
      const updatedMember = adminMemberState(callsign);
      assert.equal(updatedMember.first_name, updatedFirstName, 'Le prenom modifie doit etre persiste en base.');
      assert.equal(updatedMember.last_name, updatedLastName, 'Le nom modifie doit etre persiste en base.');
      assert.equal(updatedMember.full_name, updatedName, 'Le nom modifie doit etre persiste en base.');
      assert.equal(updatedMember.locator, updatedLocator, 'Le locator modifie doit etre persiste en base.');
      const source = await driver.getPageSource();
      assert.match(source, new RegExp(updatedName), 'Le nom modifie doit rester rendu dans le formulaire admin_members.');
      assert.match(source, new RegExp(updatedLocator), 'Le locator modifie doit rester rendu dans le formulaire admin_members.');

      await visit(driver, 'admin_members');
      const createMemberForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="create_member"]]'));
      await setFieldValue(driver, await createMemberForm.findElement(By.css('input[name="callsign"]')), createdCallsign);
      await setFieldValue(driver, await createMemberForm.findElement(By.css('input[name="first_name"]')), createdFirstName);
      await setFieldValue(driver, await createMemberForm.findElement(By.css('input[name="last_name"]')), createdLastName);
      await setFieldValue(driver, await createMemberForm.findElement(By.css('input[name="email"]')), createdEmail);
      await setFieldValue(driver, await createMemberForm.findElement(By.css('input[name="locator"]')), 'JO20BB');
      await setFieldValue(driver, await createMemberForm.findElement(By.css('input[name="password"]')), 'Selenium!2026');
      await submitForm(driver, createMemberForm);
      const createdMember = adminMemberState(createdCallsign);
      assert.equal(createdMember.callsign, createdCallsign, 'Le membre cree depuis admin_members doit etre persiste avec son indicatif.');
      assert.equal(createdMember.email, createdEmail, 'Le membre cree depuis admin_members doit etre persiste avec son email.');
      assert.equal(createdMember.first_name, createdFirstName, 'Le membre cree depuis admin_members doit etre persiste avec son prenom.');
      assert.equal(createdMember.last_name, createdLastName, 'Le membre cree depuis admin_members doit etre persiste avec son nom.');
      assert.equal(createdMember.full_name, `${createdFirstName} ${createdLastName}`, 'Le membre cree depuis admin_members doit recalculer son nom complet.');
      assert.equal(createdMember.locator, 'JO20BB', 'Le membre cree depuis admin_members doit etre persiste avec son locator.');
      assert.equal(Number(createdMember.is_active), 1, 'Le membre cree depuis admin_members doit etre actif.');
      if (Object.prototype.hasOwnProperty.call(createdMember, 'auth_user_id')) {
        assert.ok(Number(createdMember.auth_user_id) > 0, 'Le membre cree depuis admin_members doit etre rattache a un compte auth.');
      }
      await visit(driver, 'admin_members', { member_q: createdCallsign });
      const createdMemberForm = await driver.findElement(By.xpath(`//input[@name="member_id" and @value="${createdMember.id}"]/ancestor::form[1]`));
      const createdCallsignValue = await createdMemberForm.findElement(By.css('input[name="callsign"]')).getAttribute('value');
      assert.equal(createdCallsignValue, createdCallsign, 'Le membre cree doit etre retrouvable dans la recherche admin.');

      const gradeForm = await driver.findElement(By.xpath(`//input[@name="action" and @value="add_member_grade"]/ancestor::form[input[@name="member_id" and @value="${createdMember.id}"]][1]`));
      await setFieldValue(driver, await gradeForm.findElement(By.css('input[name="grade_label"]')), 'HAREC Selenium');
      await setFieldValue(driver, await gradeForm.findElement(By.css('input[name="obtained_on"]')), '2026-06-15');
      await submitForm(driver, gradeForm);
      let relatedState = adminMemberRelatedState(createdMember.id);
      assert.equal(relatedState.grades.length, 1, 'Le grade ajoute depuis admin_members doit etre persiste.');
      assert.equal(relatedState.grades[0].grade_label, 'HAREC Selenium', 'Le libelle du grade doit etre conserve.');
      assert.equal(relatedState.grades[0].obtained_on, '2026-06-15', 'La date d obtention du grade doit etre conservee.');

      await visit(driver, 'admin_members', { member_q: createdCallsign });
      const paymentForm = await driver.findElement(By.xpath(`//input[@name="action" and @value="save_member_payment"]/ancestor::form[input[@name="member_id" and @value="${createdMember.id}"]][1]`));
      await setFieldValue(driver, await paymentForm.findElement(By.css('select[name="payment_period_type"]')), 'year');
      await setFieldValue(driver, await paymentForm.findElement(By.css('input[name="payment_year"]')), '2026');
      await setFieldValue(driver, await paymentForm.findElement(By.css('select[name="payment_status"]')), 'paid');
      await submitForm(driver, paymentForm);
      relatedState = adminMemberRelatedState(createdMember.id);
      assert.equal(relatedState.payments.length, 1, 'Le statut de paiement ajoute depuis admin_members doit etre persiste.');
      assert.equal(relatedState.payments[0].period_type, 'year', 'Le paiement doit conserver le mode annuel.');
      assert.equal(relatedState.payments[0].period_key, '2026', 'Le paiement doit conserver l annee cible.');
      assert.equal(relatedState.payments[0].status, 'paid', 'Le paiement doit conserver son etat.');

      await visit(driver, 'admin_members', { member_q: createdCallsign });
      const relatedSource = await driver.getPageSource();
      assert.match(relatedSource, /HAREC Selenium/, 'Le grade ajoute doit rester visible dans la gestion du membre.');
      assert.match(relatedSource, /2026/, 'La periode de paiement doit rester visible dans la gestion du membre.');
      assert.match(relatedSource, /mutual_form=1/, 'Le formulaire mutuelle doit etre disponible quand l annee est payee.');
      const deleteGradeForm = await driver.findElement(By.xpath(`//input[@name="action" and @value="delete_member_grade"]/ancestor::form[input[@name="grade_id" and @value="${relatedState.grades[0].id}"]][1]`));
      await submitForm(driver, deleteGradeForm);
      relatedState = adminMemberRelatedState(createdMember.id);
      assert.equal(relatedState.grades.length, 0, 'La suppression du grade doit etre persistante.');

      await visit(driver, 'admin_members', { member_q: createdCallsign });
      const deletePaymentForm = await driver.findElement(By.xpath(`//input[@name="action" and @value="delete_member_payment"]/ancestor::form[input[@name="payment_id" and @value="${relatedState.payments[0].id}"]][1]`));
      await submitForm(driver, deletePaymentForm);
      relatedState = adminMemberRelatedState(createdMember.id);
      assert.equal(relatedState.payments.length, 0, 'La suppression du statut de paiement doit etre persistante.');

      await visit(driver, 'admin_permissions');
      const assignForm = await driver.findElement(By.css('input[name="action"][value="assign_role"]'));
      const form = await assignForm.findElement(By.xpath('ancestor::form[1]'));
      await setFieldValue(driver, await form.findElement(By.css('select[name="member_id"]')), String(memberState.id));
      await setFieldValue(driver, await form.findElement(By.css('select[name="role_id"]')), String(role.id));
      await submitForm(driver, form);
      let assignmentCount = Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$stmt = db()->prepare('SELECT COUNT(*) FROM member_roles WHERE member_id = ? AND role_id = ?');
$stmt->execute([(int) getenv('SELENIUM_MEMBER_ID'), (int) getenv('SELENIUM_ROLE_ID')]);
echo (int) $stmt->fetchColumn();
`, { SELENIUM_MEMBER_ID: String(memberState.id), SELENIUM_ROLE_ID: String(role.id) }).trim());
      assert.equal(assignmentCount, 1, 'Le role temporaire doit etre affecte en base.');
      const roleText = await pagePlainText(driver);
      assert.match(roleText, new RegExp(role.label), 'Le role temporaire doit apparaitre dans les attributions.');

      const removeForm = await driver.findElement(By.xpath(`//input[@name="action" and @value="remove_role"]/ancestor::form[input[@name="member_id" and @value="${memberState.id}"] and input[@name="role_id" and @value="${role.id}"]][1]`));
      await submitForm(driver, removeForm);
      assignmentCount = Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$stmt = db()->prepare('SELECT COUNT(*) FROM member_roles WHERE member_id = ? AND role_id = ?');
$stmt->execute([(int) getenv('SELENIUM_MEMBER_ID'), (int) getenv('SELENIUM_ROLE_ID')]);
echo (int) $stmt->fetchColumn();
`, { SELENIUM_MEMBER_ID: String(memberState.id), SELENIUM_ROLE_ID: String(role.id) }).trim());
      assert.equal(assignmentCount, 0, 'Le role temporaire doit etre retire.');
    } finally {
      restoreModule(moduleOriginal);
      restoreAdminMember(memberState);
      cleanupCreatedAdminMember(createdCallsign, createdEmail);
      cleanupTemporaryRole(role);
    }
  });
});

test('Selenium admin configuration: flux live, presse et diner annuel', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const suffix = Date.now();
  const token = `SELENIUMCFG${suffix}`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  const feedCode = `selenium-feed-${suffix}`;
  const feedLabel = `Selenium feed ${suffix}`;
  const feedOriginal = liveFeedState(feedCode, feedLabel);
  const feedUpdatedLabel = `Selenium feed modifie ${suffix}`;
  const contactName = `Contact ${token}`;
  const releaseTitle = `Communique ${token}`;
  const dinnerName = `Reservation ${token}`;

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);

      await visit(driver, 'admin_live_feeds');
      const feedSection = await driver.findElement(By.xpath(`//strong[normalize-space(.)="${feedCode}"]/ancestor::section[1]`));
      const feedForm = await feedSection.findElement(By.xpath('ancestor::form[1]'));
      await setFieldValue(driver, await feedSection.findElement(By.css(`input[name="feeds[${feedOriginal.id}][label]"]`)), feedUpdatedLabel);
      await setFieldValue(driver, await feedSection.findElement(By.css(`textarea[name="feeds[${feedOriginal.id}][notes]"]`)), `Notes ${token}`);
      await submitForm(driver, feedForm);
      const feedAfter = liveFeedRecord(feedOriginal.id);
      assert.equal(feedAfter.label, feedUpdatedLabel, 'Le libelle du flux live doit etre mis a jour.');
      assert.equal(feedAfter.code, feedCode, 'La mise a jour du flux live doit conserver le code.');
      assert.equal(feedAfter.url, 'https://example.com/selenium-feed.json', 'La mise a jour du flux live doit conserver l URL.');
      assert.equal(feedAfter.parser, 'json', 'La mise a jour du flux live doit conserver le parser.');
      assert.equal(Number(feedAfter.cache_ttl), 120, 'La mise a jour du flux live doit conserver le TTL cache.');
      assert.equal(Number(feedAfter.refresh_seconds), 180, 'La mise a jour du flux live doit conserver la frequence de refresh.');
      assert.equal(Number(feedAfter.is_enabled), Number(feedOriginal.is_enabled), 'La mise a jour du flux live doit conserver l etat actif.');
      assert.match(String(feedAfter.notes || ''), new RegExp(token), 'Les notes du flux live doivent etre mises a jour.');

      await visit(driver, 'admin_press');
      const contactAction = await driver.findElement(By.css('input[name="action"][value="contact"]'));
      const contactForm = await contactAction.findElement(By.xpath('ancestor::form[1]'));
      await contactForm.findElement(By.css('input[name="full_name"]')).sendKeys(contactName);
      await contactForm.findElement(By.css('input[name="role_label"]')).sendKeys('Relation presse Selenium');
      await contactForm.findElement(By.css('input[name="email"]')).sendKeys(`press-${suffix}@example.test`);
      await contactForm.findElement(By.css('input[name="phone"]')).sendKeys('+320000000');
      await setFieldValue(driver, await contactForm.findElement(By.css('textarea[name="notes"]')), `Notes ${token}`);
      await submitForm(driver, contactForm);
      const contactAfter = pressContactState(contactName);
      assert.ok(contactAfter, 'Le contact presse doit etre cree en DB.');
      assert.equal(contactAfter.role_label, 'Relation presse Selenium', 'Le role du contact presse doit etre persiste.');
      assert.equal(contactAfter.email, `press-${suffix}@example.test`, 'L email du contact presse doit etre persiste.');
      assert.equal(contactAfter.phone, '+320000000', 'Le telephone du contact presse doit etre persiste.');
      assert.equal(contactAfter.notes, `Notes ${token}`, 'Les notes du contact presse doivent etre persistees.');
      assert.equal(Number(contactAfter.is_active), 1, 'Le contact presse cree doit etre actif.');
      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(contactName), 'Le contact presse doit apparaitre dans la liste admin.');

      const releaseAction = await driver.findElement(By.css('input[name="action"][value="release"]'));
      const releaseForm = await releaseAction.findElement(By.xpath('ancestor::form[1]'));
      await releaseForm.findElement(By.css('input[name="title"]')).sendKeys(releaseTitle);
      await setFieldValue(driver, await releaseForm.findElement(By.css('textarea[name="summary"]')), `Resume ${token}`);
      await setFieldValue(driver, await releaseForm.findElement(By.css('input[name="published_on"]')), '2026-06-18');
      await submitForm(driver, releaseForm);
      const releaseAfter = pressReleaseState(releaseTitle);
      assert.ok(releaseAfter, 'Le communique presse doit etre cree en DB.');
      assert.equal(releaseAfter.summary, `Resume ${token}`, 'Le resume du communique presse doit etre persiste.');
      assert.equal(releaseAfter.published_on, '2026-06-18', 'La date de publication du communique doit etre persistee.');
      assert.equal(releaseAfter.file_path, null, 'Sans upload, aucun fichier PDF ne doit etre associe.');
      assert.equal(Number(releaseAfter.is_published), 1, 'Le communique cree doit etre publie.');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(releaseTitle), 'Le communique presse doit apparaitre dans la liste admin.');

      await visit(driver, 'admin_dinner_reservations');
      const dinnerForm = await driver.findElement(By.css('#dinner-reservation-form'));
      await dinnerForm.findElement(By.css('input[name="reserved_by"]')).sendKeys(dinnerName);
      await driver.wait(async () => {
        const lines = await driver.findElements(By.css('.dinner-line'));
        return lines.length > 0;
      }, 5000);
      await setFieldValue(driver, await dinnerForm.findElement(By.css('.quantity-input')), '2');
      await setFieldValue(driver, await dinnerForm.findElement(By.css('textarea[name="notes"]')), `Notes ${token}`);
      await submitForm(driver, dinnerForm);
      const dinnerAfter = dinnerReservationState(dinnerName);
      assert.ok(dinnerAfter.reservation, 'La reservation diner doit etre creee en DB.');
      assert.equal(dinnerAfter.reservation.notes, `Notes ${token}`, 'Les notes diner doivent etre persistees.');
      assert.equal(Number(dinnerAfter.reservation.total_cents), 4800, 'Le total diner doit etre calcule et persiste.');
      assert.ok(dinnerAfter.line, 'La reservation diner doit creer une ligne en DB.');
      assert.equal(Number(dinnerAfter.line.starter_enabled), 0, 'L entree doit rester desactivee par defaut.');
      assert.equal(Number(dinnerAfter.line.meal_enabled), 1, 'Le plat doit etre active par defaut.');
      assert.equal(Number(dinnerAfter.line.dessert_enabled), 1, 'Le dessert doit etre active par defaut.');
      assert.equal(dinnerAfter.line.meal_code, 'vol_au_vent', 'Le plat par defaut doit etre persiste.');
      assert.equal(dinnerAfter.line.dessert_code, 'tiramisu', 'Le dessert par defaut doit etre persiste.');
      assert.equal(Number(dinnerAfter.line.quantity), 2, 'La quantite diner doit etre persistee.');
      assert.equal(Number(dinnerAfter.line.line_total_cents), 4800, 'Le total de ligne diner doit etre persiste.');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(dinnerName), 'La reservation diner doit apparaitre dans l historique.');
      assert.match(text, /2/, 'La quantite diner doit etre rendue dans l historique.');
    } finally {
      restoreLiveFeed(feedOriginal);
      cleanupAdminContent(token);
    }
  });
});
