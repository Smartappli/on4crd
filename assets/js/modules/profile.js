(function () {
  const toggle = document.querySelector('[data-uba-member-toggle]');
  const numberInput = document.querySelector('[data-uba-member-number]');

  if (toggle && numberInput) {
    const syncUbaNumberState = () => {
      numberInput.disabled = !toggle.checked;
    };

    toggle.addEventListener('change', syncUbaNumberState);
    syncUbaNumberState();
  }

  const photoInput = document.querySelector('[data-profile-photo-input]');
  const photoPreview = document.querySelector('[data-profile-photo-preview]');

  if (!photoInput || !photoPreview || typeof URL === 'undefined' || typeof URL.createObjectURL !== 'function') {
    return;
  }

  const initialPhotoSrc = photoPreview.getAttribute('src') || '';
  let previewObjectUrl = '';

  const revokePreviewObjectUrl = () => {
    if (previewObjectUrl !== '') {
      URL.revokeObjectURL(previewObjectUrl);
      previewObjectUrl = '';
    }
  };

  photoInput.addEventListener('change', () => {
    revokePreviewObjectUrl();

    const file = photoInput.files && photoInput.files.length > 0 ? photoInput.files[0] : null;
    if (!file || (file.type !== '' && !file.type.startsWith('image/'))) {
      photoPreview.setAttribute('src', initialPhotoSrc);
      return;
    }

    previewObjectUrl = URL.createObjectURL(file);
    photoPreview.setAttribute('src', previewObjectUrl);
  });

  window.addEventListener('beforeunload', revokePreviewObjectUrl);
})();
