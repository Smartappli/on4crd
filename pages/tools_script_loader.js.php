    const toolLinks = document.querySelectorAll('[data-tool-target]');
    const toolsContent = document.getElementById('tools-content');
    const toolPanelsCache = new Map();
    const toolPanelRequests = new Map();
    const knownToolIds = new Set(['tool-grid']);
    toolLinks.forEach((link) => {
        const targetId = link.getAttribute('data-tool-target') || '';
        if (/^tool-[a-z0-9-]+$/.test(targetId)) {
            knownToolIds.add(targetId);
        }
    });
    document.querySelectorAll('[data-tool-panel]').forEach((panel) => toolPanelsCache.set(panel.id, panel));

    const getToolPanels = () => Array.from(document.querySelectorAll('[data-tool-panel]'));

    const loadToolPanel = async (id) => {
        if (!/^tool-[a-z0-9-]+$/.test(id)) {
            return null;
        }
        if (!knownToolIds.has(id)) {
            return null;
        }
        if (toolPanelsCache.has(id)) {
            return toolPanelsCache.get(id) ?? null;
        }
        if (toolPanelRequests.has(id)) {
            return toolPanelRequests.get(id) ?? null;
        }
        if (!(toolsContent instanceof HTMLElement)) {
            return null;
        }

        const endpoint = new URL(window.location.href);
        endpoint.hash = '';
        endpoint.searchParams.set('route', 'tools');
        endpoint.searchParams.set('ajax', 'tool_panel');
        endpoint.searchParams.set('id', id);

        const request = fetch(endpoint.toString(), {
            headers: { 'Accept': 'text/html' },
            credentials: 'same-origin',
        }).then(async (response) => {
            if (!response.ok) {
                return null;
            }

            const html = await response.text();
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const panel = wrapper.firstElementChild;
            if (!(panel instanceof HTMLElement) || panel.id !== id) {
                return null;
            }

            panel.classList.add('is-hidden');
            toolsContent.appendChild(panel);
            refreshDomRefs();
            toolPanelsCache.set(id, panel);
            return panel;
        }).finally(() => {
            toolPanelRequests.delete(id);
        });

        toolPanelRequests.set(id, request);
        return request;
    };

    let activeToolRequestToken = 0;

    const setActiveTool = async (requestedId) => {
        const requestToken = ++activeToolRequestToken;
        let id = requestedId;
        if (!id) {
            id = 'tool-grid';
        }
        if (!knownToolIds.has(id)) {
            id = 'tool-grid';
        }

        let activePanel = null;
        try {
            activePanel = await loadToolPanel(id);
        } catch (_) {
            activePanel = null;
        }
        if (activePanel === null && id !== 'tool-grid') {
            id = 'tool-grid';
            try {
                activePanel = await loadToolPanel(id);
            } catch (_) {
                activePanel = null;
            }
        }
        if (activePanel === null || requestToken !== activeToolRequestToken) {
            setError(i18n.err_tool_load);
            return;
        }

        clearError();
        initToolIfNeeded(id);
        getToolPanels().forEach((panel) => {
            panel.classList.toggle('is-hidden', panel.id !== id);
        });
        toolLinks.forEach((link) => {
            const isActive = link.getAttribute('data-tool-target') === id;
            link.classList.toggle('is-active', isActive);
        });
    };
    const initialTool = window.location.hash ? window.location.hash.slice(1) : 'tool-grid';
    setActiveTool(initialTool);
    toolLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const targetId = link.getAttribute('data-tool-target') || '';
            if (targetId === '') return;
            event.preventDefault();
            window.history.replaceState(null, '', `#${targetId}`);
            setActiveTool(targetId);
        });
    });
    window.addEventListener('hashchange', () => {
        const hashTool = window.location.hash ? window.location.hash.slice(1) : 'tool-grid';
        setActiveTool(hashTool);
    });
