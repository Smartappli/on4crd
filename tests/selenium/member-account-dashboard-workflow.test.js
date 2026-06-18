const test = require('node:test');
const {
  By,
  assert,
  routeUrl,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  pagePlainText,
  loginAsAdmin,
  requireAdminCredentials,
  runSeleniumPhp,
  timeoutMs,
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
      const option = Array.from(element.options).find((item) => item.value === value)
        || Array.from(element.options).find((item) => item.value !== '');
      if (option) {
        element.value = option.value;
      }
    } else {
      element.value = value;
    }
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
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

function memberByCallsign(callsign) {
  const member = seleniumJson(`
require_once 'app/bootstrap.php';
$callsign = strtoupper((string) getenv('SELENIUM_TARGET_CALLSIGN'));
$stmt = db()->prepare('SELECT * FROM members WHERE callsign = ? LIMIT 1');
$stmt->execute([$callsign]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TARGET_CALLSIGN: callsign });
  assert.ok(member && Number(member.id) > 0, `Membre Selenium introuvable pour ${callsign}.`);
  return member;
}

function restoreMemberProfile(state) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$state = json_decode((string) getenv('SELENIUM_MEMBER_STATE'), true);
if (is_array($state) && (int) ($state['id'] ?? 0) > 0) {
    $columns = [
        'first_name', 'last_name', 'full_name', 'phone', 'country', 'address', 'postal_code', 'qth',
        'locator', 'licence_class', 'operator_since', 'cq_zone', 'itu_zone', 'qsl_via',
        'lotw_username', 'eqsl_username', 'qrz_url', 'website', 'is_uba_member',
        'uba_member_number', 'station_equipment', 'antennas', 'favourite_bands',
        'favourite_modes', 'interests'
    ];
    $sets = [];
    $params = [];
    foreach ($columns as $column) {
        if (!table_has_column('members', $column)) {
            continue;
        }
        $sets[] = $column . ' = ?';
        if ($column === 'is_uba_member') {
            $params[] = (int) ($state[$column] ?? 0);
        } else {
            $value = $state[$column] ?? null;
            $params[] = $value !== '' ? $value : null;
        }
    }
    if ($sets !== []) {
        $params[] = (int) $state['id'];
        db()->prepare('UPDATE members SET ' . implode(', ', $sets) . ' WHERE id = ? LIMIT 1')->execute($params);
    }
}
`, { SELENIUM_MEMBER_STATE: JSON.stringify(state) });
}

function capturePreferences(memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/member_preferences.php';
ensure_member_preference_table();
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$keys = [
    'personalized_recommendations_enabled',
    'recommendations_signal_article_enabled',
    'recommendations_signal_wiki_enabled',
    'recommendations_signal_classified_enabled',
    'recommendations_signal_album_enabled',
    'recommendations_signal_library_enabled',
];
$placeholders = implode(',', array_fill(0, count($keys), '?'));
$stmt = db()->prepare('SELECT preference_key, preference_value FROM member_preferences WHERE member_id = ? AND preference_key IN (' . $placeholders . ') ORDER BY preference_key');
$stmt->execute(array_merge([$memberId], $keys));
echo json_encode($stmt->fetchAll() ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_MEMBER_ID: String(memberId) });
}

function restorePreferences(memberId, rows) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/member_preferences.php';
ensure_member_preference_table();
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$rows = json_decode((string) getenv('SELENIUM_PREFERENCE_ROWS'), true);
$keys = [
    'personalized_recommendations_enabled',
    'recommendations_signal_article_enabled',
    'recommendations_signal_wiki_enabled',
    'recommendations_signal_classified_enabled',
    'recommendations_signal_album_enabled',
    'recommendations_signal_library_enabled',
];
$placeholders = implode(',', array_fill(0, count($keys), '?'));
db()->prepare('DELETE FROM member_preferences WHERE member_id = ? AND preference_key IN (' . $placeholders . ')')
    ->execute(array_merge([$memberId], $keys));
