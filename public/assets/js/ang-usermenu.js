/* ang-usermenu.js : buka/tutup dropdown menu pengguna */
(function () {
  var root = document.querySelector('[data-um]');
  if (!root) return;
  var btn = root.querySelector('[data-um-btn]');
  var menu = root.querySelector('[data-um-menu]');

  function setOpen(open) {
    menu.hidden = !open;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  btn.addEventListener('click', function () { setOpen(menu.hidden); });
  document.addEventListener('click', function (e) { if (!root.contains(e.target)) setOpen(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !menu.hidden) { setOpen(false); btn.focus(); }
  });
})();
