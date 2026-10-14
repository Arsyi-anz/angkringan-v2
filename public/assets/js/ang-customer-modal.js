/* ang-customer-modal.js : ganti mode modal Tambah <-> Edit berdasarkan tombol yang diklik */
(function () {
  var dlg = document.getElementById('ang-customer-modal');
  if (!dlg) return;

  var form = dlg.querySelector('#ang-customer-form');
  var title = dlg.querySelector('.ang-modal-title');
  var submit = dlg.querySelector('button[type="submit"]');
  var fEdit = dlg.querySelector('#ang-f-edit');
  var fName = dlg.querySelector('#ang-f-name');
  var fWa = dlg.querySelector('#ang-f-wa');
  var fBirth = dlg.querySelector('#ang-f-birth');
  var fConsent = dlg.querySelector('input[name="setuju_pesan"][type="checkbox"]');

  function setMethod(method) {
    var el = form.querySelector('input[name="_method"]');
    if (!method) {
      if (el) el.remove();
      return;
    }
    if (!el) {
      el = document.createElement('input');
      el.type = 'hidden';
      el.name = '_method';
      form.appendChild(el);
    }
    el.value = method;
  }

  function resetErrors() {
    dlg.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    dlg.querySelectorAll('.ang-error').forEach(function (el) { el.hidden = true; });
  }

  document.addEventListener('click', function (e) {
    var opener = e.target.closest('[data-ang-modal="ang-customer-modal"]');
    if (!opener) return;

    var id = opener.getAttribute('data-edit-id');

    if (id) {
      form.action = form.getAttribute('data-edit-base') + '/' + id;
      setMethod('PUT');
      fEdit.value = id;
      fName.value = opener.getAttribute('data-edit-nama') || '';
      fWa.value = opener.getAttribute('data-edit-wa') || '';
      fBirth.value = opener.getAttribute('data-edit-birth') || '';
      if (fConsent) fConsent.checked = opener.getAttribute('data-edit-consent') === '1';
      title.textContent = 'Edit customer';
      submit.textContent = 'Simpan perubahan';
    } else {
      form.action = form.getAttribute('data-store-action');
      setMethod('');
      fEdit.value = '';
      fName.value = '';
      fWa.value = '';
      fBirth.value = '';
      if (fConsent) fConsent.checked = false;
      title.textContent = 'Tambah customer';
      submit.textContent = 'Simpan customer';
    }

    resetErrors();
  });
})();
