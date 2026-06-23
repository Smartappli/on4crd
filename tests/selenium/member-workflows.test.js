const test = require('node:test');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {
  By,
  until,
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
    $like = '%' . $title . '%';
    if (table_exists('albums')) {
        $stmt = db()->prepare('SELECT id FROM albums WHERE title = ? OR title = ?');
        $stmt->execute([$title, $title . ' updated']);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $albumId) {
            $albumId = (int) $albumId;
            if ($albumId <= 0) {
                continue;
            }
            if (function_exists('album_delete_record')) {
                album_delete_record($albumId);
            } else {
                if (table_exists('album_photos')) {
                    db()->prepare('DELETE FROM album_photos WHERE album_id = ?')->execute([$albumId]);
                }
                db()->prepare('DELETE FROM albums WHERE id = ?')->execute([$albumId]);
            }
        }
    }
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
        db()->prepare('DELETE FROM content_proposals WHERE title LIKE ?')->execute([$title . '%']);
    }
    if (table_exists('member_library_subcategories')) {
        db()->prepare('DELETE FROM member_library_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')->execute([$like, $like, $like]);
    }
    if (table_exists('member_library_categories')) {
        db()->prepare('DELETE FROM member_library_categories WHERE code <> "general" AND (code LIKE ? OR label LIKE ?)')->execute([$like, $like]);
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

function memberLibraryDocumentByTitle(title) {
  return JSON.parse(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$title = (string) getenv('SELENIUM_TEST_TITLE');
if ($title === '' || !table_exists('member_library_documents')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, member_id, title, file_path FROM member_library_documents WHERE title = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$title]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TEST_TITLE: title }).trim() || 'null');
}

function memberLibraryCategoryByLabel(label) {
  return JSON.parse(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$label = (string) getenv('SELENIUM_LIBRARY_LABEL');
if ($label === '' || !table_exists('member_library_categories')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT code, label FROM member_library_categories WHERE label = ? ORDER BY code DESC LIMIT 1');
$stmt->execute([$label]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_LIBRARY_LABEL: label }).trim() || 'null');
}

function memberLibrarySubcategoryByLabel(label) {
  return JSON.parse(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$label = (string) getenv('SELENIUM_LIBRARY_LABEL');
if ($label === '' || !table_exists('member_library_subcategories')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT category_code, code, label FROM member_library_subcategories WHERE label = ? ORDER BY code DESC LIMIT 1');
$stmt->execute([$label]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_LIBRARY_LABEL: label }).trim() || 'null');
}

function contentProposalExists(area, type, title) {
  return Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$area = (string) getenv('SELENIUM_PROPOSAL_AREA');
$type = (string) getenv('SELENIUM_PROPOSAL_TYPE');
$title = (string) getenv('SELENIUM_PROPOSAL_TITLE');
if ($area === '' || $type === '' || $title === '' || !table_exists('content_proposals')) {
    echo 0;
    return;
}
$stmt = db()->prepare('SELECT COUNT(*) FROM content_proposals WHERE area = ? AND proposal_type = ? AND title = ?');
$stmt->execute([$area, $type, $title]);
echo (int) ($stmt->fetchColumn() ?: 0);
`, {
    SELENIUM_PROPOSAL_AREA: area,
    SELENIUM_PROPOSAL_TYPE: type,
    SELENIUM_PROPOSAL_TITLE: title,
  }).trim()) > 0;
}

function libraryFavoriteSaved(memberId, documentId) {
  return Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$documentId = (int) getenv('SELENIUM_DOCUMENT_ID');
if ($memberId <= 0 || $documentId <= 0 || !table_exists('member_favorites')) {
    echo 0;
    return;
}
$stmt = db()->prepare('SELECT COUNT(*) FROM member_favorites WHERE member_id = ? AND target_type = "library_document" AND target_id = ?');
$stmt->execute([$memberId, $documentId]);
echo (int) ($stmt->fetchColumn() ?: 0);
`, {
    SELENIUM_MEMBER_ID: String(memberId),
    SELENIUM_DOCUMENT_ID: String(documentId),
  }).trim()) > 0;
}

function classifiedByTitle(title) {
  return JSON.parse(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$title = (string) getenv('SELENIUM_TEST_TITLE');
if ($title === '' || !table_exists('classified_ads')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, owner_member_id, category_code, title, description, location, contact, price_cents, status, expires_at FROM classified_ads WHERE title = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$title]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TEST_TITLE: title }).trim() || 'null');
}

