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

function auctionLotBySlug(slug) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
$slug = trim((string) (getenv('SELENIUM_AUCTION_SLUG') ?: ''));
$stmt = db()->prepare('SELECT id, slug, title, summary, description, starting_price_cents, reserve_price_cents, min_increment_cents, buy_now_price_cents, current_price_cents, starts_at, ends_at, status, winner_member_id FROM auction_lots WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
echo json_encode($stmt->fetch() ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_AUCTION_SLUG: slug });

  return JSON.parse(output || 'null');
}

function auctionBidState(lotId) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
$lotId = (int) (getenv('SELENIUM_AUCTION_LOT_ID') ?: 0);
$stmt = db()->prepare(
    'SELECT b.id, b.member_id, b.amount_cents, b.created_at, m.callsign
     FROM auction_bids b
     INNER JOIN members m ON m.id = b.member_id
     WHERE b.lot_id = ?
     ORDER BY b.amount_cents DESC, b.id DESC'
);
$stmt->execute([$lotId]);
echo json_encode($stmt->fetchAll() ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`, { SELENIUM_AUCTION_LOT_ID: String(lotId) });

  return JSON.parse(output || '[]');
}

function cleanupAuctionRows(slug) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/cache.php';
$slug = trim((string) (getenv('SELENIUM_AUCTION_SLUG') ?: ''));
if ($slug !== '' && table_exists('auction_lots')) {
    $stmt = db()->prepare('SELECT id FROM auction_lots WHERE slug = ? OR slug LIKE ?');
    $stmt->execute([$slug, $slug . '-%']);
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if (table_exists('auction_bids')) {
            db()->prepare('DELETE FROM auction_bids WHERE lot_id IN (' . $placeholders . ')')->execute($ids);
        }
        db()->prepare('DELETE FROM auction_lots WHERE id IN (' . $placeholders . ')')->execute($ids);
    }
}
$cacheDir = function_exists('cache_storage_dir') ? cache_storage_dir() : __DIR__ . '/../storage/cache/data';
foreach (glob(rtrim($cacheDir, '/') . '/*') ?: [] as $file) {
    $name = basename((string) $file);
    if (stripos($name, 'auction') !== false || stripos($name, 'home_') !== false) {
        @unlink((string) $file);
    }
}
`, { SELENIUM_AUCTION_SLUG: slug });
}

