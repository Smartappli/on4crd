(function () {
  document.querySelectorAll('[data-album-upload-dropzone]').forEach((dropzone) => {
    if (!(dropzone instanceof HTMLElement)) return;
    const input = dropzone.closest('label')?.querySelector('[data-album-upload-input]')
      || dropzone.closest('form')?.querySelector('[data-album-upload-input]');
    if (!(input instanceof HTMLInputElement)) return;

    const ready = dropzone.dataset.readyFiles || 'files ready';
    const setCount = () => {
      const count = input.files?.length || 0;
      if (count > 0) {
        dropzone.textContent = count + ' ' + ready;
      }
    };

    dropzone.addEventListener('click', () => input.click());
    dropzone.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        input.click();
      }
    });
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

  const dialog = document.getElementById('album-photo-viewer');
  const openLinks = document.querySelectorAll('[data-album-viewer-open]');

  if (!(dialog instanceof HTMLDialogElement) || openLinks.length === 0) {
    return;
  }

  const image = dialog.querySelector('[data-album-viewer-image]');
  const titleNode = dialog.querySelector('[data-album-viewer-title]');
  const captionNode = dialog.querySelector('[data-album-viewer-caption]');
  const descriptionNode = dialog.querySelector('[data-album-viewer-description]');
  const copyNode = dialog.querySelector('.album-photo-viewer-copy');
  const closeButtons = dialog.querySelectorAll('[data-album-viewer-close]');
  const previousButton = dialog.querySelector('[data-album-viewer-prev]');
  const nextButton = dialog.querySelector('[data-album-viewer-next]');
  const links = Array.from(openLinks);
  let currentIndex = -1;

  if (!(image instanceof HTMLImageElement) || !(copyNode instanceof HTMLElement)) {
    return;
  }

  const setText = (node, value) => {
    if (!(node instanceof HTMLElement)) {
      return false;
    }

    const text = value.trim();
    node.textContent = text;
    node.hidden = text === '';

    return text !== '';
  };

  const closeViewer = () => {
    if (dialog.open) {
      dialog.close();
    }
  };

  const clearImage = () => {
    image.removeAttribute('src');
    image.alt = '';
  };

  const showPhoto = (index) => {
    if (links.length === 0) {
      return;
    }

    currentIndex = (index + links.length) % links.length;
    const link = links[currentIndex];
    const href = link.getAttribute('href') || '';
    if (href === '') {
      return;
    }

    const thumbnail = link.querySelector('img');
    const title = link.getAttribute('data-photo-title') || '';
    const caption = link.getAttribute('data-photo-caption') || '';
    const description = dialog.getAttribute('data-album-description') || '';
    const fallbackAlt = thumbnail instanceof HTMLImageElement ? thumbnail.alt : '';

    image.src = href;
    image.alt = title.trim() || fallbackAlt;

    const hasTitle = setText(titleNode, title);
    const hasCaption = setText(captionNode, caption);
    const hasDescription = setText(descriptionNode, description);
    copyNode.hidden = !hasTitle && !hasCaption && !hasDescription;

    if (!dialog.open) {
      dialog.showModal();

      const closeButton = dialog.querySelector('[data-album-viewer-close]');
      if (closeButton instanceof HTMLElement) {
        closeButton.focus();
      }
    }
  };

  links.forEach((link, index) => {
    link.addEventListener('click', (event) => {
      const href = link.getAttribute('href') || '';
      if (href === '') {
        return;
      }

      event.preventDefault();
      showPhoto(index);
    });
  });

  if (previousButton instanceof HTMLButtonElement) {
    previousButton.hidden = links.length < 2;
    previousButton.addEventListener('click', () => {
      showPhoto(currentIndex - 1);
    });
  }

  if (nextButton instanceof HTMLButtonElement) {
    nextButton.hidden = links.length < 2;
    nextButton.addEventListener('click', () => {
      showPhoto(currentIndex + 1);
    });
  }

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeViewer);
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      closeViewer();
    }
  });

  dialog.addEventListener('close', clearImage);

  document.addEventListener('keydown', (event) => {
    if (!dialog.open || links.length < 2) {
      return;
    }

    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      showPhoto(currentIndex - 1);
    }

    if (event.key === 'ArrowRight') {
      event.preventDefault();
      showPhoto(currentIndex + 1);
    }
  });
})();
