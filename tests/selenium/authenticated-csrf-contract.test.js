const test = require('node:test');
const {
  assert,
  withSelenium,
  visit,
  loginAsAdmin,
  requireAdminCredentials,
  isOutsideBaseUrl,
  isLoginPage,
  assertPageHasContent,
  skipIfInstallWizard,
} = require('./helpers');

const authenticatedRoutes = [
  'dashboard',
  'notifications',
  'profile',
  'change_password',
  'my_requests',
  'members_library',
  'webotheque',
  'presentations',
  'videos',
  'pv',
  'fichiers',
  'telechargements',
  'qsl',
  'newsletter',
  'settings',
  'classifieds_manage',
  'article_propose',
  'wiki_edit',
  'wiki_propose',
  'ads',
  'admin',
  'admin_permissions',
  'admin_members',
  'admin_newsletters',
  'admin_privacy',
  'admin_modules',
  'admin_articles',
  'admin_committee',
  'admin_wiki',
  'admin_albums',
  'admin_library',
  'admin_webotheque',
  'admin_presentations',
  'admin_videos',
  'admin_pv',
  'admin_fichiers',
  'admin_telechargements',
  'admin_news',
  'admin_press',
  'admin_editorial',
  'admin_translation_reviews',
  'admin_live_feeds',
  'admin_events',
  'admin_events_feed',
  'admin_dinner_reservations',
  'admin_dashboard',
  'admin_auctions',
  'admin_classifieds',
  'admin_ads',
];

test('Selenium CSRF authentifie: les formulaires POST membres et admin sont proteges', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  await withSelenium(t, async (driver) => {
    await loginAsAdmin(driver, credentials.username, credentials.password);
    if (await isOutsideBaseUrl(driver)) {
      t.skip('app.base_url redirige hors de SELENIUM_BASE_URL; verification authentifiee ignoree.');
      return;
    }

    for (const route of authenticatedRoutes) {
      await visit(driver, route, route === 'admin_albums' ? { focus: 'album-wizard' } : {});
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }
      assert.equal(await isLoginPage(driver), false, `${route} ne doit pas revenir sur login.`);
      await assertPageHasContent(driver, route);

      const postForms = await driver.executeScript(`
        return Array.from(document.forms)
          .filter((form) => ((form.getAttribute('method') || 'get').toLowerCase() === 'post'))
          .map((form, index) => ({
            index,
            action: form.getAttribute('action') || '',
            hasCsrf: !!form.querySelector('input[name="_csrf"]'),
            signature: (form.outerHTML || '').replace(/\\s+/g, ' ').slice(0, 260)
          }));
      `);
      const missing = postForms.filter((form) => !form.hasCsrf);
      assert.deepEqual(missing, [], `Formulaire POST sans CSRF sur ${route}: ${JSON.stringify(missing)}`);
    }
  });
});
