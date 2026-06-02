(function () {
  const dialog = document.getElementById('classifieds-category-dialog');
  if (typeof HTMLDialogElement === 'undefined' || !(dialog instanceof HTMLDialogElement)) {
    return;
  }

  const openButtons = document.querySelectorAll('[data-classifieds-category-open]');
  const closeButtons = dialog.querySelectorAll('[data-classifieds-category-close]');
  const firstField = dialog.querySelector('input[name="proposal_category"]');

  const openDialog = () => {
    if (!dialog.open) {
      dialog.showModal();
    }
    if (firstField instanceof HTMLElement) {
      firstField.focus();
    }
  };

  const closeDialog = () => {
    if (dialog.open) {
      dialog.close();
    }
  };

  openButtons.forEach((button) => {
    button.addEventListener('click', openDialog);
  });

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeDialog);
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      closeDialog();
    }
  });
})();
