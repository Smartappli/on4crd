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

function seededModules() {
  const runtime = source('app/runtime_schema.php');
  const modules = [];
  const pattern = /\[\s*'([^']+)'\s*,\s*'[^']*'\s*,\s*'[^']*'\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*'([^']+)'\s*,\s*(\d+)\s*\]/g;
  let match = pattern.exec(runtime);
  while (match !== null) {
    modules.push({
      code: match[1],
      isCore: Number(match[2]) === 1,
      enabled: Number(match[3]) === 1,
      visibility: match[4],
      sortOrder: Number(match[5]),
    });
    match = pattern.exec(runtime);
  }

  assert.ok(modules.length > 0, 'seed_modules() doit declarer les modules applicatifs.');
  return modules;
}

function dispatchedRoutes() {
  const indexPhp = source('index.php');
  const switchRoutes = Array.from(indexPhp.matchAll(/case '([^']+)'\s*:/g), (match) => match[1]);
  const earlyActionRoutes = Array.from(indexPhp.matchAll(/\$route === '([^']+)'/g), (match) => match[1]);

  return new Set([...switchRoutes, ...earlyActionRoutes]);
}

function jsStringArray(jsSource, constName) {
  const pattern = new RegExp(`const\\s+${constName}\\s*=\\s*\\[([\\s\\S]*?)\\];`);
  const match = jsSource.match(pattern);
  assert.ok(match, `Tableau JS ${constName} introuvable.`);
  return Array.from(match[1].matchAll(/'([^']+)'/g), (item) => item[1]);
}

