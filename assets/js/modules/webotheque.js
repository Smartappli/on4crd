(function () {
  const openButtons = document.querySelectorAll('[data-webotheque-modal-open]');
  const supportsDialog = typeof HTMLDialogElement !== 'undefined';

  if (openButtons.length === 0 || !supportsDialog) {
    return;
  }

  const closeDialog = (dialog) => {
    if (dialog instanceof HTMLDialogElement && dialog.open) {
      dialog.close();
    }
  };

  const openDialog = (dialog) => {
    if (!(dialog instanceof HTMLDialogElement)) {
      return;
    }
    if (dialog.open) {
      dialog.close();
    }
    dialog.showModal();
    const firstField = dialog.querySelector('input, textarea, select');
    if (firstField instanceof HTMLElement) {
      firstField.focus();
    }
  };

  openButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      const dialogId = button.getAttribute('data-webotheque-modal-open') || '';
      const dialog = document.getElementById(dialogId);
      if (!(dialog instanceof HTMLDialogElement)) {
        return;
      }

      event.preventDefault();
      openDialog(dialog);
    });
  });

  document.querySelectorAll('.webotheque-proposal-dialog').forEach((dialog) => {
    dialog.querySelectorAll('[data-webotheque-modal-close]').forEach((button) => {
      button.addEventListener('click', () => closeDialog(dialog));
    });

    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        closeDialog(dialog);
      }
    });

    if (dialog.hasAttribute('data-webotheque-auto-open')) {
      openDialog(dialog);
    }
  });
})();
