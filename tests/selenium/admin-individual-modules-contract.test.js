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
  const patterns = [
    /\$action\s*(?:={2,3}|!==?)\s*'([^']+)'/g,
    /\$action\s*=\s*\(string\)\s*\(\$_POST\['action'\]\s*\?\?\s*'([^']*)'\)/g,
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

function proof(file, snippets) {
  return { file, snippets };
}

const adminTaxonomyProof = (route) => proof(
  'tests/selenium/admin-module-crud-workflows.test.js',
  [route, 'createUpdateDeleteAdminTaxonomy'],
);

const adminModuleDocumentProof = (route) => proof(
  'tests/selenium/admin-module-crud-workflows.test.js',
  [route, 'createModuleDocumentFromAdminRoute'],
);

const pvFilesUploadProof = (route) => proof(
  'tests/selenium/member-document-modules.test.js',
  [route, '#admin-member-document-upload'],
);

const pvFilesDeleteProof = (route) => proof(
  'tests/selenium/member-document-modules.test.js',
  [route, 'delete_document'],
);

const routeContracts = {
  admin: {
    actions: {
      update_content_proposal_status: proof('tests/selenium/admin-proposals-workflow.test.js', ['proposalDashboardForm', 'updateDashboardProposal']),
    },
    features: [proof('tests/selenium/admin-proposals-workflow.test.js', ['Selenium admin propositions', 'accepted', 'rejected', 'reviewed'])],
  },
  admin_ads: {
    actions: {
      add_placement: proof('tests/selenium/member-ads-workflow.test.js', ['admin_ads', 'add_placement']),
      moderate_ad: proof('tests/selenium/member-ads-workflow.test.js', ['admin_ads', 'moderate_ad']),
    },
  },
  admin_albums: {
    actions: {
      add_category: adminTaxonomyProof('admin_albums'),
      add_subcategory: adminTaxonomyProof('admin_albums'),
      create_album: proof('tests/selenium/admin-albums.test.js', ['admin_albums', 'create_album']),
      delete_album: proof('tests/selenium/admin-module-crud-workflows.test.js', ['admin_albums', 'delete_album']),
      delete_category: adminTaxonomyProof('admin_albums'),
      delete_photo: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_albums', 'delete_photo']),
      delete_subcategory: adminTaxonomyProof('admin_albums'),
      finalize_album_creation: proof('tests/selenium/admin-albums.test.js', ['finalize_album_creation']),
      rebuild_thumbnails: proof('tests/selenium/admin-maintenance-coverage.test.js', ['rebuild_thumbnails']),
      reorder_photo: proof('tests/selenium/admin-maintenance-coverage.test.js', ['reorder_photo']),
      update_album: proof('tests/selenium/admin-module-crud-workflows.test.js', ['admin_albums', 'update_album']),
      update_category: adminTaxonomyProof('admin_albums'),
      update_photo: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_albums', 'update_photo']),
      update_subcategory: adminTaxonomyProof('admin_albums'),
      upload_photo: proof('tests/selenium/admin-albums.test.js', ['upload_photo', 'album-wizard']),
    },
  },
  admin_articles: {
    actions: {
      add_category: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_articles', 'add_category']),
      add_subcategory: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_articles', 'add_subcategory']),
      add_subsubcategory: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_articles', 'add_subsubcategory']),
      bulk_update_articles: proof('tests/selenium/admin-maintenance-coverage.test.js', ['bulk_update_articles']),
      delete_article: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['delete_article']),
      delete_old_articles: proof('tests/selenium/admin-maintenance-coverage.test.js', ['delete_old_articles']),
      delete_category: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['delete_category']),
      delete_subcategory: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['delete_subcategory']),
      delete_subsubcategory: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['delete_subsubcategory']),
      import_article_word: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['import_article_word', 'wysiwygImportDocxUrl']),
      preview_article: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['preview_article']),
      restore_revision: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['restore_revision']),
      retry_scheduled_article: proof('tests/selenium/admin-maintenance-coverage.test.js', ['retry_scheduled_article']),
      retry_scheduled_bulk: proof('tests/selenium/admin-maintenance-coverage.test.js', ['retry_scheduled_bulk']),
      save_article: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['save_article']),
      save_category: proof('tests/selenium/admin-maintenance-coverage.test.js', ['save_category']),
      update_category: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_articles', 'update_category']),
      update_proposal_status: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['submitProposalStatus', 'admin_articles']),
      update_subcategory: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_articles', 'update_subcategory']),
      update_subsubcategory: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_articles', 'update_subsubcategory']),
    },
  },
  admin_auctions: {
    actions: {},
    features: [proof('tests/selenium/admin-auctions-workflow.test.js', ['admin_auctions', 'auction_view', 'auction_bid'])],
  },
  admin_classifieds: {
    actions: {
      bulk_update: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_classifieds', 'bulk_update']),
      delete: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_classifieds', 'singleDeleteForm']),
      save: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_classifieds', 'classifiedEditForm']),
    },
  },
  admin_committee: {
    actions: {},
    features: [proof('tests/selenium/admin-committee-workflow.test.js', ['admin_committee', 'committee_role', 'committee_bio', 'committee_move'])],
  },
  admin_dashboard: {
    actions: {},
    features: [proof('tests/selenium/member-account-dashboard-workflow.test.js', ['admin_dashboard', 'dashboard_widget_settings'])],
  },
  admin_dinner_reservations: {
    actions: {},
    features: [proof('tests/selenium/admin-configuration-workflows.test.js', ['admin_dinner_reservations', 'reserved_by', 'quantity-input'])],
  },
  admin_editorial: {
    actions: {},
    features: [proof('tests/selenium/admin-editorial-translation-workflow.test.js', ['admin_editorial', 'editorial_contents'])],
  },
  admin_events: {
    actions: {},
    features: [proof('tests/selenium/admin-content-workflows.test.js', ['admin_events', 'event_view', 'events_feed'])],
  },
  admin_events_feed: {
    actions: {},
    features: [proof('tests/selenium/admin-module-contract.test.js', ["routeUrl('admin_events_feed')"])],
  },
  admin_fichiers: {
    actions: {
      add_category: adminTaxonomyProof('admin_fichiers'),
      add_subcategory: adminTaxonomyProof('admin_fichiers'),
      delete_category: adminTaxonomyProof('admin_fichiers'),
      delete_document: pvFilesDeleteProof('admin_fichiers'),
      delete_subcategory: adminTaxonomyProof('admin_fichiers'),
      update_category: adminTaxonomyProof('admin_fichiers'),
      update_subcategory: adminTaxonomyProof('admin_fichiers'),
      upload: pvFilesUploadProof('admin_fichiers'),
    },
  },
  admin_library: {
    actions: {
      add_category: adminTaxonomyProof('admin_library'),
      add_subcategory: adminTaxonomyProof('admin_library'),
      add_subsubcategory: adminTaxonomyProof('admin_library'),
      bulk_delete_documents: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_library', 'bulk_delete_documents']),
      delete_category: adminTaxonomyProof('admin_library'),
      delete_document: proof('tests/selenium/admin-module-crud-workflows.test.js', ['admin_library', 'delete_document']),
      delete_subcategory: adminTaxonomyProof('admin_library'),
      delete_subsubcategory: adminTaxonomyProof('admin_library'),
      merge_tags: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_library', 'merge_tags']),
      update_category: adminTaxonomyProof('admin_library'),
      update_proposal_status: proof('tests/selenium/admin-proposals-workflow.test.js', ['admin_library', 'updateModuleProposal']),
      update_subcategory: adminTaxonomyProof('admin_library'),
      update_subsubcategory: adminTaxonomyProof('admin_library'),
      upload: proof('tests/selenium/admin-module-crud-workflows.test.js', ['admin_library', 'admin-library-upload-form']),
    },
  },
  admin_live_feeds: {
    actions: {},
    features: [proof('tests/selenium/admin-configuration-workflows.test.js', ['admin_live_feeds', 'feeds[', 'notes'])],
  },
  admin_members: {
    actions: {
      create_member: proof('tests/selenium/admin-configuration-workflows.test.js', ['admin_members', 'create_member']),
      update_member: proof('tests/selenium/admin-configuration-workflows.test.js', ['admin_members', 'memberForm']),
    },
  },
  admin_modules: {
    actions: {},
    features: [proof('tests/selenium/admin-configuration-workflows.test.js', ['admin_modules', 'visibility_', "moduleState('press'"])],
  },
  admin_news: {
    actions: {
      assign_section_manager: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_news', 'assign_section_manager']),
      moderate_post: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_news', 'moderate_post']),
      save_post: proof('tests/selenium/admin-content-workflows.test.js', ['admin_news', 'save_post']),
    },
  },
  admin_newsletters: {
    actions: {
      add_subscriber: proof('tests/selenium/admin-newsletters-workflow.test.js', ['admin_newsletters', 'add_subscriber']),
      create_campaign: proof('tests/selenium/admin-newsletters-workflow.test.js', ['create_campaign']),
      delete_subscriber: proof('tests/selenium/admin-newsletters-workflow.test.js', ['delete_subscriber']),
      import_csv: proof('tests/selenium/admin-newsletters-workflow.test.js', ['import_csv']),
      send_campaign: proof('tests/selenium/admin-newsletters-workflow.test.js', ['send_campaign']),
      set_status: proof('tests/selenium/admin-newsletters-workflow.test.js', ['set_status']),
    },
  },
  admin_permissions: {
    actions: {
      assign_role: proof('tests/selenium/admin-configuration-workflows.test.js', ['admin_permissions', 'assign_role']),
      remove_role: proof('tests/selenium/admin-configuration-workflows.test.js', ['remove_role']),
    },
  },
  admin_presentations: {
    actions: {
      add_category: adminTaxonomyProof('admin_presentations'),
      add_subcategory: adminTaxonomyProof('admin_presentations'),
      delete_category: adminTaxonomyProof('admin_presentations'),
      delete_document: adminModuleDocumentProof('admin_presentations'),
      delete_subcategory: adminTaxonomyProof('admin_presentations'),
      update_category: adminTaxonomyProof('admin_presentations'),
      update_subcategory: adminTaxonomyProof('admin_presentations'),
      upload: adminModuleDocumentProof('admin_presentations'),
    },
  },
  admin_press: {
    actions: {
      contact: proof('tests/selenium/admin-configuration-workflows.test.js', ['admin_press', 'contact']),
      release: proof('tests/selenium/admin-configuration-workflows.test.js', ['admin_press', 'release']),
    },
  },
  admin_privacy: {
    actions: {},
    features: [proof('tests/selenium/member-privacy-notifications.test.js', ['admin_privacy', 'apply_erasure'])],
  },
  admin_pv: {
    actions: {
      add_category: adminTaxonomyProof('admin_pv'),
      add_subcategory: adminTaxonomyProof('admin_pv'),
      delete_category: adminTaxonomyProof('admin_pv'),
      delete_document: pvFilesDeleteProof('admin_pv'),
      delete_subcategory: adminTaxonomyProof('admin_pv'),
      update_category: adminTaxonomyProof('admin_pv'),
      update_subcategory: adminTaxonomyProof('admin_pv'),
      upload: pvFilesUploadProof('admin_pv'),
    },
  },
  admin_telechargements: {
    actions: {},
    features: [proof('tests/selenium/admin-module-contract.test.js', ['admin_telechargements'])],
  },
  admin_translation_reviews: {
    actions: {
      review_article_translation: proof('tests/selenium/admin-editorial-translation-workflow.test.js', ['review_article_translation']),
      review_news_translation: proof('tests/selenium/admin-editorial-translation-workflow.test.js', ['review_news_translation']),
    },
  },
  admin_videos: {
    actions: {
      add_category: adminTaxonomyProof('admin_videos'),
      add_subcategory: adminTaxonomyProof('admin_videos'),
      delete_category: adminTaxonomyProof('admin_videos'),
      delete_document: adminModuleDocumentProof('admin_videos'),
      delete_subcategory: adminTaxonomyProof('admin_videos'),
      update_category: adminTaxonomyProof('admin_videos'),
      update_subcategory: adminTaxonomyProof('admin_videos'),
      upload: adminModuleDocumentProof('admin_videos'),
    },
  },
  admin_webotheque: {
    actions: {
      add_category: adminTaxonomyProof('admin_webotheque'),
      add_link: proof('tests/selenium/admin-module-crud-workflows.test.js', ['admin_webotheque', 'admin-webotheque-link-dialog']),
      add_subcategory: adminTaxonomyProof('admin_webotheque'),
      add_subsubcategory: adminTaxonomyProof('admin_webotheque'),
      delete_category: adminTaxonomyProof('admin_webotheque'),
      delete_link: proof('tests/selenium/admin-module-crud-workflows.test.js', ['admin_webotheque', 'delete_link']),
      delete_subcategory: adminTaxonomyProof('admin_webotheque'),
      delete_subsubcategory: adminTaxonomyProof('admin_webotheque'),
      update_category: adminTaxonomyProof('admin_webotheque'),
      update_link: proof('tests/selenium/admin-module-crud-workflows.test.js', ['admin_webotheque', 'update_link']),
      update_proposal_status: proof('tests/selenium/admin-proposals-workflow.test.js', ['admin_webotheque', 'updateModuleProposal']),
      update_subcategory: adminTaxonomyProof('admin_webotheque'),
      update_subsubcategory: adminTaxonomyProof('admin_webotheque'),
    },
  },
  admin_wiki: {
    actions: {
      add_category: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_wiki', 'add_category']),
      add_subcategory: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_wiki', 'add_subcategory']),
      add_subsubcategory: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_wiki', 'add_subsubcategory']),
      delete_category: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_wiki', 'delete_category']),
      delete_subcategory: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_wiki', 'delete_subcategory']),
      delete_subsubcategory: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_wiki', 'delete_subsubcategory']),
      update_category: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_wiki', 'update_category']),
      update_page_status: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_wiki', 'pageStatusForm']),
      update_proposal_status: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_wiki', 'proposal_status']),
      update_subcategory: proof('tests/selenium/admin-maintenance-coverage.test.js', ['admin_wiki', 'update_subcategory']),
      update_subsubcategory: proof('tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_wiki', 'update_subsubcategory']),
    },
  },
};

