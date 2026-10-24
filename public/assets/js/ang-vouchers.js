/* ang-vouchers.js : filter toolbar, modal "Generate voucher", dan modal "Unduh brosur (ZIP)". */
(function () {
  'use strict';

  var fmtId = new Intl.NumberFormat('id-ID');
  var MAX_SHEETS = 1000;

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

  function currentList() {
    return scopeSel && scopeSel.value === 'all' ? codes : latest;
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
    var source = print.getAttribute('data-source') || 'BRO';

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

    var list = currentList();
    var scopeLabel = scopeSel && scopeSel.value === 'all' ? 'Semua kode' : 'Batch terakhir';
    sheets = buildSheets(list);

    if (summaryEl) summaryEl.textContent = scopeLabel + ': ' + list.length + ' kode → ' + sheets.length + ' lembar';
    showNote('');

    if (!codes.length) {
      showError('Belum ada kode tersedia untuk kanal ini.');
      startBtn.disabled = true;
      return;
    }

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

    if (list.length % slots.length !== 0) {
      showNote((list.length % slots.length) + ' kode tidak terpakai karena tidak penuh satu lembar.');
    }

    if (!img) showNote('Gambar template tidak dimuat, kode dicetak di atas latar polos.');

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
        ready = true;
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
        finish(files, cancelled);
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

  function finish(files, wasCancelled, error) {
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

    if (summaryEl) summaryEl.textContent = jumlah + ' lembar berhasil diunduh sebagai ZIP.';
    update();
  }

  if (startBtn) startBtn.addEventListener('click', run);
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function () {
      cancelled = true;
      if (progressTextEl) progressTextEl.textContent = 'Membatalkan...';
    });
  }

  if (scopeSel) {
    scopeSel.addEventListener('change', function () { if (!running) update(); });
  }

  // muat ulang data tiap modal dibuka (baik lewat tombol maupun auto-open)
  if (typeof MutationObserver === 'function') {
    new MutationObserver(function () {
      if (print.open) refresh();
    }).observe(print, { attributes: true, attributeFilter: ['open'] });
  }
  if (print.open) refresh();
})();
