const test = require('node:test');
const {
  By,
  assert,
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
      editor.dispatchEvent(new Event('change', { bubbles: true }));
    }
  `, element, value);
}

function seleniumJson(source, env = {}) {
  return JSON.parse(runSeleniumPhp(source, env) || 'null');
}

function editorialTableExists() {
  return runSeleniumPhp(`
require_once 'app/bootstrap.php';
echo table_exists('editorial_contents') ? '1' : '0';
`).trim() === '1';
}

function captureEditorialRows(keys) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$keys = json_decode((string) getenv('SELENIUM_KEYS'), true);
if (!is_array($keys) || !table_exists('editorial_contents')) {
    echo '[]';
    return;
}
$keyColumn = table_has_column('editorial_contents', 'content_key') ? 'content_key' : 'slot';
$placeholders = implode(',', array_fill(0, count($keys), '?'));
$stmt = db()->prepare('SELECT * FROM editorial_contents WHERE ' . $keyColumn . ' IN (' . $placeholders . ') ORDER BY ' . $keyColumn);
$stmt->execute($keys);
echo json_encode($stmt->fetchAll() ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_KEYS: JSON.stringify(keys) });
}

function restoreEditorialRows(keys, rows) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$keys = json_decode((string) getenv('SELENIUM_KEYS'), true);
$rows = json_decode((string) getenv('SELENIUM_ROWS'), true);
if (!is_array($keys) || !table_exists('editorial_contents')) {
    return;
}
$keyColumn = table_has_column('editorial_contents', 'content_key') ? 'content_key' : 'slot';
$placeholders = implode(',', array_fill(0, count($keys), '?'));
db()->prepare('DELETE FROM editorial_contents WHERE ' . $keyColumn . ' IN (' . $placeholders . ')')->execute($keys);
if (is_array($rows)) {
    $columns = array_map(static fn(array $column): string => (string) $column['Field'], db()->query('SHOW COLUMNS FROM editorial_contents')->fetchAll() ?: []);
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $insertColumns = array_values(array_filter($columns, static fn(string $column): bool => array_key_exists($column, $row)));
        $quoted = array_map(static fn(string $column): string => chr(96) . str_replace(chr(96), chr(96) . chr(96), $column) . chr(96), $insertColumns);
        $placeholders = implode(',', array_fill(0, count($insertColumns), '?'));
        $values = array_map(static fn(string $column) => $row[$column], $insertColumns);
        db()->prepare('INSERT INTO editorial_contents (' . implode(',', $quoted) . ') VALUES (' . $placeholders . ')')->execute($values);
    }
}
`, {
    SELENIUM_KEYS: JSON.stringify(keys),
    SELENIUM_ROWS: JSON.stringify(rows),
  });
}

function editorialRowMap(rows) {
  return Object.fromEntries(
    rows.map((row) => [row.content_key || row.slot, row]),
  );
}

function editorialLocaleValue(row, locale) {
  return row?.[`${locale}_text`] ?? row?.[locale] ?? '';
}

function prepareTranslationReviewRows(memberId, token) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$token = (string) getenv('SELENIUM_TOKEN');

if (!table_exists('news_posts') || !table_exists('news_translations') || !table_exists('articles') || !table_exists('article_translations')) {
    echo 'null';
    return;
}

if (function_exists('news_default_section_id')) {
    $sectionId = news_default_section_id();
} else {
    db()->prepare('INSERT INTO news_sections (slug, name, sort_order) VALUES ("selenium", "Selenium", 999) ON DUPLICATE KEY UPDATE name = VALUES(name)')->execute();
    $stmt = db()->prepare('SELECT id FROM news_sections WHERE slug = "selenium" LIMIT 1');
    $stmt->execute();
    $sectionId = (int) ($stmt->fetchColumn() ?: 0);
}

$newsSlug = 'selenium-review-news-' . strtolower($token);
$articleSlug = 'selenium-review-article-' . strtolower($token);
db()->prepare('DELETE FROM news_translations WHERE news_post_id IN (SELECT id FROM news_posts WHERE slug = ?)')->execute([$newsSlug]);
db()->prepare('DELETE FROM news_posts WHERE slug = ?')->execute([$newsSlug]);
db()->prepare('DELETE FROM article_translations WHERE article_id IN (SELECT id FROM articles WHERE slug = ?)')->execute([$articleSlug]);
db()->prepare('DELETE FROM articles WHERE slug = ?')->execute([$articleSlug]);

