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
  runSeleniumPhp,
} = require('./helpers');

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
      if (Array.isArray(value)) {
        for (const item of value) {
          form.append(key, item);
        }
      } else {
        form.append(key, value);
      }
    }
    fetch(url, {
      method: 'POST',
      body: form,
      credentials: 'same-origin',
      redirect: 'follow'
    }).then(async (response) => ({
      ok: true,
      status: response.status,
      body: await response.text()
    })).catch((error) => ({
      ok: false,
      status: 0,
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

function cleanupAdminRows(token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$token = trim((string) getenv('SELENIUM_TOKEN'));
if ($token === '') {
    return;
}
$like = '%' . $token . '%';
$root = dirname(__DIR__);
$unlinkPublic = static function (string $path) use ($root): void {
    $path = trim($path);
    if ($path === '' || str_contains($path, '..')) {
        return;
    }
    foreach ([$path, function_exists('album_thumbnail_public_path') ? album_thumbnail_public_path($path) : ''] as $publicPath) {
        if ($publicPath === '' || str_contains($publicPath, '..')) {
            continue;
        }
        $absolute = $root . '/' . ltrim($publicPath, '/');
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
};
$idsFor = static function (string $table, string $where, array $params): array {
    if (!table_exists($table)) {
        return [];
    }
    $stmt = db()->prepare('SELECT id FROM ' . $table . ' WHERE ' . $where);
    $stmt->execute($params);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
};
$deleteIds = static function (string $table, string $column, array $ids): void {
    if ($ids === [] || !table_exists($table)) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    db()->prepare('DELETE FROM ' . $table . ' WHERE ' . $column . ' IN (' . $placeholders . ')')->execute($ids);
};

if (table_exists('album_photos')) {
    $stmt = db()->prepare('SELECT id, file_path FROM album_photos WHERE title LIKE ? OR caption LIKE ? OR file_path LIKE ?');
    $stmt->execute([$like, $like, $like]);
    $photoRows = $stmt->fetchAll() ?: [];
    foreach ($photoRows as $row) {
        $unlinkPublic((string) ($row['file_path'] ?? ''));
    }
    $photoIds = array_map('intval', array_column($photoRows, 'id'));
    $deleteIds('album_photos', 'id', $photoIds);
}
$albumIds = $idsFor('albums', 'title LIKE ? OR description LIKE ?', [$like, $like]);
if ($albumIds !== []) {
    if (table_exists('album_photos')) {
        $placeholders = implode(',', array_fill(0, count($albumIds), '?'));
        $photoStmt = db()->prepare('SELECT id, file_path FROM album_photos WHERE album_id IN (' . $placeholders . ')');
        $photoStmt->execute($albumIds);
        $rows = $photoStmt->fetchAll() ?: [];
        foreach ($rows as $row) {
            $unlinkPublic((string) ($row['file_path'] ?? ''));
        }
        $deleteIds('album_photos', 'album_id', $albumIds);
    }
    $deleteIds('albums', 'id', $albumIds);
}
if (table_exists('album_subcategories')) {
    db()->prepare('DELETE FROM album_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')->execute([$like, $like, $like]);
}
if (table_exists('album_categories')) {
    db()->prepare('DELETE FROM album_categories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
}

$articleIds = $idsFor('articles', 'slug LIKE ? OR title LIKE ? OR excerpt LIKE ? OR content LIKE ? OR category LIKE ? OR subcategory LIKE ?', [$like, $like, $like, $like, $like, $like]);
if ($articleIds !== []) {
    $deleteIds('article_translations', 'article_id', $articleIds);
    $deleteIds('article_revisions', 'article_id', $articleIds);
    $deleteIds('articles', 'id', $articleIds);
}
if (table_exists('article_subcategories')) {
    db()->prepare('DELETE FROM article_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')->execute([$like, $like, $like]);
}
if (table_exists('article_categories')) {
    db()->prepare('DELETE FROM article_categories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
}
if (table_exists('wiki_subcategories')) {
    db()->prepare('DELETE FROM wiki_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')->execute([$like, $like, $like]);
}
if (table_exists('wiki_categories')) {
    db()->prepare('DELETE FROM wiki_categories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
}
if (table_exists('member_webotheque_subcategories')) {
    db()->prepare('DELETE FROM member_webotheque_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')->execute([$like, $like, $like]);
}
if (table_exists('member_webotheque_categories')) {
    db()->prepare('DELETE FROM member_webotheque_categories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
}
if (table_exists('member_library_subcategories')) {
    db()->prepare('DELETE FROM member_library_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')->execute([$like, $like, $like]);
}
if (table_exists('member_library_categories')) {
    db()->prepare('DELETE FROM member_library_categories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
}
if (table_exists('member_module_subcategories')) {
    db()->prepare('DELETE FROM member_module_subcategories WHERE category_code LIKE ? OR code LIKE ? OR label LIKE ?')->execute([$like, $like, $like]);
}
if (table_exists('member_module_categories')) {
    db()->prepare('DELETE FROM member_module_categories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
}

$newsIds = $idsFor('news_posts', 'slug LIKE ? OR title LIKE ? OR excerpt LIKE ? OR content LIKE ?', [$like, $like, $like, $like]);
if ($newsIds !== []) {
    $deleteIds('news_translations', 'news_post_id', $newsIds);
    $deleteIds('news_posts', 'id', $newsIds);
}
$sectionIds = $idsFor('news_sections', 'slug LIKE ? OR name LIKE ?', [$like, $like]);
if ($sectionIds !== []) {
    $deleteIds('news_section_managers', 'section_id', $sectionIds);
    $deleteIds('news_sections', 'id', $sectionIds);
}

if (table_exists('member_library_documents')) {
    $stmt = db()->prepare('SELECT id, file_path FROM member_library_documents WHERE title LIKE ? OR description LIKE ? OR tags LIKE ? OR file_path LIKE ? OR extracted_text LIKE ?');
    $stmt->execute([$like, $like, $like, $like, $like]);
    $rows = $stmt->fetchAll() ?: [];
    foreach ($rows as $row) {
        $unlinkPublic((string) ($row['file_path'] ?? ''));
    }
    $deleteIds('member_library_documents', 'id', array_map('intval', array_column($rows, 'id')));
}

if (table_exists('classified_ads')) {
    db()->prepare('DELETE FROM classified_ads WHERE title LIKE ? OR description LIKE ? OR location LIKE ? OR contact LIKE ?')->execute([$like, $like, $like, $like]);
}
if (table_exists('member_notifications')) {
    db()->prepare('DELETE FROM member_notifications WHERE title LIKE ? OR body LIKE ? OR url LIKE ?')->execute([$like, $like, $like]);
}
`, { SELENIUM_TOKEN: token });
}

function prepareAlbumFixture(token, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$token = trim((string) getenv('SELENIUM_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
album_ensure_source_proposal_column();
album_ensure_photo_sort_order_column();
$dir = dirname(__DIR__) . '/storage/uploads/albums/selenium';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAGklEQVR4nGP8z8Dwn4GBgYGJgYGB4T8ABQsCBAJH7m4AAAAASUVORK5CYII=');
$paths = [];
for ($i = 1; $i <= 2; $i++) {
    $public = 'storage/uploads/albums/selenium/' . strtolower($token) . '-' . $i . '.png';
    file_put_contents(dirname(__DIR__) . '/' . $public, $png);
    $paths[] = $public;
}
db()->prepare('INSERT INTO albums (member_id, category, subcategory, title, description, is_public) VALUES (?, "general", "", ?, ?, 1)')
    ->execute([$memberId, 'Admin album ' . $token, 'Admin album description ' . $token]);
$albumId = (int) db()->lastInsertId();
$photoIds = [];
foreach ($paths as $index => $path) {
    db()->prepare('INSERT INTO album_photos (album_id, sort_order, title, caption, file_path) VALUES (?, ?, ?, ?, ?)')
        ->execute([$albumId, $index + 1, 'Photo ' . ($index + 1) . ' ' . $token, 'Caption ' . ($index + 1) . ' ' . $token, $path]);
    $photoIds[] = (int) db()->lastInsertId();
}
echo json_encode(['album_id' => $albumId, 'photo_ids' => $photoIds], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) });
}

function albumState(albumId, photoIds = []) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$albumId = (int) getenv('SELENIUM_ALBUM_ID');
$photoIds = array_values(array_filter(array_map('intval', explode(',', (string) getenv('SELENIUM_PHOTO_IDS')))));
$albumStmt = db()->prepare('SELECT id, title, description FROM albums WHERE id = ? LIMIT 1');
$albumStmt->execute([$albumId]);
$album = $albumStmt->fetch() ?: null;
$photos = [];
if ($photoIds !== []) {
    $placeholders = implode(',', array_fill(0, count($photoIds), '?'));
    $stmt = db()->prepare('SELECT id, title, caption, sort_order FROM album_photos WHERE id IN (' . $placeholders . ') ORDER BY id ASC');
    $stmt->execute($photoIds);
    foreach ($stmt->fetchAll() ?: [] as $row) {
        $photos[(int) $row['id']] = $row;
    }
}
echo json_encode(['album' => $album, 'photos' => $photos], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, {
    SELENIUM_ALBUM_ID: String(albumId),
    SELENIUM_PHOTO_IDS: photoIds.join(','),
  });
}

function prepareArticleFixture(token, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$token = trim((string) getenv('SELENIUM_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$category = strtolower($token) . '-cat';
$renamedCategory = strtolower($token) . '-renamed';
$subcategory = strtolower($token) . '-sub';
article_ensure_taxonomy_schema(i18n_domain_locale('articles', 'fr'));
db()->prepare('INSERT INTO article_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')->execute([$category, 'Article Category ' . $token]);
db()->prepare('INSERT INTO article_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')->execute([$category, $subcategory, 'Article Subcategory ' . $token]);
$ids = [];
for ($i = 1; $i <= 2; $i++) {
    db()->prepare('INSERT INTO articles (slug, title, excerpt, content, status, category, subcategory, author_id) VALUES (?, ?, ?, ?, "draft", ?, ?, ?)')
        ->execute([strtolower($token) . '-bulk-' . $i, 'Bulk article ' . $i . ' ' . $token, 'Excerpt ' . $token, '<p>Content ' . $token . '</p>', $category, $subcategory, $memberId]);
    $ids[] = (int) db()->lastInsertId();
}
$scheduledIds = [];
for ($i = 1; $i <= 2; $i++) {
    db()->prepare('INSERT INTO articles (slug, title, excerpt, content, status, category, subcategory, scheduled_at, author_id) VALUES (?, ?, ?, ?, "scheduled", ?, ?, NULL, ?)')
        ->execute([strtolower($token) . '-scheduled-' . $i, 'Scheduled article ' . $i . ' ' . $token, 'Scheduled excerpt ' . $token, '<p>Scheduled content ' . $token . '</p>', $category, $subcategory, $memberId]);
    $scheduledIds[] = (int) db()->lastInsertId();
}
echo json_encode(['ids' => $ids, 'scheduled_ids' => $scheduledIds, 'category' => $category, 'renamed_category' => $renamedCategory], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) });
}

function articleRows(ids) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$ids = array_values(array_filter(array_map('intval', explode(',', (string) getenv('SELENIUM_IDS')))));
if ($ids === []) {
    echo json_encode([]);
    return;
}
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare('SELECT id, status, category, scheduled_at, published_at FROM articles WHERE id IN (' . $placeholders . ') ORDER BY id ASC');
$stmt->execute($ids);
$rows = [];
foreach ($stmt->fetchAll() ?: [] as $row) {
    $rows[(int) $row['id']] = $row;
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_IDS: ids.join(',') });
}

function prepareNewsFixture(token, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$token = trim((string) getenv('SELENIUM_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
if (!table_exists('news_sections')) {
    db()->exec('CREATE TABLE IF NOT EXISTS news_sections (id INT AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(190) NOT NULL UNIQUE, name VARCHAR(190) NOT NULL, sort_order INT NOT NULL DEFAULT 100)');
}
if (!table_exists('news_section_managers')) {
    db()->exec('CREATE TABLE IF NOT EXISTS news_section_managers (member_id INT NOT NULL, section_id INT NOT NULL, PRIMARY KEY (member_id, section_id))');
}
db()->prepare('INSERT INTO news_sections (slug, name, sort_order) VALUES (?, ?, 500)')->execute([strtolower($token) . '-section', 'Section ' . $token]);
$sectionId = (int) db()->lastInsertId();
db()->prepare('INSERT INTO news_posts (section_id, author_id, slug, title, excerpt, content, status) VALUES (?, ?, ?, ?, ?, ?, "pending")')
    ->execute([$sectionId, $memberId, strtolower($token) . '-news', 'Pending news ' . $token, 'Excerpt ' . $token, '<p>Content ' . $token . '</p>']);
$postId = (int) db()->lastInsertId();
echo json_encode(['section_id' => $sectionId, 'post_id' => $postId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) });
}

function newsState(postId, sectionId, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$postId = (int) getenv('SELENIUM_POST_ID');
$sectionId = (int) getenv('SELENIUM_SECTION_ID');
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
$postStmt = db()->prepare('SELECT id, status, moderation_note FROM news_posts WHERE id = ? LIMIT 1');
$postStmt->execute([$postId]);
$managerStmt = db()->prepare('SELECT COUNT(*) FROM news_section_managers WHERE member_id = ? AND section_id = ?');
$managerStmt->execute([$memberId, $sectionId]);
echo json_encode(['post' => $postStmt->fetch() ?: null, 'manager_count' => (int) ($managerStmt->fetchColumn() ?: 0)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, {
    SELENIUM_POST_ID: String(postId),
    SELENIUM_SECTION_ID: String(sectionId),
    SELENIUM_MEMBER_ID: String(memberId),
  });
}

function prepareLibraryFixture(token, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$token = trim((string) getenv('SELENIUM_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
if (function_exists('member_library_ensure_tables')) {
    member_library_ensure_tables();
}
$dir = dirname(__DIR__) . '/storage/uploads/library/selenium';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}
$ids = [];
for ($i = 1; $i <= 2; $i++) {
    $public = 'storage/uploads/library/selenium/' . strtolower($token) . '-' . $i . '.txt';
    file_put_contents(dirname(__DIR__) . '/' . $public, 'Document ' . $i . ' ' . $token);
    db()->prepare('INSERT INTO member_library_documents (member_id, category, subcategory, tags, title, description, file_path, extracted_text) VALUES (?, "general", "", ?, ?, ?, ?, ?)')
        ->execute([$memberId, 'from-' . $token . ', keep-' . $token, 'Library document ' . $i . ' ' . $token, 'Library description ' . $token, $public, 'Extracted ' . $token]);
    $ids[] = (int) db()->lastInsertId();
}
echo json_encode(['ids' => $ids, 'from_tag' => 'from-' . $token, 'to_tag' => 'to-' . $token], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) });
}

function libraryDocs(ids) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$ids = array_values(array_filter(array_map('intval', explode(',', (string) getenv('SELENIUM_IDS')))));
if ($ids === [] || !table_exists('member_library_documents')) {
    echo json_encode([]);
    return;
}
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare('SELECT id, tags FROM member_library_documents WHERE id IN (' . $placeholders . ') ORDER BY id ASC');
$stmt->execute($ids);
$rows = [];
foreach ($stmt->fetchAll() ?: [] as $row) {
    $rows[(int) $row['id']] = $row;
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_IDS: ids.join(',') });
}

function prepareClassifiedFixture(token, memberId) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$token = trim((string) getenv('SELENIUM_TOKEN'));
$memberId = (int) getenv('SELENIUM_MEMBER_ID');
ensure_classified_ads_table();
$ids = [];
for ($i = 1; $i <= 2; $i++) {
    db()->prepare('INSERT INTO classified_ads (owner_member_id, category_code, title, description, location, contact, price_cents, status, expires_at) VALUES (?, "gear", ?, ?, ?, ?, 1000, "pending", NULL)')
        ->execute([$memberId, 'Classified bulk ' . $i . ' ' . $token, 'Classified description ' . $token, 'Location ' . $token, 'selenium@example.test']);
    $ids[] = (int) db()->lastInsertId();
}
echo json_encode(['ids' => $ids], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TOKEN: token, SELENIUM_MEMBER_ID: String(memberId) });
}

function classifiedStatuses(ids) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$ids = array_values(array_filter(array_map('intval', explode(',', (string) getenv('SELENIUM_IDS')))));
if ($ids === [] || !table_exists('classified_ads')) {
    echo json_encode([]);
    return;
}
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare('SELECT id, status FROM classified_ads WHERE id IN (' . $placeholders . ') ORDER BY id ASC');
$stmt->execute($ids);
$rows = [];
foreach ($stmt->fetchAll() ?: [] as $row) {
    $rows[(int) $row['id']] = $row['status'];
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_IDS: ids.join(',') });
}

function prepareTaxonomyEditFixture(token) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$token = strtolower(trim((string) getenv('SELENIUM_TOKEN')));
$category = $token . '-cat';
$subcategory = $token . '-sub';
$categoryLabel = 'Category ' . $token;
$subcategoryLabel = 'Subcategory ' . $token;

album_ensure_categories_table();
album_ensure_subcategories_table();
db()->prepare('INSERT INTO album_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')->execute([$category, $categoryLabel]);
db()->prepare('INSERT INTO album_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')->execute([$category, $subcategory, $subcategoryLabel]);

article_ensure_taxonomy_schema(i18n_domain_locale('articles', 'fr'));
db()->prepare('INSERT INTO article_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')->execute([$category, $categoryLabel]);
db()->prepare('INSERT INTO article_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')->execute([$category, $subcategory, $subcategoryLabel]);

ensure_wiki_tables();
wiki_ensure_categories_table();
wiki_ensure_subcategories_table();
db()->prepare('INSERT INTO wiki_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')->execute([$category, $categoryLabel]);
db()->prepare('INSERT INTO wiki_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')->execute([$category, $subcategory, $subcategoryLabel]);

ensure_webotheque_table();
webotheque_ensure_categories_table(i18n_domain_locale('webotheque', 'fr'));
webotheque_ensure_subcategories_table();
db()->prepare('INSERT INTO member_webotheque_categories (code, label, deleted_at) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')->execute([$category, $categoryLabel]);
db()->prepare('INSERT INTO member_webotheque_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')->execute([$category, $subcategory, $subcategoryLabel]);

member_library_ensure_categories_table();
member_library_ensure_subcategories_table();
db()->prepare('INSERT INTO member_library_categories (code, label) VALUES (?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')->execute([$category, $categoryLabel]);
db()->prepare('INSERT INTO member_library_subcategories (category_code, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)')->execute([$category, $subcategory, $subcategoryLabel]);

ensure_member_module_documents_table();
member_document_ensure_categories_table('presentations');
member_document_ensure_subcategories_table('presentations');
db()->prepare('INSERT INTO member_module_categories (module_code, code, label, deleted_at) VALUES ("presentations", ?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')->execute([$category, $categoryLabel]);
db()->prepare('INSERT INTO member_module_subcategories (module_code, category_code, code, label, deleted_at) VALUES ("presentations", ?, ?, ?, NULL) ON DUPLICATE KEY UPDATE label = VALUES(label), deleted_at = NULL')->execute([$category, $subcategory, $subcategoryLabel]);

echo json_encode(['category' => $category, 'subcategory' => $subcategory], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_TOKEN: token });
}

function taxonomyEditState(category, subcategory) {
  return seleniumJson(`
require_once 'app/bootstrap.php';
$category = trim((string) getenv('SELENIUM_CATEGORY'));
$subcategory = trim((string) getenv('SELENIUM_SUBCATEGORY'));
$state = [];
$single = static function (string $table, string $where, array $params): ?string {
    if (!table_exists($table)) {
        return null;
    }
    $stmt = db()->prepare('SELECT label FROM ' . $table . ' WHERE ' . $where . ' LIMIT 1');
    $stmt->execute($params);
    $value = $stmt->fetchColumn();
    return is_string($value) ? $value : null;
};
$state['albums_category'] = $single('album_categories', 'code = ?', [$category]);
$state['albums_subcategory'] = $single('album_subcategories', 'category_code = ? AND code = ?', [$category, $subcategory]);
$state['articles_category'] = $single('article_categories', 'code = ?', [$category]);
$state['articles_subcategory'] = $single('article_subcategories', 'category_code = ? AND code = ?', [$category, $subcategory]);
$state['wiki_category'] = $single('wiki_categories', 'code = ?', [$category]);
$state['wiki_subcategory'] = $single('wiki_subcategories', 'category_code = ? AND code = ?', [$category, $subcategory]);
$state['webotheque_category'] = $single('member_webotheque_categories', 'code = ?', [$category]);
$state['webotheque_subcategory'] = $single('member_webotheque_subcategories', 'category_code = ? AND code = ?', [$category, $subcategory]);
$state['library_category'] = $single('member_library_categories', 'code = ?', [$category]);
$state['library_subcategory'] = $single('member_library_subcategories', 'category_code = ? AND code = ?', [$category, $subcategory]);
$state['presentations_category'] = $single('member_module_categories', 'module_code = "presentations" AND code = ?', [$category]);
$state['presentations_subcategory'] = $single('member_module_subcategories', 'module_code = "presentations" AND category_code = ? AND code = ?', [$category, $subcategory]);
echo json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_CATEGORY: category, SELENIUM_SUBCATEGORY: subcategory });
}

test('Selenium admin albums: maintenance album, photos, ordre et miniatures', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const token = `SELENIUMADMALB${Date.now()}`;
  const member = memberByCallsign(credentials.username.toUpperCase());
  cleanupAdminRows(token);
  const fixture = prepareAlbumFixture(token, Number(member.id));
  const [firstPhotoId, secondPhotoId] = fixture.photo_ids;

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_albums');

      const rebuildForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="rebuild_thumbnails"]]'));
      await submitForm(driver, rebuildForm);

      await visit(driver, 'admin_albums');
      const albumForm = await driver.findElement(By.xpath(`//form[.//input[@name="action" and @value="update_album"] and .//input[@name="album_id" and @value="${fixture.album_id}"]]`));
      await setFieldValue(driver, await albumForm.findElement(By.css('input[name="title"]')), `Admin album updated ${token}`);
      await setFieldValue(driver, await albumForm.findElement(By.css('textarea[name="description"]')), `Admin album updated description ${token}`);
      await submitForm(driver, albumForm);
      let state = albumState(fixture.album_id, fixture.photo_ids);
      assert.equal(state.album.title, `Admin album updated ${token}`, 'Le titre album admin doit etre mis a jour.');

      await visit(driver, 'admin_albums');
      const photoForm = await driver.findElement(By.xpath(`//form[.//input[@name="action" and @value="update_photo"] and .//input[@name="photo_id" and @value="${firstPhotoId}"]]`));
      await setFieldValue(driver, await photoForm.findElement(By.css('input[name="title"]')), `Photo updated ${token}`);
      await setFieldValue(driver, await photoForm.findElement(By.css('textarea[name="caption"]')), `Caption updated ${token}`);
      await submitForm(driver, photoForm);
      state = albumState(fixture.album_id, fixture.photo_ids);
      assert.equal(state.photos[firstPhotoId].title, `Photo updated ${token}`, 'Le titre photo doit etre mis a jour.');

      await visit(driver, 'admin_albums');
      const reorderForm = await driver.findElement(By.xpath(`//form[.//input[@name="action" and @value="reorder_photo"] and .//input[@name="photo_id" and @value="${secondPhotoId}"]]`));
      await submitForm(driver, reorderForm);
      state = albumState(fixture.album_id, fixture.photo_ids);
      assert.equal(Number(state.photos[secondPhotoId].sort_order), 1, 'La seconde photo doit remonter en premiere position.');

      await visit(driver, 'admin_albums');
      const deleteForm = await driver.findElement(By.xpath(`//form[.//input[@name="action" and @value="delete_photo"] and .//input[@name="photo_id" and @value="${firstPhotoId}"]]`));
      await submitForm(driver, deleteForm);
      state = albumState(fixture.album_id, fixture.photo_ids);
      assert.equal(Boolean(state.photos[firstPhotoId]), false, 'La photo supprimee ne doit plus etre en base.');
    } finally {
      cleanupAdminRows(token);
    }
  });
});

test('Selenium admin articles: taxonomie, bulk update et relance programmee', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const token = `SELENIUMADMART${Date.now()}`;
  const member = memberByCallsign(credentials.username.toUpperCase());
  cleanupAdminRows(token);
  const fixture = prepareArticleFixture(token, Number(member.id));

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_articles');
      let csrf = await firstCsrfToken(driver);
      let response = await postBrowserForm(driver, routeUrl('admin_articles'), {
        _csrf: csrf,
        action: 'save_category',
        old_code: fixture.category,
        new_code: fixture.renamed_category,
      });
      assert.equal(response.ok, true, response.body);
      assert.doesNotMatch(response.body, /Une erreur interne|Internal Server Error|Fatal error/i);
      let rows = articleRows([...fixture.ids, ...fixture.scheduled_ids]);
      for (const row of Object.values(rows)) {
        assert.equal(row.category, fixture.renamed_category, 'Le renommage de categorie doit propager les articles.');
      }

      await visit(driver, 'admin_articles');
      csrf = await firstCsrfToken(driver);
      response = await postBrowserForm(driver, routeUrl('admin_articles'), {
        _csrf: csrf,
        action: 'bulk_update_articles',
        'ids[]': fixture.ids.map(String),
        bulk_op: 'published',
      });
      assert.equal(response.ok, true, response.body);
      rows = articleRows(fixture.ids);
      for (const row of Object.values(rows)) {
        assert.equal(row.status, 'published', 'Le bulk update article doit publier les articles selectionnes.');
      }

      await visit(driver, 'admin_articles');
      const retryForm = await driver.findElement(By.xpath(`//form[.//input[@name="action" and @value="retry_scheduled_article"] and .//input[@name="id" and @value="${fixture.scheduled_ids[0]}"]]`));
      await submitForm(driver, retryForm);
      rows = articleRows([fixture.scheduled_ids[0]]);
      assert.ok(rows[fixture.scheduled_ids[0]].scheduled_at, 'La relance individuelle doit renseigner une date de publication.');

      await visit(driver, 'admin_articles');
      csrf = await firstCsrfToken(driver);
      response = await postBrowserForm(driver, routeUrl('admin_articles'), {
        _csrf: csrf,
        action: 'retry_scheduled_bulk',
        'ids[]': [String(fixture.scheduled_ids[1])],
      });
      assert.equal(response.ok, true, response.body);
      rows = articleRows([fixture.scheduled_ids[1]]);
      assert.ok(rows[fixture.scheduled_ids[1]].scheduled_at, 'La relance groupée doit renseigner une date de publication.');
    } finally {
      cleanupAdminRows(token);
    }
  });
});

