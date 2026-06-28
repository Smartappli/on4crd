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
  assert.notEqual(braceStart, -1, `${functionName} doit avoir un corps lisible.`);

  let depth = 0;
  for (let index = braceStart; index < body.length; index += 1) {
    const char = body[index];
    if (char === '{') {
      depth += 1;
    } else if (char === '}') {
      depth -= 1;
      if (depth === 0) {
        return body.slice(start, index + 1);
      }
    }
  }

  assert.fail(`Impossible d'extraire ${functionName} depuis ${relativePath}.`);
}

function dispatchAdminRoutes() {
  const router = source('index.php');
  const routes = [];
  const pattern = /case '([^']+)':(?:(?!case ').)*?\$dispatchPage\('([^']+)'\);/gs;
  let match = pattern.exec(router);
  while (match !== null) {
    const route = match[1];
    if (route === 'admin' || route.startsWith('admin_')) {
      routes.push(route);
    }
    match = pattern.exec(router);
  }

  return sorted(routes);
}

function extractActionValues(controllerSource) {
  const actions = [];
  const comparisons = /\$action\s*(?:={2,3}|!==?)\s*'([^']+)'/g;
  const defaults = /\$action\s*=\s*\(string\)\s*\(\$_POST\['action'\]\s*\?\?\s*'([^']*)'\)/g;

  let match = comparisons.exec(controllerSource);
  while (match !== null) {
    actions.push(match[1]);
    match = comparisons.exec(controllerSource);
  }

  match = defaults.exec(controllerSource);
  while (match !== null) {
    if (match[1] !== '') {
      actions.push(match[1]);
    }
    match = defaults.exec(controllerSource);
  }

  return sorted(actions);
}

const memberDocumentAdminActions = [
  'add_category',
  'add_subcategory',
  'delete_category',
  'delete_document',
  'delete_subcategory',
  'update_category',
  'update_subcategory',
  'upload',
];

const expectedExplicitActionsByRoute = {
  admin: ['update_content_proposal_status'],
  admin_ads: ['add_placement', 'moderate_ad'],
  admin_albums: [
    'add_category',
    'add_subcategory',
    'create_album',
    'delete_album',
    'delete_category',
    'delete_photo',
    'delete_subcategory',
    'finalize_album_creation',
    'rebuild_thumbnails',
    'reorder_photo',
    'update_album',
    'update_category',
    'update_photo',
    'update_subcategory',
    'upload_photo',
  ],
  admin_articles: [
    'add_category',
    'add_subcategory',
    'add_subsubcategory',
    'bulk_update_articles',
    'delete_article',
    'delete_category',
    'delete_subcategory',
    'delete_subsubcategory',
    'preview_article',
    'restore_revision',
    'retry_scheduled_article',
    'retry_scheduled_bulk',
    'save_article',
    'save_category',
    'update_category',
    'update_proposal_status',
    'update_subcategory',
    'update_subsubcategory',
  ],
  admin_auctions: [],
  admin_classifieds: ['bulk_update', 'delete', 'save'],
  admin_committee: [],
  admin_dashboard: [],
  admin_dinner_reservations: [],
  admin_editorial: [],
  admin_events: [],
  admin_events_feed: [],
  admin_fichiers: memberDocumentAdminActions,
  admin_library: [
    'add_category',
    'add_subcategory',
    'add_subsubcategory',
    'bulk_delete_documents',
    'delete_category',
    'delete_document',
    'delete_subcategory',
    'delete_subsubcategory',
    'merge_tags',
    'update_category',
    'update_proposal_status',
    'update_subcategory',
    'update_subsubcategory',
    'upload',
  ],
  admin_live_feeds: [],
  admin_members: ['create_member', 'update_member'],
  admin_modules: [],
  admin_news: ['assign_section_manager', 'moderate_post', 'save_post'],
  admin_newsletters: ['add_subscriber', 'create_campaign', 'delete_subscriber', 'import_csv', 'send_campaign', 'set_status'],
  admin_permissions: ['assign_role', 'remove_role'],
  admin_presentations: memberDocumentAdminActions,
  admin_press: ['contact', 'release'],
  admin_privacy: [],
  admin_pv: memberDocumentAdminActions,
  admin_telechargements: [],
  admin_translation_reviews: ['review_article_translation', 'review_news_translation'],
  admin_videos: memberDocumentAdminActions,
  admin_webotheque: [
    'add_category',
    'add_link',
    'add_subcategory',
    'add_subsubcategory',
    'delete_category',
    'delete_link',
    'delete_subcategory',
    'delete_subsubcategory',
    'update_category',
    'update_link',
    'update_proposal_status',
    'update_subcategory',
    'update_subsubcategory',
  ],
  admin_wiki: [
    'add_category',
    'add_subcategory',
    'add_subsubcategory',
    'delete_category',
    'delete_subcategory',
    'delete_subsubcategory',
    'update_category',
    'update_page_status',
    'update_proposal_status',
    'update_subcategory',
    'update_subsubcategory',
  ],
};

