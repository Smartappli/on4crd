const test = require('node:test');
const {
  By,
  assert,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  assertPageHasContent,
  pagePlainText,
  skipIfInstallWizard,
  ensureSeleniumFixtures,
} = require('./helpers');

const detailScenarios = [
  {
    label: 'articles',
    listRoute: 'articles',
    linkSelector: 'a[href*="route=article"][href*="slug="]',
  },
  {
    label: 'wiki',
    listRoute: 'wiki',
    linkSelector: 'a[href*="route=wiki_view"][href*="slug="]',
  },
  {
    label: 'actualites',
    listRoute: 'news',
    linkSelector: 'a[href*="route=news_view"][href*="slug="]',
  },
  {
    label: 'albums',
    listRoute: 'albums',
    linkSelector: 'a[href*="route=album"][href*="id="]',
  },
  {
    label: 'encheres',
    listRoute: 'auctions',
    linkSelector: 'a[href*="route=auction_view"][href*="slug="]',
  },
  {
    label: 'evenements',
    listRoute: 'events',
    linkSelector: 'a[href*="route=event_view"][href*="slug="]',
  },
];

for (const scenario of detailScenarios) {
  test(`Selenium liste/detail: ${scenario.label} ouvre le premier detail disponible`, async (t) => {
    await withSelenium(t, async (driver) => {
      ensureSeleniumFixtures();
      await visit(driver, scenario.listRoute);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }

      const links = await driver.findElements(By.css(scenario.linkSelector));
      if (links.length === 0) {
        t.skip(`Aucun lien detail ${scenario.label} disponible sur cet environnement.`);
        return;
      }

      const href = await links[0].getAttribute('href');
      assert.ok(href && href.includes('route='), `Lien detail invalide pour ${scenario.label}.`);
      await driver.get(href);
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      await assertPageHasContent(driver, `detail ${scenario.label}`);
    });
  });
}

const taxonomyRoutes = ['articles', 'wiki', 'albums'];

for (const route of taxonomyRoutes) {
  test(`Selenium taxonomie publique: ${route} applique un filtre sans erreur`, async (t) => {
    await withSelenium(t, async (driver) => {
      ensureSeleniumFixtures();
      await visit(driver, route);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }

      const filterLinks = await driver.findElements(By.css('.module-taxonomy-item[href], .articles-category-item[href], .wiki-theme-item[href]'));
      if (filterLinks.length === 0) {
        t.skip(`Aucun filtre de taxonomie disponible pour ${route}.`);
        return;
      }

      const href = await filterLinks[0].getAttribute('href');
      await driver.get(href);
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      await assertPageHasContent(driver, `filtre ${route}`);
    });
  });
}

test('Selenium petites annonces: recherche et categories restent navigables', async (t) => {
  await withSelenium(t, async (driver) => {
    ensureSeleniumFixtures();
    await visit(driver, 'classifieds', { q: 'radio' });
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }
    await assertPageHasContent(driver, 'petites annonces recherche');

    const categoryLinks = await driver.findElements(By.css('.classifieds-category-pill[href]'));
    if (categoryLinks.length === 0) {
      t.skip('Aucune categorie de petites annonces disponible.');
      return;
    }

    await driver.get(await categoryLinks[0].getAttribute('href'));
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);
    const text = await pagePlainText(driver);
    assert.match(text, /annonce|classified|categorie|category|radio|ON4CRD/i);
  });
});