test('Selenium admin encheres: creer, modifier, publier et enchérir sur un lot', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const suffix = Date.now();
  const slug = `selenium-auction-${suffix}`;
  const title = `Selenium lot ${suffix}`;
  const updatedTitle = `${title} modifie`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupAuctionRows(slug);

  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_auctions');

      const createForm = await driver.findElement(By.css('form.stack'));
      await createForm.findElement(By.css('input[name="title"]')).sendKeys(title);
      await createForm.findElement(By.css('input[name="slug"]')).sendKeys(slug);
      await setFieldValue(driver, await createForm.findElement(By.css('textarea[name="summary"]')), 'Resume Selenium enchere.');
      await setFieldValue(driver, await createForm.findElement(By.css('textarea[name="description"]')), '<p>Description Selenium enchere.</p>');
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="starting_price"]')), '10,00');
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="reserve_price"]')), '');
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="min_increment"]')), '2,00');
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="buy_now_price"]')), '');
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="starts_at"]')), '2020-01-01T00:00');
      await setFieldValue(driver, await createForm.findElement(By.css('input[name="ends_at"]')), '2099-12-31T23:00');
      await selectValue(driver, await createForm.findElement(By.css('select[name="status"]')), 'active');
      await submitForm(driver, createForm);

      let lot = auctionLotBySlug(slug);
      assert.ok(lot && Number(lot.id) > 0, 'Le lot cree doit etre present en DB.');
      assert.equal(lot.slug, slug, 'Le slug initial du lot doit etre persiste.');
      assert.equal(lot.title, title, 'Le titre initial du lot doit etre persiste.');
      assert.equal(lot.summary, 'Resume Selenium enchere.', 'Le resume initial du lot doit etre persiste.');
      assert.match(lot.description, /Description Selenium enchere\./, 'La description initiale du lot doit etre persistee.');
      assert.equal(Number(lot.starting_price_cents), 1000, 'Le prix de depart doit etre persiste en cents.');
      assert.equal(lot.reserve_price_cents, null, 'Sans prix de reserve, la colonne reserve_price_cents doit rester nulle.');
      assert.equal(Number(lot.min_increment_cents), 200, 'L increment minimum doit etre persiste en cents.');
      assert.equal(lot.buy_now_price_cents, null, 'Sans prix achat immediat, la colonne buy_now_price_cents doit rester nulle.');
      assert.equal(Number(lot.current_price_cents), 0, 'Avant la premiere enchere, le prix courant DB doit rester a 0.');
      assert.match(String(lot.starts_at), /^2020-01-01 00:00:00$/, 'La date de debut du lot doit etre persistee.');
      assert.match(String(lot.ends_at), /^2099-12-31 23:00:00$/, 'La date de fin du lot doit etre persistee.');
      assert.equal(lot.status, 'active', 'Le lot cree doit etre actif en DB.');
      assert.equal(lot.winner_member_id, null, 'Un lot sans enchere ne doit pas avoir de gagnant.');

      let text = await pagePlainText(driver);
      assert.match(text, new RegExp(title), 'Le lot cree doit apparaitre en admin.');

      const editLink = await driver.findElement(By.xpath(`//li[.//a[contains(normalize-space(.), "${title}")]]//a[contains(@href,"edit=")]`));
      await driver.get(await editLink.getAttribute('href'));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);

      const editForm = await driver.findElement(By.css('form.stack'));
      await setFieldValue(driver, await editForm.findElement(By.css('input[name="title"]')), updatedTitle);
      await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="summary"]')), 'Resume Selenium enchere modifie.');
      await setFieldValue(driver, await editForm.findElement(By.css('textarea[name="description"]')), '<p>Description Selenium enchere modifiee.</p>');
      await selectValue(driver, await editForm.findElement(By.css('select[name="status"]')), 'active');
      await submitForm(driver, editForm);

      lot = auctionLotBySlug(slug);
      assert.equal(lot.title, updatedTitle, 'Le titre modifie du lot doit etre persiste en DB.');
      assert.equal(lot.summary, 'Resume Selenium enchere modifie.', 'Le resume modifie du lot doit etre persiste.');
      assert.match(lot.description, /Description Selenium enchere modifiee/, 'La description modifiee du lot doit etre persistee.');
      assert.equal(Number(lot.starting_price_cents), 1000, 'La modification du texte ne doit pas changer le prix de depart.');
      assert.equal(Number(lot.min_increment_cents), 200, 'La modification du texte ne doit pas changer l increment minimum.');
      assert.match(String(lot.starts_at), /^2020-01-01 00:00:00$/, 'La modification du texte ne doit pas changer la date de debut.');
      assert.match(String(lot.ends_at), /^2099-12-31 23:00:00$/, 'La modification du texte ne doit pas changer la date de fin.');
      assert.equal(lot.status, 'active', 'Le lot modifie doit rester actif en DB.');

      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'Le lot modifie doit rester visible en admin.');

      cleanupAuctionRows('__clear_cache_only__');
      await visit(driver, 'auction_view', { slug });
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'La page publique du lot doit afficher le titre modifie.');
      assert.match(text, /Description Selenium enchere modifiee/i, 'La page publique du lot doit afficher la description modifiee.');
      assert.match(text, /10,00|10\.00|10\s*€/i, 'La page publique doit afficher le prix de depart.');

      const bidForm = await driver.findElement(By.css('form[action*="route=auction_bid"]'));
      await setFieldValue(driver, await bidForm.findElement(By.css('input[name="amount"]')), '12,00');
      await submitForm(driver, bidForm);

      lot = auctionLotBySlug(slug);
      assert.equal(Number(lot.current_price_cents), 1200, 'Le prix courant du lot doit refleter l enchere en DB.');
      const bids = auctionBidState(lot.id);
      assert.ok(bids.length >= 1, 'L enchere doit creer une ligne auction_bids.');
      assert.ok(Number(bids[0].id) > 0, 'La ligne auction_bids doit avoir un identifiant.');
      assert.ok(Number(bids[0].member_id) > 0, 'La ligne auction_bids doit etre rattachee a un membre.');
      assert.equal(Number(bids[0].amount_cents), 1200, 'Le montant de l enchere doit etre persiste en cents.');
      assert.equal(bids[0].callsign, credentials.username.toUpperCase(), 'L enchere doit etre reliee au membre connecte.');
      assert.ok(String(bids[0].created_at || '') !== '', 'La ligne auction_bids doit enregistrer created_at.');

      text = await pagePlainText(driver);
      assert.match(text, /ench[eè]re|bid/i, 'Le retour apres enchere doit rester sur le contexte enchere.');
      assert.match(text, new RegExp(updatedTitle), 'Le lot doit rester affiche apres enchere.');
      assert.match(text, /SELENIUMADMIN/i, 'L historique doit afficher le membre ayant encheri.');
      assert.match(text, /12,00|12\.00|12\s*€/i, 'Le prix courant doit refleter l enchere deposee.');

      await visit(driver, 'auctions');
      text = await pagePlainText(driver);
      assert.match(text, new RegExp(updatedTitle), 'La liste publique des encheres doit inclure le lot actif.');
    } finally {
      cleanupAuctionRows(slug);
    }
  });
});
