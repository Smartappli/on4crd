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
      dropzone.style.background = 'var(--panel-3)';
    });
    dropzone.addEventListener('dragleave', () => {
      dropzone.style.background = '';
    });
    dropzone.addEventListener('drop', (event) => {
      event.preventDefault();
      dropzone.style.background = '';
      const files = event.dataTransfer?.files;
      if (!files || files.length === 0) return;
      input.files = files;
      setCount();
    });
    input.addEventListener('change', setCount);
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
})();
