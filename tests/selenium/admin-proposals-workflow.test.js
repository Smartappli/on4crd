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

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function xpathLiteral(value) {
  const text = String(value);
  if (!text.includes("'")) {
    return `'${text}'`;
  }
  if (!text.includes('"')) {
    return `"${text}"`;
  }

  return `concat('${text.split("'").join(`', "'", '`)}')`;
}

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

async function setFieldValue(driver, field, value) {
  await driver.executeScript(`
    const field = arguments[0];
    field.value = arguments[1];
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
    const label = field.closest('label');
    const editor = label ? label.querySelector('.wysiwyg-editor[contenteditable="true"]') : null;
    if (editor) {
      editor.innerHTML = arguments[1];
      editor.dispatchEvent(new Event('input', { bubbles: true }));
      editor.dispatchEvent(new Event('change', { bubbles: true }));
    }
  `, field, value);
}

async function setSelectValue(driver, select, value) {
  await driver.executeScript(`
    const select = arguments[0];
    const value = arguments[1];
    select.value = value;
    select.dispatchEvent(new Event('input', { bubbles: true }));
    select.dispatchEvent(new Event('change', { bubbles: true }));
  `, select, value);
}

function phpJson(source, env = {}) {
  return JSON.parse(runSeleniumPhp(source, env).trim() || 'null');
}

function cleanupAdminProposalFixtures(token) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');

$token = trim((string) (getenv('SELENIUM_ADMIN_PROPOSAL_TOKEN') ?: ''));
if ($token === '') {
    return;
}
$like = '%' . $token . '%';

if (table_exists('content_proposals')) {
    db()->prepare('DELETE FROM content_proposals WHERE title LIKE ? OR summary LIKE ? OR contact LIKE ? OR source_ref LIKE ?')
        ->execute([$like, $like, $like, $like]);
}

if (table_exists('member_webotheque_links')) {
    $stmt = db()->prepare('SELECT id FROM member_webotheque_links WHERE title LIKE ? OR url LIKE ? OR description LIKE ? OR tags LIKE ?');
    $stmt->execute([$like, $like, $like, $like]);
    $ids = array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []), static fn(int $id): bool => $id > 0));
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if (table_exists('member_favorites')) {
            db()->prepare('DELETE FROM member_favorites WHERE target_type = ? AND target_id IN (' . $placeholders . ')')
                ->execute(array_merge(['webotheque_link'], $ids));
        }
        db()->prepare('DELETE FROM member_webotheque_links WHERE id IN (' . $placeholders . ')')->execute($ids);
    }
}

