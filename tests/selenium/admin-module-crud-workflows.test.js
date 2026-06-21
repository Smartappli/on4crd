const test = require('node:test');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
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

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function escapeXPathText(value) {
  const text = String(value);
  if (!text.includes("'")) {
    return `'${text}'`;
  }
  if (!text.includes('"')) {
    return `"${text}"`;
  }

  return `concat('${text.replace(/'/g, `', "'", '`)}')`;
}

async function submitForm(driver, form, submitter = null) {
  await driver.executeScript(`
    window.confirm = () => true;
    const form = arguments[0];
    const submitter = arguments[1] || form.querySelector('button[type="submit"], button:not([type="button"]), input[type="submit"]');
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit(submitter || undefined);
    } else if (submitter) {
      submitter.click();
    } else {
      form.submit();
    }
  `, form, submitter);
  await waitForDocumentReady(driver);
  await assertNoServerError(driver);
}

async function setFieldValue(driver, field, value) {
  await driver.executeScript(`
    const field = arguments[0];
    const value = arguments[1];
    if (field.tagName && field.tagName.toLowerCase() === 'select') {
      for (const option of field.options) {
        option.selected = option.value === value;
      }
    }
    field.value = value;
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
    const label = field.closest('label');
    const editor = label ? label.querySelector('.wysiwyg-editor[contenteditable="true"]') : null;
    if (editor) {
      editor.innerHTML = value;
      editor.dispatchEvent(new Event('input', { bubbles: true }));
      editor.dispatchEvent(new Event('change', { bubbles: true }));
    }
  `, field, value);
}

async function setCheckbox(driver, checkbox, checked) {
  await driver.executeScript(`
    const checkbox = arguments[0];
    checkbox.checked = Boolean(arguments[1]);
    checkbox.dispatchEvent(new Event('input', { bubbles: true }));
    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
  `, checkbox, checked);
}

async function firstCsrfToken(driver) {
  return driver.findElement(By.css('input[name="_csrf"]')).getAttribute('value');
}

async function postBrowserForm(driver, url, fields) {
  return driver.executeAsyncScript(`
    const url = arguments[0];
    const fields = arguments[1];
    const done = arguments[arguments.length - 1];
    const form = new FormData();
    for (const [key, value] of Object.entries(fields)) {
      if (Array.isArray(value)) {
        for (const item of value) {
          form.append(key, item);
        }
      } else {
        form.append(key, value);
      }
    }
    fetch(url, {
      method: 'POST',
      body: form,
      credentials: 'same-origin',
      redirect: 'follow',
    }).then(async (response) => ({
      ok: true,
      status: response.status,
      body: await response.text(),
    })).catch((error) => ({
      ok: false,
      status: 0,
      body: String(error),
    })).then(done);
  `, url, fields);
}

function seleniumJson(source, env = {}) {
  return JSON.parse(runSeleniumPhp(source, env).trim() || 'null');
}

