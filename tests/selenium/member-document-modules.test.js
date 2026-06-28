const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
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
    const wysiwygWrapper = field.previousElementSibling && field.previousElementSibling.querySelector
      ? field.previousElementSibling
      : null;
    const editor = wysiwygWrapper ? wysiwygWrapper.querySelector('.wysiwyg-editor[contenteditable="true"]') : null;
    if (editor) {
      editor.innerHTML = value;
      editor.dispatchEvent(new Event('input', { bubbles: true }));
      field.value = editor.innerHTML;
    }
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

function memberDocumentByTitle(module, title) {
  return JSON.parse(runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$module = preg_replace('/[^a-z0-9_]/', '', strtolower((string) getenv('SELENIUM_DOCUMENT_MODULE')));
$title = (string) getenv('SELENIUM_DOCUMENT_TITLE');
if ($module === '' || $title === '' || !ensure_member_module_documents_table()) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, module_code, member_id, category, subcategory, tags, title, description, file_path, extracted_text, uploaded_at FROM member_module_documents WHERE module_code = ? AND title = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$module, $title]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null);
`, { SELENIUM_DOCUMENT_MODULE: module, SELENIUM_DOCUMENT_TITLE: title }).trim() || 'null');
}

function memberDocumentFavoriteRecord(memberId, documentId) {
  return JSON.parse(runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/member_favorites.php';
ensure_member_favorites_table();
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$documentId = (int) getenv('SELENIUM_DOCUMENT_ID');
$stmt = db()->prepare('SELECT id, member_id, target_type, target_id, title, url, created_at FROM member_favorites WHERE member_id = ? AND target_type = "member_module_document" AND target_id = ? LIMIT 1');
$stmt->execute([$memberId, $documentId]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null);
`, { SELENIUM_MEMBER_ID: String(memberId), SELENIUM_DOCUMENT_ID: String(documentId) }).trim() || 'null');
}

