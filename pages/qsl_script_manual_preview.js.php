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
