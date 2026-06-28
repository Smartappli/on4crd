(function () {
  const titleInput = document.querySelector('input[name="title"]');
  const slugInput = document.querySelector('input[name="slug"]');
  const categorySelect = document.querySelector('#article-category');
  const customCategoryWrapper = document.querySelector('#article-category-custom');
  const setFieldDisabled = (field, disabled) => {
    if (!(field instanceof HTMLElement)) return;
    field.classList.toggle('is-muted', disabled);
    field.querySelectorAll('input, select, textarea').forEach((control) => {
      if (control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement) {
        control.disabled = disabled;
      }
    });
  };

  if (titleInput instanceof HTMLInputElement && slugInput instanceof HTMLInputElement) {
    let slugWasAuto = slugInput.value.trim() === '';
    const slugify = (value) => {
      const normalized = value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
      let slug = '';
      let lastWasDash = true;

      Array.from(normalized).forEach((character) => {
        const code = character.charCodeAt(0);
        const isAsciiLetter = code >= 97 && code <= 122;
        const isDigit = code >= 48 && code <= 57;
        if (isAsciiLetter || isDigit) {
          slug += character;
          lastWasDash = false;
        } else if (!lastWasDash) {
          slug += '-';
          lastWasDash = true;
        }
      });

      return slug.endsWith('-') ? slug.slice(0, -1) : slug;
    };

    const syncSlug = () => {
      if (!slugWasAuto) return;
      slugInput.value = slugify(titleInput.value);
    };

    slugInput.addEventListener('input', () => {
      slugWasAuto = slugInput.value.trim() === '';
    });
    titleInput.addEventListener('input', syncSlug);
    syncSlug();
  }

  if (categorySelect instanceof HTMLSelectElement && customCategoryWrapper instanceof HTMLElement) {
    const syncCategoryCustom = () => {
      customCategoryWrapper.hidden = categorySelect.value !== '__custom__';
    };
    categorySelect.addEventListener('change', syncCategoryCustom);
    syncCategoryCustom();
  }

  const editorStatus = document.querySelector('[data-admin-editor-status]');
  if (editorStatus instanceof HTMLSelectElement) {
    const scheduledField = document.querySelector('[data-admin-editor-scheduled-field]');
    const noteField = document.querySelector('[data-admin-editor-note-field]');

    const syncEditorPublicationFields = () => {
      setFieldDisabled(scheduledField, editorStatus.value !== 'scheduled');
      if (noteField instanceof HTMLElement) {
        noteField.classList.toggle('is-muted', editorStatus.value !== 'rejected');
      }
    };

    editorStatus.addEventListener('change', syncEditorPublicationFields);
    syncEditorPublicationFields();
  }

  const bulkForm = document.querySelector('#admin-article-bulk-form');
  if (bulkForm instanceof HTMLFormElement) {
    const articleChecks = Array.from(document.querySelectorAll('input[type="checkbox"][form="admin-article-bulk-form"][name="ids[]"]'))
      .filter((input) => input instanceof HTMLInputElement);
    const selectPage = bulkForm.querySelector('[data-admin-articles-select-page]');
    const selectedCount = bulkForm.querySelector('[data-admin-articles-selected-count]');
    const submitButton = bulkForm.querySelector('[data-admin-bulk-submit]');
    const bulkOp = bulkForm.querySelector('[data-admin-bulk-op]');
    const scheduledField = bulkForm.querySelector('[data-admin-bulk-scheduled-field]');
    const noteField = bulkForm.querySelector('[data-admin-bulk-note-field]');

    const syncBulkAuxiliaryFields = () => {
      const op = bulkOp instanceof HTMLSelectElement ? bulkOp.value : '';
      setFieldDisabled(scheduledField, op !== 'scheduled');
      setFieldDisabled(noteField, op !== 'rejected');
    };

    const syncBulkSelection = () => {
      const checkedCount = articleChecks.filter((input) => input.checked).length;
      if (selectedCount instanceof HTMLElement) {
        selectedCount.textContent = String(checkedCount);
      }
      if (submitButton instanceof HTMLButtonElement) {
        submitButton.disabled = checkedCount === 0;
      }
      if (selectPage instanceof HTMLInputElement) {
        selectPage.checked = checkedCount > 0 && checkedCount === articleChecks.length;
        selectPage.indeterminate = checkedCount > 0 && checkedCount < articleChecks.length;
      }
    };

    if (selectPage instanceof HTMLInputElement) {
      selectPage.addEventListener('change', () => {
        articleChecks.forEach((input) => {
          input.checked = selectPage.checked;
        });
        syncBulkSelection();
      });
    }
    articleChecks.forEach((input) => input.addEventListener('change', syncBulkSelection));
    if (bulkOp instanceof HTMLSelectElement) {
      bulkOp.addEventListener('change', syncBulkAuxiliaryFields);
    }
    bulkForm.addEventListener('submit', (event) => {
      if (!articleChecks.some((input) => input.checked)) {
        event.preventDefault();
        syncBulkSelection();
        return;
      }
      const confirmMessage = bulkForm.getAttribute('data-confirm-message') || '';
      if (confirmMessage !== '' && !window.confirm(confirmMessage)) {
        event.preventDefault();
      }
    });
    syncBulkSelection();
    syncBulkAuxiliaryFields();
  }

  const queueForm = document.querySelector('#admin-article-queue-form');
  if (queueForm instanceof HTMLFormElement) {
    const queueChecks = Array.from(document.querySelectorAll('input[type="checkbox"][form="admin-article-queue-form"][name="ids[]"]'))
      .filter((input) => input instanceof HTMLInputElement);
    const selectQueue = queueForm.querySelector('[data-admin-queue-select-page]');
    const selectedCount = queueForm.querySelector('[data-admin-queue-selected-count]');
    const submitButton = queueForm.querySelector('[data-admin-queue-submit]');

    const syncQueueSelection = () => {
      const checkedCount = queueChecks.filter((input) => input.checked).length;
      if (selectedCount instanceof HTMLElement) {
        selectedCount.textContent = String(checkedCount);
      }
      if (submitButton instanceof HTMLButtonElement) {
        submitButton.disabled = checkedCount === 0;
      }
      if (selectQueue instanceof HTMLInputElement) {
        selectQueue.checked = checkedCount > 0 && checkedCount === queueChecks.length;
        selectQueue.indeterminate = checkedCount > 0 && checkedCount < queueChecks.length;
      }
    };

    if (selectQueue instanceof HTMLInputElement) {
      selectQueue.addEventListener('change', () => {
        queueChecks.forEach((input) => {
          input.checked = selectQueue.checked;
        });
        syncQueueSelection();
      });
    }
    queueChecks.forEach((input) => input.addEventListener('change', syncQueueSelection));
    queueForm.addEventListener('submit', (event) => {
      if (!queueChecks.some((input) => input.checked)) {
        event.preventDefault();
        syncQueueSelection();
        return;
      }
      const confirmMessage = queueForm.getAttribute('data-confirm-message') || '';
      if (confirmMessage !== '' && !window.confirm(confirmMessage)) {
        event.preventDefault();
      }
    });
    syncQueueSelection();
  }

  const rejectDetails = Array.from(document.querySelectorAll('.admin-article-row-reject'))
    .filter((detail) => detail instanceof HTMLDetailsElement);
  rejectDetails.forEach((detail) => {
    detail.addEventListener('toggle', () => {
      if (!detail.open) return;
      rejectDetails.forEach((other) => {
        if (other !== detail) other.open = false;
      });
    });
  });
  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Node)) return;
    rejectDetails.forEach((detail) => {
      if (!detail.contains(target)) detail.open = false;
    });
  });
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    rejectDetails.forEach((detail) => {
      detail.open = false;
    });
  });
})();
