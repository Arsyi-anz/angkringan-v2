/* ang-dropdown.js : buka/tutup dropdown generik ([data-ang-dd]) */
(function () {
  var roots = document.querySelectorAll('[data-ang-dd]');
  if (!roots.length) return;

  function closeAll(except) {
    roots.forEach(function (root) {
      if (root === except) return;
      root.querySelector('[data-ang-dd-menu]').hidden = true;
      root.querySelector('[data-ang-dd-btn]').setAttribute('aria-expanded', 'false');
    });
  }

  roots.forEach(function (root) {
    var btn = root.querySelector('[data-ang-dd-btn]');
    var menu = root.querySelector('[data-ang-dd-menu]');
    if (!btn || !menu) return;

    function setOpen(open) {
      menu.hidden = !open;
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      closeAll(root);
      setOpen(menu.hidden);
    });
  });

  document.addEventListener('click', function (e) {
    roots.forEach(function (root) {
      if (root.contains(e.target)) return;
      root.querySelector('[data-ang-dd-menu]').hidden = true;
      root.querySelector('[data-ang-dd-btn]').setAttribute('aria-expanded', 'false');
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    roots.forEach(function (root) {
      var menu = root.querySelector('[data-ang-dd-menu]');
      if (menu.hidden) return;
      menu.hidden = true;
      var btn = root.querySelector('[data-ang-dd-btn]');
      btn.setAttribute('aria-expanded', 'false');
      btn.focus();
    });
  });
})();
