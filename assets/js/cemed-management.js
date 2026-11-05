(function () {
  'use strict';

  document.querySelectorAll('.fafar-cemed-manage').forEach(function (form) {
    var classificationOtherChoice = form.querySelector('[name="cemed_classificacao[]"][value="Outro"]');
    var classificationOtherWrap = form.querySelector('[data-cemed-classification-other]');
    var additionalDetailsWrap = form.querySelector('[data-cemed-additional-details]');
    var additionalOtherChoice = form.querySelector('[name="cemed_informacao_adicional_tipo[]"][value="Outro"]');
    var additionalOtherWrap = form.querySelector('[data-cemed-additional-other]');

    function updateFields() {
      var isAdditional = form.querySelector('[name="cemed_informacao_adicional"]:checked')?.value === 'Sim';
      var isClassificationOther = Boolean(classificationOtherChoice && classificationOtherChoice.checked);
      var isAdditionalOther = Boolean(additionalOtherChoice && additionalOtherChoice.checked);
      if (classificationOtherWrap) classificationOtherWrap.hidden = !isClassificationOther;
      if (additionalDetailsWrap) additionalDetailsWrap.hidden = !isAdditional;
      if (additionalOtherWrap) additionalOtherWrap.hidden = !isAdditional || !isAdditionalOther;
    }

    form.addEventListener('change', function (event) {
      if (event.target.matches('[name="cemed_classificacao[]"], [name="cemed_informacao_adicional"], [name="cemed_informacao_adicional_tipo[]"]')) {
        updateFields();
      }
    });

    updateFields();
  });

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-cemed-delete-post]');
    if (form && !window.confirm(form.getAttribute('data-confirm'))) {
      event.preventDefault();
    }
  });
}());
