(function () {
  const openButtons = document.querySelectorAll('[data-articles-dialog-open], [data-articles-category-open]');
  if (openButtons.length === 0) {
    return;
  }

  if (typeof HTMLDialogElement === 'undefined') {
    return;
  }

  const closeDialog = (dialog) => {
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

  openButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      const dialogId = button.dataset.articlesDialogOpen || 'articles-category-dialog';
      const dialog = document.getElementById(dialogId);
      if (!(dialog instanceof HTMLDialogElement)) {
        return;
      }

      event.preventDefault();
      const menu = button.closest('details');
      if (menu) {
        menu.removeAttribute('open');
      }
      if (!dialog.open) {
        dialog.showModal();
      }
      const firstField = dialog.querySelector('input, textarea, select');
      if (firstField instanceof HTMLElement) {
        firstField.focus();
      }
    });
  });

  document.querySelectorAll('.articles-category-dialog').forEach((dialog) => {
    if (!(dialog instanceof HTMLDialogElement)) {
      return;
    }

    dialog.querySelectorAll('[data-articles-dialog-close], [data-articles-category-close]').forEach((button) => {
      button.addEventListener('click', () => closeDialog(dialog));
    });

    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        closeDialog(dialog);
      }
    });

    const form = dialog.querySelector('[data-articles-proposal-form], [data-articles-category-form]');
    if (!form || String(form.getAttribute('method') || '').toLowerCase() !== 'dialog') {
      return;
    }

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const recipient = form.dataset.articlesProposalRecipient || form.dataset.articlesCategoryRecipient || 'crdurnal@gmail.com';
      const subject = form.dataset.articlesProposalSubject || form.dataset.articlesCategorySubject || '';
      const intro = form.dataset.articlesProposalIntro || form.dataset.articlesCategoryIntro || '';
      const fields = Array.from(form.querySelectorAll('[name]'))
        .map((field) => field.getAttribute('name'))
        .filter((name) => name && name !== '_csrf' && name !== 'action');
      const body = [
        intro,
        ...fields.map((name) => `${fieldLabel(form, name)}: ${fieldValue(form, name)}`)
      ].filter(Boolean).join('\n');

      window.location.href = `mailto:${recipient}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
      closeDialog(dialog);
    });
  });
})();
