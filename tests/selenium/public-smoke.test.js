const test = require('node:test');
const {
  By,
  assert,
  withSelenium,
  visit,
  firstText,
} = require('./helpers');

const publicRoutes = [
  ['home', {}],
  ['articles', {}],
  ['wiki', {}],
  ['albums', {}],
  ['search', { q: 'radio', source: 'all' }],
];

for (const [route, query] of publicRoutes) {
  test(`Selenium public smoke: ${route} ne renvoie pas d'erreur serveur`, async (t) => {
    await withSelenium(t, async (driver) => {
      await visit(driver, route, query);
      const bodyText = await firstText(driver, 'body');
      assert.ok(bodyText.length > 0, `La route ${route} doit rendre du contenu.`);
    });
  });
}

test('Selenium recherche: formulaire, query et resultats restent rendus', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'search', { q: 'on4crd', source: 'all' });
    const input = await driver.findElement(By.css('.site-search-box input[name="q"]'));
    assert.equal(await input.getAttribute('value'), 'on4crd');
    assert.ok((await driver.findElements(By.css('.site-search-results'))).length > 0);
  });
});

test('Selenium admin: acces non authentifie redirige vers login avec next conserve', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'admin_albums', { focus: 'album-wizard' });
    const bodyText = await firstText(driver, 'body');
    if (/Assistant de d.ploiement ON4CRD|installation/i.test(bodyText)) {
      t.skip('Instance locale non installee; controle de redirection admin ignore.');
      return;
    }
    assert.match(await driver.getCurrentUrl(), /route=login/);
    assert.ok((await driver.findElements(By.css('[data-login-form]'))).length > 0);
    const next = await driver.findElement(By.css('[data-login-form] input[name="next"]')).getAttribute('value');
    assert.match(next, /route=admin_albums/);
    assert.match(next, /focus=album-wizard/);
  });
});
