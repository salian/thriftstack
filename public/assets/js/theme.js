(() => {
  const root = document.documentElement;
  const toggleButton = document.querySelector('[data-theme-toggle]');
  const applyTheme = (theme) => {
    root.setAttribute('data-theme', theme);
  };

  const stored = localStorage.getItem('thriftstack_theme');
  if (stored) {
    applyTheme(stored);
  }

  if (toggleButton) {
    toggleButton.addEventListener('click', () => {
      const current = root.getAttribute('data-theme') || 'light';
      const next = current === 'dark' ? 'light' : 'dark';
      applyTheme(next);
      localStorage.setItem('thriftstack_theme', next);
    });
  }
})();
