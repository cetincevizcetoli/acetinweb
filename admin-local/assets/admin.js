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
      item.addEventListener('dragstart', () => { dragging = item; item.classList.add('dragging'); });
      item.addEventListener('dragend', () => { item.classList.remove('dragging'); dragging = null; updateOrder(list); });
      item.addEventListener('dragover', (e) => {
        e.preventDefault();
        if (!dragging || dragging === item) return;
        const rect = item.getBoundingClientRect();
        list.insertBefore(dragging, e.clientY < rect.top + rect.height / 2 ? item : item.nextSibling);
      });
    });
  });

  function updateOrder(list) {
    list.querySelectorAll('[data-order-input]').forEach((input, i) => { input.value = String(i + 1); });
  }

  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
      if (!confirm(el.dataset.confirm || 'Emin misiniz?')) e.preventDefault();
    });
  });

  document.querySelectorAll('[data-section-preview]').forEach((preview) => {
    const form = preview.closest('form');
    if (!form) return;

    const typeSelect = form.querySelector('[data-section-type]');
    const layoutSelect = form.querySelector('[data-section-layout]');
    const typeNote = form.querySelector('[data-section-type-note]');
    const layoutNote = form.querySelector('[data-section-layout-note]');
    const previewTitle = preview.querySelector('[data-preview-title]');
    const previewBody = preview.querySelector('[data-preview-body]');
    const previewType = preview.querySelector('[data-preview-type]');
    const previewLayout = preview.querySelector('[data-preview-layout]');
    const previewAdvice = preview.querySelector('[data-preview-advice]');

    const textOf = (select) => select?.selectedOptions?.[0]?.textContent?.trim() || '';
    const noteOf = (select) => select?.selectedOptions?.[0]?.dataset?.note || '';

    const typeDescriptions = {
      text: 'Metin agirlikli bolum. Baslik, paragraflar ve gerekirse alinti okunur kalir.',
      hero: 'Kisa ve vurucu acilis bolumu. Uzun metin veya cok satirli icerik icin uygun degildir.',
      gallery: 'Gorsel agirlikli bolum. Medya yoksa sistem sade metin gorunumune iner.',
      video: 'Video veya hareket odakli bolum. Aciklama kisa tutulursa daha iyi calisir.',
      code: 'Kod, terminal veya teknik cikti bolumu. Metin kod kutusunun etrafinda destekleyici kalir.',
      lesson: 'Ogrenilenler veya dersler icin satirli yapi. Satir eklenirse daha okunakli olur.',
      compare: 'Iki tarafli karsilastirma bolumu. Sol/sag satirlar varsa anlam kazanir.',
      status: 'Durum, sonuc veya ilerleme ozeti. Kisa satirlarla kullanilmalidir.',
      timeline: 'Zaman akisina uygun bolum. Tarih veya adim satirlariyla calisir.',
      question: 'Soru-cevap veya karar bolumu. Sorular satirlar halinde girilirse daha net olur.'
    };

    const layoutDescriptions = {
      default: 'Standart okuma. En guvenli ve en cok kullanilacak yerlesim.',
      wide: 'Genis okuma. Uzun metinlerde ve sakin anlatimda guvenli secim.',
      split: 'Metin ve medya yan yana. Medya varsa kullan; yoksa standart gorunum daha iyi olur.',
      'hero-split': 'Buyuk acilis etkisi. Kisa baslik ve az metin ister.',
      'full-bleed': 'Gorseli one alan sahne. Metin az, medya guclu olmali.',
      diagonal: 'Daha hareketli vurgu. Uzun metinde okunurlugu zorlayabilir.',
      compact: 'Kisa not veya ara bolum. Uzun anlatim icin secilmemeli.'
    };

    function syncPreview() {
      const type = typeSelect?.value || 'text';
      const layout = layoutSelect?.value || 'default';

      if (typeNote) typeNote.textContent = noteOf(typeSelect);
      if (layoutNote) layoutNote.textContent = noteOf(layoutSelect);
      if (previewType) previewType.textContent = textOf(typeSelect);
      if (previewLayout) previewLayout.textContent = textOf(layoutSelect);
      if (previewTitle) previewTitle.textContent = 'Secimin davranisi';
      if (previewBody) previewBody.textContent = typeDescriptions[type] || 'Bu secim, bolumu public sayfada uygun bir okuma kalibina yerlestirir.';

      if (previewAdvice) {
        const mediaHeavy = ['gallery', 'video', 'split', 'code'].includes(type);
        const dramatic = ['hero-split', 'full-bleed', 'diagonal'].includes(layout);
        previewAdvice.textContent = mediaHeavy && dramatic
          ? 'Medya gucluyse kullan. Metin uzunsa daha sade yerlesime gec.'
          : dramatic
            ? 'Vurgu yerlesimi. Kisa baslik ve kisa aciklama ister.'
            : (layoutDescriptions[layout] || 'Okunurluk icin guvenli secim.');
      }
    }

    [typeSelect, layoutSelect].forEach((el) => el?.addEventListener('change', syncPreview));
    syncPreview();
  });
})();
