const test = require('node:test');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {
  By,
  until,
  assert,
  timeoutMs,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  pagePlainText,
  loginAsAdmin,
  requireAdminCredentials,
  ensureSeleniumFixtures,
  ensureSeleniumRunnable,
  runSeleniumPhp,
} = require('./helpers');

const memberUsername = 'SELENIUMMEMBER';
const memberPassword = process.env.SELENIUM_MEMBER_PASSWORD || 'SeleniumMember!2026';

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function xpathLiteral(value) {
  const text = String(value);
  if (!text.includes("'")) {
    return `'${text}'`;
  }
  if (!text.includes('"')) {
    return `"${text}"`;
  }

  return `concat('${text.split("'").join(`', "'", '`)}')`;
}

async function submitForm(driver, form) {
  await driver.executeScript(`
    window.confirm = () => true;
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
    input.value = arguments[1];
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  `, input, value);
}

async function setRichTextarea(driver, textarea, value) {
  await driver.executeScript(`
    const textarea = arguments[0];
    const value = arguments[1];
    textarea.value = value;
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    textarea.dispatchEvent(new Event('change', { bubbles: true }));
    const label = textarea.closest('label');
    const editor = label ? label.querySelector('.wysiwyg-editor[contenteditable="true"]') : null;
    if (editor) {
      editor.innerHTML = value;
      editor.dispatchEvent(new Event('input', { bubbles: true }));
    }
  `, textarea, value);
}

async function setSelectValue(driver, select, value) {
  await driver.executeScript(`
    const select = arguments[0];
    select.value = arguments[1];
    select.dispatchEvent(new Event('input', { bubbles: true }));
    select.dispatchEvent(new Event('change', { bubbles: true }));
  `, select, value);
}

