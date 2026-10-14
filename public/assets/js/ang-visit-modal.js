/* ang-visit-modal.js : combobox customer (cari nama / nomor WA) di modal Catat kunjungan */
(function () {
  var dlg = document.getElementById('ang-visit-modal');
  if (!dlg) return;

  var form = dlg.querySelector('form');
  var input = dlg.querySelector('#ang-v-search');
  var hidden = dlg.querySelector('#ang-v-customer');
  var list = dlg.querySelector('.ang-combo-list');
  var empty = dlg.querySelector('.ang-combo-empty');
  var err = dlg.querySelector('[data-ang-combo-error]');
  var items = [].slice.call(list.querySelectorAll('[role="option"]'));
  var active = -1;

  function digits(s) {
    var d = (s || '').replace(/\D/g, '');
    if (d.indexOf('62') === 0) d = '0' + d.slice(2);
    return d;
  }
  items.forEach(function (it) {
    it.dataset.name = (it.dataset.name || '').toLowerCase();
    it.dataset.wa = digits(it.dataset.waRaw);
  });

  function visible() { return items.filter(function (i) { return !i.hidden; }); }
  function byId(id) { return items.filter(function (i) { return i.dataset.id === String(id); })[0]; }

  function openList() { list.hidden = false; input.setAttribute('aria-expanded', 'true'); }
  function closeList() { list.hidden = true; input.setAttribute('aria-expanded', 'false'); setActive(-1); }

  function setActive(i) {
    var v = visible();
    items.forEach(function (x) { x.classList.remove('is-active'); x.setAttribute('aria-selected', 'false'); });
    active = i;
    if (i >= 0 && v[i]) {
      v[i].classList.add('is-active');
      v[i].setAttribute('aria-selected', 'true');
      v[i].scrollIntoView({ block: 'nearest' });
      input.setAttribute('aria-activedescendant', v[i].id);
    } else {
      input.removeAttribute('aria-activedescendant');
    }
  }

  function filter(q) {
    var t = q.toLowerCase().trim();
    var hasDigit = /\d/.test(q);
    var d = digits(q);
    items.forEach(function (it) {
      var ok = !t || it.dataset.name.indexOf(t) > -1 || (hasDigit && d && it.dataset.wa.indexOf(d) > -1);
      it.hidden = !ok;
    });
    empty.hidden = visible().length > 0;
    setActive(visible().length ? 0 : -1);
  }

  function choose(it) {
    hidden.value = it.dataset.id;
    input.value = it.dataset.label;
    input.classList.remove('is-invalid');
    err.hidden = true;
    closeList();
  }

  function clearChoice() { hidden.value = ''; input.value = ''; }

  // nilai awal (mis. setelah error validasi server)
  if (hidden.value && byId(hidden.value)) input.value = byId(hidden.value).dataset.label;

  input.addEventListener('focus', function () { input.select(); filter(''); openList(); });
  input.addEventListener('input', function () {
    hidden.value = '';
    filter(input.value);
    openList();
  });
  input.addEventListener('blur', function () {
    closeList();
    if (hidden.value && byId(hidden.value)) input.value = byId(hidden.value).dataset.label;
  });
  input.addEventListener('keydown', function (e) {
    var v = visible();
    if (e.key === 'ArrowDown') { e.preventDefault(); if (list.hidden) { filter(''); openList(); } else setActive(Math.min(active + 1, v.length - 1)); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(Math.max(active - 1, 0)); }
    else if (e.key === 'Enter' && !list.hidden) { e.preventDefault(); if (v[active]) choose(v[active]); }
    else if (e.key === 'Escape' && !list.hidden) { e.preventDefault(); e.stopPropagation(); closeList(); }
  });

  list.addEventListener('mousedown', function (e) {
    var it = e.target.closest('[role="option"]');
    if (!it) return;
    e.preventDefault(); // cegah input kehilangan fokus sebelum dipilih
    choose(it);
  });

  form.addEventListener('submit', function (e) {
    if (!hidden.value) {
      e.preventDefault();
      err.hidden = false;
      input.classList.add('is-invalid');
      input.focus();
    }
  });

  // tombol "+ Kunjungan" per baris mengisi customer otomatis
  document.addEventListener('click', function (e) {
    var opener = e.target.closest('[data-ang-modal="ang-visit-modal"]');
    if (!opener) return;
    var it = byId(opener.getAttribute('data-customer-id'));
    if (it) choose(it); else clearChoice();
    err.hidden = true;
    input.classList.remove('is-invalid');
  });

  dlg.addEventListener('close', closeList);
})();