if (is_array($rows)) {
    $insert = db()->prepare('INSERT INTO member_preferences (member_id, preference_key, preference_value) VALUES (?, ?, ?)');
    foreach ($rows as $row) {
        $key = (string) ($row['preference_key'] ?? '');
        if (in_array($key, $keys, true)) {
            $insert->execute([$memberId, $key, (string) ($row['preference_value'] ?? '')]);
        }
    }
}
`, {
    SELENIUM_MEMBER_ID: String(memberId),
    SELENIUM_PREFERENCE_ROWS: JSON.stringify(rows),
  });
}

function captureNewsletterRows(memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/newsletter.php';
newsletter_ensure_tables();
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$stmt = db()->prepare('SELECT * FROM newsletter_subscribers WHERE member_id = ? ORDER BY id');
$stmt->execute([$memberId]);
echo json_encode($stmt->fetchAll() ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_MEMBER_ID: String(memberId) });
}

function restoreNewsletterRows(memberId, rows, token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/newsletter.php';
newsletter_ensure_tables();
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$token = (string) getenv('SELENIUM_TOKEN');
$rows = json_decode((string) getenv('SELENIUM_NEWSLETTER_ROWS'), true);
$columns = array_map(static fn(array $column): string => (string) $column['Field'], db()->query('SHOW COLUMNS FROM newsletter_subscribers')->fetchAll() ?: []);
db()->prepare('DELETE FROM newsletter_subscribers WHERE member_id = ? OR email LIKE ?')->execute([$memberId, '%' . $token . '%']);
if (is_array($rows)) {
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $insertColumns = array_values(array_filter($columns, static fn(string $column): bool => array_key_exists($column, $row)));
        if ($insertColumns === []) {
            continue;
        }
        $quoted = array_map(static fn(string $column): string => chr(96) . str_replace(chr(96), chr(96) . chr(96), $column) . chr(96), $insertColumns);
        $placeholders = implode(',', array_fill(0, count($insertColumns), '?'));
        $values = array_map(static fn(string $column) => $row[$column], $insertColumns);
        db()->prepare('INSERT INTO newsletter_subscribers (' . implode(',', $quoted) . ') VALUES (' . $placeholders . ')')->execute($values);
    }
}
`, {
    SELENIUM_MEMBER_ID: String(memberId),
    SELENIUM_TOKEN: token,
    SELENIUM_NEWSLETTER_ROWS: JSON.stringify(rows),
  });
}

function captureDashboardRows(memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
if (!table_exists('dashboard_widgets')) {
    echo 'null';
    return;
}
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$stmt = db()->prepare('SELECT * FROM dashboard_widgets WHERE member_id = ? ORDER BY position ASC, id ASC');
$stmt->execute([$memberId]);
echo json_encode($stmt->fetchAll() ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_MEMBER_ID: String(memberId) });
}

function restoreDashboardRows(memberId, rows) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
if (!table_exists('dashboard_widgets')) {
    return;
}
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$rows = json_decode((string) getenv('SELENIUM_DASHBOARD_ROWS'), true);
$columns = array_map(static fn(array $column): string => (string) $column['Field'], db()->query('SHOW COLUMNS FROM dashboard_widgets')->fetchAll() ?: []);
db()->prepare('DELETE FROM dashboard_widgets WHERE member_id = ?')->execute([$memberId]);
if (is_array($rows)) {
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $insertColumns = array_values(array_filter($columns, static fn(string $column): bool => array_key_exists($column, $row)));
        $quoted = array_map(static fn(string $column): string => chr(96) . str_replace(chr(96), chr(96) . chr(96), $column) . chr(96), $insertColumns);
        $placeholders = implode(',', array_fill(0, count($insertColumns), '?'));
        $values = array_map(static fn(string $column) => $row[$column], $insertColumns);
        db()->prepare('INSERT INTO dashboard_widgets (' . implode(',', $quoted) . ') VALUES (' . $placeholders . ')')->execute($values);
    }
}
`, {
    SELENIUM_MEMBER_ID: String(memberId),
    SELENIUM_DASHBOARD_ROWS: JSON.stringify(rows || []),
  });
}

function prepareDashboardForMember(memberId) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
if (table_exists('dashboard_widget_settings')) {
    db()->prepare('REPLACE INTO dashboard_widget_settings (widget_key, is_enabled) VALUES ("welcome", 1), ("radio_clocks", 1)')->execute();
}
if (table_exists('dashboard_widgets')) {
    db()->prepare('DELETE FROM dashboard_widgets WHERE member_id = ?')->execute([(int) getenv('SELENIUM_MEMBER_ID')]);
    db()->prepare('INSERT INTO dashboard_widgets (member_id, widget_key, config_json, position) VALUES (?, "welcome", "{}", 0)')
        ->execute([(int) getenv('SELENIUM_MEMBER_ID')]);
}
`, { SELENIUM_MEMBER_ID: String(memberId) });
}

function dashboardWidgetKeys(memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
if (!table_exists('dashboard_widgets')) {
    echo '[]';
    return;
}
$stmt = db()->prepare('SELECT widget_key FROM dashboard_widgets WHERE member_id = ? ORDER BY position ASC, id ASC');
$stmt->execute([(int) getenv('SELENIUM_MEMBER_ID')]);
echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_MEMBER_ID: String(memberId) });
}