function ensureStandardMember() {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');

$callsign = 'SELENIUMMEMBER';
$email = 'selenium-member@example.test';
$password = getenv('SELENIUM_MEMBER_PASSWORD') ?: 'SeleniumMember!2026';
$hash = class_exists('Delight\\\\Auth\\\\PasswordHash')
    ? Delight\\Auth\\PasswordHash::from($password)
    : password_hash($password, PASSWORD_DEFAULT);
if (!is_string($hash) || $hash === '') {
    throw new RuntimeException('Cannot hash Selenium member password.');
}

if (!table_exists('users') || !table_exists('members')) {
    throw new RuntimeException('Auth tables unavailable.');
}

$stmt = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ? ORDER BY id ASC LIMIT 1');
$stmt->execute([$callsign, $email]);
$authUserId = (int) ($stmt->fetchColumn() ?: 0);
if ($authUserId > 0) {
    db()->prepare('UPDATE users SET email = ?, password = ?, username = ?, status = 0, verified = 1, resettable = 1, roles_mask = 0, force_logout = force_logout + 1 WHERE id = ?')
        ->execute([$email, $hash, $callsign, $authUserId]);
} else {
    db()->prepare('INSERT INTO users (email, password, username, status, verified, resettable, roles_mask, registered) VALUES (?, ?, ?, 0, 1, 1, 0, ?)')
        ->execute([$email, $hash, $callsign, time()]);
    $authUserId = (int) db()->lastInsertId();
}

if (table_exists('users_2fa')) {
    db()->prepare('DELETE FROM users_2fa WHERE user_id = ?')->execute([$authUserId]);
}
if (table_exists('users_remembered')) {
    db()->prepare('DELETE FROM users_remembered WHERE user = ?')->execute([$authUserId]);
}

if (table_has_column('members', 'auth_user_id')) {
    db()->prepare('UPDATE members SET auth_user_id = NULL WHERE auth_user_id = ? AND UPPER(callsign) <> ?')
        ->execute([$authUserId, $callsign]);
}

$memberStmt = db()->prepare('SELECT id FROM members WHERE UPPER(callsign) = ? LIMIT 1');
$memberStmt->execute([$callsign]);
$memberId = (int) ($memberStmt->fetchColumn() ?: 0);
if ($memberId > 0) {
    $assignments = ['full_name = ?', 'email = ?', 'password_hash = ?', 'is_active = 1'];
    $params = ['Selenium Member', $email, password_hash($password, PASSWORD_DEFAULT)];
    if (table_has_column('members', 'auth_user_id')) {
        $assignments[] = 'auth_user_id = ?';
        $params[] = $authUserId;
    }
    if (table_has_column('members', 'is_admin')) {
        $assignments[] = 'is_admin = 0';
    }
    $params[] = $memberId;
    db()->prepare('UPDATE members SET ' . implode(', ', $assignments) . ' WHERE id = ?')
        ->execute($params);
} else {
    if (table_has_column('members', 'auth_user_id')) {
        db()->prepare('INSERT INTO members (auth_user_id, callsign, full_name, email, password_hash, is_active) VALUES (?, ?, ?, ?, ?, 1)')
            ->execute([$authUserId, $callsign, 'Selenium Member', $email, password_hash($password, PASSWORD_DEFAULT)]);
    } else {
        db()->prepare('INSERT INTO members (callsign, full_name, email, password_hash, is_active) VALUES (?, ?, ?, ?, 1)')
            ->execute([$callsign, 'Selenium Member', $email, password_hash($password, PASSWORD_DEFAULT)]);
    }
    $memberId = (int) db()->lastInsertId();
}

if (table_exists('member_roles')) {
    db()->prepare('DELETE FROM member_roles WHERE member_id = ?')->execute([$memberId]);
}
if (table_exists('member_permissions')) {
    db()->prepare('DELETE FROM member_permissions WHERE member_id = ?')->execute([$memberId]);
}
if (table_exists('users_throttling')) {
    db()->exec('DELETE FROM users_throttling');
}

echo json_encode(['member_id' => $memberId, 'auth_user_id' => $authUserId], JSON_THROW_ON_ERROR);
`, { SELENIUM_MEMBER_PASSWORD: memberPassword });

  return { username: memberUsername, password: memberPassword };
}

function cleanupPendingValidationRows(token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');

$token = trim((string) (getenv('SELENIUM_TEST_TOKEN') ?: ''));
if ($token === '') {
    return;
}
$like = '%' . $token . '%';

$deleteStoragePath = static function (string $path): void {
    $path = rawurldecode(trim(str_replace('\\\\', '/', $path)));
    if ($path === '') {
        return;
    }
    $safePath = null;
    if (function_exists('safe_storage_document_path_or_null')) {
        $safePath = safe_storage_document_path_or_null($path, [
            'storage/private/library/',
            'storage/uploads/library/',
            'storage/private/member_modules/',
            'storage/uploads/member_modules/',
        ]);
    }
    if ($safePath === null && preg_match('~(storage/(?:private|uploads)/(?:library|member_modules)/[^\\s?#]+)~i', $path, $matches) === 1) {
        $safePath = ltrim((string) $matches[1], '/');
    }
    if (!is_string($safePath) || $safePath === '' || str_contains($safePath, '..')) {
        return;
    }
    $absolute = function_exists('storage_document_absolute_path')
        ? storage_document_absolute_path($safePath)
        : (getcwd() . '/' . $safePath);
    if (is_file($absolute)) {
        @unlink($absolute);
    }
};

if (table_exists('content_proposals')) {
    $sourceStmt = db()->prepare('SELECT source_ref FROM content_proposals WHERE title LIKE ? OR summary LIKE ? OR contact LIKE ? OR source_ref LIKE ?');
    $sourceStmt->execute([$like, $like, $like, $like]);
    foreach ($sourceStmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $sourceRef) {
        $deleteStoragePath((string) $sourceRef);
    }
}

if (table_exists('member_library_documents')) {
    $stmt = db()->prepare('SELECT id, file_path FROM member_library_documents WHERE title LIKE ? OR description LIKE ? OR tags LIKE ? OR file_path LIKE ?');
    $stmt->execute([$like, $like, $like, $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id > 0 && table_exists('member_favorites')) {
            db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id = ?')->execute(['library_document', $id]);
        }
        $deleteStoragePath((string) ($row['file_path'] ?? ''));
        if ($id > 0) {
            db()->prepare('DELETE FROM member_library_documents WHERE id = ?')->execute([$id]);
        }
    }
}

if (table_exists('member_module_documents')) {
    $stmt = db()->prepare('SELECT id, file_path FROM member_module_documents WHERE title LIKE ? OR description LIKE ? OR tags LIKE ? OR file_path LIKE ?');
    $stmt->execute([$like, $like, $like, $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id > 0 && table_exists('member_favorites')) {
            db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id = ?')->execute(['member_module_document', $id]);
        }
        $deleteStoragePath((string) ($row['file_path'] ?? ''));
        if ($id > 0) {
            db()->prepare('DELETE FROM member_module_documents WHERE id = ?')->execute([$id]);
        }
    }
}

if (table_exists('member_webotheque_links')) {
    db()->prepare('DELETE FROM member_webotheque_links WHERE title LIKE ? OR url LIKE ? OR description LIKE ? OR tags LIKE ?')
        ->execute([$like, $like, $like, $like]);
}

if (table_exists('albums')) {
    $stmt = db()->prepare('SELECT id FROM albums WHERE title LIKE ? OR description LIKE ?');
    $stmt->execute([$like, $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $albumId) {
        $albumId = (int) $albumId;
        if ($albumId <= 0) {
            continue;
        }
        if (function_exists('album_delete_record')) {
            try {
                album_delete_record($albumId);
                continue;
            } catch (Throwable) {
            }
        }
        if (table_exists('album_photos')) {
            db()->prepare('DELETE FROM album_photos WHERE album_id = ?')->execute([$albumId]);
        }
        if (table_exists('member_favorites')) {
            db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id = ?')->execute(['album', $albumId]);
        }
        db()->prepare('DELETE FROM albums WHERE id = ?')->execute([$albumId]);
    }
}

if (table_exists('content_proposals')) {
    db()->prepare('DELETE FROM content_proposals WHERE title LIKE ? OR summary LIKE ? OR contact LIKE ? OR source_ref LIKE ?')
        ->execute([$like, $like, $like, $like]);
}

foreach (['admin_albums_list_v2', 'admin_albums_photos_total_v2'] as $cacheKey) {
    if (function_exists('cache_forget')) {
        cache_forget($cacheKey);
    }
}
`, { SELENIUM_TEST_TOKEN: token });
}

