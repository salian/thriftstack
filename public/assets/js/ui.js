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

(() => {
  const menus = Array.from(document.querySelectorAll('.nav-user-menu'));
  if (menus.length === 0) {
    return;
  }

  document.addEventListener('click', (event) => {
    for (const menu of menus) {
      if (!menu.hasAttribute('open')) {
        continue;
      }
      const link = event.target.closest('a');
      if (link && menu.contains(link)) {
        menu.removeAttribute('open');
        continue;
      }
      if (!menu.contains(event.target)) {
        menu.removeAttribute('open');
      }
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
      return;
    }
    for (const menu of menus) {
      if (menu.hasAttribute('open')) {
        menu.removeAttribute('open');
      }
    }
  });
})();

(() => {
  document.addEventListener('change', (event) => {
    const select = event.target.closest('[data-auto-submit]');
    if (!select) {
      return;
    }
    const form = select.closest('form');
    if (form) {
      if (select.value === '__create__') {
        const target = select.getAttribute('data-create-url') || '/workspaces';
        window.location.href = target + '?open=create-workspace';
        return;
      }
      form.submit();
    }
  });
})();

(() => {
  const inputs = document.querySelectorAll('[data-auto-search]');
  if (inputs.length === 0) {
    return;
  }
  let timer = null;
  const scheduleSubmit = (input) => {
    const form = input.closest('form');
    if (!form) {
      return;
    }
    const status = form.querySelector('[data-search-status]');
    const value = input.value.trim();
    if (value.length > 2 || value.length === 0) {
      if (status) {
        status.textContent = 'Searching...';
        status.classList.add('is-visible');
      }
      form.submit();
    }
  };

  inputs.forEach((input) => {
    input.addEventListener('input', () => {
      if (timer) {
        clearTimeout(timer);
      }
      timer = setTimeout(() => scheduleSubmit(input), 350);
    });
  });
})();

(() => {
  const flash = document.querySelector('[data-flash]');
  if (!flash) {
    return;
  }
  setTimeout(() => {
    flash.classList.add('is-hidden');
    setTimeout(() => {
      flash.remove();
    }, 300);
  }, 3000);
})();

(() => {
  const openButtons = document.querySelectorAll('[data-modal-open]');
  if (openButtons.length === 0) {
    return;
  }

  openButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const targetId = button.getAttribute('data-modal-open');
      const modal = targetId ? document.getElementById(targetId) : null;
      if (modal && typeof modal.showModal === 'function') {
        modal.showModal();
      }
    });
  });

  document.addEventListener('click', (event) => {
    const closeButton = event.target.closest('[data-modal-close]');
    if (closeButton) {
      const modal = closeButton.closest('dialog');
      if (modal) {
        modal.close();
      }
    }
  });
})();

(() => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('open') !== 'create-workspace') {
    return;
  }
  const modal = document.getElementById('workspace-create');
  if (modal && typeof modal.showModal === 'function') {
    modal.showModal();
  }
})();

(() => {
  const editButtons = document.querySelectorAll('[data-workspace-edit]');
  if (editButtons.length === 0) {
    return;
  }

  let activeForm = null;
  let activeCell = null;

  const closeActive = (submit) => {
    if (!activeForm) {
      return;
    }
    const input = activeForm.querySelector('[data-workspace-input]');
    const original = activeForm.dataset.original || (input ? input.value : '');
    if (input && (input.value.trim() === '' || !submit)) {
      input.value = original;
    }
    activeForm.classList.remove('is-editing');
    if (activeCell) {
      activeCell.classList.remove('is-hidden');
    }
    const formToSubmit = activeForm;
    activeForm = null;
    activeCell = null;
    if (submit && input) {
      formToSubmit.submit();
    }
  };

  editButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      const id = button.getAttribute('data-workspace-edit');
      const form = id ? document.querySelector(`[data-workspace-form="${id}"]`) : null;
      const cell = id ? document.querySelector(`[data-workspace-row="${id}"]`) : null;
      if (!form) {
        return;
      }
      if (activeForm && activeForm !== form) {
        closeActive(true);
        return;
      }
      if (form.classList.contains('is-editing')) {
        return;
      }
      const input = form.querySelector('[data-workspace-input]');
      if (!input) {
        return;
      }
      form.dataset.original = input.value;
      form.classList.add('is-editing');
      if (cell) {
        cell.classList.add('is-hidden');
      }
      activeForm = form;
      activeCell = cell;
      setTimeout(() => {
        input.focus();
        input.select();
      }, 0);
    });
  });

  document.addEventListener('click', (event) => {
    if (!activeForm) {
      return;
    }
    if (activeForm.contains(event.target)) {
      return;
    }
    if (activeCell && activeCell.contains(event.target)) {
      return;
    }
    closeActive(true);
  });

  document.addEventListener('focusin', (event) => {
    if (!activeForm) {
      return;
    }
    if (activeForm.contains(event.target)) {
      return;
    }
    closeActive(true);
  });

  document.addEventListener('keydown', (event) => {
    if (!activeForm) {
      return;
    }
    if (event.key === 'Escape') {
      event.preventDefault();
      closeActive(false);
      return;
    }
    if (event.key === 'Enter') {
      const input = activeForm.querySelector('[data-workspace-input]');
      if (input && document.activeElement === input) {
        event.preventDefault();
        closeActive(true);
      }
    }
  });
})();
