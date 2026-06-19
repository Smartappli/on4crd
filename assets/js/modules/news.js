(function () {
  if (!window.ON4CRD || typeof window.ON4CRD.bindModalDialogs !== 'function' || typeof window.ON4CRD.bindDialogMailtoForms !== 'function') {
    return;
  }

  window.ON4CRD.bindModalDialogs({
    openAttribute: 'data-news-proposal-open',
    closeSelector: '[data-news-proposal-close]',
    dialogSelector: '.news-proposal-dialog'
  });

  window.ON4CRD.bindDialogMailtoForms({
    dialogSelector: '.news-proposal-dialog',
    formSelector: '[data-news-proposal-form]',
    recipientDatasetKeys: ['newsProposalRecipient'],
    subjectDatasetKeys: ['newsProposalSubject'],
    introDatasetKeys: ['newsProposalIntro']
  });
})();
