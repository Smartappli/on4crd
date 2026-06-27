const test = require('node:test');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {
  By,
  assert,
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

const crc32Table = new Uint32Array(256);
for (let index = 0; index < 256; index += 1) {
  let value = index;
  for (let bit = 0; bit < 8; bit += 1) {
    value = (value & 1) ? (0xedb88320 ^ (value >>> 1)) : (value >>> 1);
  }
  crc32Table[index] = value >>> 0;
}

async function submitForm(driver, form, submitterSelector = null) {
  await driver.executeScript(`
    const form = arguments[0];
    const selector = arguments[1];
    const submitter = selector
      ? form.querySelector(selector)
      : form.querySelector('button[type="submit"], button:not([type="button"]), input[type="submit"]');
    window.confirm = () => true;
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit(submitter || undefined);
    } else {
      form.submit();
    }
  `, form, submitterSelector);
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
    }
  `, element, value);
}

async function selectValue(driver, select, value) {
  const selected = await driver.executeScript(`
    const select = arguments[0];
    const value = arguments[1];
    const option = Array.from(select.options).find((item) => item.value === value);
    if (!option) {
      return false;
    }
    select.value = value;
    select.dispatchEvent(new Event('input', { bubbles: true }));
    select.dispatchEvent(new Event('change', { bubbles: true }));
    return select.value === value;
  `, select, value);
  assert.equal(selected, true, `Option absente dans la liste: ${value}`);
}

function phpJson(source, extraEnv = {}) {
  const output = runSeleniumPhp(source, extraEnv).trim();
  return output === '' ? {} : JSON.parse(output);
}

function xpathLiteral(value) {
  if (!value.includes("'")) {
    return `'${value}'`;
  }
  if (!value.includes('"')) {
    return `"${value}"`;
  }

  return `concat('${value.replace(/'/g, `', "'", '`)}')`;
}

async function buttonDisabledByHidden(driver, action, hiddenName, hiddenValue) {
  return driver.executeScript(`
    const action = arguments[0];
    const hiddenName = arguments[1];
    const hiddenValue = arguments[2];
    const form = Array.from(document.forms).find((candidate) => {
      const actionInput = candidate.querySelector('input[name="action"]');
      const hidden = candidate.querySelector('input[name="' + hiddenName + '"]');
      const matchingSubmitter = Array.from(candidate.querySelectorAll('button, input[type="submit"]'))
        .some((submitter) => submitter.name === 'action' && submitter.value === action);
      return hidden && hidden.value === hiddenValue && ((actionInput && actionInput.value === action) || matchingSubmitter);
    });
    if (!form) {
      return null;
    }
    const submitters = Array.from(form.querySelectorAll('button, input[type="submit"]'));
    const button = submitters.find((submitter) => submitter.name === 'action' && submitter.value === action) || submitters[0];
    return button ? Boolean(button.disabled) : null;
  `, action, hiddenName, hiddenValue);
}

async function submitFormByHidden(driver, action, hiddenName, hiddenValue) {
  const submitted = await driver.executeScript(`
    const action = arguments[0];
    const hiddenName = arguments[1];
    const hiddenValue = arguments[2];
    const form = Array.from(document.forms).find((candidate) => {
      const actionInput = candidate.querySelector('input[name="action"]');
      const hidden = candidate.querySelector('input[name="' + hiddenName + '"]');
      const matchingSubmitter = Array.from(candidate.querySelectorAll('button, input[type="submit"]'))
        .some((submitter) => submitter.name === 'action' && submitter.value === action);
      return hidden && hidden.value === hiddenValue && ((actionInput && actionInput.value === action) || matchingSubmitter);
    });
    if (!form) {
      return false;
    }
    window.confirm = () => true;
    const submitters = Array.from(form.querySelectorAll('button[type="submit"], button:not([type="button"]), input[type="submit"]'));
    const submitter = submitters.find((candidate) => candidate.name === 'action' && candidate.value === action) || submitters[0];
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit(submitter || undefined);
    } else {
      form.submit();
    }
    return true;
  `, action, hiddenName, hiddenValue);
  assert.equal(submitted, true, `Formulaire introuvable: ${action} ${hiddenName}=${hiddenValue}`);
  await waitForDocumentReady(driver);
  await assertNoServerError(driver);
}

