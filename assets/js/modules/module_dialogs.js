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

  const fieldValue = (form, name) => {
    const field = form.querySelector(`[name="${name}"]`);
    return field && 'value' in field ? String(field.value).trim() : '';
  };

  const fieldLabel = (form, name) => {
    const field = form.querySelector(`[name="${name}"]`);
    const label = field ? field.closest('label') : null;
    const labelText = label ? label.querySelector('span') : null;
    return labelText ? labelText.textContent.trim() : name;
  };

  const datasetValue = (form, keys) => {
    for (const key of keys) {
      const value = String(form.dataset[key] || '').trim();
      if (value !== '') {
        return value;
      }
    }

    return '';
  };

  const bindDialogMailtoForms = (options) => {
    const dialogSelector = String(options.dialogSelector || '');
    const formSelector = String(options.formSelector || '');
    if (!dialogSelector || !formSelector) {
      return;
    }

    const ignoredFieldNames = new Set(options.ignoredFieldNames || []);
    document.querySelectorAll(dialogSelector).forEach((dialog) => {
      const form = dialog.querySelector(formSelector);
      if (!form || String(form.getAttribute('method') || '').toLowerCase() !== 'dialog') {
        return;
      }

      form.addEventListener('submit', (event) => {
        event.preventDefault();
        const recipient = datasetValue(form, options.recipientDatasetKeys || []) || options.fallbackRecipient || 'crdurnal@gmail.com';
        const subject = datasetValue(form, options.subjectDatasetKeys || []);
        const intro = datasetValue(form, options.introDatasetKeys || []);
        const fields = Array.from(form.querySelectorAll('[name]'))
          .map((field) => field.getAttribute('name'))
          .filter((name) => name && !ignoredFieldNames.has(name));
        const body = [
          intro,
          ...fields.map((name) => `${fieldLabel(form, name)}: ${fieldValue(form, name)}`)
        ].filter(Boolean).join('\n');

        window.location.href = `mailto:${recipient}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        closeDialog(dialog);
      });
    });
  };

  window.ON4CRD.closeDialog = closeDialog;
  window.ON4CRD.openDialog = openDialog;
  window.ON4CRD.bindModalDialogs = bindModalDialogs;
  window.ON4CRD.bindDialogMailtoForms = bindDialogMailtoForms;
})();
