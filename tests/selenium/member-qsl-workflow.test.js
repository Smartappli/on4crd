const test = require('node:test');
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

test('Selenium membre QSL: fond, creation manuelle, preview, export et suppression', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const suffix = Date.now();
  const qsoCall = `F${String(suffix).slice(-5)}SL`;
  const backgroundLabel = `selenium-qsl-bg-${suffix}`;
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
