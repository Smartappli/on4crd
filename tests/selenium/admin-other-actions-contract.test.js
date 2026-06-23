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
  ];

  for (const contract of contracts) {
    const body = source(contract.file);
    for (const snippet of contract.snippets) {
      assert.match(body, new RegExp(snippet.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), `${contract.label}: ${snippet}`);
    }
  }
});
