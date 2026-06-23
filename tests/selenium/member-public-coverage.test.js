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
  writeTinyPngFixture,
  runSeleniumPhp,
} = require('./helpers');

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
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

async function setFieldValue(driver, element, value) {
  await driver.executeScript(`
    const element = arguments[0];
    const value = arguments[1];
    if (element.tagName && element.tagName.toLowerCase() === 'select') {
      for (const option of element.options) {
        option.selected = option.value === value;
      }
    }
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

async function setCheckbox(driver, element, checked) {
  await driver.executeScript(`
    const element = arguments[0];
    element.checked = Boolean(arguments[1]);
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
  `, element, checked);
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
      form.append(key, value);
    }
    fetch(url, {
      method: 'POST',
      body: form,
      credentials: 'same-origin',
      redirect: 'follow'
    }).then(async (response) => ({
      ok: true,
      status: response.status,
      url: response.url,
      contentType: response.headers.get('content-type') || '',
      body: await response.text()
    })).catch((error) => ({
      ok: false,
      status: 0,
      url,
      contentType: '',
      body: String(error)
    })).then(done);
  `, url, fields);
}

function seleniumJson(source, env = {}) {
  return JSON.parse(runSeleniumPhp(source, env) || 'null');
}

function memberByCallsign(callsign) {
  const member = seleniumJson(`
require_once 'app/bootstrap.php';
$callsign = strtoupper(trim((string) getenv('SELENIUM_CALLSIGN')));
$stmt = db()->prepare('SELECT id, callsign, email FROM members WHERE callsign = ? LIMIT 1');
$stmt->execute([$callsign]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_CALLSIGN: callsign });
  assert.ok(member && Number(member.id) > 0, `Membre Selenium introuvable pour ${callsign}.`);
  return member;
}

function cleanupCoverageRows(token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$token = trim((string) getenv('SELENIUM_TOKEN'));
if ($token === '') {
    return;
}
$like = '%' . $token . '%';
$deleteByIds = static function (string $table, string $idColumn, array $ids): void {
    if ($ids === [] || !table_exists($table)) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    db()->prepare('DELETE FROM ' . $table . ' WHERE ' . $idColumn . ' IN (' . $placeholders . ')')->execute($ids);
};
$idsFor = static function (string $table, string $where, array $params): array {
    if (!table_exists($table)) {
        return [];
    }
    $stmt = db()->prepare('SELECT id FROM ' . $table . ' WHERE ' . $where);
    $stmt->execute($params);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
};

$articleIds = $idsFor('articles', 'slug LIKE ? OR title LIKE ? OR excerpt LIKE ? OR content LIKE ?', [$like, $like, $like, $like]);
if ($articleIds !== []) {
    $deleteByIds('article_translations', 'article_id', $articleIds);
    $deleteByIds('article_revisions', 'article_id', $articleIds);
    if (table_exists('member_favorites')) {
        $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
        db()->prepare('DELETE FROM member_favorites WHERE target_type = "article" AND target_id IN (' . $placeholders . ')')->execute($articleIds);
    }
    $deleteByIds('articles', 'id', $articleIds);
}

$wikiIds = $idsFor('wiki_pages', 'slug LIKE ? OR title LIKE ? OR content LIKE ?', [$like, $like, $like]);
if ($wikiIds !== []) {
    $deleteByIds('wiki_revisions', 'wiki_page_id', $wikiIds);
    if (table_exists('member_favorites')) {
        $placeholders = implode(',', array_fill(0, count($wikiIds), '?'));
        db()->prepare('DELETE FROM member_favorites WHERE target_type = "wiki_page" AND target_id IN (' . $placeholders . ')')->execute($wikiIds);
    }
    $deleteByIds('wiki_pages', 'id', $wikiIds);
}

$albumIds = $idsFor('albums', 'title LIKE ? OR description LIKE ?', [$like, $like]);
if ($albumIds !== []) {
    $deleteByIds('album_photos', 'album_id', $albumIds);
    if (table_exists('member_favorites')) {
        $placeholders = implode(',', array_fill(0, count($albumIds), '?'));
        db()->prepare('DELETE FROM member_favorites WHERE target_type = "album" AND target_id IN (' . $placeholders . ')')->execute($albumIds);
    }
    $deleteByIds('albums', 'id', $albumIds);
}
if (table_exists('album_subcategories')) {
    db()->prepare('DELETE FROM album_subcategories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
}
if (table_exists('album_categories')) {
    db()->prepare('DELETE FROM album_categories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
}

$webIds = $idsFor('member_webotheque_links', 'title LIKE ? OR url LIKE ? OR description LIKE ? OR tags LIKE ?', [$like, $like, $like, $like]);
if ($webIds !== []) {
    if (table_exists('member_favorites')) {
        $placeholders = implode(',', array_fill(0, count($webIds), '?'));
        db()->prepare('DELETE FROM member_favorites WHERE target_type = "webotheque_link" AND target_id IN (' . $placeholders . ')')->execute($webIds);
    }
    $deleteByIds('member_webotheque_links', 'id', $webIds);
}
if (table_exists('member_webotheque_subcategories')) {
    db()->prepare('DELETE FROM member_webotheque_subcategories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
}
if (table_exists('member_webotheque_categories')) {
    db()->prepare('DELETE FROM member_webotheque_categories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
}

if (table_exists('member_module_subcategories')) {
    db()->prepare('DELETE FROM member_module_subcategories WHERE module_code = "presentations" AND (code LIKE ? OR label LIKE ?)')->execute([$like, $like]);
}
if (table_exists('member_module_categories')) {
    db()->prepare('DELETE FROM member_module_categories WHERE module_code = "presentations" AND code <> "general" AND (code LIKE ? OR label LIKE ?)')->execute([$like, $like]);
}

if (table_exists('member_favorites')) {
    db()->prepare('DELETE FROM member_favorites WHERE title LIKE ? OR url LIKE ? OR target_key LIKE ?')->execute([$like, $like, $like]);
}
if (table_exists('member_notifications')) {
    db()->prepare('DELETE FROM member_notifications WHERE title LIKE ? OR body LIKE ? OR url LIKE ?')->execute([$like, $like, $like]);
}
if (table_exists('content_proposals')) {
    db()->prepare('DELETE FROM content_proposals WHERE title LIKE ? OR summary LIKE ? OR contact LIKE ? OR source_ref LIKE ?')->execute([$like, $like, $like, $like]);
}
if (table_exists('news_translations') && table_exists('news_posts')) {
    $newsIds = $idsFor('news_posts', 'slug LIKE ? OR title LIKE ? OR excerpt LIKE ? OR content LIKE ?', [$like, $like, $like, $like]);
    $deleteByIds('news_translations', 'news_post_id', $newsIds);
}
if (table_exists('news_posts')) {
    db()->prepare('DELETE FROM news_posts WHERE slug LIKE ? OR title LIKE ? OR excerpt LIKE ? OR content LIKE ?')->execute([$like, $like, $like, $like]);
}
if (table_exists('news_sections')) {
    db()->prepare('DELETE FROM news_sections WHERE slug LIKE ? OR name LIKE ?')->execute([$like, $like]);
}
if (table_exists('events')) {
    db()->prepare('DELETE FROM events WHERE slug LIKE ? OR title LIKE ? OR summary LIKE ? OR description LIKE ? OR location LIKE ?')->execute([$like, $like, $like, $like, $like]);
}
if (table_exists('auction_lots')) {
    db()->prepare('DELETE FROM auction_lots WHERE slug LIKE ? OR title LIKE ? OR summary LIKE ? OR description LIKE ?')->execute([$like, $like, $like, $like]);
}
if (table_exists('member_tool_presets')) {
    db()->prepare('DELETE FROM member_tool_presets WHERE label LIKE ? OR payload_json LIKE ?')->execute([$like, $like]);
}
if (table_exists('chatbot_logs')) {
    db()->prepare('DELETE FROM chatbot_logs WHERE question LIKE ? OR answer LIKE ?')->execute([$like, $like]);
}
`, { SELENIUM_TOKEN: token });
}

