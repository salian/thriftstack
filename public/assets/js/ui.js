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

(() => {
  const page = document.querySelector('.page');
  if (!page) {
    return;
  }

  const tooltip = document.createElement('div');
  tooltip.className = 'sidebar-tooltip';
  document.body.appendChild(tooltip);

  const showTooltip = (target) => {
    if (!page.classList.contains('sidebar-collapsed')) {
      tooltip.classList.remove('is-visible');
      return;
    }
    const text = target.getAttribute('data-tooltip');
    if (!text) {
      tooltip.classList.remove('is-visible');
      return;
    }
    tooltip.textContent = text;
    const rect = target.getBoundingClientRect();
    tooltip.style.left = `${rect.right + 10}px`;
    tooltip.style.top = `${rect.top + rect.height / 2}px`;
    tooltip.classList.add('is-visible');
  };

  const hideTooltip = () => {
    tooltip.classList.remove('is-visible');
  };

  document.addEventListener('mouseover', (event) => {
    const target = event.target.closest('[data-tooltip]');
    if (target) {
      showTooltip(target);
    }
  });

  document.addEventListener('mouseout', (event) => {
    if (event.target.closest('[data-tooltip]')) {
      hideTooltip();
    }
  });

  document.addEventListener('focusin', (event) => {
    const target = event.target.closest('[data-tooltip]');
    if (target) {
      showTooltip(target);
    }
  });

  document.addEventListener('focusout', (event) => {
    if (event.target.closest('[data-tooltip]')) {
      hideTooltip();
    }
  });
})();
