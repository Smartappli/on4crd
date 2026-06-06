(function () {
  const menuToggle = document.querySelector('.menu-toggle');
  const nav = document.getElementById('main-nav');
  const navBackdrop = document.querySelector('.nav-backdrop');
  if (menuToggle && nav) {
    const closeMenu = () => {
      menuToggle.setAttribute('aria-expanded', 'false');
      nav.classList.remove('nav-open');
      document.body.classList.remove('menu-open');
      if (navBackdrop instanceof HTMLElement) {
        navBackdrop.hidden = true;
      }
    };

    menuToggle.addEventListener('click', () => {
      const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
      menuToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      nav.classList.toggle('nav-open');
      const nowOpen = !expanded;
      document.body.classList.toggle('menu-open', nowOpen);
      if (navBackdrop instanceof HTMLElement) {
        navBackdrop.hidden = !nowOpen;
      }
    });

    nav.querySelectorAll('a, button').forEach((node) => node.addEventListener('click', closeMenu));
    if (navBackdrop instanceof HTMLElement) {
      navBackdrop.addEventListener('click', closeMenu);
    }
    window.addEventListener('resize', () => {
      if (window.innerWidth > 900) {
        closeMenu();
      }
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeMenu();
      }
    });
  }

  document.querySelectorAll('select.js-auto-submit').forEach((select) => {
    select.addEventListener('change', () => {
      if (select.form) {
        select.form.submit();
      }
    });
  });

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

