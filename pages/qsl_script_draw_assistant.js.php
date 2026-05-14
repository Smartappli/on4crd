(() => {
    const drawAssistant = document.querySelector('[data-qsl-draw-assistant]');
    if (!drawAssistant) {
        return;
    }

    const choices = drawAssistant.querySelectorAll('[data-qsl-draw-choice]');
    const panels = drawAssistant.querySelectorAll('[data-qsl-draw-panel]');
    if (!choices.length || !panels.length) {
        return;
    }

    const syncPanels = () => {
        const selected = drawAssistant.querySelector('[data-qsl-draw-choice]:checked');
        const activeFlow = selected ? selected.value : 'gradient';
        panels.forEach((panel) => {
            panel.classList.toggle('is-hidden', panel.getAttribute('data-qsl-draw-panel') !== activeFlow);
        });
    };

    choices.forEach((input) => input.addEventListener('change', syncPanels));
    syncPanels();
})();
