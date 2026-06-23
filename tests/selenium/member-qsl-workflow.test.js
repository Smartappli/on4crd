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
  ensureSeleniumRunnable,
  runSeleniumPhp,
} = require('./helpers');

const TINY_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAYAAACp8Z5+AAAAEklEQVR4nGPQz3/7HxkzkC4AAE5fKKFmq1FQAAAAAElFTkSuQmCC';

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

async function postMultipartFile(driver, form, fileFieldName, fileName, payload, mimeType, isBase64 = false) {
  const result = await driver.executeAsyncScript(`
    const form = arguments[0];
    const fileFieldName = arguments[1];
    const fileName = arguments[2];
    const payload = arguments[3];
    const mimeType = arguments[4];
    const isBase64 = arguments[5];
    const done = arguments[arguments.length - 1];
    const data = new FormData(form);
    data.delete(fileFieldName);
    const body = isBase64
      ? Uint8Array.from(atob(payload), (char) => char.charCodeAt(0))
      : payload;
    data.append(fileFieldName, new File([body], fileName, { type: mimeType }));
    fetch(form.getAttribute('action') || window.location.href, {
      method: 'POST',
      body: data,
      credentials: 'same-origin',
      redirect: 'follow',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(async (response) => {
      const bodyText = await response.text();
      let json = null;
      try {
        json = JSON.parse(bodyText);
      } catch (error) {}
      return {
        ok: response.ok && (!json || json.ok !== false),
        status: response.status,
        url: response.url,
        body: bodyText,
        json
      };
    }).catch((error) => ({
      ok: false,
      status: 0,
      url: '',
      body: String(error),
      json: null
    })).then(done);
  `, form, fileFieldName, fileName, payload, mimeType, isBase64);

  assert.equal(
    result.ok,
    true,
    `Le POST multipart QSL doit repondre en succes HTTP, recu ${result.status}: ${String(result.body).slice(0, 240)}`,
  );
  assert.doesNotMatch(
    String(result.body),
    /Une erreur interne|Internal Server Error|HTTP ERROR 500|HTTP ERROR 503|Erreur 503|Service Unavailable/i,
    'La reponse multipart QSL ne doit pas contenir d erreur serveur.',
  );

  return result;
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
    $stmt = db()->prepare('SELECT id, label, type, image_data_uri, color_primary, color_secondary, is_default, created_at FROM qsl_background_presets WHERE member_id = ? AND label LIKE ? ORDER BY id ASC');
    $stmt->execute([$memberId, '%' . $token . '%']);
    $out['backgrounds'] = $stmt->fetchAll() ?: [];
}
if ($memberId > 0 && $qsoCall !== '' && table_exists('qso_logs')) {
    $stmt = db()->prepare('SELECT id, qso_call, qso_date, time_on, band, mode, rst_sent, rst_recv, raw_payload, created_at FROM qso_logs WHERE member_id = ? AND UPPER(qso_call) = ? ORDER BY id ASC');
    $stmt->execute([$memberId, $qsoCall]);
    $out['qsos'] = $stmt->fetchAll() ?: [];
}
if ($memberId > 0 && $qsoCall !== '' && table_exists('qsl_cards')) {
    $stmt = db()->prepare('SELECT id, title, qso_call, qso_date, time_on, band, mode, rst_sent, rst_recv, template_name, svg_content, created_at FROM qsl_cards WHERE member_id = ? AND UPPER(qso_call) = ? ORDER BY id ASC');
    $stmt->execute([$memberId, $qsoCall]);
    $out['cards'] = $stmt->fetchAll() ?: [];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, {
    SELENIUM_QSL_CALL: qsoCall,
    SELENIUM_TOKEN: token,
  }) || '{}');
}

