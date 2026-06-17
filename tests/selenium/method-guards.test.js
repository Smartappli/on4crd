const test = require('node:test');
const {
  assert,
  routeUrl,
  withSelenium,
  waitForDocumentReady,
  assertNoServerError,
  pagePlainText,
  skipIfInstallWizard,
} = require('./helpers');

const postOnlyRoutes = [
  'logout',
  'toggle_theme',
  'set_language',
  'set_accent',
  'set_theme',
  'idea_submit',
  'footer_contact',
];

for (const route of postOnlyRoutes) {
  test(`Selenium methodes HTTP: ${route} refuse un GET de maniere controlee`, async (t) => {
    await withSelenium(t, async (driver) => {
      await driver.get(routeUrl(route));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }

      const text = await pagePlainText(driver);
      assert.match(
        text,
        /Method not allowed|M.thode non autoris.e|methode non autorisee|method_not_allowed|405/i,
        `${route} doit refuser GET sans erreur serveur.`,
      );
    });
  });
}

test('Selenium routing: les routes avec extension interdite rendent un 404 controle', async (t) => {
  await withSelenium(t, async (driver) => {
    await driver.get(routeUrl('bad.route'));
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }

    const text = await pagePlainText(driver);
    assert.match(text, /404|introuvable|not found/i);
  });
});
