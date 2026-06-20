(function () {
  if (!window.ON4CRD || typeof window.ON4CRD.bindModalDialogs !== 'function' || typeof window.ON4CRD.bindDialogMailtoForms !== 'function') {
    return;
  }

  window.ON4CRD.bindModalDialogs({
    openAttribute: 'data-members-library-modal-open',
    closeSelector: '[data-members-library-modal-close]',
    dialogSelector: '.members-library-dialog',
    closeParentDetails: true,
    autoOpenAttribute: 'data-members-library-auto-open'
  });

  window.ON4CRD.bindDialogMailtoForms({
    dialogSelector: '.members-library-dialog',
    formSelector: '[data-members-library-proposal-form]',
    recipientDatasetKeys: ['membersLibraryRecipient'],
    subjectDatasetKeys: ['membersLibrarySubject'],
    introDatasetKeys: ['membersLibraryIntro']
  });
})();