function createDocxFixture(token) {
  const safeToken = String(token).replace(/[^A-Za-z0-9_.-]/g, '-');
  const documentText = `Import DOCX Selenium ${safeToken}`;
  const contentTypes = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>`;
  const rels = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>`;
  const documentXml = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p><w:r><w:t>${escapeXml(documentText)}</w:t></w:r></w:p>
  </w:body>
</w:document>`;
  const filePath = path.join(os.tmpdir(), `${safeToken}.docx`);
  fs.writeFileSync(filePath, zipStore({
    '[Content_Types].xml': contentTypes,
    '_rels/.rels': rels,
    'word/document.xml': documentXml,
  }));
  return filePath;
}

function escapeXml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function zipStore(entries) {
  const localParts = [];
  const centralParts = [];
  let offset = 0;

  for (const [name, content] of Object.entries(entries)) {
    const nameBuffer = Buffer.from(name, 'utf8');
    const contentBuffer = Buffer.from(content, 'utf8');
    const crc = crc32(contentBuffer);

    const localHeader = Buffer.alloc(30);
    localHeader.writeUInt32LE(0x04034b50, 0);
    localHeader.writeUInt16LE(20, 4);
    localHeader.writeUInt16LE(0, 6);
    localHeader.writeUInt16LE(0, 8);
    localHeader.writeUInt16LE(0, 10);
    localHeader.writeUInt16LE(0, 12);
    localHeader.writeUInt32LE(crc, 14);
    localHeader.writeUInt32LE(contentBuffer.length, 18);
    localHeader.writeUInt32LE(contentBuffer.length, 22);
    localHeader.writeUInt16LE(nameBuffer.length, 26);
    localHeader.writeUInt16LE(0, 28);
    localParts.push(localHeader, nameBuffer, contentBuffer);

    const centralHeader = Buffer.alloc(46);
    centralHeader.writeUInt32LE(0x02014b50, 0);
    centralHeader.writeUInt16LE(20, 4);
    centralHeader.writeUInt16LE(20, 6);
    centralHeader.writeUInt16LE(0, 8);
    centralHeader.writeUInt16LE(0, 10);
    centralHeader.writeUInt16LE(0, 12);
    centralHeader.writeUInt16LE(0, 14);
    centralHeader.writeUInt32LE(crc, 16);
    centralHeader.writeUInt32LE(contentBuffer.length, 20);
    centralHeader.writeUInt32LE(contentBuffer.length, 24);
    centralHeader.writeUInt16LE(nameBuffer.length, 28);
    centralHeader.writeUInt16LE(0, 30);
    centralHeader.writeUInt16LE(0, 32);
    centralHeader.writeUInt16LE(0, 34);
    centralHeader.writeUInt16LE(0, 36);
    centralHeader.writeUInt32LE(0, 38);
    centralHeader.writeUInt32LE(offset, 42);
    centralParts.push(centralHeader, nameBuffer);

    offset += localHeader.length + nameBuffer.length + contentBuffer.length;
  }

  const centralDirectory = Buffer.concat(centralParts);
  const endHeader = Buffer.alloc(22);
  const entryCount = Object.keys(entries).length;
  endHeader.writeUInt32LE(0x06054b50, 0);
  endHeader.writeUInt16LE(0, 4);
  endHeader.writeUInt16LE(0, 6);
  endHeader.writeUInt16LE(entryCount, 8);
  endHeader.writeUInt16LE(entryCount, 10);
  endHeader.writeUInt32LE(centralDirectory.length, 12);
  endHeader.writeUInt32LE(offset, 16);
  endHeader.writeUInt16LE(0, 20);

  return Buffer.concat([...localParts, centralDirectory, endHeader]);
}

function crc32(buffer) {
  let crc = 0xffffffff;
  for (const byte of buffer) {
    crc = crc32Table[(crc ^ byte) & 0xff] ^ (crc >>> 8);
  }
  return (crc ^ 0xffffffff) >>> 0;
}

async function assertArticleDocxWysiwygImport(driver, token) {
  const fixturePath = createDocxFixture(token);
  assert.notEqual(fixturePath, '', 'Un fichier DOCX de test doit pouvoir etre genere.');

  const controlsReady = await driver.wait(async () => driver.executeScript(`
    const textarea = document.querySelector('textarea[name="content"][data-wysiwyg="full"]');
    const wrapper = textarea ? textarea.previousElementSibling : null;
    if (!textarea || !wrapper || !wrapper.classList.contains('wysiwyg')) {
      return null;
    }
    const importButton = Array.from(wrapper.querySelectorAll('.wysiwyg-toolbar button'))
      .find((button) => button.textContent.trim() === 'Importer Word');
    const fileInput = wrapper.querySelector('.wysiwyg-toolbar input[type="file"][accept*=".docx"]');
    const editor = wrapper.querySelector('.wysiwyg-editor[contenteditable="true"]');
    return importButton && fileInput && editor ? true : null;
  `), timeoutMs);
  assert.equal(controlsReady, true, 'Le WYSIWYG article doit exposer le bouton Importer Word.');

  const fileInput = await driver.findElement(By.css('.wysiwyg-toolbar input[type="file"][accept*=".docx"]'));
  await driver.executeScript(`
    const input = arguments[0];
    input.hidden = false;
    input.style.display = 'block';
  `, fileInput);
  await fileInput.sendKeys(fixturePath);

  const importedText = `Import DOCX Selenium ${token}`;
  const imported = await driver.wait(async () => driver.executeScript(`
    const expected = arguments[0];
    const textarea = document.querySelector('textarea[name="content"]');
    const wrapper = textarea ? textarea.previousElementSibling : null;
    const editor = wrapper ? wrapper.querySelector('.wysiwyg-editor[contenteditable="true"]') : null;
    if (!textarea || !editor) {
      return null;
    }
    return editor.textContent.includes(expected) && textarea.value.includes(expected) ? true : null;
  `, importedText), timeoutMs);
  assert.equal(imported, true, 'Le DOCX importe doit remplir l editeur WYSIWYG et le textarea article.');
}

async function submitProposalStatus(driver, title, status, note) {
  const form = await driver.findElement(By.xpath(
    `//article[contains(@class,"article-item")][.//h3[contains(normalize-space(.), ${xpathLiteral(title)})]]//form[.//input[@name="action" and @value="update_proposal_status"]]`,
  ));
  await selectValue(driver, await form.findElement(By.css('select[name="proposal_status"]')), status);
  await setFieldValue(driver, await form.findElement(By.css('textarea[name="moderation_note"]')), note);
  await submitForm(driver, form);
}

