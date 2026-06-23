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

function newsletterSubscriber(email) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/newsletter.php';
newsletter_ensure_tables();
$email = trim((string) (getenv('SELENIUM_NEWSLETTER_EMAIL') ?: ''));
$stmt = db()->prepare('SELECT id, email, status, source, consent_proof, consented_at, unsubscribed_at FROM newsletter_subscribers WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_NEWSLETTER_EMAIL: email });

  return JSON.parse(output || 'null');
}

function newsletterCampaign(title) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/newsletter.php';
newsletter_ensure_tables();
$title = trim((string) (getenv('SELENIUM_NEWSLETTER_CAMPAIGN') ?: ''));
$stmt = db()->prepare('SELECT id, title, subject, content, status, sent_at FROM newsletter_campaigns WHERE title = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$title]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_NEWSLETTER_CAMPAIGN: title });

  return JSON.parse(output || 'null');
}

function newsletterDeliveryRows(campaignId, emails) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/newsletter.php';
newsletter_ensure_tables();
$campaignId = (int) (getenv('SELENIUM_NEWSLETTER_CAMPAIGN_ID') ?: 0);
$emails = array_values(array_filter(array_map('trim', explode(',', (string) (getenv('SELENIUM_NEWSLETTER_EMAILS') ?: '')))));
if ($campaignId <= 0 || $emails === []) {
    echo json_encode([], JSON_THROW_ON_ERROR);
    return;
}
$placeholders = implode(',', array_fill(0, count($emails), '?'));
$params = array_merge([$campaignId], $emails);
$stmt = db()->prepare(
    'SELECT ns.email, d.status, d.error_message, d.sent_at
     FROM newsletter_deliveries d
     INNER JOIN newsletter_subscribers ns ON ns.id = d.subscriber_id
     WHERE d.campaign_id = ? AND ns.email IN (' . $placeholders . ')
     ORDER BY ns.email ASC'
);
$stmt->execute($params);
echo json_encode($stmt->fetchAll() ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, {
    SELENIUM_NEWSLETTER_CAMPAIGN_ID: String(campaignId),
    SELENIUM_NEWSLETTER_EMAILS: emails.join(','),
  });

  return JSON.parse(output || '[]');
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
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupNewsletterRows(campaignTitle, [emailOne, emailTwo]);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_newsletters');

      const addForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_subscriber"]]'));
      await addForm.findElement(By.css('input[name="email"]')).sendKeys(emailOne);
      await addForm.findElement(By.css('input[name="consent_proof"]')).sendKeys('Consentement Selenium unitaire');
      await submitForm(driver, addForm);

      let subscriberOne = newsletterSubscriber(emailOne);
      assert.ok(subscriberOne && Number(subscriberOne.id) > 0, 'L abonne ajoute doit etre cree en DB.');
      assert.equal(subscriberOne.email, emailOne, 'L email ajoute doit etre normalise et persiste.');
      assert.equal(subscriberOne.status, 'active', 'L abonne ajoute doit etre actif en DB.');
      assert.equal(subscriberOne.source, 'admin', 'L ajout manuel doit garder la source admin.');
      assert.equal(subscriberOne.consent_proof, 'Consentement Selenium unitaire', 'La preuve de consentement manuelle doit etre persistee.');
      assert.ok(String(subscriberOne.consented_at || '').length > 0, 'La date de consentement doit etre renseignee en DB.');

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(emailOne), 'L abonne ajoute manuellement doit apparaitre en admin.');
      assert.match(text, /active/i, 'L abonne ajoute doit etre actif.');

      const importForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="import_csv"]]'));
      await setFieldValue(driver, await importForm.findElement(By.css('textarea[name="csv_content"]')), `Nom,Email\nSelenium,${emailTwo}`);
      await importForm.findElement(By.css('input[name="consent_proof"]')).sendKeys('Import CSV Selenium consenti');
      await submitForm(driver, importForm);

      let subscriberTwo = newsletterSubscriber(emailTwo);
      assert.ok(subscriberTwo && Number(subscriberTwo.id) > 0, 'L abonne importe doit etre cree en DB.');
      assert.equal(subscriberTwo.status, 'active', 'L abonne importe doit etre actif en DB.');
      assert.equal(subscriberTwo.source, 'import_csv', 'L import CSV doit garder la source import_csv.');
      assert.equal(subscriberTwo.consent_proof, 'Import CSV Selenium consenti', 'La preuve de consentement CSV doit etre persistee.');

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(emailTwo), 'L abonne importe par CSV doit apparaitre en admin.');

      const statusForm = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${emailOne}")]]//form[.//input[@name="action" and @value="set_status"]]`));
      await submitForm(driver, statusForm);

      subscriberOne = newsletterSubscriber(emailOne);
      assert.equal(subscriberOne.status, 'unsubscribed', 'La desactivation admin doit persister le statut unsubscribed.');
      assert.ok(String(subscriberOne.unsubscribed_at || '').length > 0, 'La desactivation admin doit renseigner unsubscribed_at.');

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(emailOne), 'L abonne desactive doit rester visible.');
      assert.match(text, /unsubscribed|désabonné|desabonne/i, 'Le statut desabonne doit etre visible apres changement.');

      const reactivateForm = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${emailOne}")]]//form[.//input[@name="action" and @value="set_status"]]`));
      const proofInputs = await reactivateForm.findElements(By.css('input[name="consent_proof"]'));
      assert.ok(proofInputs.length > 0, 'La reactivation doit demander une preuve de consentement.');
      await proofInputs[0].sendKeys('Reactivation Selenium consenti');
      await submitForm(driver, reactivateForm);

      subscriberOne = newsletterSubscriber(emailOne);
      assert.equal(subscriberOne.status, 'active', 'La reactivation admin doit repasser l abonne en active.');
      assert.equal(subscriberOne.source, 'admin_status', 'La reactivation admin doit persister la source admin_status.');
      assert.equal(subscriberOne.consent_proof, 'Reactivation Selenium consenti', 'La preuve de reactivation doit etre persistee.');
      assert.equal(subscriberOne.unsubscribed_at, null, 'La reactivation admin doit vider unsubscribed_at.');

      text = await pagePlainText(driver);
      assert.match(text, /active/i, 'Le statut actif doit etre visible apres reactivation.');

      const campaignForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="create_campaign"]]'));
      await campaignForm.findElement(By.css('input[name="title"]')).sendKeys(campaignTitle);
      await campaignForm.findElement(By.css('input[name="subject"]')).sendKeys(campaignSubject);
      await setFieldValue(driver, await campaignForm.findElement(By.css('textarea[name="content"]')), 'Contenu de campagne Selenium newsletter.');
      await submitForm(driver, campaignForm);

      let campaign = newsletterCampaign(campaignTitle);
      assert.ok(campaign && Number(campaign.id) > 0, 'La campagne doit etre creee en DB.');
      assert.equal(campaign.subject, campaignSubject, 'Le sujet de campagne doit etre persiste.');
      assert.equal(campaign.content, 'Contenu de campagne Selenium newsletter.', 'Le contenu de campagne doit etre persiste.');
      assert.equal(campaign.status, 'draft', 'La campagne creee doit demarrer en draft.');

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(campaignTitle), 'La campagne creee doit apparaitre en admin.');
      assert.match(text, new RegExp(campaignSubject), 'Le sujet de campagne doit apparaitre en admin.');

      const sendForm = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${campaignTitle}")]]//form[.//input[@name="action" and @value="send_campaign"]]`));
      await submitForm(driver, sendForm);

      campaign = newsletterCampaign(campaignTitle);
      assert.equal(campaign.status, 'sent', 'L envoi admin doit passer la campagne en sent.');
      assert.ok(String(campaign.sent_at || '').length > 0, 'L envoi admin doit renseigner sent_at.');
      const deliveryRows = newsletterDeliveryRows(campaign.id, [emailOne, emailTwo]);
      assert.equal(deliveryRows.length, 2, 'L envoi doit creer une livraison pour chaque abonne Selenium actif.');
      for (const delivery of deliveryRows) {
        assert.match(delivery.email, /selenium-newsletter-/);
        assert.match(delivery.status, /sent|failed/, 'Chaque livraison Selenium doit etre marquee sent ou failed.');
      }

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(campaignTitle), 'La campagne envoyee doit rester visible.');
      assert.match(text, /sent|envoy/i, 'La campagne doit passer au statut envoye meme si mail() echoue localement.');
      assert.match(text, /2|deux/i, 'Le resultat d envoi doit tenir compte des deux abonnes actifs.');

      const deleteForm = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${emailTwo}")]]//form[.//input[@name="action" and @value="delete_subscriber"]]`));
      await submitForm(driver, deleteForm);

      subscriberTwo = newsletterSubscriber(emailTwo);
      assert.equal(subscriberTwo, null, 'La suppression admin doit retirer l abonne de la DB.');

      text = await pagePlainText(driver);
      assert.doesNotMatch(text, new RegExp(emailTwo), 'L abonne supprime ne doit plus apparaitre.');
    } finally {
      cleanupNewsletterRows(campaignTitle, [emailOne, emailTwo]);
    }
  });
});
