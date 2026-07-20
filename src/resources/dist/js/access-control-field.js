(function () {
  function initField(field) {
    if (!field || field.dataset.scaReady === '1') {
      return;
    }

    field.dataset.scaReady = '1';

    var audiences = field.querySelector('[data-sca-audiences]');
    var emptyWarning = field.querySelector('[data-sca-empty]');
    var users = field.querySelector('[data-sca-users]');

    function isRestricted() {
      var checked = field.querySelector('[data-sca-mode]:checked');
      return !!checked && checked.getAttribute('data-sca-mode') === 'restricted';
    }

    function hasAudience() {
      var boxes = field.querySelectorAll('[data-sca-audience]');
      for (var i = 0; i < boxes.length; i++) {
        if (boxes[i].checked) {
          return true;
        }
      }

      return !!(users && users.querySelectorAll('.element').length > 0);
    }

    function sync() {
      var restricted = isRestricted();

      if (audiences) {
        audiences.classList.toggle('hidden', !restricted);
      }

      if (emptyWarning) {
        emptyWarning.classList.toggle('hidden', !(restricted && !hasAudience()));
      }
    }

    field.querySelectorAll('[data-sca-mode]').forEach(function (input) {
      input.addEventListener('change', sync);
    });

    if (audiences) {
      audiences.addEventListener('change', sync);

      if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(sync).observe(audiences, { childList: true, subtree: true });
      }
    }

    sync();
  }

  function initAll() {
    document.querySelectorAll('[data-sca-field]').forEach(initField);
  }

  initAll();

  if (typeof MutationObserver !== 'undefined') {
    new MutationObserver(initAll).observe(document.body, { childList: true, subtree: true });
  }
})();