function cleanupArticleFixture(token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/article_helpers.php';
require_once 'app/content_helpers.php';
$token = trim((string) (getenv('SELENIUM_TOKEN') ?: ''));
if ($token !== '') {
    article_ensure_taxonomy_schema();
    ensure_content_proposals_table();
    $like = '%' . $token . '%';
    if (table_exists('articles')) {
        $stmt = db()->prepare('SELECT id FROM articles WHERE slug LIKE ? OR title LIKE ?');
        $stmt->execute([$like, $like]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
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
    if (table_exists('content_proposals')) {
        db()->prepare('DELETE FROM content_proposals WHERE title LIKE ? OR summary LIKE ?')->execute([$like, $like]);
    }
    if (table_exists('article_subsubcategories')) {
        db()->prepare('DELETE FROM article_subsubcategories WHERE category_code LIKE ? OR subcategory_code LIKE ? OR code LIKE ? OR label LIKE ?')->execute([$like, $like, $like, $like]);
    }
    if (table_exists('article_subcategories')) {
        db()->prepare('DELETE FROM article_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')->execute([$like, $like, $like]);
    }
    if (table_exists('article_categories')) {
        db()->prepare('DELETE FROM article_categories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
    }
}
`, { SELENIUM_TOKEN: token });
}

function articleTaxonomyByLabels(categoryLabel, subcategoryLabel, subsubcategoryLabel = '') {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/article_helpers.php';
article_ensure_taxonomy_schema();
$categoryLabel = (string) (getenv('SELENIUM_CATEGORY_LABEL') ?: '');
$subcategoryLabel = (string) (getenv('SELENIUM_SUBCATEGORY_LABEL') ?: '');
$subsubcategoryLabel = (string) (getenv('SELENIUM_SUBSUBCATEGORY_LABEL') ?: '');
$result = ['category' => null, 'subcategory' => null, 'subsubcategory' => null];
$stmt = db()->prepare('SELECT code, label, deleted_at FROM article_categories WHERE label = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$categoryLabel]);
$category = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
if (is_array($category)) {
    $result['category'] = $category;
}
$stmt = db()->prepare('SELECT category_code, code, label FROM article_subcategories WHERE label = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$subcategoryLabel]);
$subcategory = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
if (is_array($subcategory)) {
    $result['subcategory'] = $subcategory;
}
if ($subsubcategoryLabel !== '') {
    $stmt = db()->prepare('SELECT category_code, subcategory_code, code, label FROM article_subsubcategories WHERE label = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$subsubcategoryLabel]);
    $subsubcategory = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (is_array($subsubcategory)) {
        $result['subsubcategory'] = $subsubcategory;
    }
}
echo json_encode($result, JSON_THROW_ON_ERROR);
`, {
    SELENIUM_CATEGORY_LABEL: categoryLabel,
    SELENIUM_SUBCATEGORY_LABEL: subcategoryLabel,
    SELENIUM_SUBSUBCATEGORY_LABEL: subsubcategoryLabel,
  });
}

function articleCategoryByCode(code) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/article_helpers.php';
article_ensure_taxonomy_schema();
$code = (string) (getenv('SELENIUM_CATEGORY_CODE') ?: '');
$stmt = db()->prepare('SELECT code, label, deleted_at FROM article_categories WHERE code = ? LIMIT 1');
$stmt->execute([$code]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_THROW_ON_ERROR);
`, { SELENIUM_CATEGORY_CODE: code });
}

function articleRecordBySlug(slug) {
  return phpJson(`
require_once 'app/bootstrap.php';
$slug = (string) (getenv('SELENIUM_SLUG') ?: '');
$stmt = db()->prepare('SELECT id, author_id, title, slug, excerpt, content, status, category, subcategory, subsubcategory FROM articles WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_THROW_ON_ERROR);
`, { SELENIUM_SLUG: slug });
}

function setArticleTaxonomy(slug, category, subcategory, subsubcategory = '') {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/article_helpers.php';
article_ensure_taxonomy_schema();
$slug = (string) (getenv('SELENIUM_SLUG') ?: '');
$category = (string) (getenv('SELENIUM_CATEGORY_CODE') ?: 'autres');
$subcategory = (string) (getenv('SELENIUM_SUBCATEGORY_CODE') ?: '');
$subsubcategory = (string) (getenv('SELENIUM_SUBSUBCATEGORY_CODE') ?: '');
db()->prepare('UPDATE articles SET category = ?, subcategory = ?, subsubcategory = ?, updated_at = NOW() WHERE slug = ?')->execute([$category, $subcategory, $subsubcategory, $slug]);
`, {
    SELENIUM_SLUG: slug,
    SELENIUM_CATEGORY_CODE: category,
    SELENIUM_SUBCATEGORY_CODE: subcategory,
    SELENIUM_SUBSUBCATEGORY_CODE: subsubcategory,
  });
}

function articleRevisionCount(articleId) {
  return Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$id = (int) (getenv('SELENIUM_ARTICLE_ID') ?: 0);
if ($id <= 0 || !table_exists('article_revisions')) {
    echo '0';
    return;
}
$stmt = db()->prepare('SELECT COUNT(*) FROM article_revisions WHERE article_id = ?');
$stmt->execute([$id]);
echo (string) (int) ($stmt->fetchColumn() ?: 0);
`, { SELENIUM_ARTICLE_ID: String(articleId) }).trim() || '0');
}

function createContentProposal(area, title, summary) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/content_helpers.php';
ensure_content_proposals_table();
$area = (string) (getenv('SELENIUM_AREA') ?: '');
$title = (string) (getenv('SELENIUM_PROPOSAL_TITLE') ?: '');
$summary = (string) (getenv('SELENIUM_PROPOSAL_SUMMARY') ?: '');
$memberId = (int) (db()->query('SELECT id FROM members ORDER BY id ASC LIMIT 1')->fetchColumn() ?: 0);
if ($memberId <= 0) {
    throw new RuntimeException('Aucun membre disponible pour la proposition Selenium.');
}
$stmt = db()->prepare('INSERT INTO content_proposals (member_id, area, proposal_type, title, summary, contact, source_ref, status) VALUES (?, ?, "content", ?, ?, "selenium@example.test", NULL, "pending")');
$stmt->execute([$memberId, $area, $title, $summary]);
echo json_encode(['id' => (int) db()->lastInsertId()], JSON_THROW_ON_ERROR);
`, {
    SELENIUM_AREA: area,
    SELENIUM_PROPOSAL_TITLE: title,
    SELENIUM_PROPOSAL_SUMMARY: summary,
  });
}

