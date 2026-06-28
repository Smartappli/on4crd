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

async function openDialog(driver, id) {
  await driver.executeScript(`
    const dialog = document.getElementById(arguments[0]);
    if (!dialog || dialog.open) {
      return;
    }
    if (typeof dialog.showModal === 'function') {
      dialog.showModal();
    } else {
      dialog.setAttribute('open', '');
    }
  `, id);
}

async function selectOptionsText(driver, selector) {
  return driver.executeScript(`
    return Array.from(document.querySelectorAll(arguments[0]))
      .map((option) => option.textContent || '')
      .join(' ');
  `, selector);
}

async function assertSelectOptionContains(driver, route, query, selector, title, message) {
  await visit(driver, route, query);
  const text = await selectOptionsText(driver, selector);
  assert.match(text, new RegExp(escapeRegExp(title)), message);
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

if (table_exists('articles')) {
    $stmt = db()->prepare('SELECT id FROM articles WHERE title LIKE ? OR excerpt LIKE ? OR content LIKE ?');
    $stmt->execute([$like, $like, $like]);
    $ids = array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []), static fn(int $id): bool => $id > 0));
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if (table_exists('article_translations')) {
            db()->prepare('DELETE FROM article_translations WHERE article_id IN (' . $placeholders . ')')->execute($ids);
        }
        if (table_exists('article_revisions')) {
            db()->prepare('DELETE FROM article_revisions WHERE article_id IN (' . $placeholders . ')')->execute($ids);
        }
        if (table_exists('member_favorites')) {
            db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id IN (' . $placeholders . ')')->execute(array_merge(['article'], $ids));
        }
        db()->prepare('DELETE FROM articles WHERE id IN (' . $placeholders . ')')->execute($ids);
    }
}

if (table_exists('wiki_pages')) {
    $wikiWhere = 'title LIKE ? OR slug LIKE ? OR content LIKE ?';
    $wikiParams = [$like, $like, $like];
    if (table_has_column('wiki_pages', 'target_slug')) {
        $wikiWhere .= ' OR target_slug LIKE ?';
        $wikiParams[] = $like;
    }
    $stmt = db()->prepare('SELECT id FROM wiki_pages WHERE ' . $wikiWhere);
    $stmt->execute($wikiParams);
    $ids = array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []), static fn(int $id): bool => $id > 0));
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if (table_exists('wiki_revisions')) {
            db()->prepare('DELETE FROM wiki_revisions WHERE wiki_page_id IN (' . $placeholders . ')')->execute($ids);
        }
        if (table_exists('member_favorites')) {
            db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id IN (' . $placeholders . ')')->execute(array_merge(['wiki_page'], $ids));
        }
        db()->prepare('DELETE FROM wiki_pages WHERE id IN (' . $placeholders . ')')->execute($ids);
    }
}

if (table_exists('classified_ads')) {
    db()->prepare('DELETE FROM classified_ads WHERE title LIKE ? OR description LIKE ? OR contact LIKE ?')
        ->execute([$like, $like, $like]);
}

if (table_exists('events')) {
    db()->prepare('DELETE FROM events WHERE title LIKE ? OR summary LIKE ? OR description LIKE ? OR location LIKE ?')
        ->execute([$like, $like, $like, $like]);
}

if (table_exists('auction_lots')) {
    db()->prepare('DELETE FROM auction_lots WHERE title LIKE ? OR summary LIKE ? OR description LIKE ?')
        ->execute([$like, $like, $like]);
}

if (table_exists('news_posts')) {
    $stmt = db()->prepare('SELECT id FROM news_posts WHERE title LIKE ? OR excerpt LIKE ? OR content LIKE ?');
    $stmt->execute([$like, $like, $like]);
    $ids = array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []), static fn(int $id): bool => $id > 0));
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if (table_exists('news_translations')) {
            db()->prepare('DELETE FROM news_translations WHERE news_post_id IN (' . $placeholders . ')')->execute($ids);
        }
        db()->prepare('DELETE FROM news_posts WHERE id IN (' . $placeholders . ')')->execute($ids);
    }
}

if (table_exists('news_sections')) {
    db()->prepare('DELETE FROM news_sections WHERE slug LIKE ? OR name LIKE ?')
        ->execute([$like, $like]);
}

if (table_exists('member_library_subcategories')) {
    db()->prepare('DELETE FROM member_library_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')
        ->execute([$like, $like, $like]);
}

if (table_exists('member_library_categories')) {
    db()->prepare('DELETE FROM member_library_categories WHERE code LIKE ? OR label LIKE ?')
        ->execute([$like, $like]);
}