function captureWidgetSettings() {
  return seleniumJson(`
require_once 'app/bootstrap.php';
if (!table_exists('dashboard_widget_settings')) {
    echo '[]';
    return;
}
$stmt = db()->query('SELECT * FROM dashboard_widget_settings ORDER BY widget_key');
echo json_encode($stmt->fetchAll() ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`);
}

function restoreWidgetSettings(rows) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
db()->exec('CREATE TABLE IF NOT EXISTS dashboard_widget_settings (
    widget_key VARCHAR(120) PRIMARY KEY,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
$rows = json_decode((string) getenv('SELENIUM_WIDGET_SETTINGS'), true);
db()->exec('DELETE FROM dashboard_widget_settings');
if (is_array($rows)) {
    $insert = db()->prepare('REPLACE INTO dashboard_widget_settings (widget_key, is_enabled) VALUES (?, ?)');
    foreach ($rows as $row) {
        $insert->execute([(string) ($row['widget_key'] ?? ''), (int) ($row['is_enabled'] ?? 1)]);
    }
}
`, { SELENIUM_WIDGET_SETTINGS: JSON.stringify(rows) });
}

function dashboardTableExists() {
  return runSeleniumPhp(`
require_once 'app/bootstrap.php';
echo table_exists('dashboard_widgets') ? '1' : '0';
`).trim() === '1';
}

async function waitForSavedStatus(driver) {
  await driver.wait(async () => {
    const text = await driver.findElement(By.css('#dashboard-save-status')).getText().catch(() => '');
    return text !== '' && !/Saving|Sauvegarde en cours|Enregistrement/i.test(text);
  }, timeoutMs, 'Le statut de sauvegarde du tableau de bord doit etre renseigne.');
}

test('Selenium membre compte: profil, preferences et newsletter se modifient sans regression', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const member = memberByCallsign(credentials.username.toUpperCase());
  const preferencesBefore = capturePreferences(member.id);
  const newsletterBefore = captureNewsletterRows(member.id);
  const suffix = Date.now();
  const token = `selenium-settings-${suffix}`;

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);

      await visit(driver, 'profile');
      const profileForm = await driver.findElement(By.css('form[enctype="multipart/form-data"]'));
      await setFieldValue(driver, await profileForm.findElement(By.css('input[name="first_name"]')), 'Selenium');
      await setFieldValue(driver, await profileForm.findElement(By.css('input[name="last_name"]')), `Compte ${suffix}`);
      await setFieldValue(driver, await profileForm.findElement(By.css('select[name="country"]')), String(member.country || 'Belgium'));
      await setFieldValue(driver, await profileForm.findElement(By.css('input[name="qth"]')), `QTH Selenium ${suffix}`);
      await setFieldValue(driver, await profileForm.findElement(By.css('input[name="locator"]')), 'JO20AA');
      await setFieldValue(driver, await profileForm.findElement(By.css('textarea[name="station_equipment"]')), `Station Selenium ${suffix}`);
      await setFieldValue(driver, await profileForm.findElement(By.css('textarea[name="interests"]')), `Interets Selenium ${suffix}`);
      const geocodeBoxes = await profileForm.findElements(By.css('input[name="allow_geocode"]'));
      if (geocodeBoxes.length > 0) {
        await setCheckbox(driver, geocodeBoxes[0], false);
      }
      await submitForm(driver, profileForm);

      const profileAfter = memberByCallsign(credentials.username.toUpperCase());
      assert.equal(profileAfter.first_name, 'Selenium', 'Le prenom du profil doit etre persiste.');
      assert.equal(profileAfter.last_name, `Compte ${suffix}`, 'Le nom du profil doit etre persiste.');
      assert.equal(profileAfter.qth, `QTH Selenium ${suffix}`, 'Le QTH du profil doit etre persiste.');
      assert.equal(profileAfter.locator, 'JO20AA', 'Le locator du profil doit etre persiste.');
      assert.match(String(profileAfter.station_equipment || ''), new RegExp(String(suffix)), 'La station doit etre persistee.');

      await visit(driver, 'change_password');
      const passwordForm = await driver.findElement(By.xpath('//input[@name="current_password"]/ancestor::form[1]'));
      await passwordForm.findElement(By.css('input[name="current_password"]')).sendKeys('mot-de-passe-errone');
      await passwordForm.findElement(By.css('input[name="password"]')).sendKeys('SeleniumPassword!2026');
      await passwordForm.findElement(By.css('input[name="password_confirm"]')).sendKeys('SeleniumPassword!2026');
      await submitForm(driver, passwordForm);
      let text = await pagePlainText(driver);
      assert.match(text, /mot de passe|password|invalid|incorrect/i, 'Le changement avec mot de passe courant invalide doit rester controle.');

      await visit(driver, 'settings');
      let recommendationForm = await driver.findElement(By.css('input[name="action"][value="toggle_recommendations"]'));
      recommendationForm = await recommendationForm.findElement(By.xpath('ancestor::form[1]'));
      await setCheckbox(driver, await recommendationForm.findElement(By.css('input[name="recommendations_enabled"]')), false);
      await submitForm(driver, recommendationForm);
      let preferenceValue = runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/member_preferences.php';
echo member_preference_bool((int) getenv('SELENIUM_MEMBER_ID'), 'personalized_recommendations_enabled', true) ? '1' : '0';
`, { SELENIUM_MEMBER_ID: String(member.id) }).trim();
      assert.equal(preferenceValue, '0', 'La preference recommandations doit pouvoir etre desactivee.');

      await visit(driver, 'settings');
      const signalsAction = await driver.findElement(By.css('input[name="action"][value="toggle_recommendation_signals"]'));
      const signalsForm = await signalsAction.findElement(By.xpath('ancestor::form[1]'));
      const articleSignal = await signalsForm.findElement(By.css('input[name="signals[article]"]'));
      await setCheckbox(driver, articleSignal, true);
      for (const signal of ['wiki', 'classified', 'album', 'library']) {
        await setCheckbox(driver, await signalsForm.findElement(By.css(`input[name="signals[${signal}]"]`)), false);
      }
      await submitForm(driver, signalsForm);
      const signalState = seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/member_preferences.php';
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$keys = ['article', 'wiki', 'classified', 'album', 'library'];
$out = [];
foreach ($keys as $key) {
    $out[$key] = member_preference_bool($memberId, 'recommendations_signal_' . $key . '_enabled', true);
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_MEMBER_ID: String(member.id) });
      assert.deepEqual(signalState, { article: true, wiki: false, classified: false, album: false, library: false }, 'Les signaux de recommandations doivent etre persistés.');

      await visit(driver, 'settings');
      const unsubscribeButtons = await driver.findElements(By.xpath('//input[@name="newsletter_action" and @value="unsubscribe"]/ancestor::form[1]//button'));
      if (unsubscribeButtons.length > 0) {
        await submitForm(driver, await unsubscribeButtons[0].findElement(By.xpath('ancestor::form[1]')));
        await visit(driver, 'settings');
      }
      const subscribeAction = await driver.findElement(By.css('input[name="newsletter_action"][value="subscribe"]'));
      const subscribeForm = await subscribeAction.findElement(By.xpath('ancestor::form[1]'));
      await setFieldValue(driver, await subscribeForm.findElement(By.css('input[name="email"]')), `${token}@example.test`);
      await setCheckbox(driver, await subscribeForm.findElement(By.css('input[name="newsletter_consent"]')), true);
      await submitForm(driver, subscribeForm);
      const newsletterStatus = seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/newsletter.php';
