(() => {
  const root = document.documentElement;
  const label = document.querySelector('[data-theme-label]');
  const toggleButton = document.querySelector('[data-theme-toggle]');
  const applyTheme = (theme) => {
    root.setAttribute('data-theme', theme);
    if (label) {
      label.textContent = theme === 'dark' ? 'Light mode' : 'Dark mode';
    }
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
