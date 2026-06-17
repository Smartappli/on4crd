const test = require('node:test');
const {
  assert,
  withSelenium,
  visit,
  assertPageHasContent,
  skipIfInstallWizard,
} = require('./helpers');

const locales = [
  'fr',
  'en',
  'de',
  'nl',
  'it',
  'es',
  'pt',
  'bg',
  'hr',
  'cs',
  'da',
  'et',
  'fi',
  'el',
  'hu',
  'ga',
  'lv',
  'lt',
  'mt',
  'pl',
  'ro',
  'sk',
  'sl',
  'sv',
  'ar',
  'hi',
  'ja',
  'zh',
  'bn',
  'ru',
  'id',
];

const localizedRoutes = [
  ['home', {}],
  ['articles', {}],
  ['wiki', {}],
  ['albums', {}],
  ['tools', {}],
  ['search', { q: 'radio', source: 'all' }],
];

test('Selenium i18n: les pages publiques representatives rendent dans toutes les locales', async (t) => {
  await withSelenium(t, async (driver) => {
    for (const locale of locales) {
      for (const [route, query] of localizedRoutes) {
        await visit(driver, route, { ...query, locale });
        if (await skipIfInstallWizard(t, driver)) {
          return;
        }
        await assertPageHasContent(driver, `${route}/${locale}`);
        const htmlLang = await driver.executeScript('return document.documentElement.getAttribute("lang") || "";');
        assert.ok(htmlLang === '' || htmlLang.toLowerCase().startsWith(locale), `Langue HTML inattendue pour ${route}/${locale}: ${htmlLang}`);
      }
    }
  });
});
