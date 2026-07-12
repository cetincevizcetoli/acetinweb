(() => {
  document.querySelectorAll('[data-repeat-add]').forEach((button) => {
    button.addEventListener('click', () => {
      const target = document.querySelector(button.dataset.repeatAdd);
      const template = document.querySelector(button.dataset.template);
      if (!target || !template) return;
      const index = target.children.length;
      const html = template.innerHTML.replaceAll('__INDEX__', String(index));
      target.insertAdjacentHTML('beforeend', html);
    });
  });
  document.addEventListener('click', (event) => {
    const remove = event.target.closest('[data-repeat-remove]');
    if (remove) remove.closest('.repeat-row,.item-editor,.media-card')?.remove();
  });
  document.querySelectorAll('[data-sortable]').forEach((list) => {
    let dragging = null;
    list.querySelectorAll('[draggable="true"]').forEach((item) => {
      item.addEventListener('dragstart', () => { dragging=item; item.classList.add('dragging'); });
      item.addEventListener('dragend', () => { item.classList.remove('dragging'); dragging=null; updateOrder(list); });
      item.addEventListener('dragover', (e) => {
        e.preventDefault(); if(!dragging || dragging===item) return;
        const rect=item.getBoundingClientRect(); list.insertBefore(dragging, e.clientY < rect.top+rect.height/2 ? item : item.nextSibling);
      });
    });
  });
  function updateOrder(list){ list.querySelectorAll('[data-order-input]').forEach((input,i)=>input.value=String(i+1)); }
  document.querySelectorAll('[data-confirm]').forEach((el)=>el.addEventListener('click',(e)=>{ if(!confirm(el.dataset.confirm || 'Emin misiniz?')) e.preventDefault(); }));
  document.querySelectorAll('[data-section-preview]').forEach((preview) => {
    const form = preview.closest('form');
    if (!form) return;
    const typeSelect = form.querySelector('[data-section-type]');
    const layoutSelect = form.querySelector('[data-section-layout]');
    const titleInput = form.querySelector('input[name="title"]');
    const bodyInput = form.querySelector('textarea[name="body_text"]');
    const introInput = form.querySelector('textarea[name="intro_text"]');
    const quoteInput = form.querySelector('textarea[name="quote_text"]');
    const typeNote = form.querySelector('[data-section-type-note]');
    const layoutNote = form.querySelector('[data-section-layout-note]');
    const previewTitle = preview.querySelector('[data-preview-title]');
    const previewBody = preview.querySelector('[data-preview-body]');
    const previewType = preview.querySelector('[data-preview-type]');
    const previewLayout = preview.querySelector('[data-preview-layout]');
    const previewAdvice = preview.querySelector('[data-preview-advice]');
    const textOf = (select) => select?.selectedOptions?.[0]?.textContent?.trim() || '';
    const noteOf = (select) => select?.selectedOptions?.[0]?.dataset?.note || '';
    function syncPreview() {
      const type = typeSelect?.value || 'text';
      const layout = layoutSelect?.value || 'default';
      if (typeNote) typeNote.textContent = noteOf(typeSelect);
      if (layoutNote) layoutNote.textContent = noteOf(layoutSelect);
      if (previewType) previewType.textContent = textOf(typeSelect);
      if (previewLayout) previewLayout.textContent = textOf(layoutSelect);
      if (previewTitle) previewTitle.textContent = titleInput?.value?.trim() || 'Bölüm başlığı';
      if (previewBody) previewBody.textContent = bodyInput?.value?.trim() || introInput?.value?.trim() || quoteInput?.value?.trim() || 'Bu alan, seçtiğin içerik tipi ve yerleşimin public sayfada nasıl davranacağını gösterir.';
      if (previewAdvice) {
        const mediaHeavy = ['gallery','video','split','code'].includes(type);
        const dramatic = ['hero-split','full-bleed','diagonal'].includes(layout);
        previewAdvice.textContent = mediaHeavy && dramatic
          ? 'Bu eşleşme güçlü görünür; uzun metin veya çok uzun başlık varsa public sayfada önizle.'
          : dramatic
            ? 'Bu yerleşim vurgu içindir; kısa başlık ve kısa metinle daha iyi çalışır.'
            : 'Okunurluk için güvenli seçim. Uzun metinlerde önce bunu tercih et.';
      }
    }
    [typeSelect, layoutSelect, titleInput, bodyInput, introInput, quoteInput].forEach((el) => el?.addEventListener('input', syncPreview));
    [typeSelect, layoutSelect].forEach((el) => el?.addEventListener('change', syncPreview));
    syncPreview();
  });
})();
