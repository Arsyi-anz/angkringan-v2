/* ang-settings.js : ganti gambar brosur (pratinjau sebelum disimpan) + zoom */
(function () {
  var MAX_MB = 4;
  var TYPES = ['image/jpeg', 'image/png', 'image/webp'];
  var form = document.getElementById('ang-broc-form');
  if (!form) return;

  var saveBtn = form.querySelector('[data-broc-save]');
  var cancelBtn = form.querySelector('[data-broc-cancel]');
  var cards = [].slice.call(form.querySelectorAll('.ang-broc'));

  function refresh() {
    var changed = cards.some(function (c) { return c.classList.contains('is-changed'); });
    saveBtn.disabled = !changed;
    cancelBtn.hidden = !changed;
  }

  cards.forEach(function (card) {
    var input = card.querySelector('input[type="file"]');
    var img = card.querySelector('.ang-broc-view img');
    var err = card.querySelector('[data-broc-error]');
    var view = card.querySelector('.ang-broc-view');
    var objectUrl = null;

    img.dataset.original = img.getAttribute('src');

    input.addEventListener('change', function () {
      var f = input.files && input.files[0];
      err.hidden = true;
      if (!f) return;
      if (TYPES.indexOf(f.type) === -1) { fail('Format harus JPG, PNG, atau WebP.'); return; }
      if (f.size > MAX_MB * 1024 * 1024) { fail('Ukuran maksimal ' + MAX_MB + ' MB.'); return; }
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = URL.createObjectURL(f);
      img.src = objectUrl;
      view.setAttribute('data-ang-zoom-src', objectUrl);
      card.classList.add('is-changed');
      refresh();
    });

    function fail(msg) {
      input.value = '';
      err.textContent = msg;
      err.hidden = false;
    }

    card.addEventListener('ang:reset', function () {
      input.value = '';
      img.src = img.dataset.original;
      view.setAttribute('data-ang-zoom-src', img.dataset.original);
      card.classList.remove('is-changed');
      err.hidden = true;
    });
  });

  cancelBtn.addEventListener('click', function () {
    cards.forEach(function (c) { c.dispatchEvent(new Event('ang:reset')); });
    refresh();
  });

  // zoom: isi gambar lightbox dari tombol pemicu
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-ang-zoom-src]');
    if (!t) return;
    var dlg = document.getElementById('ang-zoom-modal');
    if (!dlg) return;
    dlg.querySelector('img').src = t.getAttribute('data-ang-zoom-src');
    dlg.querySelector('[data-zoom-title]').textContent = t.getAttribute('data-ang-zoom-title') || '';
  });

  refresh();
})();
