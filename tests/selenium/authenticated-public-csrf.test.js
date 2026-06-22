const test = require('node:test');
const {
  By,
  assert,
  routeUrl,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  loginAsAdmin,
  requireAdminCredentials,
  skipIfInstallWizard,
  ensureSeleniumFixtures,
} = require('./helpers');

const authenticatedPublicPages = [
  ['home', {}],
  ['donation', {}],
  ['membership', {}],
  ['gdpr', {}],
  ['articles', {}],
  ['wiki', {}],
  ['news', {}],
  ['albums', {}],
  ['classifieds', {}],
  ['events', {}],
  ['auctions', {}],
  ['chatbot', {}],
  ['tools', {}],
  ['newsletter_public', {}],
  ['newsletter_unsubscribe', { token: 'selenium-invalid-token' }],
];

const authenticatedPublicDetails = [
  ['article', { slug: 'selenium-fixture-article' }],
  ['wiki_view', { slug: 'selenium-fixture-wiki' }],
  ['news_view', { slug: 'selenium-fixture-news' }],
  ['event_view', { slug: 'selenium-fixture-event' }],
  ['auction_view', { slug: 'selenium-fixture-lot' }],
];

async function albumDetailScenario(driver) {
  await visit(driver, 'albums', { q: 'Selenium fixture album public' });
  const links = await driver.findElements(By.xpath('//article[contains(@class,"album-tile")]//h2/a[contains(normalize-space(.), "Selenium fixture album public")]'));
  if (links.length === 0) {
    return null;
  }

  return links[0].getAttribute('href');
}

async function assertEveryPostFormHasCsrf(driver, label) {
  const forms = await driver.executeScript(`
    return Array.from(document.forms)
      .filter((form) => ((form.getAttribute('method') || 'get').toLowerCase() === 'post'))
      .map((form, index) => ({
        index,
        action: form.getAttribute('action') || '',
        hasCsrf: !!form.querySelector('input[name="_csrf"]'),
        signature: (form.outerHTML || '').replace(/\\s+/g, ' ').slice(0, 260),
      }));
  `);

  const missing = forms.filter((form) => !form.hasCsrf);
  assert.deepEqual(missing, [], `Formulaire POST public authentifie sans CSRF sur ${label}: ${JSON.stringify(missing)}`);
}

test('Selenium CSRF public authentifie: les pages publiques avec actions membre restent protegees', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  await withSelenium(t, async (driver) => {
    await loginAsAdmin(driver, credentials.username, credentials.password);

    for (const [route, query] of authenticatedPublicPages) {
      await visit(driver, route, query);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }
      await assertEveryPostFormHasCsrf(driver, route);
    }
  });
});

test('Selenium CSRF public authentifie: les details publics avec favoris, propositions ou encheres sont proteges', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  await withSelenium(t, async (driver) => {
    ensureSeleniumFixtures();
    await loginAsAdmin(driver, credentials.username, credentials.password);

    for (const [route, query] of authenticatedPublicDetails) {
      await driver.get(routeUrl(route, query));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }
      await assertEveryPostFormHasCsrf(driver, route);
    }

    const albumUrl = await albumDetailScenario(driver);
    if (albumUrl === null) {
      t.skip('Album fixture public indisponible sur cet environnement.');
      return;
    }

    await driver.get(albumUrl);
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);
    await assertEveryPostFormHasCsrf(driver, 'album');
  });
});