if (table_exists('member_library_subcategories')) {
    db()->prepare('DELETE FROM member_library_subcategories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
}

if (table_exists('member_library_categories')) {
    db()->prepare('DELETE FROM member_library_categories WHERE code LIKE ? OR label LIKE ?')->execute([$like, $like]);
}
if (table_exists('events')) {
    db()->prepare('DELETE FROM events WHERE title LIKE ?')->execute([$like]);
}
if (table_exists('auction_lots')) {
    db()->prepare('DELETE FROM auction_lots WHERE title LIKE ?')->execute([$like]);
}
if (table_exists('news_posts')) {
    db()->prepare('DELETE FROM news_posts WHERE title LIKE ?')->execute([$like]);
}
if (table_exists('news_sections')) {
    db()->prepare('DELETE FROM news_sections WHERE name LIKE ? OR slug LIKE ?')->execute([$like, $like]);
}

foreach (['webotheque_links_v2', 'webotheque_categories_v2'] as $cacheKey) {
    if (function_exists('cache_forget')) {
        cache_forget($cacheKey);
    }
}
`, { SELENIUM_ADMIN_PROPOSAL_TOKEN: token });
}

function prepareAdminProposalFixtures(token) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');

$token = trim((string) (getenv('SELENIUM_ADMIN_PROPOSAL_TOKEN') ?: ''));
if ($token === '') {
    throw new RuntimeException('Jeton Selenium absent.');
}
if (!ensure_content_proposals_table() || !ensure_webotheque_table()) {
    throw new RuntimeException('Stockage des propositions ou webotheque indisponible.');
}
$memberId = (int) (db()->query('SELECT id FROM members ORDER BY id ASC LIMIT 1')->fetchColumn() ?: 0);
if ($memberId <= 0) {
    throw new RuntimeException('Aucun membre disponible pour les propositions Selenium.');
}

$lowerToken = strtolower($token);
$createTitle = 'selenium admin proposal create ' . $token;
$reviewTitle = 'selenium admin proposal review ' . $token;
$rejectTitle = 'selenium admin proposal reject ' . $token;
$updateOriginalTitle = 'selenium admin proposal update original ' . $token;
$updateTitle = 'selenium admin proposal update accepted ' . $token;
$deleteOriginalTitle = 'selenium admin proposal delete original ' . $token;
$deleteTitle = 'selenium admin proposal delete accepted ' . $token;
$eventTitle = 'selenium admin proposal event ' . $token;
$auctionTitle = 'selenium admin proposal auction ' . $token;
$newsTitle = 'selenium admin proposal news ' . $token;
$newsSectionTitle = 'selenium admin proposal news section ' . $token;

$createUrl = 'https://example.org/selenium-admin-proposal-create-' . $lowerToken;
$rejectUrl = 'https://example.org/selenium-admin-proposal-reject-' . $lowerToken;
$updateOriginalUrl = 'https://example.org/selenium-admin-proposal-update-original-' . $lowerToken;
$updateUrl = 'https://example.org/selenium-admin-proposal-update-accepted-' . $lowerToken;
$deleteUrl = 'https://example.org/selenium-admin-proposal-delete-original-' . $lowerToken;
$eventSourceRef = 'https://example.org/selenium-admin-proposal-event-' . $lowerToken;
$auctionSourceRef = 'https://example.org/selenium-admin-proposal-auction-' . $lowerToken;
$newsSourceRef = 'https://example.org/selenium-admin-proposal-news-' . $lowerToken;

$updateLinkId = webotheque_insert_link(
    $memberId,
    'general',
    $updateOriginalTitle,
    $updateOriginalUrl,
    'Lien original avant modification ' . $token,
    'selenium,proposal'
);
$deleteLinkId = webotheque_insert_link(
    $memberId,
    'general',
    $deleteOriginalTitle,
    $deleteUrl,
    'Lien original avant suppression ' . $token,
    'selenium,proposal'
);

$createSummary = content_proposal_details_text([
    'Domain' => 'general',
    'Description' => 'Lien cree depuis une proposition admin ' . $token,
    'Tags' => 'selenium, admin-proposal',
]);
$updateSummary = content_proposal_details_text([
    'Action' => 'update_link',
    'Link ID' => (string) $updateLinkId,
    'Domain' => 'general',
    'Description' => 'Lien modifie depuis une proposition admin ' . $token,
    'Tags' => 'selenium, admin-proposal, updated',
]);
$deleteSummary = content_proposal_details_text([
    'Action' => 'delete_link',
    'Link ID' => (string) $deleteLinkId,
    'Reason' => 'Suppression demandee par Selenium ' . $token,
]);
$eventSummary = content_proposal_details_text([
    'Date' => date('Y-m-d H:i', strtotime('+3 days')),
    'Lieu' => 'Salle Selenium',
    'Description' => 'Evenement propose par Selenium ' . $token,
]);
$auctionSummary = content_proposal_details_text([
    'Description' => 'Lot propose par Selenium ' . $token,
    'Prix' => '150',
    'Details' => 'Description detaillee du lot.',
]);
$newsSummary = content_proposal_details_text([
    'Resume' => 'Actualite a publier ' . $token,
    'Excerpt' => 'Resume de test Selenium',
]);

$proposalIds = [
    'create' => content_proposal_create($memberId, 'webotheque', 'content', $createTitle, $createSummary, 'selenium-admin-proposals@example.test', $createUrl, 'pending'),
    'review' => content_proposal_create($memberId, 'webotheque', 'content', $reviewTitle, 'Proposition a relire ' . $token, 'selenium-admin-proposals@example.test', '', 'pending'),
    'reject' => content_proposal_create($memberId, 'webotheque', 'content', $rejectTitle, 'Proposition a refuser ' . $token, 'selenium-admin-proposals@example.test', $rejectUrl, 'pending'),
    'update' => content_proposal_create($memberId, 'webotheque', 'content', $updateTitle, $updateSummary, 'selenium-admin-proposals@example.test', $updateUrl, 'pending'),
    'delete' => content_proposal_create($memberId, 'webotheque', 'content', $deleteTitle, $deleteSummary, 'selenium-admin-proposals@example.test', $deleteUrl, 'pending'),
    'event' => content_proposal_create($memberId, 'events', 'content', $eventTitle, $eventSummary, 'selenium-admin-proposals@example.test', $eventSourceRef, 'pending'),
    'auction' => content_proposal_create($memberId, 'auctions', 'content', $auctionTitle, $auctionSummary, 'selenium-admin-proposals@example.test', $auctionSourceRef, 'pending'),
    'news' => content_proposal_create($memberId, 'news', 'content', $newsTitle, $newsSummary, 'selenium-admin-proposals@example.test', $newsSourceRef, 'pending'),
    'news_category' => content_proposal_create(
        $memberId,
        'news',
        'category',
        $newsSectionTitle,
        'Rubrique news par proposition ' . $token,
        'selenium-admin-proposals@example.test',
        '',
        'pending'
    ),
];

echo json_encode([
    'ids' => $proposalIds,
    'titles' => [
        'create' => $createTitle,
        'review' => $reviewTitle,
        'reject' => $rejectTitle,
        'update' => $updateTitle,
        'delete' => $deleteTitle,
        'event' => $eventTitle,
        'auction' => $auctionTitle,
        'news' => $newsTitle,
        'news_category' => $newsSectionTitle,
    ],
    'urls' => [
        'create' => $createUrl,
        'reject' => $rejectUrl,
        'update_original' => $updateOriginalUrl,
        'update' => $updateUrl,
        'delete' => $deleteUrl,
        'event' => $eventSourceRef,
        'auction' => $auctionSourceRef,
        'news' => $newsSourceRef,
    ],
    'links' => [
        'update' => $updateLinkId,
        'delete' => $deleteLinkId,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_ADMIN_PROPOSAL_TOKEN: token });
}

function prepareAdminModuleProposalFixtures(token) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
require_once 'app/member_library_helpers.php';

$token = trim((string) (getenv('SELENIUM_ADMIN_PROPOSAL_TOKEN') ?: ''));
if ($token === '') {
    throw new RuntimeException('Jeton Selenium absent.');
}
if (!ensure_content_proposals_table() || !ensure_webotheque_table() || !member_library_ensure_categories_table()) {
    throw new RuntimeException('Stockage des propositions ou modules indisponible.');
}
$memberId = (int) (db()->query('SELECT id FROM members ORDER BY id ASC LIMIT 1')->fetchColumn() ?: 0);
if ($memberId <= 0) {
    throw new RuntimeException('Aucun membre disponible pour les propositions Selenium.');
}

$lowerToken = strtolower($token);
$libraryCategoryTitle = 'selenium admin library category ' . $token;
$libraryCategoryReviewedTitle = 'selenium admin library category reviewed ' . $token;
$libraryCategoryRejectedTitle = 'selenium admin library category rejected ' . $token;
$webothequeTitle = 'selenium admin webotheque module ' . $token;
$webothequeUrl = 'https://example.org/selenium-admin-webotheque-module-' . $lowerToken;
$webothequeReviewedUrl = 'https://example.org/selenium-admin-webotheque-module-reviewed-' . $lowerToken;
$webothequeRejectedUrl = 'https://example.org/selenium-admin-webotheque-module-rejected-' . $lowerToken;
$webothequeSummary = content_proposal_details_text([
    'Domain' => 'general',
    'Description' => 'Lien valide depuis admin_webotheque ' . $token,
    'Tags' => 'selenium, admin-module-proposal',
]);

$proposalIds = [
    'library_category' => content_proposal_create(
        $memberId,
        'members_library',
        'category',
        $libraryCategoryTitle,
        'Creation thematique bibliotheque ' . $token,
        'selenium-admin-proposals@example.test',
        '',
        'pending'
    ),
    'library_category_reviewed' => content_proposal_create(
        $memberId,
        'members_library',
        'category',
        $libraryCategoryReviewedTitle,
        'Relecture thematique bibliotheque ' . $token,
        'selenium-admin-proposals@example.test',
        '',
        'pending'
    ),
    'library_category_rejected' => content_proposal_create(
        $memberId,
        'members_library',
        'category',
        $libraryCategoryRejectedTitle,
        'Rejet thematique bibliotheque ' . $token,
        'selenium-admin-proposals@example.test',
        '',
        'pending'
    ),
    'webotheque_content' => content_proposal_create(
        $memberId,
        'webotheque',
        'content',
        $webothequeTitle,
        $webothequeSummary,
        'selenium-admin-proposals@example.test',
        $webothequeUrl,
        'pending'
    ),
    'webotheque_content_reviewed' => content_proposal_create(
        $memberId,
        'webotheque',
        'content',
        'selenium admin webotheque module reviewed ' . $token,
        $webothequeSummary,
        'selenium-admin-proposals@example.test',
        $webothequeReviewedUrl,
        'pending'
    ),
    'webotheque_content_rejected' => content_proposal_create(
        $memberId,
        'webotheque',
        'content',
        'selenium admin webotheque module rejected ' . $token,
        $webothequeSummary,
        'selenium-admin-proposals@example.test',
        $webothequeRejectedUrl,
        'pending'
    ),
];

echo json_encode([
    'ids' => $proposalIds,
    'titles' => [
        'library_category' => $libraryCategoryTitle,
        'library_category_reviewed' => $libraryCategoryReviewedTitle,
        'library_category_rejected' => $libraryCategoryRejectedTitle,
        'webotheque_content' => $webothequeTitle,
        'webotheque_content_reviewed' => 'selenium admin webotheque module reviewed ' . $token,
        'webotheque_content_rejected' => 'selenium admin webotheque module rejected ' . $token,
    ],
    'urls' => [
        'webotheque_content' => $webothequeUrl,
        'webotheque_content_reviewed' => $webothequeReviewedUrl,
        'webotheque_content_rejected' => $webothequeRejectedUrl,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_ADMIN_PROPOSAL_TOKEN: token });
}

function proposalRecord(id) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/content_helpers.php';
ensure_content_proposals_table();
$id = (int) (getenv('SELENIUM_PROPOSAL_ID') ?: 0);
$stmt = db()->prepare('SELECT id, area, proposal_type, title, source_ref, status, moderation_note FROM content_proposals WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_PROPOSAL_ID: String(id) });
}

function eventByTitle(title) {
  return phpJson(`
require_once 'app/bootstrap.php';
if (!table_exists('events')) {
    echo 'null';
    return;
}
$title = trim((string) (getenv('SELENIUM_EVENT_TITLE') ?: ''));
$stmt = db()->prepare('SELECT id, title FROM events WHERE title = ? LIMIT 1');
$stmt->execute([$title]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_EVENT_TITLE: title });
}

