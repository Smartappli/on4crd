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

function captureCommitteeState(callsign, required = true) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
$callsign = strtoupper(trim((string) (getenv('SELENIUM_COMMITTEE_CALLSIGN') ?: '')));
$stmt = db()->prepare('SELECT id, callsign, is_committee, committee_role, committee_bio, committee_sort_order FROM members WHERE callsign = ? LIMIT 1');
$stmt->execute([$callsign]);
$row = $stmt->fetch() ?: null;
echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_COMMITTEE_CALLSIGN: callsign });
  const state = JSON.parse(output || 'null');
  if (required) {
    assert.ok(state && Number(state.id) > 0, `Membre Selenium introuvable pour ${callsign}.`);
  }
  return state;
}

function ensureCommitteeFixture(callsign, fullName, sortOrder) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
$callsign = strtoupper(trim((string) (getenv('SELENIUM_COMMITTEE_CALLSIGN') ?: '')));
$fullName = trim((string) (getenv('SELENIUM_COMMITTEE_FULL_NAME') ?: 'Selenium Committee'));
$sortOrder = (int) (getenv('SELENIUM_COMMITTEE_SORT_ORDER') ?: 100);

if ($callsign === '' || !table_exists('members')) {
    echo json_encode(['ok' => false, 'reason' => 'members table missing or callsign empty'], JSON_THROW_ON_ERROR);
    return;
}
foreach (['is_active', 'is_committee', 'committee_role', 'committee_bio', 'committee_sort_order'] as $requiredColumn) {
    if (!table_has_column('members', $requiredColumn)) {
        echo json_encode(['ok' => false, 'reason' => 'members.' . $requiredColumn . ' missing'], JSON_THROW_ON_ERROR);
        return;
    }
}

$values = [
    'callsign' => $callsign,
    'full_name' => $fullName,
    'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
    'is_active' => 1,
    'is_committee' => 1,
    'committee_role' => 'Role Selenium fixture ' . $callsign,
    'committee_bio' => 'Bio Selenium fixture ' . $callsign,
    'committee_sort_order' => $sortOrder,
];
if (table_has_column('members', 'first_name')) {
    $values['first_name'] = 'Selenium';
}
if (table_has_column('members', 'last_name')) {
    $values['last_name'] = $callsign;
}
if (table_has_column('members', 'email')) {
    $values['email'] = null;
}
if (table_has_column('members', 'directory_hidden')) {
    $values['directory_hidden'] = 1;
}

$columns = [];
foreach ($values as $column => $value) {
    if (table_has_column('members', (string) $column)) {
        $columns[(string) $column] = $value;
    }
}
$quoteIdent = static fn(string $column): string => chr(96) . str_replace(chr(96), chr(96) . chr(96), $column) . chr(96);

$stmt = db()->prepare('SELECT id FROM members WHERE UPPER(callsign) = ? LIMIT 1');
$stmt->execute([$callsign]);
$memberId = (int) ($stmt->fetchColumn() ?: 0);
if ($memberId > 0) {
    $assignments = [];
    $params = [];
    foreach ($columns as $column => $value) {
        if ($column === 'password_hash') {
            continue;
        }
        $assignments[] = $quoteIdent($column) . ' = ?';
        $params[] = $value;
    }
    $params[] = $memberId;
    db()->prepare('UPDATE members SET ' . implode(', ', $assignments) . ' WHERE id = ? LIMIT 1')->execute($params);
} else {
    $insertColumns = array_keys($columns);
    $quotedColumns = array_map($quoteIdent, $insertColumns);
    $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));
    $params = array_map(static fn(string $column): mixed => $columns[$column], $insertColumns);
    db()->prepare('INSERT INTO members (' . implode(', ', $quotedColumns) . ') VALUES (' . $placeholders . ')')->execute($params);
    $memberId = (int) db()->lastInsertId();
}

echo json_encode(['ok' => $memberId > 0, 'member_id' => $memberId, 'callsign' => $callsign], JSON_THROW_ON_ERROR);
`, {
    SELENIUM_COMMITTEE_CALLSIGN: callsign,
    SELENIUM_COMMITTEE_FULL_NAME: fullName,
    SELENIUM_COMMITTEE_SORT_ORDER: String(sortOrder),
  });
  return JSON.parse(output || '{"ok":false,"reason":"empty output"}');
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

function deleteCommitteeFixture(callsign) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$callsign = strtoupper(trim((string) (getenv('SELENIUM_COMMITTEE_CALLSIGN') ?: '')));
if ($callsign !== '' && table_exists('members')) {
    db()->prepare('DELETE FROM members WHERE callsign = ? LIMIT 1')->execute([$callsign]);
}
`, { SELENIUM_COMMITTEE_CALLSIGN: callsign });
}

