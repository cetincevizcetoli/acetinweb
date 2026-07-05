(() => {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const navToggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  if (navToggle && nav) {
    navToggle.addEventListener('click', () => {
      const next = navToggle.getAttribute('aria-expanded') !== 'true';
      navToggle.setAttribute('aria-expanded', String(next));
      nav.classList.toggle('is-open', next);
    });
    nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
      navToggle.setAttribute('aria-expanded', 'false');
      nav.classList.remove('is-open');
    }));
  }

  const revealItems = document.querySelectorAll('[data-reveal]');
  if (prefersReducedMotion || !('IntersectionObserver' in window)) {
    revealItems.forEach((item) => item.classList.add('is-visible'));
  } else {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.13, rootMargin: '0px 0px -6% 0px' });
    revealItems.forEach((item) => revealObserver.observe(item));
  }

  document.querySelectorAll('[data-tabs]').forEach((tabs) => {
    const buttons = Array.from(tabs.querySelectorAll('[role="tab"]'));
    const panels = Array.from(tabs.querySelectorAll('[role="tabpanel"]'));

    const activate = (button, focus = false) => {
      const wanted = button.dataset.tab;
      buttons.forEach((item) => {
        const active = item === button;
        item.setAttribute('aria-selected', String(active));
        item.tabIndex = active ? 0 : -1;
      });
      panels.forEach((panel) => {
        panel.hidden = panel.dataset.panel !== wanted;
      });
      if (focus) button.focus();
    };

    buttons.forEach((button, index) => {
      button.addEventListener('click', () => activate(button));
      button.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        let next = index;
        if (event.key === 'ArrowRight') next = (index + 1) % buttons.length;
        if (event.key === 'ArrowLeft') next = (index - 1 + buttons.length) % buttons.length;
        if (event.key === 'Home') next = 0;
        if (event.key === 'End') next = buttons.length - 1;
        activate(buttons[next], true);
      });
    });
  });

  const drawer = document.querySelector('[data-notes-drawer]');
  const overlay = document.querySelector('[data-notes-overlay]');
  const openButtons = document.querySelectorAll('[data-notes-open]');
  const closeButtons = document.querySelectorAll('[data-notes-close]');
  let lastFocused = null;

  const openNotes = () => {
    if (!drawer || !overlay) return;
    lastFocused = document.activeElement;
    overlay.hidden = false;
    requestAnimationFrame(() => {
      drawer.classList.add('is-open');
      drawer.setAttribute('aria-hidden', 'false');
      document.body.classList.add('notes-open');
      drawer.querySelector('input, textarea, button, a')?.focus();
    });
  };

  const closeNotes = () => {
    if (!drawer || !overlay) return;
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('notes-open');
    window.setTimeout(() => { overlay.hidden = true; }, 350);
    if (lastFocused instanceof HTMLElement) lastFocused.focus();
  };

  openButtons.forEach((button) => button.addEventListener('click', openNotes));
  closeButtons.forEach((button) => button.addEventListener('click', closeNotes));
  overlay?.addEventListener('click', closeNotes);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && drawer?.classList.contains('is-open')) closeNotes();
  });

  const query = new URLSearchParams(window.location.search);
  if (query.has('note')) openNotes();

  if (!prefersReducedMotion) {
    const parallaxRoots = Array.from(document.querySelectorAll('[data-parallax-root]'));
    let scheduled = false;

    const renderParallax = () => {
      const viewportHeight = window.innerHeight || 1;
      parallaxRoots.forEach((root) => {
        const rect = root.getBoundingClientRect();
        if (rect.bottom < -180 || rect.top > viewportHeight + 180) return;
        const centerOffset = (rect.top + rect.height / 2 - viewportHeight / 2) / viewportHeight;
        root.querySelectorAll('[data-parallax]').forEach((layer) => {
          const depth = Number(layer.dataset.depth || 0.08);
          const travel = Math.max(-90, Math.min(90, -centerOffset * 170 * depth * 10));
          layer.style.translate = `0 ${travel.toFixed(2)}px`;
        });
      });
      scheduled = false;
    };

    const requestParallax = () => {
      if (scheduled) return;
      scheduled = true;
      requestAnimationFrame(renderParallax);
    };

    window.addEventListener('scroll', requestParallax, { passive: true });
    window.addEventListener('resize', requestParallax);
    renderParallax();
  }
})();