function prepareFavoriteFixtures(token, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
require_once 'app/member_webotheque.php';
require_once 'app/member_favorites.php';
$token = trim((string) getenv('SELENIUM_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
if ($token === '' || $memberId <= 0) {
    throw new RuntimeException('Missing Selenium fixture input.');
}
ensure_wiki_tables();
ensure_webotheque_table();
ensure_member_favorites_table();
if (function_exists('album_ensure_source_proposal_column')) {
    album_ensure_source_proposal_column();
}

$articleSlug = strtolower($token) . '-article';
$articleTitle = 'Selenium article ' . $token;
db()->prepare('INSERT INTO articles (slug, title, excerpt, content, status, category, subcategory, published_at, author_id) VALUES (?, ?, ?, ?, "published", "general", "", NOW(), ?)')
    ->execute([$articleSlug, $articleTitle, 'Excerpt ' . $token, '<p>Content ' . $token . '</p>', $memberId]);
$articleId = (int) db()->lastInsertId();

$wikiSlug = strtolower($token) . '-wiki';
$wikiTitle = 'Selenium wiki ' . $token;
db()->prepare('INSERT INTO wiki_pages (slug, title, content, category, subcategory, author_id, status, proposal_kind) VALUES (?, ?, ?, "general", "", ?, "published", "page")')
    ->execute([$wikiSlug, $wikiTitle, '<p>Wiki ' . $token . '</p>', $memberId]);
$wikiId = (int) db()->lastInsertId();

$albumTitle = 'Selenium album ' . $token;
db()->prepare('INSERT INTO albums (member_id, category, subcategory, title, description, is_public) VALUES (?, "general", "", ?, ?, 1)')
    ->execute([$memberId, $albumTitle, 'Album ' . $token]);
$albumId = (int) db()->lastInsertId();

$webTitle = 'Selenium webotheque ' . $token;
$webId = webotheque_insert_link($memberId, 'general', $webTitle, 'https://example.org/' . strtolower($token), 'Web ' . $token, 'selenium,' . $token, '');

echo json_encode([
    'article' => ['id' => $articleId, 'slug' => $articleSlug, 'title' => $articleTitle],
    'wiki' => ['id' => $wikiId, 'slug' => $wikiSlug, 'title' => $wikiTitle],
    'album' => ['id' => $albumId, 'title' => $albumTitle],
    'webotheque' => ['id' => $webId, 'title' => $webTitle],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) });
}

function favoriteSaved(memberId, type, targetId) {
  return Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/member_favorites.php';
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$type = (string) getenv('SELENIUM_TYPE');
$targetId = (int) getenv('SELENIUM_TARGET_ID');
echo favorite_is_saved($memberId, $type, $targetId) ? '1' : '0';
`, {
    SELENIUM_MEMBER_ID: String(memberId),
    SELENIUM_TYPE: type,
    SELENIUM_TARGET_ID: String(targetId),
  }).trim()) === 1;
}

function favoriteRecord(memberId, type, targetId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/member_favorites.php';
ensure_member_favorites_table();
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$type = (string) getenv('SELENIUM_TYPE');
$targetId = (int) getenv('SELENIUM_TARGET_ID');
$stmt = db()->prepare('SELECT id, member_id, target_type, target_id, target_key, title, url, created_at FROM member_favorites WHERE member_id = ? AND target_type = ? AND target_id = ? LIMIT 1');
$stmt->execute([$memberId, $type, $targetId]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, {
    SELENIUM_MEMBER_ID: String(memberId),
    SELENIUM_TYPE: type,
    SELENIUM_TARGET_ID: String(targetId),
  });
}

async function submitFavorite(driver, route, query, action, idName, targetId) {
  await visit(driver, route, query);
  const form = await driver.findElement(By.xpath(`//form[.//input[@name="action" and @value="${action}"] and .//input[@name="${idName}" and @value="${targetId}"]]`));
  await submitForm(driver, form);
}

function albumPhotoCount(albumId) {
  return Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$albumId = (int) getenv('SELENIUM_ALBUM_ID');
if ($albumId <= 0 || !table_exists('album_photos')) {
    echo 0;
    return;
}
$stmt = db()->prepare('SELECT COUNT(*) FROM album_photos WHERE album_id = ?');
$stmt->execute([$albumId]);
echo (int) ($stmt->fetchColumn() ?: 0);
`, { SELENIUM_ALBUM_ID: String(albumId) }).trim());
}

function latestAlbumPhoto(albumId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$albumId = (int) getenv('SELENIUM_ALBUM_ID');
if ($albumId <= 0 || !table_exists('album_photos')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, album_id, sort_order, title, caption, file_path, created_at FROM album_photos WHERE album_id = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$albumId]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_ALBUM_ID: String(albumId) });
}

function wikiPageRecord(pageId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$pageId = (int) getenv('SELENIUM_WIKI_PAGE_ID');
if ($pageId <= 0 || !table_exists('wiki_pages')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, slug, title, content FROM wiki_pages WHERE id = ? LIMIT 1');
$stmt->execute([$pageId]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_WIKI_PAGE_ID: String(pageId) });
}

async function submitProposal(driver, route, query, action, fields) {
  await visit(driver, route, query);
  const form = await driver.findElement(By.xpath(`//form[.//input[@name="action" and @value="${action}"]]`));
  for (const [name, value] of Object.entries(fields)) {
    const elements = await form.findElements(By.css(`[name="${name}"]`));
    assert.ok(elements.length > 0, `Champ ${name} introuvable pour ${action}.`);
    await setFieldValue(driver, elements[0], value);
  }
  await submitForm(driver, form);
}

function proposalArtifactCounts(token) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$token = trim((string) getenv('SELENIUM_TOKEN'));
$like = '%' . $token . '%';
$count = static function (string $table, string $where, array $params) {
    if (!table_exists($table)) {
        return 0;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE ' . $where);
    $stmt->execute($params);
    return (int) ($stmt->fetchColumn() ?: 0);
};
echo json_encode([
    'content_proposals' => $count('content_proposals', 'title LIKE ? OR summary LIKE ? OR contact LIKE ? OR source_ref LIKE ?', [$like, $like, $like, $like]),
    'news_posts' => $count('news_posts', 'title LIKE ? OR excerpt LIKE ? OR content LIKE ?', [$like, $like, $like]),
    'news_sections' => $count('news_sections', 'name LIKE ? OR slug LIKE ?', [$like, $like]),
    'events' => $count('events', 'title LIKE ? OR summary LIKE ? OR description LIKE ? OR location LIKE ?', [$like, $like, $like, $like]),
    'auction_lots' => $count('auction_lots', 'title LIKE ? OR summary LIKE ? OR description LIKE ?', [$like, $like, $like]),
    'albums' => $count('albums', 'title LIKE ? OR description LIKE ?', [$like, $like]),
    'webotheque_links' => $count('member_webotheque_links', 'title LIKE ? OR url LIKE ? OR description LIKE ? OR tags LIKE ?', [$like, $like, $like, $like]),
    'presentation_categories' => $count('member_module_categories', 'module_code = "presentations" AND (code LIKE ? OR label LIKE ?)', [$like, $like]),
    'presentation_subcategories' => $count('member_module_subcategories', 'module_code = "presentations" AND (code LIKE ? OR label LIKE ?)', [$like, $like]),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TOKEN: token });
}

function toolPresetId(token, memberId) {
  return Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$token = trim((string) getenv('SELENIUM_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
if (!table_exists('member_tool_presets')) {
    echo 0;
    return;
}
$stmt = db()->prepare('SELECT id FROM member_tool_presets WHERE member_id = ? AND label LIKE ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$memberId, '%' . $token . '%']);
echo (int) ($stmt->fetchColumn() ?: 0);
`, { SELENIUM_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) }).trim());
}

function toolPresetRecord(presetId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$presetId = (int) getenv('SELENIUM_PRESET_ID');
if ($presetId <= 0 || !table_exists('member_tool_presets')) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, member_id, tool_id, label, payload_json, created_at FROM member_tool_presets WHERE id = ? LIMIT 1');
$stmt->execute([$presetId]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_PRESET_ID: String(presetId) });
}

function createUnreadNotifications(token, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/notifications.php';
$token = trim((string) getenv('SELENIUM_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$ids = [];
for ($i = 1; $i <= 2; $i++) {
    notify_member($memberId, 'selenium', 'Notification ' . $token . ' #' . $i, 'Body ' . $token . ' #' . $i, route_url('dashboard'));
    $ids[] = (int) db()->lastInsertId();
}
echo json_encode($ids);
`, { SELENIUM_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) });
}

function unreadNotificationCount(token, memberId) {
  return Number(runSeleniumPhp(`
require_once 'app/bootstrap.php';
$token = trim((string) getenv('SELENIUM_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
if (!table_exists('member_notifications')) {
    echo 0;
    return;
}
$stmt = db()->prepare('SELECT COUNT(*) FROM member_notifications WHERE member_id = ? AND is_read = 0 AND title LIKE ?');
$stmt->execute([$memberId, '%' . $token . '%']);
echo (int) ($stmt->fetchColumn() ?: 0);
`, { SELENIUM_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) }).trim());
}

function notificationRows(token, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$token = trim((string) getenv('SELENIUM_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
if (!table_exists('member_notifications')) {
    echo '[]';
    return;
}
$stmt = db()->prepare('SELECT id, member_id, type, title, body, url, is_read, read_at, created_at FROM member_notifications WHERE member_id = ? AND title LIKE ? ORDER BY id ASC');
$stmt->execute([$memberId, '%' . $token . '%']);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) });
}