function proposalRecord(id) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/content_helpers.php';
ensure_content_proposals_table();
$id = (int) (getenv('SELENIUM_PROPOSAL_ID') ?: 0);
$stmt = db()->prepare('SELECT id, area, proposal_type, title, summary, status, moderation_note FROM content_proposals WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_THROW_ON_ERROR);
`, { SELENIUM_PROPOSAL_ID: String(id) });
}

function cleanupWikiFixture(token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/content_helpers.php';
$token = trim((string) (getenv('SELENIUM_TOKEN') ?: ''));
if ($token !== '') {
    ensure_wiki_tables();
    ensure_content_proposals_table();
    $like = '%' . $token . '%';
    if (table_exists('wiki_pages')) {
        $stmt = db()->prepare('SELECT id FROM wiki_pages WHERE slug LIKE ? OR title LIKE ? OR target_slug LIKE ?');
        $stmt->execute([$like, $like, $like]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
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
    if (table_exists('content_proposals')) {
        db()->prepare('DELETE FROM content_proposals WHERE title LIKE ? OR summary LIKE ?')->execute([$like, $like]);
    }
    if (table_exists('wiki_subsubcategories')) {
        db()->prepare('DELETE FROM wiki_subsubcategories WHERE category_code LIKE ? OR subcategory_code LIKE ? OR code LIKE ? OR label LIKE ?')->execute([$like, $like, $like, $like]);
    }
    if (table_exists('wiki_subcategories')) {
        db()->prepare('DELETE FROM wiki_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')->execute([$like, $like, $like]);
    }
    if (table_exists('wiki_categories')) {
        db()->prepare('DELETE FROM wiki_categories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
    }
}
`, { SELENIUM_TOKEN: token });
}

function prepareWikiFixture(token, pageTitle, pageSlug, proposalTitle) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/content_helpers.php';
ensure_wiki_tables();
ensure_content_proposals_table();
$pageTitle = (string) (getenv('SELENIUM_PAGE_TITLE') ?: '');
$pageSlug = (string) (getenv('SELENIUM_PAGE_SLUG') ?: '');
$proposalTitle = (string) (getenv('SELENIUM_PROPOSAL_TITLE') ?: '');
$memberId = (int) (db()->query('SELECT id FROM members ORDER BY id ASC LIMIT 1')->fetchColumn() ?: 0);
if ($memberId <= 0) {
    throw new RuntimeException('Aucun membre disponible pour la fixture wiki Selenium.');
}
$stmt = db()->prepare('INSERT INTO wiki_pages (title, slug, content, category, subcategory, subsubcategory, author_id, status, proposal_kind) VALUES (?, ?, ?, "general", "", "", ?, "pending", "page")');
$stmt->execute([$pageTitle, $pageSlug, '<p>Contenu wiki Selenium initial.</p>', $memberId]);
$pageId = (int) db()->lastInsertId();
$stmt = db()->prepare('INSERT INTO content_proposals (member_id, area, proposal_type, title, summary, contact, source_ref, status) VALUES (?, "wiki", "content", ?, ?, "selenium@example.test", NULL, "pending")');
$stmt->execute([$memberId, $proposalTitle, 'Proposition wiki Selenium ' . (string) (getenv('SELENIUM_TOKEN') ?: '')]);
echo json_encode(['pageId' => $pageId, 'proposalId' => (int) db()->lastInsertId()], JSON_THROW_ON_ERROR);
`, {
    SELENIUM_TOKEN: token,
    SELENIUM_PAGE_TITLE: pageTitle,
    SELENIUM_PAGE_SLUG: pageSlug,
    SELENIUM_PROPOSAL_TITLE: proposalTitle,
  });
}

function wikiTaxonomyByLabels(categoryLabel, subcategoryLabel, subsubcategoryLabel = '') {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/content_helpers.php';
ensure_wiki_tables();
$categoryLabel = (string) (getenv('SELENIUM_CATEGORY_LABEL') ?: '');
$subcategoryLabel = (string) (getenv('SELENIUM_SUBCATEGORY_LABEL') ?: '');
$subsubcategoryLabel = (string) (getenv('SELENIUM_SUBSUBCATEGORY_LABEL') ?: '');
$result = ['category' => null, 'subcategory' => null, 'subsubcategory' => null];
$stmt = db()->prepare('SELECT code, label, deleted_at FROM wiki_categories WHERE label = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$categoryLabel]);
$category = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
if (is_array($category)) {
    $result['category'] = $category;
}
$stmt = db()->prepare('SELECT category_code, code, label FROM wiki_subcategories WHERE label = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$subcategoryLabel]);
$subcategory = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
if (is_array($subcategory)) {
    $result['subcategory'] = $subcategory;
}
if ($subsubcategoryLabel !== '') {
    $stmt = db()->prepare('SELECT category_code, subcategory_code, code, label FROM wiki_subsubcategories WHERE label = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$subsubcategoryLabel]);
    $subsubcategory = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (is_array($subsubcategory)) {
        $result['subsubcategory'] = $subsubcategory;
    }
}
echo json_encode($result, JSON_THROW_ON_ERROR);
`, {
    SELENIUM_CATEGORY_LABEL: categoryLabel,
    SELENIUM_SUBCATEGORY_LABEL: subcategoryLabel,
    SELENIUM_SUBSUBCATEGORY_LABEL: subsubcategoryLabel,
  });
}

