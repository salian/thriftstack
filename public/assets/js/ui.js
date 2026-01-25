(() => {
  const page = document.querySelector('.page');
  const toggle = document.querySelector('[data-sidebar-toggle]');
  if (!page || !toggle) {
    return;
  }

  const stored = localStorage.getItem('thriftstack_sidebar');
  if (stored === 'collapsed') {
    page.classList.add('sidebar-collapsed');
    toggle.setAttribute('aria-pressed', 'true');
  }

  toggle.addEventListener('click', () => {
    const collapsed = page.classList.toggle('sidebar-collapsed');
    toggle.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
    localStorage.setItem('thriftstack_sidebar', collapsed ? 'collapsed' : 'expanded');
  });
})();