async function fetchAuthenticatedResource(driver, url) {
  return driver.executeAsyncScript(`
    const url = arguments[0];
    const done = arguments[arguments.length - 1];
    fetch(url, { credentials: 'same-origin' })
      .then(async (response) => ({
        ok: response.ok,
        status: response.status,
        contentType: response.headers.get('content-type') || '',
        body: await response.arrayBuffer().then((buffer) => buffer.byteLength)
      }))
      .catch((error) => ({
        ok: false,
        status: 0,
        contentType: '',
        body: String(error)
      }))
      .then(done);
  `, url);
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
      let classified = classifiedByTitle(title);
      assert.ok(classified && Number(classified.id) > 0, 'La petite annonce membre doit etre creee en DB.');
      assert.ok(Number(classified.owner_member_id) > 0, 'La petite annonce membre doit etre rattachee au membre connecte.');
      assert.equal(classified.category_code, 'gear', 'La categorie par defaut de l annonce membre doit etre persistee.');
      assert.equal(classified.description, 'Annonce Selenium de regression.', 'La description initiale de l annonce membre doit etre persistee.');
      assert.equal(Number(classified.price_cents), 1250, 'Le prix initial de l annonce membre doit etre persiste en cents.');
      assert.equal(classified.location, 'Durnal', 'Le lieu initial de l annonce membre doit etre persiste.');
      assert.equal(classified.contact, 'selenium@example.test', 'Le contact initial de l annonce membre doit etre persiste.');
      assert.equal(classified.status, 'draft', 'La creation membre doit rester en brouillon tant que la publication active n est pas demandee.');
      assert.equal(classified.expires_at, null, 'Une annonce brouillon ne doit pas avoir de date expiration.');

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
      classified = classifiedByTitle(updatedTitle);
      assert.ok(classified && Number(classified.id) > 0, 'La petite annonce modifiee doit rester en DB.');
      assert.equal(classified.description, 'Annonce Selenium de regression.', 'La modification du titre ne doit pas perdre la description.');
      assert.equal(Number(classified.price_cents), 1250, 'La modification du titre ne doit pas perdre le prix.');
      assert.equal(classified.status, 'draft', 'La modification du titre doit conserver le statut brouillon.');

      let statusForm = await driver.findElement(By.xpath(`//article[contains(@class,"classifieds-my-card")][.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[contains(@class,"classifieds-status-form")]`));
      await driver.executeScript(`
        const form = arguments[0];
        const button = form.querySelector('button[name="status"][value="active"]');
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit(button);
        } else {
          button.click();
        }
      `, statusForm);
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      text = await pagePlainText(driver);
      assert.match(text, /actif|active|en ligne/i, 'Le statut actif doit etre visible apres reactualisation.');
      classified = classifiedByTitle(updatedTitle);
      assert.equal(classified.status, 'active', 'Le passage actif doit etre persiste en DB.');
      assert.ok(String(classified.expires_at || '') !== '', 'Le passage actif doit calculer une date expiration.');
      const activeExpiresAt = String(classified.expires_at || '');

      statusForm = await driver.findElement(By.xpath(`//article[contains(@class,"classifieds-my-card")][.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[contains(@class,"classifieds-status-form")]`));
      const renewButton = await statusForm.findElement(By.css('button[name="action"][value="renew"]'));
      await driver.executeScript(`
        const form = arguments[0];
        const button = arguments[1];
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit(button);
        } else {
          button.click();
        }
      `, statusForm, renewButton);
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      text = await pagePlainText(driver);
      assert.match(text, /renouvel|renew|actif|active|en ligne/i, 'Le renouvellement doit rester visible apres action renew.');
      classified = classifiedByTitle(updatedTitle);
      assert.equal(classified.status, 'active', 'Le renouvellement doit conserver le statut actif.');
      assert.ok(String(classified.expires_at || '') >= activeExpiresAt, 'Le renouvellement ne doit pas raccourcir la date expiration.');

      statusForm = await driver.findElement(By.xpath(`//article[contains(@class,"classifieds-my-card")][.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[contains(@class,"classifieds-status-form")]`));
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
      classified = classifiedByTitle(updatedTitle);
      assert.equal(classified.status, 'sold', 'Le statut vendu doit etre persiste en DB.');
      assert.equal(classified.expires_at, null, 'Une annonce vendue ne doit plus avoir de date expiration active.');
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
  const fixture = path.join(fixtureDir, `${title}.doc`);
  fs.writeFileSync(fixture, Buffer.concat([
    Buffer.from([0xD0, 0xCF, 0x11, 0xE0, 0xA1, 0xB1, 0x1A, 0xE1]),
    Buffer.alloc(256),
  ]));
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
      assert.match(text, new RegExp(title), 'Le document .doc ajoute doit etre visible en ligne dans la bibliotheque membre.');

      const document = memberLibraryDocumentByTitle(title);
      assert.ok(document && Number(document.id) > 0, 'Le document bibliotheque doit exister apres upload admin.');
      const preview = await fetchAuthenticatedResource(driver, routeUrl('member_library_preview', { id: document.id, download: '1' }));
      assert.equal(preview.ok, true, `member_library_preview doit servir le document (${preview.status}).`);
      assert.match(preview.contentType, /application\/msword|application\/octet-stream/i, 'member_library_preview doit renvoyer un type de document bureautique.');
      assert.ok(Number(preview.body) > 0, 'member_library_preview doit renvoyer un fichier non vide.');

      await visit(driver, 'my_requests');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'Le document .doc ajoute doit apparaitre dans Mes contenus.');
      assert.match(text, /biblioth|library/i);
    } finally {
      cleanupWorkflowRows(title);
    }
  });
});

