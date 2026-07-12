(function () {
  const calendarEl = document.getElementById('admin-events-calendar');
  if (!calendarEl || !window.FullCalendar) return;

  const config = calendarEl.dataset.calendarConfig ? JSON.parse(calendarEl.dataset.calendarConfig) : {};
  const compact = window.matchMedia('(max-width: 760px)').matches;
  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale: config.locale || document.documentElement.lang || 'fr',
    firstDay: 1,
    height: 'auto',
    initialView: compact ? 'listMonth' : 'dayGridMonth',
    headerToolbar: compact ? {
      left: 'prev,next',
      center: 'title',
      right: 'listMonth'
    } : {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,listMonth'
    },
    buttonText: config.buttonText || {},
    events: config.eventsUrl || ''
  });

  calendar.render();
})();