const routeSources = {
  admin: () => source('pages/admin.php'),
  admin_ads: () => source('pages/admin_ads.php'),
  admin_albums: () => source('pages/admin_albums.php'),
  admin_articles: () => source('pages/admin_articles.php'),
  admin_auctions: () => source('pages/admin_auctions.php'),
  admin_classifieds: () => source('pages/admin_classifieds.php'),
  admin_committee: () => source('pages/admin_committee.php'),
  admin_dashboard: () => source('pages/admin_dashboard.php'),
  admin_dinner_reservations: () => source('pages/admin_dinner_reservations.php'),
  admin_editorial: () => source('pages/admin_editorial.php'),
  admin_events: () => source('pages/admin_events.php'),
  admin_events_feed: () => source('pages/admin_events_feed.php'),
  admin_fichiers: () => extractFunctionSource('app/member_module_documents.php', 'render_admin_member_document_module_page'),
  admin_library: () => source('pages/admin_library.php'),
  admin_live_feeds: () => source('pages/admin_live_feeds.php'),
  admin_members: () => source('pages/admin_members.php'),
  admin_modules: () => source('pages/admin_modules.php'),
  admin_news: () => source('pages/admin_news.php'),
  admin_newsletters: () => source('pages/admin_newsletters.php'),
  admin_permissions: () => source('pages/admin_permissions.php'),
  admin_presentations: () => extractFunctionSource('app/member_module_documents.php', 'render_admin_member_document_module_page'),
  admin_press: () => source('pages/admin_press.php'),
  admin_privacy: () => source('pages/admin_privacy.php'),
  admin_pv: () => extractFunctionSource('app/member_module_documents.php', 'render_admin_member_document_module_page'),
  admin_telechargements: () => source('pages/admin_telechargements.php'),
  admin_translation_reviews: () => source('pages/admin_translation_reviews.php'),
  admin_videos: () => extractFunctionSource('app/member_module_documents.php', 'render_admin_member_document_module_page'),
  admin_webotheque: () => extractFunctionSource('app/member_webotheque.php', 'render_admin_webotheque_page'),
  admin_wiki: () => source('pages/admin_wiki.php'),
};

const implicitPostFeatureCoverage = {
  admin_auctions: {
    controllerSnippets: ['INSERT INTO auction_lots', 'UPDATE auction_lots SET'],
    seleniumFile: 'tests/selenium/admin-auctions-workflow.test.js',
    seleniumSnippets: ['admin_auctions', 'auction_view', 'auction_bid'],
  },
  admin_committee: {
    controllerSnippets: ['committee_move', 'committee_role', 'committee_bio'],
    seleniumFile: 'tests/selenium/admin-committee-workflow.test.js',
    seleniumSnippets: ['committee_move', 'committee_role', 'committee_bio', 'moveDownButton'],
  },
  admin_dashboard: {
    controllerSnippets: ['dashboard_widget_settings', 'widget_'],
    seleniumFile: 'tests/selenium/member-account-dashboard-workflow.test.js',
    seleniumSnippets: ['admin_dashboard', 'widget_radio_clocks', 'dashboard_widget_settings'],
  },
  admin_dinner_reservations: {
    controllerSnippets: ['dinner_reservations', 'dinner_reservation_lines', 'reserved_by'],
    seleniumFile: 'tests/selenium/admin-configuration-workflows.test.js',
    seleniumSnippets: ['admin_dinner_reservations', 'reserved_by', 'quantity-input'],
  },
  admin_editorial: {
    controllerSnippets: ['save_editorial_content(', 'committee.title', 'press.contact'],
    seleniumFile: 'tests/selenium/admin-editorial-translation-workflow.test.js',
    seleniumSnippets: ['admin_editorial', 'content[committee_title][fr]', 'editorial_contents'],
  },
  admin_events: {
    controllerSnippets: ['INSERT INTO events', 'UPDATE events SET'],
    seleniumFile: 'tests/selenium/admin-content-workflows.test.js',
    seleniumSnippets: ['admin_events', 'event_view', 'events_feed'],
  },
  admin_live_feeds: {
    controllerSnippets: ['feeds', 'UPDATE live_feeds SET'],
    seleniumFile: 'tests/selenium/admin-configuration-workflows.test.js',
    seleniumSnippets: ['admin_live_feeds', 'feeds[', 'notes'],
  },
  admin_modules: {
    controllerSnippets: ['UPDATE modules SET is_enabled = ?, visibility = ?'],
    seleniumFile: 'tests/selenium/admin-configuration-workflows.test.js',
    seleniumSnippets: ['admin_modules', 'visibility_', "moduleState('press'"],
  },
  admin_privacy: {
    controllerSnippets: ['privacy_update_request_status(', 'apply_erasure'],
    seleniumFile: 'tests/selenium/member-privacy-notifications.test.js',
    seleniumSnippets: ['admin_privacy', 'select[name="status"]', 'apply_erasure'],
  },
};

test('Selenium admin inventaire: les routes admin dispatch sont toutes listees', () => {
  const routedAdminRoutes = dispatchAdminRoutes();
  const expectedRoutes = sorted(Object.keys(expectedExplicitActionsByRoute));

  assert.deepEqual(expectedRoutes, routedAdminRoutes, 'Chaque route admin doit declarer son inventaire d actions POST.');
  assert.deepEqual(sorted(Object.keys(routeSources)), routedAdminRoutes, 'Chaque route admin doit avoir une source controlee.');
});

test('Selenium admin inventaire: les actions POST explicites restent exhaustives', () => {
  for (const route of dispatchAdminRoutes()) {
    const actual = extractActionValues(routeSources[route]());
    const expected = sorted(expectedExplicitActionsByRoute[route] || []);

    assert.deepEqual(actual, expected, `${route}: les actions POST explicites ont change; ajouter un scenario Selenium ou documenter l'absence d'action.`);
  }
});

test('Selenium admin inventaire: les POST implicites ont une couverture metier Selenium', () => {
  for (const [route, contract] of Object.entries(implicitPostFeatureCoverage)) {
    const controller = routeSources[route]();
    for (const snippet of contract.controllerSnippets) {
      assert.match(controller, new RegExp(snippet.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), `${route}: snippet controleur ${snippet}`);
    }

    const selenium = source(contract.seleniumFile);
    for (const snippet of contract.seleniumSnippets) {
      assert.match(selenium, new RegExp(snippet.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), `${route}: couverture Selenium ${snippet}`);
    }
  }
});
