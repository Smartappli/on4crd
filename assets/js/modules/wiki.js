(function () {
  const supportsDialog = typeof HTMLDialogElement !== 'undefined';

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

  const bindModalFamily = (openSelector, closeSelector, dialogSelector) => {
    if (!supportsDialog) {
      return;
    }

    document.querySelectorAll(openSelector).forEach((openButton) => {
      openButton.addEventListener('click', (event) => {
        const dialogId = openButton.getAttribute(openSelector.slice(1, -1)) || '';
        const dialog = document.getElementById(dialogId);
        if (!(dialog instanceof HTMLDialogElement)) {
          return;
        }

        event.preventDefault();
        const menu = openButton.closest('details');
        if (menu) {
          menu.removeAttribute('open');
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
    });
  };

  bindModalFamily('[data-wiki-page-modal-open]', '[data-wiki-page-modal-close]', '.wiki-page-dialog');

  const dialog = document.getElementById('wiki-theme-dialog');
  const openButtons = document.querySelectorAll('[data-wiki-theme-open]');
  if (!supportsDialog || !(dialog instanceof HTMLDialogElement) || openButtons.length === 0) {
    return;
  }

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

  openButtons.forEach((openButton) => {
    openButton.addEventListener('click', (event) => {
      event.preventDefault();
      const menu = openButton.closest('details');
      if (menu) {
        menu.removeAttribute('open');
      }
      openDialog(dialog);
    });
  });

  dialog.querySelectorAll('[data-wiki-theme-close]').forEach((button) => {
    button.addEventListener('click', () => closeDialog(dialog));
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      closeDialog(dialog);
    }
  });

  const form = dialog.querySelector('[data-wiki-theme-form]');
  if (!form) {
    return;
  }
  if (String(form.getAttribute('method') || '').toLowerCase() !== 'dialog') {
    return;
  }

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const recipient = form.dataset.wikiThemeRecipient || 'crdurnal@gmail.com';
    const subject = form.dataset.wikiThemeSubject || '';
    const intro = form.dataset.wikiThemeIntro || '';
    const fields = ['proposal_theme', 'proposal_reason', 'proposal_contact'];
    const body = [
      intro,
      ...fields.map((name) => `${fieldLabel(form, name)}: ${fieldValue(form, name)}`)
    ].filter(Boolean).join('\n');

    window.location.href = `mailto:${recipient}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    closeDialog(dialog);
  });
})();
