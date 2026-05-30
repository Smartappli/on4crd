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