if (table_exists('album_subcategories')) {
    db()->prepare('DELETE FROM album_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')
        ->execute([$like, $like, $like]);
}

if (table_exists('album_categories')) {
    db()->prepare('DELETE FROM album_categories WHERE code LIKE ? OR label LIKE ?')
        ->execute([$like, $like]);
}

if (table_exists('member_webotheque_subcategories')) {
    db()->prepare('DELETE FROM member_webotheque_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')
        ->execute([$like, $like, $like]);
}

if (table_exists('member_webotheque_categories')) {
    db()->prepare('DELETE FROM member_webotheque_categories WHERE code LIKE ? OR label LIKE ?')
        ->execute([$like, $like]);
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

function pendingValidationRecord(kind, title) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');

$kind = getenv('SELENIUM_RECORD_KIND') ?: '';
$title = getenv('SELENIUM_RECORD_TITLE') ?: '';
$row = [];
if ($kind === 'article' && table_exists('articles')) {
    $stmt = db()->prepare('SELECT id, slug, status FROM articles WHERE title = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$title]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} elseif ($kind === 'wiki' && table_exists('wiki_pages')) {
    $stmt = db()->prepare('SELECT id, slug, status FROM wiki_pages WHERE title = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$title]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} elseif ($kind === 'classified' && table_exists('classified_ads')) {
    $stmt = db()->prepare('SELECT id, "" AS slug, status FROM classified_ads WHERE title = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$title]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
echo json_encode($row ?: new stdClass(), JSON_THROW_ON_ERROR);
`, {
    SELENIUM_RECORD_KIND: kind,
    SELENIUM_RECORD_TITLE: title,
  }).trim();

  return JSON.parse(output || '{}');
}

function pendingValidationStatus(kind, title) {
  return String(pendingValidationRecord(kind, title).status || '');
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

async function waitForMyRequestsOrAlbumDetail(driver) {
  await driver.wait(async () => {
    const currentUrl = await driver.getCurrentUrl();
    return currentUrl.includes('route=my_requests') || currentUrl.includes('route=album');
  }, timeoutMs);
  if (!(await driver.getCurrentUrl()).includes('route=my_requests')) {
    await visit(driver, 'my_requests');
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
  await visit(driver, 'admin');
}

async function publishArticleWithAdmin(driver, credentials, title) {
  const row = pendingValidationRecord('article', title);
  assert.ok(Number(row.id || 0) > 0, `L article ${title} doit exister avant validation admin.`);

  await loginAsAdmin(driver, credentials.username, credentials.password);
  await visit(driver, 'admin_articles', { id: row.id });
  const form = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save_article"]]'));
  await setSelectValue(driver, await form.findElement(By.css('select[name="status"]')), 'published');
  await submitForm(driver, form);

  assert.equal(pendingValidationStatus('article', title), 'published', `L article ${title} doit etre publie.`);
}

async function publishWikiPageWithAdmin(driver, credentials, title) {
  const row = pendingValidationRecord('wiki', title);
  assert.ok(Number(row.id || 0) > 0, `La page wiki ${title} doit exister avant validation admin.`);

  await loginAsAdmin(driver, credentials.username, credentials.password);
  await visit(driver, 'admin_wiki', { status: 'pending' });
  const csrf = await driver.executeScript('return document.querySelector("input[name=\\"_csrf\\"]") ? document.querySelector("input[name=\\"_csrf\\"]").value : "";');
  assert.notEqual(csrf, '', 'admin_wiki doit exposer un jeton CSRF.');
  await driver.executeScript(`
    const fields = arguments[0];
    const form = document.createElement('form');
    form.method = 'post';
    form.action = window.location.href.split('#')[0];
    for (const [name, value] of Object.entries(fields)) {
      const input = document.createElement('input');
      input.name = name;
      input.value = value;
      form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
  `, {
    _csrf: csrf,
    action: 'update_page_status',
    id: String(row.id),
    status: 'published',
    return_status: 'pending',
  });
  await waitForDocumentReady(driver);
  await assertNoServerError(driver);

  assert.equal(pendingValidationStatus('wiki', title), 'published', `La page wiki ${title} doit etre publiee.`);
}

async function activateClassifiedWithAdmin(driver, credentials, title) {
  const row = pendingValidationRecord('classified', title);
  assert.ok(Number(row.id || 0) > 0, `L annonce ${title} doit exister avant validation admin.`);

  await loginAsAdmin(driver, credentials.username, credentials.password);
  await visit(driver, 'admin_classifieds', { edit: row.id });
  const form = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save"]]'));
  await setSelectValue(driver, await form.findElement(By.css('select[name="status"]')), 'active');
  await submitForm(driver, form);

  assert.equal(pendingValidationStatus('classified', title), 'active', `L annonce ${title} doit etre active.`);
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

function proposalDateTime(daysFromNow = 1) {
  const date = new Date(Date.now() + (daysFromNow * 24 * 60 * 60 * 1000));
  date.setHours(19, 30, 0, 0);
  const yyyy = date.getFullYear();
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  const dd = String(date.getDate()).padStart(2, '0');

  return `${yyyy}-${mm}-${dd} 19:30`;
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

      await waitForMyRequestsOrAlbumDetail(driver);
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

test('Selenium membre: proposer une video, la valider et la retrouver dans videos', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `selenium-pending-video-${Date.now()}`;
  const title = `${token}-video`;
  const fixture = writeTextFixture(title, 'Ressource video Selenium proposee puis validee.');
  cleanupPendingValidationRows(token);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      ensureStandardMember();

      await loginAsMember(driver);
      await visit(driver, 'videos');
      const proposeMenu = await driver.findElement(By.css('.member-document-propose-menu summary'));
      const proposeMenuLabel = (await proposeMenu.getText()).replace(/\s+/g, ' ').trim();
      assert.match(proposeMenuLabel, /Propos/i, 'La page videos doit afficher un menu Proposer.');
      await driver.executeScript('arguments[0].click();', proposeMenu);

      const proposeMenuLayout = await driver.executeScript(`
        const hero = document.querySelector('[data-route="videos"] .member-module-hero');
        const panel = document.querySelector('.member-document-propose-menu-panel');
        if (!hero || !panel) {
          return null;
        }
        const heroRect = hero.getBoundingClientRect();
        const panelRect = panel.getBoundingClientRect();
        const probeX = Math.min(Math.max(panelRect.left + panelRect.width / 2, 0), window.innerWidth - 1);
        const probeY = Math.min(Math.max(panelRect.bottom - 2, 0), window.innerHeight - 1);
        const topElement = document.elementFromPoint(probeX, probeY);
        return {
          heroOverflow: getComputedStyle(hero).overflow,
          panelBottom: panelRect.bottom,
          heroBottom: heroRect.bottom,
          visibleAtBottom: panel.contains(topElement) || topElement === panel,
        };
      `);
      assert.ok(proposeMenuLayout, 'Le menu Proposer videos doit exposer un panneau.');
      assert.equal(proposeMenuLayout.heroOverflow, 'visible', 'Le hero videos doit permettre au dropdown de sortir du header.');
      assert.ok(proposeMenuLayout.panelBottom > proposeMenuLayout.heroBottom, 'Le dropdown videos doit depasser visuellement du header.');
      assert.equal(proposeMenuLayout.visibleAtBottom, true, 'Le dropdown videos ne doit pas etre masque hors du header.');

      const proposeButton = await driver.findElement(By.css('.member-document-propose-menu [data-member-document-modal-open="member-document-proposal-dialog"]'));
      const proposeLabel = (await proposeButton.getText()).replace(/\s+/g, ' ').trim();
      assert.match(proposeLabel, /vid/i, 'Le menu videos doit contenir une entree video.');
      await driver.executeScript('arguments[0].click();', proposeButton);

      const form = await driver.findElement(By.css('#member-document-proposal-dialog form.member-document-dialog-form'));
      await form.findElement(By.css('input[name="proposal_title"]')).sendKeys(title);
      await form.findElement(By.css('input[name="proposal_tags"]')).sendKeys('selenium, video');
      await form.findElement(By.css('input[type="file"][name="proposal_file"]')).sendKeys(path.resolve(fixture));
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_description"]')), 'Video proposee par Selenium.');
      const contact = await form.findElement(By.css('input[name="proposal_contact"]')).getAttribute('value');
      assert.ok(contact.trim() !== '', 'Le contact de proposition video doit etre pre-rempli.');
      await submitForm(driver, form);

      await driver.wait(until.urlContains('route=my_requests'), timeoutMs);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /attente|pending/i,
        moduleRegex: /vid[eé]os/i,
      });
      assert.equal(proposalStatus(title), 'pending', 'La video proposee doit rester en attente avant validation admin.');

      await acceptProposalWithAdmin(driver, credentials, title);
      await assertRouteContains(driver, 'videos', { q: title }, title, 'La video validee doit etre visible dans videos.');

      await loginAsMember(driver);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /accept|publ/i,
        moduleRegex: /vid[eé]os/i,
      });
    } finally {
      cleanupPendingValidationRows(token);
    }
  });
});

test('Selenium membre: proposer une presentation, la valider et la retrouver dans presentations', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `selenium-pending-presentation-${Date.now()}`;
  const title = `${token}-presentation`;
  const fixture = writeTextFixture(title, 'Presentation Selenium proposee puis validee.');
  cleanupPendingValidationRows(token);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      ensureStandardMember();

      await loginAsMember(driver);
      await visit(driver, 'presentations');
      const proposeMenu = await driver.findElement(By.css('.member-document-propose-menu'));
      await driver.executeScript('arguments[0].setAttribute("open", "");', proposeMenu);
      const proposeLink = await proposeMenu.findElement(By.css('[data-member-document-modal-open="member-document-proposal-dialog"]'));
      const proposeLabel = String(await driver.executeScript('return arguments[0].textContent || "";', proposeLink)).replace(/\s+/g, ' ').trim();
      assert.match(proposeLabel, /pr.sent/i, 'Le dropdown presentations doit proposer une presentation.');
      await driver.executeScript('arguments[0].click();', proposeLink);

      const form = await driver.findElement(By.css('#member-document-proposal-dialog form.member-document-dialog-form'));
      await form.findElement(By.css('input[name="proposal_title"]')).sendKeys(title);
      await form.findElement(By.css('input[name="proposal_tags"]')).sendKeys('selenium, presentation');
      await form.findElement(By.css('input[type="file"][name="proposal_file"]')).sendKeys(path.resolve(fixture));
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_description"]')), 'Presentation proposee par Selenium.');
      const contact = await form.findElement(By.css('input[name="proposal_contact"]')).getAttribute('value');
      assert.ok(contact.trim() !== '', 'Le contact de proposition presentation doit etre pre-rempli.');
      await submitForm(driver, form);

      await driver.wait(until.urlContains('route=my_requests'), timeoutMs);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /attente|pending/i,
        moduleRegex: /presentations/i,
      });
      assert.equal(proposalStatus(title), 'pending', 'La presentation proposee doit rester en attente avant validation admin.');

      await acceptProposalWithAdmin(driver, credentials, title);
      await assertRouteContains(driver, 'presentations', { q: title }, title, 'La presentation validee doit etre visible dans presentations.');

      await loginAsMember(driver);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /accept|publ/i,
        moduleRegex: /presentations/i,
      });
    } finally {
      cleanupPendingValidationRows(token);
    }
  });
});

async function runContentProposalScenario(t, scenario) {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `${scenario.tokenPrefix}-${Date.now()}`;
  const title = `${token}-${scenario.titleSuffix}`;
  cleanupPendingValidationRows(token);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      ensureStandardMember();

      await loginAsMember(driver);
      await scenario.submit(driver, title, token);

      await driver.wait(until.urlContains('route=my_requests'), timeoutMs);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /attente|pending/i,
        moduleRegex: scenario.moduleRegex,
      });
      assert.equal(proposalStatus(title), 'pending', `${title} doit rester en attente avant validation admin.`);

      await acceptProposalWithAdmin(driver, credentials, title);
      if (typeof scenario.afterAccept === 'function') {
        await scenario.afterAccept(driver, title, token);
      }

      await loginAsMember(driver);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /accept|publ|active|actif/i,
        moduleRegex: scenario.moduleRegex,
      });
    } finally {
      cleanupPendingValidationRows(token);
    }
  });
}

const contentProposalScenarios = [
  {
    name: 'Selenium membre: proposer une thematique albums, la valider et la retrouver dans le formulaire album',
    tokenPrefix: 'selenium-pending-album-category',
    titleSuffix: 'topic',
    moduleRegex: /album/i,
    submit: async (driver, title) => {
      await visit(driver, 'albums', { propose_category: '1' });
      const form = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="propose_category"]]'));
      await form.findElement(By.css('input[name="proposal_category_name"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_reason"]')), 'Thematique albums proposee par Selenium.');
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertSelectOptionContains(driver, 'albums', { propose_album: '1' }, 'select[name="proposal_theme"] option', title, 'La thematique albums validee doit etre disponible pour proposer un album.');
    },
  },
  {
    name: 'Selenium membre: proposer une sous-thematique albums, la valider et la retrouver dans le formulaire album',
    tokenPrefix: 'selenium-pending-album-subcategory',
    titleSuffix: 'subtopic',
    moduleRegex: /album/i,
    submit: async (driver, title) => {
      await visit(driver, 'albums', { propose_subcategory: '1' });
      const form = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="propose_subcategory"]]'));
      await form.findElement(By.css('input[name="proposal_subcategory_name"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_reason"]')), 'Sous-thematique albums proposee par Selenium.');
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertSelectOptionContains(driver, 'albums', { propose_album: '1' }, 'select[name="proposal_subcategory_ref"] option', title, 'La sous-thematique albums validee doit etre disponible pour proposer un album.');
    },
  },
  ...['presentations'].flatMap((moduleCode) => [
    {
      name: `Selenium membre: proposer une thematique ${moduleCode}, la valider et la retrouver dans le formulaire document`,
      tokenPrefix: `selenium-pending-${moduleCode}-category`,
      titleSuffix: 'topic',
      moduleRegex: new RegExp(moduleCode, 'i'),
      submit: async (driver, title) => {
        await visit(driver, moduleCode, { propose_category: '1' });
        const form = await driver.findElement(By.css('#member-document-category-dialog form.member-document-dialog-form'));
        await setInputValue(driver, await form.findElement(By.css('input[name="proposal_category_name"]')), title);
        await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_reason"]')), `Thematique ${moduleCode} proposee par Selenium.`);
        await submitForm(driver, form);
      },
      afterAccept: async (driver, title) => {
        await assertSelectOptionContains(driver, moduleCode, { propose_document: '1' }, '#member-document-proposal-dialog select[name="category"] option', title, `La thematique ${moduleCode} validee doit etre disponible pour proposer un document.`);
      },
    },
    {
      name: `Selenium membre: proposer une sous-thematique ${moduleCode}, la valider et la retrouver dans le formulaire document`,
      tokenPrefix: `selenium-pending-${moduleCode}-subcategory`,
      titleSuffix: 'subtopic',
      moduleRegex: new RegExp(moduleCode, 'i'),
      submit: async (driver, title) => {
        await visit(driver, moduleCode, { propose_subcategory: '1' });
        const form = await driver.findElement(By.css('#member-document-subcategory-dialog form.member-document-dialog-form'));
        await setSelectValue(driver, await form.findElement(By.css('select[name="proposal_parent_category"]')), 'general');
        await setInputValue(driver, await form.findElement(By.css('input[name="proposal_subcategory_name"]')), title);
        await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_reason"]')), `Sous-thematique ${moduleCode} proposee par Selenium.`);
        await submitForm(driver, form);
      },
      afterAccept: async (driver, title) => {
        await assertSelectOptionContains(driver, moduleCode, { propose_document: '1' }, '#member-document-proposal-dialog select[name="subcategory_ref"] option', title, `La sous-thematique ${moduleCode} validee doit etre disponible pour proposer un document.`);
      },
    },
  ]),
  {
    name: 'Selenium membre: proposer un evenement, le valider et le retrouver dans agenda',
    tokenPrefix: 'selenium-pending-events',
    titleSuffix: 'event',
    moduleRegex: /event|agenda|v.nement/i,
    submit: async (driver, title) => {
      await visit(driver, 'events');
      await openDialog(driver, 'events-proposal-dialog');
      const form = await driver.findElement(By.css('#events-proposal-dialog form.events-proposal-form'));
      await form.findElement(By.css('input[name="proposal_title"]')).sendKeys(title);
      await form.findElement(By.css('input[name="proposal_datetime"]')).sendKeys(proposalDateTime(1));
      await form.findElement(By.css('input[name="proposal_location"]')).sendKeys('Durnal');
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_description"]')), 'Evenement Selenium propose puis valide.');
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertRouteContains(driver, 'events', {}, title, 'L evenement valide doit etre visible dans agenda.');
    },
  },
  {
    name: 'Selenium membre: proposer un lot enchere, le valider et le retrouver dans les encheres',
    tokenPrefix: 'selenium-pending-auctions',
    titleSuffix: 'lot',
    moduleRegex: /encher|auction/i,
    submit: async (driver, title) => {
      await visit(driver, 'auctions', { propose_lot: '1' });
      const form = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="propose_lot"]]'));
      await form.findElement(By.css('input[name="proposal_title"]')).sendKeys(title);
      await form.findElement(By.css('input[name="proposal_summary"]')).sendKeys('Lot Selenium.');
      await form.findElement(By.css('input[name="proposal_price"]')).sendKeys('12,50');
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_description"]')), 'Lot enchere propose par Selenium.');
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertRouteContains(driver, 'auctions', {}, title, 'Le lot valide doit etre visible dans les encheres.');
    },
  },
  {
    name: 'Selenium membre: proposer une actualite, la valider et la retrouver dans les actualites',
    tokenPrefix: 'selenium-pending-news',
    titleSuffix: 'post',
    moduleRegex: /actualit|news/i,
    submit: async (driver, title, token) => {
      await visit(driver, 'news');
      await openDialog(driver, 'news-proposal-dialog');
      const form = await driver.findElement(By.css('#news-proposal-dialog form.news-proposal-form'));
      await form.findElement(By.css('input[name="proposal_title"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_summary"]')), 'Actualite Selenium proposee puis validee.');
      await form.findElement(By.css('input[name="proposal_source"]')).sendKeys(`https://example.org/${token}`);
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertRouteContains(driver, 'news', { q: title }, title, 'L actualite validee doit etre visible dans les actualites.');
    },
  },
  {
    name: 'Selenium membre: proposer une rubrique actualites, la valider et la retrouver dans les actualites',
    tokenPrefix: 'selenium-pending-news-category',
    titleSuffix: 'rubrique',
    moduleRegex: /actualit|news/i,
    submit: async (driver, title) => {
      await visit(driver, 'news');
      await openDialog(driver, 'news-category-proposal-dialog');
      const form = await driver.findElement(By.css('#news-category-proposal-dialog form.news-proposal-form'));
      await form.findElement(By.css('input[name="proposal_category"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_reason"]')), 'Rubrique actualites proposee par Selenium.');
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertRouteContains(driver, 'news', {}, title, 'La rubrique actualites validee doit etre visible dans les actualites.');
    },
  },
  {
    name: 'Selenium membre: proposer une categorie article, la valider et la retrouver dans le formulaire article',
    tokenPrefix: 'selenium-pending-article-category',
    titleSuffix: 'theme',
    moduleRegex: /article/i,
    submit: async (driver, title) => {
      await visit(driver, 'articles');
      await openDialog(driver, 'articles-category-dialog');
      const form = await driver.findElement(By.css('#articles-category-dialog form.articles-category-form'));
      await form.findElement(By.css('input[name="proposal_category"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_reason"]')), 'Categorie article proposee par Selenium.');
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertSelectOptionContains(driver, 'article_propose', {}, 'select[name="category"] option', title, 'La categorie article validee doit etre disponible dans le formulaire article.');
    },
  },
  {
    name: 'Selenium membre: proposer un mot cle article et le valider dans Mes contenus',
    tokenPrefix: 'selenium-pending-article-tag',
    titleSuffix: 'tag',
    moduleRegex: /article/i,
    submit: async (driver, title) => {
      await visit(driver, 'articles');
      await openDialog(driver, 'articles-tag-dialog');
      const form = await driver.findElement(By.css('#articles-tag-dialog form.articles-category-form'));
      await form.findElement(By.css('input[name="proposal_tag"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_reason"]')), 'Mot cle article propose par Selenium.');
      await submitForm(driver, form);
    },
  },
  {
    name: 'Selenium membre: proposer une categorie petites annonces, la valider et la retrouver dans classifieds',
    tokenPrefix: 'selenium-pending-classified-category',
    titleSuffix: 'category',
    moduleRegex: /annonce|classified/i,
    submit: async (driver, title) => {
      await visit(driver, 'classifieds', { propose_category: '1' });
      const form = await driver.findElement(By.css('#classifieds-category-inline form.classifieds-category-form'));
      await form.findElement(By.css('input[name="proposal_category"]')).sendKeys(title);
      await setInputValue(driver, await form.findElement(By.css('input[name="proposal_name"]')), 'Selenium Member');
      await setInputValue(driver, await form.findElement(By.css('input[name="proposal_email"]')), 'selenium-member@example.test');
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_details"]')), 'Categorie petites annonces proposee par Selenium.');
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertRouteContains(driver, 'classifieds', {}, title, 'La categorie petites annonces validee doit etre visible dans classifieds.');
    },
  },
  {
    name: 'Selenium membre: proposer une thematique wiki, la valider et la retrouver dans le formulaire wiki',
    tokenPrefix: 'selenium-pending-wiki-category',
    titleSuffix: 'theme',
    moduleRegex: /wiki/i,
    submit: async (driver, title) => {
      await visit(driver, 'wiki');
      await openDialog(driver, 'wiki-theme-dialog');
      const form = await driver.findElement(By.css('#wiki-theme-dialog form.wiki-theme-form'));
      await form.findElement(By.css('input[name="proposal_theme"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_reason"]')), 'Thematique wiki proposee par Selenium.');
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertSelectOptionContains(driver, 'wiki_propose', {}, 'select[name="category"] option', title, 'La thematique wiki validee doit etre disponible dans le formulaire wiki.');
    },
  },
  {
    name: 'Selenium membre: proposer une categorie members_library, la valider et la retrouver dans le formulaire document',
    tokenPrefix: 'selenium-pending-library-category',
    titleSuffix: 'topic',
    moduleRegex: /biblioth|library/i,
    submit: async (driver, title) => {
      await visit(driver, 'members_library');
      await openDialog(driver, 'members-library-category-dialog');
      const form = await driver.findElement(By.css('#members-library-category-dialog form.members-library-dialog-form'));
      await form.findElement(By.css('input[name="proposal_category_name"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_reason"]')), 'Categorie bibliotheque proposee par Selenium.');
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertSelectOptionContains(driver, 'members_library', {}, '#members-library-document-dialog select[name="proposal_category"] option', title, 'La categorie members_library validee doit etre disponible pour un document.');
    },
  },
  {
    name: 'Selenium membre: proposer une sous-thematique members_library, la valider et la retrouver dans le formulaire document',
    tokenPrefix: 'selenium-pending-library-subcategory',
    titleSuffix: 'subtopic',
    moduleRegex: /biblioth|library/i,
    submit: async (driver, title) => {
      await visit(driver, 'members_library');
      await openDialog(driver, 'members-library-subcategory-dialog');
      const form = await driver.findElement(By.css('#members-library-subcategory-dialog form.members-library-dialog-form'));
      await form.findElement(By.css('input[name="proposal_subcategory_name"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_reason"]')), 'Sous-thematique bibliotheque proposee par Selenium.');
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertSelectOptionContains(driver, 'members_library', {}, '#members-library-document-dialog select[name="proposal_subcategory_ref"] option', title, 'La sous-thematique members_library validee doit etre disponible pour un document.');
    },
  },
  {
    name: 'Selenium membre: proposer un mot cle members_library et le valider dans Mes contenus',
    tokenPrefix: 'selenium-pending-library-tag',
    titleSuffix: 'tag',
    moduleRegex: /biblioth|library/i,
    submit: async (driver, title) => {
      await visit(driver, 'members_library');
      await openDialog(driver, 'members-library-tag-dialog');
      const form = await driver.findElement(By.css('#members-library-tag-dialog form.members-library-dialog-form'));
      await form.findElement(By.css('input[name="proposal_tag"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_reason"]')), 'Mot cle bibliotheque propose par Selenium.');
      await submitForm(driver, form);
    },
  },
  {
    name: 'Selenium membre: proposer une thematique webotheque, la valider et la retrouver dans le formulaire lien',
    tokenPrefix: 'selenium-pending-webotheque-domain',
    titleSuffix: 'domain',
    moduleRegex: /weboth|web library/i,
    submit: async (driver, title) => {
      await visit(driver, 'webotheque', { propose_domain: '1' });
      const form = await driver.findElement(By.css('#webotheque-domain-dialog form.webotheque-proposal-form'));
      await form.findElement(By.css('input[name="proposal_domain"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_details"]')), 'Thematique webotheque proposee par Selenium.');
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertSelectOptionContains(driver, 'webotheque', { propose_link: '1' }, '#webotheque-link-dialog select[name="category"] option', title, 'La thematique webotheque validee doit etre disponible pour un lien.');
    },
  },
  {
    name: 'Selenium membre: proposer une sous-thematique webotheque, la valider et la retrouver dans le formulaire lien',
    tokenPrefix: 'selenium-pending-webotheque-subcategory',
    titleSuffix: 'subtopic',
    moduleRegex: /weboth|web library/i,
    submit: async (driver, title) => {
      await visit(driver, 'webotheque', { propose_subcategory: '1' });
      const form = await driver.findElement(By.css('#webotheque-subcategory-dialog form.webotheque-proposal-form'));
      await setSelectValue(driver, await form.findElement(By.css('select[name="proposal_parent_category"]')), 'general');
      await form.findElement(By.css('input[name="proposal_subcategory"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_details"]')), 'Sous-thematique webotheque proposee par Selenium.');
      await submitForm(driver, form);
    },
    afterAccept: async (driver, title) => {
      await assertSelectOptionContains(driver, 'webotheque', { propose_link: '1' }, '#webotheque-link-dialog select[name="subcategory_ref"] option', title, 'La sous-thematique webotheque validee doit etre disponible pour proposer un lien.');
    },
  },
  {
    name: 'Selenium membre: proposer un mot cle webotheque et le valider dans Mes contenus',
    tokenPrefix: 'selenium-pending-webotheque-tag',
    titleSuffix: 'tag',
    moduleRegex: /weboth|web library/i,
    submit: async (driver, title) => {
      await visit(driver, 'webotheque', { propose_tag: '1' });
      const form = await driver.findElement(By.css('#webotheque-tag-dialog form.webotheque-proposal-form'));
      await form.findElement(By.css('input[name="proposal_tag"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_details"]')), 'Mot cle webotheque propose par Selenium.');
      await submitForm(driver, form);
    },
  },
];

