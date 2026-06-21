const test = require('node:test');
const {
  By,
  assert,
  routeUrl,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  pagePlainText,
  loginAsAdmin,
  requireAdminCredentials,
  runSeleniumPhp,
} = require('./helpers');

const adminRoutes = [
  'admin',
  'admin_modules',
  'admin_members',
  'admin_permissions',
  'admin_news',
  'admin_articles',
  'admin_committee',
  'admin_press',
  'admin_events',
  'admin_dinner_reservations',
  'admin_auctions',
  'admin_editorial',
  'admin_translation_reviews',
  'admin_dashboard',
  'admin_live_feeds',
  'admin_newsletters',
  'admin_wiki',
  'admin_albums',
  'admin_library',
  'admin_webotheque',
  'admin_presentations',
  'admin_videos',
  'admin_pv',
  'admin_fichiers',
  'admin_telechargements',
  'admin_ads',
  'admin_privacy',
  'admin_classifieds',
];

const expectedRouteActions = {
  admin: ['update_content_proposal_status'],
  admin_ads: ['add_placement'],
  admin_albums: ['add_category', 'add_subcategory', 'create_album', 'upload_photo', 'update_album', 'delete_album', 'rebuild_thumbnails'],
  admin_articles: ['save_article', 'preview_article', 'add_category', 'add_subcategory', 'bulk_update_articles'],
  admin_classifieds: [],
  admin_dashboard: [],
  admin_library: ['add_category', 'add_subcategory', 'merge_tags', 'bulk_delete_documents'],
  admin_members: ['update_member', 'create_member'],
  admin_news: ['save_post', 'assign_section_manager'],
  admin_newsletters: ['add_subscriber', 'import_csv', 'create_campaign'],
  admin_permissions: ['assign_role'],
  admin_press: ['contact', 'release'],
  admin_translation_reviews: ['review_news_translation', 'review_article_translation'],
  admin_webotheque: ['add_category', 'add_subcategory', 'update_link'],
  admin_wiki: ['add_category', 'add_subcategory'],
};

const dashboardExpectedRoutes = [
  'admin_modules',
  'admin_members',
  'admin_permissions',
  'admin_news',
  'admin_articles',
  'admin_committee',
  'admin_press',
  'admin_events',
  'admin_dinner_reservations',
  'admin_auctions',
  'admin_editorial',
  'admin_translation_reviews',
  'admin_dashboard',
  'admin_live_feeds',
  'admin_newsletters',
  'admin_wiki',
  'admin_albums',
  'admin_library',
  'admin_webotheque',
  'admin_presentations',
  'admin_videos',
  'admin_pv',
  'admin_fichiers',
  'admin_ads',
  'admin_privacy',
  'admin_classifieds',
];

const moduleCodesToEnable = [
  'news',
  'articles',
  'committee',
  'press',
  'events',
  'auctions',
  'dashboard',
  'wiki',
  'albums',
  'webotheque',
  'presentations',
  'videos',
  'pv',
  'fichiers',
  'advertising',
  'classifieds',
];

const routesWithoutForms = new Set([
  'admin_editorial',
  'admin_events_feed',
  'admin_telechargements',
]);

function enableAdminModulesForContract() {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
require_once 'app/runtime_schema.php';
seed_modules();
$codes = json_decode((string) getenv('SELENIUM_ADMIN_MODULE_CODES'), true);
if (!is_array($codes)) {
    $codes = [];
}
$states = [];
foreach ($codes as $code) {
    $code = trim((string) $code);
    if ($code === '') {
        continue;
    }
    $stmt = db()->prepare('SELECT id, code, is_enabled, visibility FROM modules WHERE code = ? LIMIT 1');
    $stmt->execute([$code]);
    $row = $stmt->fetch() ?: null;
    if (!is_array($row)) {
        continue;
    }
    $states[] = $row;
    db()->prepare('UPDATE modules SET is_enabled = 1 WHERE id = ?')->execute([(int) $row['id']]);
}
echo json_encode($states, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
`, { SELENIUM_ADMIN_MODULE_CODES: JSON.stringify(moduleCodesToEnable) }).trim();

  return JSON.parse(output || '[]');
}

function restoreAdminModules(states) {
  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$states = json_decode((string) getenv('SELENIUM_ADMIN_MODULE_STATES'), true);
if (!is_array($states)) {
    return;
}
foreach ($states as $state) {
    if (!is_array($state) || (int) ($state['id'] ?? 0) <= 0) {
        continue;
    }
    db()->prepare('UPDATE modules SET is_enabled = ?, visibility = ? WHERE id = ?')
        ->execute([(int) ($state['is_enabled'] ?? 1), (string) ($state['visibility'] ?? 'public'), (int) $state['id']]);
}
`, { SELENIUM_ADMIN_MODULE_STATES: JSON.stringify(states || []) });
}

