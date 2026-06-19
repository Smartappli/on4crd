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

async function setInputValue(driver, input, value) {
  await driver.executeScript(`
    const input = arguments[0];
    const value = arguments[1];
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  `, input, value);
}

function cleanupWorkflowRows(title) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$title = getenv('SELENIUM_TEST_TITLE') ?: '';
if ($title !== '') {
    if (table_exists('classified_ads')) {
        db()->prepare('DELETE FROM classified_ads WHERE title = ?')->execute([$title]);
        db()->prepare('DELETE FROM classified_ads WHERE title = ?')->execute([$title . ' updated']);
    }
    if (table_exists('articles')) {
        db()->prepare('DELETE FROM articles WHERE title = ?')->execute([$title]);
    }
    if (table_exists('wiki_pages')) {
        db()->prepare('DELETE FROM wiki_pages WHERE title = ?')->execute([$title]);
    }
    if (table_exists('member_webotheque_links')) {
        db()->prepare('DELETE FROM member_webotheque_links WHERE title = ?')->execute([$title]);
        db()->prepare('DELETE FROM member_webotheque_links WHERE title = ?')->execute([$title . ' updated']);
    }
    if (table_exists('content_proposals')) {
        db()->prepare('DELETE FROM content_proposals WHERE title = ?')->execute([$title]);
        db()->prepare('DELETE FROM content_proposals WHERE title = ?')->execute([$title . ' updated']);
    }
    if (table_exists('member_library_documents')) {
        $stmt = db()->prepare('SELECT id, file_path FROM member_library_documents WHERE title = ? OR title = ?');
        $stmt->execute([$title, $title . ' updated']);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0 && table_exists('member_favorites')) {
                db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id = ?')->execute(['library_document', $id]);
            }
            if (function_exists('member_library_delete_document_file')) {
                member_library_delete_document_file((string) ($row['file_path'] ?? ''));
            }
            if ($id > 0) {
                db()->prepare('DELETE FROM member_library_documents WHERE id = ?')->execute([$id]);
            }
        }
    }
}
`, { SELENIUM_TEST_TITLE: title });
}

test('Selenium membre: creer, modifier et vendre une petite annonce', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const title = `selenium-classified-${Date.now()}`;
  const updatedTitle = `${title} updated`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupWorkflowRows(title);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'classifieds_manage');

      const createForm = await driver.findElement(By.css('.classifieds-editor-form'));
      await createForm.findElement(By.css('input[name="title"]')).sendKeys(title);
      await setRichTextarea(driver, await createForm.findElement(By.css('textarea[name="description"]')), 'Annonce Selenium de regression.');
      await createForm.findElement(By.css('input[name="price"]')).clear();
      await createForm.findElement(By.css('input[name="price"]')).sendKeys('12,50');
      await createForm.findElement(By.css('input[name="location"]')).sendKeys('Durnal');
      const contactInput = await createForm.findElement(By.css('input[name="contact"]'));
      await contactInput.clear();
      await contactInput.sendKeys('selenium@example.test');
      await submitForm(driver, createForm);

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'La nouvelle annonce doit apparaitre dans Mes annonces.');

      const editLink = await driver.findElement(By.xpath(`//article[contains(@class,"classifieds-my-card")][.//*[contains(normalize-space(.), "${title}")]]//a[contains(@href,"edit=")]`));
      await driver.get(await editLink.getAttribute('href'));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      const editForm = await driver.findElement(By.css('.classifieds-editor-form'));
      const titleInput = await editForm.findElement(By.css('input[name="title"]'));
      await titleInput.clear();
      await titleInput.sendKeys(updatedTitle);
      await submitForm(driver, editForm);

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'Le titre modifie doit apparaitre dans Mes annonces.');

      const statusForm = await driver.findElement(By.xpath(`//article[contains(@class,"classifieds-my-card")][.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[contains(@class,"classifieds-status-form")]`));
      await driver.executeScript(`
        const form = arguments[0];
        const button = form.querySelector('button[name="status"][value="sold"]');
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit(button);
        } else {
          button.click();
        }
      `, statusForm);
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      text = await pagePlainText(driver);
      assert.match(text, /vendu|sold/i, 'Le statut vendu doit etre visible apres marquage.');
    } finally {
      cleanupWorkflowRows(title);
    }
  });
});

test('Selenium membre: proposer un article et le retrouver dans Mes contenus', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const title = `selenium-article-${Date.now()}`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupWorkflowRows(title);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'article_propose');

      const form = await driver.findElement(By.css('form.stack'));
      await form.findElement(By.css('input[name="title"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="excerpt"]')), 'Resume Selenium.');
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="content"]')), '<p>Contenu article Selenium.</p>');
      await submitForm(driver, form);

      await driver.wait(until.urlContains('route=my_requests'), timeoutMs);
      const text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'La proposition article doit apparaitre dans Mes contenus.');
      assert.match(text, /article/i);
    } finally {
      cleanupWorkflowRows(title);
    }
  });
});

