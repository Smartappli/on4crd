(function () {
  const toggle = document.querySelector('[data-uba-member-toggle]');
  const numberInput = document.querySelector('[data-uba-member-number]');

  if (!toggle || !numberInput) {
    return;
  }

  const syncUbaNumberState = () => {
    numberInput.disabled = !toggle.checked;
  };

  toggle.addEventListener('change', syncUbaNumberState);
  syncUbaNumberState();
})();
