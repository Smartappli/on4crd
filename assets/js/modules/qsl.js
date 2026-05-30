(function () {
  if (window.__qslScriptsInitialized) return;
  window.__qslScriptsInitialized = true;

(() => {
    const navLinks = document.querySelectorAll('[data-qsl-nav-target]');
    const panels = document.querySelectorAll('[data-qsl-panel]');
    if (!navLinks.length || !panels.length) {
        return;
    }

    const activate = (target) => {
        const allowed = ['design', 'create', 'manage'];
        const current = allowed.includes(target) ? target : 'design';
        panels.forEach((panel) => {
            panel.classList.toggle('is-hidden', panel.getAttribute('data-qsl-panel') !== current);
        });
        navLinks.forEach((link) => {
            const isActive = link.getAttribute('data-qsl-nav-target') === current;
            link.classList.toggle('active', isActive);
            link.setAttribute('aria-current', isActive ? 'page' : 'false');
        });
    };

    navLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const target = link.getAttribute('data-qsl-nav-target') || 'design';
            activate(target);
        });
    });

    activate('design');
})();

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

(() => {
    const previewRoot = document.querySelector('[data-qsl-manual-preview]');
    if (!previewRoot) {
        return;
    }

    const card = previewRoot.querySelector('[data-manual-preview-card]');
    if (!card) {
        return;
    }
    const backWrap = previewRoot.querySelector('[data-manual-preview-back-wrap]');
    const frontDetails = previewRoot.querySelectorAll('[data-manual-preview-front-detail]');
    const frontMessage = previewRoot.querySelector('[data-manual-preview-front-message]');
    const templateSource = document.querySelector('select[name="template_name"]');

    const fieldDefaults = {
        qso_call: 'F4XYZ',
        qso_date: '2026-04-12',
        time_on: '09:15',
        band: '20M',
        mode: 'SSB',
        rst_sent: '59',
        rst_recv: '59',
        comment: 'TNX QSO 73',
    };
    const formatPreviewDate = (value) => {
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            const [year, month, day] = value.split('-');
            return `${day}/${month}/${year}`;
        }
        return value;
    };
    const formatPreviewTime = (value) => {
        if (/^\d{2}:\d{2}(:\d{2})?$/.test(value)) {
            return value.slice(0, 5);
        }
        return value;
    };

    const sync = () => {
        Object.keys(fieldDefaults).forEach((field) => {
            const source = document.querySelector(`[data-manual-preview-source="${field}"]`);
            const target = previewRoot.querySelector(`[data-manual-preview-field="${field}"]`);
            if (!target) {
                return;
            }
            const rawValue = source instanceof HTMLInputElement || source instanceof HTMLSelectElement || source instanceof HTMLTextAreaElement
                ? source.value
                : '';
            const value = (rawValue || '').trim();
            let displayValue = value !== '' ? value : fieldDefaults[field];
            if (field === 'qso_date') {
                displayValue = formatPreviewDate(displayValue);
            } else if (field === 'time_on') {
                displayValue = formatPreviewTime(displayValue);
            } else if (field !== 'comment') {
                displayValue = displayValue.toUpperCase();
            }
            target.textContent = displayValue;
            const backTarget = previewRoot.querySelector(`[data-manual-preview-back-field="${field}"]`);
            if (backTarget) {
                backTarget.textContent = displayValue;
            }
        });

        const presetSelect = document.querySelector('[data-manual-preview-source="background_preset_id"]');
        const note = previewRoot.querySelector('[data-manual-preview-note]');
        if (!(presetSelect instanceof HTMLSelectElement)) {
            return;
        }

        const selectedOption = presetSelect.selectedOptions[0];
        const type = selectedOption?.getAttribute('data-bg-type') || 'gradient';
        const imageData = selectedOption?.getAttribute('data-bg-image') || '';
        const primary = selectedOption?.getAttribute('data-bg-primary') || '#0B1F3A';
        const secondary = selectedOption?.getAttribute('data-bg-secondary') || '#1D4ED8';
        if (type === 'image' && imageData !== '') {
            card.style.backgroundImage = `linear-gradient(rgba(5, 10, 25, .35), rgba(5, 10, 25, .35)), url('${imageData}')`;
            card.style.backgroundSize = 'cover';
            card.style.backgroundPosition = 'center';
            if (note) {
                note.textContent = "<?= addslashes($qt('label_bg_image')) ?>";
            }
        } else if (type === 'gradient') {
            card.style.background = `linear-gradient(135deg, ${primary}, ${secondary})`;
            card.style.backgroundSize = '';
            card.style.backgroundPosition = '';
            if (note) {
                note.textContent = previewRoot.getAttribute('data-preview-note') || '';
            }
        } else {
            card.style.background = 'linear-gradient(135deg, #0f172a, #1e293b)';
            card.style.backgroundSize = '';
            card.style.backgroundPosition = '';
            if (note) {
                note.textContent = "<?= addslashes($qt('label_bg_image')) ?>";
            }
        }

        const isDuplex = templateSource instanceof HTMLSelectElement && templateSource.value === 'classic_duplex';
        if (backWrap) {
            backWrap.classList.toggle('is-hidden', !isDuplex);
        }
        frontDetails.forEach((node) => node.classList.toggle('is-hidden', isDuplex));
        if (frontMessage) {
            frontMessage.classList.toggle('is-hidden', !isDuplex);
        }
    };

    document.querySelectorAll('[data-manual-preview-source]').forEach((source) => {
        source.addEventListener('input', sync);
        source.addEventListener('change', sync);
    });
    document.querySelectorAll('[data-qsl-uppercase]').forEach((source) => {
        source.addEventListener('input', () => {
            if (!(source instanceof HTMLInputElement)) {
                return;
            }
            const start = source.selectionStart;
            const end = source.selectionEnd;
            source.value = source.value.toUpperCase();
            if (start !== null && end !== null) {
                source.setSelectionRange(start, end);
            }
        });
    });
    if (templateSource instanceof HTMLSelectElement) {
        templateSource.addEventListener('change', sync);
    }

    sync();
})();

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