function memberDocumentTaxonomyState(module, categoryCode, subcategoryCode) {
  return JSON.parse(runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$module = preg_replace('/[^a-z0-9_]/', '', strtolower((string) getenv('SELENIUM_DOCUMENT_MODULE')));
$category = (string) getenv('SELENIUM_DOCUMENT_CATEGORY');
$subcategory = (string) getenv('SELENIUM_DOCUMENT_SUBCATEGORY');
member_document_ensure_categories_table($module);
member_document_ensure_subcategories_table($module);
$out = ['category' => null, 'subcategory' => null];
$categoryDeletedSql = table_has_column('member_module_categories', 'deleted_at') ? ' AND deleted_at IS NULL' : '';
$subcategoryDeletedSql = table_has_column('member_module_subcategories', 'deleted_at') ? ' AND deleted_at IS NULL' : '';
$stmt = db()->prepare('SELECT module_code, code, label, sort_order FROM member_module_categories WHERE module_code = ? AND code = ?' . $categoryDeletedSql . ' LIMIT 1');
$stmt->execute([$module, $category]);
$out['category'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
$stmt = db()->prepare('SELECT module_code, category_code, code, label, sort_order FROM member_module_subcategories WHERE module_code = ? AND category_code = ? AND code = ?' . $subcategoryDeletedSql . ' LIMIT 1');
$stmt->execute([$module, $category, $subcategory]);
$out['subcategory'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
echo json_encode($out);
`, {
    SELENIUM_DOCUMENT_MODULE: module,
    SELENIUM_DOCUMENT_CATEGORY: categoryCode,
    SELENIUM_DOCUMENT_SUBCATEGORY: subcategoryCode,
  }).trim() || '{"category":null,"subcategory":null}');
}

async function fetchAuthenticatedText(driver, url) {
  const result = await driver.executeAsyncScript(`
    const url = arguments[0];
    const done = arguments[arguments.length - 1];
    fetch(url, { credentials: 'same-origin' })
      .then(async (response) => ({
        ok: response.ok,
        status: response.status,
        text: await response.text()
      }))
      .catch((error) => ({ ok: false, status: 0, text: String(error) }))
      .then(done);
  `, url);
  assert.equal(result.ok, true, `La ressource authentifiee ${url} doit etre lisible (${result.status}).`);

  return String(result.text || '');
}

async function taxonomyText(driver) {
  const elements = await driver.findElements(By.css('.member-document-taxonomy .module-taxonomy-list'));
  if (elements.length === 0) {
    return '';
  }

  return (await elements[0].getText()).replace(/\s+/g, ' ').trim();
}

for (const moduleCode of ['pv', 'fichiers']) {
  test(`Selenium admin/membre: publier consulter et supprimer un document ${moduleCode}`, async (t) => {
    const credentials = requireAdminCredentials(t);
    if (credentials === null) {
      return;
    }

    const token = `selenium-admin-${moduleCode}-${Date.now()}`;
    const title = `${token} document`;
    const fixtureText = `Contenu Selenium admin ${moduleCode} ${token}.`;
    const fixture = writeTextFixture(`${token}.txt`, `${fixtureText}\n`);
    const adminRoute = moduleCode === 'pv' ? 'admin_pv' : 'admin_fichiers';
    const memberRoute = moduleCode === 'pv' ? 'pv' : 'fichiers';
    if (!(await ensureSeleniumRunnable(t))) {
      return;
    }
    cleanupMemberDocumentFixture(moduleCode, token);

    await withSelenium(t, async (driver) => {
      try {
        await loginAsAdmin(driver, credentials.username, credentials.password);
        await visit(driver, adminRoute);

        const uploadForm = await driver.findElement(By.css('#admin-member-document-upload form.admin-member-document-form'));
        await uploadForm.findElement(By.css('input[name="title"]')).sendKeys(title);
        await setFieldValue(driver, await uploadForm.findElement(By.css('textarea[name="description"]')), `Document Selenium ${moduleCode}.`);
        await uploadForm.findElement(By.css('input[name="tags"]')).sendKeys(`selenium,${moduleCode}`);
        await uploadForm.findElement(By.css('input[type="file"][name="document"]')).sendKeys(path.resolve(fixture));
        await submitForm(driver, uploadForm);

        let text = await pagePlainText(driver);
        assert.match(text, new RegExp(title), `Le document ${moduleCode} uploade doit apparaitre en admin.`);

        const document = memberDocumentByTitle(moduleCode, title);
        assert.ok(document && Number(document.id) > 0, `Le document ${moduleCode} doit exister en base apres upload admin.`);
        assert.equal(document.module_code, moduleCode, `Le document ${moduleCode} doit stocker son module.`);
        assert.ok(Number(document.member_id) > 0, `Le document ${moduleCode} doit etre rattache a un membre.`);
        assert.equal(document.category, 'general', `Le document ${moduleCode} doit rester dans la categorie generale par defaut.`);
        assert.equal(document.subcategory, '', `Le document ${moduleCode} ne doit pas inventer de sous-categorie.`);
        assert.equal(document.title, title, `Le titre ${moduleCode} doit etre persiste.`);
        assert.equal(document.description, `Document Selenium ${moduleCode}.`, `La description ${moduleCode} doit etre persistee.`);
        assert.equal(document.tags, `selenium,${moduleCode}`, `Les tags ${moduleCode} doivent etre persistes.`);
        assert.match(String(document.file_path || ''), /^storage\/(?:private|uploads)\/member_modules\//, `Le fichier ${moduleCode} doit etre stocke dans member_modules.`);
        assert.match(String(document.extracted_text || ''), new RegExp(fixtureText), `Le texte extrait ${moduleCode} doit contenir le fichier uploade.`);
        assert.ok(String(document.uploaded_at || '') !== '', `Le document ${moduleCode} doit etre horodate.`);

        await visit(driver, memberRoute, { q: title });
        text = await pagePlainText(driver);
        assert.match(text, new RegExp(title), `Le document ${moduleCode} doit etre consultable cote membre.`);

        if (moduleCode === 'fichiers') {
          const adminButtonState = await driver.executeScript(`
            const manageMenu = document.querySelector('[data-route="fichiers"] .member-document-manage-menu');
            const manageSummary = manageMenu ? manageMenu.querySelector('summary') : null;
            const managePanel = manageMenu ? manageMenu.querySelector('.member-document-propose-menu-panel[role="menu"]') : null;
            const adminButton = document.querySelector('[data-route="fichiers"] .member-document-admin-button');
            if (!manageMenu || !manageSummary || !managePanel || !adminButton) {
              return null;
            }
            manageMenu.open = true;
            const manageRect = manageSummary.getBoundingClientRect();
            const adminRect = adminButton.getBoundingClientRect();
            const panelRect = managePanel.getBoundingClientRect();
            const style = getComputedStyle(adminButton);
            const panelLinks = [...managePanel.querySelectorAll('a[role="menuitem"]')].map((link) => ({
              text: link.textContent.trim(),
              href: link.getAttribute('href') || '',
            }));
            return {
              isDetails: manageMenu.tagName.toLowerCase() === 'details',
              isOpen: manageMenu.open === true,
              manageText: manageSummary.textContent.trim(),
              panelRole: managePanel.getAttribute('role') || '',
              panelHasSize: panelRect.width > 0 && panelRect.height > 0,
              panelLinks,
              adminText: adminButton.textContent.trim(),
              adminHref: adminButton.getAttribute('href') || '',
              adminBackground: style.backgroundColor,
              adminColor: style.color,
              isRightOfManage: adminRect.left >= manageRect.right,
            };
          `);
          assert.ok(adminButtonState, 'Le header fichiers doit afficher le menu Gerer et le bouton Administrer.');
          assert.equal(adminButtonState.isDetails, true, 'Le bouton Gerer fichiers doit etre un details dropdown.');
          assert.equal(adminButtonState.isOpen, true, 'Le dropdown Gerer fichiers doit pouvoir etre ouvert.');
          assert.match(adminButtonState.manageText, /G.rer/i, 'Le menu fichiers doit rester libelle Gerer.');
          assert.equal(adminButtonState.panelRole, 'menu', 'Le dropdown Gerer fichiers doit exposer un panneau de menu.');
          assert.equal(adminButtonState.panelHasSize, true, 'Le panneau Gerer fichiers doit devenir visible une fois ouvert.');
          assert.ok(adminButtonState.panelLinks.length >= 2, 'Le dropdown Gerer fichiers doit contenir des entrees de menu.');
          assert.ok(adminButtonState.panelLinks.some((link) => /route=fichiers/.test(link.href) || /#member-document-list/.test(link.href)), 'Le dropdown Gerer doit contenir un lien vers mes fichiers.');
          assert.ok(adminButtonState.panelLinks.some((link) => /route=admin_fichiers/.test(link.href)), 'Le dropdown Gerer doit contenir un lien vers la gestion admin des partages.');
          assert.equal(adminButtonState.adminText, 'Administrer', 'Le bouton admin fichiers doit etre libelle Administrer.');
          assert.match(adminButtonState.adminHref, /route=admin_fichiers/, 'Le bouton Administrer doit pointer vers admin_fichiers.');
          assert.match(adminButtonState.adminBackground, /rgb\(0,\s*0,\s*0\)/, 'Le bouton Administrer doit etre noir.');
          assert.match(adminButtonState.adminColor, /rgb\(255,\s*255,\s*255\)/, 'Le bouton Administrer noir doit garder un texte blanc.');
          assert.equal(adminButtonState.isRightOfManage, true, 'Le bouton Administrer doit etre a droite de Gerer.');
        }

        const favoriteForm = await driver.findElement(By.xpath(`//article[contains(@class,"member-document-card")][.//*[contains(normalize-space(.), "${title}")]]//form[.//input[@name="action" and @value="toggle_favorite_document"]]`));
        await submitForm(driver, favoriteForm);
        const favorite = memberDocumentFavoriteRecord(Number(document.member_id), Number(document.id));
        assert.ok(favorite && Number(favorite.id) > 0, `Le favori ${moduleCode} doit etre cree en DB.`);
        assert.equal(Number(favorite.member_id), Number(document.member_id), `Le favori ${moduleCode} doit etre rattache au membre.`);
        assert.equal(favorite.target_type, 'member_module_document', `Le favori ${moduleCode} doit cibler un document membre.`);
        assert.equal(Number(favorite.target_id), Number(document.id), `Le favori ${moduleCode} doit stocker l id document.`);
        assert.equal(favorite.title, title, `Le favori ${moduleCode} doit stocker le titre du document.`);
        assert.match(String(favorite.url || ''), new RegExp(`route=${memberRoute}`), `Le favori ${moduleCode} doit stocker l URL du module.`);

        const downloadUrl = routeUrl('member_document_preview', { module: moduleCode, id: document.id, download: '1' });
        assert.match(await fetchAuthenticatedText(driver, downloadUrl), new RegExp(fixtureText), `Le telechargement ${moduleCode} doit renvoyer le fichier uploade.`);

        if (moduleCode === 'fichiers') {
          await visit(driver, 'telechargements', { q: title });
          text = await pagePlainText(driver);
          assert.match(text, new RegExp(title), 'L alias telechargements doit afficher les fichiers.');
        }

        await visit(driver, adminRoute, { q: title });
        const deleteForm = await driver.findElement(By.xpath(`//article[contains(@class,"member-document-card")][.//*[contains(normalize-space(.), "${title}")]]//form[.//input[@name="action" and @value="delete_document"]]`));
        await submitForm(driver, deleteForm);

        await visit(driver, memberRoute, { q: title });
        text = await pagePlainText(driver);
        assert.doesNotMatch(text, new RegExp(title), `Le document ${moduleCode} supprime en admin ne doit plus etre visible cote membre.`);
        assert.equal(memberDocumentByTitle(moduleCode, title), null, `Le document ${moduleCode} supprime doit etre retire de la DB.`);
      } finally {
        cleanupMemberDocumentFixture(moduleCode, token);
      }
    });
  });
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
      let taxonomyState = memberDocumentTaxonomyState('presentations', category, subcategory);
      assert.ok(taxonomyState.category, 'La thematique presentations doit etre creee en DB.');
      assert.equal(taxonomyState.category.module_code, 'presentations', 'La thematique doit etre rattachee au module presentations.');
      assert.equal(taxonomyState.category.code, category, 'Le code thematique doit etre persiste.');
      assert.equal(taxonomyState.category.label, category, 'Le libelle thematique doit etre persiste.');
      assert.ok(taxonomyState.subcategory, 'La sous-thematique presentations doit etre creee en DB.');
      assert.equal(taxonomyState.subcategory.category_code, category, 'La sous-thematique doit etre rattachee a la thematique.');
      assert.equal(taxonomyState.subcategory.code, subcategory, 'Le code sous-thematique doit etre persiste.');
      assert.equal(taxonomyState.subcategory.label, subcategory, 'Le libelle sous-thematique doit etre persiste.');

      await visit(driver, 'presentations');
      const proposeDropdownState = await driver.executeScript(`
        const hero = document.querySelector('[data-route="presentations"] .member-module-hero');
        const actions = hero ? hero.querySelector('.member-document-hero-actions') : null;
        const menu = actions ? actions.querySelector('.member-document-propose-menu') : null;
        const summary = menu ? menu.querySelector('summary') : null;
        const panel = menu ? menu.querySelector('.member-document-propose-menu-panel[role="menu"]') : null;
        if (!hero || !actions || !menu || !summary || !panel) {
          return null;
        }
        menu.open = true;
        const panelRect = panel.getBoundingClientRect();
        const items = [...panel.querySelectorAll('a[role="menuitem"]')].map((link) => ({
          text: link.textContent.trim(),
          href: link.getAttribute('href') || '',
          dialog: link.getAttribute('aria-controls') || '',
        }));

        return {
          isDetails: menu.tagName.toLowerCase() === 'details',
          isInHeroActions: actions.contains(menu),
          isOpen: menu.open === true,
          summaryText: summary.textContent.trim(),
          panelRole: panel.getAttribute('role') || '',
          panelHasSize: panelRect.width > 0 && panelRect.height > 0,
          items,
        };
      `);
      assert.ok(proposeDropdownState, 'Le header presentations doit afficher le menu Proposer.');
      assert.equal(proposeDropdownState.isDetails, true, 'Proposer presentations doit etre un details dropdown.');
      assert.equal(proposeDropdownState.isInHeroActions, true, 'Le dropdown Proposer presentations doit rester dans les actions du header.');
      assert.equal(proposeDropdownState.isOpen, true, 'Le dropdown Proposer presentations doit pouvoir etre ouvert.');
      assert.match(proposeDropdownState.summaryText, /Propos/i, 'Le summary du dropdown presentations doit rester libelle Proposer.');
      assert.equal(proposeDropdownState.panelRole, 'menu', 'Le dropdown Proposer presentations doit exposer un panneau menu.');
      assert.equal(proposeDropdownState.panelHasSize, true, 'Le panneau Proposer presentations doit devenir visible une fois ouvert.');
      assert.ok(proposeDropdownState.items.some((item) => /propose_document=1/.test(item.href) && item.dialog === 'member-document-proposal-dialog'), 'Le dropdown Proposer presentations doit contenir l entree presentation.');
      assert.ok(proposeDropdownState.items.some((item) => /propose_category=1/.test(item.href) && item.dialog === 'member-document-category-dialog'), 'Le dropdown Proposer presentations doit contenir l entree thematique.');
      assert.ok(proposeDropdownState.items.some((item) => /propose_subcategory=1/.test(item.href) && item.dialog === 'member-document-subcategory-dialog'), 'Le dropdown Proposer presentations doit contenir l entree sous-thematique.');
      assert.ok(proposeDropdownState.items.some((item) => /propose_subsubcategory=1/.test(item.href) && item.dialog === 'member-document-subsubcategory-dialog'), 'Le dropdown Proposer presentations doit contenir l entree sous-sous-thematique.');
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
      let document = memberDocumentByTitle('presentations', title);
      assert.ok(document && Number(document.id) > 0, 'Le document presentations doit exister en DB apres upload.');
      assert.equal(document.module_code, 'presentations', 'Le document presentations doit stocker son module.');
      assert.equal(document.category, category, 'Le document presentations doit stocker sa thematique.');
      assert.equal(document.subcategory, subcategory, 'Le document presentations doit stocker sa sous-thematique.');
      assert.equal(document.description, 'Document Selenium module presentations.', 'La description presentations doit etre persistee.');
      assert.equal(document.tags, 'selenium,documents', 'Les tags presentations doivent etre persistes.');
      assert.match(String(document.file_path || ''), /^storage\/(?:private|uploads)\/member_modules\//, 'Le fichier presentations doit etre stocke dans member_modules.');
      assert.match(String(document.extracted_text || ''), new RegExp(title), 'Le texte extrait presentations doit contenir le fichier uploade.');

      const subcategoryDeleteButton = await driver.findElement(By.xpath(`//form[.//input[@name="subcategory_ref" and @value="${category}:${subcategory}"]]//button[@name="action" and @value="delete_subcategory"]`));
      assert.ok(await subcategoryDeleteButton.getAttribute('disabled'), 'Une sous-thematique avec document ne doit pas etre supprimable.');

      await visit(driver, 'presentations', { q: title });
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'Le document uploade doit etre retrouve par la recherche membre.');
      assert.match(await taxonomyText(driver), new RegExp(category), 'La thematique avec document doit apparaitre cote membre.');
      assert.match(await taxonomyText(driver), new RegExp(subcategory), 'La sous-thematique avec document doit apparaitre cote membre.');

      const favoriteForm = await driver.findElement(By.xpath(`//article[contains(@class,"member-document-card")][.//*[contains(normalize-space(.), "${title}")]]//form[.//input[@name="action" and @value="toggle_favorite_document"]]`));
      await submitForm(driver, favoriteForm);
      let favorite = memberDocumentFavoriteRecord(Number(document.member_id), Number(document.id));
      assert.ok(favorite && Number(favorite.id) > 0, 'Le favori presentations doit etre cree en DB.');
      assert.equal(favorite.title, title, 'Le favori presentations doit stocker le titre du document.');
      assert.match(String(favorite.url || ''), /route=presentations/, 'Le favori presentations doit stocker l URL du module.');

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
      const updatedDocument = memberDocumentByTitle('presentations', updatedTitle);
      assert.ok(updatedDocument && Number(updatedDocument.id) === Number(document.id), 'La modification presentations doit garder le meme document en DB.');
      assert.equal(updatedDocument.category, category, 'La modification presentations doit conserver la thematique.');
      assert.equal(updatedDocument.subcategory, subcategory, 'La modification presentations doit conserver la sous-thematique.');
      assert.equal(updatedDocument.description, 'Document Selenium module presentations modifie.', 'La description modifiee presentations doit etre persistee.');
      assert.equal(updatedDocument.tags, 'selenium,documents', 'Les tags presentations doivent rester inchanges si non modifies.');

      const deleteForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[.//input[@name="action" and @value="delete_document"]]`));
      await submitForm(driver, deleteForm);
      await driver.wait(async () => !(await pagePlainText(driver)).includes(updatedTitle), timeoutMs);
      assert.equal(memberDocumentByTitle('presentations', updatedTitle), null, 'Le document presentations supprime doit etre retire de la DB.');
      assert.equal(memberDocumentFavoriteRecord(Number(document.member_id), Number(document.id)), null, 'La suppression du document presentations doit supprimer le favori.');

      await visit(driver, 'admin_presentations', { category });
      const enabledSubcategoryDeleteButton = await driver.findElement(By.xpath(`//form[.//input[@name="subcategory_ref" and @value="${category}:${subcategory}"]]//button[@name="action" and @value="delete_subcategory"]`));
      assert.equal(await enabledSubcategoryDeleteButton.getAttribute('disabled'), null, 'La sous-thematique vide doit redevenir supprimable.');
      await submitForm(driver, await enabledSubcategoryDeleteButton.findElement(By.xpath('./ancestor::form')), enabledSubcategoryDeleteButton);
      taxonomyState = memberDocumentTaxonomyState('presentations', category, subcategory);
      assert.equal(taxonomyState.subcategory, null, 'La sous-thematique supprimee doit etre retiree de la DB.');

      await visit(driver, 'admin_presentations');
      const enabledCategoryDeleteButton = await driver.findElement(By.xpath(`//form[.//input[@name="category_code" and @value="${category}"]]//button[@name="action" and @value="delete_category"]`));
      assert.equal(await enabledCategoryDeleteButton.getAttribute('disabled'), null, 'La thematique sans sous-thematique doit redevenir supprimable.');
      await submitForm(driver, await enabledCategoryDeleteButton.findElement(By.xpath('./ancestor::form')), enabledCategoryDeleteButton);
      taxonomyState = memberDocumentTaxonomyState('presentations', category, subcategory);
      assert.equal(taxonomyState.category, null, 'La thematique supprimee doit etre retiree de la DB.');

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
        let document = memberDocumentByTitle(moduleCode, title);
        assert.ok(document && Number(document.id) > 0, `Le document ${moduleCode} cree cote membre doit exister en DB.`);
        assert.equal(document.module_code, moduleCode, `Le document ${moduleCode} cree cote membre doit stocker son module.`);
        assert.ok(Number(document.member_id) > 0, `Le document ${moduleCode} cree cote membre doit etre rattache a un membre.`);
        assert.equal(document.category, 'general', `Le document ${moduleCode} cree cote membre doit rester en categorie generale.`);
        assert.equal(document.subcategory, '', `Le document ${moduleCode} cree cote membre ne doit pas creer de sous-categorie.`);
        assert.equal(document.description, `Document ${moduleCode} propose par Selenium.`, `La description ${moduleCode} creee cote membre doit etre persistee.`);
        assert.equal(document.tags, `selenium,${moduleCode}`, `Les tags ${moduleCode} crees cote membre doivent etre persistes.`);
        assert.match(String(document.file_path || ''), /^storage\/(?:private|uploads)\/member_modules\//, `Le fichier ${moduleCode} cree cote membre doit etre stocke dans member_modules.`);
        assert.match(String(document.extracted_text || ''), new RegExp(`Contenu Selenium membre ${moduleCode}`), `Le texte extrait ${moduleCode} cree cote membre doit contenir le fichier.`);

        const favoriteForm = await driver.findElement(By.xpath(`//article[contains(@class,"member-document-card")][.//*[contains(normalize-space(.), "${title}")]]//form[.//input[@name="action" and @value="toggle_favorite_document"]]`));
        await submitForm(driver, favoriteForm);
        let favorite = memberDocumentFavoriteRecord(Number(document.member_id), Number(document.id));
        assert.ok(favorite && Number(favorite.id) > 0, `Le favori ${moduleCode} cree cote membre doit etre persiste.`);
        assert.equal(favorite.title, title, `Le favori ${moduleCode} cree cote membre doit stocker le titre.`);
        assert.match(String(favorite.url || ''), new RegExp(`route=${moduleCode}`), `Le favori ${moduleCode} cree cote membre doit stocker l URL du module.`);

        await visit(driver, moduleCode, { q: title });
        const editForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${title}")]]//form[.//input[@name="action" and @value="update_document"]]`));
        await setFieldValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
        await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="description"]')), `Document ${moduleCode} modifie cote membre.`);
        await setFieldValue(driver, await editForm.findElement(By.css('input[name="tags"]')), `selenium,${moduleCode},updated`);
        await submitForm(driver, editForm);

        await visit(driver, moduleCode, { q: updatedTitle });
        text = await pagePlainText(driver);
        assert.match(text, new RegExp(updatedTitle), `Le document ${moduleCode} modifie cote membre doit etre visible.`);
        const updatedDocument = memberDocumentByTitle(moduleCode, updatedTitle);
        assert.ok(updatedDocument && Number(updatedDocument.id) === Number(document.id), `Le document ${moduleCode} modifie doit conserver son id DB.`);
        assert.equal(updatedDocument.description, `Document ${moduleCode} modifie cote membre.`, `La description ${moduleCode} modifiee doit etre persistee.`);
        assert.equal(updatedDocument.tags, `selenium,${moduleCode},updated`, `Les tags ${moduleCode} modifies doivent etre persistes.`);
        assert.equal(updatedDocument.category, 'general', `La modification ${moduleCode} doit conserver la categorie.`);
        assert.equal(updatedDocument.subcategory, '', `La modification ${moduleCode} doit conserver la sous-categorie vide.`);

        const deleteForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[.//input[@name="action" and @value="delete_document"]]`));
        await submitForm(driver, deleteForm);

        await visit(driver, moduleCode, { q: updatedTitle });
        text = await pagePlainText(driver);
        assert.doesNotMatch(text, new RegExp(updatedTitle), `Le document ${moduleCode} supprime cote membre ne doit plus etre visible.`);
        assert.equal(memberDocumentByTitle(moduleCode, updatedTitle), null, `Le document ${moduleCode} supprime cote membre doit etre retire de la DB.`);
        assert.equal(memberDocumentFavoriteRecord(Number(document.member_id), Number(document.id)), null, `La suppression ${moduleCode} doit retirer le favori DB.`);
      } finally {
        cleanupMemberDocumentFixture(moduleCode, token);
      }
    });
  });
}