function wikiCategoryByCode(code) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/content_helpers.php';
ensure_wiki_tables();
$code = (string) (getenv('SELENIUM_CATEGORY_CODE') ?: '');
$stmt = db()->prepare('SELECT code, label, deleted_at FROM wiki_categories WHERE code = ? LIMIT 1');
$stmt->execute([$code]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_THROW_ON_ERROR);
`, { SELENIUM_CATEGORY_CODE: code });
}

function wikiRecordBySlug(slug) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/content_helpers.php';
ensure_wiki_tables();
$slug = (string) (getenv('SELENIUM_SLUG') ?: '');
$stmt = db()->prepare('SELECT id, author_id, title, slug, content, status, category, subcategory, subsubcategory, proposal_kind FROM wiki_pages WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_THROW_ON_ERROR);
`, { SELENIUM_SLUG: slug });
}

function setWikiPageTaxonomy(slug, category, subcategory, subsubcategory = '') {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/content_helpers.php';
ensure_wiki_tables();
$slug = (string) (getenv('SELENIUM_SLUG') ?: '');
$category = (string) (getenv('SELENIUM_CATEGORY_CODE') ?: 'general');
$subcategory = (string) (getenv('SELENIUM_SUBCATEGORY_CODE') ?: '');
$subsubcategory = (string) (getenv('SELENIUM_SUBSUBCATEGORY_CODE') ?: '');
db()->prepare('UPDATE wiki_pages SET category = ?, subcategory = ?, subsubcategory = ?, updated_at = NOW() WHERE slug = ?')->execute([$category, $subcategory, $subsubcategory, $slug]);
`, {
    SELENIUM_SLUG: slug,
    SELENIUM_CATEGORY_CODE: category,
    SELENIUM_SUBCATEGORY_CODE: subcategory,
    SELENIUM_SUBSUBCATEGORY_CODE: subsubcategory,
  });
}

test('Selenium admin articles: taxonomie, apercu, revisions, suppression et proposition', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const suffix = String(Date.now());
  const token = `selenium-article-${suffix}`;
  const slug = token;
  const title = `Selenium article ${suffix}`;
  const updatedTitle = `Selenium article modifie ${suffix}`;
  const categoryLabel = `Selenium article theme ${suffix}`;
  const subcategoryLabel = `Selenium article sous theme ${suffix}`;
  const subsubcategoryLabel = `Selenium article sous sous theme ${suffix}`;
  const updatedSubsubcategoryLabel = `Selenium article sous sous theme updated ${suffix}`;
  const proposalTitle = `Selenium article proposition ${suffix}`;
  const proposalNote = `Moderation article Selenium ${suffix}`;

  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupArticleFixture(token);

  await withSelenium(t, async (driver) => {
    try {
      const proposal = createContentProposal('articles', proposalTitle, `Resume proposition ${token}`);
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_articles');
      await assertArticleDocxWysiwygImport(driver, token);

      const categoryForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_category"]]'));
      await categoryForm.findElement(By.css('input[name="category_label"]')).sendKeys(categoryLabel);
      await submitForm(driver, categoryForm);

      let taxonomy = articleTaxonomyByLabels(categoryLabel, subcategoryLabel);
      assert.ok(taxonomy.category && taxonomy.category.code, 'La thematique article doit etre creee.');
      assert.equal(taxonomy.category.label, categoryLabel, 'Le libelle de thematique article doit etre persiste.');

      const subcategoryForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_subcategory"]]'));
      await selectValue(driver, await subcategoryForm.findElement(By.css('select[name="subcategory_category"]')), taxonomy.category.code);
      await subcategoryForm.findElement(By.css('input[name="subcategory_label"]')).sendKeys(subcategoryLabel);
      await submitForm(driver, subcategoryForm);

      taxonomy = articleTaxonomyByLabels(categoryLabel, subcategoryLabel);
      assert.ok(taxonomy.subcategory && taxonomy.subcategory.code, 'La sous-thematique article doit etre creee.');
      const categoryCode = taxonomy.category.code;
      const subcategoryCode = taxonomy.subcategory.code;
      const subcategoryRef = `${categoryCode}:${subcategoryCode}`;
      assert.equal(taxonomy.subcategory.category_code, categoryCode, 'La sous-thematique article doit etre rattachee a la thematique creee.');
      assert.equal(taxonomy.subcategory.label, subcategoryLabel, 'Le libelle de sous-thematique article doit etre persiste.');

      const subsubcategoryForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_subsubcategory"]]'));
      await selectValue(driver, await subsubcategoryForm.findElement(By.css('select[name="subsubcategory_parent_ref"]')), subcategoryRef);
      await subsubcategoryForm.findElement(By.css('input[name="subsubcategory_label"]')).sendKeys(subsubcategoryLabel);
      await submitForm(driver, subsubcategoryForm);

      taxonomy = articleTaxonomyByLabels(categoryLabel, subcategoryLabel, subsubcategoryLabel);
      assert.ok(taxonomy.subsubcategory && taxonomy.subsubcategory.code, 'La sous-sous-thematique article doit etre creee.');
      const subsubcategoryCode = taxonomy.subsubcategory.code;
      const subsubcategoryRef = `${categoryCode}:${subcategoryCode}:${subsubcategoryCode}`;
      assert.equal(taxonomy.subsubcategory.category_code, categoryCode, 'La sous-sous-thematique article doit etre rattachee a la thematique creee.');
      assert.equal(taxonomy.subsubcategory.subcategory_code, subcategoryCode, 'La sous-sous-thematique article doit etre rattachee a la sous-thematique creee.');
      assert.equal(taxonomy.subsubcategory.label, subsubcategoryLabel, 'Le libelle de sous-sous-thematique article doit etre persiste.');

      const subsubcategoryUpdateForm = await driver.findElement(By.xpath(`//form[.//input[@name="action" and @value="update_subsubcategory"] and .//input[@name="subsubcategory_category" and @value="${categoryCode}"] and .//input[@name="subsubcategory_parent" and @value="${subcategoryCode}"] and .//input[@name="subsubcategory_code" and @value="${subsubcategoryCode}"]]`));
      await subsubcategoryUpdateForm.findElement(By.css('input[name="subsubcategory_label"]')).clear();
      await subsubcategoryUpdateForm.findElement(By.css('input[name="subsubcategory_label"]')).sendKeys(updatedSubsubcategoryLabel);
      await submitForm(driver, subsubcategoryUpdateForm);

      taxonomy = articleTaxonomyByLabels(categoryLabel, subcategoryLabel, updatedSubsubcategoryLabel);
      assert.ok(taxonomy.subsubcategory && taxonomy.subsubcategory.code, 'La sous-sous-thematique article doit exister apres modification.');
      assert.equal(taxonomy.subsubcategory.label, updatedSubsubcategoryLabel, 'Le libelle de sous-sous-thematique article doit etre modifie.');

      const createForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save_article"]]'));
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="title"]')), title);
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="slug"]')), slug);
      await selectValue(driver, await createForm.findElement(By.css('select[name="category"]')), categoryCode);
      await selectValue(driver, await createForm.findElement(By.css('select[name="subcategory_ref"]')), subcategoryRef);
      await selectValue(driver, await createForm.findElement(By.css('select[name="subsubcategory_ref"]')), subsubcategoryRef);
      await setFieldValue(driver, await createForm.findElement(By.css('textarea[name="excerpt"]')), `Extrait article ${token}`);
      await setFieldValue(driver, await createForm.findElement(By.css('textarea[name="content"]')), `<p>Contenu initial article ${token}</p>`);
      await selectValue(driver, await createForm.findElement(By.css('select[name="status"]')), 'draft');
      await submitForm(driver, createForm, 'button[name="action"][value="preview_article"]');

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'La previsualisation article doit afficher le titre.');
      assert.match(text, new RegExp(`Contenu initial article ${token}`), 'La previsualisation article doit afficher le contenu.');
      assert.equal(articleRecordBySlug(slug), null, 'La previsualisation ne doit pas creer l article.');

      const previewForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save_article"]]'));
      await submitForm(driver, previewForm, ':scope > button.button:not([name])');

      let article = articleRecordBySlug(slug);
      assert.ok(article && Number(article.id) > 0, 'L article doit etre enregistre.');
      assert.ok(Number(article.author_id) > 0, 'L article enregistre doit etre rattache a un auteur.');
      assert.equal(article.title, title, 'Le titre article doit etre persiste.');
      assert.equal(article.slug, slug, 'Le slug article doit etre persiste.');
      assert.equal(article.excerpt, `Extrait article ${token}`, 'L extrait article doit etre persiste.');
      assert.match(article.content, new RegExp(`Contenu initial article ${token}`), 'Le contenu article initial doit etre persiste.');
      assert.equal(article.status, 'draft');
      assert.equal(article.category, categoryCode);
      assert.equal(article.subcategory, subcategoryCode);
      assert.equal(article.subsubcategory, subsubcategoryCode);

      await visit(driver, 'admin_articles');
      const subDeleteDisabled = await buttonDisabledByHidden(driver, 'delete_subcategory', 'subcategory_ref', subcategoryRef);
      assert.equal(subDeleteDisabled, true, 'La sous-thematique article utilisee ne doit pas etre supprimable.');
      const subsubDeleteDisabled = await buttonDisabledByHidden(driver, 'delete_subsubcategory', 'subsubcategory_code', subsubcategoryCode);
      assert.equal(subsubDeleteDisabled, true, 'La sous-sous-thematique article utilisee ne doit pas etre supprimable.');

      await visit(driver, 'admin_articles', { id: article.id });
      const editForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save_article"]]'));
      await setFieldValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
      await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="content"]')), `<p>Contenu modifie article ${token}</p>`);
      await submitForm(driver, editForm, ':scope > button.button:not([name])');

      article = articleRecordBySlug(slug);
      assert.equal(article.title, updatedTitle);
      assert.match(article.content, /Contenu modifie article/);
      assert.ok(articleRevisionCount(article.id) > 0, 'La modification article doit creer au moins une revision en base.');

      await visit(driver, 'admin_articles', { id: article.id });
      const revisionForms = await driver.findElements(By.xpath('//form[.//input[@name="action" and @value="restore_revision"]]'));
      assert.ok(revisionForms.length > 0, 'Une revision article doit etre disponible apres modification.');
      await submitForm(driver, revisionForms[0]);

      article = articleRecordBySlug(slug);
      assert.equal(article.title, title, 'La restauration de revision doit remettre le titre initial.');
      assert.match(article.content, /Contenu initial article/, 'La restauration de revision doit remettre le contenu initial.');

      await visit(driver, 'admin_articles', { status: 'pending' });
      await submitProposalStatus(driver, proposalTitle, 'rejected', proposalNote);
      const moderatedProposal = proposalRecord(proposal.id);
      assert.equal(moderatedProposal.area, 'articles');
      assert.equal(moderatedProposal.proposal_type, 'content');
      assert.equal(moderatedProposal.title, proposalTitle);
      assert.match(moderatedProposal.summary, new RegExp(token), 'La proposition article doit conserver son resume en base.');
      assert.equal(moderatedProposal.status, 'rejected');
      assert.equal(moderatedProposal.moderation_note, proposalNote);

      await visit(driver, 'admin_articles', { id: article.id });
      await submitFormByHidden(driver, 'delete_article', 'id', String(article.id));
      assert.equal(articleRecordBySlug(slug), null, 'L article doit etre supprime.');

      await visit(driver, 'admin_articles');
      await submitFormByHidden(driver, 'delete_subsubcategory', 'subsubcategory_code', subsubcategoryCode);
      taxonomy = articleTaxonomyByLabels(categoryLabel, subcategoryLabel, updatedSubsubcategoryLabel);
      assert.equal(taxonomy.subsubcategory, null, 'La sous-sous-thematique article vide doit etre supprimee.');

      await submitFormByHidden(driver, 'delete_subcategory', 'subcategory_ref', subcategoryRef);
      taxonomy = articleTaxonomyByLabels(categoryLabel, subcategoryLabel);
      assert.equal(taxonomy.subcategory, null, 'La sous-thematique article vide doit etre supprimee.');

      await submitFormByHidden(driver, 'delete_category', 'category_code', categoryCode);
      const deletedCategory = articleCategoryByCode(categoryCode);
      assert.ok(deletedCategory === null || deletedCategory.deleted_at !== null, 'La thematique article doit etre supprimee ou marquee supprimee.');
    } finally {
      cleanupArticleFixture(token);
    }
  });
});

