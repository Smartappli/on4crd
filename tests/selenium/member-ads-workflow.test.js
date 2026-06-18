const test = require('node:test');
const {
  By,
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

async function setFieldValue(driver, element, value) {
  await driver.executeScript(`
    const element = arguments[0];
    const value = arguments[1];
    element.value = value;
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
  `, element, value);
}

async function selectValue(driver, select, value) {
  await driver.executeScript(`
    const select = arguments[0];
    const value = arguments[1];
    select.value = value;
    select.dispatchEvent(new Event('input', { bubbles: true }));
    select.dispatchEvent(new Event('change', { bubbles: true }));
  `, select, value);
}

function ensureAdStorageAndCleanup(code, title) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
$code = trim((string) (getenv('SELENIUM_AD_CODE') ?: ''));
$title = trim((string) (getenv('SELENIUM_AD_TITLE') ?: ''));
db()->exec('CREATE TABLE IF NOT EXISTS ad_placements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    description TEXT DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1
)');
db()->exec('CREATE TABLE IF NOT EXISTS ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_member_id INT NOT NULL,
    placement_id INT NOT NULL,
    format_code VARCHAR(80) NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    target_url VARCHAR(255) DEFAULT NULL,
    start_at DATETIME DEFAULT NULL,
    duration_days INT DEFAULT NULL,
    end_at DATETIME DEFAULT NULL,
    max_impressions INT DEFAULT NULL,
    weight INT NOT NULL DEFAULT 100,
    status ENUM("draft","pending","active","paused","expired","rejected") NOT NULL DEFAULT "draft",
    moderation_note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)');
db()->exec('CREATE TABLE IF NOT EXISTS ad_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT NOT NULL,
    event_type ENUM("impression","click") NOT NULL,
    placement_code VARCHAR(100) NOT NULL,
    member_id INT DEFAULT NULL,
    ip_hash VARCHAR(64) DEFAULT NULL,
    user_agent_hash VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ad_events_ad_id (ad_id),
    INDEX idx_ad_events_type (event_type),
    INDEX idx_ad_events_created_at (created_at)
)');
if ($title !== '' && table_exists('ads')) {
    $stmt = db()->prepare('SELECT id FROM ads WHERE title = ? OR title = ?');
    $stmt->execute([$title, $title . ' updated']);
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        db()->prepare('DELETE FROM ad_events WHERE ad_id IN (' . $placeholders . ')')->execute($ids);
        db()->prepare('DELETE FROM ads WHERE id IN (' . $placeholders . ')')->execute($ids);
    }
}
if ($code !== '' && table_exists('ad_placements')) {
    db()->prepare('DELETE FROM ad_placements WHERE code = ?')->execute([$code]);
}
$cacheDir = function_exists('cache_storage_dir') ? cache_storage_dir() : __DIR__ . '/../storage/cache/data';
foreach (glob(rtrim($cacheDir, '/') . '/*') ?: [] as $file) {
    $name = basename((string) $file);
    if (stripos($name, 'ad') !== false || stripos($name, 'home_') !== false) {
        @unlink((string) $file);
    }
}
`, {
    SELENIUM_AD_CODE: code,
    SELENIUM_AD_TITLE: title,
  });
}

test('Selenium publicites: placement, campagne, clic suivi, stats et moderation', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const suffix = Date.now();
  const placementCode = `selenium-ad-${suffix}`;
  const placementName = `Selenium placement ${suffix}`;
  const title = `Selenium publicite ${suffix}`;
  const updatedTitle = `${title} updated`;
  ensureAdStorageAndCleanup(placementCode, title);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_ads', { refresh: '1' });

      const placementForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_placement"]]'));
      await placementForm.findElement(By.css('input[name="code"]')).sendKeys(placementCode);
      await placementForm.findElement(By.css('input[name="name"]')).sendKeys(placementName);
      await setFieldValue(driver, await placementForm.findElement(By.css('textarea[name="description"]')), 'Placement Selenium publicite.');
      await setFieldValue(driver, await placementForm.findElement(By.css('input[name="sort_order"]')), '5');
      await submitForm(driver, placementForm);

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(placementCode), 'Le placement publicitaire cree doit apparaitre en admin.');

      await visit(driver, 'ads');
      const createForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save_ad"]]'));
      await createForm.findElement(By.css('input[name="title"]')).sendKeys(title);
      await setFieldValue(driver, await createForm.findElement(By.css('textarea[name="description"]')), 'Campagne Selenium publicite.');
      await driver.executeScript(`
        const form = arguments[0];
        const placementName = arguments[1];
        const placement = form.querySelector('select[name="placement_id"]');
        const option = Array.from(placement.options).find((candidate) => candidate.textContent.includes(placementName));
        if (!option) {
          throw new Error('Placement option not found: ' + placementName);
        }
        placement.value = option.value;
        placement.dispatchEvent(new Event('change', { bubbles: true }));
      `, createForm, placementName);
      await selectValue(driver, await createForm.findElement(By.css('select[name="format_code"]')), 'square');
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="target_url"]')), routeUrl('home'));
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="duration_days"]')), '10');
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="max_impressions"]')), '100');
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="weight"]')), '100');
      const statusSelects = await createForm.findElements(By.css('select[name="status"]'));
      if (statusSelects.length > 0) {
        await selectValue(driver, statusSelects[0], 'active');
      }
      await submitForm(driver, createForm);

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'La campagne publicitaire creee doit apparaitre dans Mes publicites.');
      assert.match(text, new RegExp(placementName), 'La campagne doit etre rattachee au placement cree.');

      const editLink = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${title}")]]//a[contains(@href,"edit=")]`));
      await driver.get(await editLink.getAttribute('href'));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      const editForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save_ad"]]'));
      await setFieldValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
      await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="description"]')), 'Campagne Selenium publicite modifiee.');
      const editStatusSelects = await editForm.findElements(By.css('select[name="status"]'));
      if (editStatusSelects.length > 0) {
        await selectValue(driver, editStatusSelects[0], 'active');
      }
      await submitForm(driver, editForm);

      await visit(driver, 'admin_ads', { refresh: '1' });
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'La campagne modifiee doit apparaitre dans les campagnes admin.');

      const clickLink = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${updatedTitle}")]]//a[contains(@href,"route=ad_click")]`));
      await driver.get(await clickLink.getAttribute('href'));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      await driver.wait(async () => /route=home\b/.test(await driver.getCurrentUrl()), timeoutMs, 'Le clic publicitaire doit rediriger vers la cible.');

      await visit(driver, 'ads');
      const statsLink = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${updatedTitle}")]]//a[contains(@href,"stats=")]`));
      await driver.get(await statsLink.getAttribute('href'));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'La page stats doit afficher la campagne.');
      assert.match(text, /Clics\s+1|Clicks\s+1|1\s+Clics/i, 'La page stats doit comptabiliser le clic suivi.');

      await visit(driver, 'ads');
      const statusForm = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[.//input[@name="action" and @value="change_status"]]`));
      await selectValue(driver, await statusForm.findElement(By.css('select[name="status"]')), 'paused');
      await submitForm(driver, statusForm);

      text = await pagePlainText(driver);
      assert.match(text, /pause|paused|en pause/i, 'La campagne doit pouvoir etre mise en pause.');

      await visit(driver, 'admin_ads', { refresh: '1' });
      const moderationForm = await driver.findElement(By.xpath(`//article[.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[.//input[@name="action" and @value="moderate_ad"]]`));
      await selectValue(driver, await moderationForm.findElement(By.css('select[name="status"]')), 'active');
      await setFieldValue(driver, await moderationForm.findElement(By.css('textarea[name="moderation_note"]')), 'Reactivation Selenium.');
      await submitForm(driver, moderationForm);

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'La campagne reactivee doit rester visible dans les campagnes recentes.');
      assert.match(text, /active/i, 'La moderation admin doit pouvoir reactiver la campagne.');
    } finally {
      ensureAdStorageAndCleanup(placementCode, title);
    }
  });
});