function proposalStatus(title) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$title = getenv('SELENIUM_PROPOSAL_TITLE') ?: '';
ensure_content_proposals_table();
$stmt = db()->prepare('SELECT status FROM content_proposals WHERE title = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$title]);
echo (string) ($stmt->fetchColumn() ?: '');
`, { SELENIUM_PROPOSAL_TITLE: title }).trim();

  return output;
}

function proposalId(title) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$title = getenv('SELENIUM_PROPOSAL_TITLE') ?: '';
ensure_content_proposals_table();
$stmt = db()->prepare('SELECT id FROM content_proposals WHERE title = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$title]);
echo (string) ((int) ($stmt->fetchColumn() ?: 0));
`, { SELENIUM_PROPOSAL_TITLE: title }).trim();

  return Number(output || 0);
}

function seedMemberModuleDocument(moduleCode, title, description) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');

$moduleCode = preg_replace('/[^a-z0-9_]/', '', strtolower((string) (getenv('SELENIUM_MODULE_CODE') ?: '')));
$title = trim((string) (getenv('SELENIUM_DOCUMENT_TITLE') ?: ''));
$description = trim((string) (getenv('SELENIUM_DOCUMENT_DESCRIPTION') ?: ''));
if ($moduleCode === '' || $title === '') {
    throw new RuntimeException('Invalid member module Selenium fixture.');
}
if (!ensure_member_module_documents_table()) {
    throw new RuntimeException('Member module storage unavailable.');
}
$memberStmt = db()->prepare('SELECT id FROM members WHERE UPPER(callsign) = ? LIMIT 1');
$memberStmt->execute(['SELENIUMMEMBER']);
$memberId = (int) ($memberStmt->fetchColumn() ?: 0);
if ($memberId <= 0) {
    throw new RuntimeException('Selenium member unavailable.');
}

