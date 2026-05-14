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