function cleanupAdminCrudFixtures(token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$token = trim((string) getenv('SELENIUM_ADMIN_CRUD_TOKEN'));
if ($token === '') {
    return;
}
$like = '%' . $token . '%';
$deleteFavorites = static function (string $type, array $ids): void {
    if ($ids === [] || !table_exists('member_favorites')) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id IN (' . $placeholders . ')')
        ->execute(array_merge([$type], $ids));
};
$idsFor = static function (string $table, string $where, array $params): array {
    if (!table_exists($table)) {
        return [];
    }
    $stmt = db()->prepare('SELECT id FROM ' . $table . ' WHERE ' . $where);
    $stmt->execute($params);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
};

if (table_exists('album_photos') && table_exists('albums')) {
    $albumIds = $idsFor('albums', 'title LIKE ? OR description LIKE ?', [$like, $like]);
    foreach ($albumIds as $albumId) {
        if (function_exists('album_delete_record')) {
            try {
                album_delete_record($albumId);
                continue;
            } catch (Throwable) {
            }
        }
        db()->prepare('DELETE FROM album_photos WHERE album_id = ?')->execute([$albumId]);
        db()->prepare('DELETE FROM albums WHERE id = ?')->execute([$albumId]);
    }
}

$webIds = $idsFor('member_webotheque_links', 'title LIKE ? OR url LIKE ? OR description LIKE ? OR tags LIKE ?', [$like, $like, $like, $like]);
if ($webIds !== []) {
    $deleteFavorites('webotheque_link', $webIds);
    $placeholders = implode(',', array_fill(0, count($webIds), '?'));
    db()->prepare('DELETE FROM member_webotheque_links WHERE id IN (' . $placeholders . ')')->execute($webIds);
}

if (table_exists('member_module_documents')) {
    $stmt = db()->prepare('SELECT id, module_code, file_path FROM member_module_documents WHERE module_code IN ("presentations", "videos") AND (title LIKE ? OR description LIKE ? OR tags LIKE ? OR file_path LIKE ?)');
    $stmt->execute([$like, $like, $like, $like]);
    $moduleRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $moduleIds = [];
    foreach ($moduleRows as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $moduleIds[] = $id;
        if (function_exists('member_document_delete_file')) {
            member_document_delete_file((string) ($row['file_path'] ?? ''));
        }
    }
    if ($moduleIds !== []) {
        $deleteFavorites('member_module_document', $moduleIds);
        $placeholders = implode(',', array_fill(0, count($moduleIds), '?'));
        db()->prepare('DELETE FROM member_module_documents WHERE id IN (' . $placeholders . ')')->execute($moduleIds);
    }
}

if (table_exists('member_library_documents')) {
    $stmt = db()->prepare('SELECT id, file_path FROM member_library_documents WHERE title LIKE ? OR description LIKE ? OR tags LIKE ? OR file_path LIKE ?');
    $stmt->execute([$like, $like, $like, $like]);
    $libraryRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $libraryIds = [];
    foreach ($libraryRows as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $libraryIds[] = $id;
        if (function_exists('member_library_delete_document_file')) {
            member_library_delete_document_file((string) ($row['file_path'] ?? ''));
        }
    }
    if ($libraryIds !== []) {
        $deleteFavorites('library_document', $libraryIds);
        $placeholders = implode(',', array_fill(0, count($libraryIds), '?'));
        db()->prepare('DELETE FROM member_library_documents WHERE id IN (' . $placeholders . ')')->execute($libraryIds);
    }
}

if (table_exists('content_proposals')) {
    db()->prepare('DELETE FROM content_proposals WHERE title LIKE ? OR summary LIKE ? OR source_ref LIKE ?')->execute([$like, $like, $like]);
}
`, { SELENIUM_ADMIN_CRUD_TOKEN: token });
}

function seleniumMemberId(callsign) {
  return Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$callsign = strtoupper(trim((string) getenv('SELENIUM_ADMIN_CALLSIGN')));
$memberId = 0;
if ($callsign !== '' && table_exists('members')) {
    $stmt = db()->prepare('SELECT id FROM members WHERE callsign = ? LIMIT 1');
    $stmt->execute([$callsign]);
    $memberId = (int) ($stmt->fetchColumn() ?: 0);
}
if ($memberId <= 0 && table_exists('members')) {
    $memberId = (int) (db()->query('SELECT id FROM members ORDER BY id ASC LIMIT 1')?->fetchColumn() ?: 0);
}
echo (string) max(1, $memberId);
`, { SELENIUM_ADMIN_CALLSIGN: callsign }).trim()) || 1;
}