for (const scenario of contentProposalScenarios) {
  test(scenario.name, async (t) => runContentProposalScenario(t, scenario));
}

test('Selenium membre: proposer un article, le publier en admin et le retrouver dans les articles', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `selenium-pending-article-${Date.now()}`;
  const title = `${token}-post`;
  cleanupPendingValidationRows(token);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      ensureStandardMember();

      await loginAsMember(driver);
      await visit(driver, 'article_propose');
      const form = await driver.findElement(By.css('form.stack'));
      await form.findElement(By.css('input[name="title"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="excerpt"]')), 'Resume article Selenium.');
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="content"]')), '<p>Article Selenium propose puis publie.</p>');
      await submitForm(driver, form);

      await driver.wait(until.urlContains('route=my_requests'), timeoutMs);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /attente|pending/i,
        moduleRegex: /article/i,
      });
      assert.equal(pendingValidationStatus('article', title), 'pending', 'L article membre doit etre en attente avant validation.');

      await publishArticleWithAdmin(driver, credentials, title);
      await assertRouteContains(driver, 'articles', { q: title }, title, 'L article publie doit etre visible dans les articles.');

      await loginAsMember(driver);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /publ|published/i,
        moduleRegex: /article/i,
      });
    } finally {
      cleanupPendingValidationRows(token);
    }
  });
});