db()->prepare('INSERT INTO news_posts (section_id, author_id, slug, title, excerpt, content, status, published_at) VALUES (?, ?, ?, ?, ?, ?, "published", NOW())')
    ->execute([$sectionId, $memberId, $newsSlug, 'Source news ' . $token, 'Source excerpt ' . $token, '<p>Source content ' . $token . '</p>']);
$newsId = (int) db()->lastInsertId();
db()->prepare('INSERT INTO news_translations (news_post_id, locale, source_hash, title, excerpt, content, status) VALUES (?, "en", ?, ?, ?, ?, "needs_review")')
    ->execute([$newsId, substr(sha1('news' . $token), 0, 40), 'Auto news ' . $token, 'Auto news excerpt ' . $token, '<p>Auto news content ' . $token . '</p>']);
$newsTranslationId = (int) db()->lastInsertId();

db()->prepare('INSERT INTO articles (slug, title, excerpt, content, status, category, published_at, author_id) VALUES (?, ?, ?, ?, "published", "selenium", NOW(), ?)')
    ->execute([$articleSlug, 'Source article ' . $token, 'Source article excerpt ' . $token, '<p>Source article content ' . $token . '</p>', $memberId]);
$articleId = (int) db()->lastInsertId();
db()->prepare('INSERT INTO article_translations (article_id, locale, source_hash, title, excerpt, content, status) VALUES (?, "en", ?, ?, ?, ?, "needs_review")')
    ->execute([$articleId, substr(sha1('article' . $token), 0, 40), 'Auto article ' . $token, 'Auto article excerpt ' . $token, '<p>Auto article content ' . $token . '</p>']);
$articleTranslationId = (int) db()->lastInsertId();

echo json_encode([
    'news_id' => $newsId,
    'news_translation_id' => $newsTranslationId,
    'article_id' => $articleId,
    'article_translation_id' => $articleTranslationId,
    'news_slug' => $newsSlug,
    'article_slug' => $articleSlug,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, {
    SELENIUM_MEMBER_ID: String(memberId),
    SELENIUM_TOKEN: token,
  });
}

function cleanupTranslationReviewRows(token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$token = strtolower((string) getenv('SELENIUM_TOKEN'));
if ($token === '') {
    return;
}
$newsSlug = 'selenium-review-news-' . $token;
$articleSlug = 'selenium-review-article-' . $token;
if (table_exists('news_translations') && table_exists('news_posts')) {
    db()->prepare('DELETE FROM news_translations WHERE news_post_id IN (SELECT id FROM news_posts WHERE slug = ?)')->execute([$newsSlug]);
    db()->prepare('DELETE FROM news_posts WHERE slug = ?')->execute([$newsSlug]);
}
if (table_exists('article_translations') && table_exists('articles')) {
    db()->prepare('DELETE FROM article_translations WHERE article_id IN (SELECT id FROM articles WHERE slug = ?)')->execute([$articleSlug]);
    db()->prepare('DELETE FROM articles WHERE slug = ?')->execute([$articleSlug]);
}
`, { SELENIUM_TOKEN: token });
}

function memberIdForCallsign(callsign) {
  return Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$stmt = db()->prepare('SELECT id FROM members WHERE callsign = ? LIMIT 1');
$stmt->execute([strtoupper((string) getenv('SELENIUM_CALLSIGN'))]);
echo (int) ($stmt->fetchColumn() ?: 0);
`, { SELENIUM_CALLSIGN: callsign }).trim());
}

