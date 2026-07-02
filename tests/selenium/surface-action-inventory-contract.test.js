const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const { assert } = require('./helpers');

function source(relativePath) {
  return fs.readFileSync(path.join(process.cwd(), relativePath), 'utf8');
}

function sorted(values) {
  return [...new Set(values)].sort((a, b) => a.localeCompare(b));
}

function extractFunctionSource(relativePath, functionName) {
  const body = source(relativePath);
  const marker = `function ${functionName}(`;
  const start = body.indexOf(marker);
  assert.notEqual(start, -1, `${functionName} doit rester present dans ${relativePath}.`);

  const braceStart = body.indexOf('{', start);
  let depth = 0;
  for (let index = braceStart; index < body.length; index += 1) {
    if (body[index] === '{') {
      depth += 1;
    } else if (body[index] === '}') {
      depth -= 1;
      if (depth === 0) {
        return body.slice(start, index + 1);
      }
    }
  }

  assert.fail(`Impossible d'extraire ${functionName} depuis ${relativePath}.`);
}

function dispatchRoutes() {
  const router = source('index.php');
  const routes = [];
  const pattern = /case '([^']+)':(?:(?!case ').)*?\$dispatchPage\('([^']+)'\);/gs;
  let match = pattern.exec(router);
  while (match !== null) {
    routes.push([match[1], match[2]]);
    match = pattern.exec(router);
  }

  return routes;
}

function publicRoutes() {
  const router = source('index.php');
  const match = router.match(/\$publicRoutes = \[(.*?)\];/s);
  assert.ok(match, 'index.php doit exposer $publicRoutes.');

  return new Set([...match[1].matchAll(/'([^']+)'/g)].map((routeMatch) => routeMatch[1]));
}

function extractActionValues(controllerSource) {
  const actions = [];
  const patterns = [
    /\$action\s*(?:={2,3}|!==?)\s*'([^']+)'/g,
    /\$action\s*=\s*\(string\)\s*\(\$_POST\['action'\]\s*\?\?\s*'([^']*)'\)/g,
    /\$_POST\['action'\][^\n;]*?(?:={2,3}|!==?)\s*'([^']+)'/g,
  ];

  for (const pattern of patterns) {
    let match = pattern.exec(controllerSource);
    while (match !== null) {
      if (match[1] !== '') {
        actions.push(match[1]);
      }
      match = pattern.exec(controllerSource);
    }
  }

  return sorted(actions);
}

function routeSource(route, page) {
  if (route === 'webotheque') {
    return extractFunctionSource('app/member_webotheque.php', 'render_webotheque_page');
  }
  if (['presentations', 'videos', 'pv', 'fichiers'].includes(route)) {
    return extractFunctionSource('app/member_module_documents.php', 'render_member_document_module_page');
  }

  return source(page);
}

const moduleDocumentMemberActions = [
  'delete_document',
  'propose_category',
  'propose_document',
  'propose_subcategory',
  'propose_subsubcategory',
  'toggle_favorite_document',
  'update_document',
];

const expectedPublicActions = {
  'ad_click': [],
  'ai-index.json': [],
  album: ['toggle_favorite', 'upload_album_photos'],
  albums: ['delete_album', 'propose_album', 'propose_category', 'propose_subcategory', 'propose_subsubcategory', 'toggle_favorite_album', 'update_album'],
  article: ['toggle_favorite'],
  articles: ['propose_category', 'propose_subcategory', 'propose_subsubcategory', 'propose_tag', 'toggle_favorite_article'],
  auction_view: [],
  auctions: ['propose_lot'],
  bandplan_harec: [],
  bandplan_on2: [],
  bandplan_on3: [],
  chatbot: ['ask', 'clear'],
  classifieds: ['propose_category', 'toggle_favorite'],
  code_cw: [],
  code_q: [],
  committee: [],
  conditions_utilisation: [],
  comics: [],
  directory: [],
  donation: [],
  errors: [],
  event_view: [],
  events: ['propose_event'],
  events_feed: [],
  footer_contact: [],
  forgot_password: [],
  gdpr: ['export_data', 'privacy_request', 'save_visibility'],
  home: [],
  idea_submit: [],
  'knowledge-graph.jsonld': [],
  'llms.txt': [],
  login: [],
  membership: [],
  mentions_legales: [],
  news: ['propose_category', 'propose_news'],
  news_view: [],
  newsletter_public: [],
  newsletter_unsubscribe: [],
  press: [],
  register: [],
  reglement_interieur: [],
  relais: [],
  reset_password: [],
  'robots.txt': [],
  schools: [],
  search: [],
  'sitemap.xml': [],
  sponsoring: [],
  tools: ['delete_tool_preset', 'save_tool_preset'],
  tools_geocode: [],
  wiki: ['propose_theme', 'toggle_favorite_page'],
  wiki_view: ['delete_page', 'toggle_favorite', 'update_page'],
};

