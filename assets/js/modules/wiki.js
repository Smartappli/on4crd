(function () {
  if (!window.ON4CRD || typeof window.ON4CRD.bindModalDialogs !== 'function') {
    return;
  }

  window.ON4CRD.bindModalDialogs({
    openAttribute: 'data-wiki-page-modal-open',
    closeSelector: '[data-wiki-page-modal-close]',
    dialogSelector: '.wiki-page-dialog',
    closeParentDetails: true
  });

  const dialog = document.getElementById('wiki-theme-dialog');
  const openButtons = document.querySelectorAll('[data-wiki-theme-open]');
  if (typeof HTMLDialogElement === 'undefined' || !(dialog instanceof HTMLDialogElement) || openButtons.length === 0) {
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
      window.ON4CRD.openDialog(dialog);
    });
  });

  dialog.querySelectorAll('[data-wiki-theme-close]').forEach((button) => {
    button.addEventListener('click', () => window.ON4CRD.closeDialog(dialog));
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      window.ON4CRD.closeDialog(dialog);
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
    window.ON4CRD.closeDialog(dialog);
  });
})();
