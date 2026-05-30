(function () {
  const calendarEl = document.getElementById('admin-events-calendar');
  if (!calendarEl || !window.FullCalendar) return;

  const config = calendarEl.dataset.calendarConfig ? JSON.parse(calendarEl.dataset.calendarConfig) : {};
  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale: config.locale || document.documentElement.lang || 'fr',
    firstDay: 1,
    height: 'auto',
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,listMonth'
    },
    buttonText: config.buttonText || {},
    events: config.eventsUrl || ''
  });

  calendar.render();
})();
