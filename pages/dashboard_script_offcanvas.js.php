(() => {
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