function auctionLotByTitle(title) {
  return phpJson(`
require_once 'app/bootstrap.php';
if (!table_exists('auction_lots')) {
    echo 'null';
    return;
}
$title = trim((string) (getenv('SELENIUM_AUCTION_TITLE') ?: ''));
$stmt = db()->prepare('SELECT id, title, status FROM auction_lots WHERE title = ? LIMIT 1');
$stmt->execute([$title]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_AUCTION_TITLE: title });
}

function newsPostByTitle(title) {
  return phpJson(`
require_once 'app/bootstrap.php';
if (!table_exists('news_posts')) {
    echo 'null';
    return;
}
$title = trim((string) (getenv('SELENIUM_NEWS_TITLE') ?: ''));
$stmt = db()->prepare('SELECT id, title, status FROM news_posts WHERE title = ? LIMIT 1');
$stmt->execute([$title]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_NEWS_TITLE: title });
}

function newsSectionByName(name) {
  return phpJson(`
require_once 'app/bootstrap.php';
if (!table_exists('news_sections')) {
    echo 'null';
    return;
}
$name = trim((string) (getenv('SELENIUM_NEWS_SECTION_NAME') ?: ''));
$stmt = db()->prepare('SELECT id, name, slug FROM news_sections WHERE name = ? LIMIT 1');
$stmt->execute([$name]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_NEWS_SECTION_NAME: name });
}

function webothequeLinkById(id) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$id = (int) (getenv('SELENIUM_LINK_ID') ?: 0);
if ($id <= 0 || !ensure_webotheque_table()) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, member_id, category, subcategory, title, url, description, tags FROM member_webotheque_links WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_LINK_ID: String(id) });
}

function webothequeLinkByUrl(url) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/route_helper_loader.php';
app_load_route_helpers('__all');
$url = trim((string) (getenv('SELENIUM_LINK_URL') ?: ''));
if ($url === '' || !ensure_webotheque_table()) {
    echo 'null';
    return;
}
$stmt = db()->prepare('SELECT id, member_id, category, subcategory, title, url, description, tags FROM member_webotheque_links WHERE url = ? LIMIT 1');
$stmt->execute([$url]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_LINK_URL: url });
}

function libraryCategoryByLabel(label) {
  return phpJson(`
require_once 'app/bootstrap.php';
require_once 'app/member_library_helpers.php';
if (!member_library_ensure_categories_table()) {
    echo 'null';
    return;
}
$label = trim((string) (getenv('SELENIUM_LIBRARY_CATEGORY_LABEL') ?: ''));
$stmt = db()->prepare('SELECT code, label FROM member_library_categories WHERE label = ? LIMIT 1');
$stmt->execute([$label]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_LIBRARY_CATEGORY_LABEL: label });
}