test('Selenium membre: proposer une page wiki, la publier en admin et la retrouver dans le wiki', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `selenium-pending-wiki-page-${Date.now()}`;
  const title = `${token}-page`;
  cleanupPendingValidationRows(token);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      ensureStandardMember();

      await loginAsMember(driver);
      await visit(driver, 'wiki_propose');
      const form = await driver.findElement(By.css('form.wiki-edit-form'));
      await form.findElement(By.css('input[name="title"]')).sendKeys(title);
      await form.findElement(By.css('input[name="slug"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="content"]')), '<p>Page wiki Selenium proposee puis publiee.</p>');
      await submitForm(driver, form);

      await driver.wait(until.urlContains('route=my_requests'), timeoutMs);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /attente|pending/i,
        moduleRegex: /wiki/i,
      });
      assert.equal(pendingValidationStatus('wiki', title), 'pending', 'La page wiki membre doit etre en attente avant validation.');

      await publishWikiPageWithAdmin(driver, credentials, title);
      await assertRouteContains(driver, 'wiki', { q: title }, title, 'La page wiki publiee doit etre visible dans le wiki.');

      await loginAsMember(driver);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /publ|published/i,
        moduleRegex: /wiki/i,
      });
    } finally {
      cleanupPendingValidationRows(token);
    }
  });
});

