(function () {
  'use strict';

  document.querySelectorAll('.fafar-cemed-manage').forEach(function (form) {
    var classificationOtherChoice = form.querySelector('[name="cemed_classificacao[]"][value="Outro"]');
    var classificationOtherWrap = form.querySelector('[data-cemed-classification-other]');
    var classificationOtherInput = form.querySelector('[name="cemed_classificacao_outro"]');
    var additionalTypes = form.querySelectorAll('[name="cemed_informacao_adicional_tipo[]"]');
    var additionalDetailsWrap = form.querySelector('[data-cemed-additional-details]');
    var additionalDetails = form.querySelector('[name="cemed_informacoes_adicionais_detalhes"]');
    var additionalOtherChoice = form.querySelector('[name="cemed_informacao_adicional_tipo[]"][value="Outro"]');
    var additionalOtherWrap = form.querySelector('[data-cemed-additional-other]');
    var additionalOtherInput = form.querySelector('[name="cemed_informacao_adicional_outro"]');

    function updateFields() {
      var isAdditional = form.querySelector('[name="cemed_informacao_adicional"]:checked')?.value === 'Sim';
      var isNotAdditional = form.querySelector('[name="cemed_informacao_adicional"]:checked')?.value === 'Não';
      var isClassificationOther = Boolean(classificationOtherChoice && classificationOtherChoice.checked);
      var isAdditionalOther = Boolean(additionalOtherChoice && additionalOtherChoice.checked);
      if (classificationOtherWrap) classificationOtherWrap.hidden = !isClassificationOther;
      if (classificationOtherInput && !isClassificationOther) classificationOtherInput.value = '';
      if (additionalDetailsWrap) additionalDetailsWrap.hidden = !isAdditional;
      if (additionalDetails && !isAdditional) additionalDetails.value = '';
      if (additionalOtherWrap) additionalOtherWrap.hidden = !isAdditional || !isAdditionalOther;
      if (additionalOtherInput && (!isAdditional || !isAdditionalOther)) additionalOtherInput.value = '';

      var noneChoice = Array.from(additionalTypes).find(function (choice) { return choice.value === 'Nenhuma'; });
      if (isNotAdditional && noneChoice) {
        additionalTypes.forEach(function (choice) {
          choice.checked = choice === noneChoice;
          choice.disabled = choice !== noneChoice;
        });
      } else {
        if (isAdditional && noneChoice) noneChoice.checked = false;
        additionalTypes.forEach(function (choice) { choice.disabled = false; });
      }
    }

    form.addEventListener('change', function (event) {
      if (event.target.matches('[name="cemed_classificacao[]"], [name="cemed_informacao_adicional"], [name="cemed_informacao_adicional_tipo[]"]')) {
        var group = event.target.name;
        var none = event.target.value === 'Nenhuma';
        if (group === 'cemed_informacao_adicional_tipo[]' && event.target.checked) {
          additionalTypes.forEach(function (choice) {
            if (none ? choice !== event.target : choice.value === 'Nenhuma') choice.checked = false;
          });
        }
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
