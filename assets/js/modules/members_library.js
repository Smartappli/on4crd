(function () {
  const openButtons = document.querySelectorAll('[data-members-library-modal-open]');
  if (openButtons.length === 0) {
    return;
  }

  const supportsDialog = typeof HTMLDialogElement !== 'undefined';

  const closeDialog = (dialog) => {
    if (dialog && dialog.open) {
      dialog.close();
    }
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

  openButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      const dialogId = button.dataset.membersLibraryModalOpen || '';
      const dialog = document.getElementById(dialogId);
      if (!supportsDialog || !(dialog instanceof HTMLDialogElement)) {
        return;
      }

      event.preventDefault();
      if (!dialog.open) {
        dialog.showModal();
      }
      const menu = button.closest('details');
      if (menu) {
        menu.removeAttribute('open');
      }
      const firstField = dialog.querySelector('input, textarea, select');
      if (firstField instanceof HTMLElement) {
        firstField.focus();
      }
    });
  });

  document.querySelectorAll('.members-library-dialog').forEach((dialog) => {
    const closeButtons = dialog.querySelectorAll('[data-members-library-modal-close]');
    closeButtons.forEach((button) => {
      button.addEventListener('click', () => closeDialog(dialog));
    });

    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        closeDialog(dialog);
      }
    });

    const form = dialog.querySelector('[data-members-library-proposal-form]');
    if (!form) {
      return;
    }
    if (String(form.getAttribute('method') || '').toLowerCase() !== 'dialog') {
      return;
    }

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const recipient = form.dataset.membersLibraryRecipient || 'crdurnal@gmail.com';
      const subject = form.dataset.membersLibrarySubject || '';
      const intro = form.dataset.membersLibraryIntro || '';
      const fields = Array.from(form.querySelectorAll('[name]')).map((field) => field.getAttribute('name')).filter(Boolean);
      const body = [
        intro,
        ...fields.map((name) => `${fieldLabel(form, name)}: ${fieldValue(form, name)}`)
      ].filter(Boolean).join('\n');

      window.location.href = `mailto:${recipient}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
      closeDialog(dialog);
    });
  });
})();
