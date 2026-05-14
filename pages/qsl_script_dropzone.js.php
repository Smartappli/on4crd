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