function prepareCommitteeMoveFixture(primaryId, secondaryId) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$primaryId = (int) (getenv('SELENIUM_PRIMARY_MEMBER_ID') ?: 0);
$secondaryId = (int) (getenv('SELENIUM_SECONDARY_MEMBER_ID') ?: 0);
if ($primaryId > 0 && $secondaryId > 0) {
    db()->prepare('UPDATE members SET is_committee = 1, committee_sort_order = 100000 WHERE id = ?')->execute([$primaryId]);
    db()->prepare('UPDATE members SET is_committee = 1, committee_role = ?, committee_bio = ?, committee_sort_order = ? WHERE id = ?')
        ->execute(['Role Selenium comite second', 'Bio Selenium comite second', 100010, $secondaryId]);
}
`, {
    SELENIUM_PRIMARY_MEMBER_ID: String(primaryId),
    SELENIUM_SECONDARY_MEMBER_ID: String(secondaryId),
  });
}

test('Selenium admin comite: modifier un membre et verifier l affichage public', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const primaryCallsign = 'SELCOM1';
  const secondaryCallsign = 'SELCOM2';
  const originalState = captureCommitteeState(primaryCallsign, false);
  const secondaryState = captureCommitteeState(secondaryCallsign, false);
  const primaryFixture = ensureCommitteeFixture(primaryCallsign, 'Selenium Committee Primary', 10);
  const secondaryFixture = ensureCommitteeFixture(secondaryCallsign, 'Selenium Committee Secondary', 20);
  if (!primaryFixture.ok || !secondaryFixture.ok) {
    t.skip(`Fixtures comite indisponibles: ${primaryFixture.reason || secondaryFixture.reason || 'raison inconnue'}`);
    return;
  }
  const testPrimaryState = captureCommitteeState(primaryCallsign);
  const testSecondaryState = captureCommitteeState(secondaryCallsign);
  const role = `Role Selenium comite ${Date.now()}`;
  const bio = `Bio Selenium comite ${Date.now()}`;

  try {
    await withSelenium(t, async (driver) => {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_committee', { member_id: testPrimaryState.id });

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
      `, form, testPrimaryState.id);
      await setFieldValue(driver, await form.findElement(By.css('input[name="committee_sort_order"]')), '7');
      await setFieldValue(driver, await form.findElement(By.css('input[name="committee_role"]')), role);
      await setFieldValue(driver, await form.findElement(By.css('textarea[name="committee_bio"]')), bio);
      await submitForm(driver, form);

      const savedPrimary = captureCommitteeState(primaryCallsign);
      assert.equal(Number(savedPrimary.is_committee), 1, 'La sauvegarde admin doit activer le membre dans le comite en DB.');
      assert.equal(savedPrimary.committee_role, role, 'La sauvegarde admin doit persister committee_role en DB.');
      assert.equal(savedPrimary.committee_bio, bio, 'La sauvegarde admin doit persister committee_bio en DB.');
      assert.equal(Number(savedPrimary.committee_sort_order), 7, 'La sauvegarde admin doit persister committee_sort_order en DB.');

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(role), 'Le role comite doit apparaitre dans le recapitulatif admin.');
      assert.match(text, new RegExp(primaryCallsign, 'i'), 'Le membre modifie doit rester visible en admin.');

      prepareCommitteeMoveFixture(testPrimaryState.id, testSecondaryState.id);
      await visit(driver, 'admin_committee', { member_id: testPrimaryState.id });
      const moveDownButton = await driver.findElement(By.css(`button[name="committee_move"][value="down:${testPrimaryState.id}"]`));
      await driver.executeScript(`
        const button = arguments[0];
        const form = document.getElementById(button.getAttribute('form'));
        if (form && typeof form.requestSubmit === 'function') {
          form.requestSubmit(button);
        } else if (form) {
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = button.name;
          hidden.value = button.value;
          form.appendChild(hidden);
          form.submit();
        }
      `, moveDownButton);
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      const movedPrimary = captureCommitteeState(primaryCallsign);
      const movedSecondary = captureCommitteeState(secondaryCallsign);
      assert.ok(
        Number(movedPrimary.committee_sort_order) > Number(movedSecondary.committee_sort_order),
        'Le bouton committee_move doit deplacer le membre vers le bas dans l ordre du comite.',
      );
      assert.equal(movedPrimary.committee_role, role, 'Le deplacement ne doit pas perdre le role persiste en DB.');
      assert.equal(movedPrimary.committee_bio, bio, 'Le deplacement ne doit pas perdre la bio persistee en DB.');

      await visit(driver, 'committee');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(primaryCallsign, 'i'), 'La page publique comite doit afficher le membre Selenium.');
      assert.match(text, new RegExp(role), 'La page publique comite doit afficher le role mis a jour.');
      assert.match(text, new RegExp(bio), 'La page publique comite doit afficher la bio mise a jour.');
    });
  } finally {
    if (originalState) {
      restoreCommitteeState(originalState);
    } else {
      deleteCommitteeFixture(primaryCallsign);
    }
    if (secondaryState) {
      restoreCommitteeState(secondaryState);
    } else {
      deleteCommitteeFixture(secondaryCallsign);
    }
  }
});
