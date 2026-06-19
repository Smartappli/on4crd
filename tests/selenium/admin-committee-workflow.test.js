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

function captureCommitteeState(callsign) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
$callsign = strtoupper(trim((string) (getenv('SELENIUM_COMMITTEE_CALLSIGN') ?: '')));
$stmt = db()->prepare('SELECT id, callsign, is_committee, committee_role, committee_bio, committee_sort_order FROM members WHERE callsign = ? LIMIT 1');
$stmt->execute([$callsign]);
$row = $stmt->fetch() ?: null;
echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_COMMITTEE_CALLSIGN: callsign });
  const state = JSON.parse(output || 'null');
  assert.ok(state && Number(state.id) > 0, `Membre Selenium introuvable pour ${callsign}.`);
  return state;
}

function restoreCommitteeState(state) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$state = json_decode((string) (getenv('SELENIUM_COMMITTEE_STATE') ?: 'null'), true);
if (is_array($state) && (int) ($state['id'] ?? 0) > 0) {
    db()->prepare('UPDATE members SET is_committee = ?, committee_role = ?, committee_bio = ?, committee_sort_order = ? WHERE id = ?')
        ->execute([
            (int) ($state['is_committee'] ?? 0),
            ($state['committee_role'] ?? null) !== '' ? $state['committee_role'] : null,
            ($state['committee_bio'] ?? null) !== '' ? $state['committee_bio'] : null,
            (int) ($state['committee_sort_order'] ?? 100),
            (int) $state['id'],
        ]);
}
`, { SELENIUM_COMMITTEE_STATE: JSON.stringify(state) });
}

test('Selenium admin comite: modifier un membre et verifier l affichage public', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const callsign = credentials.username.toUpperCase();
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  const originalState = captureCommitteeState(callsign);
  const role = `Role Selenium comite ${Date.now()}`;
  const bio = `Bio Selenium comite ${Date.now()}`;

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_committee', { member_id: originalState.id });

      const form = await driver.findElement(By.css('#admin-committee-form'));
      await driver.executeScript(`
        const form = arguments[0];
        const memberId = String(arguments[1]);
        const memberSelect = form.querySelector('select[name="member_id"]');
        memberSelect.value = memberId;
        memberSelect.dispatchEvent(new Event('change', { bubbles: true }));
        const checkbox = form.querySelector('input[name="is_committee"]');
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
      `, form, originalState.id);
      await setFieldValue(driver, await form.findElement(By.css('input[name="committee_sort_order"]')), '7');
      await setFieldValue(driver, await form.findElement(By.css('input[name="committee_role"]')), role);
      await setFieldValue(driver, await form.findElement(By.css('textarea[name="committee_bio"]')), bio);
      await submitForm(driver, form);

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(role), 'Le role comite doit apparaitre dans le recapitulatif admin.');
      assert.match(text, new RegExp(callsign, 'i'), 'Le membre modifie doit rester visible en admin.');

      await visit(driver, 'committee');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(callsign, 'i'), 'La page publique comite doit afficher le membre Selenium.');
      assert.match(text, new RegExp(role), 'La page publique comite doit afficher le role mis a jour.');
      assert.match(text, new RegExp(bio), 'La page publique comite doit afficher la bio mise a jour.');
    } finally {
      restoreCommitteeState(originalState);
    }
  });
});
