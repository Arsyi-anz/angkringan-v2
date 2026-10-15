/* ang-validate.js : normalisasi input kode + dialog konfirmasi "Tandai USED" */
(function () {
  var code = document.getElementById('ang-code');
  if (code) {
    code.addEventListener('input', function () {
      var pos = code.selectionStart;
      var before = code.value.length;
      code.value = code.value.toUpperCase().replace(/\s+/g, '');
      var diff = before - code.value.length;
      try { code.setSelectionRange(pos - diff, pos - diff); } catch (e) {}
    });
  }

  var dlg = document.getElementById('ang-redeem-modal');
  if (!dlg) return;
  var form = dlg.querySelector('form');
  var codeEl = dlg.querySelector('[data-redeem-code]');
  var submit = dlg.querySelector('[type="submit"]');

  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-redeem-action]');
    if (!t) return;
    form.setAttribute('action', t.getAttribute('data-redeem-action'));
    codeEl.textContent = t.getAttribute('data-redeem-code');
    submit.disabled = false;
  });

  // cegah klik ganda saat memproses
  form.addEventListener('submit', function () { submit.disabled = true; });
})();
