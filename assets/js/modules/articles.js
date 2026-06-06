(function () {
  const dialog = document.getElementById('articles-category-dialog');
  const openButtons = document.querySelectorAll('[data-articles-category-open]');
  if (!dialog || openButtons.length === 0) {
    return;
  }

  if (typeof HTMLDialogElement === 'undefined' || !(dialog instanceof HTMLDialogElement)) {
    return;
  }

  const closeButtons = dialog.querySelectorAll('[data-articles-category-close]');
  const form = dialog.querySelector('[data-articles-category-form]');
  const firstField = dialog.querySelector('input[name="proposal_category"]');

  const openDialog = (event) => {
    event.preventDefault();
    if (!dialog.open) {
      dialog.showModal();
    }
    if (firstField instanceof HTMLElement) {
      firstField.focus();
    }
  };

  const closeDialog = () => {
    if (dialog.open) {
      dialog.close();
    }
  };

  const fieldValue = (name) => {
    const field = dialog.querySelector(`[name="${name}"]`);
    return field && 'value' in field ? String(field.value).trim() : '';
  };

  const fieldLabel = (name) => {
    const field = dialog.querySelector(`[name="${name}"]`);
    const label = field ? field.closest('label') : null;
    const labelText = label ? label.querySelector('span') : null;
    return labelText ? labelText.textContent.trim() : name;
  };

  openButtons.forEach((button) => {
    button.addEventListener('click', openDialog);
  });

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeDialog);
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      closeDialog();
    }
  });

  if (form && String(form.getAttribute('method') || '').toLowerCase() === 'dialog') {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const recipient = form.dataset.articlesCategoryRecipient || 'crdurnal@gmail.com';
      const subject = form.dataset.articlesCategorySubject || '';
      const intro = form.dataset.articlesCategoryIntro || '';
      const fields = [
        'proposal_category',
        'proposal_reason',
        'proposal_contact'
      ];
      const body = [
        intro,
        ...fields.map((name) => `${fieldLabel(name)}: ${fieldValue(name)}`)
      ].filter(Boolean).join('\n');

      window.location.href = `mailto:${recipient}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
      closeDialog();
    });
  }
})();