test('Selenium membre: ajouter un document bibliotheque et le retrouver en ligne et dans Mes contenus', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const title = `selenium-library-${Date.now()}`;
  const fixtureDir = path.join(os.tmpdir(), 'on4crd-selenium-fixtures');
  fs.mkdirSync(fixtureDir, { recursive: true });
  const fixture = path.join(fixtureDir, `${title}.txt`);
  fs.writeFileSync(fixture, `Document Selenium ${title}\n`, 'utf8');
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupWorkflowRows(title);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_library');

      const form = await driver.findElement(By.css('form.admin-library-upload-form'));
      await form.findElement(By.css('input[name="title"]')).sendKeys(title);
      await form.findElement(By.css('input[name="tags"]')).sendKeys('formation');
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="description"]')), 'Document bibliotheque Selenium.');
      await form.findElement(By.css('input[type="file"][name="document"]')).sendKeys(path.resolve(fixture));
      await submitForm(driver, form);

      await visit(driver, 'members_library', { q: title });
      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'Le document ajoute doit etre visible en ligne dans la bibliotheque membre.');

      await visit(driver, 'my_requests');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'Le document ajoute doit apparaitre dans Mes contenus.');
      assert.match(text, /biblioth|library/i);
    } finally {
      cleanupWorkflowRows(title);
    }
  });
});

test('Selenium membre: publier une page wiki avec les droits admin', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const title = `selenium-wiki-${Date.now()}`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupWorkflowRows(title);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'wiki_propose');

      const form = await driver.findElement(By.css('form.wiki-edit-form'));
      await form.findElement(By.css('input[name="title"]')).sendKeys(title);
      await form.findElement(By.css('input[name="slug"]')).sendKeys(title);
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="content"]')), '<p>Contenu wiki Selenium.</p>');
      await submitForm(driver, form);

      await driver.wait(until.urlContains('route=wiki_view'), timeoutMs);
      const text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'La page wiki publiee doit etre ouverte apres soumission.');
    } finally {
      cleanupWorkflowRows(title);
    }
  });
});

test('Selenium membre: creer, modifier et supprimer un lien webotheque', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const title = `selenium-webotheque-${Date.now()}`;
  const updatedTitle = `${title} updated`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupWorkflowRows(title);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'webotheque', { propose_link: '1' });

      const createForm = await driver.findElement(By.css('#webotheque-link-dialog form.webotheque-proposal-form'));
      await createForm.findElement(By.css('input[name="title"]')).sendKeys(title);
      await createForm.findElement(By.css('input[name="url"]')).sendKeys(`https://example.org/${title}`);
      await setRichTextarea(driver, await createForm.findElement(By.css('textarea[name="description"]')), 'Lien webotheque Selenium.');
      await createForm.findElement(By.css('input[name="tags"]')).sendKeys('selenium, regression');
      await submitForm(driver, createForm);

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'Le lien webotheque cree doit etre visible.');

      const editForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${title}")]]//form[.//input[@name="action" and @value="update_link"]]`));
      await setInputValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
      await setRichTextarea(driver, await editForm.findElement(By.css('textarea[name="description"]')), 'Lien webotheque Selenium modifie.');
      await submitForm(driver, editForm);

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'Le lien webotheque modifie doit etre visible.');

      const deleteForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[.//input[@name="action" and @value="delete_link"]]`));
      await submitForm(driver, deleteForm);

      text = await pagePlainText(driver);
      assert.doesNotMatch(text, new RegExp(updatedTitle), 'Le lien webotheque supprime ne doit plus etre affiche.');
    } finally {
      cleanupWorkflowRows(title);
    }
  });
});

test('Selenium admin: moderer une petite annonce depuis admin_classifieds', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const title = `selenium-admin-classified-${Date.now()}`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupWorkflowRows(title);
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
ensure_classified_ads_table();
$title = getenv('SELENIUM_TEST_TITLE') ?: 'selenium-admin-classified';
$memberId = (int) (db()->query("SELECT id FROM members WHERE callsign = 'SELENIUMADMIN' LIMIT 1")->fetchColumn() ?: 1);
db()->prepare('INSERT INTO classified_ads (owner_member_id, category_code, title, description, location, contact, price_cents, status, expires_at) VALUES (?, "gear", ?, "Annonce admin Selenium.", "Durnal", "selenium@example.test", 1000, "pending", NULL)')
    ->execute([$memberId, $title]);
`, { SELENIUM_TEST_TITLE: title });

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_classifieds', { q: title });

      const editLink = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${title}")]]//a[contains(@href,"edit=")]`));
      await driver.get(await editLink.getAttribute('href'));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      const form = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save"]]'));
      const status = await form.findElement(By.css('select[name="status"]'));
      await status.sendKeys('Active');
      await submitForm(driver, form);

      await visit(driver, 'admin_classifieds', { q: title });
      const text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'L annonce moderee doit rester retrouvee en admin.');
      assert.match(text, /active|actif|en ligne/i);
    } finally {
      cleanupWorkflowRows(title);
    }
  });
});
