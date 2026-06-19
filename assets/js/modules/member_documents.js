(function () {
  if (!window.ON4CRD || typeof window.ON4CRD.bindModalDialogs !== 'function') {
    return;
  }

  window.ON4CRD.bindModalDialogs({
    openAttribute: 'data-member-document-modal-open',
    closeSelector: '[data-member-document-modal-close]',
    dialogSelector: '.member-document-dialog'
  });
})();
