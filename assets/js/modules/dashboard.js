(function () {
  const grid = document.getElementById('dashboard-grid');
  if (!grid || !window.dashboardConfig) {
    return;
  }

  const renderBase = window.dashboardConfig.renderBase;
  const csrf = window.dashboardConfig.csrf;
  const saveEnabled = Boolean(window.dashboardConfig.saveEnabled);
  const saveButton = document.getElementById('save-dashboard');
  const saveStatus = document.getElementById('dashboard-save-status');
  const addWidgetContainer = document.querySelector('.split-home aside .stack');
  const addWidgetTemplates = new Map();
  let dragged = null;
  let isSaving = false;

  function setSaveStatus(message, isError) {
    if (!saveStatus) return;
    saveStatus.textContent = message;
    saveStatus.classList.toggle('error', Boolean(isError));
  }

  async function saveDashboardLayout() {
    if (!saveEnabled) {
      setSaveStatus('Sauvegarde indisponible (table dashboard_widgets absente).', true);
      return;
    }
    if (isSaving) {
      return;
    }
    isSaving = true;
    if (saveButton) saveButton.disabled = true;
    setSaveStatus('Enregistrementâ€¦', false);

    try {
      const widgets = [...grid.querySelectorAll('.widget-card')].map((card) => {
        const key = card.dataset.widget || '';
        if (!key) return null;
        let config = {};
        if (card.dataset.widgetConfig) {
          try {
            const parsed = JSON.parse(card.dataset.widgetConfig);
            if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
              config = parsed;
            }
          } catch (error) {
            config = {};
          }
        }
        return { key, config };
      }).filter(Boolean);
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
      const data = await response.json().catch(() => ({ ok: false, error: 'RÃ©ponse serveur invalide.' }));
      if (!response.ok || !data.ok) {
        throw new Error(data.error || 'Erreur de sauvegarde');
      }
      setSaveStatus(`Disposition enregistrÃ©e Ã  ${new Date().toLocaleTimeString()}.`, false);
    } catch (error) {
      setSaveStatus(error instanceof Error ? error.message : 'Erreur de sauvegarde.', true);
    } finally {
      if (saveButton) saveButton.disabled = false;
      isSaving = false;
    }
  }

  function bindAddWidgetButton(button) {
    button.addEventListener('click', () => {
      const key = button.dataset.widget;
      if (!key) {
        return;
      }
      if ([...grid.querySelectorAll('.widget-card')].some((card) => card.dataset.widget === key)) {
        return;
      }
      const card = createCard(key, button.dataset.title || key);
      grid.appendChild(card);
      button.closest('.widget-card')?.remove();
      saveDashboardLayout();
    });
  }

  function addWidgetOption(widgetKey, title) {
    if (!addWidgetContainer || !widgetKey) {
      return;
    }
    const existing = [...addWidgetContainer.querySelectorAll('.add-widget')]
      .some((button) => button.dataset.widget === widgetKey);
    if (existing) {
      return;
    }

    const template = addWidgetTemplates.get(widgetKey) || {};
    const widgetTitle = title || template.title || widgetKey;
    const article = document.createElement('article');
    article.className = 'widget-card';
    article.innerHTML = `<header><strong></strong></header><p class="help"></p><div class="widget-body widget-preview"></div><button class="button small add-widget" type="button">Ajouter</button>`;
    const titleNode = article.querySelector('header strong');
    if (titleNode) {
      titleNode.textContent = widgetTitle;
    }
    const helpNode = article.querySelector('.help');
    const description = template.description || '';
    if (helpNode) {
        if (description !== '') {
        helpNode.textContent = description;
      } else {
        helpNode.remove();
      }
    }
    const previewNode = article.querySelector('.widget-preview');
    if (previewNode) {
      previewNode.innerHTML = template.previewHtml || '<p class="help">PrÃ©visualisation indisponible.</p>';
    }
    const button = article.querySelector('.add-widget');
    if (!button) {
      return;
    }
    button.dataset.widget = widgetKey;
    button.dataset.title = widgetTitle;
    bindAddWidgetButton(button);
    addWidgetContainer.appendChild(article);
  }

  function bindCard(card) {
    card.addEventListener('dragstart', () => {
      dragged = card;
      card.classList.add('dragging');
    });
    card.addEventListener('dragend', () => {
      card.classList.remove('dragging');
      dragged = null;
      saveDashboardLayout();
    });
    card.querySelector('.remove-widget')?.setAttribute('aria-label', 'Retirer le widget');
    card.querySelector('.remove-widget')?.addEventListener('click', () => {
      const widgetKey = card.dataset.widget || '';
      const widgetTitle = card.querySelector('header strong')?.textContent?.trim() || widgetKey;
      card.remove();
      addWidgetOption(widgetKey, widgetTitle);
      saveDashboardLayout();
    });
  }

  function createCard(widgetKey, title) {
    const card = document.createElement('article');
    card.className = 'widget-card';
    card.draggable = true;
    card.dataset.widget = widgetKey;
    card.dataset.widgetConfig = '{}';
    card.classList.add('is-loading');
    card.innerHTML = '<header><strong></strong><button class="ghost remove-widget" type="button" aria-label="Retirer le widget">âœ•</button></header><div class="widget-body">Chargementâ€¦</div>';
    const titleNode = card.querySelector('header strong');
    if (titleNode) {
      titleNode.textContent = title;
    }
    bindCard(card);
    fetch(renderBase + encodeURIComponent(widgetKey), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then((response) => response.text())
      .then((html) => {
        card.querySelector('.widget-body').innerHTML = html;
        card.classList.remove('is-loading');
      })
      .catch(() => {
        card.querySelector('.widget-body').innerHTML = '<p>Impossible de charger le widget.</p>';
        card.classList.add('is-error');
        card.classList.remove('is-loading');
      });
    return card;
  }

  grid.querySelectorAll('.widget-card').forEach(bindCard);
  document.querySelectorAll('.add-widget').forEach((button) => {
    const key = button.dataset.widget || '';
    if (key !== '' && !addWidgetTemplates.has(key)) {
      addWidgetTemplates.set(key, {
        title: button.dataset.title || key,
        description: button.closest('.widget-card')?.querySelector('.help')?.textContent?.trim() || '',
        previewHtml: button.closest('.widget-card')?.querySelector('.widget-preview')?.innerHTML || ''
      });
    }
    bindAddWidgetButton(button);
  });

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

  saveButton?.addEventListener('click', saveDashboardLayout);
})();

