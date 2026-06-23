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
    const wysiwygWrapper = element.previousElementSibling && element.previousElementSibling.querySelector
      ? element.previousElementSibling
      : null;
    const editor = wysiwygWrapper ? wysiwygWrapper.querySelector('.wysiwyg-editor[contenteditable="true"]') : null;
    if (editor) {
      editor.innerHTML = value;
      editor.dispatchEvent(new Event('input', { bubbles: true }));
      element.value = editor.innerHTML;
    }
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

function adPlacementByCode(code) {
  return JSON.parse(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$code = trim((string) getenv('SELENIUM_AD_CODE'));
if ($code === '' || !table_exists('ad_placements')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, code, name, description, sort_order, is_active FROM ad_placements WHERE code = ? LIMIT 1');
$stmt->execute([$code]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_AD_CODE: code }).trim() || 'null');
}

function adCampaignByTitle(title) {
  return JSON.parse(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$title = trim((string) getenv('SELENIUM_AD_TITLE'));
if ($title === '' || !table_exists('ads')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT a.id, a.owner_member_id, a.placement_id, a.format_code, a.title, a.description, a.image_path, a.target_url, a.start_at, a.duration_days, a.end_at, a.max_impressions, a.weight, a.status, a.moderation_note, a.created_at, a.updated_at, p.code AS placement_code, p.name AS placement_name FROM ads a INNER JOIN ad_placements p ON p.id = a.placement_id WHERE a.title = ? ORDER BY a.id DESC LIMIT 1');
$stmt->execute([$title]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_AD_TITLE: title }).trim() || 'null');
}

function adEventCounts(adId) {
  return JSON.parse(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$adId = (int) getenv('SELENIUM_AD_ID');
if ($adId <= 0 || !table_exists('ad_events')) {
    echo json_encode(['impression' => 0, 'click' => 0, 'rows' => []]);
    return;
}
$stmt = db()->prepare('SELECT event_type, COUNT(*) AS total FROM ad_events WHERE ad_id = ? GROUP BY event_type');
$stmt->execute([$adId]);
$out = ['impression' => 0, 'click' => 0, 'rows' => []];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
    $out[(string) $row['event_type']] = (int) $row['total'];
}
$rows = db()->prepare('SELECT id, event_type, placement_code, member_id, ip_hash, user_agent_hash, created_at FROM ad_events WHERE ad_id = ? ORDER BY id ASC');
$rows->execute([$adId]);
$out['rows'] = $rows->fetchAll(PDO::FETCH_ASSOC) ?: [];
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_AD_ID: String(adId) }).trim() || '{"impression":0,"click":0,"rows":[]}');
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
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
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
      const placement = adPlacementByCode(placementCode);
      assert.ok(placement && Number(placement.id) > 0, 'Le placement publicitaire doit etre cree en DB.');
      assert.equal(placement.code, placementCode, 'Le code placement doit etre persiste.');
      assert.equal(placement.name, placementName, 'Le nom placement doit etre persiste.');
      assert.equal(placement.description, 'Placement Selenium publicite.', 'La description placement doit etre persistee.');
      assert.equal(Number(placement.sort_order), 5, 'L ordre placement doit etre persiste.');
      assert.equal(Number(placement.is_active), 1, 'Le placement cree doit etre actif.');

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
      let ad = adCampaignByTitle(title);
      assert.ok(ad && Number(ad.id) > 0, 'La campagne publicitaire doit etre creee en DB.');
      assert.ok(Number(ad.owner_member_id) > 0, 'La campagne doit etre rattachee au membre createur.');
      assert.equal(Number(ad.placement_id), Number(placement.id), 'La campagne doit stocker le placement choisi.');
      assert.equal(ad.placement_code, placementCode, 'La campagne doit exposer le code placement en DB.');
      assert.equal(ad.placement_name, placementName, 'La campagne doit exposer le nom placement en DB.');
      assert.equal(ad.format_code, 'square', 'Le format publicitaire doit etre persiste.');
      assert.equal(ad.title, title, 'Le titre publicitaire doit etre persiste.');
      assert.equal(ad.description, 'Campagne Selenium publicite.', 'La description publicitaire doit etre persistee.');
      assert.match(String(ad.target_url || ''), /route=home\b/, 'La cible publicitaire doit etre persistee.');
      assert.equal(Number(ad.duration_days), 10, 'La duree publicitaire doit etre persistee.');
      assert.equal(Number(ad.max_impressions), 100, 'Le plafond impressions doit etre persiste.');
      assert.equal(Number(ad.weight), 100, 'Le poids publicitaire doit etre persiste.');
      assert.equal(ad.status, 'active', 'La campagne creee doit etre active.');
      assert.equal(ad.moderation_note, null, 'Une campagne creee ne doit pas avoir de note de moderation.');

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
      assert.equal(adCampaignByTitle(title), null, 'La modification doit retirer l ancien titre de la DB.');
      ad = adCampaignByTitle(updatedTitle);
      assert.ok(ad && Number(ad.id) > 0, 'La campagne modifiee doit rester en DB.');
      assert.equal(ad.description, 'Campagne Selenium publicite modifiee.', 'La description modifiee doit etre persistee.');
      assert.equal(ad.status, 'active', 'La modification doit conserver le statut actif.');
      assert.equal(Number(ad.placement_id), Number(placement.id), 'La modification ne doit pas changer le placement.');

      const clickLink = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${updatedTitle}")]]//a[contains(@href,"route=ad_click")]`));
      await driver.get(await clickLink.getAttribute('href'));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      await driver.wait(async () => /route=home\b/.test(await driver.getCurrentUrl()), timeoutMs, 'Le clic publicitaire doit rediriger vers la cible.');
      let events = adEventCounts(Number(ad.id));
      assert.equal(Number(events.click), 1, 'Le clic publicitaire doit creer un evenement click en DB.');
      assert.equal(events.rows.length, 1, 'Un seul evenement publicitaire doit etre cree par le clic suivi.');
      assert.equal(events.rows[0].event_type, 'click', 'L evenement publicitaire doit etre un clic.');
      assert.equal(events.rows[0].placement_code, placementCode, 'L evenement publicitaire doit conserver le code placement.');
      assert.ok(String(events.rows[0].created_at || '') !== '', 'L evenement publicitaire doit etre horodate.');

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
      ad = adCampaignByTitle(updatedTitle);
      assert.equal(ad.status, 'paused', 'Le changement de statut membre doit etre persiste en DB.');

      await visit(driver, 'admin_ads', { refresh: '1' });
      const moderationForm = await driver.findElement(By.xpath(`//article[.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[.//input[@name="action" and @value="moderate_ad"]]`));
      await selectValue(driver, await moderationForm.findElement(By.css('select[name="status"]')), 'active');
      await setFieldValue(driver, await moderationForm.findElement(By.css('textarea[name="moderation_note"]')), 'Reactivation Selenium.');
      await submitForm(driver, moderationForm);

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'La campagne reactivee doit rester visible dans les campagnes recentes.');
      assert.match(text, /active/i, 'La moderation admin doit pouvoir reactiver la campagne.');
      ad = adCampaignByTitle(updatedTitle);
      assert.equal(ad.status, 'active', 'La moderation admin doit reactiver la campagne en DB.');
      assert.equal(ad.moderation_note, 'Reactivation Selenium.', 'La note de moderation doit etre persistee.');
      events = adEventCounts(Number(ad.id));
      assert.equal(Number(events.click), 1, 'La moderation ne doit pas modifier les evenements de clic.');
    } finally {
      ensureAdStorageAndCleanup(placementCode, title);
    }
  });
});
