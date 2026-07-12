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
  const confirmDialog = document.createElement('dialog');
  confirmDialog.className = 'admin-confirm-dialog';
  confirmDialog.innerHTML = `<form method="dialog" class="admin-confirm-card"><h2>${dialogLabels.title}</h2><p data-admin-confirm-copy></p><div class="actions"><button class="button secondary" value="cancel">${dialogLabels.cancel}</button><button class="button danger" value="confirm">${dialogLabels.confirm}</button></div></form>`;
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
      if (!(field instanceof HTMLSelectElement) || field.value !== expectedValue) return;
    }
    if (typeof confirmDialog.showModal !== 'function') {
      if (!window.confirm(message)) event.preventDefault();
      return;
    }
    event.preventDefault();
    event.stopImmediatePropagation();
    pendingConfirmation = { form, submitter: event.submitter };
    const copy = confirmDialog.querySelector('[data-admin-confirm-copy]');
    if (copy instanceof HTMLElement) copy.textContent = message;
    confirmDialog.returnValue = 'cancel';
    confirmDialog.showModal();
  }, true);
  confirmDialog.addEventListener('close', () => {
    const pending = pendingConfirmation;
    pendingConfirmation = null;
    if (!pending || confirmDialog.returnValue !== 'confirm') return;
    pending.form.dataset.adminConfirmed = 'true';
    pending.form.requestSubmit(pending.submitter instanceof HTMLElement ? pending.submitter : undefined);
  });

  document.querySelectorAll('#main-content table').forEach((table) => {
    if (!(table instanceof HTMLTableElement)) return;
    const labels = Array.from(table.querySelectorAll('thead th')).map((cell) => cell.textContent.trim());
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
        status.textContent = '•';
        status.setAttribute('aria-label', 'Unsaved changes');
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