function chatbotLogForQuestion(question, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$question = trim((string) getenv('SELENIUM_QUESTION'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
if (!table_exists('chatbot_logs')) {
    echo json_encode(['table_exists' => false, 'row' => null], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}
$stmt = db()->prepare('SELECT id, member_id, question, answer, source_name, latency_ms, had_error, confidence_score, freshness_hours, created_at FROM chatbot_logs WHERE member_id = ? AND question = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$memberId, $question]);
echo json_encode(['table_exists' => true, 'row' => ($stmt->fetch(PDO::FETCH_ASSOC) ?: null)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_QUESTION: question, SELENIUM_MEMBER_ID: String(memberId) });
}

test('Selenium membre/public: favoris articles, wiki, albums et webotheque', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const token = `SELENIUMFAV${Date.now()}`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  const member = memberByCallsign(credentials.username.toUpperCase());
  cleanupCoverageRows(token);
  const fixtures = prepareFavoriteFixtures(token, Number(member.id));

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      const targets = [
        ['articles', fixtures.article.title, 'toggle_favorite_article', 'article_id', fixtures.article.id, 'article'],
        ['wiki', fixtures.wiki.title, 'toggle_favorite_page', 'page_id', fixtures.wiki.id, 'wiki_page'],
        ['albums', fixtures.album.title, 'toggle_favorite_album', 'album_id', fixtures.album.id, 'album'],
        ['webotheque', fixtures.webotheque.title, 'toggle_favorite_link', 'id', fixtures.webotheque.id, 'webotheque_link'],
      ];

      for (const [route, title, action, idName, targetId, type] of targets) {
        await submitFavorite(driver, route, { q: title }, action, idName, targetId);
        assert.equal(favoriteSaved(Number(member.id), type, Number(targetId)), true, `${type} doit etre ajoute aux favoris.`);
        const favorite = favoriteRecord(Number(member.id), type, Number(targetId));
        assert.ok(favorite && Number(favorite.id) > 0, `${type} doit creer une ligne member_favorites.`);
        assert.equal(Number(favorite.member_id), Number(member.id), `${type} doit rattacher le favori au membre connecte.`);
        assert.equal(favorite.target_type, type, `${type} doit stocker le type cible.`);
        assert.equal(Number(favorite.target_id), Number(targetId), `${type} doit stocker l id cible.`);
        assert.equal(favorite.title, title, `${type} doit stocker le titre du favori.`);
        assert.ok(String(favorite.url || '') !== '', `${type} doit stocker une URL de retour.`);
        assert.ok(String(favorite.created_at || '') !== '', `${type} doit horodater le favori.`);
      }

      for (const [route, title, action, idName, targetId, type] of targets) {
        await visit(driver, route, { favorites: '1' });
        const text = await pagePlainText(driver);
        assert.match(text, new RegExp(escapeRegExp(title)), `${type} favori doit apparaitre dans le filtre Favoris.`);
        await submitFavorite(driver, route, { favorites: '1' }, action, idName, targetId);
        assert.equal(favoriteSaved(Number(member.id), type, Number(targetId)), false, `${type} doit etre retire des favoris.`);
        assert.equal(favoriteRecord(Number(member.id), type, Number(targetId)), null, `${type} doit supprimer la ligne member_favorites.`);
      }
    } finally {
      cleanupCoverageRows(token);
    }
  });
});

