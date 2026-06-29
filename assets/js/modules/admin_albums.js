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

  const photoFilter = document.querySelector('[data-admin-album-photo-filter]');
  if (photoFilter instanceof HTMLFormElement) {
    const select = photoFilter.querySelector('select[name="photo_album"]');
    if (select instanceof HTMLSelectElement) {
      select.addEventListener('change', () => {
        try {
          if (typeof photoFilter.requestSubmit === 'function') {
            photoFilter.requestSubmit();
            return;
          }
          photoFilter.submit();
        } catch (error) {
          photoFilter.submit();
        }
      });
    }
  }

  const listFilter = document.querySelector('[data-admin-album-list-filter]');
  if (listFilter instanceof HTMLFormElement) {
    listFilter.querySelectorAll('select').forEach((select) => {
      if (!(select instanceof HTMLSelectElement)) return;
      select.addEventListener('change', () => {
        try {
          if (typeof listFilter.requestSubmit === 'function') {
            listFilter.requestSubmit();
            return;
          }
          listFilter.submit();
        } catch (error) {
          listFilter.submit();
        }
      });
    });
  }

  const uploadTarget = document.querySelector('[data-admin-album-upload-target]');
  const uploadForm = document.querySelector('.admin-album-upload-form');
  if (uploadTarget instanceof HTMLElement && uploadForm instanceof HTMLFormElement) {
    const albumSelect = uploadForm.querySelector('select[name="album_id"]');
    const media = uploadTarget.querySelector('[data-admin-album-upload-media]');
    const title = uploadTarget.querySelector('[data-admin-album-upload-title]');
    const meta = uploadTarget.querySelector('[data-admin-album-upload-meta]');

    const replaceMedia = (option, titleText) => {
      if (!(media instanceof HTMLElement)) return;

      media.replaceChildren();
      const imageSrc = option.dataset.imageSrc || '';
      if (imageSrc !== '') {
        const picture = document.createElement('picture');
        const webpSrc = option.dataset.imageWebpSrc || '';
        if (webpSrc !== '') {
          const source = document.createElement('source');
          source.srcset = webpSrc;
          source.type = 'image/webp';
          picture.appendChild(source);
        }

        const image = document.createElement('img');
        image.src = imageSrc;
        image.alt = option.dataset.imageAlt || titleText;
        image.loading = 'lazy';
        image.decoding = 'async';
        picture.appendChild(image);
        media.appendChild(picture);
        return;
      }

      const placeholder = document.createElement('span');
      placeholder.textContent = uploadTarget.dataset.placeholder || 'Album';
      media.appendChild(placeholder);
    };

    const syncUploadTarget = () => {
      if (!(albumSelect instanceof HTMLSelectElement)) return;
      const option = albumSelect.selectedOptions[0];
      if (!(option instanceof HTMLOptionElement)) return;

      const titleText = option.dataset.title || option.textContent?.trim() || '';
      if (title instanceof HTMLElement) title.textContent = titleText;
      if (meta instanceof HTMLElement) {
        const photosLabel = uploadTarget.dataset.photosLabel || 'photos';
        const publicLabel = uploadTarget.dataset.publicLabel || 'Public';
        const visibility = option.dataset.isPublic === '1' ? uploadTarget.dataset.yes || 'Yes' : uploadTarget.dataset.no || 'No';
        meta.textContent = (option.dataset.photoCount || '0') + ' ' + photosLabel + ' - ' + publicLabel + ': ' + visibility;
      }
      replaceMedia(option, titleText);
    };

    if (albumSelect instanceof HTMLSelectElement) {
      albumSelect.addEventListener('change', syncUploadTarget);
      syncUploadTarget();
    }
  }

  document.querySelectorAll('.admin-album-edit-form').forEach((form) => {
    if (!(form instanceof HTMLFormElement)) return;

    const controls = Array.from(form.elements).filter((control) => {
      if (!(control instanceof HTMLInputElement || control instanceof HTMLTextAreaElement || control instanceof HTMLSelectElement)) return false;
      if (control instanceof HTMLInputElement && ['hidden', 'submit', 'button'].includes(control.type)) return false;
      return !control.disabled;
    });
    const saveButton = form.querySelector('[data-admin-album-save]');
    const listItem = form.closest('.admin-album-list-item');
    const valueOf = (control) => {
      if (control instanceof HTMLInputElement && (control.type === 'checkbox' || control.type === 'radio')) {
        return control.checked ? '1' : '0';
      }
      return control.value;
    };
    const initialValues = new Map(controls.map((control) => [control, valueOf(control)]));
    const syncDirtyState = () => {
      const isDirty = controls.some((control) => valueOf(control) !== initialValues.get(control));
      form.classList.toggle('is-dirty', isDirty);
      if (listItem instanceof HTMLElement) listItem.classList.toggle('is-dirty', isDirty);
      if (saveButton instanceof HTMLElement) saveButton.classList.toggle('is-dirty', isDirty);
    };

    controls.forEach((control) => {
      control.addEventListener('input', syncDirtyState);
      control.addEventListener('change', syncDirtyState);
    });
    syncDirtyState();
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