const moduleContracts = {
  admin: {
    routes: ['admin', 'admin_modules'],
    proofs: [
      ['tests/selenium/admin-module-contract.test.js', ['adminRoutes', 'admin_modules', 'moduleCodesToEnable']],
      ['tests/selenium/admin-configuration-workflows.test.js', ['admin_modules', "moduleState('press'"]],
    ],
  },
  advertising: {
    routes: ['ads', 'ad_click', 'admin_ads'],
    proofs: [
      ['tests/selenium/member-ads-workflow.test.js', ['admin_ads', 'ads', 'ad_click', 'moderate_ad']],
    ],
  },
  albums: {
    routes: ['albums', 'album', 'admin_albums'],
    proofs: [
      ['tests/selenium/admin-albums.test.js', ['admin_albums', 'create_album', 'upload_photo', 'finalize_album_creation']],
      ['tests/selenium/member-public-coverage.test.js', ['toggle_favorite_album', 'upload_album_photos']],
      ['tests/selenium/member-workflows.test.js', ['propose_album', 'update_album', 'delete_album']],
    ],
  },
  articles: {
    routes: ['articles', 'article', 'article_propose', 'admin_articles'],
    proofs: [
      ['tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_articles', 'save_article', 'preview_article', 'delete_article']],
      ['tests/selenium/member-public-coverage.test.js', ['toggle_favorite_article', 'propose_category', 'propose_tag']],
      ['tests/selenium/member-workflows.test.js', ['article_propose']],
    ],
  },
  auctions: {
    routes: ['auctions', 'auction_view', 'auction_bid', 'admin_auctions'],
    proofs: [
      ['tests/selenium/admin-auctions-workflow.test.js', ['admin_auctions', 'auction_view', 'auction_bid']],
      ['tests/selenium/member-public-coverage.test.js', ['propose_lot']],
    ],
  },
  chatbot: {
    routes: ['chatbot'],
    proofs: [
      ['tests/selenium/member-public-coverage.test.js', ['chatbot', 'ask', 'clear']],
    ],
  },
  classifieds: {
    routes: ['classifieds', 'classifieds_manage', 'admin_classifieds'],
    proofs: [
      ['tests/selenium/member-workflows.test.js', ['classifieds_manage', 'classifieds-status-form', 'admin_classifieds']],
      ['tests/selenium/member-public-coverage.test.js', ['classifieds', 'propose_category', 'toggle_favorite']],
      ['tests/selenium/admin-maintenance-coverage.test.js', ['admin_classifieds', 'bulk_update', 'singleDeleteForm']],
    ],
  },
  committee: {
    routes: ['committee', 'admin_committee', 'admin_editorial'],
    proofs: [
      ['tests/selenium/admin-committee-workflow.test.js', ['admin_committee', 'committee_role', 'committee_bio', 'committee_move']],
      ['tests/selenium/admin-editorial-translation-workflow.test.js', ['admin_editorial', 'committee_title', 'editorial_contents']],
      ['tests/selenium/public-routes.test.js', ['committee']],
    ],
  },
  dashboard: {
    routes: ['dashboard', 'save_dashboard', 'widget_render', 'dashboard_widget_card', 'admin_dashboard'],
    proofs: [
      ['tests/selenium/member-account-dashboard-workflow.test.js', ['dashboard_widgets', 'widget_render', 'admin_dashboard']],
      ['tests/selenium/member-privacy-notifications.test.js', ['mark_notifications_read']],
    ],
  },
  directory: {
    routes: ['directory'],
    proofs: [
      ['tests/selenium/public-routes.test.js', ['directory']],
      ['tests/selenium/public-layout-contract.test.js', ['route=directory']],
    ],
  },
  education: {
    routes: ['schools', 'relais'],
    proofs: [
      ['tests/selenium/public-routes.test.js', ['schools', 'relais']],
      ['tests/selenium/public-layout-contract.test.js', ['schools', 'relais']],
    ],
  },
  events: {
    routes: ['events', 'event_view', 'events_feed', 'admin_events', 'admin_events_feed', 'admin_dinner_reservations'],
    proofs: [
      ['tests/selenium/admin-content-workflows.test.js', ['admin_events', 'event_view', 'events_feed']],
      ['tests/selenium/admin-configuration-workflows.test.js', ['admin_dinner_reservations', 'reserved_by']],
      ['tests/selenium/member-public-coverage.test.js', ['propose_event']],
    ],
  },
  fichiers: {
    routes: ['fichiers', 'telechargements', 'member_document_preview', 'admin_fichiers', 'admin_telechargements'],
    proofs: [
      ['tests/selenium/member-document-modules.test.js', ['fichiers', 'telechargements', 'member_document_preview', 'delete_document']],
      ['tests/selenium/admin-module-crud-workflows.test.js', ['admin_fichiers', 'fichiers']],
    ],
  },
  members: {
    routes: [
      'profile',
      'change_password',
      'notifications',
      'gdpr',
      'my_requests',
      'newsletter',
      'settings',
      'admin_members',
      'admin_permissions',
      'admin_newsletters',
      'admin_privacy',
    ],
    proofs: [
      ['tests/selenium/member-account-dashboard-workflow.test.js', ['profile', 'change_password', 'newsletter_action']],
      ['tests/selenium/member-privacy-notifications.test.js', ['gdpr', 'notifications', 'mark_read']],
      ['tests/selenium/admin-configuration-workflows.test.js', ['admin_members', 'create_member', 'admin_permissions', 'assign_role']],
      ['tests/selenium/admin-newsletters-workflow.test.js', ['admin_newsletters', 'create_campaign', 'send_campaign']],
    ],
  },
  news: {
    routes: ['news', 'news_view', 'newsletter_public', 'newsletter_unsubscribe', 'admin_news', 'admin_newsletters'],
    proofs: [
      ['tests/selenium/admin-content-workflows.test.js', ['admin_news', 'save_post', 'news_view']],
      ['tests/selenium/admin-maintenance-coverage.test.js', ['admin_news', 'assign_section_manager', 'moderate_post']],
      ['tests/selenium/public-forms.test.js', ['newsletter_public', 'newsletter_unsubscribe']],
      ['tests/selenium/member-public-coverage.test.js', ['propose_news', 'propose_category']],
    ],
  },
  presentations: {
    routes: ['presentations', 'member_document_preview', 'admin_presentations'],
    proofs: [
      ['tests/selenium/member-document-modules.test.js', ['presentations', 'toggle_favorite_document', 'update_document', 'delete_document']],
      ['tests/selenium/admin-module-crud-workflows.test.js', ['admin_presentations', 'presentations']],
    ],
  },
  press: {
    routes: ['press', 'admin_press', 'admin_editorial'],
    proofs: [
      ['tests/selenium/admin-configuration-workflows.test.js', ['admin_press', 'contact', 'release']],
      ['tests/selenium/admin-editorial-translation-workflow.test.js', ['admin_editorial', 'press.contact']],
      ['tests/selenium/public-routes.test.js', ['press']],
    ],
  },
  pv: {
    routes: ['pv', 'member_document_preview', 'admin_pv'],
    proofs: [
      ['tests/selenium/member-document-modules.test.js', ['pv', 'member_document_preview', 'delete_document']],
      ['tests/selenium/admin-module-crud-workflows.test.js', ['admin_pv', 'pv']],
    ],
  },
  qsl: {
    routes: ['qsl', 'qsl_preview', 'qsl_export'],
    proofs: [
      ['tests/selenium/member-qsl-workflow.test.js', ['qsl', 'qsl_preview', 'qsl_export', 'create_manual', 'delete_qsl']],
    ],
  },
  tools: {
    routes: ['tools', 'tools_geocode'],
    proofs: [
      ['tests/selenium/tools-interactions.test.js', ['tools', 'tool-freq-wave', 'tool_panel']],
      ['tests/selenium/member-public-coverage.test.js', ['save_tool_preset', 'delete_tool_preset']],
    ],
  },
  videos: {
    routes: ['videos', 'member_document_preview', 'admin_videos'],
    proofs: [
      ['tests/selenium/member-document-modules.test.js', ['videos', 'propose_document', 'update_document', 'delete_document']],
      ['tests/selenium/admin-module-crud-workflows.test.js', ['admin_videos', 'videos']],
    ],
  },
  webotheque: {
    routes: ['webotheque', 'admin_webotheque'],
    proofs: [
      ['tests/selenium/member-workflows.test.js', ['webotheque', 'update_link', 'delete_link']],
      ['tests/selenium/member-public-coverage.test.js', ['propose_domain', 'propose_tag', 'toggle_favorite_link']],
      ['tests/selenium/admin-module-crud-workflows.test.js', ['admin_webotheque', 'admin-webotheque-link-dialog', 'update_link', 'delete_link']],
    ],
  },
  wiki: {
    routes: ['wiki', 'wiki_view', 'wiki_edit', 'wiki_propose', 'admin_wiki'],
    proofs: [
      ['tests/selenium/admin-articles-wiki-workflow.test.js', ['admin_wiki', 'pageStatusForm', 'proposal_status']],
      ['tests/selenium/member-public-coverage.test.js', ['propose_theme', 'toggle_favorite_page', 'update_page', 'delete_page']],
      ['tests/selenium/member-workflows.test.js', ['wiki_propose']],
    ],
  },
};

