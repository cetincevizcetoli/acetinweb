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
})();
