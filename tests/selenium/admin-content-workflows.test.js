const test = require('node:test');
const {
  By,
  assert,
  routeUrl,
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
  await driver.executeScript(`
    const select = arguments[0];
    const value = arguments[1];
    select.value = value;
    select.dispatchEvent(new Event('input', { bubbles: true }));
    select.dispatchEvent(new Event('change', { bubbles: true }));
  `, select, value);
}

async function browserFetchText(driver, url) {
  return driver.executeAsyncScript(`
    const url = arguments[0];
    const done = arguments[arguments.length - 1];
    fetch(url, { credentials: 'same-origin' })
      .then((response) => response.text())
      .then((body) => done(body))
      .catch((error) => done('SELENIUM_FETCH_ERROR:' + error.message));
  `, url);
}

function prepareContentStorage() {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
if (function_exists('news_default_section_id')) {
    news_default_section_id();
}
`);
}

function cleanupContentRows(slug) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
$slug = trim((string) (getenv('SELENIUM_CONTENT_SLUG') ?: ''));
if ($slug !== '') {
    if (table_exists('news_posts')) {
        $stmt = db()->prepare('SELECT id FROM news_posts WHERE slug = ? OR slug LIKE ?');
        $stmt->execute([$slug, $slug . '-%']);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            if (table_exists('news_translations')) {
                db()->prepare('DELETE FROM news_translations WHERE news_post_id IN (' . $placeholders . ')')->execute($ids);
            }
            db()->prepare('DELETE FROM news_posts WHERE id IN (' . $placeholders . ')')->execute($ids);
        }
    }
    if (table_exists('events')) {
        db()->prepare('DELETE FROM events WHERE slug = ? OR slug LIKE ?')->execute([$slug, $slug . '-%']);
    }
    $cacheDir = function_exists('cache_storage_dir') ? cache_storage_dir() : __DIR__ . '/../storage/cache/data';
    foreach (glob(rtrim($cacheDir, '/') . '/*') ?: [] as $file) {
        $name = basename((string) $file);
        if (stripos($name, 'news') !== false || stripos($name, 'event') !== false || stripos($name, 'home_') !== false) {
            @unlink((string) $file);
        }
    }
}
`, { SELENIUM_CONTENT_SLUG: slug });
}

function newsPostBySlug(slug) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
$slug = trim((string) (getenv('SELENIUM_CONTENT_SLUG') ?: ''));
$stmt = db()->prepare('SELECT id, section_id, author_id, slug, title, excerpt, content, status, published_at FROM news_posts WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_CONTENT_SLUG: slug });

  return JSON.parse(output || 'null');
}

function eventBySlug(slug) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
$slug = trim((string) (getenv('SELENIUM_CONTENT_SLUG') ?: ''));
$stmt = db()->prepare('SELECT id, slug, title, summary, description, kind, start_at, end_at, location, external_url, status FROM events WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_CONTENT_SLUG: slug });

  return JSON.parse(output || 'null');
}

