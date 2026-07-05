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

  const header = document.querySelector('[data-header]');
  const updateHeader = () => header?.classList.toggle('is-scrolled', window.scrollY > 48);
  window.addEventListener('scroll', updateHeader, { passive: true });
  updateHeader();

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
    }, { threshold: 0.12, rootMargin: '0px 0px -5% 0px' });
    revealItems.forEach((item) => revealObserver.observe(item));
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
    window.setTimeout(() => { overlay.hidden = true; }, 350);
    if (lastFocused instanceof HTMLElement) lastFocused.focus();
  };

  openButtons.forEach((button) => button.addEventListener('click', openNotes));
  closeButtons.forEach((button) => button.addEventListener('click', closeNotes));
  overlay?.addEventListener('click', closeNotes);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && drawer?.classList.contains('is-open')) closeNotes();
  });
  if (new URLSearchParams(window.location.search).has('note')) openNotes();

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
          const travel = Math.max(-86, Math.min(86, -centerOffset * 150 * depth * 10));
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

  document.querySelectorAll('[data-story-index]').forEach((indexRoot) => {
    const cards = Array.from(indexRoot.querySelectorAll('[data-story-card]'));
    const categoryButtons = Array.from(indexRoot.querySelectorAll('[data-story-category]'));
    const statusSelect = indexRoot.querySelector('[data-story-status]');
    const searchInput = indexRoot.querySelector('[data-story-search]');
    const countOutput = indexRoot.querySelector('[data-story-count]');
    const emptyState = indexRoot.querySelector('[data-story-empty]');
    let activeCategory = indexRoot.dataset.initialCategory || 'all';

    const normalize = (value) => String(value || '').toLocaleLowerCase('tr-TR').trim();
    const applyStoryFilters = () => {
      const wantedStatus = statusSelect?.value || 'all';
      const wantedSearch = normalize(searchInput?.value);
      let visibleCount = 0;
      cards.forEach((card) => {
        const categoryMatch = activeCategory === 'all' || card.dataset.category === activeCategory;
        const statusMatch = wantedStatus === 'all' || card.dataset.status === wantedStatus;
        const searchMatch = wantedSearch === '' || normalize(card.dataset.search).includes(wantedSearch);
        const visible = categoryMatch && statusMatch && searchMatch;
        card.hidden = !visible;
        if (visible) visibleCount += 1;
      });
      if (countOutput) countOutput.textContent = String(visibleCount);
      if (emptyState) emptyState.hidden = visibleCount !== 0;
    };

    categoryButtons.forEach((button) => {
      button.addEventListener('click', () => {
        activeCategory = button.dataset.storyCategory || 'all';
        categoryButtons.forEach((item) => {
          const active = item === button;
          item.classList.toggle('is-active', active);
          item.setAttribute('aria-pressed', String(active));
        });
        const url = new URL(window.location.href);
        if (activeCategory === 'all') url.searchParams.delete('kategori');
        else url.searchParams.set('kategori', activeCategory);
        history.replaceState(null, '', url);
        applyStoryFilters();
      });
    });
    statusSelect?.addEventListener('change', applyStoryFilters);
    searchInput?.addEventListener('input', applyStoryFilters);
    applyStoryFilters();
  });

  const rail = document.querySelector('[data-journal-rail]');
  if (rail && window.matchMedia('(pointer:fine)').matches) {
    rail.addEventListener('wheel', (event) => {
      if (Math.abs(event.deltaY) <= Math.abs(event.deltaX)) return;
      const canScroll = rail.scrollWidth > rail.clientWidth;
      if (!canScroll) return;
      const atStart = rail.scrollLeft <= 0 && event.deltaY < 0;
      const atEnd = Math.ceil(rail.scrollLeft + rail.clientWidth) >= rail.scrollWidth && event.deltaY > 0;
      if (atStart || atEnd) return;
      event.preventDefault();
      rail.scrollLeft += event.deltaY;
    }, { passive: false });
  }
})();
