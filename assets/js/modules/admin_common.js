(function () {
  const adminRoot = document.querySelector('body[data-route="admin"], body[data-route^="admin_"]');
  if (!(adminRoot instanceof HTMLElement)) return;

  const groups = Array.from(document.querySelectorAll('.admin-shell-group'))
    .filter((group) => group instanceof HTMLDetailsElement);
  groups.forEach((group) => {
    group.addEventListener('toggle', () => {
      if (!group.open) return;
      groups.forEach((other) => {
        if (other !== group) other.open = false;
      });
    });
  });

  const adminNav = document.querySelector('.admin-shell-nav');
  const dialogLabels = {
    title: adminNav?.dataset.adminConfirmTitle || 'Confirm action',
    cancel: adminNav?.dataset.adminConfirmCancel || 'Cancel',
    confirm: adminNav?.dataset.adminConfirmSubmit || 'Confirm',
  };
  const unsavedChangesLabel = adminNav?.dataset.adminUnsavedLabel || 'Unsaved changes';
  const confirmDialog = document.createElement('dialog');
  confirmDialog.className = 'admin-confirm-dialog';
  confirmDialog.setAttribute('aria-labelledby', 'admin-confirm-dialog-title');
  confirmDialog.setAttribute('aria-describedby', 'admin-confirm-dialog-copy');
  confirmDialog.innerHTML = `<form method="dialog" class="admin-confirm-card"><h2 id="admin-confirm-dialog-title">${dialogLabels.title}</h2><p id="admin-confirm-dialog-copy" data-admin-confirm-copy></p><div class="actions"><button class="button secondary" value="cancel">${dialogLabels.cancel}</button><button class="button danger" value="confirm">${dialogLabels.confirm}</button></div></form>`;
  document.body.appendChild(confirmDialog);
  let pendingConfirmation = null;
  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.adminConfirmed === 'true') return;
    const message = form.getAttribute('data-confirm-message') || '';
    if (message === '') return;
    const condition = form.getAttribute('data-confirm-when-select') || '';
    if (condition.includes(':')) {
      const [fieldName, expectedValue] = condition.split(':', 2);
      const field = form.elements.namedItem(fieldName);
      const expectedValues = expectedValue.split('|').filter(Boolean);
      if (!(field instanceof HTMLSelectElement) || !expectedValues.includes(field.value)) return;
    }
    const submitActionCondition = form.getAttribute('data-confirm-when-submit-action') || '';
    if (submitActionCondition !== '') {
      const submitter = event.submitter;
      const isActionSubmitter = (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement)
        && submitter.name === 'action';
      const expectedActions = submitActionCondition.split('|').filter(Boolean);
      if (!isActionSubmitter || !expectedActions.includes(submitter.value)) return;
    }
    const checkedCondition = form.getAttribute('data-confirm-when-checked') || '';
    if (checkedCondition !== '') {
      const field = form.elements.namedItem(checkedCondition);
      if (!(field instanceof HTMLInputElement) || field.type !== 'checkbox' || !field.checked) return;
    }
    if (typeof confirmDialog.showModal !== 'function') {
      if (!window.confirm(message)) event.preventDefault();
      return;
    }
    event.preventDefault();
    event.stopImmediatePropagation();
    const focusTarget = event.submitter instanceof HTMLElement
      ? event.submitter
      : (document.activeElement instanceof HTMLElement ? document.activeElement : null);
    pendingConfirmation = { form, submitter: event.submitter, focusTarget };
    const copy = confirmDialog.querySelector('[data-admin-confirm-copy]');
    if (copy instanceof HTMLElement) copy.textContent = message;
    confirmDialog.returnValue = 'cancel';
    confirmDialog.showModal();
  }, true);
  confirmDialog.addEventListener('close', () => {
    const pending = pendingConfirmation;
    pendingConfirmation = null;
    if (!pending) return;
    if (confirmDialog.returnValue !== 'confirm') {
      pending.focusTarget?.focus();
      return;
    }
    pending.form.dataset.adminConfirmed = 'true';
    pending.form.requestSubmit(pending.submitter instanceof HTMLElement ? pending.submitter : undefined);
  });

  document.querySelectorAll('#main-content table').forEach((table) => {
    if (!(table instanceof HTMLTableElement)) return;
    const headerCells = Array.from(table.querySelectorAll('thead th'))
      .filter((cell) => cell instanceof HTMLTableCellElement);
    headerCells.forEach((cell) => {
      if (!cell.hasAttribute('scope')) cell.scope = 'col';
    });
    const labels = headerCells.map((cell) => cell.textContent.trim());
    if (labels.length === 0) return;
    table.classList.add('admin-responsive-table');
    table.querySelectorAll('tbody tr').forEach((row) => {
      Array.from(row.children).forEach((cell, index) => {
        if (cell instanceof HTMLTableCellElement && !cell.hasAttribute('colspan')) {
          cell.dataset.adminLabel = labels[index] || '';
        }
      });
    });
  });

  document.querySelectorAll('form[data-admin-wizard]').forEach((form) => {
    if (!(form instanceof HTMLFormElement)) return;
    const steps = Array.from(form.querySelectorAll('[data-admin-wizard-step]'))
      .filter((step) => step instanceof HTMLElement);
    if (steps.length < 2) return;
    const currentLabel = form.dataset.adminWizardCurrentLabel || 'Current step';
    const previousLabel = form.dataset.adminWizardPreviousLabel || 'Previous';
    const nextLabel = form.dataset.adminWizardNextLabel || 'Next';
    const navigation = document.createElement('ol');
    navigation.className = 'admin-wizard-progress';
    navigation.setAttribute('aria-label', form.dataset.adminWizardLabel || 'Progress');
    const controls = document.createElement('div');
    controls.className = 'admin-wizard-controls';
    const previous = document.createElement('button');
    previous.type = 'button';
    previous.className = 'button secondary';
    previous.textContent = previousLabel;
    const next = document.createElement('button');
    next.type = 'button';
    next.className = 'button';
    next.textContent = nextLabel;
    controls.append(previous, next);
    form.prepend(navigation);
    form.append(controls);
    let activeStep = 0;
    const focusActiveStep = () => {
      steps[activeStep].querySelector('input, select, textarea, button')?.focus();
    };
    const render = () => {
      steps.forEach((step, index) => {
        const isActive = index === activeStep;
        step.hidden = !isActive;
        step.setAttribute('aria-hidden', String(!isActive));
      });
      navigation.replaceChildren(...steps.map((step, index) => {
        const item = document.createElement('li');
        item.textContent = step.dataset.adminWizardTitle || `${currentLabel} ${index + 1}`;
        if (index === activeStep) item.setAttribute('aria-current', 'step');
        return item;
      }));
      previous.hidden = activeStep === 0;
      next.hidden = activeStep === steps.length - 1;
    };
    previous.addEventListener('click', () => {
      activeStep -= 1;
      render();
      focusActiveStep();
    });
    next.addEventListener('click', () => {
      const fields = Array.from(steps[activeStep].querySelectorAll('input, select, textarea'))
        .filter((field) => field instanceof HTMLInputElement
          || field instanceof HTMLSelectElement
          || field instanceof HTMLTextAreaElement);
      const firstInvalid = fields.find((field) => !field.checkValidity());
      if (firstInvalid) {
        firstInvalid.reportValidity();
        return;
      }
      activeStep += 1;
      render();
      focusActiveStep();
    });
    render();
  });

  const trackedForms = Array.from(document.querySelectorAll('form[data-admin-dirty-track]'))
    .filter((form) => form instanceof HTMLFormElement);
  let submittedForm = null;
  trackedForms.forEach((form) => {
    const baseline = new FormData(form);
    const signature = (data) => Array.from(data.entries())
      .filter(([name]) => name !== '_csrf')
      .map(([name, value]) => `${name}=${value instanceof File ? value.name : String(value)}`)
      .join('&');
    const initialSignature = signature(baseline);
    const syncDirty = () => {
      const dirty = signature(new FormData(form)) !== initialSignature;
      form.classList.toggle('is-dirty', dirty);
      if (dirty && !form.querySelector('.admin-dirty-status')) {
        const status = document.createElement('span');
        status.className = 'admin-dirty-status';
        status.textContent = unsavedChangesLabel;
        status.setAttribute('role', 'status');
        status.setAttribute('aria-label', unsavedChangesLabel);
        form.appendChild(status);
      }
      if (!dirty) form.querySelector('.admin-dirty-status')?.remove();
    };
    form.addEventListener('input', syncDirty);
    form.addEventListener('change', syncDirty);
    form.addEventListener('submit', () => { submittedForm = form; });
  });

  window.addEventListener('beforeunload', (event) => {
    if (!trackedForms.some((form) => form !== submittedForm && form.classList.contains('is-dirty'))) return;
    event.preventDefault();
    event.returnValue = '';
  });
})();