(function () {
  if (!window.dashboardConfig) return;
  const refreshMs = Number(window.dashboardConfig.refreshMs || 0);
  if (!refreshMs) return;

  const inFlightWidgets = new Set();
  const visibleCards = new Set();

  const refreshCard = (card) => {
    const widgetKey = card.dataset.widget;
    const body = card.querySelector('.widget-body');
    if (!widgetKey || !body || inFlightWidgets.has(widgetKey)) return;

    inFlightWidgets.add(widgetKey);
    fetch(window.dashboardConfig.renderBase + encodeURIComponent(widgetKey), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then((response) => response.text())
      .then((html) => { body.innerHTML = html; })
      .catch(() => {})
      .finally(() => {
        inFlightWidgets.delete(widgetKey);
      });
  };

  const refreshVisibleWidgets = () => {
    if (document.visibilityState === 'hidden') {
      return;
    }

    visibleCards.forEach((card) => {
      if (!(card instanceof HTMLElement) || !card.isConnected) return;
      refreshCard(card);
    });
  };

  const cards = Array.from(document.querySelectorAll('#dashboard-grid .widget-card[data-widget]'));
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        const widgetKey = entry.target instanceof HTMLElement ? (entry.target.dataset.widget || '') : '';
        if (!widgetKey) return;
        if (entry.isIntersecting) {
          visibleCards.add(entry.target);
          if (document.visibilityState === 'visible') {
            refreshCard(entry.target);
          }
        } else {
          visibleCards.delete(entry.target);
        }
      });
    }, { root: null, threshold: 0.05 });

    cards.forEach((card) => observer.observe(card));
  } else {
    const isCardVisible = (card) => {
      const rect = card.getBoundingClientRect();
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
      const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
      return rect.bottom >= 0 && rect.right >= 0 && rect.top <= viewportHeight && rect.left <= viewportWidth;
    };

    const updateVisibleWidgets = () => {
      visibleCards.clear();
      cards.forEach((card) => {
        const widgetKey = card.dataset.widget || '';
        if (widgetKey && isCardVisible(card)) {
          visibleCards.add(card);
        }
      });
    };

    updateVisibleWidgets();
    window.addEventListener('scroll', updateVisibleWidgets, { passive: true });
    window.addEventListener('resize', updateVisibleWidgets);
  }

  setInterval(refreshVisibleWidgets, refreshMs);
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      refreshVisibleWidgets();
    }
  });
})();



