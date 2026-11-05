(function () {
  'use strict';

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-cemed-delete-post]');
    if (form && !window.confirm(form.getAttribute('data-confirm'))) {
      event.preventDefault();
    }
  });
}());