test('Selenium admin wiki: taxonomie, statut de page et proposition', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const suffix = String(Date.now());
  const token = `selenium-wiki-${suffix}`;
  const pageSlug = token;
  const pageTitle = `Selenium wiki ${suffix}`;
  const categoryLabel = `Selenium wiki theme ${suffix}`;
  const subcategoryLabel = `Selenium wiki sous theme ${suffix}`;
  const subsubcategoryLabel = `Selenium wiki sous sous theme ${suffix}`;
  const updatedSubsubcategoryLabel = `Selenium wiki sous sous theme updated ${suffix}`;
  const proposalTitle = `Selenium wiki proposition ${suffix}`;
  const proposalNote = `Moderation wiki Selenium ${suffix}`;

  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupWikiFixture(token);
  const fixture = prepareWikiFixture(token, pageTitle, pageSlug, proposalTitle);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_wiki');

      const categoryForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_category"]]'));
      await categoryForm.findElement(By.css('input[name="category_label"]')).sendKeys(categoryLabel);
      await submitForm(driver, categoryForm);

      let taxonomy = wikiTaxonomyByLabels(categoryLabel, subcategoryLabel);
      assert.ok(taxonomy.category && taxonomy.category.code, 'La thematique wiki doit etre creee.');
      assert.equal(taxonomy.category.label, categoryLabel, 'Le libelle de thematique wiki doit etre persiste.');

      const subcategoryForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_subcategory"]]'));
      await selectValue(driver, await subcategoryForm.findElement(By.css('select[name="subcategory_category"]')), taxonomy.category.code);
      await subcategoryForm.findElement(By.css('input[name="subcategory_label"]')).sendKeys(subcategoryLabel);
      await submitForm(driver, subcategoryForm);

      taxonomy = wikiTaxonomyByLabels(categoryLabel, subcategoryLabel);
      assert.ok(taxonomy.subcategory && taxonomy.subcategory.code, 'La sous-thematique wiki doit etre creee.');
      const categoryCode = taxonomy.category.code;
      const subcategoryCode = taxonomy.subcategory.code;
      const subcategoryRef = `${categoryCode}:${subcategoryCode}`;
      assert.equal(taxonomy.subcategory.category_code, categoryCode, 'La sous-thematique wiki doit etre rattachee a la thematique creee.');
      assert.equal(taxonomy.subcategory.label, subcategoryLabel, 'Le libelle de sous-thematique wiki doit etre persiste.');

      const subsubcategoryForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_subsubcategory"]]'));
      await selectValue(driver, await subsubcategoryForm.findElement(By.css('select[name="subsubcategory_parent_ref"]')), subcategoryRef);
      await subsubcategoryForm.findElement(By.css('input[name="subsubcategory_label"]')).sendKeys(subsubcategoryLabel);
      await submitForm(driver, subsubcategoryForm);

      taxonomy = wikiTaxonomyByLabels(categoryLabel, subcategoryLabel, subsubcategoryLabel);
      assert.ok(taxonomy.subsubcategory && taxonomy.subsubcategory.code, 'La sous-sous-thematique wiki doit etre creee.');
      const subsubcategoryCode = taxonomy.subsubcategory.code;
      assert.equal(taxonomy.subsubcategory.category_code, categoryCode, 'La sous-sous-thematique wiki doit etre rattachee a la thematique creee.');
      assert.equal(taxonomy.subsubcategory.subcategory_code, subcategoryCode, 'La sous-sous-thematique wiki doit etre rattachee a la sous-thematique creee.');
      assert.equal(taxonomy.subsubcategory.label, subsubcategoryLabel, 'Le libelle de sous-sous-thematique wiki doit etre persiste.');

      const subsubcategoryUpdateForm = await driver.findElement(By.xpath(`//form[.//input[@name="action" and @value="update_subsubcategory"] and .//input[@name="subsubcategory_category" and @value="${categoryCode}"] and .//input[@name="subsubcategory_parent" and @value="${subcategoryCode}"] and .//input[@name="subsubcategory_code" and @value="${subsubcategoryCode}"]]`));
      await subsubcategoryUpdateForm.findElement(By.css('input[name="subsubcategory_label"]')).clear();
      await subsubcategoryUpdateForm.findElement(By.css('input[name="subsubcategory_label"]')).sendKeys(updatedSubsubcategoryLabel);
      await submitForm(driver, subsubcategoryUpdateForm);

      taxonomy = wikiTaxonomyByLabels(categoryLabel, subcategoryLabel, updatedSubsubcategoryLabel);
      assert.ok(taxonomy.subsubcategory && taxonomy.subsubcategory.code, 'La sous-sous-thematique wiki doit exister apres modification.');
      assert.equal(taxonomy.subsubcategory.label, updatedSubsubcategoryLabel, 'Le libelle de sous-sous-thematique wiki doit etre modifie.');

      setWikiPageTaxonomy(pageSlug, categoryCode, subcategoryCode, subsubcategoryCode);
      await visit(driver, 'admin_wiki');
      const subDeleteDisabled = await buttonDisabledByHidden(driver, 'delete_subcategory', 'subcategory_ref', subcategoryRef);
      assert.equal(subDeleteDisabled, true, 'La sous-thematique wiki utilisee ne doit pas etre supprimable.');
      const subsubDeleteDisabled = await buttonDisabledByHidden(driver, 'delete_subsubcategory', 'subsubcategory_code', subsubcategoryCode);
      assert.equal(subsubDeleteDisabled, true, 'La sous-sous-thematique wiki utilisee ne doit pas etre supprimable.');

      const pageStatusForm = await driver.findElement(By.xpath(
        `//tr[.//td[contains(normalize-space(.), ${xpathLiteral(pageTitle)})]]//form[.//select[@name="status"]]`,
      ));
      await selectValue(driver, await pageStatusForm.findElement(By.css('select[name="status"]')), 'published');
      await submitForm(driver, pageStatusForm);

      let wikiPage = wikiRecordBySlug(pageSlug);
      assert.ok(Number(wikiPage.author_id) > 0, 'La page wiki doit etre rattachee a un auteur.');
      assert.equal(wikiPage.title, pageTitle, 'Le titre wiki doit etre conserve en base.');
      assert.equal(wikiPage.slug, pageSlug, 'Le slug wiki doit etre conserve en base.');
      assert.match(wikiPage.content, /Contenu wiki Selenium initial/, 'Le contenu wiki initial doit etre conserve en base.');
      assert.equal(wikiPage.status, 'published');
      assert.equal(Number(wikiPage.id), Number(fixture.pageId));
      assert.equal(wikiPage.category, categoryCode, 'La thematique wiki appliquee doit etre persistee.');
      assert.equal(wikiPage.subcategory, subcategoryCode, 'La sous-thematique wiki appliquee doit etre persistee.');
      assert.equal(wikiPage.subsubcategory, subsubcategoryCode, 'La sous-sous-thematique wiki appliquee doit etre persistee.');
      assert.equal(wikiPage.proposal_kind, 'page', 'Le type de proposition wiki doit rester page.');

      setWikiPageTaxonomy(pageSlug, 'general', '');
      await visit(driver, 'admin_wiki');
      await submitFormByHidden(driver, 'delete_subsubcategory', 'subsubcategory_code', subsubcategoryCode);
      taxonomy = wikiTaxonomyByLabels(categoryLabel, subcategoryLabel, updatedSubsubcategoryLabel);
      assert.equal(taxonomy.subsubcategory, null, 'La sous-sous-thematique wiki vide doit etre supprimee.');

      await submitFormByHidden(driver, 'delete_subcategory', 'subcategory_ref', subcategoryRef);
      taxonomy = wikiTaxonomyByLabels(categoryLabel, subcategoryLabel);
      assert.equal(taxonomy.subcategory, null, 'La sous-thematique wiki vide doit etre supprimee.');

      await submitFormByHidden(driver, 'delete_category', 'category_code', categoryCode);
      const deletedCategory = wikiCategoryByCode(categoryCode);
      assert.ok(deletedCategory === null || deletedCategory.deleted_at !== null, 'La thematique wiki doit etre supprimee ou marquee supprimee.');

      await visit(driver, 'admin_wiki', { status: 'pending' });
      await submitProposalStatus(driver, proposalTitle, 'reviewed', proposalNote);
      const moderatedProposal = proposalRecord(fixture.proposalId);
      assert.equal(moderatedProposal.area, 'wiki');
      assert.equal(moderatedProposal.proposal_type, 'content');
      assert.equal(moderatedProposal.title, proposalTitle);
      assert.match(moderatedProposal.summary, new RegExp(token), 'La proposition wiki doit conserver son resume en base.');
      assert.equal(moderatedProposal.status, 'reviewed');
      assert.equal(moderatedProposal.moderation_note, proposalNote);

      wikiPage = wikiRecordBySlug(pageSlug);
      assert.equal(wikiPage.status, 'published');
    } finally {
      cleanupWikiFixture(token);
    }
  });
});
