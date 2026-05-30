(function () {
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
        if (titleNode) titleNode.textContent = chip.dataset.title || 'Ã‰vÃ©nement';
        if (summaryNode) summaryNode.textContent = chip.dataset.summary || 'Aucun rÃ©sumÃ© disponible.';
        if (startNode) startNode.textContent = chip.dataset.start || '';
        if (endNode) endNode.textContent = chip.dataset.end || '';
        if (locationNode) locationNode.textContent = chip.dataset.location || 'Ã€ confirmer';
        if (detailLinkNode) detailLinkNode.setAttribute('href', chip.dataset.detailUrl || '#');
        if (externalLinkNode) {
          const url = chip.dataset.externalUrl || '';
          externalLinkNode.setAttribute('href', url || '#');
          externalLinkNode.classList.toggle('is-hidden', !url);
        }
      });
    });
  }

})();

(function () {
  const calendarEl = document.getElementById('events-calendar');
  if (!calendarEl) return;

  const config = calendarEl.dataset.calendarConfig ? JSON.parse(calendarEl.dataset.calendarConfig) : {};
  if (!window.FullCalendar) {
    const message = config.loadError || 'Calendar unavailable.';
    calendarEl.insertAdjacentHTML('beforeend', `<p class="help">${message}</p>`);
    return;
  }

  const detail = {
    title: document.getElementById('event-detail-title'),
    summary: document.getElementById('event-detail-summary'),
    start: document.getElementById('event-detail-start'),
    end: document.getElementById('event-detail-end'),
    location: document.getElementById('event-detail-location'),
    link: document.getElementById('event-detail-link'),
    external: document.getElementById('event-detail-external')
  };

  const updateDetails = (event) => {
    const props = event.extendedProps || {};
    if (detail.title) detail.title.textContent = event.title || config.eventLabel || 'Evénement';
    if (detail.summary) detail.summary.textContent = props.summary || config.noSummary || '';
    if (detail.start) detail.start.textContent = props.startLabel || '';
    if (detail.end) detail.end.textContent = props.endLabel || '';
    if (detail.location) detail.location.textContent = props.location || config.locationTbd || '';
    if (detail.link) detail.link.setAttribute('href', event.url || '#');
    if (detail.external) {
      const externalUrl = props.externalUrl || '';
      detail.external.setAttribute('href', externalUrl || '#');
      detail.external.classList.toggle('is-hidden', !externalUrl);
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