test('Selenium membre/public: detail article et album couvrent favoris et upload photos', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const token = `SELENIUMDETAIL${Date.now()}`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  const member = memberByCallsign(credentials.username.toUpperCase());
  cleanupCoverageRows(token);
  const fixtures = prepareFavoriteFixtures(token, Number(member.id));
  const photoFixture = writeTinyPngFixture(`${token.toLowerCase()}-album-detail.png`);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);

      await visit(driver, 'article', { slug: fixtures.article.slug });
      const articleFavoriteForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="toggle_favorite"]]'));
      await submitForm(driver, articleFavoriteForm);
      assert.equal(favoriteSaved(Number(member.id), 'article', Number(fixtures.article.id)), true, 'Le detail article doit ajouter le favori.');
      let favorite = favoriteRecord(Number(member.id), 'article', Number(fixtures.article.id));
      assert.ok(favorite && Number(favorite.id) > 0, 'Le detail article doit creer la ligne favori.');
      assert.equal(favorite.title, fixtures.article.title, 'Le detail article doit stocker le titre du favori.');
      assert.match(String(favorite.url || ''), /route=article/, 'Le detail article doit stocker une URL article.');

      await visit(driver, 'album', { id: fixtures.album.id });
      const albumFavoriteForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="toggle_favorite"]]'));
      await submitForm(driver, albumFavoriteForm);
      assert.equal(favoriteSaved(Number(member.id), 'album', Number(fixtures.album.id)), true, 'Le detail album doit ajouter le favori.');
      favorite = favoriteRecord(Number(member.id), 'album', Number(fixtures.album.id));
      assert.ok(favorite && Number(favorite.id) > 0, 'Le detail album doit creer la ligne favori.');
      assert.equal(favorite.title, fixtures.album.title, 'Le detail album doit stocker le titre du favori.');
      assert.match(String(favorite.url || ''), /route=album/, 'Le detail album doit stocker une URL album.');

      const beforePhotos = albumPhotoCount(fixtures.album.id);
      await visit(driver, 'album', { id: fixtures.album.id });
      const uploadForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="upload_album_photos"]]'));
      const photoCaption = `Photo detail ${token}`;
      await setFieldValue(driver, await uploadForm.findElement(By.css('textarea[name="caption"]')), photoCaption);
      await uploadForm.findElement(By.css('input[type="file"][name="photos[]"]')).sendKeys(photoFixture);
      await submitForm(driver, uploadForm);
      assert.equal(albumPhotoCount(fixtures.album.id), beforePhotos + 1, 'Le detail album doit accepter l upload de photo.');
      const uploadedPhoto = latestAlbumPhoto(fixtures.album.id);
      assert.ok(uploadedPhoto && Number(uploadedPhoto.id) > 0, 'L upload album doit creer une ligne photo.');
      assert.equal(Number(uploadedPhoto.album_id), Number(fixtures.album.id), 'La photo uploadee doit etre rattachee au bon album.');
      assert.equal(uploadedPhoto.caption, photoCaption, 'La legende de la photo doit etre persistee.');
      assert.match(String(uploadedPhoto.file_path || ''), /^storage\/uploads\/albums\/.+\.png$/i, 'La photo uploadee doit pointer vers le stockage albums.');
      assert.ok(Number(uploadedPhoto.sort_order) > 0, 'La photo uploadee doit avoir un ordre de tri.');
      assert.ok(String(uploadedPhoto.created_at || '') !== '', 'La photo uploadee doit etre horodatee.');
    } finally {
      cleanupCoverageRows(token);
    }
  });
});

