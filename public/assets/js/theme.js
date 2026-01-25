(() => {
  const stored = localStorage.getItem('thriftstack_theme');
  if (stored) {
    document.documentElement.setAttribute('data-theme', stored);
  }
})();