$targetDir = getcwd() . '/storage/private/member_modules/' . $moduleCode;
if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
    throw new RuntimeException('Cannot create member module fixture directory.');
}
$base = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($title));
$base = trim((string) $base, '-');
if ($base === '') {
    $base = 'selenium-document';
}
$filename = $base . '.txt';
$absolute = $targetDir . '/' . $filename;
file_put_contents($absolute, "Fixture Selenium " . $moduleCode . "\\n" . $title . "\\n");
@chmod($absolute, 0644);
$publicPath = 'storage/private/member_modules/' . $moduleCode . '/' . $filename;

$stmt = db()->prepare('SELECT id FROM member_module_documents WHERE module_code = ? AND title = ? LIMIT 1');
$stmt->execute([$moduleCode, $title]);
$documentId = (int) ($stmt->fetchColumn() ?: 0);
if ($documentId > 0) {
    db()->prepare('UPDATE member_module_documents SET member_id = ?, category = "general", subcategory = "", tags = "selenium", description = ?, file_path = ?, extracted_text = ? WHERE id = ?')
        ->execute([$memberId, $description, $publicPath, $title, $documentId]);
} else {
    db()->prepare('INSERT INTO member_module_documents (module_code, member_id, category, subcategory, tags, title, description, file_path, extracted_text) VALUES (?, ?, "general", "", "selenium", ?, ?, ?, ?)')
        ->execute([$moduleCode, $memberId, $title, $description, $publicPath, $title]);
    $documentId = (int) db()->lastInsertId();
}

echo (string) $documentId;
`, {
    SELENIUM_MODULE_CODE: moduleCode,
    SELENIUM_DOCUMENT_TITLE: title,
    SELENIUM_DOCUMENT_DESCRIPTION: description,
  }).trim();

  const documentId = Number(output || 0);
  assert.ok(documentId > 0, `Document fixture ${moduleCode} doit etre cree.`);
  return documentId;
}

async function assertMyRequestsCard(driver, title, { statusRegex, moduleRegex }) {
  await visit(driver, 'my_requests');
  const card = await driver.findElement(By.xpath(`//article[contains(@class,"my-requests-shortcut")][.//*[contains(normalize-space(.), ${xpathLiteral(title)})]]`));
  const text = (await card.getText()).replace(/\s+/g, ' ').trim();
  assert.match(text, new RegExp(escapeRegExp(title)), `${title} doit etre visible dans Mes contenus.`);
  if (statusRegex) {
    assert.match(text, statusRegex, `${title} doit avoir le statut attendu dans Mes contenus.`);
  }
  if (moduleRegex) {
    assert.match(text, moduleRegex, `${title} doit etre rattache au module attendu dans Mes contenus.`);
  }
}

async function assertRouteContains(driver, route, query, title, message) {
  await visit(driver, route, query);
  const text = await pagePlainText(driver);
  assert.match(text, new RegExp(escapeRegExp(title)), message);
}

async function acceptProposalWithAdmin(driver, credentials, title) {
  const id = proposalId(title);
  assert.ok(id > 0, `La proposition ${title} doit exister avant validation admin.`);

  await loginAsAdmin(driver, credentials.username, credentials.password);
  await visit(driver, 'admin');

  const visibleForms = await driver.findElements(By.xpath(`//article[contains(@class,"admin-pending-item")][.//h3[contains(normalize-space(.), ${xpathLiteral(title)})]]//form[.//input[@name="action" and @value="update_content_proposal_status"]]`));
  if (visibleForms.length > 0) {
    const form = visibleForms[0];
    await setSelectValue(driver, await form.findElement(By.css('select[name="proposal_status"]')), 'accepted');
    await setInputValue(driver, await form.findElement(By.css('textarea[name="moderation_note"]')), 'Validation Selenium');
    await submitForm(driver, form);
  } else {
    const csrf = await driver.executeScript('return document.querySelector("input[name=\\"_csrf\\"]") ? document.querySelector("input[name=\\"_csrf\\"]").value : "";');
    assert.notEqual(csrf, '', 'Le tableau de bord admin doit exposer un jeton CSRF pour valider la proposition.');
    await driver.executeScript(`
      const csrf = arguments[0];
      const proposalId = arguments[1];
      const form = document.createElement('form');
      form.method = 'post';
      form.action = window.location.href.split('#')[0];
      const fields = {
        _csrf: csrf,
        action: 'update_content_proposal_status',
        proposal_id: proposalId,
        proposal_status: 'accepted',
        moderation_note: 'Validation Selenium'
      };
      for (const [name, value] of Object.entries(fields)) {
        const input = name === 'moderation_note' ? document.createElement('textarea') : document.createElement('input');
        input.name = name;
        input.value = value;
        form.appendChild(input);
      }
      document.body.appendChild(form);
      form.submit();
    `, csrf, String(id));
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);
  }

  assert.equal(proposalStatus(title), 'accepted', `La proposition ${title} doit etre acceptee.`);
}