test('Selenium admin news: moderation et attribution de responsable de rubrique', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const token = `SELENIUMADMNEWS${Date.now()}`;
  const member = memberByCallsign(credentials.username.toUpperCase());
  cleanupAdminRows(token);
  const fixture = prepareNewsFixture(token, Number(member.id));

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_news');

      const moderationForm = await driver.findElement(By.xpath(`//form[.//input[@name="action" and @value="moderate_post"] and .//input[@name="post_id" and @value="${fixture.post_id}"]]`));
      await setFieldValue(driver, await moderationForm.findElement(By.css('select[name="status"]')), 'published');
      await setFieldValue(driver, await moderationForm.findElement(By.css('textarea[name="moderation_note"]')), `Moderation ${token}`);
      await submitForm(driver, moderationForm);
      let state = newsState(fixture.post_id, fixture.section_id, Number(member.id));
      assert.equal(state.post.status, 'published', 'La moderation news doit publier le post.');

      await visit(driver, 'admin_news');
      const managerForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="assign_section_manager"]]'));
      await setFieldValue(driver, await managerForm.findElement(By.css('select[name="member_id"]')), String(member.id));
      await setFieldValue(driver, await managerForm.findElement(By.css('select[name="section_id"]')), String(fixture.section_id));
      await submitForm(driver, managerForm);
      state = newsState(fixture.post_id, fixture.section_id, Number(member.id));
      assert.equal(Number(state.manager_count), 1, 'Le responsable de rubrique doit etre affecte.');
    } finally {
      cleanupAdminRows(token);
    }
  });
});

