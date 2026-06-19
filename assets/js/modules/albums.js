(function () {
  if (!window.ON4CRD || typeof window.ON4CRD.bindModalDialogs !== 'function') {
    return;
  }

  window.ON4CRD.bindModalDialogs({
    openAttribute: 'data-album-modal-open',
    closeSelector: '[data-album-modal-close]',
    dialogSelector: '.album-dialog'
  });
})();