test('Selenium admin actualites: creer, modifier, rechercher et consulter publiquement', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const suffix = Date.now();
  const slug = `selenium-news-${suffix}`;
  const title = `Selenium actualite ${suffix}`;
  const updatedTitle = `${title} modifiee`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupContentRows(slug);
  prepareContentStorage();

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_news');

      const createForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save_post"]]'));
      await createForm.findElement(By.css('input[name="title"]')).sendKeys(title);
      await createForm.findElement(By.css('input[name="slug"]')).sendKeys(slug);
      await setFieldValue(driver, await createForm.findElement(By.css('textarea[name="excerpt"]')), 'Resume Selenium actualite.');
      await setFieldValue(driver, await createForm.findElement(By.css('textarea[name="content"]')), '<p>Contenu public Selenium actualite.</p>');
      const statusSelects = await createForm.findElements(By.css('select[name="status"]'));
      if (statusSelects.length > 0) {
        await selectValue(driver, statusSelects[0], 'published');
      }
      await submitForm(driver, createForm);

      let newsPost = newsPostBySlug(slug);
      assert.ok(newsPost, 'L actualite creee doit etre presente en DB.');
      assert.ok(Number(newsPost.id) > 0, 'L actualite creee doit avoir un identifiant DB.');
      assert.ok(Number(newsPost.section_id) > 0, 'L actualite creee doit etre rattachee a une rubrique.');
      assert.ok(Number(newsPost.author_id) > 0, 'L actualite creee doit etre rattachee a un auteur.');
      assert.equal(newsPost.slug, slug, 'Le slug initial de l actualite doit etre persiste.');
      assert.equal(newsPost.title, title, 'Le titre initial de l actualite doit etre persiste.');
      assert.equal(newsPost.excerpt, 'Resume Selenium actualite.', 'Le resume initial de l actualite doit etre persiste.');
      assert.match(newsPost.content, /Contenu public Selenium actualite\./, 'Le contenu initial de l actualite doit etre persiste.');
      assert.equal(newsPost.status, 'published', 'L actualite creee doit etre publiee en DB.');
      assert.ok(String(newsPost.published_at || '') !== '', 'L actualite publiee doit avoir une date de publication.');
      const originalNewsSectionId = Number(newsPost.section_id);
      const originalNewsAuthorId = Number(newsPost.author_id);

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'L actualite creee doit apparaitre en admin.');

      const editLink = await driver.findElement(By.xpath(`//tr[.//*[contains(normalize-space(.), "${title}")]]//a[contains(@href,"edit=")]`));
      await driver.get(await editLink.getAttribute('href'));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      const editForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save_post"]]'));
      await setFieldValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
      await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="excerpt"]')), 'Resume Selenium actualite modifie.');
      await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="content"]')), '<p>Contenu public Selenium actualite modifie.</p>');
      const editStatusSelects = await editForm.findElements(By.css('select[name="status"]'));
      if (editStatusSelects.length > 0) {
        await selectValue(driver, editStatusSelects[0], 'published');
      }
      await submitForm(driver, editForm);

      newsPost = newsPostBySlug(slug);
      assert.equal(newsPost.title, updatedTitle, 'Le titre modifie de l actualite doit etre persiste en DB.');
      assert.equal(newsPost.excerpt, 'Resume Selenium actualite modifie.', 'Le resume modifie de l actualite doit etre persiste.');
      assert.match(newsPost.content, /Contenu public Selenium actualite modifie/, 'Le contenu modifie de l actualite doit etre persiste.');
      assert.equal(newsPost.status, 'published', 'L actualite modifiee doit rester publiee en DB.');
      assert.equal(Number(newsPost.section_id), originalNewsSectionId, 'La modification actualite doit conserver la rubrique.');
      assert.equal(Number(newsPost.author_id), originalNewsAuthorId, 'La modification actualite doit conserver l auteur.');
      assert.ok(String(newsPost.published_at || '') !== '', 'La modification actualite ne doit pas perdre published_at.');

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'L actualite modifiee doit rester visible en admin.');

      await visit(driver, 'news_view', { slug });
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'La page publique doit afficher le titre modifie.');
      assert.match(text, /Contenu public Selenium actualite modifie/i, 'La page publique doit afficher le contenu modifie.');

      await visit(driver, 'news', { q: updatedTitle });
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'La recherche publique des actualites doit retrouver l actualite publiee.');
    } finally {
      cleanupContentRows(slug);
    }
  });
});