async function proposalDashboardForm(driver, title) {
  return driver.findElement(By.xpath(
    `//article[contains(@class,"admin-pending-item")][.//h3[contains(normalize-space(.), ${xpathLiteral(title)})]]//form[.//input[@name="action" and @value="update_content_proposal_status"]]`,
  ));
}

async function moduleProposalForm(driver, title) {
  return driver.findElement(By.xpath(
    `//article[.//h3[contains(normalize-space(.), ${xpathLiteral(title)})]]//form[.//input[@name="action" and @value="update_proposal_status"]]`,
  ));
}

async function updateDashboardProposal(driver, title, status, note) {
  await visit(driver, 'admin');
  const form = await proposalDashboardForm(driver, title);
  await setSelectValue(driver, await form.findElement(By.css('select[name="proposal_status"]')), status);
  await setFieldValue(driver, await form.findElement(By.css('textarea[name="moderation_note"]')), note);
  await submitForm(driver, form);
}

async function updateModuleProposal(driver, route, title, status, note, category = '') {
  await visit(driver, route, { status: 'pending' });
  const form = await moduleProposalForm(driver, title);
  await setSelectValue(driver, await form.findElement(By.css('select[name="proposal_status"]')), status);
  if (category !== '') {
    const categorySelects = await form.findElements(By.css('select[name="proposal_category"]'));
    if (categorySelects.length > 0) {
      await setSelectValue(driver, categorySelects[0], category);
    }
  }
  await setFieldValue(driver, await form.findElement(By.css('textarea[name="moderation_note"]')), note);
  await submitForm(driver, form);
}

async function assertDashboardDoesNotList(driver, title) {
  await visit(driver, 'admin');
  const text = await pagePlainText(driver);
  assert.doesNotMatch(
    text,
    new RegExp(escapeRegExp(title)),
    `La proposition ${title} ne doit plus apparaitre dans la file admin une fois traitee.`,
  );
}

