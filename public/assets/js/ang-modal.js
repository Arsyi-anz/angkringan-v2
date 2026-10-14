/* ang-modal.js : buka/tutup <dialog class="ang-modal"> */
(function () {
  document.addEventListener('click', function (e) {
    var opener = e.target.closest('[data-ang-modal]');
    if (opener) {
      var dlg = document.getElementById(opener.getAttribute('data-ang-modal'));
      if (dlg && typeof dlg.showModal === 'function') {
        e.preventDefault();
        dlg.showModal();
      }
      return;
    }
    var closer = e.target.closest('[data-ang-close]');
    if (closer) {
      var parent = closer.closest('dialog');
      if (parent) parent.close();
      return;
    }
    // klik di area backdrop
    if (e.target.matches && e.target.matches('dialog.ang-modal')) e.target.close();
  });

  // buka otomatis saat ada error validasi dari server
  document.querySelectorAll('dialog.ang-modal[data-ang-open]').forEach(function (d) {
    if (typeof d.showModal === 'function') d.showModal();
  });
})();
