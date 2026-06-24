const test = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const { assert } = require('./helpers');

function source(relativePath) {
  return fs.readFileSync(path.join(process.cwd(), relativePath), 'utf8');
}

test('Selenium admin: contrats des actions de propositions', () => {
  const contracts = [
    {
      label: 'dashboard proposal moderation',
      file: 'tests/selenium/admin-proposals-workflow.test.js',
      snippets: ['proposalDashboardForm', 'updateDashboardProposal', "'reviewed'", "'rejected'", "'accepted'"],
    },
    {
      label: 'article proposal moderation',
      file: 'tests/selenium/admin-articles-wiki-workflow.test.js',
      snippets: ['submitProposalStatus', 'admin_articles', "'rejected'"],
    },
    {
      label: 'wiki proposal moderation',
      file: 'tests/selenium/admin-articles-wiki-workflow.test.js',
      snippets: ['submitProposalStatus', 'admin_wiki', "'reviewed'"],
    },
    {
      label: 'library proposal moderation',
      file: 'tests/selenium/admin-proposals-workflow.test.js',
      snippets: ['admin_library', 'updateModuleProposal', 'libraryCategoryByLabel'],
    },
    {
      label: 'webotheque proposal moderation',
      file: 'tests/selenium/admin-proposals-workflow.test.js',
      snippets: ['admin_webotheque', 'updateModuleProposal', 'webothequeLinkByUrl'],
    },
    {
      label: 'event proposal moderation',
      file: 'tests/selenium/admin-proposals-workflow.test.js',
      snippets: ['eventByTitle', "'events'", "'accepted'"],
    },
    {
      label: 'auction proposal moderation',
      file: 'tests/selenium/admin-proposals-workflow.test.js',
      snippets: ['auctionLotByTitle', "'auctions'", "'accepted'"],
    },
    {
      label: 'news proposal moderation',
      file: 'tests/selenium/admin-proposals-workflow.test.js',
      snippets: ['newsPostByTitle', 'newsSectionByName', "'news'", "'accepted'"],
    },
  ];

  for (const contract of contracts) {
    const body = source(contract.file);
    for (const snippet of contract.snippets) {
      assert.match(body, new RegExp(snippet.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), `${contract.label}: ${snippet}`);
    }
  }
});
