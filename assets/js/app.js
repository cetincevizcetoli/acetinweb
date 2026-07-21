(() => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const themeKey = 'fikrimvar-theme';
  const themeMeta = document.querySelector('meta[name="theme-color"]');
  const systemDark = window.matchMedia('(prefers-color-scheme: dark)');
  const readTheme = () => {
    try {
      return localStorage.getItem(themeKey) || (systemDark.matches ? 'dark' : 'light');
    } catch (_) {
      return systemDark.matches ? 'dark' : 'light';
    }
  };
  const writeTheme = (theme) => {
    document.documentElement.dataset.theme = theme;
    document.documentElement.style.colorScheme = theme;
    if (themeMeta) themeMeta.setAttribute('content', theme === 'dark' ? '#0f1318' : '#efe7d8');
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
      const dark = theme === 'dark';
      button.setAttribute('aria-pressed', String(dark));
      button.setAttribute('aria-label', dark ? 'Aydınlık moda geç' : 'Karanlık moda geç');
      const label = button.querySelector('[data-theme-label]');
      if (label) label.textContent = dark ? 'Aydınlık' : 'Karanlık';
    });
  };
  writeTheme(readTheme());
  document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const next = (document.documentElement.dataset.theme || readTheme()) === 'dark' ? 'light' : 'dark';
      try { localStorage.setItem(themeKey, next); } catch (_) { /* private mode */ }
      writeTheme(next);
    });
  });
  systemDark.addEventListener?.('change', () => {
    try {
      if (localStorage.getItem(themeKey)) return;
    } catch (_) { /* private mode */ }
    writeTheme(readTheme());
  });

  const navToggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  if (navToggle && nav) {
    const closeNav = () => {
      navToggle.setAttribute('aria-expanded', 'false');
      nav.classList.remove('is-open');
    };
    navToggle.addEventListener('click', () => {
      const open = navToggle.getAttribute('aria-expanded') !== 'true';
      navToggle.setAttribute('aria-expanded', String(open));
      nav.classList.toggle('is-open', open);
      if (open) nav.querySelector('a, button')?.focus();
    });
    nav.querySelectorAll('a, button').forEach((item) => item.addEventListener('click', closeNav));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && nav.classList.contains('is-open')) {
        closeNav();
        navToggle.focus();
      }
    });
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
    drawer.hidden = false;
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
    window.setTimeout(() => {
      overlay.hidden = true;
      drawer.hidden = true;
    }, reducedMotion ? 0 : 360);
    if (lastFocused instanceof HTMLElement) lastFocused.focus();
  };
  openButtons.forEach((button) => button.addEventListener('click', openNotes));
  closeButtons.forEach((button) => button.addEventListener('click', closeNotes));
  overlay?.addEventListener('click', closeNotes);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && drawer?.classList.contains('is-open')) closeNotes();
  });
  if (new URLSearchParams(window.location.search).has('note')) openNotes();

  const storyNotes = Array.from(document.querySelectorAll('[data-story-note]'));
  if (storyNotes.length) {
    const closeStoryNote = (note) => {
      if (note instanceof HTMLDetailsElement) note.open = false;
    };
    const closeAllStoryNotes = () => storyNotes.forEach(closeStoryNote);
    const syncStoryNoteState = () => {
      document.body.classList.toggle('story-note-open', storyNotes.some((note) => note.open));
    };
    storyNotes.forEach((note) => {
      note.addEventListener('toggle', () => {
        if (note.open) {
          storyNotes.forEach((candidate) => {
            if (candidate !== note) closeStoryNote(candidate);
          });
        }
        syncStoryNoteState();
      });
      note.querySelectorAll('[data-story-note-close]').forEach((button) => {
        button.addEventListener('click', () => closeStoryNote(note));
      });
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') closeAllStoryNotes();
    });
  }

  const atelierWidget = document.querySelector('[data-atelier-widget]');
  const atelierWidgetOverlay = document.querySelector('[data-atelier-widget-overlay]');
  const atelierWidgetOpeners = document.querySelectorAll('[data-atelier-widget-open]');
  const atelierWidgetClosers = document.querySelectorAll('[data-atelier-widget-close]');
  let atelierLastFocused = null;

  const openAtelierWidget = () => {
    if (!atelierWidget || !atelierWidgetOverlay) return;
    if (drawer?.classList.contains('is-open')) closeNotes();
    atelierLastFocused = document.activeElement;
    atelierWidgetOverlay.hidden = false;
    atelierWidget.hidden = false;
    requestAnimationFrame(() => {
      atelierWidgetOverlay.classList.add('is-visible');
      atelierWidget.classList.add('is-open');
      atelierWidget.setAttribute('aria-hidden', 'false');
      document.body.classList.add('atelier-widget-open');
      atelierWidgetOpeners.forEach((button) => button.setAttribute('aria-expanded', 'true'));
      atelierWidget.querySelector('a, button')?.focus();
    });
  };

  const closeAtelierWidget = () => {
    if (!atelierWidget || !atelierWidgetOverlay) return;
    atelierWidgetOverlay.classList.remove('is-visible');
    atelierWidget.classList.remove('is-open');
    atelierWidget.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('atelier-widget-open');
    atelierWidgetOpeners.forEach((button) => button.setAttribute('aria-expanded', 'false'));
    window.setTimeout(() => {
      atelierWidgetOverlay.hidden = true;
      atelierWidget.hidden = true;
    }, reducedMotion ? 0 : 300);
    if (atelierLastFocused instanceof HTMLElement) atelierLastFocused.focus();
  };

  atelierWidgetOpeners.forEach((button) => button.addEventListener('click', openAtelierWidget));
  atelierWidgetClosers.forEach((button) => button.addEventListener('click', closeAtelierWidget));
  atelierWidgetOverlay?.addEventListener('click', closeAtelierWidget);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && atelierWidget?.classList.contains('is-open')) closeAtelierWidget();
  });

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
      try { history.replaceState(null, '', url); } catch (_) { /* file preview */ }
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
    const day = atelierConsole.querySelector('[data-atelier-day]');
    const title = atelierConsole.querySelector('[data-atelier-title]');
    const summary = atelierConsole.querySelector('[data-atelier-summary]');
    const seedLabel = atelierConsole.querySelector('[data-atelier-seed-label]');
    const seedText = atelierConsole.querySelector('[data-atelier-seed-text]');
    const artifactsRoot = atelierConsole.querySelector('[data-atelier-artifacts]');
    const galleryRoot = atelierConsole.querySelector('[data-atelier-gallery]');
    const linksRoot = atelierConsole.querySelector('[data-atelier-links]');
    const socialLinks = Array.from(atelierConsole.querySelectorAll('[data-social-field]'));
    const entries = Array.from(document.querySelectorAll('[data-atelier-entry]'));

    const setText = (node, value) => { if (node) node.textContent = value || ''; };
    const isCodeArtifact = (artifact) => {
      const type = artifact.type || '';
      const body = artifact.body || '';
      return ['prompt', 'code', 'output', 'log', 'error'].includes(type)
        || /(<[a-z][\s\S]*>|```|\$ |PS |PROMPT:|SQLSTATE|Fatal error|SELECT |UPDATE |INSERT |function |class )/i.test(body);
    };
    const renderArtifacts = (entry) => {
      if (!artifactsRoot) return;
      let artifacts = [];
      try { artifacts = JSON.parse(entry.dataset.artifacts || '[]'); } catch (_) { artifacts = []; }
      const wrap = document.createElement('div');
      wrap.className = 'atelier-artifacts atelier-artifacts--stage';
      artifacts.forEach((artifact) => {
        if (!artifact || !artifact.body) return;
        const type = String(artifact.type || 'note').replace(/[^a-z0-9_-]/gi, '') || 'note';
        const article = document.createElement('article');
        article.className = `atelier-artifact atelier-artifact--${type}`;
        const label = document.createElement('span');
        label.textContent = artifact.label || '';
        const heading = document.createElement('h4');
        heading.textContent = artifact.title || artifact.label || '';
        article.append(label, heading);
        if (isCodeArtifact(artifact)) {
          const pre = document.createElement('pre');
          const code = document.createElement('code');
          code.textContent = artifact.body || '';
          pre.append(code);
          article.append(pre);
        } else {
          const body = document.createElement('p');
          body.textContent = artifact.body || '';
          article.append(body);
        }
        wrap.append(article);
      });
      artifactsRoot.replaceChildren(wrap);
    };
    const renderGallery = (entry) => {
      if (!galleryRoot) return;
      let items = [];
      try { items = JSON.parse(entry.dataset.gallery || '[]'); } catch (_) { items = []; }
      if (!items.length) {
        galleryRoot.replaceChildren();
        return;
      }
      const section = document.createElement('div');
      section.className = 'atelier-evidence-gallery';
      const label = document.createElement('p');
      label.className = 'atelier-material-label';
      label.textContent = 'Medya / görsel kanıt';
      const grid = document.createElement('div');
      grid.className = 'atelier-media-grid';
      items.forEach((item) => {
        if (!item || !item.url) return;
        const figure = document.createElement('figure');
        const type = item.type || 'file';
        if (type === 'image') {
          const img = document.createElement('img');
          img.src = item.url;
          img.alt = item.alt || '';
          figure.append(img);
        } else if (type === 'video') {
          const video = document.createElement('video');
          video.controls = true;
          video.playsInline = true;
          video.preload = 'metadata';
          const source = document.createElement('source');
          source.src = item.url;
          video.append(source);
          figure.append(video);
        } else if (type === 'audio') {
          const audio = document.createElement('audio');
          audio.controls = true;
          audio.preload = 'metadata';
          const source = document.createElement('source');
          source.src = item.url;
          audio.append(source);
          figure.append(audio);
        } else {
          const link = document.createElement('a');
          link.href = item.url;
          link.target = '_blank';
          link.rel = 'noopener noreferrer';
          link.textContent = item.title || 'Dosyayı aç';
          figure.append(link);
        }
        if (item.caption) {
          const caption = document.createElement('figcaption');
          caption.textContent = item.caption;
          figure.append(caption);
        }
        grid.append(figure);
      });
      section.append(label, grid);
      galleryRoot.replaceChildren(section);
    };
    const renderLinks = (entry) => {
      if (!linksRoot) return;
      let links = [];
      try { links = JSON.parse(entry.dataset.links || '[]'); } catch (_) { links = []; }
      if (!links.length) {
        linksRoot.replaceChildren();
        return;
      }
      const grid = document.createElement('div');
      grid.className = 'content-link-cards';
      grid.setAttribute('aria-label', 'İlgili bağlantılar');
      links.forEach((link) => {
        if (!link || !link.url) return;
        const provider = String(link.provider || 'external').replace(/[^a-z0-9-]/gi, '') || 'external';
        const card = document.createElement('article');
        card.className = `content-link-card content-link-card--${provider}`;
        if (link.embedKind === 'iframe' && link.embedUrl) {
          const player = document.createElement('div');
          player.className = 'content-link-player';
          const frame = document.createElement('iframe');
          frame.src = link.embedUrl;
          frame.title = link.title || 'Bağlantı';
          frame.loading = 'lazy';
          frame.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
          frame.allowFullscreen = true;
          player.append(frame);
          card.append(player);
        } else if (link.embedKind === 'video' && link.embedUrl) {
          const player = document.createElement('div');
          player.className = 'content-link-player';
          const video = document.createElement('video');
          video.controls = true;
          video.playsInline = true;
          video.preload = 'metadata';
          const source = document.createElement('source');
          source.src = link.embedUrl;
          video.append(source);
          player.append(video);
          card.append(player);
        } else if (link.embedKind === 'audio' && link.embedUrl) {
          const audioBox = document.createElement('div');
          audioBox.className = 'content-link-audio';
          const audio = document.createElement('audio');
          audio.controls = true;
          audio.preload = 'metadata';
          const source = document.createElement('source');
          source.src = link.embedUrl;
          audio.append(source);
          audioBox.append(audio);
          card.append(audioBox);
        }
        const main = document.createElement('a');
        main.className = 'content-link-card-main';
        main.href = link.url;
        main.target = '_blank';
        main.rel = 'noopener noreferrer';
        const label = document.createElement('span');
        label.textContent = link.providerLabel || provider;
        const titleNode = document.createElement('strong');
        titleNode.textContent = link.title || 'Bağlantı';
        const small = document.createElement('small');
        small.textContent = link.displayUrl || link.url;
        const action = document.createElement('em');
        action.textContent = 'Bağlantıyı aç';
        main.append(label, titleNode, small, action);
        card.append(main);
        grid.append(card);
      });
      linksRoot.replaceChildren(grid);
    };
    const activate = (entry) => {
      entries.forEach((candidate) => candidate.classList.toggle('is-active', candidate === entry));
      if (stageFigure) stageFigure.classList.add('is-changing');
      window.setTimeout(() => {
        if (stageFigure && entry.dataset.media) {
          const mediaType = entry.dataset.mediaType || 'image';
          const src = entry.dataset.media;
          const alt = entry.dataset.alt || '';
          if (mediaType === 'video') {
            stageFigure.innerHTML = `<video controls playsinline preload="metadata"><source src="${src}"></video>`;
          } else if (mediaType === 'audio') {
            stageFigure.innerHTML = `<div class="atelier-audio-stage"><audio controls preload="metadata"><source src="${src}"></audio></div>`;
          } else {
            const img = document.createElement('img');
            img.src = src; img.alt = alt; img.setAttribute('data-atelier-media', '');
            stageFigure.replaceChildren(img);
          }
        }
        setText(day, entry.dataset.day);
        setText(title, entry.dataset.title);
        setText(summary, entry.dataset.summary);
        setText(seedLabel, entry.dataset.seedLabel);
        setText(seedText, entry.dataset.seedText);
        renderArtifacts(entry);
        renderGallery(entry);
        renderLinks(entry);
        socialLinks.forEach((link) => {
          const field = link.dataset.socialField || '';
          const datasetKey = field.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
          const url = entry.dataset[datasetKey] || '';
          link.hidden = url === '';
          if (url !== '') link.href = url;
        });
        const updateId = entry.dataset.updateId || '';
        if (updateId !== '') { try { history.replaceState(null, '', `#update-${encodeURIComponent(updateId)}`); } catch (_) { /* file preview */ } }
        stageFigure?.classList.remove('is-changing');
        if (entry.classList.contains('atelier-archive-entry')) {
          atelierConsole.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
        }
      }, reducedMotion ? 0 : 180);
    };
    entries.forEach((entry) => entry.addEventListener('click', () => activate(entry)));
    const requestedId = decodeURIComponent(window.location.hash.replace(/^#update-/, ''));
    if (requestedId) {
      const requestedEntry = entries.find((entry) => entry.dataset.updateId === requestedId);
      if (requestedEntry) activate(requestedEntry);
    }
  }
})();