function prepareAlbumFixture(token, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$token = trim((string) getenv('SELENIUM_ADMIN_CRUD_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
if ($token === '' || $memberId <= 0 || !table_exists('albums')) {
    throw new RuntimeException('Missing album fixture prerequisites.');
}
if (function_exists('album_ensure_schema_columns_and_indexes')) {
    album_ensure_schema_columns_and_indexes();
}
if (function_exists('album_ensure_source_proposal_column')) {
    album_ensure_source_proposal_column();
}
$title = 'selenium-admin-album-' . $token;
db()->prepare('INSERT INTO albums (member_id, category, subcategory, title, description, is_public, is_featured, publish_requested) VALUES (?, "general", "", ?, ?, 0, 0, 0)')
    ->execute([$memberId, $title, 'Album admin Selenium ' . $token]);
echo json_encode(['id' => (int) db()->lastInsertId(), 'title' => $title], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_ADMIN_CRUD_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) });
}

function albumRecord(albumId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$id = (int) getenv('SELENIUM_ALBUM_ID');
if ($id <= 0 || !table_exists('albums')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, title, description, is_public, is_featured FROM albums WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_ALBUM_ID: String(albumId) });
}

function prepareWebothequeFixture(token, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$token = trim((string) getenv('SELENIUM_ADMIN_CRUD_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
if ($token === '' || $memberId <= 0 || !ensure_webotheque_table()) {
    throw new RuntimeException('Missing webotheque fixture prerequisites.');
}
$title = 'selenium-admin-webotheque-' . $token;
$url = 'https://example.org/selenium-admin-webotheque-' . strtolower($token);
$id = webotheque_insert_link($memberId, 'general', $title, $url, 'Lien webotheque admin Selenium ' . $token, 'selenium,admin');
echo json_encode(['id' => $id, 'title' => $title, 'url' => $url], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_ADMIN_CRUD_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) });
}

function webothequeRecord(linkId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$id = (int) getenv('SELENIUM_LINK_ID');
if ($id <= 0 || !table_exists('member_webotheque_links')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, title, url, description, tags FROM member_webotheque_links WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_LINK_ID: String(linkId) });
}

function prepareModuleDocumentFixture(module, token, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$module = preg_replace('/[^a-z0-9_]/', '', strtolower((string) getenv('SELENIUM_MODULE')));
$token = trim((string) getenv('SELENIUM_ADMIN_CRUD_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
if (!in_array($module, ['presentations', 'videos'], true) || $token === '' || $memberId <= 0 || !ensure_member_module_documents_table()) {
    throw new RuntimeException('Missing member document fixture prerequisites.');
}
member_document_ensure_categories_table($module);
member_document_ensure_subcategories_table($module);
$dir = getcwd() . '/storage/private/member_modules/' . $module;
if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    throw new RuntimeException('Cannot create document fixture directory.');
}
$fileName = 'selenium-admin-' . $module . '-' . strtolower($token) . '.txt';
$publicPath = 'storage/private/member_modules/' . $module . '/' . $fileName;
file_put_contents($dir . '/' . $fileName, 'Contenu Selenium ' . $module . ' ' . $token);
@chmod($dir . '/' . $fileName, 0644);
$title = 'selenium-admin-' . $module . '-' . $token;
$id = member_document_create_record($memberId, $module, $title, 'Document admin Selenium ' . $module . ' ' . $token, 'selenium,admin', $publicPath, 'general', '');
echo json_encode(['id' => $id, 'module' => $module, 'title' => $title], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_MODULE: module, SELENIUM_ADMIN_CRUD_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) });
}

function moduleDocumentRecord(module, documentId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$module = preg_replace('/[^a-z0-9_]/', '', strtolower((string) getenv('SELENIUM_MODULE')));
$id = (int) getenv('SELENIUM_DOCUMENT_ID');
if ($id <= 0 || $module === '' || !table_exists('member_module_documents')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, module_code, title, description, tags FROM member_module_documents WHERE id = ? AND module_code = ? LIMIT 1');
$stmt->execute([$id, $module]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_MODULE: module, SELENIUM_DOCUMENT_ID: String(documentId) });
}

