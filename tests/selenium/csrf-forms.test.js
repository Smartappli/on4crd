const test = require('node:test');
const {
  assert,
  withSelenium,
  visit,
  skipIfInstallWizard,
} = require('./helpers');

const publicFormRoutes = [
  ['home', {}],
  ['login', {}],
  ['register', {}],
  ['forgot_password', {}],
  ['reset_password', {}],
  ['newsletter_public', {}],
  ['articles', {}],
  ['wiki', {}],
  ['news', {}],
  ['albums', {}],
  ['classifieds', {}],
  ['events', {}],
  ['auctions', {}],
  ['tools', {}],
  ['donation', {}],
  ['membership', {}],
  ['search', { q: 'radio', source: 'all' }],
];

for (const [route, query] of publicFormRoutes) {
  test(`Selenium CSRF public: tous les formulaires POST de ${route} sont proteges`, async (t) => {
    await withSelenium(t, async (driver) => {
      await visit(driver, route, query);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }

      const postForms = await driver.executeScript(`
        return Array.from(document.forms)
          .filter((form) => ((form.getAttribute('method') || 'get').toLowerCase() === 'post'))
          .map((form, index) => ({
            index,
            action: form.getAttribute('action') || '',
            hasCsrf: !!form.querySelector('input[name="_csrf"]'),
            signature: (form.outerHTML || '').replace(/\\s+/g, ' ').slice(0, 260),
          }));
      `);

      const missing = postForms.filter((form) => !form.hasCsrf);
      assert.deepEqual(missing, [], `Formulaire POST sans CSRF sur ${route}: ${JSON.stringify(missing)}`);
    });
  });
}
