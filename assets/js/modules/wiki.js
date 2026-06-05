(function () {
  const dialog = document.getElementById('wiki-theme-dialog');
  const openButton = document.querySelector('[data-wiki-theme-open]');
  if (!dialog || !openButton) {
    return;
  }

  if (typeof HTMLDialogElement === 'undefined' || !(dialog instanceof HTMLDialogElement)) {
    return;
  }

  const closeDialog = () => {
    if (dialog.open) {
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

  openButton.addEventListener('click', (event) => {
    event.preventDefault();
    if (!dialog.open) {
      dialog.showModal();
    }
    const firstField = dialog.querySelector('input, textarea');
    if (firstField instanceof HTMLElement) {
      firstField.focus();
    }
  });

  dialog.querySelectorAll('[data-wiki-theme-close]').forEach((button) => {
    button.addEventListener('click', closeDialog);
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      closeDialog();
    }
  });

  const form = dialog.querySelector('[data-wiki-theme-form]');
  if (!form) {
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
    closeDialog();
  });
})();