const expectedMemberActions = {
  ads: ['change_status', 'save_ad'],
  article_propose: [],
  auction_bid: [],
  change_password: [],
  classifieds_manage: ['renew', 'save', 'set_status'],
  dashboard: ['mark_notifications_read'],
  dashboard_widget_card: [],
  fichiers: moduleDocumentMemberActions,
  member_document_preview: [],
  member_library_preview: [],
  members_library: ['delete_document', 'propose_category', 'propose_document', 'propose_subcategory', 'propose_subsubcategory', 'propose_tag', 'toggle_favorite_document', 'update_document'],
  my_requests: [],
  newsletter: ['subscribe', 'unsubscribe'],
  notifications: ['mark_all_read', 'mark_read'],
  presentations: moduleDocumentMemberActions,
  profile: [],
  pv: moduleDocumentMemberActions,
  qsl: [
    'create_manual',
    'delete_background',
    'delete_qsl',
    'delete_qso',
    'generate_batch',
    'import_adif',
    'save_background_gradient',
    'save_background_image',
    'save_background_palette',
    'save_background_solid',
    'set_default_background',
  ],
  qsl_export: [],
  qsl_preview: [],
  save_dashboard: [],
  settings: ['toggle_newsletter', 'toggle_recommendation_signals', 'toggle_recommendations'],
  telechargements: [],
  videos: moduleDocumentMemberActions,
  webotheque: ['delete_link', 'propose_category', 'propose_domain', 'propose_link', 'propose_subcategory', 'propose_subsubcategory', 'propose_tag', 'toggle_favorite_link', 'update_link'],
  widget_render: [],
  wiki_edit: [],
  wiki_propose: [],
};

const actionCoverage = {
  album: ['tests/selenium/member-public-coverage.test.js', ['toggle_favorite', 'upload_album_photos']],
  albums: ['tests/selenium/member-workflows.test.js', ['propose_album', 'update_album', 'delete_album']],
  article: ['tests/selenium/member-public-coverage.test.js', ['toggle_favorite']],
  articles: ['tests/selenium/member-public-coverage.test.js', ['propose_category', 'propose_subcategory', 'propose_subsubcategory', 'propose_tag', 'toggle_favorite_article']],
  auctions: ['tests/selenium/member-public-coverage.test.js', ['propose_lot']],
  chatbot: ['tests/selenium/member-public-coverage.test.js', ['ask', 'clear']],
  classifieds: ['tests/selenium/member-public-coverage.test.js', ['propose_category', 'toggle_favorite']],
  events: ['tests/selenium/member-public-coverage.test.js', ['propose_event']],
  gdpr: ['tests/selenium/member-public-coverage.test.js', ['export_data', 'save_visibility']],
  news: ['tests/selenium/member-public-coverage.test.js', ['propose_news', 'propose_category']],
  tools: ['tests/selenium/member-public-coverage.test.js', ['save_tool_preset', 'delete_tool_preset']],
  wiki: ['tests/selenium/member-public-coverage.test.js', ['propose_theme', 'toggle_favorite_page']],
  wiki_view: ['tests/selenium/member-public-coverage.test.js', ['update_page', 'delete_page']],

  ads: ['tests/selenium/member-ads-workflow.test.js', ['save_ad', 'change_status']],
  classifieds_manage: ['tests/selenium/member-workflows.test.js', ['classifieds_manage', 'sold']],
  dashboard: ['tests/selenium/member-privacy-notifications.test.js', ['mark_notifications_read']],
  fichiers: ['tests/selenium/member-document-modules.test.js', ['fichiers', 'delete_document', 'member_document_preview']],
  members_library: ['tests/selenium/member-workflows.test.js', ['propose_category', 'propose_subcategory', 'propose_subsubcategory', 'propose_tag', 'propose_document', 'toggle_favorite_document', 'update_document', 'delete_document']],
  newsletter: ['tests/selenium/member-account-dashboard-workflow.test.js', ['newsletter_action"][value="subscribe', 'newsletter_action"][value="unsubscribe']],
  notifications: ['tests/selenium/member-privacy-notifications.test.js', ['mark_read']],
  presentations: ['tests/selenium/member-document-modules.test.js', ['propose_document', 'update_document', 'delete_document', 'toggle_favorite_document']],
  pv: ['tests/selenium/member-document-modules.test.js', ['pv', 'delete_document', 'member_document_preview']],
  qsl: ['tests/selenium/member-qsl-workflow.test.js', ['create_manual', 'delete_background', 'delete_qsl', 'generate_batch', 'set_default_background']],
  settings: ['tests/selenium/member-account-dashboard-workflow.test.js', ['toggle_recommendations', 'toggle_recommendation_signals']],
  videos: ['tests/selenium/member-document-modules.test.js', ['videos', 'propose_document', 'update_document', 'delete_document']],
  webotheque: ['tests/selenium/member-workflows.test.js', ['propose_subsubcategory', 'propose_link', 'update_link', 'delete_link']],
};

