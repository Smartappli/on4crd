(function () {
  if (!window.ON4CRD || typeof window.ON4CRD.bindModalDialogs !== 'function') {
    return;
  }

  window.ON4CRD.bindModalDialogs({
    openAttribute: 'data-webotheque-modal-open',
    closeSelector: '[data-webotheque-modal-close]',
    dialogSelector: '.webotheque-proposal-dialog',
    closeParentDetails: true,
    autoOpenAttribute: 'data-webotheque-auto-open'
  });
})();