async function loginAsMember(driver) {
  await loginAsAdmin(driver, memberUsername, memberPassword);
}

function writeTextFixture(title, content) {
  const fixtureDir = path.join(os.tmpdir(), 'on4crd-selenium-fixtures');
  fs.mkdirSync(fixtureDir, { recursive: true });
  const fixture = path.join(fixtureDir, `${title}.txt`);
  fs.writeFileSync(fixture, content, 'utf8');

  return fixture;
}

test('Selenium membre: proposer un document bibliotheque, le valider et le retrouver dans members_library', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `selenium-pending-library-${Date.now()}`;
  const title = `${token}-document`;
  const fixture = writeTextFixture(title, 'Document Selenium propose puis valide dans la bibliotheque membre.');
  cleanupPendingValidationRows(token);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      ensureStandardMember();

      await loginAsMember(driver);
      await visit(driver, 'members_library');
      await driver.executeScript(`
        const dialog = document.getElementById('members-library-document-dialog');
        if (dialog && !dialog.open) {
          if (typeof dialog.showModal === 'function') {
            dialog.showModal();
          } else {
            dialog.setAttribute('open', '');
          }
        }
      `);

      const form = await driver.findElement(By.css('#members-library-document-dialog form'));
      await form.findElement(By.css('input[name="proposal_title"]')).sendKeys(title);
      await form.findElement(By.css('input[name="proposal_tags"]')).sendKeys('formation');
      await form.findElement(By.css('input[type="file"][name="proposal_file"]')).sendKeys(path.resolve(fixture));
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_description"]')), 'Document propose par un membre Selenium.');
      const contact = await form.findElement(By.css('input[name="proposal_contact"]')).getAttribute('value');
      assert.ok(contact.trim() !== '', 'Le contact de proposition document doit etre pre-rempli.');
      await submitForm(driver, form);

      await driver.wait(until.urlContains('route=my_requests'), timeoutMs);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /attente|pending/i,
        moduleRegex: /biblioth|library/i,
      });
      assert.equal(proposalStatus(title), 'pending', 'Le document propose par un membre doit rester en attente avant validation admin.');

      await acceptProposalWithAdmin(driver, credentials, title);
      await assertRouteContains(driver, 'members_library', { q: title }, title, 'Le document valide doit etre visible dans members_library.');

      await loginAsMember(driver);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /accept|publ/i,
        moduleRegex: /biblioth|library/i,
      });
    } finally {
      cleanupPendingValidationRows(token);
    }
  });
});

test('Selenium membre: proposer un lien webotheque, le valider et le retrouver dans la webotheque', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `selenium-pending-webotheque-${Date.now()}`;
  const title = `${token}-link`;
  cleanupPendingValidationRows(token);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      ensureStandardMember();

      await loginAsMember(driver);
      await visit(driver, 'webotheque', { propose_link: '1' });

      const form = await driver.findElement(By.css('#webotheque-link-dialog form.webotheque-proposal-form'));
      await form.findElement(By.css('input[name="title"]')).sendKeys(title);
      await form.findElement(By.css('input[name="url"]')).sendKeys(`https://example.org/${token}`);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="description"]')), 'Lien webotheque propose par Selenium.');
      await form.findElement(By.css('input[name="tags"]')).sendKeys('selenium, regression');
      await submitForm(driver, form);

      await driver.wait(until.urlContains('route=my_requests'), timeoutMs);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /attente|pending/i,
        moduleRegex: /weboth|web library/i,
      });
      assert.equal(proposalStatus(title), 'pending', 'Le lien webotheque doit rester en attente avant validation admin.');

      await acceptProposalWithAdmin(driver, credentials, title);
      await assertRouteContains(driver, 'webotheque', { q: title }, title, 'Le lien valide doit etre visible dans la webotheque.');

      await loginAsMember(driver);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /accept|publ/i,
        moduleRegex: /weboth|web library/i,
      });
    } finally {
      cleanupPendingValidationRows(token);
    }
  });
});

