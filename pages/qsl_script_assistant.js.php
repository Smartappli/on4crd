(() => {
    const assistant = document.querySelector('[data-qsl-assistant]');
    if (!assistant) {
        return;
    }

    const choices = assistant.querySelectorAll('[data-qsl-assistant-choice]');
    const panels = assistant.querySelectorAll('[data-qsl-assistant-panel]');
    if (!choices.length || !panels.length) {
        return;
    }

    const syncPanels = () => {
        const selected = assistant.querySelector('[data-qsl-assistant-choice]:checked');
        const activeFlow = selected ? selected.value : 'manual';
        panels.forEach((panel) => {
            panel.classList.toggle('is-hidden', panel.getAttribute('data-qsl-assistant-panel') !== activeFlow);
        });
    };

    choices.forEach((input) => input.addEventListener('change', syncPanels));
    syncPanels();
})();
