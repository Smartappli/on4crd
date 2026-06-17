const test = require('node:test');
const {
  assert,
  withSelenium,
  visit,
  loginAsAdmin,
  requireAdminCredentials,
  assertPageHasContent,
  pagePlainText,
  isLoginPage,
} = require('./helpers');

const authenticatedMemberRoutes = [
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
  'wiki_propose',
  'ads',
];

const authenticatedAdminRoutes = [
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

async function assertAuthenticatedRoute(driver, route) {
  assert.equal(await isLoginPage(driver), false, `${route} ne doit pas rester sur login apres authentification.`);
  await assertPageHasContent(driver, route);
  const text = await pagePlainText(driver);
  assert.doesNotMatch(text, /Veuillez vous connecter|Please sign in/i, `${route} ne doit pas demander une connexion apres login admin.`);
}

test('Selenium authentifie: les pages membres principales restent disponibles', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  await withSelenium(t, async (driver) => {
    await loginAsAdmin(driver, credentials.username, credentials.password);
    for (const route of authenticatedMemberRoutes) {
      await visit(driver, route);
      await assertAuthenticatedRoute(driver, route);
    }
  });
});

test('Selenium authentifie: les pages admin principales restent disponibles', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  await withSelenium(t, async (driver) => {
    await loginAsAdmin(driver, credentials.username, credentials.password);
    for (const route of authenticatedAdminRoutes) {
      await visit(driver, route, route === 'admin_albums' ? { focus: 'album-wizard' } : {});
      await assertAuthenticatedRoute(driver, route);
    }
  });
});