test('Selenium membre/public: detail wiki couvre modification et suppression admin', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const token = `SELENIUMWIKIDETAIL${Date.now()}`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  const member = memberByCallsign(credentials.username.toUpperCase());
  cleanupCoverageRows(token);
  const fixtures = prepareFavoriteFixtures(token, Number(member.id));
  const updatedTitle = `${fixtures.wiki.title} updated`;
  const updatedSlug = `${fixtures.wiki.slug}-updated`;

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);

      await visit(driver, 'wiki_view', { slug: fixtures.wiki.slug });
      const favoriteForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="toggle_favorite"]]'));
      await submitForm(driver, favoriteForm);
      assert.equal(favoriteSaved(Number(member.id), 'wiki_page', Number(fixtures.wiki.id)), true, 'Le detail wiki doit ajouter le favori.');
      const favorite = favoriteRecord(Number(member.id), 'wiki_page', Number(fixtures.wiki.id));
      assert.ok(favorite && Number(favorite.id) > 0, 'Le detail wiki doit creer la ligne favori.');
      assert.equal(favorite.title, fixtures.wiki.title, 'Le detail wiki doit stocker le titre favori.');
      assert.match(String(favorite.url || ''), /route=wiki_view/, 'Le detail wiki doit stocker une URL wiki.');

      const updateForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="update_page"]]'));
      await setFieldValue(driver, await updateForm.findElement(By.css('input[name="title"]')), updatedTitle);
      await setFieldValue(driver, await updateForm.findElement(By.css('input[name="slug"]')), updatedSlug);
      await setFieldValue(driver, await updateForm.findElement(By.css('textarea[name="content"]')), `<p>Wiki detail ${token} modifie.</p>`);
      await submitForm(driver, updateForm);

      const updatedRecord = wikiPageRecord(fixtures.wiki.id);
      assert.ok(updatedRecord, 'La page wiki doit encore exister apres modification.');
      assert.equal(updatedRecord.title, updatedTitle, 'Le detail wiki doit mettre a jour le titre.');
      assert.equal(updatedRecord.slug, updatedSlug, 'Le detail wiki doit mettre a jour le slug.');
      assert.match(String(updatedRecord.content), new RegExp(token), 'Le detail wiki doit mettre a jour le contenu.');

      await visit(driver, 'wiki_view', { slug: updatedSlug });
      const deleteForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="delete_page"]]'));
      await submitForm(driver, deleteForm);
      assert.equal(wikiPageRecord(fixtures.wiki.id), null, 'Le detail wiki doit supprimer la page pour un administrateur.');
    } finally {
      cleanupCoverageRows(token);
    }
  });
});