function assertProof(route, action, item) {
  const body = source(item.file);
  for (const snippet of item.snippets) {
    assert.match(
      body,
      new RegExp(snippet.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')),
      `${route}${action ? `:${action}` : ''}: preuve Selenium manquante pour ${snippet} dans ${item.file}.`,
    );
  }
}

test('Selenium admin individuel: chaque route admin a un contrat fonctionnel', () => {
  assert.deepEqual(
    sorted(Object.keys(routeContracts)),
    dispatchAdminRoutes(),
    'Chaque route admin dispatch doit avoir un contrat individuel.',
  );
  assert.deepEqual(
    sorted(Object.keys(routeSources)),
    dispatchAdminRoutes(),
    'Chaque route admin dispatch doit avoir une source controlee.',
  );
});

for (const route of sorted(Object.keys(routeContracts))) {
  test(`Selenium admin individuel: ${route} couvre toutes ses fonctionnalites`, () => {
    const contract = routeContracts[route];
    const actualActions = extractActionValues(routeSources[route]());
    const actions = Object.keys(contract.actions || {});
    const features = contract.features || [];

    assert.deepEqual(
      sorted(actions),
      actualActions,
      `${route}: les actions admin doivent etre couvertes individuellement.`,
    );

    if (actions.length === 0) {
      assert.ok(features.length > 0, `${route}: module admin sans action explicite mais sans preuve fonctionnelle.`);
    }
    for (const [action, item] of Object.entries(contract.actions || {})) {
      assertProof(route, action, item);
    }
    for (const item of features) {
      assertProof(route, '', item);
    }
  });
}
