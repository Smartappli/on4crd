document.querySelectorAll('[data-qso-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const table = button.closest('form');
        if (!table) {
            return;
        }
        const checked = button.dataset.qsoToggle === 'all';
        table.querySelectorAll('input[name="qso_ids[]"]').forEach((checkbox) => {
            checkbox.checked = checked;
        });
    });
});
