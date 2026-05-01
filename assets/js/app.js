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


  const detailPanel = document.getElementById('event-detail');
  if (detailPanel) {
    const titleNode = document.getElementById('event-detail-title');
    const summaryNode = document.getElementById('event-detail-summary');
    const startNode = document.getElementById('event-detail-start');
    const endNode = document.getElementById('event-detail-end');
    const locationNode = document.getElementById('event-detail-location');
    const detailLinkNode = document.getElementById('event-detail-link');
    const externalLinkNode = document.getElementById('event-detail-external');

    document.querySelectorAll('.event-chip[data-event-id]').forEach((chip) => {
      chip.addEventListener('click', () => {
        if (titleNode) titleNode.textContent = chip.dataset.title || 'Événement';
        if (summaryNode) summaryNode.textContent = chip.dataset.summary || 'Aucun résumé disponible.';
        if (startNode) startNode.textContent = chip.dataset.start || '';
        if (endNode) endNode.textContent = chip.dataset.end || '';
        if (locationNode) locationNode.textContent = chip.dataset.location || 'À confirmer';
        if (detailLinkNode) detailLinkNode.setAttribute('href', chip.dataset.detailUrl || '#');
        if (externalLinkNode) {
          const url = chip.dataset.externalUrl || '';
          externalLinkNode.setAttribute('href', url || '#');
          externalLinkNode.classList.toggle('is-hidden', !url);
        }
      });
    });
  }

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
    setSaveStatus('Enregistrement…', false);

    try {
      const widgets = [...grid.querySelectorAll('.widget-card')].map((card) => card.dataset.widget).filter(Boolean);
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
      if (!response.ok || !data.ok) {
        throw new Error(data.error || 'Erreur de sauvegarde');
      }
      setSaveStatus('Disposition enregistrée.', false);
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
      previewNode.innerHTML = template.previewHtml || '<p class="help">Prévisualisation indisponible.</p>';
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

(function () {
  const textareas = document.querySelectorAll('textarea:not([data-wysiwyg="off"])');
  if (!textareas.length) return;
  const currentRoute = new URLSearchParams(window.location.search).get('route') || 'home';

  const syncHandlers = [];
  const toolbarButtons = [
    { label: 'B', command: 'bold', title: 'Gras' },
    { label: 'I', command: 'italic', title: 'Italique' },
    { label: 'U', command: 'underline', title: 'Souligné' },
    { label: '• Liste', command: 'insertUnorderedList', title: 'Liste à puces' },
    { label: '1. Liste', command: 'insertOrderedList', title: 'Liste numérotée' },
    { label: 'Lien', command: 'createLink', title: 'Insérer un lien' },
  ];

  const applyCommand = (editor, command) => {
    editor.focus();
    if (command === 'createLink') {
      const url = window.prompt('URL du lien (https://...)', 'https://');
      if (!url) return;
      document.execCommand('createLink', false, url);
      return;
    }
    document.execCommand(command, false, null);
  };

  let mammothLoader = null;
  const loadMammoth = async () => {
    if (window.mammoth) return window.mammoth;
    if (!mammothLoader) {
      mammothLoader = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/mammoth@1.8.0/mammoth.browser.min.js';
        script.async = true;
        script.onload = () => resolve(window.mammoth);
        script.onerror = () => reject(new Error('Impossible de charger le convertisseur Word.'));
        document.head.appendChild(script);
      });
    }
    return mammothLoader;
  };

  textareas.forEach((textarea, index) => {
    if (textarea.dataset.wysiwygApplied === '1') return;
    textarea.dataset.wysiwygApplied = '1';

    const wrapper = document.createElement('div');
    wrapper.className = 'wysiwyg';

    const toolbar = document.createElement('div');
    toolbar.className = 'wysiwyg-toolbar';

    toolbarButtons.forEach((buttonConfig) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'ghost small';
      button.textContent = buttonConfig.label;
      button.title = buttonConfig.title;
      button.addEventListener('click', () => applyCommand(editor, buttonConfig.command));
      toolbar.appendChild(button);
    });

    const editor = document.createElement('div');
    editor.className = 'wysiwyg-editor';
    editor.contentEditable = 'true';
    editor.setAttribute('role', 'textbox');
    editor.setAttribute('aria-multiline', 'true');
    editor.setAttribute('data-wysiwyg-editor-index', String(index));
    editor.innerHTML = textarea.value && textarea.value.trim() !== '' ? textarea.value : '<p><br></p>';

    const sync = () => {
      textarea.value = editor.innerHTML;
    };

    if (currentRoute === 'admin_news' && textarea.name === 'content') {
      const importButton = document.createElement('button');
      importButton.type = 'button';
      importButton.className = 'ghost small';
      importButton.textContent = 'Importer Word';
      importButton.title = 'Importer un document Word (.docx)';

      const fileInput = document.createElement('input');
      fileInput.type = 'file';
      fileInput.accept = '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document';
      fileInput.hidden = true;

      importButton.addEventListener('click', () => fileInput.click());
      fileInput.addEventListener('change', async () => {
        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (!file) return;

        const extension = file.name.toLowerCase();
        if (extension.endsWith('.doc')) {
          window.alert('Le format .doc n’est pas supporté directement. Merci d’enregistrer en .docx puis de réimporter.');
          return;
        }

        try {
          const mammoth = await loadMammoth();
          if (!mammoth) {
            throw new Error('Convertisseur indisponible.');
          }
          const arrayBuffer = await file.arrayBuffer();
          const result = await mammoth.convertToHtml({ arrayBuffer });
          editor.innerHTML = result.value || '<p><br></p>';
          sync();
        } catch (error) {
          window.alert('Import Word impossible pour le moment.');
        } finally {
          fileInput.value = '';
        }
      });

      toolbar.appendChild(importButton);
      toolbar.appendChild(fileInput);
    }

    editor.addEventListener('input', sync);
    editor.addEventListener('blur', sync);
    syncHandlers.push(sync);

    textarea.classList.add('wysiwyg-source');
    textarea.insertAdjacentElement('beforebegin', wrapper);
    wrapper.appendChild(toolbar);
    wrapper.appendChild(editor);
  });

  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => {
      syncHandlers.forEach((sync) => sync());
    });
  });
})();
