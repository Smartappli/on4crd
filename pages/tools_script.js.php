(() => {
    const i18n = <?= json_encode($jsI18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
<?php require __DIR__ . '/tools_script_helpers.js.php'; ?>
<?php require __DIR__ . '/tools_script_domrefs.js.php'; ?>

    const setError = (message) => {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove('is-hidden');
    };
    const clearError = () => errorBox?.classList.add('is-hidden');

    const initializedTools = new Set();
<?php require __DIR__ . '/tools_script_initializers.js.php'; ?>

    const initToolIfNeeded = (id) => {
        if (initializedTools.has(id)) return;
        const initializer = toolInitializers[id];
        if (typeof initializer === 'function') {
            initializer();
            initializedTools.add(id);
        }
    };
<?php require __DIR__ . '/tools_script_computes.js.php'; ?>

<?php require __DIR__ . '/tools_script_loader.js.php'; ?>


})();
