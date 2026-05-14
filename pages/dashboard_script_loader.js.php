(() => {
  const config = window.dashboardConfig || {};
  const renderBase = typeof config.renderBase === 'string' ? config.renderBase : '';
  if (!renderBase) return;

  const previews = Array.from(document.querySelectorAll('[data-widget-preview]'));
  if (previews.length === 0) return;

  const loaded = new Set();
  const loadPreview = async (node) => {
    const widget = node.getAttribute('data-widget-preview') || '';
    if (!widget || loaded.has(widget)) return;
    loaded.add(widget);
    try {
      const res = await fetch(`${renderBase}${encodeURIComponent(widget)}`, { credentials: 'same-origin' });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const html = await res.text();
      node.innerHTML = html;
    } catch (_e) {
      node.innerHTML = `<p class="help">${unavailableMsg}</p>`;
    }
  };

  const panel = document.getElementById('dashboard-widgets-panel');
  if (!panel) return;
  const unavailableMsg = panel.getAttribute('data-widget-unavailable') || 'Widget unavailable.';
  const observer = new MutationObserver(() => {
    if (!panel.classList.contains('is-open')) return;
    previews.forEach((node) => { void loadPreview(node); });
  });
  observer.observe(panel, { attributes: true, attributeFilter: ['class'] });
})();
