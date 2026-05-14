(() => {
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
