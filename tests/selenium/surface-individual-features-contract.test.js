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

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function extractFunctionSource(relativePath, functionName) {
  const body = source(relativePath);
  const marker = `function ${functionName}(`;
  const start = body.indexOf(marker);
  assert.notEqual(start, -1, `${functionName} doit rester present dans ${relativePath}.`);

  const braceStart = body.indexOf('{', start);
  assert.notEqual(braceStart, -1, `${functionName} doit avoir un corps lisible.`);

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

function dispatchPageRouteMap() {
  const router = source('index.php');
  const routes = new Map();
  const pattern = /case '([^']+)':(?:(?!case ').)*?\$dispatchPage\('([^']+)'\);/gs;
  let match = pattern.exec(router);
  while (match !== null) {
    routes.set(match[1], match[2]);
    match = pattern.exec(router);
  }

  return routes;
}

function dispatchNonAdminRoutes() {
  const router = source('index.php');
  const switchRoutes = Array.from(router.matchAll(/case '([^']+)'\s*:/g), (match) => match[1]);
  const earlyActionRoutes = Array.from(router.matchAll(/\$route === '([^']+)'/g), (match) => match[1]);

  return sorted([...switchRoutes, ...earlyActionRoutes].filter((route) => route !== 'admin' && !route.startsWith('admin_')));
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

const dispatchPages = dispatchPageRouteMap();

function routeSource(route) {
  if (route === 'webotheque') {
    return extractFunctionSource('app/member_webotheque.php', 'render_webotheque_page');
  }
  if (['presentations', 'videos', 'pv', 'fichiers'].includes(route)) {
    return extractFunctionSource('app/member_module_documents.php', 'render_member_document_module_page');
  }

  const page = dispatchPages.get(route);
  return page ? source(page) : null;
}

function proof(file, snippets) {
  return { file, snippets };
}

function actionProofs(file, actions, overrides = {}) {
  return Object.fromEntries(actions.map((action) => [action, proof(file, overrides[action] || [action])]));
}

const publicRoutesFile = 'tests/selenium/public-routes.test.js';
const publicCoverageFile = 'tests/selenium/member-public-coverage.test.js';
const memberWorkflowFile = 'tests/selenium/member-workflows.test.js';
const memberDocumentFile = 'tests/selenium/member-document-modules.test.js';
const memberAccountFile = 'tests/selenium/member-account-dashboard-workflow.test.js';
const memberPrivacyFile = 'tests/selenium/member-privacy-notifications.test.js';
const memberQslFile = 'tests/selenium/member-qsl-workflow.test.js';

const publicPageProof = (route) => proof(publicRoutesFile, [route]);
const discoveryProof = (route) => proof(publicRoutesFile, [route]);
const detailProof = (route) => proof('tests/selenium/public-detail-not-found.test.js', [route]);
const protectedProof = (route) => proof('tests/selenium/protected-routes.test.js', [route]);
const authenticatedProof = (route) => proof('tests/selenium/authenticated-routes.test.js', [route]);
const methodGuardProof = (route) => proof('tests/selenium/method-guards.test.js', [route]);

function memberDocumentActionProofs(route) {
  return {
    delete_document: proof(memberDocumentFile, [route, 'delete_document']),
    propose_category: proof(publicCoverageFile, ['presentations', 'propose_category']),
    propose_document: proof(memberDocumentFile, [route, 'propose_document']),
    propose_subcategory: proof(publicCoverageFile, ['presentations', 'propose_subcategory']),
    toggle_favorite_document: proof(memberDocumentFile, [route, 'toggle_favorite_document']),
    update_document: proof(memberDocumentFile, [route, 'update_document']),
  };
}

const routeContracts = {};

for (const route of [
  'home',
  'login',
  'register',
  'forgot_password',
  'reset_password',
  'membership',
  'donation',
  'conditions_utilisation',
  'mentions_legales',
  'reglement_interieur',
  'sponsoring',
  'search',
  'directory',
  'committee',
  'press',
  'schools',
  'relais',
  'code_q',
  'code_cw',
  'bandplan_on3',
  'bandplan_on2',
  'bandplan_harec',
  'errors',
]) {
  routeContracts[route] = { actions: {}, features: [publicPageProof(route)] };
}

for (const route of ['sitemap.xml', 'robots.txt', 'llms.txt', 'ai-index.json', 'knowledge-graph.jsonld', 'events_feed']) {
  routeContracts[route] = { actions: {}, features: [discoveryProof(route)] };
}

for (const route of ['news_view', 'event_view', 'auction_view']) {
  routeContracts[route] = { actions: {}, features: [detailProof(route), proof('tests/selenium/list-detail-regression.test.js', [route])] };
}

Object.assign(routeContracts, {
  ad_click: {
    actions: {},
    features: [detailProof('ad_click'), proof('tests/selenium/member-ads-workflow.test.js', ['ad_click'])],
  },
  album: {
    actions: actionProofs(publicCoverageFile, ['toggle_favorite', 'upload_album_photos']),
  },
  albums: {
    actions: {
      delete_album: proof(memberWorkflowFile, ['delete_album']),
      propose_album: proof(publicCoverageFile, ['propose_album']),
      propose_category: proof(publicCoverageFile, ['Album category', 'propose_category']),
      propose_subcategory: proof(publicCoverageFile, ['Album subcategory', 'propose_subcategory']),
      toggle_favorite_album: proof(publicCoverageFile, ['toggle_favorite_album']),
      update_album: proof(memberWorkflowFile, ['update_album']),
    },
  },
  article: {
    actions: actionProofs(publicCoverageFile, ['toggle_favorite']),
  },
  articles: {
    actions: actionProofs(publicCoverageFile, ['propose_category', 'propose_tag', 'toggle_favorite_article']),
  },
  auctions: {
    actions: actionProofs(publicCoverageFile, ['propose_lot']),
  },
  chatbot: {
    actions: actionProofs(publicCoverageFile, ['ask', 'clear']),
  },
  classifieds: {
    actions: actionProofs(publicCoverageFile, ['propose_category', 'toggle_favorite']),
  },
  events: {
    actions: actionProofs(publicCoverageFile, ['propose_event']),
  },
  footer_contact: {
    actions: {},
    features: [proof('tests/selenium/public-layout-contract.test.js', ['footer_contact', 'contact_captcha']), methodGuardProof('footer_contact')],
  },
  gdpr: {
    actions: {
      export_data: proof(publicCoverageFile, ['export_data']),
      privacy_request: proof(memberPrivacyFile, ['privacy_request']),
      save_visibility: proof(publicCoverageFile, ['save_visibility']),
    },
  },
  'install.php': {
    actions: {},
    features: [proof('tests/selenium/route-inventory-contract.test.js', ['install.php'])],
  },
  idea_submit: {
    actions: {},
    features: [methodGuardProof('idea_submit')],
  },
  logout: {
    actions: {},
    features: [methodGuardProof('logout')],
  },
  news: {
    actions: actionProofs(publicCoverageFile, ['propose_category', 'propose_news']),
  },
  newsletter_public: {
    actions: {},
    features: [proof('tests/selenium/public-forms.test.js', ['newsletter_public', 'unsubscribe_token'])],
  },
  newsletter_unsubscribe: {
    actions: {},
    features: [proof('tests/selenium/public-forms.test.js', ['newsletter_unsubscribe', 'unsubscribed'])],
  },
  set_accent: {
    actions: {},
    features: [proof('tests/selenium/preference-actions.test.js', ['set_accent'])],
  },
  set_language: {
    actions: {},
    features: [proof('tests/selenium/preference-actions.test.js', ['set_language'])],
  },
  set_theme: {
    actions: {},
    features: [proof('tests/selenium/preference-actions.test.js', ['set_theme'])],
  },
  toggle_theme: {
    actions: {},
    features: [proof('tests/selenium/preference-actions.test.js', ['toggle_theme'])],
  },
  tools: {
    actions: actionProofs(publicCoverageFile, ['delete_tool_preset', 'save_tool_preset']),
  },
  tools_geocode: {
    actions: {},
    features: [proof(publicRoutesFile, ['tools_geocode'])],
  },
  wiki: {
    actions: actionProofs(publicCoverageFile, ['propose_theme', 'toggle_favorite_page']),
  },
  wiki_view: {
    actions: actionProofs(publicCoverageFile, ['delete_page', 'toggle_favorite', 'update_page']),
  },
});

Object.assign(routeContracts, {
  ads: {
    actions: actionProofs('tests/selenium/member-ads-workflow.test.js', ['change_status', 'save_ad']),
  },
  article_propose: {
    actions: {},
    features: [proof(memberWorkflowFile, ['article_propose'])],
  },
  auction_bid: {
    actions: {},
    features: [proof('tests/selenium/admin-auctions-workflow.test.js', ['auction_bid'])],
  },
  change_password: {
    actions: {},
    features: [proof(memberAccountFile, ['change_password'])],
  },
  classifieds_manage: {
    actions: {
      renew: proof(memberWorkflowFile, ['button[name="action"][value="renew"]']),
      save: proof(memberWorkflowFile, ['classifieds-editor-form']),
      set_status: proof(memberWorkflowFile, ['classifieds-status-form', 'button[name="status"][value="sold"]']),
    },
  },
  dashboard: {
    actions: actionProofs(memberPrivacyFile, ['mark_notifications_read']),
  },
  dashboard_widget_card: {
    actions: {},
    features: [protectedProof('dashboard_widget_card')],
  },
  fichiers: {
    actions: memberDocumentActionProofs('fichiers'),
  },
  member_document_preview: {
    actions: {},
    features: [proof(memberDocumentFile, ['member_document_preview'])],
  },
  member_library_preview: {
    actions: {},
    features: [proof(memberWorkflowFile, ['member_library_preview'])],
  },
  members_library: {
    actions: actionProofs(memberWorkflowFile, [
      'delete_document',
      'propose_category',
      'propose_document',
      'propose_subcategory',
      'propose_tag',
      'toggle_favorite_document',
      'update_document',
    ]),
  },
  my_requests: {
    actions: {},
    features: [proof(memberWorkflowFile, ['my_requests']), proof(memberPrivacyFile, ['my_requests'])],
  },
  newsletter: {
    actions: actionProofs(memberAccountFile, ['subscribe', 'unsubscribe'], {
      subscribe: ['input[name="action"][value="subscribe"]'],
      unsubscribe: ['input[name="action"][value="unsubscribe"]'],
    }),
  },
  notifications: {
    actions: {
      mark_all_read: proof(publicCoverageFile, ['mark_all_read']),
      mark_read: proof(memberPrivacyFile, ['mark_read']),
    },
  },
  presentations: {
    actions: memberDocumentActionProofs('presentations'),
  },
  profile: {
    actions: {},
    features: [proof(memberAccountFile, ['profile', 'first_name'])],
  },
  pv: {
    actions: memberDocumentActionProofs('pv'),
  },
  qsl: {
    actions: {
      create_manual: proof(memberQslFile, ['create_manual']),
      delete_background: proof(memberQslFile, ['delete_background']),
      delete_qsl: proof(memberQslFile, ['delete_qsl']),
      delete_qso: proof(memberQslFile, ['delete_qso_id', 'La suppression QSO']),
      generate_batch: proof(memberQslFile, ['generate_batch']),
      import_adif: proof(memberQslFile, ['#adif-dropzone-form', 'adif_files[]']),
      save_background_gradient: proof(memberQslFile, ['data-preview-form="gradient"']),
      save_background_image: proof(memberQslFile, ['data-preview-form="image"']),
      save_background_palette: proof(memberQslFile, ['data-preview-form="palette"']),
      save_background_solid: proof(memberQslFile, ['data-preview-form="solid"']),
      set_default_background: proof(memberQslFile, ['set_default_background']),
    },
  },
  qsl_export: {
    actions: {},
    features: [proof(memberQslFile, ['qsl_export'])],
  },
  qsl_preview: {
    actions: {},
    features: [proof(memberQslFile, ['qsl_preview'])],
  },
  save_dashboard: {
    actions: {},
    features: [proof(memberAccountFile, ['dashboard_widgets'])],
  },
  settings: {
    actions: {
      toggle_newsletter: proof(publicCoverageFile, ['toggle_newsletter']),
      toggle_recommendation_signals: proof(memberAccountFile, ['toggle_recommendation_signals']),
      toggle_recommendations: proof(memberAccountFile, ['toggle_recommendations']),
    },
  },
  telechargements: {
    actions: {},
    features: [proof(memberDocumentFile, ['telechargements'])],
  },
  videos: {
    actions: memberDocumentActionProofs('videos'),
  },
  webotheque: {
    actions: {
      delete_link: proof(memberWorkflowFile, ['delete_link']),
      propose_category: proof(publicCoverageFile, ['propose_category', 'Web category alias']),
      propose_domain: proof(publicCoverageFile, ['propose_domain']),
      propose_link: proof(publicCoverageFile, ['propose_link']),
      propose_subcategory: proof(publicCoverageFile, ['propose_subcategory', 'Web subcategory']),
      propose_tag: proof(publicCoverageFile, ['propose_tag']),
      toggle_favorite_link: proof(publicCoverageFile, ['toggle_favorite_link']),
      update_link: proof(memberWorkflowFile, ['update_link']),
    },
  },
  widget_render: {
    actions: {},
    features: [proof(memberAccountFile, ['widget_render'])],
  },
  wiki_edit: {
    actions: {},
    features: [protectedProof('wiki_edit')],
  },
  wiki_propose: {
    actions: {},
    features: [proof(memberWorkflowFile, ['wiki_propose'])],
  },
});

function assertProof(route, action, item) {
  const body = source(item.file);
  for (const snippet of item.snippets) {
    assert.match(
      body,
      new RegExp(escapeRegExp(snippet)),
      `${route}${action ? `:${action}` : ''}: preuve Selenium manquante pour ${snippet} dans ${item.file}.`,
    );
  }
}

test('Selenium surfaces individuelles: chaque route publique et membre a un contrat fonctionnel', () => {
  assert.deepEqual(
    sorted(Object.keys(routeContracts)),
    dispatchNonAdminRoutes(),
    'Chaque route non-admin exposee par index.php doit avoir un contrat individuel.',
  );
});

for (const route of sorted(Object.keys(routeContracts))) {
  test(`Selenium surface individuelle: ${route} couvre toutes ses fonctionnalites`, () => {
    const contract = routeContracts[route];
    const controllerSource = routeSource(route);
    const actions = Object.keys(contract.actions || {});
    const features = contract.features || [];

    if (controllerSource !== null) {
      assert.deepEqual(
        sorted(actions),
        extractActionValues(controllerSource),
        `${route}: les actions POST explicites doivent etre couvertes individuellement.`,
      );
    } else {
      assert.deepEqual(sorted(actions), [], `${route}: route sans source dispatch ne doit pas declarer d'action POST explicite.`);
    }

    if (actions.length === 0) {
      assert.ok(features.length > 0, `${route}: route sans action explicite mais sans preuve Selenium fonctionnelle.`);
    }
    for (const [action, item] of Object.entries(contract.actions || {})) {
      assertProof(route, action, item);
    }
    for (const item of features) {
      assertProof(route, '', item);
    }
  });
}
