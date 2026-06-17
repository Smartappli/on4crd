(function () {
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
})();