function prepareLibraryDocumentFixture(token, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$token = trim((string) getenv('SELENIUM_ADMIN_CRUD_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
if ($token === '' || $memberId <= 0 || !ensure_member_library_table()) {
    throw new RuntimeException('Missing library fixture prerequisites.');
}
member_library_ensure_categories_table();
member_library_ensure_subcategories_table();
$dir = getcwd() . '/storage/private/library';
if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    throw new RuntimeException('Cannot create library fixture directory.');
}
$fileName = 'selenium-admin-library-' . strtolower($token) . '.txt';
$publicPath = 'storage/private/library/' . $fileName;
file_put_contents($dir . '/' . $fileName, 'Contenu Selenium member_library ' . $token);
@chmod($dir . '/' . $fileName, 0644);
$title = 'selenium-admin-member-library-' . $token;
$id = member_library_create_document_record($memberId, $title, 'general', 'formation,club', 'Document member_library admin Selenium ' . $token, $publicPath, '');
echo json_encode(['id' => $id, 'title' => $title], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_ADMIN_CRUD_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) });
}

function libraryDocumentRecord(documentId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$id = (int) getenv('SELENIUM_DOCUMENT_ID');
if ($id <= 0 || !table_exists('member_library_documents')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, title, description, tags FROM member_library_documents WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_DOCUMENT_ID: String(documentId) });
}

function libraryDocumentRecordByTitle(title) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$title = trim((string) getenv('SELENIUM_DOCUMENT_TITLE'));
if ($title === '' || !table_exists('member_library_documents')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, title, description, tags FROM member_library_documents WHERE title = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$title]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_DOCUMENT_TITLE: title });
}

function writeLibraryUploadFixture(title, token) {
  const fixtureDir = path.join(os.tmpdir(), 'on4crd-selenium-fixtures');
  fs.mkdirSync(fixtureDir, { recursive: true });
  const fixture = path.join(fixtureDir, `${title}.txt`);
  fs.writeFileSync(fixture, `Document Selenium member_library admin ${token}`);

  return fixture;
}

async function assertPageContains(driver, text, message) {
  assert.match(await pagePlainText(driver), new RegExp(escapeRegExp(text)), message);
}

async function updateAndDeleteAlbum(driver, fixture, token) {
  const updatedTitle = `${fixture.title}-modifie`;
  const updatedDescription = `Album admin Selenium modifie ${token}`;
  await visit(driver, 'admin_albums');
  await assertPageContains(driver, fixture.title, 'L album fixture doit apparaitre dans admin_albums avant modification.');

  const editForm = await driver.findElement(By.xpath(`//article[contains(@class,"article-item")][.//input[@name="album_id" and @value="${fixture.id}"]]//form[.//input[@name="action" and @value="update_album"]]`));
  await setFieldValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
  await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="description"]')), updatedDescription);
  await setCheckbox(driver, await editForm.findElement(By.css('input[type="checkbox"][name="is_public"]')), true);
  const featuredCheckboxes = await editForm.findElements(By.css('input[type="checkbox"][name="album_is_featured"]'));
  if (featuredCheckboxes.length > 0) {
    await setCheckbox(driver, featuredCheckboxes[0], true);
  }
  await submitForm(driver, editForm);

  const updated = albumRecord(fixture.id);
  assert.ok(updated, 'L album modifie doit exister en base.');
  assert.equal(updated.title, updatedTitle, 'Le titre album doit etre mis a jour depuis admin_albums.');
  assert.equal(updated.description, updatedDescription, 'La description album doit etre mise a jour depuis admin_albums.');
  assert.equal(Number(updated.is_public), 1, 'La visibilite publique de l album doit etre persistee.');

  await visit(driver, 'admin_albums');
  const deleteForm = await driver.findElement(By.xpath(`//article[contains(@class,"article-item")][.//input[@name="album_id" and @value="${fixture.id}"]]//form[.//input[@name="action" and @value="delete_album"]]`));
  await submitForm(driver, deleteForm);
  assert.equal(albumRecord(fixture.id), null, 'L album supprime depuis admin_albums ne doit plus exister.');
}

