(function () {
  const openButtons = document.querySelectorAll('[data-news-proposal-open]');
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
      const dialogId = button.dataset.newsProposalOpen || '';
      const dialog = document.getElementById(dialogId);
      if (!supportsDialog || !(dialog instanceof HTMLDialogElement)) {
        return;
      }

      event.preventDefault();
      if (!dialog.open) {
        dialog.showModal();
      }
      const firstField = dialog.querySelector('input, textarea, select');
      if (firstField instanceof HTMLElement) {
        firstField.focus();
      }
    });
  });

  document.querySelectorAll('.news-proposal-dialog').forEach((dialog) => {
    dialog.querySelectorAll('[data-news-proposal-close]').forEach((button) => {
      button.addEventListener('click', () => closeDialog(dialog));
    });

    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        closeDialog(dialog);
      }
    });

    const form = dialog.querySelector('[data-news-proposal-form]');
    if (!form) {
      return;
    }

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const recipient = form.dataset.newsProposalRecipient || 'crdurnal@gmail.com';
      const subject = form.dataset.newsProposalSubject || '';
      const intro = form.dataset.newsProposalIntro || '';
      const fields = Array.from(form.querySelectorAll('[name]'))
        .map((field) => field.getAttribute('name'))
        .filter(Boolean);
      const body = [
        intro,
        ...fields.map((name) => `${fieldLabel(form, name)}: ${fieldValue(form, name)}`)
      ].filter(Boolean).join('\n');

      window.location.href = `mailto:${recipient}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
      closeDialog(dialog);
    });
  });
})();
