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

  const bindProposalMenuWizards = () => {
    if (typeof HTMLDialogElement === 'undefined') {
      return;
    }

    document.querySelectorAll('details[class*="propose-menu"]').forEach((menu, index) => {
      if (!(menu instanceof HTMLDetailsElement) || menu.dataset.proposalWizardBound === 'true') {
        return;
      }
      const summary = menu.querySelector(':scope > summary');
      const choices = Array.from(menu.querySelectorAll('[role="menuitem"]'))
        .filter((choice) => choice instanceof HTMLAnchorElement);
      if (!(summary instanceof HTMLElement) || choices.length === 0) {
        return;
      }

      menu.dataset.proposalWizardBound = 'true';
      const label = summary.textContent?.trim() || 'Propose';
      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'button';
      trigger.textContent = label;
      trigger.setAttribute('aria-haspopup', 'dialog');

      const dialog = document.createElement('dialog');
      dialog.className = 'proposal-wizard-dialog';
      dialog.id = `proposal-wizard-${index + 1}`;
      dialog.setAttribute('aria-labelledby', `${dialog.id}-title`);
      const card = document.createElement('div');
      card.className = 'proposal-wizard-card';
      const title = document.createElement('h2');
      title.id = `${dialog.id}-title`;
      title.textContent = label;
      const progress = document.createElement('p');
      progress.className = 'proposal-wizard-progress';
      progress.textContent = '1 / 1';
      const list = document.createElement('div');
      list.className = 'proposal-wizard-choices';

      choices.forEach((choice) => {
        const option = document.createElement('button');
        option.type = 'button';
        option.className = 'proposal-wizard-choice';
        option.textContent = choice.textContent?.trim() || choice.href;
        option.addEventListener('click', () => {
          closeDialog(dialog);
          choice.click();
        });
        list.append(option);
      });

      card.append(title, progress, list);
      dialog.append(card);
      trigger.setAttribute('aria-controls', dialog.id);
      trigger.addEventListener('click', () => openDialog(dialog));
      dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
          closeDialog(dialog);
        }
      });
      menu.before(trigger, dialog);
      menu.hidden = true;
    });
  };

  window.ON4CRD.closeDialog = closeDialog;
  window.ON4CRD.openDialog = openDialog;
  window.ON4CRD.bindModalDialogs = bindModalDialogs;
  window.ON4CRD.bindDialogMailtoForms = bindDialogMailtoForms;
  window.ON4CRD.bindProposalMenuWizards = bindProposalMenuWizards;
  bindProposalMenuWizards();
})();