function translationState(newsTranslationId, articleTranslationId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$newsStmt = db()->prepare('SELECT locale, source_hash, status, title, excerpt, content, reviewed_by, reviewed_at FROM news_translations WHERE id = ? LIMIT 1');
$newsStmt->execute([(int) getenv('SELENIUM_NEWS_TRANSLATION_ID')]);
$articleStmt = db()->prepare('SELECT locale, source_hash, status, title, excerpt, content, reviewed_by, reviewed_at FROM article_translations WHERE id = ? LIMIT 1');
$articleStmt->execute([(int) getenv('SELENIUM_ARTICLE_TRANSLATION_ID')]);
echo json_encode([
    'news' => $newsStmt->fetch() ?: null,
    'article' => $articleStmt->fetch() ?: null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, {
    SELENIUM_NEWS_TRANSLATION_ID: String(newsTranslationId),
    SELENIUM_ARTICLE_TRANSLATION_ID: String(articleTranslationId),
  });
}

test('Selenium admin editorial: les contenus multilingues sont sauvegardes', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  if (!editorialTableExists()) {
    t.skip('Table editorial_contents absente; scenario editorial ignore.');
    return;
  }

  const keys = ['committee.title', 'committee.intro', 'committee.mission', 'press.title', 'press.intro', 'press.contact'];
  const beforeRows = captureEditorialRows(keys);
  const token = `SELEDIT${Date.now()}`;

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_editorial');

      const form = await driver.findElement(By.css('main form'));
      await setFieldValue(driver, await form.findElement(By.css('textarea[name="content[committee_title][fr]"]')), `Titre FR ${token}`);
      await setFieldValue(driver, await form.findElement(By.css('textarea[name="content[committee_title][en]"]')), `Title EN ${token}`);
      await setFieldValue(driver, await form.findElement(By.css('textarea[name="content[committee_title][de]"]')), `Titel DE ${token}`);
      await setFieldValue(driver, await form.findElement(By.css('textarea[name="content[committee_title][nl]"]')), `Titel NL ${token}`);
      await setFieldValue(driver, await form.findElement(By.css('textarea[name="content[press_contact][fr]"]')), `Contact FR ${token}`);
      await setFieldValue(driver, await form.findElement(By.css('textarea[name="content[press_contact][en]"]')), `Contact EN ${token}`);
      await setFieldValue(driver, await form.findElement(By.css('textarea[name="content[press_contact][de]"]')), `Kontakt DE ${token}`);
      await setFieldValue(driver, await form.findElement(By.css('textarea[name="content[press_contact][nl]"]')), `Contact NL ${token}`);
      await submitForm(driver, form);

      const rowsByKey = editorialRowMap(captureEditorialRows(['committee.title', 'press.contact']));
      const committeeTitle = rowsByKey['committee.title'];
      assert.equal(editorialLocaleValue(committeeTitle, 'fr'), `Titre FR ${token}`, 'Le titre comite FR doit etre persiste.');
      assert.equal(editorialLocaleValue(committeeTitle, 'en'), `Title EN ${token}`, 'Le titre comite EN doit etre persiste.');
      assert.equal(editorialLocaleValue(committeeTitle, 'de'), `Titel DE ${token}`, 'Le titre comite DE doit etre persiste.');
      assert.equal(editorialLocaleValue(committeeTitle, 'nl'), `Titel NL ${token}`, 'Le titre comite NL doit etre persiste.');
      const pressContact = rowsByKey['press.contact'];
      assert.equal(editorialLocaleValue(pressContact, 'fr'), `Contact FR ${token}`, 'Le contact presse FR doit etre persiste.');
      assert.equal(editorialLocaleValue(pressContact, 'en'), `Contact EN ${token}`, 'Le contact presse EN doit etre persiste.');
      assert.equal(editorialLocaleValue(pressContact, 'de'), `Kontakt DE ${token}`, 'Le contact presse DE doit etre persiste.');
      assert.equal(editorialLocaleValue(pressContact, 'nl'), `Contact NL ${token}`, 'Le contact presse NL doit etre persiste.');
      const text = await pagePlainText(driver);
      assert.match(text, new RegExp(token), 'Le contenu sauvegarde doit etre rendu apres redirection.');
    } finally {
      restoreEditorialRows(keys, beforeRows);
    }
  });
});