test('Selenium admin bibliotheque et petites annonces: fusion tags, bulk delete et bulk statuts', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const token = `SELENIUMADMBULK${Date.now()}`;
  const member = memberByCallsign(credentials.username.toUpperCase());
  cleanupAdminRows(token);
  const library = prepareLibraryFixture(token, Number(member.id));
  const classifieds = prepareClassifiedFixture(token, Number(member.id));

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_library', { q: token });

      const mergeForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="merge_tags"]]'));
      await setFieldValue(driver, await mergeForm.findElement(By.css('input[name="from_tag"]')), library.from_tag);
      await setFieldValue(driver, await mergeForm.findElement(By.css('input[name="to_tag"]')), library.to_tag);
      await submitForm(driver, mergeForm);
      let docs = libraryDocs(library.ids);
      assert.equal(Object.keys(docs).length, library.ids.length, 'Les documents bibliotheque doivent exister apres fusion de tags.');
      for (const row of Object.values(docs)) {
        assert.match(row.tags.toLowerCase(), new RegExp(library.to_tag.toLowerCase()), 'La fusion de tags doit ajouter le tag cible.');
        assert.doesNotMatch(row.tags.toLowerCase(), new RegExp(library.from_tag.toLowerCase()), 'La fusion de tags doit retirer le tag source.');
      }

      await visit(driver, 'admin_library', { q: token });
      const deleteCsrf = await firstCsrfToken(driver);
      const deleteResponse = await postBrowserForm(driver, routeUrl('admin_library'), {
        _csrf: deleteCsrf,
        action: 'bulk_delete_documents',
        'ids[]': library.ids.map(String),
      });
      assert.equal(deleteResponse.ok, true, 'La suppression groupée bibliotheque doit repondre au POST navigateur.');
      assert.ok(deleteResponse.status < 500, 'La suppression groupée bibliotheque ne doit pas produire d erreur serveur.');
      docs = libraryDocs(library.ids);
      assert.equal(Object.keys(docs).length, 0, 'La suppression groupée bibliotheque doit retirer les documents.');

      await visit(driver, 'admin_classifieds', { q: token });
      const classifiedForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="bulk_update"]]'));
      await setFieldValue(driver, await classifiedForm.findElement(By.css('select[name="bulk_op"]')), 'active');
      for (const id of classifieds.ids) {
        const checkbox = await classifiedForm.findElement(By.css(`input[name="ids[]"][value="${id}"]`));
        await setCheckbox(driver, checkbox, true);
      }
      await submitForm(driver, classifiedForm);
      let statuses = classifiedStatuses(classifieds.ids);
      assert.equal(Object.values(statuses).every((status) => status === 'active'), true, 'Le bulk update annonces doit activer les annonces.');

      await visit(driver, 'admin_classifieds', { q: token });
      const deleteClassifiedForm = await driver.findElement(By.xpath('//form[.//input[@name="action" and @value="bulk_update"]]'));
      await setFieldValue(driver, await deleteClassifiedForm.findElement(By.css('select[name="bulk_op"]')), 'delete');
      for (const id of classifieds.ids) {
        const checkbox = await deleteClassifiedForm.findElement(By.css(`input[name="ids[]"][value="${id}"]`));
        await setCheckbox(driver, checkbox, true);
      }
      await submitForm(driver, deleteClassifiedForm);
      statuses = classifiedStatuses(classifieds.ids);
      assert.equal(Object.keys(statuses).length, 0, 'La suppression groupée annonces doit retirer les annonces.');
    } finally {
      cleanupAdminRows(token);
    }
  });
});
