const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
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

async function submitForm(driver, form, submitter = null) {
  await driver.executeScript(`
    const form = arguments[0];
    const submitter = arguments[1] || form.querySelector('button[type="submit"], button:not([type="button"]), input[type="submit"]');
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit(submitter || undefined);
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
    field.value = value;
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
  `, field, value);
}

function writeTextFixture(name, content) {
  const dir = path.join(os.tmpdir(), 'on4crd-selenium-fixtures');
  fs.mkdirSync(dir, { recursive: true });
  const filePath = path.join(dir, name);
  fs.writeFileSync(filePath, content, 'utf8');

  return filePath;
}

function cleanupMemberDocumentFixture(module, token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$token = getenv('SELENIUM_DOCUMENT_TOKEN') ?: '';
$module = preg_replace('/[^a-z0-9_]/', '', strtolower((string) getenv('SELENIUM_DOCUMENT_MODULE')));
if ($module === '') {
    $module = 'presentations';
}
if ($token !== '') {
    ensure_member_module_documents_table();
    member_document_ensure_categories_table($module);
    member_document_ensure_subcategories_table($module);
    $stmt = db()->prepare('SELECT id, file_path FROM member_module_documents WHERE module_code = ? AND title LIKE ?');
    $stmt->execute([$module, $token . '%']);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id > 0 && table_exists('member_favorites')) {
            db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id = ?')->execute(['member_module_document', $id]);
        }
        member_document_delete_file((string) ($row['file_path'] ?? ''));
        if ($id > 0) {
            db()->prepare('DELETE FROM member_module_documents WHERE id = ? AND module_code = ?')->execute([$id, $module]);
        }
    }
    if (table_exists('content_proposals')) {
        db()->prepare('DELETE FROM content_proposals WHERE area = ? AND title LIKE ?')->execute([$module, $token . '%']);
    }
    db()->prepare('DELETE FROM member_module_subcategories WHERE module_code = ? AND (category_code = ? OR code = ?)')->execute([$module, $token, $token . '-sub']);
    db()->prepare('DELETE FROM member_module_categories WHERE module_code = ? AND code = ?')->execute([$module, $token]);
}
`, { SELENIUM_DOCUMENT_MODULE: module, SELENIUM_DOCUMENT_TOKEN: token });
}

async function taxonomyText(driver) {
  const elements = await driver.findElements(By.css('.member-document-taxonomy .module-taxonomy-list'));
  if (elements.length === 0) {
    return '';
  }

  return (await elements[0].getText()).replace(/\s+/g, ' ').trim();
}

test('Selenium modules documents: taxonomy, upload, favoris, edition et suppression presentations', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const token = `selenium-doc-${Date.now()}`;
  const category = token;
  const subcategory = `${token}-sub`;
  const title = `${token} presentation`;
  const updatedTitle = `${title} updated`;
  const fixture = writeTextFixture(`${token}.txt`, `Contenu Selenium pour ${title}.\n`);
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupMemberDocumentFixture('presentations', token);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_presentations');

      const categoryForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_category"]]'));
      await categoryForm.findElement(By.css('input[name="category_label"]')).sendKeys(category);
      await submitForm(driver, categoryForm);

      const subcategoryForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="add_subcategory"]]'));
      await setFieldValue(driver, await subcategoryForm.findElement(By.css('select[name="subcategory_category"]')), category);
      await subcategoryForm.findElement(By.css('input[name="subcategory_label"]')).sendKeys(subcategory);
      await submitForm(driver, subcategoryForm);

      await visit(driver, 'presentations');
      assert.doesNotMatch(await taxonomyText(driver), new RegExp(category), 'Une thematique vide ne doit pas apparaitre cote membre.');

      await visit(driver, 'admin_presentations', { category, subcategory });
      const categoryDeleteButton = await driver.findElement(By.xpath(`//form[.//input[@name="category_code" and @value="${category}"]]//button[@name="action" and @value="delete_category"]`));
      assert.ok(await categoryDeleteButton.getAttribute('disabled'), 'Une thematique avec sous-thematique ne doit pas etre supprimable.');

      const uploadForm = await driver.findElement(By.css('#admin-member-document-upload form.admin-member-document-form'));
      await uploadForm.findElement(By.css('input[name="title"]')).sendKeys(title);
      await setFieldValue(driver, await uploadForm.findElement(By.css('select[name="category"]')), category);
      await setFieldValue(driver, await uploadForm.findElement(By.css('select[name="subcategory_ref"]')), `${category}:${subcategory}`);
      await setFieldValue(driver, await uploadForm.findElement(By.css('textarea[name="description"]')), 'Document Selenium module presentations.');
      await uploadForm.findElement(By.css('input[name="tags"]')).sendKeys('selenium,documents');
      await uploadForm.findElement(By.css('input[type="file"][name="document"]')).sendKeys(path.resolve(fixture));
      await submitForm(driver, uploadForm);

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'Le document uploade doit apparaitre en admin.');

      const subcategoryDeleteButton = await driver.findElement(By.xpath(`//form[.//input[@name="subcategory_ref" and @value="${category}:${subcategory}"]]//button[@name="action" and @value="delete_subcategory"]`));
      assert.ok(await subcategoryDeleteButton.getAttribute('disabled'), 'Une sous-thematique avec document ne doit pas etre supprimable.');

      await visit(driver, 'presentations', { q: title });
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'Le document uploade doit etre retrouve par la recherche membre.');
      assert.match(await taxonomyText(driver), new RegExp(category), 'La thematique avec document doit apparaitre cote membre.');
      assert.match(await taxonomyText(driver), new RegExp(subcategory), 'La sous-thematique avec document doit apparaitre cote membre.');

      const favoriteForm = await driver.findElement(By.xpath(`//article[contains(@class,"member-document-card")][.//*[contains(normalize-space(.), "${title}")]]//form[.//input[@name="action" and @value="toggle_favorite_document"]]`));
      await submitForm(driver, favoriteForm);

      await visit(driver, 'presentations', { q: title });
      const taxonomyItems = await driver.findElements(By.css('.member-document-taxonomy .module-taxonomy-list > a.module-taxonomy-item span'));
      assert.ok(taxonomyItems.length >= 2, 'La navigation taxonomie doit contenir au moins Favoris et toutes les thematiques.');
      assert.match(await taxonomyItems[0].getText(), /favor/i, 'Favoris doit apparaitre en premier quand un favori existe.');

      const editForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${title}")]]//form[.//input[@name="action" and @value="update_document"]]`));
      await setFieldValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
      await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="description"]')), 'Document Selenium module presentations modifie.');
      await submitForm(driver, editForm);

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'Le document modifie doit apparaitre cote membre.');

      const deleteForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[.//input[@name="action" and @value="delete_document"]]`));
      await submitForm(driver, deleteForm);
      await driver.wait(async () => !(await pagePlainText(driver)).includes(updatedTitle), timeoutMs);

      await visit(driver, 'admin_presentations', { category });
      const enabledSubcategoryDeleteButton = await driver.findElement(By.xpath(`//form[.//input[@name="subcategory_ref" and @value="${category}:${subcategory}"]]//button[@name="action" and @value="delete_subcategory"]`));
      assert.equal(await enabledSubcategoryDeleteButton.getAttribute('disabled'), null, 'La sous-thematique vide doit redevenir supprimable.');
      await submitForm(driver, await enabledSubcategoryDeleteButton.findElement(By.xpath('./ancestor::form')), enabledSubcategoryDeleteButton);

      await visit(driver, 'admin_presentations');
      const enabledCategoryDeleteButton = await driver.findElement(By.xpath(`//form[.//input[@name="category_code" and @value="${category}"]]//button[@name="action" and @value="delete_category"]`));
      assert.equal(await enabledCategoryDeleteButton.getAttribute('disabled'), null, 'La thematique sans sous-thematique doit redevenir supprimable.');
      await submitForm(driver, await enabledCategoryDeleteButton.findElement(By.xpath('./ancestor::form')), enabledCategoryDeleteButton);

      await visit(driver, 'admin_presentations');
      assert.equal((await driver.findElements(By.xpath(`//input[@name="category_code" and @value="${category}"]`))).length, 0, 'La thematique supprimee ne doit plus etre listee.');
    } finally {
      cleanupMemberDocumentFixture('presentations', token);
    }
  });
});

