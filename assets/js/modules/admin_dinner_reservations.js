(function () {
  const config = window.dinnerReservationConfig || {};
  const starterOptions = config.starterOptions || {};
  const mainOptions = config.mainOptions || {};
  const dessertOptions = config.dessertOptions || {};
  const ui = config.ui || {};
  const linesContainer = document.getElementById('dinner-lines');
  const addButton = document.getElementById('add-dinner-line');
  const totalEl = document.getElementById('dinner-total');
  if (!linesContainer || !totalEl) return;

  let lineIndex = 0;
  const localeMap = { fr: 'fr-BE', en: 'en-GB', de: 'de-DE', nl: 'nl-BE' };
  const numberLocale = localeMap[config.locale || 'fr'] || 'fr-BE';
  const formatEur = (cents) => new Intl.NumberFormat(numberLocale, { style: 'currency', currency: 'EUR' }).format((Number(cents) || 0) / 100);

  const renderSelectOptions = (options) => Object.entries(options)
    .map(([key, value]) => `<option value="${key}" data-price="${value.price_cents}">${value.label} (${formatEur(value.price_cents)})</option>`)
    .join('');

  const updateTotals = () => {
    let total = 0;
    linesContainer.querySelectorAll('.dinner-line').forEach((line) => {
      const starterEnabled = line.querySelector('.starter-enabled')?.checked;
      const mealEnabled = line.querySelector('.meal-enabled')?.checked;
      const dessertEnabled = line.querySelector('.dessert-enabled')?.checked;
      const starterPrice = starterEnabled ? Number(line.querySelector('.starter-select option:checked')?.dataset.price || 0) : 0;
      const mealPrice = mealEnabled ? Number(line.querySelector('.meal-select option:checked')?.dataset.price || 0) : 0;
      const dessertPrice = dessertEnabled ? Number(line.querySelector('.dessert-select option:checked')?.dataset.price || 0) : 0;
      const quantity = Number(line.querySelector('.quantity-input').value || 0);
      const lineTotal = (starterPrice + mealPrice + dessertPrice) * quantity;
      const lineTotalNode = line.querySelector('.line-total');
      if (lineTotalNode) lineTotalNode.textContent = formatEur(lineTotal);
      total += lineTotal;

      const starterSelect = line.querySelector('.starter-select');
      const mealSelect = line.querySelector('.meal-select');
      const dessertSelect = line.querySelector('.dessert-select');
      if (starterSelect) starterSelect.disabled = !starterEnabled;
      if (mealSelect) mealSelect.disabled = !mealEnabled;
      if (dessertSelect) dessertSelect.disabled = !dessertEnabled;
    });
    totalEl.textContent = formatEur(total);
  };

  const addLine = () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'stack inner-card dinner-line';
    wrapper.innerHTML = `
      <div class="grid-3">
        <label><input class="starter-enabled" type="checkbox" name="lines[${lineIndex}][starter_enabled]" value="1"> ${ui.enableStarter || 'Activer entrée'}</label>
        <label><input class="meal-enabled" type="checkbox" name="lines[${lineIndex}][meal_enabled]" value="1" checked> ${ui.enableMeal || 'Activer plat'}</label>
        <label><input class="dessert-enabled" type="checkbox" name="lines[${lineIndex}][dessert_enabled]" value="1" checked> ${ui.enableDessert || 'Activer dessert'}</label>
      </div>
      <div class="grid-3">
        <label>${ui.starter || 'Entrée'}
          <select class="starter-select" name="lines[${lineIndex}][starter]">${renderSelectOptions(starterOptions)}</select>
        </label>
        <label>${ui.meal || 'Plat'}
          <select class="meal-select" name="lines[${lineIndex}][meal]">${renderSelectOptions(mainOptions)}</select>
        </label>
        <label>${ui.dessert || 'Dessert'}
          <select class="dessert-select" name="lines[${lineIndex}][dessert]">${renderSelectOptions(dessertOptions)}</select>
        </label>
      </div>
      <div class="grid-3">
        <label>${ui.qty || 'Nombre'}
          <input class="quantity-input" type="number" name="lines[${lineIndex}][quantity]" min="0" step="1" value="1">
        </label>
        <p class="help">${ui.lineTotal || 'Total ligne'} : <strong class="line-total">${formatEur(0)}</strong></p>
        <p><button type="button" class="button secondary remove-line">${ui.remove || 'Retirer'}</button></p>
      </div>
    `;
    lineIndex += 1;
    linesContainer.appendChild(wrapper);
    wrapper.querySelectorAll('select,input').forEach((input) => input.addEventListener('input', updateTotals));
    wrapper.querySelector('.remove-line')?.addEventListener('click', () => {
      wrapper.remove();
      updateTotals();
    });
    updateTotals();
  };

  addButton?.addEventListener('click', addLine);
  addLine();
})();
