(function () {
  const views = document.querySelectorAll('[data-gdpr-view]');
  const visibilityInputs = document.querySelectorAll('.gdpr-visibility-table input[type="radio"]');
  if (!views.length || !visibilityInputs.length) {
    return;
  }

  const canView = (viewer, visibility) => {
    if (viewer === 'private') return true;
    if (viewer === 'members') return visibility === 'public' || visibility === 'members';
    return visibility === 'public';
  };

  const currentVisibility = (fieldName) => {
    const checked = document.querySelector(`input[name="${fieldName}"]:checked`);
    return checked instanceof HTMLInputElement ? checked.value : 'members';
  };

  const syncView = (view) => {
    const viewer = view.getAttribute('data-gdpr-view') || 'public';
    let visibleRows = 0;

    view.querySelectorAll('[data-gdpr-preview-row]').forEach((row) => {
      const fieldName = row.getAttribute('data-gdpr-visibility-field') || '';
      const isVisible = canView(viewer, currentVisibility(fieldName));
      row.hidden = !isVisible;
      if (isVisible) visibleRows += 1;
    });

    view.querySelectorAll('[data-gdpr-photo]').forEach((photo) => {
      const fieldName = photo.getAttribute('data-gdpr-visibility-field') || 'visibility_photo';
      const isVisible = canView(viewer, currentVisibility(fieldName));
      photo.hidden = !isVisible;
      if (isVisible) visibleRows += 1;
    });

    const empty = view.querySelector('[data-gdpr-empty]');
    if (empty instanceof HTMLElement) {
      empty.hidden = visibleRows > 0;
    }
  };

  const syncViews = () => {
    views.forEach(syncView);
  };

  visibilityInputs.forEach((input) => {
    input.addEventListener('change', syncViews);
  });
  syncViews();
})();