(() => {
    const form = document.getElementById('adif-dropzone-form');
    const status = document.getElementById('adif-dropzone-status');
    if (!form || typeof Dropzone === 'undefined') {
        return;
    }

    Dropzone.autoDiscover = false;
    const csrf = form.querySelector('input[name="_csrf"]')?.value || '';
    const action = form.querySelector('input[name="action"]')?.value || 'import_adif';
    const dropzone = new Dropzone('#adif-dropzone', {
        url: window.location.href,
        method: 'post',
        paramName: 'adif_files[]',
        acceptedFiles: '.adi,.adif,text/plain',
        uploadMultiple: false,
        parallelUploads: 6,
        maxFilesize: 8,
        addRemoveLinks: true,
        autoProcessQueue: true,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        params: {
            _csrf: csrf,
            action: action,
        },
        dictDefaultMessage: '',
    });

    dropzone.on('sending', () => {
        if (status) {
            status.textContent = form.getAttribute('data-adif-processing') || ''; 
        }
    });
    dropzone.on('success', (file, response) => {
        const imported = Number(response?.imported || 0);
        const files = Number(response?.files || 1);
        if (status) {
            const template = form.getAttribute('data-adif-imported-status') || '';
            status.textContent = template.replace('{imported}', String(imported)).replace('{files}', String(files));
        }
    });
    dropzone.on('error', (file, message) => {
        const text = typeof message === 'string' ? message : (message?.error || (form.getAttribute('data-adif-import-error') || ''));
        if (status) {
            status.textContent = text;
        }
    });
    dropzone.on('queuecomplete', () => {
        window.setTimeout(() => window.location.reload(), 500);
    });
})();

})();