test('Selenium admin propositions: relire, refuser, accepter creation, modification et suppression', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `ADMPROP${Date.now()}`;
  cleanupAdminProposalFixtures(token);
  const fixture = prepareAdminProposalFixtures(token);
  const notes = {
    review: `Note relecture ${token}`,
    reject: `Note refus ${token}`,
    create: `Note creation ${token}`,
    update: `Note modification ${token}`,
    delete: `Note suppression ${token}`,
    event: `Note evenement ${token}`,
    auction: `Note enchere ${token}`,
    news: `Note actualite ${token}`,
    news_category: `Note rubrique actualite ${token}`,
  };

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin');

      let text = await pagePlainText(driver);
      for (const title of Object.values(fixture.titles)) {
        assert.match(text, new RegExp(escapeRegExp(title)), `La proposition ${title} doit apparaitre dans la file admin.`);
      }

      const moduleLinks = await driver.findElements(By.css('a[href*="route=admin_webotheque"]'));
      assert.ok(moduleLinks.length > 0, 'La file admin doit proposer un lien vers le module webotheque concerne.');
      const pendingCardLinks = await driver.findElements(By.css('a[href*="route=admin_webotheque"][href*="status=pending"]'));
      assert.ok(pendingCardLinks.length > 0, 'La carte admin webotheque doit pointer vers les propositions en attente.');

      await updateDashboardProposal(driver, fixture.titles.review, 'reviewed', notes.review);
      let record = proposalRecord(fixture.ids.review);
      assert.equal(record.area, 'webotheque', 'La proposition relue doit rester rattachee au module webotheque.');
      assert.equal(record.proposal_type, 'content', 'La proposition relue doit rester de type content.');
      assert.equal(record.status, 'reviewed', 'La proposition relue doit passer au statut reviewed.');
      assert.equal(record.moderation_note, notes.review, 'La note de relecture doit etre conservee.');
      await assertDashboardDoesNotList(driver, fixture.titles.review);

      await updateDashboardProposal(driver, fixture.titles.reject, 'rejected', notes.reject);
      record = proposalRecord(fixture.ids.reject);
      assert.equal(record.area, 'webotheque', 'La proposition refusee doit rester rattachee au module webotheque.');
      assert.equal(record.proposal_type, 'content', 'La proposition refusee doit rester de type content.');
      assert.equal(record.source_ref, fixture.urls.reject, 'La proposition refusee doit conserver sa reference source.');
      assert.equal(record.status, 'rejected', 'La proposition refusee doit passer au statut rejected.');
      assert.equal(record.moderation_note, notes.reject, 'La note de refus doit etre conservee.');
      assert.equal(webothequeLinkByUrl(fixture.urls.reject), null, 'Un refus ne doit pas creer de lien webotheque.');
      await assertDashboardDoesNotList(driver, fixture.titles.reject);

      await updateDashboardProposal(driver, fixture.titles.create, 'accepted', notes.create);
      record = proposalRecord(fixture.ids.create);
      assert.equal(record.area, 'webotheque', 'La proposition de creation doit rester rattachee au module webotheque.');
      assert.equal(record.proposal_type, 'content', 'La proposition de creation doit rester de type content.');
      assert.equal(record.source_ref, fixture.urls.create, 'La proposition de creation doit conserver son URL source.');
      assert.equal(record.status, 'accepted', 'La creation acceptee doit passer au statut accepted.');
      assert.equal(record.moderation_note, notes.create, 'La note de validation creation doit etre conservee.');
      const createdLink = webothequeLinkByUrl(fixture.urls.create);
      assert.ok(createdLink && Number(createdLink.id) > 0, 'La proposition acceptee doit creer un lien webotheque.');
      assert.ok(Number(createdLink.member_id) > 0, 'Le lien cree par proposition doit etre rattache a un membre.');
      assert.equal(createdLink.category, 'general', 'Le lien cree par proposition doit persister la categorie proposee.');
      assert.equal(createdLink.subcategory, '', 'Le lien cree par proposition ne doit pas forcer de sous-categorie.');
      assert.equal(createdLink.title, fixture.titles.create, 'Le lien cree doit reprendre le titre propose.');
      assert.equal(createdLink.url, fixture.urls.create, 'Le lien cree doit reprendre l URL proposee.');
      assert.match(createdLink.description, new RegExp(escapeRegExp(token)), 'Le lien cree doit reprendre la description proposee.');
      assert.match(createdLink.tags, /selenium/i, 'Le lien cree doit reprendre les tags proposes.');
      assert.match(createdLink.tags, /admin-proposal/i, 'Le lien cree doit reprendre le tag admin-proposal.');

      await visit(driver, 'webotheque', { q: fixture.titles.create });
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(escapeRegExp(fixture.titles.create)), 'Le lien cree doit etre visible dans la webotheque publique.');

      await updateDashboardProposal(driver, fixture.titles.update, 'accepted', notes.update);
      record = proposalRecord(fixture.ids.update);
      assert.equal(record.area, 'webotheque', 'La proposition de modification doit rester rattachee au module webotheque.');
      assert.equal(record.proposal_type, 'content', 'La proposition de modification doit rester de type content.');
      assert.equal(record.source_ref, fixture.urls.update, 'La proposition de modification doit conserver son URL source.');
      assert.equal(record.status, 'accepted', 'La modification acceptee doit passer au statut accepted.');
      assert.equal(record.moderation_note, notes.update, 'La note de validation modification doit etre conservee.');
      const updatedLink = webothequeLinkById(fixture.links.update);
      assert.ok(updatedLink, 'Le lien a modifier doit exister apres validation.');
      assert.ok(Number(updatedLink.member_id) > 0, 'Le lien modifie par proposition doit rester rattache a un membre.');
      assert.equal(updatedLink.category, 'general', 'Le lien modifie par proposition doit conserver sa categorie.');
      assert.equal(updatedLink.title, fixture.titles.update, 'La proposition acceptee doit modifier le titre du lien.');
      assert.equal(updatedLink.url, fixture.urls.update, 'La proposition acceptee doit modifier l URL du lien.');
      assert.match(updatedLink.description, /Lien modifie depuis une proposition admin/i, 'La proposition acceptee doit modifier la description du lien.');
      assert.match(updatedLink.tags, /updated/i, 'La proposition acceptee doit modifier les tags du lien.');

      await updateDashboardProposal(driver, fixture.titles.delete, 'accepted', notes.delete);
      record = proposalRecord(fixture.ids.delete);
      assert.equal(record.area, 'webotheque', 'La proposition de suppression doit rester rattachee au module webotheque.');
      assert.equal(record.proposal_type, 'content', 'La proposition de suppression doit rester de type content.');
      assert.equal(record.source_ref, fixture.urls.delete, 'La proposition de suppression doit conserver sa reference source.');
      assert.equal(record.status, 'accepted', 'La suppression acceptee doit passer au statut accepted.');
      assert.equal(record.moderation_note, notes.delete, 'La note de validation suppression doit etre conservee.');
      assert.equal(webothequeLinkById(fixture.links.delete), null, 'La proposition acceptee doit supprimer le lien cible.');
      await assertDashboardDoesNotList(driver, fixture.titles.delete);

      await updateDashboardProposal(driver, fixture.titles.event, 'accepted', notes.event);
      record = proposalRecord(fixture.ids.event);
      assert.equal(record.area, 'events', 'La proposition evenement doit rester rattachee au module events.');
      assert.equal(record.proposal_type, 'content', 'La proposition evenement doit rester de type content.');
      assert.equal(record.status, 'accepted', 'L acceptation evenement doit passer au statut accepted.');
      assert.equal(record.moderation_note, notes.event, 'La note evenement doit etre conservee.');
      const acceptedEvent = eventByTitle(fixture.titles.event);
      assert.ok(acceptedEvent && Number(acceptedEvent.id) > 0, 'L acceptation evenement doit creer une fiche event.');
      assert.equal(acceptedEvent.title, fixture.titles.event, 'Le titre event doit reprendre le titre de la proposition.');
      await assertDashboardDoesNotList(driver, fixture.titles.event);

      await updateDashboardProposal(driver, fixture.titles.auction, 'accepted', notes.auction);
      record = proposalRecord(fixture.ids.auction);
      assert.equal(record.area, 'auctions', 'La proposition enchere doit rester rattachee au module auctions.');
      assert.equal(record.proposal_type, 'content', 'La proposition enchere doit rester de type content.');
      assert.equal(record.status, 'accepted', 'L acceptation enchere doit passer au statut accepted.');
      assert.equal(record.moderation_note, notes.auction, 'La note enchere doit etre conservee.');
      const acceptedAuction = auctionLotByTitle(fixture.titles.auction);
      assert.ok(acceptedAuction && Number(acceptedAuction.id) > 0, 'L acceptation enchere doit creer un lot auction_lot.');
      assert.equal(acceptedAuction.title, fixture.titles.auction, 'Le titre lot doit reprendre le titre de la proposition.');
      assert.equal(acceptedAuction.status, 'active', 'Le lot cree doit etre actif.');
      await assertDashboardDoesNotList(driver, fixture.titles.auction);

      await updateDashboardProposal(driver, fixture.titles.news, 'accepted', notes.news);
      record = proposalRecord(fixture.ids.news);
      assert.equal(record.area, 'news', 'La proposition actualite doit rester rattachee au module news.');
      assert.equal(record.proposal_type, 'content', 'La proposition actualite doit rester de type content.');
      assert.equal(record.status, 'accepted', 'L acceptation actualite doit passer au statut accepted.');
      assert.equal(record.moderation_note, notes.news, 'La note actualite doit etre conservee.');
      const acceptedNews = newsPostByTitle(fixture.titles.news);
      assert.ok(acceptedNews && Number(acceptedNews.id) > 0, 'L acceptation actualite doit creer un article.');
      assert.equal(acceptedNews.title, fixture.titles.news, 'Le titre article doit reprendre le titre de la proposition.');
      assert.equal(acceptedNews.status, 'published', 'L article cree doit etre publie.');
      await assertDashboardDoesNotList(driver, fixture.titles.news);

      await updateDashboardProposal(driver, fixture.titles.news_category, 'accepted', notes.news_category);
      record = proposalRecord(fixture.ids.news_category);
      assert.equal(record.area, 'news', 'La proposition de rubrique news doit rester rattachee au module news.');
      assert.equal(record.proposal_type, 'category', 'La proposition de rubrique news doit rester de type category.');
      assert.equal(record.status, 'accepted', 'L acceptance rubrique news doit passer au statut accepted.');
      assert.equal(record.moderation_note, notes.news_category, 'La note rubrique news doit etre conservee.');
      const acceptedNewsSection = newsSectionByName(fixture.titles.news_category);
      assert.ok(acceptedNewsSection && Number(acceptedNewsSection.id) > 0, 'L acceptance rubrique news doit creer une section news.');
      assert.equal(acceptedNewsSection.name, fixture.titles.news_category, 'Le nom de section doit reprendre le titre de la proposition.');
      await assertDashboardDoesNotList(driver, fixture.titles.news_category);
    } finally {
      cleanupAdminProposalFixtures(token);
    }
  });
});

