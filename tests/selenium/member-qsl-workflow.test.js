const test = require('node:test');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {
  By,
  until,
  assert,
  routeUrl,
  timeoutMs,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  pagePlainText,
  loginAsAdmin,
  requireAdminCredentials,
  ensureSeleniumRunnable,
  runSeleniumPhp,
  writeTinyPngFixture,
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

async function setInputValue(driver, input, value) {
  await driver.executeScript(`
    const input = arguments[0];
    const value = arguments[1];
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  `, input, value);
}

async function activateQslPanel(driver, target) {
  await driver.executeScript(`
    const link = document.querySelector('[data-qsl-nav-target="' + arguments[0] + '"]');
    if (link) {
      link.click();
    }
  `, target);
}

function cleanupQslRows(qsoCall, backgroundLabel) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$qsoCall = strtoupper(trim((string) (getenv('SELENIUM_QSL_CALL') ?: '')));
$backgroundLabel = trim((string) (getenv('SELENIUM_QSL_BACKGROUND') ?: ''));
$memberId = (int) (db()->query("SELECT id FROM members WHERE callsign = 'SELENIUMADMIN' LIMIT 1")->fetchColumn() ?: 0);
if ($memberId > 0 && $qsoCall !== '') {
    if (table_exists('qsl_cards')) {
        db()->prepare('DELETE FROM qsl_cards WHERE member_id = ? AND UPPER(qso_call) = ?')->execute([$memberId, $qsoCall]);
    }
    if (table_exists('qso_logs')) {
        db()->prepare('DELETE FROM qso_logs WHERE member_id = ? AND UPPER(qso_call) = ?')->execute([$memberId, $qsoCall]);
    }
}
if ($memberId > 0 && $backgroundLabel !== '' && table_exists('qsl_background_presets')) {
    db()->prepare('DELETE FROM qsl_background_presets WHERE member_id = ? AND label = ?')->execute([$memberId, $backgroundLabel]);
}
`, {
    SELENIUM_QSL_CALL: qsoCall,
    SELENIUM_QSL_BACKGROUND: backgroundLabel,
  });
}

function cleanupQslToken(qsoCall, token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$qsoCall = strtoupper(trim((string) (getenv('SELENIUM_QSL_CALL') ?: '')));
$token = trim((string) (getenv('SELENIUM_TOKEN') ?: ''));
$memberId = (int) (db()->query("SELECT id FROM members WHERE callsign = 'SELENIUMADMIN' LIMIT 1")->fetchColumn() ?: 0);
if ($memberId > 0 && $qsoCall !== '') {
    if (table_exists('qsl_cards')) {
        db()->prepare('DELETE FROM qsl_cards WHERE member_id = ? AND UPPER(qso_call) = ?')->execute([$memberId, $qsoCall]);
    }
    if (table_exists('qso_logs')) {
        db()->prepare('DELETE FROM qso_logs WHERE member_id = ? AND UPPER(qso_call) = ?')->execute([$memberId, $qsoCall]);
    }
}
if ($memberId > 0 && $token !== '' && table_exists('qsl_background_presets')) {
    db()->prepare('DELETE FROM qsl_background_presets WHERE member_id = ? AND label LIKE ?')->execute([$memberId, '%' . $token . '%']);
}
`, {
    SELENIUM_QSL_CALL: qsoCall,
    SELENIUM_TOKEN: token,
  });
}