test('Selenium admin evenements: creer, modifier, flux public et export ICS', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const suffix = Date.now();
  const slug = `selenium-event-${suffix}`;
  const title = `Selenium evenement ${suffix}`;
  const updatedTitle = `${title} modifie`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupContentRows(slug);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_events');

      const createForm = await driver.findElement(By.css('form.stack'));
      await createForm.findElement(By.css('input[name="title"]')).sendKeys(title);
      await createForm.findElement(By.css('input[name="slug"]')).sendKeys(slug);
      await setFieldValue(driver, await createForm.findElement(By.css('textarea[name="summary"]')), 'Resume Selenium evenement.');
      await setFieldValue(driver, await createForm.findElement(By.css('textarea[name="description"]')), '<p>Description publique Selenium evenement.</p>');
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="start_at"]')), '2026-12-18T18:00');
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="end_at"]')), '2026-12-18T20:00');
      await createForm.findElement(By.css('input[name="location"]')).sendKeys('Durnal Selenium');
      await createForm.findElement(By.css('input[name="external_url"]')).sendKeys('https://example.org/selenium-event');
      await selectValue(driver, await createForm.findElement(By.css('select[name="kind"]')), 'club');
      await selectValue(driver, await createForm.findElement(By.css('select[name="status"]')), 'published');
      await submitForm(driver, createForm);

      let event = eventBySlug(slug);
      assert.ok(event, 'L evenement cree doit etre present en DB.');
      assert.ok(Number(event.id) > 0, 'L evenement cree doit avoir un identifiant DB.');
      assert.equal(event.slug, slug, 'Le slug initial de l evenement doit etre persiste.');
      assert.equal(event.title, title, 'Le titre initial de l evenement doit etre persiste.');
      assert.equal(event.summary, 'Resume Selenium evenement.', 'Le resume initial de l evenement doit etre persiste.');
      assert.match(event.description, /Description publique Selenium evenement\./, 'La description initiale doit etre persistee.');
      assert.equal(event.kind, 'club', 'Le type evenement doit etre persiste.');
      assert.match(String(event.start_at), /^2026-12-18 18:00:00$/, 'La date de debut initiale doit etre persistee.');
      assert.match(String(event.end_at), /^2026-12-18 20:00:00$/, 'La date de fin initiale doit etre persistee.');
      assert.equal(event.location, 'Durnal Selenium', 'Le lieu initial doit etre persiste.');
      assert.equal(event.external_url, 'https://example.org/selenium-event', 'L URL externe initiale doit etre persistee.');
      assert.equal(event.status, 'published', 'L evenement cree doit etre publie en DB.');

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'L evenement cree doit apparaitre en admin.');

      const editLink = await driver.findElement(By.xpath(`//li[.//a[contains(normalize-space(.), "${title}")]]//a[contains(@href,"edit=")]`));
      await driver.get(await editLink.getAttribute('href'));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      const editForm = await driver.findElement(By.css('form.stack'));
      await setFieldValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
      await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="summary"]')), 'Resume Selenium evenement modifie.');
      await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="description"]')), '<p>Description publique Selenium evenement modifie.</p>');
      await setFieldValue(driver, await editForm.findElement(By.css('input[name="location"]')), 'Durnal Selenium modifie');
      await selectValue(driver, await editForm.findElement(By.css('select[name="status"]')), 'published');
      await submitForm(driver, editForm);

      event = eventBySlug(slug);
      assert.equal(event.title, updatedTitle, 'Le titre modifie de l evenement doit etre persiste en DB.');
      assert.equal(event.summary, 'Resume Selenium evenement modifie.', 'Le resume modifie de l evenement doit etre persiste.');
      assert.match(event.description, /Description publique Selenium evenement modifie/, 'La description modifiee doit etre persistee.');
      assert.equal(event.kind, 'club', 'La modification evenement doit conserver le type.');
      assert.match(String(event.start_at), /^2026-12-18 18:00:00$/, 'La modification evenement doit conserver la date de debut.');
      assert.match(String(event.end_at), /^2026-12-18 20:00:00$/, 'La modification evenement doit conserver la date de fin.');
      assert.equal(event.location, 'Durnal Selenium modifie', 'Le lieu modifie doit etre persiste en DB.');
      assert.equal(event.external_url, 'https://example.org/selenium-event', 'La modification evenement doit conserver l URL externe.');
      assert.equal(event.status, 'published', 'L evenement modifie doit rester publie en DB.');

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'L evenement modifie doit rester visible en admin.');

      await visit(driver, 'event_view', { slug });
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'La page evenement publique doit afficher le titre modifie.');
      assert.match(text, /Durnal Selenium modifie/i, 'La page evenement publique doit afficher le lieu modifie.');
      assert.match(text, /Description publique Selenium evenement modifie/i, 'La page evenement publique doit afficher la description modifiee.');

      const feedBody = await browserFetchText(driver, routeUrl('events_feed'));
      assert.doesNotMatch(feedBody, /^SELENIUM_FETCH_ERROR:/, 'Le flux JSON public des evenements doit etre lisible.');
      assert.match(feedBody, new RegExp(updatedTitle), 'Le flux JSON public doit contenir l evenement publie.');

      const icsBody = await browserFetchText(driver, routeUrl('events', { format: 'ics' }));
      assert.doesNotMatch(icsBody, /^SELENIUM_FETCH_ERROR:/, 'L export ICS public doit etre lisible.');
      assert.match(icsBody, /BEGIN:VCALENDAR/i, 'L export ICS doit contenir un calendrier.');
      assert.match(icsBody, new RegExp(updatedTitle), 'L export ICS doit contenir l evenement publie.');
    } finally {
      cleanupContentRows(slug);
    }
  });
});
