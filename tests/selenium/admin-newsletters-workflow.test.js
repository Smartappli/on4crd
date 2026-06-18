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

function cleanupNewsletterRows(campaignTitle, emails) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/newsletter.php';
newsletter_ensure_tables();
$campaignTitle = trim((string) (getenv('SELENIUM_NEWSLETTER_CAMPAIGN') ?: ''));
$emails = array_values(array_filter(array_map('trim', explode(',', (string) (getenv('SELENIUM_NEWSLETTER_EMAILS') ?: '')))));
if ($campaignTitle !== '') {
    $stmt = db()->prepare('SELECT id FROM newsletter_campaigns WHERE title = ?');
    $stmt->execute([$campaignTitle]);
    $campaignIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    if ($campaignIds !== []) {
        $placeholders = implode(',', array_fill(0, count($campaignIds), '?'));
        db()->prepare('DELETE FROM newsletter_deliveries WHERE campaign_id IN (' . $placeholders . ')')->execute($campaignIds);
        db()->prepare('DELETE FROM newsletter_campaigns WHERE id IN (' . $placeholders . ')')->execute($campaignIds);
    }
}
if ($emails !== []) {
    $placeholders = implode(',', array_fill(0, count($emails), '?'));
    db()->prepare('DELETE FROM newsletter_subscribers WHERE email IN (' . $placeholders . ')')->execute($emails);
}
`, {
    SELENIUM_NEWSLETTER_CAMPAIGN: campaignTitle,
    SELENIUM_NEWSLETTER_EMAILS: emails.join(','),
  });
}

test('Selenium admin newsletters: abonnes, import CSV, campagne, envoi et statuts', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const suffix = Date.now();
  const emailOne = `selenium-newsletter-${suffix}-one@example.test`;
  const emailTwo = `selenium-newsletter-${suffix}-two@example.test`;
  const campaignTitle = `Selenium newsletter ${suffix}`;
  const campaignSubject = `Sujet Selenium newsletter ${suffix}`;
  cleanupNewsletterRows(campaignTitle, [emailOne, emailTwo]);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_newsletters');

      const addForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_subscriber"]]'));
      await addForm.findElement(By.css('input[name="email"]')).sendKeys(emailOne);
      await addForm.findElement(By.css('input[name="consent_proof"]')).sendKeys('Consentement Selenium unitaire');
      await submitForm(driver, addForm);

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(emailOne), 'L abonne ajoute manuellement doit apparaitre en admin.');
      assert.match(text, /active/i, 'L abonne ajoute doit etre actif.');

      const importForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="import_csv"]]'));
      await setFieldValue(driver, await importForm.findElement(By.css('textarea[name="csv_content"]')), `Nom,Email\nSelenium,${emailTwo}`);
      await importForm.findElement(By.css('input[name="consent_proof"]')).sendKeys('Import CSV Selenium consenti');
      await submitForm(driver, importForm);

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(emailTwo), 'L abonne importe par CSV doit apparaitre en admin.');

      const statusForm = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${emailOne}")]]//form[.//input[@name="action" and @value="set_status"]]`));
      await submitForm(driver, statusForm);

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(emailOne), 'L abonne desactive doit rester visible.');
      assert.match(text, /unsubscribed|désabonné|desabonne/i, 'Le statut desabonne doit etre visible apres changement.');

      const reactivateForm = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${emailOne}")]]//form[.//input[@name="action" and @value="set_status"]]`));
      const proofInputs = await reactivateForm.findElements(By.css('input[name="consent_proof"]'));
      assert.ok(proofInputs.length > 0, 'La reactivation doit demander une preuve de consentement.');
      await proofInputs[0].sendKeys('Reactivation Selenium consenti');
      await submitForm(driver, reactivateForm);

      text = await pagePlainText(driver);
      assert.match(text, /active/i, 'Le statut actif doit etre visible apres reactivation.');

      const campaignForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="create_campaign"]]'));
      await campaignForm.findElement(By.css('input[name="title"]')).sendKeys(campaignTitle);
      await campaignForm.findElement(By.css('input[name="subject"]')).sendKeys(campaignSubject);
      await setFieldValue(driver, await campaignForm.findElement(By.css('textarea[name="content"]')), 'Contenu de campagne Selenium newsletter.');
      await submitForm(driver, campaignForm);

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(campaignTitle), 'La campagne creee doit apparaitre en admin.');
      assert.match(text, new RegExp(campaignSubject), 'Le sujet de campagne doit apparaitre en admin.');

      const sendForm = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${campaignTitle}")]]//form[.//input[@name="action" and @value="send_campaign"]]`));
      await submitForm(driver, sendForm);

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(campaignTitle), 'La campagne envoyee doit rester visible.');
      assert.match(text, /sent|envoy/i, 'La campagne doit passer au statut envoye meme si mail() echoue localement.');
      assert.match(text, /2|deux/i, 'Le resultat d envoi doit tenir compte des deux abonnes actifs.');

      const deleteForm = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${emailTwo}")]]//form[.//input[@name="action" and @value="delete_subscriber"]]`));
      await submitForm(driver, deleteForm);

      text = await pagePlainText(driver);
      assert.doesNotMatch(text, new RegExp(emailTwo), 'L abonne supprime ne doit plus apparaitre.');
    } finally {
      cleanupNewsletterRows(campaignTitle, [emailOne, emailTwo]);
    }
  });
});