newsletter_ensure_tables();
$stmt = db()->prepare('SELECT email, status, source FROM newsletter_subscribers WHERE member_id = ? AND email = ? LIMIT 1');
$stmt->execute([(int) getenv('SELENIUM_MEMBER_ID'), (string) getenv('SELENIUM_EMAIL')]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_MEMBER_ID: String(member.id), SELENIUM_EMAIL: `${token}@example.test` });
      assert.ok(newsletterStatus, 'L abonnement newsletter doit etre cree depuis les reglages.');
      assert.equal(newsletterStatus.status, 'active', 'L abonnement newsletter doit etre actif.');

      await visit(driver, 'settings');
      const unsubscribeAction = await driver.findElement(By.css('input[name="newsletter_action"][value="unsubscribe"]'));
      await submitForm(driver, await unsubscribeAction.findElement(By.xpath('ancestor::form[1]')));
      const afterUnsubscribe = seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/newsletter.php';
newsletter_ensure_tables();
$stmt = db()->prepare('SELECT status FROM newsletter_subscribers WHERE member_id = ? AND email = ? LIMIT 1');
$stmt->execute([(int) getenv('SELENIUM_MEMBER_ID'), (string) getenv('SELENIUM_EMAIL')]);
echo json_encode($stmt->fetchColumn() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_MEMBER_ID: String(member.id), SELENIUM_EMAIL: `${token}@example.test` });
      assert.equal(afterUnsubscribe, 'unsubscribed', 'Le desabonnement newsletter doit etre persiste.');
    } finally {
      restoreMemberProfile(member);
      restorePreferences(member.id, preferencesBefore);
      restoreNewsletterRows(member.id, newsletterBefore, token);
    }
  });
});

