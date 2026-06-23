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
      label: 'classifieds bulk moderation',
      file: 'tests/selenium/admin-maintenance-coverage.test.js',
      snippets: ['bulk_update', 'admin_classifieds'],
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
      label: 'permission assignments',
      file: 'tests/selenium/admin-configuration-workflows.test.js',
      snippets: ['assign_role', 'remove_role'],
    },
    {
      label: 'member account forms',
      file: 'tests/selenium/admin-module-contract.test.js',
      snippets: ["admin_members: ['update_member', 'create_member']"],
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
      label: 'wiki taxonomy and status',
      file: 'tests/selenium/admin-articles-wiki-workflow.test.js',
      snippets: ['admin_wiki', 'update_page_status', 'delete_subcategory', 'delete_category'],
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
