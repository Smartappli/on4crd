const test = require('node:test');
const {
  assert,
  withSelenium,
  visit,
  assertPageHasContent,
  pagePlainText,
  skipIfInstallWizard,
  isOutsideBaseUrl,
} = require('./helpers');

const missingDetails = [
  ['news_view', { slug: 'selenium-missing-news' }],
  ['article', { slug: 'selenium-missing-article' }],
  ['wiki_view', { slug: 'selenium-missing-wiki' }],
  ['album', { id: 999999999 }],
  ['event_view', { slug: 'selenium-missing-event' }],
  ['auction_view', { slug: 'selenium-missing-lot' }],
  ['ad_click', { id: 999999999 }],
];

for (const [route, query] of missingDetails) {
  test(`Selenium detail public: ${route} introuvable reste controle`, async (t) => {
    await withSelenium(t, async (driver) => {
      await visit(driver, route, query);
      if (await isOutsideBaseUrl(driver)) {
        t.skip('La route redirige hors de SELENIUM_BASE_URL; verification locale ignoree.');
        return;
      }
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }

      await assertPageHasContent(driver, route);
      const text = await pagePlainText(driver);
      assert.match(
        text,
        /404|introuvable|not found|indisponible|unavailable|invalide|invalid/i,
        `${route} doit afficher une page introuvable ou indisponible controlee.`,
      );
    });
  });
}