test('Selenium membre dashboard: widgets, rendu AJAX, sauvegarde et retour au catalogue', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!dashboardTableExists()) {
    t.skip('Table dashboard_widgets absente; scenario dashboard ignore.');
    return;
  }

  const member = memberByCallsign(credentials.username.toUpperCase());
  const dashboardBefore = captureDashboardRows(member.id);
  const settingsBefore = captureWidgetSettings();
  prepareDashboardForMember(member.id);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'widget_render', { widget: 'radio_clocks' });
      let text = await pagePlainText(driver);
      assert.match(text, /UTC/i, 'Le rendu AJAX du widget radio_clocks doit contenir UTC.');

      await visit(driver, 'dashboard');
      await driver.findElement(By.css('#open-widgets-panel')).click();
      await driver.wait(async () => {
        const hidden = await driver.findElement(By.css('#dashboard-widgets-panel')).getAttribute('aria-hidden');
        return hidden === 'false';
      }, timeoutMs, 'Le panneau widgets doit s ouvrir.');

      const addRadio = await driver.findElement(By.css('#dashboard-widgets-panel .add-widget[data-widget="radio_clocks"]'));
      await addRadio.click();
      await driver.wait(async () => {
        const cards = await driver.findElements(By.css('#dashboard-grid .widget-card[data-widget="radio_clocks"]'));
        return cards.length === 1;
      }, timeoutMs, 'Le widget radio_clocks doit etre ajoute au dashboard.');
      await waitForSavedStatus(driver);
      assert.deepEqual(dashboardWidgetKeys(member.id), ['welcome', 'radio_clocks'], 'La sauvegarde AJAX doit enregistrer le widget ajoute.');

      await driver.findElement(By.css('#close-widgets-panel')).click();
      await driver.wait(async () => {
        const hidden = await driver.findElement(By.css('#dashboard-widgets-panel')).getAttribute('aria-hidden');
        return hidden === 'true';
      }, timeoutMs, 'Le panneau widgets doit se fermer avant la suppression.');

      const radioCard = await driver.findElement(By.css('#dashboard-grid .widget-card[data-widget="radio_clocks"]'));
      await radioCard.findElement(By.css('.remove-widget')).click();
      await driver.wait(async () => {
        const cards = await driver.findElements(By.css('#dashboard-grid .widget-card[data-widget="radio_clocks"]'));
        return cards.length === 0;
      }, timeoutMs, 'Le widget radio_clocks doit etre retire du dashboard.');
      await waitForSavedStatus(driver);
      assert.deepEqual(dashboardWidgetKeys(member.id), ['welcome'], 'La sauvegarde AJAX doit retirer le widget supprime.');

      await driver.findElement(By.css('#open-widgets-panel')).click();
      await driver.wait(async () => {
        const hidden = await driver.findElement(By.css('#dashboard-widgets-panel')).getAttribute('aria-hidden');
        return hidden === 'false';
      }, timeoutMs, 'Le panneau widgets doit se rouvrir.');
      const returnedButtons = await driver.findElements(By.css('#dashboard-widgets-panel .add-widget[data-widget="radio_clocks"]'));
      assert.equal(returnedButtons.length, 1, 'Un widget supprime doit revenir dans le catalogue d ajout.');

      await visit(driver, 'admin_dashboard');
      const widgetForm = await driver.findElement(By.css('form.stack'));
      const radioToggle = await widgetForm.findElement(By.css('input[name="widget_radio_clocks"]'));
      await setCheckbox(driver, radioToggle, false);
      await submitForm(driver, widgetForm);
      const disabled = runSeleniumPhp(`
require_once 'app/bootstrap.php';
$stmt = db()->prepare('SELECT is_enabled FROM dashboard_widget_settings WHERE widget_key = "radio_clocks" LIMIT 1');
$stmt->execute();
echo (int) ($stmt->fetchColumn() ?: 0);
`).trim();
      assert.equal(disabled, '0', 'L admin doit pouvoir desactiver un widget dashboard.');
    } finally {
      restoreDashboardRows(member.id, dashboardBefore);
      restoreWidgetSettings(settingsBefore);
    }
  });
});