test('Selenium membre: proposer un album, le valider et le retrouver dans les albums', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `selenium-pending-album-${Date.now()}`;
  const title = `${token}-album`;
  cleanupPendingValidationRows(token);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      ensureStandardMember();

      await loginAsMember(driver);
      await visit(driver, 'albums', { propose_album: '1' });

      const form = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="propose_album"]]'));
      await form.findElement(By.css('input[name="proposal_title"]')).sendKeys(title);
      await form.findElement(By.css('input[name="proposal_keywords"]')).sendKeys('selenium regression');
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_description"]')), 'Album propose par Selenium puis valide.');
      await submitForm(driver, form);

      await driver.wait(until.urlContains('route=my_requests'), timeoutMs);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /attente|pending/i,
        moduleRegex: /album/i,
      });
      assert.equal(proposalStatus(title), 'pending', 'L album propose doit rester en attente avant validation admin.');

      await acceptProposalWithAdmin(driver, credentials, title);
      await assertRouteContains(driver, 'albums', { q: title }, title, 'L album valide doit etre visible dans les albums.');

      await loginAsMember(driver);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /accept|publ/i,
        moduleRegex: /album/i,
      });
    } finally {
      cleanupPendingValidationRows(token);
    }
  });
});

for (const moduleCode of ['presentations', 'videos']) {
  test(`Selenium membre: proposer une modification ${moduleCode}, la valider et verifier le module`, async (t) => {
    const credentials = requireAdminCredentials(t);
    if (credentials === null) {
      return;
    }
    if (!(await ensureSeleniumRunnable(t))) {
      return;
    }

    const token = `selenium-pending-${moduleCode}-${Date.now()}`;
    const originalTitle = `${token}-original`;
    const updatedTitle = `${token}-validated`;
    cleanupPendingValidationRows(token);

    await withSelenium(t, async (driver) => {
      try {
        ensureSeleniumFixtures();
        ensureStandardMember();
        seedMemberModuleDocument(moduleCode, originalTitle, `Document ${moduleCode} initial Selenium.`);

        await loginAsMember(driver);
        await visit(driver, moduleCode, { q: originalTitle });
        const openButton = await driver.findElement(By.xpath(`//article[contains(@class,"member-document-card")][.//*[contains(normalize-space(.), ${xpathLiteral(originalTitle)})]]//button[contains(@data-member-document-modal-open, "member-document-edit-dialog-")]`));
        await driver.executeScript('arguments[0].click();', openButton);

        const form = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), ${xpathLiteral(originalTitle)})]]//form[contains(@class,"member-document-dialog-form") and .//input[@name="action" and @value="update_document"]]`));
        await setInputValue(driver, await form.findElement(By.css('input[name="title"]')), updatedTitle);
        await setRichTextarea(driver, await form.findElement(By.css('textarea[name="description"]')), `Modification ${moduleCode} proposee par Selenium.`);
        await setInputValue(driver, await form.findElement(By.css('input[name="tags"]')), 'selenium,validation');
        await submitForm(driver, form);

        await driver.wait(until.urlContains('route=my_requests'), timeoutMs);
        await assertMyRequestsCard(driver, updatedTitle, {
          statusRegex: /attente|pending/i,
          moduleRegex: new RegExp(moduleCode, 'i'),
        });
        assert.equal(proposalStatus(updatedTitle), 'pending', `La modification ${moduleCode} doit rester en attente avant validation admin.`);

        await acceptProposalWithAdmin(driver, credentials, updatedTitle);
        await assertRouteContains(driver, moduleCode, { q: updatedTitle }, updatedTitle, `Le document ${moduleCode} valide doit etre visible dans son module.`);

        await loginAsMember(driver);
        await assertMyRequestsCard(driver, updatedTitle, {
          statusRegex: /accept|publ/i,
          moduleRegex: new RegExp(moduleCode, 'i'),
        });
      } finally {
        cleanupPendingValidationRows(token);
      }
    });
  });
}
