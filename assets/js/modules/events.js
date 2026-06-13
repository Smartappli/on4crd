(function () {
  const detailPanel = document.getElementById('event-detail');
  if (detailPanel) {
    const nextPanel = document.getElementById('events-next-event');
    const titleNode = document.getElementById('event-detail-title');
    const summaryNode = document.getElementById('event-detail-summary');
    const startNode = document.getElementById('event-detail-start');
    const endNode = document.getElementById('event-detail-end');
    const locationNode = document.getElementById('event-detail-location');
    const actionsNode = document.getElementById('event-detail-actions');
    const externalLinkNode = document.getElementById('event-detail-external');

    document.querySelectorAll('.event-chip[data-event-id]').forEach((chip) => {
      chip.addEventListener('click', () => {
        detailPanel.hidden = false;
        detailPanel.classList.remove('is-hidden');
        if (nextPanel) {
          nextPanel.hidden = true;
          nextPanel.classList.add('is-hidden');
        }
        if (titleNode) titleNode.textContent = chip.dataset.title || 'Ã‰vÃ©nement';
        if (summaryNode) summaryNode.textContent = chip.dataset.summary || 'Aucun rÃ©sumÃ© disponible.';
        if (startNode) startNode.textContent = chip.dataset.start || '';
        if (endNode) endNode.textContent = chip.dataset.end || '';
        if (locationNode) locationNode.textContent = chip.dataset.location || 'Ã€ confirmer';
        if (externalLinkNode) {
          const url = chip.dataset.externalUrl || '';
          externalLinkNode.setAttribute('href', url || '#');
          externalLinkNode.classList.toggle('is-hidden', !url);
          if (actionsNode) actionsNode.classList.toggle('is-hidden', !url);
        }
      });
    });
  }

})();

(function () {
  const dialog = document.getElementById('events-proposal-dialog');
  const openButtons = document.querySelectorAll('[data-event-proposal-open]');
  if (!dialog || openButtons.length === 0) {
    return;
  }

  if (typeof HTMLDialogElement === 'undefined' || !(dialog instanceof HTMLDialogElement)) {
    return;
  }

  const closeButtons = dialog.querySelectorAll('[data-event-proposal-close]');
  const form = dialog.querySelector('[data-event-proposal-form]');
  const firstField = dialog.querySelector('input[name="proposal_title"]');

  const openDialog = (event) => {
    event.preventDefault();
    if (!dialog.open) {
      dialog.showModal();
    }
    if (firstField instanceof HTMLElement) {
      firstField.focus();
    }
  };

  const closeDialog = () => {
    if (dialog.open) {
      dialog.close();
    }
  };

  const fieldValue = (name) => {
    const field = dialog.querySelector(`[name="${name}"]`);
    return field && 'value' in field ? String(field.value).trim() : '';
  };

  const fieldLabel = (name) => {
    const field = dialog.querySelector(`[name="${name}"]`);
    const label = field ? field.closest('label') : null;
    const labelText = label ? label.querySelector('span') : null;
    return labelText ? labelText.textContent.trim() : name;
  };

  openButtons.forEach((button) => {
    button.addEventListener('click', openDialog);
  });

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeDialog);
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      closeDialog();
    }
  });

  if (form && String(form.getAttribute('method') || '').toLowerCase() === 'dialog') {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const recipient = form.dataset.eventProposalRecipient || 'crdurnal@gmail.com';
      const subject = form.dataset.eventProposalSubject || '';
      const intro = form.dataset.eventProposalIntro || '';
      const fields = [
        'proposal_title',
        'proposal_datetime',
        'proposal_location',
        'proposal_description',
        'proposal_contact'
      ];
      const body = [
        intro,
        ...fields.map((name) => `${fieldLabel(name)}: ${fieldValue(name)}`)
      ].filter(Boolean).join('\n');

      window.location.href = `mailto:${recipient}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
      closeDialog();
    });
  }
})();

(function () {
  const calendarEl = document.getElementById('events-calendar');
  if (!calendarEl) return;
  const detailPanel = document.getElementById('event-detail');
  const nextPanel = document.getElementById('events-next-event');

  const config = calendarEl.dataset.calendarConfig ? JSON.parse(calendarEl.dataset.calendarConfig) : {};
  if (!window.FullCalendar) {
    const message = config.loadError || 'Calendar unavailable.';
    const help = document.createElement('p');
    help.className = 'help';
    help.textContent = message;
    calendarEl.appendChild(help);
    return;
  }

  const detail = {
    title: document.getElementById('event-detail-title'),
    summary: document.getElementById('event-detail-summary'),
    start: document.getElementById('event-detail-start'),
    end: document.getElementById('event-detail-end'),
    location: document.getElementById('event-detail-location'),
    actions: document.getElementById('event-detail-actions'),
    external: document.getElementById('event-detail-external'),
    imageWrap: document.getElementById('event-detail-image-wrap'),
    image: document.getElementById('event-detail-image')
  };

  const updateDetails = (event) => {
    const props = event.extendedProps || {};
    if (detailPanel) {
      detailPanel.hidden = false;
      detailPanel.classList.remove('is-hidden');
    }
    if (nextPanel) {
      nextPanel.hidden = true;
      nextPanel.classList.add('is-hidden');
    }
    if (detail.title) detail.title.textContent = event.title || config.eventLabel || 'Evénement';
    if (detail.summary) detail.summary.textContent = props.summary || config.noSummary || '';
    if (detail.start) detail.start.textContent = props.startLabel || '';
    if (detail.end) detail.end.textContent = props.endLabel || '';
    if (detail.location) detail.location.textContent = props.location || config.locationTbd || '';
    if (detail.image && detail.imageWrap) {
      const imageUrl = props.imageUrl || '';
      detail.image.setAttribute('alt', event.title || config.eventLabel || 'Evénement');
      if (imageUrl) {
        detail.image.setAttribute('src', imageUrl);
      } else {
        detail.image.removeAttribute('src');
      }
      detail.imageWrap.classList.toggle('is-hidden', !imageUrl);
    }
    if (detail.external) {
      const externalUrl = props.externalUrl || '';
      detail.external.setAttribute('href', externalUrl || '#');
      detail.external.classList.toggle('is-hidden', !externalUrl);
      if (detail.actions) detail.actions.classList.toggle('is-hidden', !externalUrl);
    }
  };
  const formatDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale: config.locale || document.documentElement.lang || 'fr',
    firstDay: 1,
    height: 'auto',
    initialView: config.initialView || 'dayGridMonth',
    initialDate: config.initialDate || undefined,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,listMonth'
    },
    buttonText: config.buttonText || {},
    events: config.eventsUrl || '',
    eventClick(info) {
      info.jsEvent.preventDefault();
      updateDetails(info.event);
    },
    datesSet(info) {
      const params = new URLSearchParams(window.location.search);
      const viewMap = {
        dayGridMonth: 'month',
        timeGridWeek: 'week',
        listMonth: 'list'
      };
      const route = params.get('route') || 'events';
      const currentView = viewMap[info.view.type] || 'month';
      const monthAnchor = info.view.currentStart instanceof Date ? info.view.currentStart : info.start;
      const weekAnchor = info.start instanceof Date ? info.start : monthAnchor;
      params.set('route', route);
      params.set('view', currentView);
      params.set('ym', formatDate(monthAnchor).slice(0, 7));
      params.set('week', formatDate(weekAnchor));
      history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
    }
  });
  calendar.render();
})();

