(function () {
  const menuToggle = document.querySelector('.menu-toggle');
  const nav = document.getElementById('main-nav');
  const navBackdrop = document.querySelector('.nav-backdrop');
  const topbar = document.querySelector('.topbar');
  if (menuToggle && nav) {
    let focusBeforeMenu = null;
    const syncMobileHeaderHeight = () => {
      if (!(topbar instanceof HTMLElement)) return;
      document.documentElement.style.setProperty('--mobile-header-height', `${Math.ceil(topbar.getBoundingClientRect().height)}px`);
    };
    const closeMenu = (restoreFocus = false) => {
      menuToggle.setAttribute('aria-expanded', 'false');
      nav.classList.remove('nav-open');
      document.body.classList.remove('menu-open');
      if (navBackdrop instanceof HTMLElement) {
        navBackdrop.hidden = true;
      }
      if (restoreFocus && focusBeforeMenu instanceof HTMLElement) {
        focusBeforeMenu.focus();
      }
      focusBeforeMenu = null;
    };

    syncMobileHeaderHeight();
    if ('ResizeObserver' in window && topbar instanceof HTMLElement) {
      new ResizeObserver(syncMobileHeaderHeight).observe(topbar);
    }

    menuToggle.addEventListener('click', () => {
      const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
      if (!expanded) {
        focusBeforeMenu = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        syncMobileHeaderHeight();
      }
      menuToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      nav.classList.toggle('nav-open');
      const nowOpen = !expanded;
      document.body.classList.toggle('menu-open', nowOpen);
      if (navBackdrop instanceof HTMLElement) {
        navBackdrop.hidden = !nowOpen;
      }
      if (nowOpen) {
        const firstMenuControl = nav.querySelector('a, button, input, select, textarea');
        if (firstMenuControl instanceof HTMLElement) {
          window.setTimeout(() => firstMenuControl.focus(), 0);
        }
      } else {
        focusBeforeMenu = null;
      }
    });

    nav.querySelectorAll('a, button').forEach((node) => node.addEventListener('click', () => closeMenu()));
    if (navBackdrop instanceof HTMLElement) {
      navBackdrop.addEventListener('click', () => closeMenu(true));
    }
    window.addEventListener('resize', () => {
      if (window.innerWidth > 900) {
        closeMenu();
      }
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeMenu(true);
      }
      if (event.key === 'Tab' && nav.classList.contains('nav-open')) {
        const focusable = Array.from(nav.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'))
          .filter((element) => element instanceof HTMLElement && !element.hasAttribute('disabled'));
        if (focusable.length === 0) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    });
  }

  const linkedTaxonomyPairs = [
    ['category', 'subcategory_ref'],
    ['proposal_category', 'proposal_subcategory_ref'],
    ['proposal_theme', 'proposal_subcategory_ref'],
    ['document_category', 'document_subcategory_ref'],
  ];
  const taxonomyParentFromOption = (option) => {
    const explicitParent = option.getAttribute('data-parent-category')
      || option.closest('optgroup')?.getAttribute('data-parent-category')
      || '';
    if (explicitParent !== '') {
      return explicitParent;
    }
    const separatorIndex = option.value.indexOf(':');
    return separatorIndex > -1 ? option.value.slice(0, separatorIndex) : '';
  };
  const syncLinkedTaxonomySelects = (categorySelect, subcategorySelect) => {
    const selectedCategory = categorySelect.value || '';
    let currentSubcategoryAllowed = subcategorySelect.value === '';

    subcategorySelect.querySelectorAll('option').forEach((option) => {
      if (option.value === '') {
        option.hidden = false;
        option.disabled = false;
        return;
      }

      const parentCategory = taxonomyParentFromOption(option);
      const allowed = selectedCategory === '' || parentCategory === '' || parentCategory === selectedCategory;
      option.hidden = !allowed;
      option.disabled = !allowed;
      if (option.selected && allowed) {
        currentSubcategoryAllowed = true;
      }
    });

    subcategorySelect.querySelectorAll('optgroup').forEach((optgroup) => {
      const hasAllowedOption = Array.from(optgroup.querySelectorAll('option')).some((option) => !option.disabled);
      optgroup.hidden = !hasAllowedOption;
      optgroup.disabled = !hasAllowedOption;
    });

    if (!currentSubcategoryAllowed) {
      subcategorySelect.value = '';
    }
  };
  document.querySelectorAll('form').forEach((form) => {
    linkedTaxonomyPairs.forEach(([categoryName, subcategoryName]) => {
      const categorySelect = form.querySelector(`select[name="${categoryName}"]`);
      const subcategorySelect = form.querySelector(`select[name="${subcategoryName}"]`);
      if (!(categorySelect instanceof HTMLSelectElement) || !(subcategorySelect instanceof HTMLSelectElement)) {
        return;
      }

      syncLinkedTaxonomySelects(categorySelect, subcategorySelect);
      categorySelect.addEventListener('change', () => syncLinkedTaxonomySelects(categorySelect, subcategorySelect), true);
    });
  });

  document.querySelectorAll('select.js-auto-submit').forEach((select) => {
    select.addEventListener('change', () => {
      if (select.form) {
        select.form.submit();
      }
    });
  });

  const ideaDialog = document.getElementById('idea-dialog');
  const ideaOpenButtons = document.querySelectorAll('[data-idea-modal-open]');
  if (ideaDialog instanceof HTMLElement && ideaOpenButtons.length > 0) {
    const closeIdeaDialog = () => {
      if (!ideaDialog.hasAttribute('open')) {
        return;
      }
      if (typeof ideaDialog.close === 'function') {
        ideaDialog.close();
        return;
      }
      ideaDialog.removeAttribute('open');
    };

    const openIdeaDialog = () => {
      if (typeof ideaDialog.showModal === 'function') {
        ideaDialog.showModal();
      } else {
        ideaDialog.setAttribute('open', '');
      }
      const firstField = ideaDialog.querySelector('input, textarea, button');
      if (firstField instanceof HTMLElement) {
        window.setTimeout(() => firstField.focus(), 0);
      }
    };

    ideaOpenButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        openIdeaDialog();
      });
    });

    ideaDialog.querySelectorAll('[data-idea-modal-close]').forEach((button) => {
      button.addEventListener('click', closeIdeaDialog);
    });

    ideaDialog.addEventListener('click', (event) => {
      if (event.target === ideaDialog) {
        closeIdeaDialog();
      }
    });
  }

  const formatterCache = new Map();
  const dateFormatterCache = new Map();
  const defaultLocale = document.documentElement.lang || 'fr-FR';
  const getFormatter = (timeZone, locale) => {
    const cacheKey = `${timeZone}|${locale}`;
    if (!formatterCache.has(cacheKey)) {
      formatterCache.set(cacheKey, new Intl.DateTimeFormat(locale, {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        timeZone,
        timeZoneName: 'short',
      }));
    }
    return formatterCache.get(cacheKey);
  };
  const updateClocks = () => {
    const liveClocks = document.querySelectorAll('[data-live-clock]');
    const liveDates = document.querySelectorAll('[data-live-date]');
    if (liveClocks.length === 0 && liveDates.length === 0) {
      return;
    }
    const now = new Date();
    liveClocks.forEach((clockNode) => {
      const zoneValue = clockNode.getAttribute('data-timezone') || 'UTC';
      const localeValue = clockNode.getAttribute('data-locale') || defaultLocale;
      const timeZone = zoneValue === 'local'
        ? Intl.DateTimeFormat().resolvedOptions().timeZone
        : zoneValue;
      clockNode.textContent = getFormatter(timeZone, localeValue).format(now);
      clockNode.setAttribute('datetime', now.toISOString());
    });
    liveDates.forEach((dateNode) => {
      const zoneValue = dateNode.getAttribute('data-timezone') || 'UTC';
      const localeValue = dateNode.getAttribute('data-locale') || defaultLocale;
      const timeZone = zoneValue === 'local'
        ? Intl.DateTimeFormat().resolvedOptions().timeZone
        : zoneValue;
      const cacheKey = `${timeZone}|${localeValue}`;
      if (!dateFormatterCache.has(cacheKey)) {
        dateFormatterCache.set(cacheKey, new Intl.DateTimeFormat(localeValue, {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
          timeZone,
        }));
      }
      dateNode.textContent = dateFormatterCache.get(cacheKey).format(now);
    });
  };
  window.ON4CRDUpdateLiveClocks = updateClocks;
  updateClocks();
  window.setInterval(updateClocks, 1000);

  let deferredInstallPrompt = null;
  const installButtons = document.querySelectorAll('[data-pwa-install]');
  installButtons.forEach((button) => {
    button.hidden = true;
    button.disabled = true;
  });

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    document.documentElement.classList.add('pwa-installable');
    installButtons.forEach((button) => {
      button.hidden = false;
      button.disabled = false;
    });
  });

  const swUrl = document.body?.dataset?.swUrl || '';
  if ('serviceWorker' in navigator && swUrl) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(swUrl).catch(() => {});
    });
  }

  installButtons.forEach((installButton) => {
    installButton.addEventListener('click', async () => {
      if (!deferredInstallPrompt) return;
      deferredInstallPrompt.prompt();
      await deferredInstallPrompt.userChoice;
      deferredInstallPrompt = null;
      document.documentElement.classList.remove('pwa-installable');
      installButtons.forEach((button) => {
        button.hidden = true;
        button.disabled = true;
      });
    });
  });


})();

