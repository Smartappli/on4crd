(function () {
  if (!window.ON4CRD || typeof window.ON4CRD.bindModalDialogs !== 'function' || typeof window.ON4CRD.bindDialogMailtoForms !== 'function') {
    return;
  }

  window.ON4CRD.bindModalDialogs({
    openAttribute: 'data-articles-dialog-open',
    closeSelector: '[data-articles-dialog-close], [data-articles-category-close]',
    dialogSelector: '.articles-category-dialog',
    closeParentDetails: true
  });

  window.ON4CRD.bindDialogMailtoForms({
    dialogSelector: '.articles-category-dialog',
    formSelector: '[data-articles-proposal-form], [data-articles-category-form]',
    recipientDatasetKeys: ['articlesProposalRecipient', 'articlesCategoryRecipient'],
    subjectDatasetKeys: ['articlesProposalSubject', 'articlesCategorySubject'],
    introDatasetKeys: ['articlesProposalIntro', 'articlesCategoryIntro'],
    ignoredFieldNames: ['_csrf', 'action']
  });
})();
