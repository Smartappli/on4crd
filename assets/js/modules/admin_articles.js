(function () {
  const titleInput = document.querySelector('input[name="title"]');
  const slugInput = document.querySelector('input[name="slug"]');
  const categorySelect = document.querySelector('#article-category');
  const customCategoryWrapper = document.querySelector('#article-category-custom');

  if (titleInput instanceof HTMLInputElement && slugInput instanceof HTMLInputElement) {
    let slugWasAuto = slugInput.value.trim() === '';
    const slugify = (value) => value
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .replace(/-{2,}/g, '-');

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
})();