test('Selenium modules individuels: chaque module runtime a un contrat dedie', () => {
  const runtimeCodes = sorted(seededModules().map((module) => module.code));
  assert.deepEqual(
    sorted(Object.keys(moduleContracts)),
    runtimeCodes,
    'Chaque module seed_modules() doit avoir une entree moduleContracts dediee.',
  );
});

test('Selenium modules individuels: chaque contrat pointe vers des routes dispatch existantes', () => {
  const routes = dispatchedRoutes();
  for (const [moduleCode, contract] of Object.entries(moduleContracts)) {
    assert.ok(Array.isArray(contract.routes) && contract.routes.length > 0, `${moduleCode}: routes manquantes.`);
    for (const route of contract.routes) {
      assert.ok(routes.has(route), `${moduleCode}: route ${route} absente du dispatch index.php.`);
    }
  }
});

test('Selenium modules individuels: chaque module reference des preuves Selenium', () => {
  for (const [moduleCode, contract] of Object.entries(moduleContracts)) {
    assert.ok(Array.isArray(contract.proofs) && contract.proofs.length > 0, `${moduleCode}: preuves Selenium manquantes.`);
    for (const [file, snippets] of contract.proofs) {
      const body = source(file);
      for (const snippet of snippets) {
        assert.match(
          body,
          new RegExp(snippet.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')),
          `${moduleCode}: preuve Selenium ${snippet} absente de ${file}.`,
        );
      }
    }
  }
});

test('Selenium modules individuels: le contrat admin active tous les modules non-core', () => {
  const nonCoreRuntimeCodes = seededModules()
    .filter((module) => !module.isCore)
    .map((module) => module.code);
  const adminContract = source('tests/selenium/admin-module-contract.test.js');

  assert.deepEqual(
    sorted(jsStringArray(adminContract, 'moduleCodesToEnable')),
    sorted(nonCoreRuntimeCodes),
    'moduleCodesToEnable doit contenir tous les modules non-core pour les tests admin.',
  );
});
