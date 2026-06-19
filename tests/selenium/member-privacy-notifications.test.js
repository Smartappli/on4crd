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
    if (element.tagName && element.tagName.toLowerCase() === 'select') {
      for (const option of element.options) {
        option.selected = option.value === value;
      }
    }
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

function seleniumJson(source, env = {}) {
  return JSON.parse(runSeleniumPhp(source, env) || 'null');
}

function memberByCallsign(callsign) {
  const member = seleniumJson(`
require_once 'app/bootstrap.php';
$callsign = strtoupper((string) getenv('SELENIUM_TARGET_CALLSIGN'));
$stmt = db()->prepare('SELECT id, callsign FROM members WHERE callsign = ? LIMIT 1');
$stmt->execute([$callsign]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TARGET_CALLSIGN: callsign });
  assert.ok(member && Number(member.id) > 0, `Membre Selenium introuvable pour ${callsign}.`);
  return member;
}

function availablePrivacyType(memberId) {
  return runSeleniumPhp(`
require_once 'app/bootstrap.php';
privacy_ensure_tables();
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$types = ['portability', 'objection', 'restriction', 'rectification', 'access'];
foreach ($types as $type) {
    $stmt = db()->prepare('SELECT COUNT(*) FROM privacy_requests WHERE member_id = ? AND request_type = ? AND status IN ("pending", "in_progress")');
    $stmt->execute([$memberId, $type]);
    if ((int) $stmt->fetchColumn() === 0) {
        echo $type;
        return;
    }
}
echo 'portability';
`, { SELENIUM_MEMBER_ID: String(memberId) }).trim();
}

function cleanupPrivacyRequest(token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$token = (string) getenv('SELENIUM_TOKEN');
if ($token !== '' && table_exists('privacy_requests')) {
    $idsStmt = db()->prepare('SELECT id FROM privacy_requests WHERE notes LIKE ? OR admin_notes LIKE ?');
    $idsStmt->execute(['%' . $token . '%', '%' . $token . '%']);
    $ids = array_map('intval', $idsStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if (table_exists('privacy_request_events')) {
            db()->prepare('DELETE FROM privacy_request_events WHERE request_id IN (' . $placeholders . ')')->execute($ids);
        }
        db()->prepare('DELETE FROM privacy_requests WHERE id IN (' . $placeholders . ')')->execute($ids);
    }
}
`, { SELENIUM_TOKEN: token });
}

function privacyRequestId(token) {
  return Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$token = (string) getenv('SELENIUM_TOKEN');
$stmt = db()->prepare('SELECT id FROM privacy_requests WHERE notes LIKE ? ORDER BY id DESC LIMIT 1');
$stmt->execute(['%' . $token . '%']);
echo (int) ($stmt->fetchColumn() ?: 0);
`, { SELENIUM_TOKEN: token }).trim());
}

function createNotification(memberId, title, body) {
  return Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/notifications.php';
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$title = (string) getenv('SELENIUM_NOTIFICATION_TITLE');
$body = (string) getenv('SELENIUM_NOTIFICATION_BODY');
notify_member($memberId, 'selenium', $title, $body, route_url('dashboard'));
$stmt = db()->prepare('SELECT id FROM member_notifications WHERE member_id = ? AND title = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$memberId, $title]);
echo (int) ($stmt->fetchColumn() ?: 0);
`, {
    SELENIUM_MEMBER_ID: String(memberId),
    SELENIUM_NOTIFICATION_TITLE: title,
    SELENIUM_NOTIFICATION_BODY: body,
  }).trim());
}

function cleanupNotification(notificationId) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$id = (int) getenv('SELENIUM_NOTIFICATION_ID');
if ($id > 0 && table_exists('member_notifications')) {
    db()->prepare('DELETE FROM member_notifications WHERE id = ? LIMIT 1')->execute([$id]);
}
`, { SELENIUM_NOTIFICATION_ID: String(notificationId || 0) });
}

test('Selenium membre RGPD: demande, suivi admin et suivi membre', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  const member = memberByCallsign(credentials.username.toUpperCase());
  const token = `SELENIUMPRIV${Date.now()}`;
  const requestType = availablePrivacyType(member.id);
  const note = `Demande RGPD ${token}`;
  const adminNote = `Traitement RGPD ${token}`;

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'gdpr');

      const actionInput = await driver.findElement(By.css('input[name="action"][value="privacy_request"]'));
      const requestForm = await actionInput.findElement(By.xpath('ancestor::form[1]'));
      await setFieldValue(driver, await requestForm.findElement(By.css('select[name="request_type"]')), requestType);
      await setFieldValue(driver, await requestForm.findElement(By.css('textarea[name="request_notes"]')), note);
      await submitForm(driver, requestForm);

      let text = await pagePlainText(driver);
      assert.match(text, /demande RGPD|request/i, 'La confirmation de demande RGPD doit rester visible cote membre.');

      const requestId = privacyRequestId(token);
      assert.ok(requestId > 0, 'La demande RGPD Selenium doit etre creee.');

      await visit(driver, 'admin_privacy');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(token), 'La demande RGPD doit apparaitre en admin.');
      const adminForm = await driver.findElement(By.xpath(`//input[@name="request_id" and @value="${requestId}"]/ancestor::form[1]`));
      await setFieldValue(driver, await adminForm.findElement(By.css('select[name="status"]')), 'resolved');
      await setFieldValue(driver, await adminForm.findElement(By.css('textarea[name="admin_notes"]')), adminNote);
      await submitForm(driver, adminForm);

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(adminNote), 'La note admin RGPD doit etre persistee.');

      await visit(driver, 'my_requests');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(adminNote), 'Le suivi membre doit afficher la note de traitement RGPD.');
    } finally {
      cleanupPrivacyRequest(token);
    }
  });
});

test('Selenium membre notifications: filtrer et marquer comme lu', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  const member = memberByCallsign(credentials.username.toUpperCase());
  const suffix = Date.now();
  const title = `Notification Selenium ${suffix}`;
  const body = `Corps notification Selenium ${suffix}`;
  const notificationId = createNotification(member.id, title, body);
  assert.ok(notificationId > 0, 'La notification Selenium doit etre creee.');

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'notifications', { filter: 'unread' });

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'La notification non lue doit etre visible.');
      assert.match(text, new RegExp(body), 'Le corps de notification doit etre visible.');

      const markForm = await driver.findElement(By.xpath(`//*[contains(normalize-space(.), "${title}")]/ancestor::li[1]//input[@name="action" and @value="mark_read"]/ancestor::form[1]`));
      await submitForm(driver, markForm);

      const isRead = Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$stmt = db()->prepare('SELECT is_read FROM member_notifications WHERE id = ? LIMIT 1');
$stmt->execute([(int) getenv('SELENIUM_NOTIFICATION_ID')]);
echo (int) ($stmt->fetchColumn() ?: 0);
`, { SELENIUM_NOTIFICATION_ID: String(notificationId) }).trim());
      assert.equal(isRead, 1, 'La notification doit etre marquee comme lue.');

      await visit(driver, 'notifications', { filter: 'unread' });
      text = await pagePlainText(driver);
      assert.doesNotMatch(text, new RegExp(title), 'La notification lue ne doit plus apparaitre dans le filtre non lu.');
    } finally {
      cleanupNotification(notificationId);
    }
  });
});
