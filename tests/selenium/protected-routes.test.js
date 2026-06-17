const test = require('node:test');
const {
  By,
  assert,
  withSelenium,
  visit,
  pagePlainText,
  skipIfInstallWizard,
  isLoginPage,
  elementExists,
} = require('./helpers');

const memberRoutes = [
  'dashboard',
  'notifications',
  'save_dashboard',
  'widget_render',
  'dashboard_widget_card',
  'profile',
  'change_password',
  'my_requests',
  'members_library',
  'member_library_preview',
  'webotheque',
  'presentations',
  'videos',
  'pv',
  'fichiers',
  'telechargements',
  'qsl',
  'qsl_preview',
  'qsl_export',
  'newsletter',
  'settings',
  'classifieds_manage',
  'article_propose',
  'wiki_edit',
  'wiki_propose',
  'auction_bid',
  'ads',
];

const adminRoutes = [
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

async function assertProtectedOrControlled(driver, route) {
  if (await isLoginPage(driver)) {
    if (await elementExists(driver, '[data-login-form] input[name="next"]')) {
      const next = await driver.findElement(By.css('[data-login-form] input[name="next"]')).getAttribute('value');
      assert.match(next, new RegExp(`route=${route}`), `Le next login doit conserver ${route}.`);
    }
    return;
  }

  const text = await pagePlainText(driver);
  assert.match(
    text,
    /Module indisponible|Module unavailable|404|introuvable|not found|acc.s refus|forbidden|connecter|sign in/i,
    `${route} doit etre protege ou indisponible de maniere controlee.`,
  );
}

test('Selenium securite: les routes membres non authentifiees sont protegees', async (t) => {
  await withSelenium(t, async (driver) => {
    for (const route of memberRoutes) {
      await visit(driver, route);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }
      await assertProtectedOrControlled(driver, route);
    }
  });
});

test('Selenium securite: les routes admin non authentifiees sont protegees', async (t) => {
  await withSelenium(t, async (driver) => {
    for (const route of adminRoutes) {
      const query = route === 'admin_albums' ? { focus: 'album-wizard' } : {};
      await visit(driver, route, query);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }
      await assertProtectedOrControlled(driver, route);
    }
  });
});