test('Selenium admin propositions: validation depuis admin_library et admin_webotheque', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }

  const token = `ADMMODPROP${Date.now()}`;
  cleanupAdminProposalFixtures(token);
  const fixture = prepareAdminModuleProposalFixtures(token);
  const notes = {
    libraryCategory: `Note module bibliotheque ${token}`,
    libraryCategoryReviewed: `Note relu module bibliotheque ${token}`,
    libraryCategoryRejected: `Note refuse module bibliotheque ${token}`,
    webothequeContent: `Note module webotheque ${token}`,
    webothequeContentReviewed: `Note relu module webotheque ${token}`,
    webothequeContentRejected: `Note refuse module webotheque ${token}`,
  };

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);

      await visit(driver, 'admin_library', { status: 'pending' });
      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(escapeRegExp(fixture.titles.library_category)), 'La proposition bibliotheque doit apparaitre dans admin_library.');
      assert.match(text, new RegExp(escapeRegExp(fixture.titles.library_category_reviewed)), 'La proposition revue bibliotheque doit apparaitre dans admin_library.');
      assert.match(text, new RegExp(escapeRegExp(fixture.titles.library_category_rejected)), 'La proposition refusee bibliotheque doit apparaitre dans admin_library.');

      await updateModuleProposal(driver, 'admin_library', fixture.titles.library_category_reviewed, 'reviewed', notes.libraryCategoryReviewed);
      let record = proposalRecord(fixture.ids.library_category_reviewed);
      assert.equal(record.area, 'members_library', 'La proposition de pre-lecture bibliotheque doit rester rattachee au module membres_library.');
      assert.equal(record.proposal_type, 'category', 'La proposition de pre-lecture bibliotheque doit rester de type category.');
      assert.equal(record.status, 'reviewed', 'La proposition bibliotheque doit passer au statut reviewed.');
      assert.equal(record.moderation_note, notes.libraryCategoryReviewed, 'La note reviewed bibliotheque doit etre conservee.');
      assert.equal(libraryCategoryByLabel(fixture.titles.library_category_reviewed), null, 'La proposition vue en reviewed ne doit pas créer de thematique bibliotheque.');

      await updateModuleProposal(driver, 'admin_library', fixture.titles.library_category_rejected, 'rejected', notes.libraryCategoryRejected);
      record = proposalRecord(fixture.ids.library_category_rejected);
      assert.equal(record.area, 'members_library', 'La proposition refusee bibliotheque doit rester rattachee au module membres_library.');
      assert.equal(record.proposal_type, 'category', 'La proposition refusee bibliotheque doit rester de type category.');
      assert.equal(record.status, 'rejected', 'La proposition bibliotheque doit passer au statut rejected.');
      assert.equal(record.moderation_note, notes.libraryCategoryRejected, 'La note refus bibliotheque doit etre conservee.');
      assert.equal(libraryCategoryByLabel(fixture.titles.library_category_rejected), null, 'Une proposition bibliotheque refusee ne doit pas créer de thematique.');

      await updateModuleProposal(driver, 'admin_library', fixture.titles.library_category, 'accepted', notes.libraryCategory);
      record = proposalRecord(fixture.ids.library_category);
      assert.equal(record.area, 'members_library', 'La proposition categorie bibliotheque doit rester rattachee au module membres_library.');
      assert.equal(record.proposal_type, 'category', 'La proposition categorie bibliotheque doit rester de type category.');
      assert.equal(record.status, 'accepted', 'La proposition categorie bibliotheque doit passer au statut accepted.');
      assert.equal(record.moderation_note, notes.libraryCategory, 'La note de validation bibliotheque doit etre conservee.');
      const category = libraryCategoryByLabel(fixture.titles.library_category);
      assert.ok(category && category.code, 'La validation admin_library doit creer la thematique bibliotheque.');
      assert.equal(category.label, fixture.titles.library_category, 'La thematique bibliotheque creee doit reprendre le libelle propose.');

      await visit(driver, 'admin_library');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(escapeRegExp(fixture.titles.library_category)), 'La thematique creee doit etre visible dans admin_library.');

      await visit(driver, 'admin_webotheque', { status: 'pending' });
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(escapeRegExp(fixture.titles.webotheque_content)), 'La proposition webotheque doit apparaitre dans admin_webotheque.');
      assert.match(text, new RegExp(escapeRegExp(fixture.titles.webotheque_content_reviewed)), 'La proposition webotheque relue doit apparaitre dans admin_webotheque.');
      assert.match(text, new RegExp(escapeRegExp(fixture.titles.webotheque_content_rejected)), 'La proposition webotheque refusee doit apparaitre dans admin_webotheque.');

      await updateModuleProposal(
        driver,
        'admin_webotheque',
        fixture.titles.webotheque_content_reviewed,
        'reviewed',
        notes.webothequeContentReviewed,
        'general',
      );
      record = proposalRecord(fixture.ids.webotheque_content_reviewed);
      assert.equal(record.area, 'webotheque', 'La proposition webotheque revue doit rester rattachee au module webotheque.');
      assert.equal(record.proposal_type, 'content', 'La proposition webotheque revue doit rester de type content.');
      assert.equal(record.status, 'reviewed', 'La proposition webotheque revue doit passer au statut reviewed.');
      assert.equal(record.moderation_note, notes.webothequeContentReviewed, 'La note reviewed webotheque doit etre conservee.');
      assert.equal(webothequeLinkByUrl(fixture.urls.webotheque_content_reviewed), null, 'Une proposition webotheque revue ne doit pas creer de lien.');

      await updateModuleProposal(
        driver,
        'admin_webotheque',
        fixture.titles.webotheque_content_rejected,
        'rejected',
        notes.webothequeContentRejected,
        'general',
      );
      record = proposalRecord(fixture.ids.webotheque_content_rejected);
      assert.equal(record.area, 'webotheque', 'La proposition webotheque refusee doit rester rattachee au module webotheque.');
      assert.equal(record.proposal_type, 'content', 'La proposition webotheque refusee doit rester de type content.');
      assert.equal(record.status, 'rejected', 'La proposition webotheque refusee doit passer au statut rejected.');
      assert.equal(record.moderation_note, notes.webothequeContentRejected, 'La note refus webotheque doit etre conservee.');
      assert.equal(webothequeLinkByUrl(fixture.urls.webotheque_content_rejected), null, 'Une proposition webotheque refusee ne doit pas creer de lien.');

      await updateModuleProposal(driver, 'admin_webotheque', fixture.titles.webotheque_content, 'accepted', notes.webothequeContent, 'general');
      record = proposalRecord(fixture.ids.webotheque_content);
      assert.equal(record.area, 'webotheque', 'La proposition lien webotheque doit rester rattachee au module webotheque.');
      assert.equal(record.proposal_type, 'content', 'La proposition lien webotheque doit rester de type content.');
      assert.equal(record.source_ref, fixture.urls.webotheque_content, 'La proposition lien webotheque doit conserver son URL source.');
      assert.equal(record.status, 'accepted', 'La proposition lien webotheque doit passer au statut accepted.');
      assert.equal(record.moderation_note, notes.webothequeContent, 'La note de validation webotheque doit etre conservee.');
      const link = webothequeLinkByUrl(fixture.urls.webotheque_content);
      assert.ok(link && Number(link.id) > 0, 'La validation admin_webotheque doit creer le lien webotheque.');
      assert.ok(Number(link.member_id) > 0, 'Le lien cree depuis admin_webotheque doit etre rattache a un membre.');
      assert.equal(link.category, 'general', 'Le lien cree depuis admin_webotheque doit persister la categorie choisie.');
      assert.equal(link.subcategory, '', 'Le lien cree depuis admin_webotheque ne doit pas forcer de sous-categorie.');
      assert.equal(link.title, fixture.titles.webotheque_content, 'Le lien webotheque cree doit reprendre le titre propose.');
      assert.match(link.description, new RegExp(escapeRegExp(token)), 'Le lien webotheque cree doit reprendre la description proposee.');
      assert.match(link.tags, /selenium/i, 'Le lien webotheque cree doit reprendre les tags proposes.');

      await visit(driver, 'webotheque', { q: fixture.titles.webotheque_content });
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(escapeRegExp(fixture.titles.webotheque_content)), 'Le lien valide depuis admin_webotheque doit etre visible publiquement.');
    } finally {
      cleanupAdminProposalFixtures(token);
    }
  });
});
