(() => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const navToggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  if (navToggle && nav) {
    navToggle.addEventListener('click', () => {
      const open = navToggle.getAttribute('aria-expanded') !== 'true';
      navToggle.setAttribute('aria-expanded', String(open));
      nav.classList.toggle('is-open', open);
    });
    nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
      navToggle.setAttribute('aria-expanded', 'false');
      nav.classList.remove('is-open');
    }));
  }

  const header = document.querySelector('[data-header]');
  const updateHeader = () => header?.classList.toggle('is-scrolled', window.scrollY > 42);
  window.addEventListener('scroll', updateHeader, { passive: true });
  updateHeader();

  const revealItems = document.querySelectorAll('[data-reveal]');
  if (reducedMotion || !('IntersectionObserver' in window)) {
    revealItems.forEach((item) => item.classList.add('is-visible'));
  } else {
    const observer = new IntersectionObserver((entries, instance) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        instance.unobserve(entry.target);
      });
    }, { threshold: 0.10, rootMargin: '0px 0px -4% 0px' });
    revealItems.forEach((item) => observer.observe(item));
  }

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
    window.setTimeout(() => { overlay.hidden = true; }, 360);
    if (lastFocused instanceof HTMLElement) lastFocused.focus();
  };
  openButtons.forEach((button) => button.addEventListener('click', openNotes));
  closeButtons.forEach((button) => button.addEventListener('click', closeNotes));
  overlay?.addEventListener('click', closeNotes);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && drawer?.classList.contains('is-open')) closeNotes();
  });
  if (new URLSearchParams(window.location.search).has('note')) openNotes();

  if (!reducedMotion) {
    const roots = Array.from(document.querySelectorAll('[data-parallax-root]'));
    let scheduled = false;
    const render = () => {
      const viewport = window.innerHeight || 1;
      roots.forEach((root) => {
        const rect = root.getBoundingClientRect();
        if (rect.bottom < -180 || rect.top > viewport + 180) return;
        const offset = (rect.top + rect.height / 2 - viewport / 2) / viewport;
        root.querySelectorAll('[data-parallax]').forEach((layer) => {
          const depth = Number(layer.dataset.depth || 0.06);
          const travel = Math.max(-72, Math.min(72, -offset * 130 * depth * 10));
          layer.style.translate = `0 ${travel.toFixed(2)}px`;
        });
      });
      scheduled = false;
    };
    const requestRender = () => {
      if (scheduled) return;
      scheduled = true;
      requestAnimationFrame(render);
    };
    window.addEventListener('scroll', requestRender, { passive: true });
    window.addEventListener('resize', requestRender);
    render();
  }

  document.querySelectorAll('[data-story-index]').forEach((root) => {
    const cards = Array.from(root.querySelectorAll('[data-story-card]'));
    const categoryButtons = Array.from(root.querySelectorAll('[data-story-category]'));
    const statusSelect = root.querySelector('[data-story-status]');
    const searchInput = root.querySelector('[data-story-search]');
    const countOutput = root.querySelector('[data-story-count]');
    const emptyState = root.querySelector('[data-story-empty]');
    let activeCategory = root.dataset.initialCategory || 'all';

    const normalize = (value) => String(value || '').toLocaleLowerCase('tr-TR').trim();
    const apply = () => {
      const status = statusSelect?.value || root.dataset.initialStatus || 'all';
      const query = normalize(searchInput?.value);
      let visible = 0;
      cards.forEach((card) => {
        const categoryMatch = activeCategory === 'all' || card.dataset.category === activeCategory;
        const statusMatch = status === 'all' || card.dataset.status === status;
        const searchMatch = query === '' || normalize(card.dataset.search).includes(query);
        const show = categoryMatch && statusMatch && searchMatch;
        card.hidden = !show;
        if (show) visible += 1;
      });
      if (countOutput) countOutput.textContent = String(visible);
      if (emptyState) emptyState.hidden = visible !== 0;

      const url = new URL(window.location.href);
      if (activeCategory === 'all') url.searchParams.delete('kategori');
      else url.searchParams.set('kategori', activeCategory);
      if (status === 'all') url.searchParams.delete('durum');
      else url.searchParams.set('durum', status);
      history.replaceState(null, '', url);
    };

    categoryButtons.forEach((button) => {
      button.addEventListener('click', () => {
        activeCategory = button.dataset.storyCategory || 'all';
        categoryButtons.forEach((candidate) => {
          const active = candidate === button;
          candidate.classList.toggle('is-active', active);
          candidate.setAttribute('aria-pressed', String(active));
        });
        apply();
      });
    });
    statusSelect?.addEventListener('change', apply);
    searchInput?.addEventListener('input', apply);
    apply();
  });

  const atelierConsole = document.querySelector('[data-atelier-console]');
  if (atelierConsole) {
    const stageFigure = atelierConsole.querySelector('.atelier-stage-media');
    const stageImage = atelierConsole.querySelector('[data-atelier-media]');
    const day = atelierConsole.querySelector('[data-atelier-day]');
    const title = atelierConsole.querySelector('[data-atelier-title]');
    const summary = atelierConsole.querySelector('[data-atelier-summary]');
    const tried = atelierConsole.querySelector('[data-atelier-tried]');
    const failed = atelierConsole.querySelector('[data-atelier-failed]');
    const decision = atelierConsole.querySelector('[data-atelier-decision]');
    const next = atelierConsole.querySelector('[data-atelier-next]');
    const entries = Array.from(atelierConsole.querySelectorAll('[data-atelier-entry]'));

    const setText = (node, value) => { if (node) node.textContent = value || ''; };
    const activate = (entry) => {
      entries.forEach((candidate) => candidate.classList.toggle('is-active', candidate === entry));
      if (stageFigure) stageFigure.classList.add('is-changing');
      window.setTimeout(() => {
        if (stageImage) {
          stageImage.src = entry.dataset.media || stageImage.src;
          stageImage.alt = entry.dataset.alt || '';
        }
        setText(day, entry.dataset.day);
        setText(title, entry.dataset.title);
        setText(summary, entry.dataset.summary);
        setText(tried, entry.dataset.tried);
        setText(failed, entry.dataset.failed);
        setText(decision, entry.dataset.decision);
        setText(next, entry.dataset.next);
        stageFigure?.classList.remove('is-changing');
      }, reducedMotion ? 0 : 180);
    };
    entries.forEach((entry) => entry.addEventListener('click', () => activate(entry)));
  }
})();
