const test = require('node:test');
const {
  By,
  assert,
  timeoutMs,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  pagePlainText,
  skipIfInstallWizard,
  elementExists,
  runSeleniumPhp,
} = require('./helpers');

function ensureDirectoryFixture() {
  try {
    const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';

if (!table_exists('members')) {
    echo json_encode(['ok' => false, 'reason' => 'members table missing'], JSON_THROW_ON_ERROR);
    return;
}

$callsign = 'SELENDIR';
$values = [
    'callsign' => $callsign,
    'first_name' => 'Selenium',
    'last_name' => 'Directory',
    'full_name' => 'Selenium Directory',
    'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
    'email' => null,
    'is_active' => 1,
    'directory_hidden' => 0,
    'licence_class' => 'on3',
    'country' => 'BE',
    'qth' => 'Durnal',
    'locator' => 'JO20LI',
    'operator_since' => '2024',
    'is_uba_member' => 1,
    'uba_member_number' => 'SEL-001',
    'favourite_bands' => '2 m, 70 cm',
    'favourite_modes' => 'FM, SSB',
    'station_equipment' => 'Selenium transceiver',
    'antennas' => 'Selenium antenna',
    'interests' => 'Selenium directory coverage',
];

foreach ([
    'visibility_full_name',
    'visibility_first_name',
    'visibility_last_name',
    'visibility_country',
    'visibility_qth',
    'visibility_locator',
    'visibility_licence_class',
    'visibility_operator_since',
    'visibility_uba',
    'visibility_favourite_bands',
    'visibility_favourite_modes',
    'visibility_station',
    'visibility_antennas',
    'visibility_interests',
] as $visibilityColumn) {
    $values[$visibilityColumn] = 'public';
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

echo json_encode(['ok' => $memberId > 0, 'callsign' => $callsign, 'licence' => 'on3', 'member_id' => $memberId], JSON_THROW_ON_ERROR);
`);

    return JSON.parse(output);
  } catch (error) {
    return { ok: false, reason: String(error && error.message ? error.message : error) };
  }
}

async function submitDirectorySearch(driver, query) {
  const form = await driver.findElement(By.css('.directory-search-panel'));
  const input = await form.findElement(By.css('input[name="q"]'));
  await input.clear();
  await input.sendKeys(query);
  await form.findElement(By.css('button[type="submit"]')).click();
  await waitForDocumentReady(driver);
  await assertNoServerError(driver);
}

async function submitDirectoryLicenceFilter(driver, licence) {
  const form = await driver.findElement(By.css('.directory-search-panel'));
  const select = await form.findElement(By.css('select[name="licence"]'));
  await driver.executeScript(`
    const select = arguments[0];
    select.value = arguments[1];
    select.dispatchEvent(new Event('change', { bubbles: true }));
  `, select, licence);
  await form.findElement(By.css('button[type="submit"]')).click();
  await waitForDocumentReady(driver);
  await assertNoServerError(driver);
}

test('Selenium annuaire public: recherche, filtre licence et etat vide', async (t) => {
  await withSelenium(t, async (driver) => {
    const fixture = ensureDirectoryFixture();
    if (!fixture.ok) {
      t.skip(`Fixture annuaire indisponible: ${fixture.reason || 'raison inconnue'}`);
      return;
    }

    await visit(driver, 'directory');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }

    assert.ok(await elementExists(driver, '.directory-search-panel input[name="q"]'));
    assert.ok(await elementExists(driver, '.directory-search-panel select[name="licence"]'));
    assert.ok(await elementExists(driver, '.directory-results'));

    await submitDirectorySearch(driver, fixture.callsign);
    assert.equal(
      await driver.findElement(By.css('.directory-search-panel input[name="q"]')).getAttribute('value'),
      fixture.callsign,
    );
    assert.ok(await elementExists(driver, '.directory-active-filters'));
    assert.match(await pagePlainText(driver), new RegExp(fixture.callsign));

    await visit(driver, 'directory');
    await driver.wait(
      async () => (await driver.findElements(By.css(`.directory-search-panel select[name="licence"] option[value="${fixture.licence}"]`))).length > 0,
      timeoutMs,
      'Le filtre de licence de la fixture annuaire doit etre disponible.',
    );
    await submitDirectoryLicenceFilter(driver, fixture.licence);
    assert.equal(
      await driver.findElement(By.css('.directory-search-panel select[name="licence"]')).getAttribute('value'),
      fixture.licence,
    );
    assert.ok(await elementExists(driver, '.directory-active-filters'));
    assert.match(await pagePlainText(driver), new RegExp(fixture.callsign));

    await visit(driver, 'directory', { q: `selenium-no-directory-match-${Date.now()}` });
    assert.ok(await elementExists(driver, '.directory-empty'));
    assert.ok(await elementExists(driver, '.directory-empty a[href*="route=directory"]'));
    await driver.findElement(By.css('.directory-empty a[href*="route=directory"]')).click();
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);
    assert.equal(await driver.findElement(By.css('.directory-search-panel input[name="q"]')).getAttribute('value'), '');
  });
});

test('Selenium education: page ecoles et fiche relais exposent leur contenu structure', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'schools');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }
    assert.ok(await elementExists(driver, '.hero-home'));
    assert.ok((await driver.findElements(By.css('.pill-row .pill'))).length >= 3);
    assert.equal((await driver.findElements(By.css('.grid-3 .feature-card'))).length, 3);

    await visit(driver, 'relais');
    const relaisText = await pagePlainText(driver);
    assert.ok((await driver.findElements(By.css('table tbody tr'))).length >= 10);
    assert.match(relaisText, /ON.?CRD/i);
    assert.match(relaisText, /145,575|145\.575/);
    assert.match(relaisText, /JO20LI/);
  });
});

test('Selenium reference radio: Code Q rend les entrees usuelles', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'code_q');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }

    const rows = await driver.findElements(By.css('table tbody tr'));
    assert.ok(rows.length >= 50, `Le Code Q doit exposer un tableau complet, recu: ${rows.length}.`);
    const text = await pagePlainText(driver);
    for (const code of ['QRA', 'QRZ', 'QSL', 'QSO', 'QTH']) {
      assert.match(text, new RegExp(`\\b${code}\\b`));
    }
  });
});

test('Selenium reference radio: Code CW couvre alphabet, chiffres et prosigns', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'code_cw');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }

    assert.ok(await elementExists(driver, '.code-cw-chart'));
    assert.ok(await elementExists(driver, '.code-cw-prosigns'));
    assert.ok((await driver.findElements(By.css('.code-cw-chart tbody tr'))).length >= 18);
    const text = await pagePlainText(driver);
    for (const fragment of ['A .-', 'B -...', '5 .....', '0 -----', 'AR .-.-.', 'SK ...-.-']) {
      assert.match(text, new RegExp(fragment.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
    }
  });
});

const bandplanExpectations = [
  ['bandplan_on3', ['3.500-3.600', '144.000-146.000', '10 W PEP', '145.500']],
  ['bandplan_on2', ['1.810-2.000', '14.000-14.350', '100 W PEP', '50 W PEP']],
  ['bandplan_harec', ['5.3515-5.3665', '24.890-24.990', '1500 W PEP', '120 W PEP']],
];

for (const [route, expectedFragments] of bandplanExpectations) {
  test(`Selenium reference radio: ${route} rend le tableau de bandes`, async (t) => {
    await withSelenium(t, async (driver) => {
      await visit(driver, route);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }

      const rows = await driver.findElements(By.css('table tbody tr'));
      assert.ok(rows.length >= 7, `${route} doit exposer plusieurs bandes, recu: ${rows.length}.`);
      const text = await pagePlainText(driver);
      assert.match(text, /IBPT|BIPT|Freq-FR\.pdf/i);
      for (const fragment of expectedFragments) {
        assert.match(text, new RegExp(fragment.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
      }
    });
  });
}
