(function () {
  const dialog = document.querySelector('[data-comics-viewer]');
  if (typeof HTMLDialogElement === 'undefined' || !(dialog instanceof HTMLDialogElement)) {
    return;
  }

  const title = dialog.querySelector('[data-comics-viewer-title]');
  const description = dialog.querySelector('[data-comics-viewer-description]');
  const image = dialog.querySelector('[data-comics-viewer-image]');
  const download = dialog.querySelector('[data-comics-viewer-download]');
  const closeButton = dialog.querySelector('[data-comics-viewer-close]');
  if (!(title instanceof HTMLElement)
      || !(description instanceof HTMLElement)
      || !(image instanceof HTMLImageElement)
      || !(download instanceof HTMLAnchorElement)
      || !(closeButton instanceof HTMLButtonElement)) {
    return;
  }

  let activeTrigger = null;

  const closeViewer = () => {
    if (dialog.open) {
      dialog.close();
    }
  };

  document.querySelectorAll('[data-comics-viewer-open]').forEach((trigger) => {
    if (!(trigger instanceof HTMLElement)) {
      return;
    }

    trigger.addEventListener('click', (event) => {
      if (typeof dialog.showModal !== 'function') {
        return;
      }

      const imageUrl = trigger.getAttribute('href') || '';
      if (imageUrl === '') {
        return;
      }

      event.preventDefault();
      if (dialog.open) {
        dialog.close();
      }

      activeTrigger = trigger;
      title.textContent = trigger.dataset.comicsTitle || '';
      description.textContent = trigger.dataset.comicsText || '';
      image.src = imageUrl;
      image.alt = trigger.dataset.comicsAlt || trigger.dataset.comicsTitle || '';
      download.href = imageUrl;
      download.setAttribute('aria-label', trigger.dataset.comicsDownloadLabel || download.textContent || '');

      const downloadName = trigger.dataset.comicsDownload || '';
      if (downloadName !== '') {
        download.setAttribute('download', downloadName);
      } else {
        download.removeAttribute('download');
      }

      dialog.showModal();
      closeButton.focus({ preventScroll: true });
    });
  });

  closeButton.addEventListener('click', closeViewer);
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      closeViewer();
    }
  });
  dialog.addEventListener('close', () => {
    image.removeAttribute('src');
    if (activeTrigger instanceof HTMLElement) {
      activeTrigger.focus({ preventScroll: true });
    }
  });
})();
