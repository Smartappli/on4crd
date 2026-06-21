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
  skipIfInstallWizard,
  elementExists,
  loginAsAdmin,
  requireAdminCredentials,
  ensureSeleniumFixtures,
  ensureSeleniumRunnable,
  runSeleniumPhp,
} = require('./helpers');

function cleanupNewsletterEmail(email) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/newsletter.php';
$email = getenv('SELENIUM_NEWSLETTER_EMAIL') ?: '';
if ($email !== '') {
    newsletter_ensure_tables();
    $stmt = db()->prepare('SELECT id FROM newsletter_subscribers WHERE email = ?');
    $stmt->execute([$email]);
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    if ($ids !== [] && table_exists('newsletter_deliveries')) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        db()->prepare('DELETE FROM newsletter_deliveries WHERE subscriber_id IN (' . $placeholders . ')')->execute($ids);
    }
    db()->prepare('DELETE FROM newsletter_subscribers WHERE email = ?')->execute([$email]);
}
`, { SELENIUM_NEWSLETTER_EMAIL: email });
}

function newsletterSubscriber(email) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/newsletter.php';
$email = getenv('SELENIUM_NEWSLETTER_EMAIL') ?: '';
newsletter_ensure_tables();
$stmt = db()->prepare('SELECT email, status, unsubscribe_token FROM newsletter_subscribers WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: [], JSON_THROW_ON_ERROR);
`, { SELENIUM_NEWSLETTER_EMAIL: email });

  return JSON.parse(output);
}

test('Selenium login: captcha, champs et next sont conserves', async (t) => {
  await withSelenium(t, async (driver) => {
    const nextUrl = '/index.php?route=admin_albums&focus=album-wizard';
    await driver.get(`${routeUrl('login', { next: nextUrl })}#album-wizard`);
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }

    assert.ok(await elementExists(driver, '[data-login-form] input[name="callsign"]'));
    assert.ok(await elementExists(driver, '[data-login-form] input[name="password"]'));
    assert.ok(await elementExists(driver, '[data-login-form] input[name="captcha"]'));
    const next = await driver.findElement(By.css('[data-login-form] input[name="next"]')).getAttribute('value');
    assert.match(next, /route=admin_albums/);
    assert.match(next, /focus=album-wizard/);
    assert.match(next, /#album-wizard$/);

    const captchaLabel = await driver.findElement(By.xpath('//input[@name="captcha"]/ancestor::label')).getText();
    assert.match(captchaLabel, /\d+\s*\+\s*\d+/);
  });
});

test('Selenium inscription: la creation publique de compte est fermee', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'register');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }

    const body = await pagePlainText(driver);
    assert.match(body, /inscription publique|public registration/i);
    assert.equal(await elementExists(driver, 'input[name="callsign"]'), false);
    assert.equal(await elementExists(driver, 'input[name="password"]'), false);
    assert.ok(await elementExists(driver, 'a[href*="route=membership"]'));
  });
});

test('Selenium mot de passe: oubli et reset rendent leurs formulaires', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'forgot_password');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }
    assert.ok(await elementExists(driver, 'input[type="email"][name="email"]'));

    await visit(driver, 'reset_password', { selector: 'invalid-selector', token: 'invalid-token' });
    assert.ok(await elementExists(driver, 'input[name="selector"]'));
    assert.ok(await elementExists(driver, 'input[name="token"]'));
    assert.ok(await elementExists(driver, 'input[name="password"]'));
  });
});

test('Selenium newsletter publique: email, consentement et CSRF sont presents', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'newsletter_public');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }

    assert.ok(await elementExists(driver, 'input[name="_csrf"]'));
    assert.ok(await elementExists(driver, 'input[type="email"][name="email"]'));
    assert.ok(await elementExists(driver, 'input[name="newsletter_consent"][type="checkbox"]'));
  });
});

test('Selenium newsletter publique: inscription puis desinscription par jeton', async (t) => {
  const email = `selenium.newsletter.${Date.now()}@example.test`;
  if (!(await ensureSeleniumRunnable(t))) {
    return;
  }
  cleanupNewsletterEmail(email);

  await withSelenium(t, async (driver) => {
    try {
      await visit(driver, 'newsletter_public');
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }

      const form = await driver.findElement(By.css('form.stack'));
      await form.findElement(By.css('input[type="email"][name="email"]')).sendKeys(email);
      await form.findElement(By.css('input[name="newsletter_consent"][type="checkbox"]')).click();
      await form.findElement(By.css('button[type="submit"]')).click();
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      await driver.wait(async () => /newsletter|abonn|subscription|confirmed/i.test(await pagePlainText(driver)), timeoutMs);

      let subscriber = newsletterSubscriber(email);
      assert.equal(subscriber.email, email);
      assert.equal(subscriber.status, 'active');
      assert.match(subscriber.unsubscribe_token, /^[a-f0-9]{48}$/);

      await visit(driver, 'newsletter_unsubscribe', { token: subscriber.unsubscribe_token });
      const text = await pagePlainText(driver);
      assert.match(text, /desabonn|désabonn|unsubscribe|unsubscribed|cancel/i);

      subscriber = newsletterSubscriber(email);
      assert.equal(subscriber.status, 'unsubscribed');
    } finally {
      cleanupNewsletterEmail(email);
    }
  });
});

test('Selenium recherche: requete courte et requete vide restent controlees', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'search', { q: 'a', source: 'all' });
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }
    assert.ok(await elementExists(driver, '.site-search-box input[name="q"]'));
    assert.equal(await driver.findElement(By.css('.site-search-box input[name="q"]')).getAttribute('value'), 'a');

    await visit(driver, 'search');
    assert.ok(await elementExists(driver, '.site-search-box input[name="q"]'));
  });
});

const nativeFallbacks = [
  { route: 'articles', selector: '[data-articles-category-open][href], [data-articles-dialog-open][href]' },
  { route: 'wiki', selector: '[data-wiki-theme-open][href]' },
  { route: 'news', selector: '[data-news-proposal-open][href]' },
  { route: 'events', selector: '[data-event-proposal-open][href]' },
  { route: 'classifieds', selector: '[data-classifieds-category-open][href], a[href*="route=classifieds_manage"]' },
  { route: 'webotheque', selector: '[data-webotheque-modal-open][href]', authenticated: true },
  { route: 'albums', selector: 'a[href*="route=login"][href*="albums"], a[href*="route=albums"][href*="propose_album"]' },
];

for (const { route, selector, authenticated = false } of nativeFallbacks) {
  test(`Selenium fallback natif: ${route} expose des liens utilisables`, async (t) => {
    await withSelenium(t, async (driver) => {
      ensureSeleniumFixtures();
      if (authenticated) {
        const credentials = requireAdminCredentials(t);
        if (credentials === null) {
          return;
        }
        await loginAsAdmin(driver, credentials.username, credentials.password);
      }
      await visit(driver, route);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }

      const links = await driver.findElements(By.css(selector));
      if (links.length === 0) {
        t.skip(`Aucun declencheur fallback trouve pour ${route}.`);
        return;
      }

      for (const link of links) {
        const href = await link.getAttribute('href');
        assert.ok(href && !href.startsWith('javascript:'), `Fallback invalide pour ${route}: ${href}`);
      }
    });
  });
}