function qslState(qsoCall, token) {
  return JSON.parse(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$qsoCall = strtoupper(trim((string) (getenv('SELENIUM_QSL_CALL') ?: '')));
$token = trim((string) (getenv('SELENIUM_TOKEN') ?: ''));
$memberId = (int) (db()->query("SELECT id FROM members WHERE callsign = 'SELENIUMADMIN' LIMIT 1")->fetchColumn() ?: 0);
$out = ['backgrounds' => [], 'qsos' => [], 'cards' => []];
if ($memberId > 0 && $token !== '' && table_exists('qsl_background_presets')) {
    $stmt = db()->prepare('SELECT label, type, is_default FROM qsl_background_presets WHERE member_id = ? AND label LIKE ? ORDER BY id ASC');
    $stmt->execute([$memberId, '%' . $token . '%']);
    $out['backgrounds'] = $stmt->fetchAll() ?: [];
}
if ($memberId > 0 && $qsoCall !== '' && table_exists('qso_logs')) {
    $stmt = db()->prepare('SELECT qso_call, qso_date, time_on, band, mode FROM qso_logs WHERE member_id = ? AND UPPER(qso_call) = ? ORDER BY id ASC');
    $stmt->execute([$memberId, $qsoCall]);
    $out['qsos'] = $stmt->fetchAll() ?: [];
}
if ($memberId > 0 && $qsoCall !== '' && table_exists('qsl_cards')) {
    $stmt = db()->prepare('SELECT qso_call, template_name, svg_content FROM qsl_cards WHERE member_id = ? AND UPPER(qso_call) = ? ORDER BY id ASC');
    $stmt->execute([$memberId, $qsoCall]);
    $out['cards'] = $stmt->fetchAll() ?: [];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, {
    SELENIUM_QSL_CALL: qsoCall,
    SELENIUM_TOKEN: token,
  }) || '{}');
}

function writeAdifFixture(token, qsoCall) {
  const dir = path.join(os.tmpdir(), 'on4crd-selenium-fixtures');
  fs.mkdirSync(dir, { recursive: true });
  const filePath = path.join(dir, `${token.toLowerCase()}.adi`);
  const field = (name, value) => `<${name}:${String(value).length}>${value}`;
  const content = [
    field('CALL', qsoCall),
    field('QSO_DATE', '20260618'),
    field('TIME_ON', '1542'),
    field('BAND', '20M'),
    field('MODE', 'SSB'),
    field('RST_SENT', '59'),
    field('RST_RCVD', '57'),
    field('COMMENT', `Selenium ${token}`),
    '<EOR>',
  ].join('');
  fs.writeFileSync(filePath, content, 'utf8');

  return filePath;
}

