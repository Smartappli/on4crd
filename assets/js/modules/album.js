(function () {
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

  openLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
      const href = link.getAttribute('href') || '';
      if (href === '') {
        return;
      }

      event.preventDefault();

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

      if (dialog.open) {
        dialog.close();
      }
      dialog.showModal();

      const closeButton = dialog.querySelector('[data-album-viewer-close]');
      if (closeButton instanceof HTMLElement) {
        closeButton.focus();
      }
    });
  });

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeViewer);
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      closeViewer();
    }
  });

  dialog.addEventListener('close', clearImage);
})();