test('Selenium membre: proposer une petite annonce, la valider et la retrouver dans classifieds', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `selenium-pending-classified-${Date.now()}`;
  const title = `${token}-ad`;
  cleanupPendingValidationRows(token);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      ensureStandardMember();

      await loginAsMember(driver);
      await visit(driver, 'classifieds_manage');
      const form = await driver.findElement(By.css('.classifieds-editor-form'));
      await form.findElement(By.css('input[name="title"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="description"]')), 'Annonce Selenium proposee puis validee.');
      await form.findElement(By.css('input[name="price"]')).clear();
      await form.findElement(By.css('input[name="price"]')).sendKeys('25,00');
      await form.findElement(By.css('input[name="location"]')).sendKeys('Durnal');
      await setInputValue(driver, await form.findElement(By.css('input[name="contact"]')), 'selenium-member@example.test');
      await setSelectValue(driver, await form.findElement(By.css('select[name="status"]')), 'active');
      await submitForm(driver, form);

      await visit(driver, 'my_requests');
      await assertMyRequestsCard(driver, title, {
        statusRegex: /attente|pending/i,
        moduleRegex: /annonce|classified/i,
      });
      assert.equal(pendingValidationStatus('classified', title), 'pending', 'L annonce membre doit etre en attente avant validation.');

      await activateClassifiedWithAdmin(driver, credentials, title);
      await assertRouteContains(driver, 'classifieds', { q: title }, title, 'L annonce validee doit etre visible dans classifieds.');

      await loginAsMember(driver);
      await assertMyRequestsCard(driver, title, {
        statusRegex: /active|actif/i,
        moduleRegex: /annonce|classified/i,
      });
    } finally {
      cleanupPendingValidationRows(token);
    }
  });
});
