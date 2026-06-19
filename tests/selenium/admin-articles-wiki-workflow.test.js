const test = require('node:test');
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
      return actionInput && hidden && actionInput.value === action && hidden.value === hiddenValue;
    });
    if (!form) {
      return null;
    }
    const button = form.querySelector('button, input[type="submit"]');
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
      return actionInput && hidden && actionInput.value === action && hidden.value === hiddenValue;
    });
    if (!form) {
      return false;
    }
    window.confirm = () => true;
    const submitter = form.querySelector('button[type="submit"], button:not([type="button"]), input[type="submit"]');
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
    if (table_exists('article_subcategories')) {
        db()->prepare('DELETE FROM article_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')->execute([$like, $like, $like]);
    }
    if (table_exists('article_categories')) {
        db()->prepare('DELETE FROM article_categories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
    }
}
`, { SELENIUM_TOKEN: token });
}

function articleTaxonomyByLabels(categoryLabel, subcategoryLabel) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/article_helpers.php';
article_ensure_taxonomy_schema();
$categoryLabel = (string) (getenv('SELENIUM_CATEGORY_LABEL') ?: '');
$subcategoryLabel = (string) (getenv('SELENIUM_SUBCATEGORY_LABEL') ?: '');
$result = ['category' => null, 'subcategory' => null];
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
echo json_encode($result, JSON_THROW_ON_ERROR);
`, {
    SELENIUM_CATEGORY_LABEL: categoryLabel,
    SELENIUM_SUBCATEGORY_LABEL: subcategoryLabel,
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
$stmt = db()->prepare('SELECT id, title, slug, excerpt, content, status, category, subcategory FROM articles WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_THROW_ON_ERROR);
`, { SELENIUM_SLUG: slug });
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
$stmt = db()->prepare('SELECT id, area, title, status, moderation_note FROM content_proposals WHERE id = ? LIMIT 1');
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
$stmt = db()->prepare('INSERT INTO wiki_pages (title, slug, content, category, subcategory, author_id, status, proposal_kind) VALUES (?, ?, ?, "general", "", ?, "pending", "page")');
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

function wikiTaxonomyByLabels(categoryLabel, subcategoryLabel) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/content_helpers.php';
ensure_wiki_tables();
$categoryLabel = (string) (getenv('SELENIUM_CATEGORY_LABEL') ?: '');
$subcategoryLabel = (string) (getenv('SELENIUM_SUBCATEGORY_LABEL') ?: '');
$result = ['category' => null, 'subcategory' => null];
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
echo json_encode($result, JSON_THROW_ON_ERROR);
`, {
    SELENIUM_CATEGORY_LABEL: categoryLabel,
    SELENIUM_SUBCATEGORY_LABEL: subcategoryLabel,
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
$stmt = db()->prepare('SELECT id, title, slug, content, status, category, subcategory FROM wiki_pages WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_THROW_ON_ERROR);
`, { SELENIUM_SLUG: slug });
}

function setWikiPageTaxonomy(slug, category, subcategory) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/content_helpers.php';
ensure_wiki_tables();
$slug = (string) (getenv('SELENIUM_SLUG') ?: '');
$category = (string) (getenv('SELENIUM_CATEGORY_CODE') ?: 'general');
$subcategory = (string) (getenv('SELENIUM_SUBCATEGORY_CODE') ?: '');
db()->prepare('UPDATE wiki_pages SET category = ?, subcategory = ?, updated_at = NOW() WHERE slug = ?')->execute([$category, $subcategory, $slug]);
`, {
    SELENIUM_SLUG: slug,
    SELENIUM_CATEGORY_CODE: category,
    SELENIUM_SUBCATEGORY_CODE: subcategory,
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

      const categoryForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_category"]]'));
      await categoryForm.findElement(By.css('input[name="category_label"]')).sendKeys(categoryLabel);
      await submitForm(driver, categoryForm);

      let taxonomy = articleTaxonomyByLabels(categoryLabel, subcategoryLabel);
      assert.ok(taxonomy.category && taxonomy.category.code, 'La thematique article doit etre creee.');

      const subcategoryForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_subcategory"]]'));
      await selectValue(driver, await subcategoryForm.findElement(By.css('select[name="subcategory_category"]')), taxonomy.category.code);
      await subcategoryForm.findElement(By.css('input[name="subcategory_label"]')).sendKeys(subcategoryLabel);
      await submitForm(driver, subcategoryForm);

      taxonomy = articleTaxonomyByLabels(categoryLabel, subcategoryLabel);
      assert.ok(taxonomy.subcategory && taxonomy.subcategory.code, 'La sous-thematique article doit etre creee.');
      const categoryCode = taxonomy.category.code;
      const subcategoryCode = taxonomy.subcategory.code;
      const subcategoryRef = `${categoryCode}:${subcategoryCode}`;

      const createForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save_article"]]'));
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="title"]')), title);
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="slug"]')), slug);
      await selectValue(driver, await createForm.findElement(By.css('select[name="category"]')), categoryCode);
      await selectValue(driver, await createForm.findElement(By.css('select[name="subcategory_ref"]')), subcategoryRef);
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
      assert.equal(article.status, 'draft');
      assert.equal(article.category, categoryCode);
      assert.equal(article.subcategory, subcategoryCode);

      await visit(driver, 'admin_articles');
      const subDeleteDisabled = await buttonDisabledByHidden(driver, 'delete_subcategory', 'subcategory_ref', subcategoryRef);
      assert.equal(subDeleteDisabled, true, 'La sous-thematique article utilisee ne doit pas etre supprimable.');

      await visit(driver, 'admin_articles', { id: article.id });
      const editForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save_article"]]'));
      await setFieldValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
      await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="content"]')), `<p>Contenu modifie article ${token}</p>`);
      await submitForm(driver, editForm, ':scope > button.button:not([name])');

      article = articleRecordBySlug(slug);
      assert.equal(article.title, updatedTitle);
      assert.match(article.content, /Contenu modifie article/);

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
      assert.equal(moderatedProposal.status, 'rejected');
      assert.equal(moderatedProposal.moderation_note, proposalNote);

      await visit(driver, 'admin_articles', { id: article.id });
      await submitFormByHidden(driver, 'delete_article', 'id', String(article.id));
      assert.equal(articleRecordBySlug(slug), null, 'L article doit etre supprime.');

      await visit(driver, 'admin_articles');
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

      const subcategoryForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_subcategory"]]'));
      await selectValue(driver, await subcategoryForm.findElement(By.css('select[name="subcategory_category"]')), taxonomy.category.code);
      await subcategoryForm.findElement(By.css('input[name="subcategory_label"]')).sendKeys(subcategoryLabel);
      await submitForm(driver, subcategoryForm);

      taxonomy = wikiTaxonomyByLabels(categoryLabel, subcategoryLabel);
      assert.ok(taxonomy.subcategory && taxonomy.subcategory.code, 'La sous-thematique wiki doit etre creee.');
      const categoryCode = taxonomy.category.code;
      const subcategoryCode = taxonomy.subcategory.code;
      const subcategoryRef = `${categoryCode}:${subcategoryCode}`;

      setWikiPageTaxonomy(pageSlug, categoryCode, subcategoryCode);
      await visit(driver, 'admin_wiki');
      const subDeleteDisabled = await buttonDisabledByHidden(driver, 'delete_subcategory', 'subcategory_ref', subcategoryRef);
      assert.equal(subDeleteDisabled, true, 'La sous-thematique wiki utilisee ne doit pas etre supprimable.');

      const pageStatusForm = await driver.findElement(By.xpath(
        `//tr[.//td[contains(normalize-space(.), ${xpathLiteral(pageTitle)})]]//form[.//select[@name="status"]]`,
      ));
      await selectValue(driver, await pageStatusForm.findElement(By.css('select[name="status"]')), 'published');
      await submitForm(driver, pageStatusForm);

      let wikiPage = wikiRecordBySlug(pageSlug);
      assert.equal(wikiPage.status, 'published');
      assert.equal(Number(wikiPage.id), Number(fixture.pageId));

      setWikiPageTaxonomy(pageSlug, 'general', '');
      await visit(driver, 'admin_wiki');
      await submitFormByHidden(driver, 'delete_subcategory', 'subcategory_ref', subcategoryRef);
      taxonomy = wikiTaxonomyByLabels(categoryLabel, subcategoryLabel);
      assert.equal(taxonomy.subcategory, null, 'La sous-thematique wiki vide doit etre supprimee.');

      await submitFormByHidden(driver, 'delete_category', 'category_code', categoryCode);
      const deletedCategory = wikiCategoryByCode(categoryCode);
      assert.ok(deletedCategory === null || deletedCategory.deleted_at !== null, 'La thematique wiki doit etre supprimee ou marquee supprimee.');

      await visit(driver, 'admin_wiki', { status: 'pending' });
      await submitProposalStatus(driver, proposalTitle, 'reviewed', proposalNote);
      const moderatedProposal = proposalRecord(fixture.proposalId);
      assert.equal(moderatedProposal.status, 'reviewed');
      assert.equal(moderatedProposal.moderation_note, proposalNote);

      wikiPage = wikiRecordBySlug(pageSlug);
      assert.equal(wikiPage.status, 'published');
    } finally {
      cleanupWikiFixture(token);
    }
  });
});