// DX CONCEPT MICRO-INTERACTIONS

document.addEventListener('DOMContentLoaded', () => {
    // 1. SCROLL PROGRESS BAR
    const progressBar = document.createElement('div');
    progressBar.className = 'dx-scroll-progress';
    document.body.appendChild(progressBar);

    window.addEventListener('scroll', () => {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        progressBar.style.width = scrolled + '%';
    });

    // 2. MAGNETIC BUTTONS (Nav Links)
    const navLinks = document.querySelectorAll('.story-signature nav a, .inner-header nav a, .story-paths a');
    navLinks.forEach(link => {
        link.addEventListener('mousemove', (e) => {
            const rect = link.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            link.style.transform = "translate(${x * 0.2}px, ${y * 0.2}px)";
        });

        link.addEventListener('mouseleave', () => {
            link.style.transform = 'translate(0px, 0px)';
        });
    });

    // 3. DIKIs IZI (SEAM) TOOLTIPS FOR TIMELINES
    const timelineArticles = document.querySelectorAll('.story-section-timeline article');
    timelineArticles.forEach((article, index) => {
        article.classList.add('dx-context-wrapper');
        
        const marker = document.createElement('div');
        marker.className = 'dx-context-marker';
        marker.innerHTML = '?';
        
        const tooltip = document.createElement('div');
        tooltip.className = 'dx-tooltip';
        tooltip.innerHTML = "<strong>Kay�t #${index + 1}</strong><br>Bu karar�n At�lye'deki ham notlar�na, sayfa sonundaki ar�iv ba�lant�s�ndan ula�abilirsiniz.";
        
        article.appendChild(marker);
        article.appendChild(tooltip);
    });
});
