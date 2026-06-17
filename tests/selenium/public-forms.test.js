const test = require('node:test');
const {
  By,
  assert,
  routeUrl,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  skipIfInstallWizard,
  elementExists,
} = require('./helpers');

test('Selenium login: captcha, champs et next sont conserves', async (t) => {
  await withSelenium(t, async (driver) => {
    const nextUrl = routeUrl('admin_albums', { focus: 'album-wizard' });
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

test('Selenium inscription: le formulaire expose les champs obligatoires du profil radio', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'register');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }

    for (const selector of [
      'input[name="callsign"]',
      'input[name="last_name"]',
      'input[name="first_name"]',
      'input[name="email"]',
      'select[name="country"]',
      'input[name="qth"]',
      'input[name="locator"]',
      'input[name="password"]',
    ]) {
      assert.ok(await elementExists(driver, selector), `Champ attendu absent: ${selector}`);
    }
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
  ['articles', '[data-articles-category-open][href], [data-articles-dialog-open][href]'],
  ['wiki', '[data-wiki-theme-open][href]'],
  ['news', '[data-news-proposal-open][href]'],
  ['events', '[data-event-proposal-open][href]'],
  ['classifieds', '[data-classifieds-category-open][href]'],
  ['webotheque', '[data-webotheque-modal-open][href]'],
  ['albums', 'a[href*="route=login"][href*="route=albums"], a[href*="route=albums"][href*="propose_album"]'],
];

for (const [route, selector] of nativeFallbacks) {
  test(`Selenium fallback natif: ${route} expose des liens utilisables`, async (t) => {
    await withSelenium(t, async (driver) => {
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