async function updateAndDeleteWebotheque(driver, fixture, token) {
  const updatedTitle = `${fixture.title}-modifie`;
  const updatedUrl = `https://example.org/selenium-admin-webotheque-${token.toLowerCase()}-modifie`;
  const updatedDescription = `Lien webotheque admin Selenium modifie ${token}`;
  await visit(driver, 'admin_webotheque', { q: fixture.title });
  await assertPageContains(driver, fixture.title, 'Le lien fixture doit apparaitre dans admin_webotheque avant modification.');

  const editForm = await driver.findElement(By.xpath(`//dialog[.//input[@name="id" and @value="${fixture.id}"]]//form[.//input[@name="action" and @value="update_link"]]`));
  await setFieldValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
  await setFieldValue(driver, await editForm.findElement(By.css('input[name="url"]')), updatedUrl);
  await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="description"]')), updatedDescription);
  await setFieldValue(driver, await editForm.findElement(By.css('input[name="tags"]')), `selenium,admin,${token}`);
  await submitForm(driver, editForm);

  const updated = webothequeRecord(fixture.id);
  assert.ok(updated, 'Le lien webotheque modifie doit exister en base.');
  assert.equal(updated.title, updatedTitle, 'Le titre webotheque doit etre mis a jour depuis admin_webotheque.');
  assert.equal(updated.url, updatedUrl, 'L URL webotheque doit etre mise a jour depuis admin_webotheque.');
  assert.equal(updated.description, updatedDescription, 'La description webotheque doit etre mise a jour depuis admin_webotheque.');

  await visit(driver, 'admin_webotheque', { q: updatedTitle });
  const deleteForm = await driver.findElement(By.xpath(`//dialog[.//input[@name="id" and @value="${fixture.id}"]]//form[.//input[@name="action" and @value="delete_link"]]`));
  await submitForm(driver, deleteForm);
  assert.equal(webothequeRecord(fixture.id), null, 'Le lien supprime depuis admin_webotheque ne doit plus exister.');
}

async function updateAndDeleteMemberModuleDocument(driver, fixture, token) {
  const updatedTitle = `${fixture.title}-modifie`;
  const updatedDescription = `Document ${fixture.module} admin Selenium modifie ${token}`;
  const publicRoute = fixture.module;
  await visit(driver, publicRoute, { q: fixture.title });
  await assertPageContains(driver, fixture.title, `Le document ${fixture.module} doit apparaitre avant modification.`);

  const editForm = await driver.findElement(By.xpath(`//dialog[.//input[@name="id" and @value="${fixture.id}"]]//form[.//input[@name="action" and @value="update_document"]]`));
  await setFieldValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
  await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="description"]')), updatedDescription);
  await setFieldValue(driver, await editForm.findElement(By.css('input[name="tags"]')), `selenium,admin,${fixture.module}`);
  await submitForm(driver, editForm);

  const updated = moduleDocumentRecord(fixture.module, fixture.id);
  assert.ok(updated, `Le document ${fixture.module} modifie doit exister en base.`);
  assert.equal(updated.title, updatedTitle, `Le titre ${fixture.module} doit etre mis a jour avec les droits admin.`);
  assert.equal(updated.description, updatedDescription, `La description ${fixture.module} doit etre mise a jour avec les droits admin.`);
  assert.match(updated.tags, /selenium/i, `Les tags ${fixture.module} doivent etre mis a jour.`);

  await visit(driver, publicRoute, { q: updatedTitle });
  const deleteForm = await driver.findElement(By.xpath(`//dialog[.//input[@name="id" and @value="${fixture.id}"]]//form[.//input[@name="action" and @value="delete_document"]]`));
  await submitForm(driver, deleteForm);
  await driver.wait(
    () => Promise.resolve(moduleDocumentRecord(fixture.module, fixture.id) === null),
    timeoutMs,
    `Le document ${fixture.module} doit etre supprime en base.`,
  );
}