test('Selenium membre: proposer un document depuis la bibliotheque membre et le retrouver dans Mes contenus', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const title = `selenium-library-member-form-${Date.now()}`;
  const updatedTitle = `${title} updated`;
  const categoryTitle = `${title}-category`;
  const subcategoryTitle = `${title}-subcategory`;
  const tagTitle = `${title}-tag`;
  const fixtureDir = path.join(os.tmpdir(), 'on4crd-selenium-fixtures');
  fs.mkdirSync(fixtureDir, { recursive: true });
  const fixture = path.join(fixtureDir, `${title}.txt`);
  fs.writeFileSync(fixture, 'Document Selenium soumis depuis la bibliotheque membre.');
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupWorkflowRows(title);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'members_library');

      const categoryForm = await driver.findElement(By.xpath('//dialog[@id="members-library-category-dialog"]//form[.//input[@name="action" and @value="propose_category"]]'));
      await setInputValue(driver, await categoryForm.findElement(By.css('input[name="proposal_category_name"]')), categoryTitle);
      await setInputValue(driver, await categoryForm.findElement(By.css('textarea[name="proposal_reason"]')), 'Categorie bibliotheque proposee par Selenium.');
      await submitForm(driver, categoryForm);
      const category = memberLibraryCategoryByLabel(categoryTitle);
      assert.ok(category && category.code, 'La proposition de categorie bibliotheque doit creer la categorie en admin.');

      await visit(driver, 'members_library');
      const subcategoryForm = await driver.findElement(By.xpath('//dialog[@id="members-library-subcategory-dialog"]//form[.//input[@name="action" and @value="propose_subcategory"]]'));
      await setInputValue(driver, await subcategoryForm.findElement(By.css('select[name="proposal_parent_category"]')), category.code);
      await setInputValue(driver, await subcategoryForm.findElement(By.css('input[name="proposal_subcategory_name"]')), subcategoryTitle);
      await setInputValue(driver, await subcategoryForm.findElement(By.css('textarea[name="proposal_reason"]')), 'Sous-categorie bibliotheque proposee par Selenium.');
      await submitForm(driver, subcategoryForm);
      const subcategory = memberLibrarySubcategoryByLabel(subcategoryTitle);
      assert.ok(subcategory && subcategory.code, 'La proposition de sous-categorie bibliotheque doit creer la sous-categorie en admin.');
      assert.equal(subcategory.category_code, category.code, 'La sous-categorie doit etre rattachee a la categorie proposee.');

      await visit(driver, 'members_library');
      const tagForm = await driver.findElement(By.xpath('//dialog[@id="members-library-tag-dialog"]//form[.//input[@name="action" and @value="propose_tag"]]'));
      await setInputValue(driver, await tagForm.findElement(By.css('input[name="proposal_tag"]')), tagTitle);
      await setInputValue(driver, await tagForm.findElement(By.css('textarea[name="proposal_reason"]')), 'Mot cle bibliotheque propose par Selenium.');
      await submitForm(driver, tagForm);
      assert.equal(contentProposalExists('members_library', 'tag', tagTitle), true, 'La proposition de tag bibliotheque doit etre enregistree.');

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

      const form = await driver.wait(until.elementLocated(By.xpath('//dialog[@id="members-library-document-dialog"]//form[.//input[@name="action" and @value="propose_document"]]')), timeoutMs);
      await driver.wait(until.elementIsVisible(form), timeoutMs);
      await setInputValue(driver, await form.findElement(By.css('input[name="proposal_title"]')), title);
      await setInputValue(driver, await form.findElement(By.css('select[name="proposal_category"]')), category.code);
      await setInputValue(driver, await form.findElement(By.css('select[name="proposal_subcategory_ref"]')), `${category.code}:${subcategory.code}`);
      await setInputValue(driver, await form.findElement(By.css('input[name="proposal_tags"]')), tagTitle);
      await form.findElement(By.css('input[type="file"][name="proposal_file"]')).sendKeys(path.resolve(fixture));
      await setRichTextarea(driver, await form.findElement(By.css('textarea[name="proposal_description"]')), 'Document propose depuis la bibliotheque membre.');

      const contactValue = await form.findElement(By.css('input[name="proposal_contact"]')).getAttribute('value');
      assert.ok(contactValue.trim() !== '', 'Le contact doit etre pre-rempli meme si le compte n a pas d email.');

      await submitForm(driver, form);

      await visit(driver, 'members_library', { q: title });
      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'Le document soumis depuis members_library doit etre visible en bibliotheque.');

      const document = memberLibraryDocumentByTitle(title);
      assert.ok(document && Number(document.id) > 0, 'Le document soumis depuis members_library doit exister en base.');
      const favoriteForm = await driver.findElement(By.xpath(`//form[.//input[@name="action" and @value="toggle_favorite_document"] and .//input[@name="document_id" and @value="${document.id}"]]`));
      await submitForm(driver, favoriteForm);
      assert.equal(libraryFavoriteSaved(Number(document.member_id), Number(document.id)), true, 'Le document members_library doit etre ajoutable aux favoris.');

      await visit(driver, 'my_requests');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'Le document soumis depuis members_library doit apparaitre dans Mes contenus.');
      assert.match(text, /biblioth|library/i);

      await visit(driver, 'members_library', { q: title });
      const editForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${title}")]]//form[.//input[@name="action" and @value="update_document"]]`));
      await setInputValue(driver, await editForm.findElement(By.css('input[name="document_title"]')), updatedTitle);
      await setRichTextarea(driver, await editForm.findElement(By.css('textarea[name="document_description"]')), 'Document bibliotheque membre modifie par Selenium.');
      await submitForm(driver, editForm);

      await visit(driver, 'members_library', { q: updatedTitle });
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'Le document members_library modifie cote membre doit etre visible.');

      const deleteForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[.//input[@name="action" and @value="delete_document"]]`));
      await submitForm(driver, deleteForm);

      await visit(driver, 'members_library', { q: updatedTitle });
      text = await pagePlainText(driver);
      assert.doesNotMatch(text, new RegExp(updatedTitle), 'Le document members_library supprime cote membre ne doit plus etre visible.');
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

test('Selenium membre: creer modifier supprimer un album', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const title = `selenium-album-member-${Date.now()}`;
  const updatedTitle = `${title} updated`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupWorkflowRows(title);

  await withSelenium(t, async (driver) => {
    try {
      ensureSeleniumFixtures();
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'albums', { propose_album: '1' });

      const createForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="propose_album"]]'));
      await createForm.findElement(By.css('input[name="proposal_title"]')).sendKeys(title);
      await createForm.findElement(By.css('input[name="proposal_keywords"]')).sendKeys('selenium,album');
      await setRichTextarea(driver, await createForm.findElement(By.css('textarea[name="proposal_description"]')), 'Album membre Selenium.');
      await submitForm(driver, createForm);

      await visit(driver, 'albums', { q: title });
      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'L album cree cote membre doit etre visible.');

      const editForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${title}")]]//form[.//input[@name="action" and @value="update_album"]]`));
      await setInputValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
      await setRichTextarea(driver, await editForm.findElement(By.css('textarea[name="description"]')), 'Album membre Selenium modifie.');
      await submitForm(driver, editForm);

      await visit(driver, 'albums', { q: updatedTitle });
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'L album modifie cote membre doit etre visible.');

      const deleteForm = await driver.findElement(By.xpath(`//dialog[.//*[contains(normalize-space(.), "${updatedTitle}")]]//form[.//input[@name="action" and @value="delete_album"]]`));
      await submitForm(driver, deleteForm);

      await visit(driver, 'albums', { q: updatedTitle });
      text = await pagePlainText(driver);
      assert.doesNotMatch(text, new RegExp(updatedTitle), 'L album supprime cote membre ne doit plus etre visible.');
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
