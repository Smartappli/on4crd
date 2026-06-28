(function () {
  const params = new URLSearchParams(window.location.search);
  const shouldFocusWizard = params.get('focus') === 'album-wizard' || window.location.hash === '#album-wizard';
  if (shouldFocusWizard) {
    const wizard = document.getElementById('album-wizard');
    if (wizard instanceof HTMLElement) {
      if (!wizard.hasAttribute('tabindex')) wizard.setAttribute('tabindex', '-1');
      window.requestAnimationFrame(() => {
        wizard.scrollIntoView({ block: 'start' });
        wizard.focus({ preventScroll: true });
      });
    }
  }

  const pairs = [
    ['#album-dropzone', '#album-photos-input'],
    ['#album-wizard-dropzone', '#album-wizard-photos-input'],
  ];

  pairs.forEach(([dropzoneSelector, inputSelector]) => {
    const dropzone = document.querySelector(dropzoneSelector);
    const input = document.querySelector(inputSelector);
    if (!(dropzone instanceof HTMLElement) || !(input instanceof HTMLInputElement)) return;

    const ready = dropzone.dataset.readyFiles || 'files ready';
    const setCount = () => {
      const count = input.files?.length || 0;
      if (count > 0) dropzone.textContent = count + ' ' + ready;
    };

    dropzone.addEventListener('click', () => input.click());
    dropzone.addEventListener('dragover', (event) => {
      event.preventDefault();
      dropzone.classList.add('is-dragging');
    });
    dropzone.addEventListener('dragleave', () => {
      dropzone.classList.remove('is-dragging');
    });
    dropzone.addEventListener('drop', (event) => {
      event.preventDefault();
      dropzone.classList.remove('is-dragging');
      const files = event.dataTransfer?.files;
      if (!files || files.length === 0) return;
      input.files = files;
      setCount();
    });
    input.addEventListener('change', setCount);
  });

  document.querySelectorAll('form').forEach((form) => {
    if (!(form instanceof HTMLFormElement)) return;
    const categorySelect = form.querySelector('select[name="category"]');
    const subcategorySelect = form.querySelector('select[name="subcategory_ref"]');
    if (!(categorySelect instanceof HTMLSelectElement) || !(subcategorySelect instanceof HTMLSelectElement)) return;

    const optionParts = (option) => String(option.value || '').split(':');
    const optionCategory = (option) => optionParts(option)[0] || '';

    const setOptionAvailable = (option, available) => {
      if (option.value === '') return;
      option.disabled = !available;
      option.hidden = !available;
    };

    const syncAlbumTaxonomy = (source) => {
      if (source === 'subcategory' && subcategorySelect.value !== '') {
        const selected = subcategorySelect.selectedOptions[0];
        const parentCategory = selected instanceof HTMLOptionElement ? optionCategory(selected) : '';
        if (parentCategory !== '' && categorySelect.value !== parentCategory) {
          categorySelect.value = parentCategory;
        }
      }

      const currentCategory = categorySelect.value;
      const selected = subcategorySelect.selectedOptions[0];
      const selectedCategory = selected instanceof HTMLOptionElement ? optionCategory(selected) : '';
      if (subcategorySelect.value !== '' && selectedCategory !== currentCategory) {
        subcategorySelect.value = '';
      }

      Array.from(subcategorySelect.options).forEach((option) => {
        setOptionAvailable(option, optionCategory(option) === currentCategory);
      });
    };

    categorySelect.addEventListener('change', () => syncAlbumTaxonomy('category'));
    subcategorySelect.addEventListener('change', () => syncAlbumTaxonomy('subcategory'));
    syncAlbumTaxonomy('init');
  });

  document.querySelectorAll('[data-admin-album-save]').forEach((button) => {
    if (!(button instanceof HTMLButtonElement)) return;
    button.addEventListener('click', (event) => {
      const form = button.form || button.closest('form');
      if (!(form instanceof HTMLFormElement)) return;

      if (button.form === form) {
        return;
      }

      event.preventDefault();
      try {
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit();
          return;
        }
        form.submit();
      } catch (error) {
        form.submit();
      }
    });
  });

  const rebuildForm = document.querySelector('[data-admin-album-rebuild-form]');
  if (rebuildForm instanceof HTMLFormElement && rebuildForm.dataset.autoContinue === '1') {
    const submitButton = rebuildForm.querySelector('button[type="submit"]');
    rebuildForm.setAttribute('aria-busy', 'true');
    if (submitButton instanceof HTMLButtonElement) {
      submitButton.setAttribute('aria-disabled', 'true');
    }

    window.setTimeout(() => {
      try {
        if (typeof rebuildForm.requestSubmit === 'function') {
          rebuildForm.requestSubmit();
          return;
        }
        rebuildForm.submit();
      } catch (error) {
        rebuildForm.submit();
      }
    }, 900);
  }
})();