async function updateAndDeleteLibraryDocument(driver, fixture, token) {
  const updatedTitle = `${fixture.title}-modifie`;
  const updatedDescription = `Document member_library admin Selenium modifie ${token}`;
  await visit(driver, 'members_library', { q: fixture.title });
  await assertPageContains(driver, fixture.title, 'Le document member_library doit apparaitre avant modification.');

  const editForm = await driver.findElement(By.xpath(`//dialog[.//input[@name="document_id" and @value="${fixture.id}"]]//form[.//input[@name="action" and @value="update_document"]]`));
  await setFieldValue(driver, await editForm.findElement(By.css('input[name="document_title"]')), updatedTitle);
  await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="document_description"]')), updatedDescription);
  await setFieldValue(driver, await editForm.findElement(By.css('input[name="document_tags"]')), 'technique,club');
  await submitForm(driver, editForm);

  const updated = libraryDocumentRecord(fixture.id);
  assert.ok(updated, 'Le document member_library modifie doit exister en base.');
  assert.equal(updated.title, updatedTitle, 'Le titre member_library doit etre mis a jour avec les droits admin.');
  assert.equal(updated.description, updatedDescription, 'La description member_library doit etre mise a jour avec les droits admin.');
  assert.match(updated.tags, /technique/i, 'Les tags member_library doivent etre mis a jour.');

  await visit(driver, 'members_library', { q: updatedTitle });
  const deleteForm = await driver.findElement(By.xpath(`//dialog[.//input[@name="document_id" and @value="${fixture.id}"]]//form[.//input[@name="action" and @value="delete_document"]]`));
  await submitForm(driver, deleteForm);
  await driver.wait(
    () => Promise.resolve(libraryDocumentRecord(fixture.id) === null),
    timeoutMs,
    'Le document member_library doit etre supprime en base.',
  );
}

async function deleteMemberModuleDocumentFromAdminRoute(driver, fixture) {
  const adminRoute = `admin_${fixture.module}`;
  await visit(driver, adminRoute, { q: fixture.title });
  await assertPageContains(driver, fixture.title, `Le document ${fixture.module} doit apparaitre sur ${adminRoute}.`);

  const deleteForm = await driver.findElement(By.xpath(
    `//article[contains(@class,"member-document-card")][.//*[contains(normalize-space(.), ${escapeXPathText(fixture.title)})]]`
    + `//form[.//input[@name="action" and @value="delete_document"] and .//input[@name="id" and @value="${fixture.id}"]]`,
  ));
  await submitForm(driver, deleteForm);
  await driver.wait(
    () => Promise.resolve(moduleDocumentRecord(fixture.module, fixture.id) === null),
    timeoutMs,
    `Le document ${fixture.module} doit etre supprime depuis ${adminRoute}.`,
  );
}

async function createLibraryDocumentFromAdminRoute(driver, token) {
  const title = `selenium-admin-member-library-${token}`;
  const fixture = writeLibraryUploadFixture(title, token);
  await visit(driver, 'admin_library');

  const form = await driver.findElement(By.css('form.admin-library-upload-form'));
  await setFieldValue(driver, await form.findElement(By.css('input[name="title"]')), title);
  await setFieldValue(driver, await form.findElement(By.css('input[name="tags"]')), `selenium,admin,${token}`);
  await setFieldValue(driver, await form.findElement(By.css('textarea[name="description"]')), `Document member_library admin Selenium ${token}`);
  await form.findElement(By.css('input[type="file"][name="document"]')).sendKeys(path.resolve(fixture));
  await submitForm(driver, form);

  const document = libraryDocumentRecordByTitle(title);
  assert.ok(document && Number(document.id) > 0, 'Le document member_library doit etre cree depuis admin_library.');

  return {
    id: Number(document.id),
    title: document.title,
  };
}