for (const moduleCode of ['presentations', 'videos']) {
  test(`Selenium membre: ajouter modifier supprimer un document ${moduleCode}`, async (t) => {
    const credentials = requireAdminCredentials(t);
    if (credentials === null) {
      return;
    }

    const token = `selenium-member-${moduleCode}-${Date.now()}`;
    const title = `${token} document`;
    const updatedTitle = `${title} updated`;
    const fixture = writeTextFixture(`${token}.txt`, `Contenu Selenium membre ${moduleCode}.\n`);
    if (!(await ensureSeleniumRunnable(t))) {
      return;
    }
    cleanupMemberDocumentFixture(moduleCode, token);

    await withSelenium(t, async (driver) => {
      try {
        await loginAsAdmin(driver, credentials.username, credentials.password);
        const proposeQuery = moduleCode === 'videos' ? { propose_video: '1' } : { propose_document: '1' };
        await visit(driver, moduleCode, proposeQuery);

        const createForm = await driver.findElement(By.css('#member-document-proposal-dialog form'));
        await createForm.findElement(By.css('input[name="proposal_title"]')).sendKeys(title);
        await setFieldValue(driver, await createForm.findElement(By.css('textarea[name="proposal_description"]')), `Document ${moduleCode} propose par Selenium.`);
        await createForm.findElement(By.css('input[name="proposal_tags"]')).sendKeys(`selenium,${moduleCode}`);
        await createForm.findElement(By.css('input[type="file"][name="proposal_file"]')).sendKeys(path.resolve(fixture));
        await submitForm(driver, createForm);

        await visit(driver, moduleCode, { q: title });
        let text = await pagePlainText(driver);
        assert.match(text, new RegExp(title), `Le document ${moduleCode} cree cote membre doit etre visible.`);

        const editForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${title}")]]//form[.//input[@name="action" and @value="update_document"]]`));
        await setFieldValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
        await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="description"]')), `Document ${moduleCode} modifie cote membre.`);
        await setFieldValue(driver, await editForm.findElement(By.css('input[name="tags"]')), `selenium,${moduleCode},updated`);
        await submitForm(driver, editForm);

        await visit(driver, moduleCode, { q: updatedTitle });
        text = await pagePlainText(driver);
        assert.match(text, new RegExp(updatedTitle), `Le document ${moduleCode} modifie cote membre doit etre visible.`);

        const deleteForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[.//input[@name="action" and @value="delete_document"]]`));
        await submitForm(driver, deleteForm);

        await visit(driver, moduleCode, { q: updatedTitle });
        text = await pagePlainText(driver);
        assert.doesNotMatch(text, new RegExp(updatedTitle), `Le document ${moduleCode} supprime cote membre ne doit plus etre visible.`);
      } finally {
        cleanupMemberDocumentFixture(moduleCode, token);
      }
    });
  });
}