test('Selenium membre/public: propositions et auto-publications des modules publics', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const token = `SELENIUMPROP${Date.now()}`;
  const contact = `${token.toLowerCase()}@example.test`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupCoverageRows(token);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);

      await submitProposal(driver, 'articles', {}, 'propose_category', {
        proposal_category: `Article category ${token}`,
        proposal_reason: `Reason ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'articles', {}, 'propose_tag', {
        proposal_tag: `article-tag-${token}`,
        proposal_reason: `Reason tag ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'wiki', {}, 'propose_theme', {
        proposal_theme: `Wiki theme ${token}`,
        proposal_reason: `Wiki reason ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'news', {}, 'propose_news', {
        proposal_title: `News ${token}`,
        proposal_summary: `News summary ${token}`,
        proposal_source: `Source ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'news', {}, 'propose_category', {
        proposal_category: `News category ${token}`,
        proposal_reason: `News category reason ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'events', {}, 'propose_event', {
        proposal_title: `Event ${token}`,
        proposal_datetime: '2026-12-19T14:00',
        proposal_location: `Location ${token}`,
        proposal_description: `Event description ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'classifieds', { propose_category: '1' }, 'propose_category', {
        proposal_category: `Classified category ${token}`,
        proposal_name: `Name ${token}`,
        proposal_email: contact,
        proposal_details: `Classified details ${token}`,
      });
      await submitProposal(driver, 'auctions', { propose_lot: '1' }, 'propose_lot', {
        proposal_title: `Auction lot ${token}`,
        proposal_summary: `Auction summary ${token}`,
        proposal_price: '12,00',
        proposal_description: `Auction description ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'albums', { propose_album: '1' }, 'propose_album', {
        proposal_title: `Album proposal ${token}`,
        proposal_keywords: `album-keyword-${token}`,
        proposal_description: `Album proposal description ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'albums', { propose_category: '1' }, 'propose_category', {
        proposal_category_name: `Album category ${token}`,
        proposal_reason: `Album category reason ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'albums', { propose_subcategory: '1' }, 'propose_subcategory', {
        proposal_parent_category: 'general',
        proposal_subcategory_name: `Album subcategory ${token}`,
        proposal_reason: `Album subcategory reason ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'webotheque', { propose_domain: '1' }, 'propose_domain', {
        proposal_domain: `Web domain ${token}`,
        proposal_details: `Web domain details ${token}`,
        proposal_contact: contact,
      });
      await visit(driver, 'webotheque');
      const webothequeCsrf = await firstCsrfToken(driver);
      const webothequeCategoryResponse = await postBrowserForm(driver, routeUrl('webotheque'), {
        _csrf: webothequeCsrf,
        action: 'propose_category',
        proposal_category: `Web category alias ${token}`,
        proposal_details: `Web category alias details ${token}`,
        proposal_contact: contact,
      });
      assert.equal(webothequeCategoryResponse.ok, true, `La proposition webotheque propose_category doit repondre: ${webothequeCategoryResponse.status}`);
      await submitProposal(driver, 'webotheque', { propose_subcategory: '1' }, 'propose_subcategory', {
        proposal_parent_category: 'general',
        proposal_subcategory: `Web subcategory ${token}`,
        proposal_details: `Web subcategory details ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'webotheque', { propose_tag: '1' }, 'propose_tag', {
        proposal_tag: `web-tag-${token}`,
        proposal_details: `Web tag details ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'webotheque', { propose_link: '1' }, 'propose_link', {
        title: `Web link ${token}`,
        url: `https://example.org/${token.toLowerCase()}`,
        description: `Web link description ${token}`,
        tags: `selenium,${token}`,
      });
      await visit(driver, 'presentations');
      const presentationMenu = await driver.findElement(By.css('.member-document-propose-menu'));
      const presentationMenuText = await driver.executeScript('arguments[0].setAttribute("open", ""); return arguments[0].textContent;', presentationMenu);
      assert.match(String(presentationMenuText), /Propos(?:er|e)/i, 'Les presentations doivent afficher le dropdown Proposer.');
      assert.match(String(presentationMenuText), /th.mat|topic/i, 'Le dropdown presentations doit proposer une thematique.');
      assert.match(String(presentationMenuText), /sous th.mat|subtopic/i, 'Le dropdown presentations doit proposer une sous-thematique.');
      assert.match(String(presentationMenuText), /pr.sent|presentation/i, 'Le dropdown presentations doit proposer une presentation.');
      await submitProposal(driver, 'presentations', { propose_category: '1' }, 'propose_category', {
        proposal_category_name: `Presentation topic ${token}`,
        proposal_reason: `Presentation topic reason ${token}`,
        proposal_contact: contact,
      });
      await submitProposal(driver, 'presentations', { propose_subcategory: '1' }, 'propose_subcategory', {
        proposal_parent_category: 'general',
        proposal_subcategory_name: `Presentation subtopic ${token}`,
        proposal_reason: `Presentation subtopic reason ${token}`,
        proposal_contact: contact,
      });

      const counts = proposalArtifactCounts(token);
      assert.ok(counts.content_proposals >= 4, `Des propositions doivent etre creees ou acceptees: ${JSON.stringify(counts)}.`);
      assert.ok(counts.news_posts + counts.content_proposals >= 1, `La proposition news doit laisser une trace: ${JSON.stringify(counts)}.`);
      assert.ok(counts.news_sections + counts.content_proposals >= 1, `La proposition de rubrique news doit laisser une trace: ${JSON.stringify(counts)}.`);
      assert.ok(counts.events + counts.content_proposals >= 1, `La proposition evenement doit laisser une trace: ${JSON.stringify(counts)}.`);
      assert.ok(counts.auction_lots + counts.content_proposals >= 1, `La proposition enchere doit laisser une trace: ${JSON.stringify(counts)}.`);
      assert.ok(counts.albums + counts.content_proposals >= 1, `La proposition album doit laisser une trace: ${JSON.stringify(counts)}.`);
      assert.ok(counts.webotheque_links + counts.content_proposals >= 1, `La proposition webotheque doit laisser une trace: ${JSON.stringify(counts)}.`);
      assert.ok(counts.presentation_categories >= 1, `La thematique presentations auto-validee doit etre creee: ${JSON.stringify(counts)}.`);
      assert.ok(counts.presentation_subcategories >= 1, `La sous-thematique presentations auto-validee doit etre creee: ${JSON.stringify(counts)}.`);
    } finally {
      cleanupCoverageRows(token);
    }
  });
});

test('Selenium membre: RGPD, notifications, chatbot, outils et newsletter', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const token = `SELENIUMUTIL${Date.now()}`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  const member = memberByCallsign(credentials.username.toUpperCase());
  cleanupCoverageRows(token);
  const notificationIds = createUnreadNotifications(token, Number(member.id));
  assert.equal(notificationIds.length, 2, 'Deux notifications Selenium doivent etre creees.');
  let notifications = notificationRows(token, Number(member.id));
  assert.equal(notifications.length, 2, 'Les deux notifications doivent exister en DB.');
  for (const [index, notification] of notifications.entries()) {
    assert.equal(Number(notification.id), Number(notificationIds[index]), 'L id notification retourne doit correspondre a la DB.');
    assert.equal(Number(notification.member_id), Number(member.id), 'La notification doit etre rattachee au membre.');
    assert.equal(notification.type, 'selenium', 'Le type notification doit etre persiste.');
    assert.match(notification.title, new RegExp(`${escapeRegExp(token)} #${index + 1}`), 'Le titre notification doit etre persiste.');
    assert.match(String(notification.body || ''), new RegExp(`${escapeRegExp(token)} #${index + 1}`), 'Le corps notification doit etre persiste.');
    assert.match(String(notification.url || ''), /route=dashboard/, 'La notification doit conserver son URL.');
    assert.equal(Number(notification.is_read), 0, 'La notification doit demarrer non lue.');
    assert.equal(notification.read_at, null, 'Une notification non lue ne doit pas avoir read_at.');
  }

  await withSelenium(t, async (driver) => {
    let visibilityField = '';
    let originalVisibility = '';
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);

      await visit(driver, 'gdpr');
      const visibilityForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="save_visibility"]]'));
      const privateChoices = await visibilityForm.findElements(By.css('input[type="radio"][value="private"]'));
      assert.ok(privateChoices.length > 0, 'La page RGPD doit proposer au moins une visibilite modifiable.');
      visibilityField = await privateChoices[0].getAttribute('name');
      originalVisibility = runSeleniumPhp(`
require_once 'app/bootstrap.php';
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$field = (string) getenv('SELENIUM_FIELD');
if ($field === '' || !table_has_column('members', $field)) {
    echo '';
    return;
}
$stmt = db()->prepare('SELECT ' . $field . ' FROM members WHERE id = ? LIMIT 1');
$stmt->execute([$memberId]);
echo (string) ($stmt->fetchColumn() ?: '');
`, { SELENIUM_MEMBER_ID: String(member.id), SELENIUM_FIELD: visibilityField }).trim();
      await setCheckbox(driver, privateChoices[0], true);
      await submitForm(driver, visibilityForm);
      const savedVisibility = runSeleniumPhp(`
require_once 'app/bootstrap.php';
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$field = (string) getenv('SELENIUM_FIELD');
$stmt = db()->prepare('SELECT ' . $field . ' FROM members WHERE id = ? LIMIT 1');
$stmt->execute([$memberId]);
echo (string) ($stmt->fetchColumn() ?: '');
`, { SELENIUM_MEMBER_ID: String(member.id), SELENIUM_FIELD: visibilityField }).trim();
      assert.equal(savedVisibility, 'private', 'La visibilite RGPD doit etre persistee.');

      await visit(driver, 'gdpr');
      const exportToken = await firstCsrfToken(driver);
      const exportResponse = await postBrowserForm(driver, routeUrl('gdpr'), {
        _csrf: exportToken,
        action: 'export_data',
      });
      assert.equal(exportResponse.ok, true, exportResponse.body);
      assert.match(exportResponse.contentType, /application\/json/i, 'L export RGPD doit renvoyer du JSON.');
      assert.match(exportResponse.body, new RegExp(escapeRegExp(String(member.callsign)), 'i'), 'L export RGPD doit contenir les donnees du membre.');

      await visit(driver, 'notifications', { filter: 'unread' });
      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(token), 'Les notifications non lues creees doivent etre visibles.');
      const markAllForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="mark_all_read"]]'));
      await submitForm(driver, markAllForm);
      assert.equal(unreadNotificationCount(token, Number(member.id)), 0, 'mark_all_read doit marquer les notifications comme lues.');
      notifications = notificationRows(token, Number(member.id));
      assert.ok(notifications.every((notification) => Number(notification.is_read) === 1), 'mark_all_read doit passer toutes les notifications a lues.');
      assert.ok(notifications.every((notification) => String(notification.read_at || '') !== ''), 'mark_all_read doit horodater read_at.');

      await visit(driver, 'chatbot');
      const question = `Question chatbot ${token}`;
      const askForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="ask"]]'));
      await setFieldValue(driver, await askForm.findElement(By.css('[name="question"]')), question);
      await submitForm(driver, askForm);
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(escapeRegExp(question)), 'La question chatbot doit rester dans le fil.');
      const chatbotLog = chatbotLogForQuestion(question, Number(member.id));
      if (chatbotLog.table_exists) {
        assert.ok(chatbotLog.row && Number(chatbotLog.row.id) > 0, 'Le chatbot doit journaliser la question en DB.');
        assert.equal(Number(chatbotLog.row.member_id), Number(member.id), 'Le log chatbot doit etre rattache au membre.');
        assert.equal(chatbotLog.row.question, question, 'Le log chatbot doit stocker la question exacte.');
        assert.ok(String(chatbotLog.row.answer || '') !== '', 'Le log chatbot doit stocker une reponse.');
        assert.ok(String(chatbotLog.row.source_name || '') !== '', 'Le log chatbot doit stocker la source.');
        assert.equal(Number(chatbotLog.row.had_error), 0, 'Le log chatbot de ce parcours ne doit pas etre en erreur.');
        assert.ok(Number(chatbotLog.row.latency_ms) >= 0, 'Le log chatbot doit stocker une latence.');
      }
      const clearForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="clear"]]'));
      await submitForm(driver, clearForm);
      text = await pagePlainText(driver);
      assert.doesNotMatch(text, new RegExp(escapeRegExp(question)), 'Le fil chatbot doit etre vide apres effacement.');

      await visit(driver, 'tools');
      const toolsCsrf = await firstCsrfToken(driver).catch(async () => {
        await visit(driver, 'gdpr');
        return firstCsrfToken(driver);
      });
      const presetLabel = `Preset ${token}`;
      const savePreset = await postBrowserForm(driver, routeUrl('tools'), {
        _csrf: toolsCsrf,
        action: 'save_tool_preset',
        tool_id: 'tool-unit-converter',
        label: presetLabel,
        input_value: `input-${token}`,
        output_value: `output-${token}`,
      });
      assert.equal(savePreset.ok, true, savePreset.body);
      assert.doesNotMatch(savePreset.body, /Une erreur interne|Internal Server Error|HTTP ERROR 5\d\d|Fatal error/i);
      const presetId = toolPresetId(token, Number(member.id));
      assert.ok(presetId > 0, 'Le preset outil doit etre enregistre.');
      const presetRecord = toolPresetRecord(presetId);
      assert.ok(presetRecord && Number(presetRecord.id) === presetId, 'Le preset outil doit etre lisible en DB.');
      assert.equal(Number(presetRecord.member_id), Number(member.id), 'Le preset outil doit etre rattache au membre.');
      assert.equal(presetRecord.tool_id, 'tool-unit-converter', 'Le preset outil doit stocker tool_id.');
      assert.equal(presetRecord.label, presetLabel, 'Le preset outil doit stocker le label.');
      assert.deepEqual(JSON.parse(presetRecord.payload_json), { input: `input-${token}`, output: `output-${token}` }, 'Le preset outil doit stocker le payload JSON.');
      assert.ok(String(presetRecord.created_at || '') !== '', 'Le preset outil doit etre horodate.');
      await visit(driver, 'gdpr');
      const deletePreset = await postBrowserForm(driver, routeUrl('tools'), {
        _csrf: await firstCsrfToken(driver),
        action: 'delete_tool_preset',
        preset_id: String(presetId),
      });
      assert.equal(deletePreset.ok, true, deletePreset.body);
      assert.equal(toolPresetId(token, Number(member.id)), 0, 'Le preset outil doit etre supprime.');
      assert.equal(toolPresetRecord(presetId), null, 'La suppression du preset doit retirer la ligne DB.');

      await visit(driver, 'settings');
      const newsletterForms = await driver.findElements(By.xpath('//form[.//input[@name="action" and @value="toggle_newsletter"]]'));
      assert.ok(newsletterForms.length > 0, 'La page settings doit afficher le formulaire newsletter.');
      const newsletterAction = await newsletterForms[0].findElement(By.css('input[name="newsletter_action"]')).getAttribute('value');
      if (newsletterAction === 'subscribe') {
        const emailInputs = await newsletterForms[0].findElements(By.css('input[name="email"]'));
        if (emailInputs.length > 0) {
          await setFieldValue(driver, emailInputs[0], member.email || `${token.toLowerCase()}@example.test`);
        }
        const consentInputs = await newsletterForms[0].findElements(By.css('input[name="newsletter_consent"]'));
        if (consentInputs.length > 0) {
          await setCheckbox(driver, consentInputs[0], true);
        }
      }
      await submitForm(driver, newsletterForms[0]);
      await visit(driver, 'settings');
      text = await pagePlainText(driver);
      assert.match(text, /newsletter|abonnement|subscribed|unsubscribe|desabonner|inscrire/i, 'La section newsletter doit rester rendue apres bascule.');
    } finally {
      if (visibilityField !== '' && originalVisibility !== '') {
        runSeleniumPhp(`
require_once 'app/bootstrap.php';
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$field = (string) getenv('SELENIUM_FIELD');
$value = (string) getenv('SELENIUM_VALUE');
if ($field !== '' && table_has_column('members', $field)) {
    db()->prepare('UPDATE members SET ' . $field . ' = ? WHERE id = ?')->execute([$value, $memberId]);
}
`, {
          SELENIUM_MEMBER_ID: String(member.id),
          SELENIUM_FIELD: visibilityField,
          SELENIUM_VALUE: originalVisibility,
        });
      }
      cleanupCoverageRows(token);
    }
  });
});