const implicitPostCoverage = {
  footer_contact: ['tests/selenium/public-layout-contract.test.js', ['footer_contact', 'contact_captcha']],
  idea_submit: ['tests/selenium/method-guards.test.js', ['idea_submit']],
  newsletter_public: ['tests/selenium/public-forms.test.js', ['newsletter_public', 'unsubscribe_token']],
  newsletter_unsubscribe: ['tests/selenium/public-forms.test.js', ['newsletter_unsubscribe', 'unsubscribed']],
  logout: ['tests/selenium/method-guards.test.js', ['logout']],
  set_accent: ['tests/selenium/preference-actions.test.js', ['set_accent']],
  set_language: ['tests/selenium/preference-actions.test.js', ['set_language']],
  set_theme: ['tests/selenium/preference-actions.test.js', ['set_theme']],
  toggle_theme: ['tests/selenium/preference-actions.test.js', ['toggle_theme']],

  auction_bid: ['tests/selenium/admin-auctions-workflow.test.js', ['auction_bid']],
  change_password: ['tests/selenium/member-account-dashboard-workflow.test.js', ['change_password']],
  dashboard_widget_card: ['tests/selenium/protected-routes.test.js', ['dashboard_widget_card']],
  member_document_preview: ['tests/selenium/member-document-modules.test.js', ['member_document_preview']],
  member_library_preview: ['tests/selenium/member-workflows.test.js', ['member_library_preview']],
  profile: ['tests/selenium/member-account-dashboard-workflow.test.js', ['profile', 'first_name']],
  qsl_export: ['tests/selenium/member-qsl-workflow.test.js', ['qsl_export']],
  qsl_preview: ['tests/selenium/member-qsl-workflow.test.js', ['qsl_preview']],
  save_dashboard: ['tests/selenium/member-account-dashboard-workflow.test.js', ['dashboard_widgets']],
  widget_render: ['tests/selenium/member-account-dashboard-workflow.test.js', ['widget_render']],
};

test('Selenium surfaces: les routes publiques et membres ont un inventaire d actions', () => {
  const publicSet = publicRoutes();
  const expectedPublicRoutes = [];
  const expectedMemberRoutes = [];

  for (const [route] of dispatchRoutes()) {
    if (route === 'admin' || route.startsWith('admin_')) {
      continue;
    }
    if (publicSet.has(route)) {
      expectedPublicRoutes.push(route);
    } else {
      expectedMemberRoutes.push(route);
    }
  }

  assert.deepEqual(sorted(Object.keys(expectedPublicActions)), sorted(expectedPublicRoutes), 'Chaque route publique doit etre inventoriee.');
  assert.deepEqual(sorted(Object.keys(expectedMemberActions)), sorted(expectedMemberRoutes), 'Chaque route membre doit etre inventoriee.');
});

test('Selenium surfaces: les actions POST publiques et membres restent exhaustives', () => {
  const publicSet = publicRoutes();
  const allExpected = { ...expectedPublicActions, ...expectedMemberActions };

  for (const [route, page] of dispatchRoutes()) {
    if (route === 'admin' || route.startsWith('admin_')) {
      continue;
    }

    const actual = extractActionValues(routeSource(route, page));
    const expected = sorted(allExpected[route] || []);
    assert.deepEqual(actual, expected, `${route}: les actions POST explicites ont change; ajouter la couverture Selenium correspondante.`);

    const expectedSurface = publicSet.has(route) ? expectedPublicActions : expectedMemberActions;
    assert.ok(Object.prototype.hasOwnProperty.call(expectedSurface, route), `${route}: route absente de l'inventaire de surface attendu.`);
  }
});

test('Selenium surfaces: chaque groupe d actions a une couverture Selenium declaree', () => {
  for (const [route, actions] of Object.entries({ ...expectedPublicActions, ...expectedMemberActions })) {
    if (actions.length === 0) {
      continue;
    }

    const contract = actionCoverage[route];
    assert.ok(contract, `${route}: actions sans contrat Selenium (${actions.join(', ')}).`);
    const [file, snippets] = contract;
    const body = source(file);
    for (const snippet of snippets) {
      assert.match(body, new RegExp(snippet.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), `${route}: couverture Selenium manquante pour ${snippet}.`);
    }
  }
});

test('Selenium surfaces: les endpoints POST implicites restent relies a des tests', () => {
  for (const [route, [file, snippets]] of Object.entries(implicitPostCoverage)) {
    const body = source(file);
    for (const snippet of snippets) {
      assert.match(body, new RegExp(snippet.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), `${route}: couverture implicite manquante pour ${snippet}.`);
    }
  }
});
