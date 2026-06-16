(function () {
  const supportsDialog = typeof HTMLDialogElement !== 'undefined';
  const openButtons = document.querySelectorAll('[data-member-document-modal-open]');

  if (!supportsDialog || openButtons.length === 0) {
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
      const dialogId = button.getAttribute('data-member-document-modal-open') || '';
      const dialog = document.getElementById(dialogId);
      if (!(dialog instanceof HTMLDialogElement)) {
        return;
      }

      event.preventDefault();
      openDialog(dialog);
    });
  });

  document.querySelectorAll('.member-document-dialog').forEach((dialog) => {
    dialog.querySelectorAll('[data-member-document-modal-close]').forEach((button) => {
      button.addEventListener('click', () => closeDialog(dialog));
    });

    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        closeDialog(dialog);
      }
    });
  });
})();
