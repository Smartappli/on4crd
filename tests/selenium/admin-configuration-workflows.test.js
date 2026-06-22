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
$columns = ['id', 'callsign', 'full_name', 'email', 'locator', 'is_active', 'is_committee'];
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

function restoreAdminMember(state) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$state = json_decode((string) getenv('SELENIUM_MEMBER_STATE'), true);
if (is_array($state) && (int) ($state['id'] ?? 0) > 0) {
    $columns = ['callsign = ?', 'full_name = ?', 'email = ?', 'locator = ?', 'is_active = ?', 'is_committee = ?'];
    $params = [
        (string) $state['callsign'],
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
  const updatedName = `Selenium Admin ${Date.now()}`;
  const updatedLocator = 'JO20AA';
  const newVisibility = moduleOriginal.visibility === 'members' ? 'public' : 'members';

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
      assert.equal(moduleAfter.visibility, newVisibility, 'La visibilite du module doit etre persistee.');

      await visit(driver, 'admin_members', { member_q: callsign });
      const memberForm = await driver.findElement(By.xpath(`//input[@name="member_id" and @value="${memberState.id}"]/ancestor::form[1]`));
      await setFieldValue(driver, await memberForm.findElement(By.css('input[name="full_name"]')), updatedName);
      await setFieldValue(driver, await memberForm.findElement(By.css('input[name="locator"]')), updatedLocator);
      await submitForm(driver, memberForm);
      const updatedMember = adminMemberState(callsign);
      assert.equal(updatedMember.full_name, updatedName, 'Le nom modifie doit etre persiste en base.');
      assert.equal(updatedMember.locator, updatedLocator, 'Le locator modifie doit etre persiste en base.');
      const source = await driver.getPageSource();
      assert.match(source, new RegExp(updatedName), 'Le nom modifie doit rester rendu dans le formulaire admin_members.');
      assert.match(source, new RegExp(updatedLocator), 'Le locator modifie doit rester rendu dans le formulaire admin_members.');

      await visit(driver, 'admin_permissions');
      const assignForm = await driver.findElement(By.css('input[name="action"][value="assign_role"]'));
      const form = await assignForm.findElement(By.xpath('ancestor::form[1]'));
      await setFieldValue(driver, await form.findElement(By.css('select[name="member_id"]')), String(memberState.id));
      await setFieldValue(driver, await form.findElement(By.css('select[name="role_id"]')), String(role.id));
      await submitForm(driver, form);
      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(role.label), 'Le role temporaire doit apparaitre dans les attributions.');

      const removeForm = await driver.findElement(By.xpath(`//input[@name="action" and @value="remove_role"]/ancestor::form[input[@name="member_id" and @value="${memberState.id}"] and input[@name="role_id" and @value="${role.id}"]][1]`));
      await submitForm(driver, removeForm);
      const assignmentCount = Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$stmt = db()->prepare('SELECT COUNT(*) FROM member_roles WHERE member_id = ? AND role_id = ?');
$stmt->execute([(int) getenv('SELENIUM_MEMBER_ID'), (int) getenv('SELENIUM_ROLE_ID')]);
echo (int) $stmt->fetchColumn();
`, { SELENIUM_MEMBER_ID: String(memberState.id), SELENIUM_ROLE_ID: String(role.id) }).trim());
      assert.equal(assignmentCount, 0, 'Le role temporaire doit etre retire.');
    } finally {
      restoreModule(moduleOriginal);
      restoreAdminMember(memberState);
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
      const feedAfter = seleniumJson(`
require_once 'app/bootstrap.php';
$stmt = db()->prepare('SELECT label, notes FROM live_feeds WHERE id = ? LIMIT 1');
$stmt->execute([(int) getenv('SELENIUM_FEED_ID')]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_FEED_ID: String(feedOriginal.id) });
      assert.equal(feedAfter.label, feedUpdatedLabel, 'Le libelle du flux live doit etre mis a jour.');
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
      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(contactName), 'Le contact presse doit apparaitre dans la liste admin.');

      const releaseAction = await driver.findElement(By.css('input[name="action"][value="release"]'));
      const releaseForm = await releaseAction.findElement(By.xpath('ancestor::form[1]'));
      await releaseForm.findElement(By.css('input[name="title"]')).sendKeys(releaseTitle);
      await setFieldValue(driver, await releaseForm.findElement(By.css('textarea[name="summary"]')), `Resume ${token}`);
      await setFieldValue(driver, await releaseForm.findElement(By.css('input[name="published_on"]')), '2026-06-18');
      await submitForm(driver, releaseForm);
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
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(dinnerName), 'La reservation diner doit apparaitre dans l historique.');
      assert.match(text, /2/, 'La quantite diner doit etre rendue dans l historique.');
    } finally {
      restoreLiveFeed(feedOriginal);
      cleanupAdminContent(token);
    }
  });
});