function buildAdifFixture(token, qsoCall) {
  const field = (name, value) => `<${name}:${String(value).length}>${value}`;
  return [
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
      let state = qslState(qsoCall, backgroundLabel);
      assert.equal(state.backgrounds.length, 1, 'Le fond gradient QSL doit etre persiste en DB.');
      assert.equal(state.backgrounds[0].label, backgroundLabel, 'Le libelle du fond gradient doit etre persiste.');
      assert.equal(state.backgrounds[0].type, 'gradient', 'Le type du fond gradient doit etre persiste.');
      assert.equal(String(state.backgrounds[0].color_primary || '').toLowerCase(), '#123456', 'La couleur primaire gradient doit etre persistee.');
      assert.equal(String(state.backgrounds[0].color_secondary || '').toLowerCase(), '#abcdef', 'La couleur secondaire gradient doit etre persistee.');
      assert.equal(Number(state.backgrounds[0].is_default), 1, 'Le fond gradient doit etre marque par defaut.');
      assert.ok(String(state.backgrounds[0].created_at || '') !== '', 'Le fond gradient doit etre horodate.');

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
      state = qslState(qsoCall, backgroundLabel);
      assert.equal(state.cards.length, 1, 'La creation manuelle doit creer une carte QSL en DB.');
      assert.equal(state.cards[0].qso_call, qsoCall.toUpperCase(), 'La carte manuelle doit stocker l indicatif QSO.');
      assert.equal(String(state.cards[0].qso_date), '20260618', 'La carte manuelle doit stocker la date QSO au format ADIF.');
      assert.equal(String(state.cards[0].time_on), '1435', 'La carte manuelle doit stocker l heure QSO au format ADIF.');
      assert.equal(state.cards[0].band, '20M', 'La carte manuelle doit stocker la bande.');
      assert.equal(state.cards[0].mode, 'SSB', 'La carte manuelle doit stocker le mode.');
      assert.equal(state.cards[0].rst_sent, '59', 'La carte manuelle doit stocker le RST envoye.');
      assert.equal(state.cards[0].rst_recv, '57', 'La carte manuelle doit stocker le RST recu.');
      assert.equal(state.cards[0].template_name, 'classic_duplex', 'La carte manuelle doit stocker le template choisi.');
      assert.match(state.cards[0].svg_content, /QSL recto|front/i, 'Le SVG manuel stocke doit correspondre au recto.');
      assert.match(state.cards[0].svg_content, /#123456/i, 'Le SVG manuel doit utiliser la couleur primaire du fond.');

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
      state = qslState(qsoCall, backgroundLabel);
      assert.equal(state.cards.length, 0, 'La suppression QSL doit retirer la carte de la DB.');
      assert.equal(state.backgrounds.length, 1, 'La suppression QSL ne doit pas supprimer le fond.');

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
      state = qslState(qsoCall, backgroundLabel);
      assert.equal(state.backgrounds.length, 0, 'La suppression du fond doit retirer la ligne DB.');
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
  const adifContent = buildAdifFixture(token, qsoCall);
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
      await postMultipartFile(driver, imageForm, 'background_image', `${token.toLowerCase()}.png`, TINY_PNG_BASE64, 'image/png', true);

      await visit(driver, 'qsl');
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
      assert.equal(state.backgrounds.length, 3, 'Les trois fonds QSL avances doivent etre persistes.');
      assert.equal(state.backgrounds.some((row) => row.label === imageLabel && Number(row.is_default) === 1), true, 'Le fond image doit pouvoir devenir le fond par defaut.');
      const backgroundsByLabel = Object.fromEntries(state.backgrounds.map((row) => [row.label, row]));
      assert.equal(backgroundsByLabel[imageLabel].type, 'image', 'Le fond image doit stocker son type.');
      assert.match(String(backgroundsByLabel[imageLabel].image_data_uri || ''), /^data:image\/png;base64,/, 'Le fond image doit stocker le data URI.');
      assert.equal(backgroundsByLabel[solidLabel].type, 'solid', 'Le fond uni doit stocker son type.');
      assert.equal(String(backgroundsByLabel[solidLabel].color_primary || '').toLowerCase(), '#225588', 'Le fond uni doit stocker sa couleur.');
      assert.equal(backgroundsByLabel[paletteLabel].type, 'gradient', 'Le fond palette doit etre stocke comme un gradient.');
      assert.ok(String(backgroundsByLabel[paletteLabel].color_primary || '') !== '', 'Le fond palette doit stocker une couleur primaire.');
      assert.ok(state.backgrounds.every((row) => String(row.created_at || '') !== ''), 'Chaque fond avance doit etre horodate.');

      await activateQslPanel(driver, 'adif');
      const adifForm = await driver.findElement(By.css('#adif-dropzone-form'));
      await postMultipartFile(driver, adifForm, 'adif_files[]', `${token.toLowerCase()}.adi`, adifContent, 'text/plain');

      state = qslState(qsoCall, token);
      assert.equal(state.qsos.length, 1, 'L import ADIF doit creer le QSO.');
      assert.equal(state.qsos[0].qso_call, qsoCall.toUpperCase(), 'Le QSO importe doit conserver l indicatif.');
      assert.equal(String(state.qsos[0].qso_date), '20260618', 'Le QSO importe doit conserver la date ADIF compacte.');
      assert.equal(String(state.qsos[0].time_on), '1542', 'Le QSO importe doit conserver l heure ADIF compacte.');
      assert.equal(state.qsos[0].band, '20M', 'Le QSO importe doit conserver la bande.');
      assert.equal(state.qsos[0].mode, 'SSB', 'Le QSO importe doit conserver le mode.');
      assert.equal(state.qsos[0].rst_sent, '59', 'Le QSO importe doit conserver le RST envoye.');
      assert.equal(state.qsos[0].rst_recv, '57', 'Le QSO importe doit conserver le RST recu.');
      assert.match(String(state.qsos[0].raw_payload || ''), new RegExp(token), 'Le QSO importe doit conserver le payload ADIF brut.');
      assert.ok(String(state.qsos[0].created_at || '') !== '', 'Le QSO importe doit etre horodate.');

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
      assert.equal(state.cards[0].qso_call, qsoCall.toUpperCase(), 'La carte groupee doit conserver l indicatif.');
      assert.equal(String(state.cards[0].qso_date), '20260618', 'La carte groupee doit conserver la date ADIF compacte.');
      assert.equal(String(state.cards[0].time_on), '1542', 'La carte groupee doit conserver l heure ADIF compacte.');
      assert.equal(state.cards[0].band, '20M', 'La carte groupee doit conserver la bande.');
      assert.equal(state.cards[0].mode, 'SSB', 'La carte groupee doit conserver le mode.');
      assert.equal(state.cards[0].rst_sent, '59', 'La carte groupee doit conserver le RST envoye.');
      assert.equal(state.cards[0].rst_recv, '57', 'La carte groupee doit conserver le RST recu.');
      assert.match(state.cards[0].svg_content, new RegExp(qsoCall), 'La carte generee doit contenir l indicatif importe.');
      assert.ok(String(state.cards[0].created_at || '') !== '', 'La carte groupee doit etre horodatee.');

      await activateQslPanel(driver, 'manage');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(qsoCall, 'i'), 'La carte generee depuis ADIF doit etre visible dans la gestion QSL.');

      await visit(driver, 'qsl', { qso_search: qsoCall });
      await activateQslPanel(driver, 'manage');
      const deleteQsoButton = await driver.findElement(By.xpath(`//tr[.//*[contains(translate(normalize-space(.), "abcdefghijklmnopqrstuvwxyz", "ABCDEFGHIJKLMNOPQRSTUVWXYZ"), "${qsoCall.toUpperCase()}")]]//button[@name="delete_qso_id"]`));
      const deleteQsoForm = await deleteQsoButton.findElement(By.xpath('ancestor::form[1]'));
      await driver.executeScript(`
        const form = arguments[0];
        const button = arguments[1];
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit(button);
        } else {
          button.click();
        }
      `, deleteQsoForm, deleteQsoButton);
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      state = qslState(qsoCall, token);
      assert.equal(state.qsos.length, 0, 'La suppression QSO doit retirer le QSO importe.');
    } finally {
      cleanupQslToken(qsoCall, token);
    }
  });
});
