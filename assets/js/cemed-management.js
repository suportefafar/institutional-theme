(function () {
  'use strict';

  document.querySelectorAll('.fafar-cemed-manage').forEach(function (form) {
    var classificationOtherChoice = form.querySelector('[name="cemed_classificacao[]"][value="Outro"]');
    var classificationOtherWrap = form.querySelector('[data-cemed-classification-other]');
    var classificationOtherInput = form.querySelector('[name="cemed_classificacao_outro"]');
    var additionalDetailsWrap = form.querySelector('[data-cemed-additional-details]');
    var additionalDetails = form.querySelector('[name="cemed_informacoes_adicionais_detalhes"]');
    var additionalOtherChoice = form.querySelector('[name="cemed_informacao_adicional_tipo[]"][value="Outro"]');
    var additionalOtherWrap = form.querySelector('[data-cemed-additional-other]');
    var additionalOtherInput = form.querySelector('[name="cemed_informacao_adicional_outro"]');

    function updateFields() {
      var isAdditional = form.querySelector('[name="cemed_informacao_adicional"]:checked')?.value === 'Sim';
      var isClassificationOther = Boolean(classificationOtherChoice && classificationOtherChoice.checked);
      var isAdditionalOther = Boolean(additionalOtherChoice && additionalOtherChoice.checked);
      if (classificationOtherWrap) classificationOtherWrap.hidden = !isClassificationOther;
      if (classificationOtherInput && !isClassificationOther) classificationOtherInput.value = '';
      if (additionalDetailsWrap) additionalDetailsWrap.hidden = !isAdditional;
      if (additionalDetails && !isAdditional) additionalDetails.value = '';
      if (additionalOtherWrap) additionalOtherWrap.hidden = !isAdditional || !isAdditionalOther;
      if (additionalOtherInput && (!isAdditional || !isAdditionalOther)) additionalOtherInput.value = '';
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