// Dashboard panel controls(() => {
  const panel = document.getElementById('dashboard-widgets-panel');
  const backdrop = document.getElementById('dashboard-widgets-backdrop');
  const openBtn = document.getElementById('open-widgets-panel');
  const closeBtn = document.getElementById('close-widgets-panel');
  if (!panel || !backdrop || !openBtn || !closeBtn) return;

  const open = () => {
    panel.classList.add('is-open');
    panel.setAttribute('aria-hidden', 'false');
    backdrop.hidden = false;
    openBtn.setAttribute('aria-expanded', 'true');
  };
  const close = () => {
    panel.classList.remove('is-open');
    panel.setAttribute('aria-hidden', 'true');
    backdrop.hidden = true;
    openBtn.setAttribute('aria-expanded', 'false');
  };

  openBtn.addEventListener('click', open);
  closeBtn.addEventListener('click', close);
  backdrop.addEventListener('click', close);
})();

// Dashboard fullscreen control(() => {
  const fsBtn = document.getElementById('dashboard-fullscreen-toggle');
  const shell = document.getElementById('dashboard-shell');
  if (!fsBtn || !shell || !document.fullscreenEnabled) return;

  fsBtn.addEventListener('click', async () => {
    try {
      if (document.fullscreenElement) {
        await document.exitFullscreen();
      } else {
        await shell.requestFullscreen();
      }
    } catch (_e) {
      // no-op
    }
  });
})();

// Dashboard lazy widget previews(() => {
  const config = window.dashboardConfig || {};
  const renderBase = typeof config.renderBase === 'string' ? config.renderBase : '';
  if (!renderBase) return;

  const previews = Array.from(document.querySelectorAll('[data-widget-preview]'));
  if (previews.length === 0) return;

  const loaded = new Set();
  const loading = new Set();
  const loadPreview = async (node) => {
    const widget = node.getAttribute('data-widget-preview') || '';
    if (!widget || loaded.has(widget) || loading.has(widget)) return;
    loading.add(widget);
    try {
      const res = await fetch(`${renderBase}${encodeURIComponent(widget)}`, { credentials: 'same-origin' });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const html = await res.text();
      node.innerHTML = html;
      loaded.add(widget);
    } catch (_e) {
      node.innerHTML = `<p class="help">${unavailableMsg}</p>`;
    } finally {
      loading.delete(widget);
    }
  };

  const panel = document.getElementById('dashboard-widgets-panel');
  if (!panel) return;
  const unavailableMsg = panel.getAttribute('data-widget-unavailable') || 'Widget unavailable.';
  const lazyLoadVisiblePreviews = () => {
    if (!panel.classList.contains('is-open')) return;
    previews.forEach((node) => {
      if (!node.isConnected) return;
      const rect = node.getBoundingClientRect();
      if (rect.width <= 0 || rect.height <= 0) return;
      void loadPreview(node);
    });
  };

  if ('IntersectionObserver' in window) {
    const previewObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting && panel.classList.contains('is-open')) {
          void loadPreview(entry.target);
        }
      });
    }, { root: null, threshold: 0.05 });
    previews.forEach((node) => previewObserver.observe(node));
  }

  const observer = new MutationObserver(() => {
    lazyLoadVisiblePreviews();
  });
  observer.observe(panel, { attributes: true, attributeFilter: ['class'] });
  lazyLoadVisiblePreviews();
})();