test('Selenium admin traductions: news et articles passent en reviewed', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const memberId = memberIdForCallsign(credentials.username);
  assert.ok(memberId > 0, 'Le membre admin Selenium doit exister.');
  const token = `SELTR${Date.now()}`;
  const rows = prepareTranslationReviewRows(memberId, token);
  if (rows === null) {
    t.skip('Tables de traduction absentes; scenario review ignore.');
    return;
  }

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_translation_reviews');

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(`Source news ${token}`), 'La traduction news temporaire doit etre visible.');
      assert.match(text, new RegExp(`Auto news excerpt ${token}`), 'L extrait news temporaire doit etre visible.');
      assert.match(text, new RegExp(`Source article ${token}`), 'La traduction article temporaire doit etre visible.');
      assert.match(text, new RegExp(`Auto article excerpt ${token}`), 'L extrait article temporaire doit etre visible.');

      const newsForm = await driver.findElement(By.xpath(`//input[@name="action" and @value="review_news_translation"]/ancestor::form[input[@name="id" and @value="${rows.news_translation_id}"]][1]`));
      await setFieldValue(driver, await newsForm.findElement(By.css('input[name="title"]')), `Reviewed news ${token}`);
      await setFieldValue(driver, await newsForm.findElement(By.css('textarea[name="excerpt"]')), `Reviewed news excerpt ${token}`);
      await setFieldValue(driver, await newsForm.findElement(By.css('textarea[name="content"]')), `<p>Reviewed news content ${token}</p>`);
      await submitForm(driver, newsForm);

      const articleForm = await driver.findElement(By.xpath(`//input[@name="action" and @value="review_article_translation"]/ancestor::form[input[@name="id" and @value="${rows.article_translation_id}"]][1]`));
      await setFieldValue(driver, await articleForm.findElement(By.css('input[name="title"]')), `Reviewed article ${token}`);
      await setFieldValue(driver, await articleForm.findElement(By.css('textarea[name="excerpt"]')), `Reviewed article excerpt ${token}`);
      await setFieldValue(driver, await articleForm.findElement(By.css('textarea[name="content"]')), `<p>Reviewed article content ${token}</p>`);
      await submitForm(driver, articleForm);

      const state = translationState(rows.news_translation_id, rows.article_translation_id);
      assert.equal(state.news.locale, 'en', 'La traduction news relue doit conserver sa locale.');
      assert.match(String(state.news.source_hash || ''), /^[a-f0-9]{40}$/i, 'La traduction news relue doit conserver son hash source.');
      assert.equal(state.news.status, 'reviewed', 'La traduction news doit passer en reviewed.');
      assert.equal(state.news.title, `Reviewed news ${token}`, 'Le titre news relu doit etre persiste.');
      assert.equal(state.news.excerpt, `Reviewed news excerpt ${token}`, 'L extrait news relu doit etre persiste.');
      assert.match(String(state.news.content || ''), new RegExp(`Reviewed news content ${token}`), 'Le contenu news relu doit etre persiste.');
      assert.equal(Number(state.news.reviewed_by), memberId, 'La traduction news doit enregistrer le relecteur exact.');
      assert.ok(String(state.news.reviewed_at || '') !== '', 'La traduction news doit enregistrer une date de relecture.');
      assert.equal(state.article.locale, 'en', 'La traduction article relue doit conserver sa locale.');
      assert.match(String(state.article.source_hash || ''), /^[a-f0-9]{40}$/i, 'La traduction article relue doit conserver son hash source.');
      assert.equal(state.article.status, 'reviewed', 'La traduction article doit passer en reviewed.');
      assert.equal(state.article.title, `Reviewed article ${token}`, 'Le titre article relu doit etre persiste.');
      assert.equal(state.article.excerpt, `Reviewed article excerpt ${token}`, 'L extrait article relu doit etre persiste.');
      assert.match(String(state.article.content || ''), new RegExp(`Reviewed article content ${token}`), 'Le contenu article relu doit etre persiste.');
      assert.equal(Number(state.article.reviewed_by), memberId, 'La traduction article doit enregistrer le relecteur exact.');
      assert.ok(String(state.article.reviewed_at || '') !== '', 'La traduction article doit enregistrer une date de relecture.');

      await visit(driver, 'admin_translation_reviews');
      text = await pagePlainText(driver);
      assert.doesNotMatch(text, new RegExp(`Reviewed news ${token}`), 'La traduction news reviewed ne doit plus etre dans la file.');
      assert.doesNotMatch(text, new RegExp(`Reviewed article ${token}`), 'La traduction article reviewed ne doit plus etre dans la file.');
    } finally {
      cleanupTranslationReviewRows(token);
    }
  });
});
