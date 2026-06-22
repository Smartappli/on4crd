const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const { assert } = require('./helpers');

function source(relativePath) {
  return fs.readFileSync(path.join(process.cwd(), relativePath), 'utf8');
}

function sorted(values) {
  return Array.from(new Set(values)).sort();
}

function phpStringArray(phpSource, variableName) {
  const pattern = new RegExp(`\\$${variableName}\\s*=\\s*\\[([\\s\\S]*?)\\];`);
  const match = phpSource.match(pattern);
  assert.ok(match, `Tableau PHP $${variableName} introuvable.`);
  return Array.from(match[1].matchAll(/'([^']+)'/g), (item) => item[1]);
}

function dispatchRoutes(phpSource) {
  return Array.from(
    phpSource.matchAll(/case '([^']+)':(?:(?!case ').)*?\$dispatchPage\('([^']+)'\);/gs),
    (item) => item[1],
  );
}

function jsStringArray(jsSource, constName) {
  const pattern = new RegExp(`const\\s+${constName}\\s*=\\s*\\[([\\s\\S]*?)\\];`);
  const match = jsSource.match(pattern);
  assert.ok(match, `Tableau JS ${constName} introuvable.`);
  return Array.from(match[1].matchAll(/'([^']+)'/g), (item) => item[1]);
}

function jsTupleFirstValues(jsSource, constName) {
  const pattern = new RegExp(`const\\s+${constName}\\s*=\\s*\\[([\\s\\S]*?)\\];`);
  const match = jsSource.match(pattern);
  assert.ok(match, `Matrice JS ${constName} introuvable.`);
  return Array.from(match[1].matchAll(/\[\s*'([^']+)'/g), (item) => item[1]);
}

function roleSurfaces() {
  const indexPhp = source('index.php');
  const routes = dispatchRoutes(indexPhp);
  const publicRoutes = phpStringArray(indexPhp, 'publicRoutes');
  const adminRoutes = routes.filter((route) => route === 'admin' || route.startsWith('admin_'));
  const memberRoutes = routes.filter((route) => !publicRoutes.includes(route) && !adminRoutes.includes(route));

  return {
    publicRoutes,
    publicPageRoutes: routes.filter((route) => publicRoutes.includes(route)),
    memberRoutes,
    adminRoutes,
  };
}

const publicNonPageRoutes = new Set([
  'ad_click',
  'ai-index.json',
  'article',
  'album',
  'auction_view',
  'event_view',
  'events_feed',
  'footer_contact',
  'idea_submit',
  'install.php',
  'knowledge-graph.jsonld',
  'llms.txt',
  'news_view',
  'robots.txt',
  'sitemap.xml',
  'tools_geocode',
  'wiki_view',
]);

const memberEndpointRoutes = new Set([
  'auction_bid',
  'dashboard_widget_card',
  'member_document_preview',
  'member_library_preview',
  'qsl_export',
  'qsl_preview',
  'save_dashboard',
  'widget_render',
  'wiki_edit',
]);

test('Selenium couverture roles: les matrices public, membre et admin suivent le routeur', () => {
  const surfaces = roleSurfaces();
  const protectedRoutes = source('tests/selenium/protected-routes.test.js');
  const authenticatedRoutes = source('tests/selenium/authenticated-routes.test.js');
  const adminContract = source('tests/selenium/admin-module-contract.test.js');
  const publicRoutes = source('tests/selenium/public-routes.test.js');

  const expectedPublicPages = surfaces.publicPageRoutes.filter((route) => !publicNonPageRoutes.has(route));
  assert.deepEqual(
    sorted(jsTupleFirstValues(publicRoutes, 'publicPageRoutes')),
    sorted(expectedPublicPages),
    'Les routes publiques HTML navigables doivent etre toutes presentes dans public-routes.test.js.',
  );

  assert.deepEqual(
    sorted(jsStringArray(protectedRoutes, 'memberRoutes')),
    sorted(surfaces.memberRoutes),
    'Les routes membres protegees doivent suivre index.php.',
  );
  assert.deepEqual(
    sorted(jsStringArray(protectedRoutes, 'adminRoutes')),
    sorted(surfaces.adminRoutes),
    'Les routes admin protegees doivent suivre index.php.',
  );

  const expectedAuthenticatedMemberPages = surfaces.memberRoutes.filter((route) => !memberEndpointRoutes.has(route));
  assert.deepEqual(
    sorted(jsStringArray(authenticatedRoutes, 'authenticatedMemberRoutes')),
    sorted(expectedAuthenticatedMemberPages),
    'Les pages membres navigables apres connexion doivent suivre index.php.',
  );
  assert.deepEqual(
    sorted(jsStringArray(authenticatedRoutes, 'authenticatedAdminRoutes')),
    sorted(surfaces.adminRoutes),
    'Les pages admin authentifiees doivent suivre index.php.',
  );

  assert.deepEqual(
    sorted(jsStringArray(adminContract, 'adminRoutes')),
    sorted(surfaces.adminRoutes.filter((route) => route !== 'admin_events_feed')),
    'Le contrat Selenium admin profond doit couvrir toutes les routes admin HTML.',
  );
  assert.match(adminContract, /routeUrl\('admin_events_feed'\)/, 'Le flux JSON admin_events_feed doit rester couvert separement.');
});
