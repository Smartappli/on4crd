(function () {
  const menuToggle = document.querySelector('.menu-toggle');
  const nav = document.getElementById('main-nav');
  if (menuToggle && nav) {
    const closeMenu = () => {
      menuToggle.setAttribute('aria-expanded', 'false');
      nav.classList.remove('nav-open');
    };

    menuToggle.addEventListener('click', () => {
      const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
      menuToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      nav.classList.toggle('nav-open');
    });

    nav.querySelectorAll('a, button').forEach((node) => node.addEventListener('click', closeMenu));
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

  const liveClocks = document.querySelectorAll('[data-live-clock]');
  if (liveClocks.length > 0) {
    const formatterCache = new Map();
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
    };
    updateClocks();
    window.setInterval(updateClocks, 1000);
  }

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

  const grid = document.getElementById('dashboard-grid');
  if (!grid || !window.dashboardConfig) {
    return;
  }

  const renderBase = window.dashboardConfig.renderBase;
  const csrf = window.dashboardConfig.csrf;
  let dragged = null;

  function bindCard(card) {
    card.addEventListener('dragstart', () => {
      dragged = card;
      card.classList.add('dragging');
    });
    card.addEventListener('dragend', () => {
      card.classList.remove('dragging');
      dragged = null;
    });
    card.querySelector('.remove-widget')?.addEventListener('click', () => {
      card.remove();
    });
  }

  function createCard(widgetKey, title) {
    const card = document.createElement('article');
    card.className = 'widget-card';
    card.draggable = true;
    card.dataset.widget = widgetKey;
    card.innerHTML = `<header><strong>${title}</strong><button class="ghost remove-widget" type="button">✕</button></header><div class="widget-body">Chargement…</div>`;
    bindCard(card);
    fetch(renderBase + encodeURIComponent(widgetKey), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then((response) => response.text())
      .then((html) => {
        card.querySelector('.widget-body').innerHTML = html;
      })
      .catch(() => {
        card.querySelector('.widget-body').innerHTML = '<p>Impossible de charger le widget.</p>';
      });
    return card;
  }

  grid.querySelectorAll('.widget-card').forEach(bindCard);

  grid.addEventListener('dragover', (event) => {
    event.preventDefault();
    const after = [...grid.querySelectorAll('.widget-card:not(.dragging)')].find((card) => {
      const rect = card.getBoundingClientRect();
      return event.clientY < rect.top + rect.height / 2;
    });
    if (!dragged) return;
    if (!after) {
      grid.appendChild(dragged);
    } else if (after !== dragged) {
      grid.insertBefore(dragged, after);
    }
  });

  document.querySelectorAll('.add-widget').forEach((button) => {
    button.addEventListener('click', () => {
      const key = button.dataset.widget;
      if ([...grid.querySelectorAll('.widget-card')].some((card) => card.dataset.widget === key)) {
        return;
      }
      const card = createCard(key, button.dataset.title || key);
      grid.appendChild(card);
    });
  });

  document.getElementById('save-dashboard')?.addEventListener('click', async () => {
    const widgets = [...grid.querySelectorAll('.widget-card')].map((card) => card.dataset.widget);
    const response = await fetch(window.dashboardConfig.saveUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrf,
      },
      credentials: 'same-origin',
      body: JSON.stringify({ _csrf: csrf, widgets })
    });
    const data = await response.json();
    alert(data.ok ? 'Disposition enregistrée' : (data.error || 'Erreur'));
  });
})();

(function () {
  if (!window.dashboardConfig) return;
  const refreshMs = Number(window.dashboardConfig.refreshMs || 0);
  if (!refreshMs) return;
  setInterval(() => {
    document.querySelectorAll('#dashboard-grid .widget-card[data-widget]').forEach((card) => {
      const widgetKey = card.dataset.widget;
      const body = card.querySelector('.widget-body');
      if (!widgetKey || !body) return;
      fetch(window.dashboardConfig.renderBase + encodeURIComponent(widgetKey), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then((response) => response.text())
        .then((html) => { body.innerHTML = html; })
        .catch(() => {});
    });
  }, refreshMs);
})();