async function routeFormContract(driver) {
  return driver.executeScript(`
    return Array.from(document.querySelectorAll('form')).map((form) => {
      const method = (form.getAttribute('method') || 'get').toLowerCase();
      return {
        method,
        actionValues: Array.from(form.querySelectorAll('input[name="action"]')).map((input) => input.value || ''),
        hasCsrf: Boolean(form.querySelector('input[name="_csrf"]')),
        hasSubmit: Boolean(form.querySelector('button[type="submit"], button:not([type="button"]), input[type="submit"]')),
        fieldCount: form.querySelectorAll('input:not([type="hidden"]), select, textarea').length,
      };
    });
  `);
}

function assertPostFormsAreProtected(route, forms) {
  const postForms = forms.filter((form) => form.method === 'post');
  for (const [index, form] of postForms.entries()) {
    assert.equal(form.hasCsrf, true, `${route}: le formulaire POST #${index + 1} doit contenir un token CSRF.`);
    assert.equal(form.hasSubmit, true, `${route}: le formulaire POST #${index + 1} doit exposer une commande de soumission.`);
  }

  return postForms;
}

function assertExpectedActions(route, forms) {
  const expectedActions = expectedRouteActions[route];
  if (!Array.isArray(expectedActions)) {
    return;
  }

  const actualActions = new Set(forms.flatMap((form) => form.actionValues).filter((value) => value !== ''));
  for (const action of expectedActions) {
    assert.equal(actualActions.has(action), true, `${route}: l action admin "${action}" doit etre exposee par un formulaire.`);
  }
}

async function assertAdminPageContract(driver, route) {
  await visit(driver, route, route === 'admin_albums' ? { focus: 'album-wizard' } : {});
  const text = await pagePlainText(driver);
  assert.doesNotMatch(text, /Veuillez vous connecter|Please sign in|Module d.authentification indisponible/i, `${route}: la page ne doit pas rester bloquee sur l authentification.`);
  assert.match(text, /\S/, `${route}: la page admin doit rendre du contenu lisible.`);

  const forms = await routeFormContract(driver);
  const postForms = assertPostFormsAreProtected(route, forms);
  assertExpectedActions(route, forms);

  if (!routesWithoutForms.has(route)) {
    assert.ok(postForms.length > 0, `${route}: au moins un formulaire POST admin doit etre disponible.`);
    assert.ok(forms.some((form) => form.fieldCount > 0), `${route}: au moins un formulaire doit exposer des champs administrables.`);
  }
}

async function assertAdminEventsFeed(driver) {
  const body = await driver.executeAsyncScript(`
    const done = arguments[arguments.length - 1];
    fetch(arguments[0], { credentials: 'same-origin' })
      .then(async (response) => ({ status: response.status, body: await response.text() }))
      .catch((error) => ({ status: 0, body: String(error) }))
      .then(done);
  `, routeUrl('admin_events_feed'));

  assert.equal(body.status, 200, 'admin_events_feed doit repondre en HTTP 200 pour un admin.');
  assert.doesNotThrow(() => JSON.parse(body.body), 'admin_events_feed doit produire un JSON valide.');
}

test('Selenium admin modules: contrats profonds des pages et formulaires', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  const moduleStates = enableAdminModulesForContract();
  await withSelenium(t, async (driver) => {
    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);

      await visit(driver, 'admin');
      const dashboardLinks = new Set(await driver.findElements(By.css('a[href*="route=admin"]'))
        .then(async (links) => Promise.all(links.map((link) => link.getAttribute('href'))))
        .then((hrefs) => hrefs.map((href) => {
          try {
            return new URL(href).searchParams.get('route');
          } catch {
            return '';
          }
        }).filter(Boolean)));

      for (const route of dashboardExpectedRoutes) {
        assert.ok(dashboardLinks.has(route), `Le tableau de bord admin doit exposer un lien vers ${route}.`);
      }

      for (const route of adminRoutes) {
        await assertAdminPageContract(driver, route);
      }

      await assertAdminEventsFeed(driver);
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
    } finally {
      restoreAdminModules(moduleStates);
    }
  });
});