test('Selenium membre QSL: fond, creation manuelle, preview, export et suppression', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const suffix = Date.now();
  const qsoCall = `F${String(suffix).slice(-5)}SL`;
  const backgroundLabel = `selenium-qsl-bg-${suffix}`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupQslRows(qsoCall, backgroundLabel);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'qsl');

      const gradientForm = await driver.findElement(By.css('form[data-preview-form="gradient"]'));
      await gradientForm.findElement(By.css('input[name="gradient_label"]')).sendKeys(backgroundLabel);
      await setInputValue(driver, await gradientForm.findElement(By.css('input[name="background_primary"]')), '#123456');
      await setInputValue(driver, await gradientForm.findElement(By.css('input[name="background_secondary"]')), '#ABCDEF');
      await driver.executeScript(`
        const checkbox = arguments[0];
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
      `, await gradientForm.findElement(By.css('input[name="set_default"]')));
      await submitForm(driver, gradientForm);

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(backgroundLabel), 'Le fond QSL cree doit apparaitre dans la table des fonds.');

      const manualForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="create_manual"]]'));
      await setInputValue(driver, await manualForm.findElement(By.css('input[name="qso_call"]')), qsoCall);
      await setInputValue(driver, await manualForm.findElement(By.css('input[name="qso_date"]')), '2026-06-18');
      await setInputValue(driver, await manualForm.findElement(By.css('input[name="time_on"]')), '14:35');
      await setInputValue(driver, await manualForm.findElement(By.css('input[name="band"]')), '20M');
      await setInputValue(driver, await manualForm.findElement(By.css('input[name="mode"]')), 'SSB');
      await setInputValue(driver, await manualForm.findElement(By.css('input[name="rst_sent"]')), '59');
      await setInputValue(driver, await manualForm.findElement(By.css('input[name="rst_recv"]')), '57');
      await setInputValue(driver, await manualForm.findElement(By.css('textarea[name="comment"]')), 'Selenium QSL regression');
      await driver.executeScript(`
        const form = arguments[0];
        const background = form.querySelector('select[name="background_preset_id"]');
        const preset = Array.from(background.options).find((option) => option.textContent.includes(arguments[1]));
        if (preset) {
          background.value = preset.value;
          background.dispatchEvent(new Event('change', { bubbles: true }));
        }
        const template = form.querySelector('select[name="template_name"]');
        template.value = 'classic_duplex';
        template.dispatchEvent(new Event('change', { bubbles: true }));
      `, manualForm, backgroundLabel);
      await submitForm(driver, manualForm);

      await activateQslPanel(driver, 'manage');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(qsoCall, 'i'), 'La carte QSL generee doit apparaitre dans la gestion.');
      assert.match(text, /Selenium QSL regression|recto|front|verso|back/i, 'Le parcours recto/verso doit rester visible apres creation.');

      await visit(driver, 'qsl', { qsl_search: qsoCall });
      await activateQslPanel(driver, 'manage');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(qsoCall, 'i'), 'La recherche QSL doit retrouver la carte generee.');

      const previewLink = await driver.findElement(By.xpath(`//tr[.//*[contains(translate(normalize-space(.), "abcdefghijklmnopqrstuvwxyz", "ABCDEFGHIJKLMNOPQRSTUVWXYZ"), "${qsoCall.toUpperCase()}")]]//a[contains(@href,"route=qsl_preview")]`));
      await driver.get(await previewLink.getAttribute('href'));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(qsoCall, 'i'), 'La page de preview doit afficher la QSL demandee.');

      const frontExportLink = await driver.findElement(By.css('a[href*="route=qsl_export"][href*="id="]:not([href*="side=back"])'));
      await driver.get(await frontExportLink.getAttribute('href'));
      await waitForDocumentReady(driver);
      let source = await driver.getPageSource();
      assert.match(source, /<svg[\s>]/i, 'L export recto doit rendre un SVG.');
      assert.match(source, new RegExp(qsoCall, 'i'), 'Le SVG recto doit contenir l indicatif QSO.');

      await driver.get(routeUrl('qsl'));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      await activateQslPanel(driver, 'manage');
      const deleteQslForm = await driver.findElement(By.xpath(`//tr[.//*[contains(translate(normalize-space(.), "abcdefghijklmnopqrstuvwxyz", "ABCDEFGHIJKLMNOPQRSTUVWXYZ"), "${qsoCall.toUpperCase()}")]]//form[.//input[@name="action" and @value="delete_qsl"]]`));
      await submitForm(driver, deleteQslForm);

      await activateQslPanel(driver, 'manage');
      text = await pagePlainText(driver);
      assert.doesNotMatch(text, new RegExp(qsoCall, 'i'), 'La QSL supprimee ne doit plus apparaitre.');

      await activateQslPanel(driver, 'design');
      const deleteBackgroundForm = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${backgroundLabel}")]]//form[.//button[@name="action" and @value="delete_background"]]`));
      const deleteButton = await deleteBackgroundForm.findElement(By.css('button[name="action"][value="delete_background"]'));
      await driver.executeScript(`
        const form = arguments[0];
        const button = arguments[1];
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit(button);
        } else {
          button.click();
        }
      `, deleteBackgroundForm, deleteButton);
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      await activateQslPanel(driver, 'design');
      text = await pagePlainText(driver);
      assert.doesNotMatch(text, new RegExp(backgroundLabel), 'Le fond QSL supprime ne doit plus apparaitre.');
    } finally {
      cleanupQslRows(qsoCall, backgroundLabel);
    }
  });
});