async function deleteLibraryDocumentFromAdminRoute(driver, fixture) {
  await visit(driver, 'admin_library');
  assert.ok(libraryDocumentRecord(fixture.id), 'Le document member_library doit exister avant suppression depuis admin_library.');

  const deleteResponse = await postBrowserForm(driver, routeUrl('admin_library'), {
    _csrf: await firstCsrfToken(driver),
    action: 'delete_document',
    id: String(fixture.id),
  });
  assert.equal(deleteResponse.ok, true, 'La suppression member_library doit repondre au POST navigateur.');
  assert.ok(deleteResponse.status < 500, 'La suppression member_library ne doit pas produire d erreur serveur.');
  assert.doesNotMatch(deleteResponse.body, /Fatal error|Parse error|Internal Server Error/i, 'La suppression member_library ne doit pas rendre d erreur PHP.');
  await driver.wait(
    () => Promise.resolve(libraryDocumentRecord(fixture.id) === null),
    timeoutMs,
    'Le document member_library doit etre supprime depuis admin_library.',
  );
}

test('Selenium admin: modifier et supprimer albums et webotheque', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `ADMCRUD${Date.now()}`;
  cleanupAdminCrudFixtures(token);
  const memberId = seleniumMemberId(credentials.username);
  const album = prepareAlbumFixture(token, memberId);
  const webotheque = prepareWebothequeFixture(token, memberId);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await updateAndDeleteAlbum(driver, album, token);
      await updateAndDeleteWebotheque(driver, webotheque, token);
    } finally {
      cleanupAdminCrudFixtures(token);
    }
  });
});

test('Selenium admin: supprimer documents depuis admin_presentations, admin_videos et admin_library', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `ADMDEL${Date.now()}`;
  cleanupAdminCrudFixtures(token);
  const memberId = seleniumMemberId(credentials.username);
  const fixtures = [
    prepareModuleDocumentFixture('presentations', token, memberId),
    prepareModuleDocumentFixture('videos', token, memberId),
  ];
  for (const fixture of fixtures) {
    assert.ok(fixture && Number(fixture.id) > 0, `La fixture ${JSON.stringify(fixture)} doit etre creee avant la suppression admin.`);
  }

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await deleteMemberModuleDocumentFromAdminRoute(driver, fixtures[0]);
      await deleteMemberModuleDocumentFromAdminRoute(driver, fixtures[1]);
      const libraryFixture = await createLibraryDocumentFromAdminRoute(driver, token);
      await deleteLibraryDocumentFromAdminRoute(driver, libraryFixture);
    } finally {
      cleanupAdminCrudFixtures(token);
    }
  });
});

test('Selenium admin: modifier et supprimer presentations, videos et member_library', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `ADMCRUD${Date.now()}`;
  cleanupAdminCrudFixtures(token);
  const memberId = seleniumMemberId(credentials.username);
  const fixtures = [
    prepareModuleDocumentFixture('presentations', token, memberId),
    prepareModuleDocumentFixture('videos', token, memberId),
    prepareLibraryDocumentFixture(token, memberId),
  ];
  for (const fixture of fixtures) {
    assert.ok(fixture && Number(fixture.id) > 0, `La fixture ${JSON.stringify(fixture)} doit etre creee avant le scenario Selenium.`);
  }

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await updateAndDeleteMemberModuleDocument(driver, fixtures[0], token);
      await updateAndDeleteMemberModuleDocument(driver, fixtures[1], token);
      await updateAndDeleteLibraryDocument(driver, fixtures[2], token);
    } finally {
      cleanupAdminCrudFixtures(token);
    }
  });
});
