(function () {
  window.ON4CRD = window.ON4CRD || {};

  const closeDialog = (dialog) => {
    if (typeof HTMLDialogElement !== 'undefined' && dialog instanceof HTMLDialogElement && dialog.open) {
      dialog.close();
    }
  };

  const openDialog = (dialog) => {
    if (typeof HTMLDialogElement === 'undefined' || !(dialog instanceof HTMLDialogElement)) {
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

  const bindModalDialogs = (options) => {
    if (typeof HTMLDialogElement === 'undefined') {
      return;
    }

    const openAttribute = String(options.openAttribute || '');
    const closeSelector = String(options.closeSelector || '');
    const dialogSelector = String(options.dialogSelector || '');
    if (!openAttribute || !closeSelector || !dialogSelector) {
      return;
    }

    document.querySelectorAll(`[${openAttribute}]`).forEach((button) => {
      button.addEventListener('click', (event) => {
        const dialogId = button.getAttribute(openAttribute) || '';
        const dialog = document.getElementById(dialogId);
        if (!(dialog instanceof HTMLDialogElement)) {
          return;
        }

        event.preventDefault();
        if (options.closeParentDetails) {
          const menu = button.closest('details');
          if (menu) {
            menu.removeAttribute('open');
          }
        }
        openDialog(dialog);
      });
    });

    document.querySelectorAll(dialogSelector).forEach((dialog) => {
      dialog.querySelectorAll(closeSelector).forEach((button) => {
        button.addEventListener('click', () => closeDialog(dialog));
      });

      dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
          closeDialog(dialog);
        }
      });

      if (options.autoOpenAttribute && dialog.hasAttribute(String(options.autoOpenAttribute))) {
        openDialog(dialog);
      }
    });
  };

  window.ON4CRD.closeDialog = closeDialog;
  window.ON4CRD.openDialog = openDialog;
  window.ON4CRD.bindModalDialogs = bindModalDialogs;
})();
