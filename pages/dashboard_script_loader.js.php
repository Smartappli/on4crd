(() => {
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
