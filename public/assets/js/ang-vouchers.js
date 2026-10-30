/* ang-vouchers.js : filter toolbar, modal "Generate voucher", dan modal "Unduh brosur (ZIP)". */
(function () {
  'use strict';

  var fmtId = new Intl.NumberFormat('id-ID');
  var MAX_SHEETS = 2000;

  function numberFormat(n) {
    return fmtId.format(n || 0);
  }

  /* ---------------- filter toolbar ---------------- */
  document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
    el.addEventListener('change', function () {
      var form = el.closest('form');
      if (form) form.submit();
    });
  });

  /* ---------------- modal generate ---------------- */
  var gen = document.getElementById('ang-gen-modal');
  if (gen) {
    var genSource = gen.querySelector('input[name="source"]');
    var genName = gen.querySelector('[data-gen-name]');
    var genFormat = gen.querySelector('[data-gen-format]');
    var genRemaining = gen.querySelector('[data-gen-remaining]');
    var genQty = gen.querySelector('#ang-gen-qty');

    var findOpener = function (source) {
      return document.querySelector('[data-ang-modal="ang-gen-modal"][data-gen-source="' + source + '"]');
    };

    var fillGen = function (source, opener) {
      source = String(
        source || (opener && opener.getAttribute('data-gen-source')) || ''
      ).toUpperCase().trim();
      if (!source || !genSource) return;

      var btn = opener || findOpener(source);
      var max = btn ? parseInt(btn.getAttribute('data-gen-max'), 10) : NaN;
      if (isNaN(max) || max < 0) max = 0;

      genSource.value = source;
      if (btn) {
        if (genName) genName.textContent = btn.getAttribute('data-gen-name') || '';
        if (genFormat) genFormat.textContent = btn.getAttribute('data-gen-format') || '';
      } else if (genFormat) {
        genFormat.textContent = source + '-XXXXXX';
      }
      if (genRemaining) genRemaining.textContent = numberFormat(max);
      if (genQty) genQty.max = String(max);
    };

    document.addEventListener('click', function (e) {
      var opener = e.target.closest('[data-ang-modal="ang-gen-modal"]');
      if (opener) fillGen('', opener);
    });

    // validasi server membuka ulang modal (data-ang-open)
    if (gen.hasAttribute('data-ang-open')) fillGen(genSource ? genSource.value : '');
  }

  /* ---------------- modal cetak brosur ---------------- */
  var print = document.getElementById('ang-print-modal');
  if (!print) return;

  var startBtn = print.querySelector('[data-print-start]');
  var cancelBtn = print.querySelector('[data-print-cancel]');
  var summaryEl = print.querySelector('[data-print-summary]');
  var noteEl = print.querySelector('[data-print-note]');
  var progressEl = print.querySelector('[data-print-progress]');
  var progressTextEl = print.querySelector('[data-print-progress-text]');
  var errorEl = print.querySelector('[data-print-error]');
  var scopeSel = print.querySelector('[data-print-scope]');
  var previewEl = print.querySelector('[data-print-canvas]');
  var rangeWrap = print.querySelector('[data-print-range]');
  var rangeFrom = print.querySelector('[data-print-range-from]');
  var rangeTo = print.querySelector('[data-print-range-to]');
  var markUrl = print.getAttribute('data-mark-url') || '';
  var source = print.getAttribute('data-source') || 'BRO';

  var templateUrl = print.getAttribute('data-template') || '';
  var refWidth = parseInt(print.getAttribute('data-width'), 10) || 1131;
  var refHeight = parseInt(print.getAttribute('data-height'), 10) || 1600;
  var slots = [];
  try {
    slots = JSON.parse(print.getAttribute('data-slots') || '[]');
  } catch (e) {
    slots = [];
  }

  var img = null;
  var codes = [];
  var latest = [];
  var all = [];
  var downloaded = 0;
  var ready = false;
  var running = false;
  var cancelled = false;
  var sheets = [];

  function showError(text) {
    if (!errorEl) return;
    errorEl.textContent = text || '';
    errorEl.hidden = !text;
  }

  function showNote(text) {
    if (!noteEl) return;
    noteEl.textContent = text || '';
    noteEl.hidden = !text;
  }

  function currentScope() {
    return scopeSel ? scopeSel.value : 'latest';
  }

  function scopeLabel() {
    var scope = currentScope();

    if (scope === 'all') return 'Semua kode';
    if (scope === 'custom') {
      return 'Rentang custom (kode ke-' + (rangeFrom ? rangeFrom.value : '?') + '–' + (rangeTo ? rangeTo.value : '?') + ')';
    }

    return 'Batch terakhir';
  }

  function perSheet() {
    return Math.max(1, slots.length);
  }

  // Total kode kanal (termasuk yang sudah terunduh): sumber penomoran rentang custom.
  function totalAll() {
    return all.length || codes.length;
  }

  // Ujung rentang default: nomor terakhir yang menutup satu lembar penuh.
  function defaultRangeEnd() {
    var total = totalAll();
    return Math.floor(total / perSheet()) * perSheet() || total;
  }

  function rangeBounds() {
    return {
      from: rangeFrom ? parseInt(rangeFrom.value, 10) : NaN,
      to: rangeTo ? parseInt(rangeTo.value, 10) : NaN,
    };
  }

  function customError() {
    var b = rangeBounds();
    var total = totalAll();

    if (isNaN(b.from) || isNaN(b.to)) return 'Isi nomor awal dan nomor akhir rentang.';
    if (b.from < 1 || b.to < 1) return 'Nomor rentang minimal 1.';
    if (b.to < b.from) return 'Nomor akhir harus sama dengan atau lebih besar dari nomor awal.';
    if (b.from > total) return 'Nomor awal melebihi jumlah kode (' + numberFormat(total) + ').';
    if (b.to > total) return 'Nomor akhir melebihi jumlah kode (' + numberFormat(total) + ').';

    return null;
  }

  // Irisan rentang pada nomor stabil (seluruh kode, paling lama = 1).
  // Kode yang sudah terunduh tetap bernomor tapi tidak dicetak.
  function rangeSelection() {
    var b = rangeBounds();
    var slice = all.slice(b.from - 1, b.to);
    var list = [];
    var dilewati = 0;

    slice.forEach(function (item) {
      if (item.downloaded) {
        dilewati += 1;
      } else {
        list.push(item.code);
      }
    });

    return { list: list, total: slice.length, dilewati: dilewati };
  }

  // tampilkan/sembunyikan input rentang; autofill hanya saat pindah scope atau data dimuat ulang
  function syncRangeUI(autofill) {
    var custom = currentScope() === 'custom';
    if (rangeWrap) rangeWrap.hidden = !custom;
    if (!custom || !rangeFrom || !rangeTo) return;

    var total = totalAll();
    rangeFrom.max = String(total || 1);
    rangeTo.max = String(total || 1);

    if (!autofill) return;

    var b = rangeBounds();
    if (isNaN(b.from) || b.from < 1 || b.from > total) rangeFrom.value = '1';
    if (isNaN(b.to) || b.to < 1 || b.to > total) rangeTo.value = String(defaultRangeEnd() || 1);
  }

  function buildSheets(list) {
    // Selalu satu kode per voucher: satu lembar memakai sejumlah slot kode berbeda.
    var per = Math.max(1, slots.length);
    var hasil = [];

    for (var i = 0; i + per <= list.length; i += per) {
      hasil.push(list.slice(i, i + per));
    }

    return hasil;
  }

  function canvasSize() {
    return {
      w: (img && img.naturalWidth) || refWidth,
      h: (img && img.naturalHeight) || refHeight,
    };
  }

  function render(canvas, sheet) {
    var size = canvasSize();
    var scaleX = size.w / refWidth;
    var scaleY = size.h / refHeight;
    var ctx = canvas.getContext('2d');

    canvas.width = size.w;
    canvas.height = size.h;

    if (img) {
      ctx.drawImage(img, 0, 0, size.w, size.h);
    } else {
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, size.w, size.h);
    }

    slots.forEach(function (slot, index) {
      var code = sheet[index];
      if (!code) return;

      var x = slot.x * scaleX;
      var y = slot.y * scaleY;
      var w = slot.w * scaleX;
      var h = slot.h * scaleY;

      ctx.fillStyle = '#ffffff';
      ctx.fillRect(x, y, w, h);
      ctx.fillStyle = '#111111';
      ctx.font = '700 ' + Math.max(10, Math.round(h * 0.62)) + 'px "Plus Jakarta Sans", Arial, sans-serif';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(code, x + w / 2, y + h / 2, w * 0.94);
    });
  }

  function drawPreview(sheet) {
    if (!previewEl || !sheet) return;
    render(previewEl, sheet);
  }

  function canvasBytes(canvas) {
    return new Promise(function (resolve, reject) {
      canvas.toBlob(function (blob) {
        if (!blob) {
          reject(new Error('Gagal merender gambar PNG.'));
          return;
        }
        var reader = new FileReader();
        reader.onload = function () { resolve(new Uint8Array(reader.result)); };
        reader.onerror = function () { reject(new Error('Gagal membaca gambar PNG.')); };
        reader.readAsArrayBuffer(blob);
      }, 'image/png');
    });
  }

  function loadTemplate() {
    return new Promise(function (resolve) {
      if (!templateUrl) {
        resolve(false);
        return;
      }
      var image = new Image();
      image.onload = function () { img = image; resolve(true); };
      image.onerror = function () { img = null; resolve(false); };
      image.src = templateUrl;
    });
  }

  function loadCodes() {
    var url = print.getAttribute('data-codes-url');

    if (!url) return Promise.reject(new Error('URL daftar kode belum diatur.'));

    var sep = url.indexOf('?') === -1 ? '?' : '&';

    return fetch(url + sep + 'source=' + encodeURIComponent(source), {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    }).then(function (res) {
      if (!res.ok) throw new Error('Gagal memuat daftar kode (' + res.status + ').');
      return res.json();
    });
  }

  function update() {
    if (!ready) return;

    if (!codes.length) {
      sheets = [];
      if (summaryEl) summaryEl.textContent = scopeLabel() + ': 0 kode';
      showNote('');
      showError(
        downloaded > 0
          ? 'Semua kode sudah pernah diunduh. Generate kode baru untuk melanjutkan.'
          : 'Belum ada kode tersedia untuk kanal ini.'
      );
      if (startBtn) startBtn.disabled = true;
      return;
    }

    var scope = currentScope();
    var list;
    var dilewati = 0;
    var rangeTotal = 0;

    if (scope === 'custom') {
      var rangeErr = customError();
      if (rangeErr) {
        sheets = [];
        if (summaryEl) summaryEl.textContent = 'Rentang custom belum valid.';
        showNote('');
        showError(rangeErr);
        if (startBtn) startBtn.disabled = true;
        return;
      }

      var sel = rangeSelection();
      list = sel.list;
      dilewati = sel.dilewati;
      rangeTotal = sel.total;

      if (!list.length) {
        sheets = [];
        if (summaryEl) summaryEl.textContent = scopeLabel() + ': 0 kode dari ' + numberFormat(rangeTotal) + ' dipilih';
        showNote('');
        showError(
          rangeTotal
            ? 'Semua kode pada rentang ini sudah diunduh.'
            : 'Kode tidak cukup untuk satu lembar (butuh ' + slots.length + ' kode per lembar).'
        );
        if (startBtn) startBtn.disabled = true;
        return;
      }
    } else {
      list = scope === 'all' ? codes : latest;
    }

    var label = scopeLabel();
    sheets = buildSheets(list);

    if (summaryEl) {
      summaryEl.textContent = label + ': ' + numberFormat(list.length) + ' kode' +
        (dilewati ? ' (' + numberFormat(dilewati) + ' terunduh dilewati)' : '') +
        ' → ' + numberFormat(sheets.length) + ' lembar';
    }
    showNote('');

    if (!list.length) {
      showError(
        scopeSel && scopeSel.value === 'all'
          ? 'Belum ada kode tersedia untuk kanal ini.'
          : 'Batch terakhir tidak ditemukan. Belum ada kode yang dibuat.'
      );
      startBtn.disabled = true;
      return;
    }

    if (!sheets.length) {
      showError('Kode tidak cukup untuk satu lembar (butuh ' + slots.length + ' kode per lembar).');
      startBtn.disabled = true;
      return;
    }

    if (sheets.length > MAX_SHEETS) {
      showError('Terlalu banyak lembar (' + sheets.length + '). Batas per unduhan ' + MAX_SHEETS + ' lembar.');
      startBtn.disabled = true;
      return;
    }

    var catatan = [];
    if (list.length % slots.length !== 0) {
      catatan.push((list.length % slots.length) + ' kode tidak terpakai karena tidak penuh satu lembar.');
    }
    if (dilewati) {
      catatan.push(numberFormat(dilewati) + ' kode pada rentang ini sudah terunduh dan dilewati.');
    }
    if (!img) catatan.push('Gambar template tidak dimuat, kode dicetak di atas latar polos.');
    if (downloaded > 0) catatan.push(numberFormat(downloaded) + ' kode sudah pernah diunduh dan tidak disertakan.');
    showNote(catatan.join(' '));

    showError('');
    if (startBtn) startBtn.disabled = false;
    drawPreview(sheets[0]);
  }

  function refresh() {
    if (running) return;

    ready = false;
    img = null;
    codes = [];
    latest = [];
    all = [];
    downloaded = 0;
    sheets = [];
    if (startBtn) startBtn.disabled = true;
    if (summaryEl) summaryEl.textContent = 'Memuat data...';
    showError('');
    showNote('');

    Promise.all([loadTemplate(), loadCodes()])
      .then(function (results) {
        var data = results[1] || {};
        codes = data.codes || [];
        latest = data.latest || [];
        all = data.all || [];
        downloaded = parseInt(data.downloaded, 10) || 0;
        ready = true;
        syncRangeUI(true);
        update();
      })
      .catch(function (err) {
        if (summaryEl) summaryEl.textContent = 'Memuat data gagal.';
        showError(err.message);
        ready = false;
        if (startBtn) startBtn.disabled = true;
      });
  }

  function download(blob, filename) {
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(function () { URL.revokeObjectURL(url); }, 2000);
  }

  function stamp() {
    var d = new Date();
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
    return '' + d.getFullYear() + pad(d.getMonth() + 1) + pad(d.getDate()) +
      pad(d.getHours()) + pad(d.getMinutes()) + pad(d.getSeconds());
  }

  // Tandai kode ke server agar tidak bisa diunduh lagi. Mengembalikan jumlah kode yang ditandai.
  function markDownloaded(list) {
    if (!markUrl || !list.length) return Promise.resolve(0);

    var meta = document.querySelector('meta[name="csrf-token"]');

    return fetch(markUrl, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': meta ? meta.getAttribute('content') : '',
      },
      body: JSON.stringify({ source: source, codes: list }),
    }).then(function (res) {
      if (!res.ok) throw new Error('Penandaan kode gagal (' + res.status + ').');
      return res.json();
    }).then(function (data) {
      return (data && data.marked) || 0;
    });
  }

  // Kode yang barusan ditandai server: keluar dari daftar cetak,
  // tapi tetap bernomor di `all` dengan status terunduh (penomoran stabil).
  function applyDownloaded(list) {
    var gone = {};
    list.forEach(function (kode) { gone[kode] = true; });

    codes = codes.filter(function (kode) { return !gone[kode]; });
    latest = latest.filter(function (kode) { return !gone[kode]; });
    all.forEach(function (item) {
      if (gone[item.code]) item.downloaded = true;
    });
  }

  function run() {
    if (running || !ready) return;

    var antrean = sheets.slice();
    if (!antrean.length) return;

    running = true;
    cancelled = false;
    if (startBtn) startBtn.disabled = true;
    if (cancelBtn) cancelBtn.hidden = false;
    showError('');
    showNote('');
    if (progressEl) {
      progressEl.max = antrean.length;
      progressEl.value = 0;
      progressEl.hidden = false;
    }
    if (progressTextEl) {
      progressTextEl.hidden = false;
      progressTextEl.textContent = 'Menyiapkan...';
    }

    var files = {};
    var index = 0;

    var step = function () {
      if (cancelled || index >= antrean.length) {
        finish(files, cancelled, null, antrean);
        return;
      }

      var canvas = document.createElement('canvas');
      render(canvas, antrean[index]);
      drawPreview(antrean[index]);
      index += 1;

      if (progressEl) progressEl.value = index;
      if (progressTextEl) progressTextEl.textContent = 'Lembar ' + index + ' dari ' + antrean.length;

      canvasBytes(canvas)
        .then(function (bytes) {
          files['voucher/lembar-' + ('0000' + index).slice(-4) + '.png'] = bytes;
          setTimeout(step, 0);
        })
        .catch(function (err) {
          running = false;
          finish({}, true, err);
        });
    };

    step();
  }

  function finish(files, wasCancelled, error, sheetList) {
    running = false;
    if (cancelBtn) cancelBtn.hidden = true;
    if (progressEl) progressEl.hidden = true;
    if (progressTextEl) progressTextEl.hidden = true;
    if (startBtn) startBtn.disabled = !ready;

    if (error) {
      showError(error.message);
      return;
    }

    if (wasCancelled) {
      if (summaryEl) summaryEl.textContent = 'Unduhan dibatalkan.';
      update();
      return;
    }

    var jumlah = Object.keys(files).length;
    if (!jumlah) {
      showError('Tidak ada lembar yang dibuat.');
      return;
    }

    if (typeof fflate === 'undefined') {
      showError('Library ZIP (fflate) tidak termuat.');
      return;
    }

    var zip = fflate.zipSync(files, { level: 6 });
    download(new Blob([zip], { type: 'application/zip' }), 'brosur-voucher-' + stamp() + '.zip');

    var kode = [];
    (sheetList || []).forEach(function (sheet) { kode = kode.concat(sheet); });

    // ZIP sudah terunduh: tandai kode di server supaya tidak bisa diunduh lagi.
    markDownloaded(kode)
      .then(function (marked) {
        downloaded += marked;
        applyDownloaded(kode);
        update();
        if (summaryEl) {
          summaryEl.textContent = jumlah + ' lembar (' + numberFormat(kode.length) +
            ' kode) berhasil diunduh dan ditandai sudah terunduh.';
        }
      })
      .catch(function () {
        update();
        showNote('ZIP berhasil diunduh, tetapi penandaan kode di server gagal. Muat ulang halaman sebelum mengunduh lagi agar kode tidak terunduh dua kali.');
      });
  }

  if (startBtn) startBtn.addEventListener('click', run);
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function () {
      cancelled = true;
      if (progressTextEl) progressTextEl.textContent = 'Membatalkan...';
    });
  }

  if (scopeSel) {
    scopeSel.addEventListener('change', function () {
      if (running) return;
      syncRangeUI(true);
      update();
    });
  }

  [rangeFrom, rangeTo].forEach(function (el) {
    if (!el) return;
    el.addEventListener('input', function () { if (!running) update(); });
    el.addEventListener('change', function () { if (!running) update(); });
  });

  // muat ulang data tiap modal dibuka (baik lewat tombol maupun auto-open)
  if (typeof MutationObserver === 'function') {
    new MutationObserver(function () {
      if (print.open) refresh();
    }).observe(print, { attributes: true, attributeFilter: ['open'] });
  }
  if (print.open) refresh();
})();
