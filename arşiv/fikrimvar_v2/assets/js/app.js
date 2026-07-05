(() => {
  const toggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      nav.classList.toggle('is-open', !open);
    });
    nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
      toggle.setAttribute('aria-expanded', 'false');
      nav.classList.remove('is-open');
    }));
  }

  const filters = document.querySelectorAll('[data-filter]');
  const records = document.querySelectorAll('[data-category]');
  filters.forEach((button) => button.addEventListener('click', () => {
    filters.forEach((item) => item.classList.remove('is-active'));
    button.classList.add('is-active');
    const wanted = button.dataset.filter;
    records.forEach((record) => {
      record.hidden = wanted !== 'all' && record.dataset.category !== wanted;
    });
  }));

  const dialog = document.getElementById('note-dialog');
  document.querySelectorAll('[data-dialog-open]').forEach((button) => button.addEventListener('click', () => dialog?.showModal()));
  document.querySelectorAll('[data-dialog-close]').forEach((button) => button.addEventListener('click', () => dialog?.close()));
  dialog?.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
})();
