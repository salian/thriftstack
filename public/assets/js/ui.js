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
        const target = select.getAttribute('data-create-url') || '/teams';
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
  if (openButtons.length > 0) {
    openButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-modal-open');
        const modal = targetId ? document.getElementById(targetId) : null;
        if (modal && typeof modal.showModal === 'function') {
          modal.showModal();
        }
      });
    });
  }

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

(() => {
  const copyButtons = document.querySelectorAll('[data-copy-text]');
  if (copyButtons.length === 0) {
    return;
  }

  copyButtons.forEach((button) => {
    button.addEventListener('click', async () => {
      const text = button.getAttribute('data-copy-text') || '';
      if (!text) {
        return;
      }
      try {
        await navigator.clipboard.writeText(text);
        button.classList.add('is-copied');
        setTimeout(() => button.classList.remove('is-copied'), 1200);
      } catch (error) {
        // Ignore clipboard errors silently.
      }
    });
  });
})();

(() => {
  const modal = document.getElementById('billing-plan-modal');
  if (!modal) {
    return;
  }

  const form = modal.querySelector('[data-billing-form]');
  const title = modal.querySelector('[data-billing-title]');
  const subtitle = modal.querySelector('[data-billing-subtitle]');
  const submit = modal.querySelector('[data-billing-submit]');
  const codeWrap = modal.querySelector('[data-billing-code]');
  const codeInput = modal.querySelector('[data-billing-code-input]');
  const idInput = modal.querySelector('[data-billing-id]');
  const nameInput = modal.querySelector('[data-billing-name]');
  const priceInput = modal.querySelector('[data-billing-price]');
  const durationInput = modal.querySelector('[data-billing-duration]');
  const activeInput = modal.querySelector('[data-billing-active]');
  const grandfatheredInput = modal.querySelector('[data-billing-grandfathered]');
  const stripeInput = modal.querySelector('[data-billing-stripe]');
  const razorpayInput = modal.querySelector('[data-billing-razorpay]');
  const paypalInput = modal.querySelector('[data-billing-paypal]');
  const lemonsqueezyInput = modal.querySelector('[data-billing-lemonsqueezy]');
  const dodoInput = modal.querySelector('[data-billing-dodo]');
  const paddleInput = modal.querySelector('[data-billing-paddle]');

  const resetForm = () => {
    if (!form) {
      return;
    }
    form.action = '/billing/plans';
    if (title) {
      title.textContent = 'Create plan';
    }
    if (subtitle) {
      subtitle.textContent = 'Define pricing and availability for a plan.';
    }
    if (submit) {
      submit.textContent = 'Create plan';
    }
    if (idInput) {
      idInput.value = '';
    }
    if (codeWrap) {
      codeWrap.removeAttribute('hidden');
    }
    if (codeInput) {
      codeInput.value = '';
      codeInput.disabled = false;
      codeInput.required = true;
    }
    if (nameInput) {
      nameInput.value = '';
    }
    if (priceInput) {
      priceInput.value = '0';
    }
    if (durationInput) {
      durationInput.value = 'monthly';
    }
    if (activeInput) {
      activeInput.checked = true;
    }
    if (grandfatheredInput) {
      grandfatheredInput.checked = false;
    }
    if (stripeInput) {
      stripeInput.value = '';
    }
    if (razorpayInput) {
      razorpayInput.value = '';
    }
    if (paypalInput) {
      paypalInput.value = '';
    }
    if (lemonsqueezyInput) {
      lemonsqueezyInput.value = '';
    }
    if (dodoInput) {
      dodoInput.value = '';
    }
    if (paddleInput) {
      paddleInput.value = '';
    }
  };

  const openForCreate = () => {
    resetForm();
    modal.showModal();
  };

  const openForEdit = (button) => {
    if (!form) {
      return;
    }
    form.action = '/billing/plans/update';
    if (title) {
      title.textContent = 'Edit plan';
    }
    if (subtitle) {
      subtitle.textContent = 'Update pricing, duration, and availability.';
    }
    if (submit) {
      submit.textContent = 'Save changes';
    }
    if (idInput) {
      idInput.value = button.dataset.planId || '';
    }
    if (codeWrap) {
      codeWrap.setAttribute('hidden', 'hidden');
    }
    if (codeInput) {
      codeInput.value = button.dataset.planCode || '';
      codeInput.disabled = true;
      codeInput.required = false;
    }
    if (nameInput) {
      nameInput.value = button.dataset.planName || '';
    }
    if (priceInput) {
      priceInput.value = button.dataset.planPrice || '0';
    }
    if (durationInput) {
      durationInput.value = button.dataset.planDuration || 'monthly';
    }
    if (activeInput) {
      activeInput.checked = (button.dataset.planActive || '0') === '1';
    }
    if (grandfatheredInput) {
      grandfatheredInput.checked = (button.dataset.planGrandfathered || '0') === '1';
    }
    if (stripeInput) {
      stripeInput.value = button.dataset.planStripe || '';
    }
    if (razorpayInput) {
      razorpayInput.value = button.dataset.planRazorpay || '';
    }
    if (paypalInput) {
      paypalInput.value = button.dataset.planPaypal || '';
    }
    if (lemonsqueezyInput) {
      lemonsqueezyInput.value = button.dataset.planLemonsqueezy || '';
    }
    if (dodoInput) {
      dodoInput.value = button.dataset.planDodo || '';
    }
    if (paddleInput) {
      paddleInput.value = button.dataset.planPaddle || '';
    }
    modal.showModal();
  };

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-billing-open]');
    if (!trigger) {
      return;
    }
    const mode = trigger.getAttribute('data-billing-open');
    if (mode === 'edit') {
      openForEdit(trigger);
      return;
    }
    openForCreate();
  });
})();

(() => {
  const banner = document.querySelector('[data-clear-status]');
  if (!banner) {
    return;
  }

  const url = new URL(window.location.href);
  if (!url.searchParams.has('status')) {
    return;
  }
  url.searchParams.delete('status');
  window.history.replaceState({}, '', url.toString());
})();

(() => {
  const containers = document.querySelectorAll('[data-tabs]');
  if (containers.length === 0) {
    return;
  }

  const params = new URLSearchParams(window.location.search);
  let preferredTab = params.get('tab');
  if (!preferredTab && params.get('open') === 'create-workspace') {
    preferredTab = 'workspaces';
  }

  containers.forEach((container) => {
    const buttons = Array.from(container.querySelectorAll('[data-tab-button]'));
    if (buttons.length === 0) {
      return;
    }

    const panels = buttons
      .map((button) => {
        const name = button.getAttribute('data-tab-button');
        if (!name) {
          return null;
        }
        return document.querySelector(`[data-tab-panel="${name}"]`);
      })
      .filter(Boolean);

    const activate = (selected) => {
      buttons.forEach((button) => {
        const active = button === selected;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      panels.forEach((panel) => {
        const active = panel && selected.getAttribute('data-tab-button') === panel.getAttribute('data-tab-panel');
        if (panel) {
          panel.classList.toggle('is-active', active);
          panel.hidden = !active;
        }
      });
    };

    buttons.forEach((button) => {
      button.addEventListener('click', () => activate(button));
    });

    if (preferredTab) {
      const preferredButton = buttons.find(
        (button) => button.getAttribute('data-tab-button') === preferredTab
      );
      if (preferredButton) {
        activate(preferredButton);
      }
    }
  });
})();
