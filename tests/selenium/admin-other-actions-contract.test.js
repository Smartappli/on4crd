const test = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const { assert } = require('./helpers');

function source(relativePath) {
  return fs.readFileSync(path.join(process.cwd(), relativePath), 'utf8');
}

test('Selenium admin: contrats des actions hors propositions', () => {
  const contracts = [
    {
      label: 'articles bulk and scheduled retries',
      file: 'tests/selenium/admin-maintenance-coverage.test.js',
      snippets: ['bulk_update_articles', 'retry_scheduled_article', 'retry_scheduled_bulk'],
    },
    {
      label: 'article editor preview restore and delete',
      file: 'tests/selenium/admin-articles-wiki-workflow.test.js',
      snippets: ['save_article', 'preview_article', 'restore_revision', 'delete_article'],
    },
    {
      label: 'article taxonomy',
      file: 'tests/selenium/admin-articles-wiki-workflow.test.js',
      snippets: ['add_category', 'add_subcategory', 'delete_subcategory', 'delete_category'],
    },
    {
      label: 'news create update and public view',
      file: 'tests/selenium/admin-content-workflows.test.js',
      snippets: ['admin_news', 'save_post', 'news_view'],
    },
    {
      label: 'news moderation and managers',
      file: 'tests/selenium/admin-maintenance-coverage.test.js',
      snippets: ['moderate_post', 'assign_section_manager'],
    },
    {
      label: 'library bulk and tags',
      file: 'tests/selenium/admin-maintenance-coverage.test.js',
      snippets: ['bulk_delete_documents', 'merge_tags'],
    },
    {
      label: 'library upload taxonomy and delete',
      file: 'tests/selenium/admin-module-crud-workflows.test.js',
      snippets: ['admin_library', 'admin-library-upload-form', 'delete_document', 'createLibraryDocumentFromAdminRoute'],
    },
    {
      label: 'classifieds bulk moderation and delete',
      file: 'tests/selenium/admin-maintenance-coverage.test.js',
      snippets: ['bulk_update', 'admin_classifieds', 'classifiedEditForm', 'singleDeleteForm'],
    },
    {
      label: 'advertising placements and moderation',
      file: 'tests/selenium/member-ads-workflow.test.js',
      snippets: ['add_placement', 'moderate_ad'],
    },
    {
      label: 'privacy status handling',
      file: 'tests/selenium/member-privacy-notifications.test.js',
      snippets: ['admin_privacy', 'select[name="status"]', "'resolved'"],
    },
    {
      label: 'translation reviews',
      file: 'tests/selenium/admin-editorial-translation-workflow.test.js',
      snippets: ['review_news_translation', 'review_article_translation'],
    },
    {
      label: 'newsletters lifecycle',
      file: 'tests/selenium/admin-newsletters-workflow.test.js',
      snippets: ['set_status', 'delete_subscriber', 'send_campaign'],
    },
    {
      label: 'newsletters import and campaign',
      file: 'tests/selenium/admin-newsletters-workflow.test.js',
      snippets: ['add_subscriber', 'import_csv', 'create_campaign'],
    },
    {
      label: 'permission assignments',
      file: 'tests/selenium/admin-configuration-workflows.test.js',
      snippets: ['assign_role', 'remove_role'],
    },
    {
      label: 'member account workflows',
      file: 'tests/selenium/admin-configuration-workflows.test.js',
      snippets: ['admin_members', 'create_member', 'memberForm', 'createdMemberForm'],
    },
    {
      label: 'module visibility updates',
      file: 'tests/selenium/admin-configuration-workflows.test.js',
      snippets: ['admin_modules', 'visibility_', "moduleState('press'"],
    },
    {
      label: 'event create update and feeds',
      file: 'tests/selenium/admin-content-workflows.test.js',
      snippets: ['admin_events', 'event_view', 'events_feed', "format: 'ics'"],
    },
    {
      label: 'dashboard widgets',
      file: 'tests/selenium/member-account-dashboard-workflow.test.js',
      snippets: ['admin_dashboard', 'widget_radio_clocks', 'dashboard_widget_settings'],
    },
    {
      label: 'wiki taxonomy and status',
      file: 'tests/selenium/admin-articles-wiki-workflow.test.js',
      snippets: ['admin_wiki', 'add_category', 'add_subcategory', 'pageStatusForm', 'delete_subcategory', 'delete_category'],
    },
    {
      label: 'album lifecycle and taxonomy',
      file: 'tests/selenium/admin-module-crud-workflows.test.js',
      snippets: ['admin_albums', 'createAlbumFromAdminRoute', 'update_album', 'delete_album', 'createUpdateDeleteAdminTaxonomy'],
    },
    {
      label: 'album wizard upload',
      file: 'tests/selenium/admin-albums.test.js',
      snippets: ['create_album', 'upload_photo', 'album-wizard'],
    },
    {
      label: 'album maintenance and photos',
      file: 'tests/selenium/admin-maintenance-coverage.test.js',
      snippets: ['rebuild_thumbnails', 'update_photo', 'delete_photo', 'reorder_photo'],
    },
    {
      label: 'auction lot create update and bid',
      file: 'tests/selenium/admin-auctions-workflow.test.js',
      snippets: ['admin_auctions', 'auction_view', 'auction_bid'],
    },
    {
      label: 'editorial content saves',
      file: 'tests/selenium/admin-editorial-translation-workflow.test.js',
      snippets: ['admin_editorial', 'content[committee_title][fr]', 'editorial_contents'],
    },
    {
      label: 'webotheque links and taxonomy',
      file: 'tests/selenium/admin-module-crud-workflows.test.js',
      snippets: ['admin_webotheque', 'createWebothequeFromAdminRoute', 'update_link', 'delete_link', 'createUpdateDeleteAdminTaxonomy'],
    },
    {
      label: 'presentation and video documents',
      file: 'tests/selenium/admin-module-crud-workflows.test.js',
      snippets: ['admin_presentations', 'admin_videos', 'createModuleDocumentFromAdminRoute', 'deleteMemberModuleDocumentFromAdminRoute', 'createUpdateDeleteAdminTaxonomy'],
    },
    {
      label: 'pv fichiers and telechargements documents',
      file: 'tests/selenium/member-document-modules.test.js',
      snippets: ['admin_pv', 'admin_fichiers', 'telechargements', 'delete_document', '#admin-member-document-upload'],
    },
    {
      label: 'pv and fichiers taxonomy',
      file: 'tests/selenium/admin-module-crud-workflows.test.js',
      snippets: ['admin_pv', 'admin_fichiers', 'createUpdateDeleteAdminTaxonomy'],
    },
    {
      label: 'press contacts and releases',
      file: 'tests/selenium/admin-configuration-workflows.test.js',
      snippets: ['admin_press', 'contact', 'release'],
    },
    {
      label: 'live feed updates',
      file: 'tests/selenium/admin-configuration-workflows.test.js',
      snippets: ['admin_live_feeds', 'feeds[', 'notes'],
    },
    {
      label: 'dinner reservations',
      file: 'tests/selenium/admin-configuration-workflows.test.js',
      snippets: ['admin_dinner_reservations', 'reserved_by', 'quantity-input'],
    },
    {
      label: 'committee membership updates',
      file: 'tests/selenium/admin-committee-workflow.test.js',
      snippets: ['admin_committee', 'committee_role', 'committee_bio'],
    },
  ];

  for (const contract of contracts) {
    const body = source(contract.file);
    for (const snippet of contract.snippets) {
      assert.match(body, new RegExp(snippet.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), `${contract.label}: ${snippet}`);
    }
  }
});