test('Selenium membre QSL: import ADIF, generation groupee et fonds avances', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const suffix = Date.now();
  const token = `SELENIUMQSLADV${suffix}`;
  const qsoCall = `F${String(suffix).slice(-5)}QA`;
  const imageLabel = `${token}-image`;
  const solidLabel = `${token}-solid`;
  const paletteLabel = `${token}-palette`;
  const pngPath = writeTinyPngFixture(`${token.toLowerCase()}.png`);
  const adifPath = writeAdifFixture(token, qsoCall);
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupQslToken(qsoCall, token);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'qsl');
      await activateQslPanel(driver, 'design');

      const imageForm = await driver.findElement(By.css('form[data-preview-form="image"]'));
      await setInputValue(driver, await imageForm.findElement(By.css('input[name="background_label"]')), imageLabel);
      const imageInput = await imageForm.findElement(By.css('input[name="background_image"]'));
      await driver.executeScript('arguments[0].hidden = false; arguments[0].style.display = "block";', imageInput);
      await imageInput.sendKeys(pngPath);
      await submitForm(driver, imageForm);

      await activateQslPanel(driver, 'design');
      const solidForm = await driver.findElement(By.css('form[data-preview-form="solid"]'));
      await setInputValue(driver, await solidForm.findElement(By.css('input[name="solid_label"]')), solidLabel);
      await setInputValue(driver, await solidForm.findElement(By.css('input[name="background_solid"]')), '#225588');
      await submitForm(driver, solidForm);

      await activateQslPanel(driver, 'design');
      const paletteForm = await driver.findElement(By.css('form[data-preview-form="palette"]'));
      await setInputValue(driver, await paletteForm.findElement(By.css('input[name="palette_label"]')), paletteLabel);
      await submitForm(driver, paletteForm);

      await activateQslPanel(driver, 'design');
      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(imageLabel), 'Le fond image QSL doit apparaitre.');
      assert.match(text, new RegExp(solidLabel), 'Le fond uni QSL doit apparaitre.');
      assert.match(text, new RegExp(paletteLabel), 'Le fond palette QSL doit apparaitre.');

      const defaultImageForm = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${imageLabel}")]]//form[.//button[@name="action" and @value="set_default_background"]]`));
      const defaultImageButton = await defaultImageForm.findElement(By.css('button[name="action"][value="set_default_background"]'));
      await driver.executeScript(`
        const form = arguments[0];
        const button = arguments[1];
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit(button);
        } else {
          button.click();
        }
      `, defaultImageForm, defaultImageButton);
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      let state = qslState(qsoCall, token);
      assert.equal(state.backgrounds.length, 3, 'Les trois fonds QSL avances doivent etre persistés.');
      assert.equal(state.backgrounds.some((row) => row.label === imageLabel && Number(row.is_default) === 1), true, 'Le fond image doit pouvoir devenir le fond par defaut.');

      await activateQslPanel(driver, 'adif');
      const adifForm = await driver.findElement(By.css('#adif-dropzone-form'));
      const adifInput = await adifForm.findElement(By.css('input[name="adif_files[]"]'));
      await driver.executeScript('arguments[0].hidden = false; arguments[0].style.display = "block";', adifInput);
      await adifInput.sendKeys(adifPath);
      await submitForm(driver, adifForm);

      state = qslState(qsoCall, token);
      assert.equal(state.qsos.length, 1, 'L import ADIF doit creer le QSO.');
      assert.equal(state.qsos[0].band, '20M', 'Le QSO importe doit conserver la bande.');
      assert.equal(state.qsos[0].mode, 'SSB', 'Le QSO importe doit conserver le mode.');

      await visit(driver, 'qsl', { qso_search: qsoCall });
      await activateQslPanel(driver, 'manage');
      const batchForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="generate_batch"]]'));
      await setInputValue(driver, await batchForm.findElement(By.css('select[name="qsl_template_name"]')), 'classic_duplex');
      const qsoCheckbox = await batchForm.findElement(By.css(`input[name="qso_ids[]"]`));
      await driver.executeScript(`
        const checkbox = arguments[0];
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('input', { bubbles: true }));
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
      `, qsoCheckbox);
      const selectedQsoIds = await driver.executeScript('return Array.from(new FormData(arguments[0]).getAll("qso_ids[]"));', batchForm);
      assert.equal(selectedQsoIds.length, 1, 'Le formulaire de generation groupee doit contenir le QSO selectionne.');
      const generateButton = await batchForm.findElement(By.xpath('.//p/button'));
      await driver.executeScript(`
        const form = arguments[0];
        const button = arguments[1];
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit(button);
        } else {
          form.submit();
        }
      `, batchForm, generateButton);
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      state = qslState(qsoCall, token);
      assert.equal(state.cards.length, 1, 'La generation groupee doit creer une carte QSL.');
      assert.equal(state.cards[0].template_name, 'classic_duplex', 'La generation groupee doit respecter le format recto verso.');
      assert.match(state.cards[0].svg_content, new RegExp(qsoCall), 'La carte generee doit contenir l indicatif importe.');

      await activateQslPanel(driver, 'manage');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(qsoCall, 'i'), 'La carte generee depuis ADIF doit etre visible dans la gestion QSL.');
    } finally {
      cleanupQslToken(qsoCall, token);
    }
  });
});
