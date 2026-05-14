(() => {
    const previewCard = document.querySelector('[data-qsl-preview-card]');
    if (!previewCard) {
        return;
    }

    const primaryInput = document.querySelector('[data-preview-color-primary]');
    const secondaryInput = document.querySelector('[data-preview-color-secondary]');
    const solidInput = document.querySelector('[data-preview-solid-color]');
    const paletteSelect = document.querySelector('[data-preview-palette-select]');
    const imageInput = document.querySelector('[data-preview-image-input]');
    const drawFlowChoices = document.querySelectorAll('[data-qsl-draw-choice]');
    const applyGradient = (primary = '#0B1F3A', secondary = '#1D4ED8') => {
        previewCard.style.backgroundImage = `linear-gradient(135deg, ${primary}, ${secondary})`;
    };
    const applyCurrentGradientInputs = () => {
        const primary = primaryInput?.value || '#0B1F3A';
        const secondary = secondaryInput?.value || '#1D4ED8';
        applyGradient(primary, secondary);
    };
    const applySolid = () => {
        const solid = solidInput?.value || '#1E293B';
        applyGradient(solid, solid);
    };
    const applyPalette = () => {
        if (!(paletteSelect instanceof HTMLSelectElement)) {
            return;
        }
        const option = paletteSelect.selectedOptions[0];
        const primary = option?.getAttribute('data-primary') || '#0B1F3A';
        const secondary = option?.getAttribute('data-secondary') || '#1D4ED8';
        applyGradient(primary, secondary);
    };
    const applyFromActiveFlow = () => {
        const activeFlow = document.querySelector('[data-qsl-draw-choice]:checked')?.getAttribute('value') || 'gradient';
        if (activeFlow === 'solid') {
            applySolid();
            return;
        }
        if (activeFlow === 'palette') {
            applyPalette();
            return;
        }
        applyCurrentGradientInputs();
    };

    primaryInput?.addEventListener('input', applyFromActiveFlow);
    secondaryInput?.addEventListener('input', applyFromActiveFlow);
    solidInput?.addEventListener('input', applyFromActiveFlow);
    paletteSelect?.addEventListener('change', applyFromActiveFlow);
    drawFlowChoices.forEach((choice) => {
        choice.addEventListener('change', applyFromActiveFlow);
    });
    applyFromActiveFlow();

    imageInput?.addEventListener('change', () => {
        const file = imageInput.files?.[0];
        if (!file) {
            applyFromActiveFlow();
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            if (typeof reader.result === 'string') {
                previewCard.style.backgroundImage = `linear-gradient(rgba(5, 10, 25, .35), rgba(5, 10, 25, .35)), url('${reader.result}')`;
                previewCard.style.backgroundSize = 'cover';
                previewCard.style.backgroundPosition = 'center';
            }
        };
        reader.readAsDataURL(file);
    });
})();
