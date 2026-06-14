(function () {
  const titleInput = document.querySelector('input[name="title"]');
  const slugInput = document.querySelector('input[name="slug"]');
  const categorySelect = document.querySelector('#article-category');
  const customCategoryWrapper = document.querySelector('#article-category-custom');

  if (titleInput instanceof HTMLInputElement && slugInput instanceof HTMLInputElement) {
    let slugWasAuto = slugInput.value.trim() === '';
    const slugify = (value) => {
      const normalized = value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
      let slug = '';
      let lastWasDash = true;

      Array.from(normalized).forEach((character) => {
        const code = character.charCodeAt(0);
        const isAsciiLetter = code >= 97 && code <= 122;
        const isDigit = code >= 48 && code <= 57;
        if (isAsciiLetter || isDigit) {
          slug += character;
          lastWasDash = false;
        } else if (!lastWasDash) {
          slug += '-';
          lastWasDash = true;
        }
      });

      return slug.endsWith('-') ? slug.slice(0, -1) : slug;
    };

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
