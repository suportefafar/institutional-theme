(function () {
  'use strict';

  var form = document.querySelector('.fafar-ppgmaf-form');
  if (!form) return;

  function selected(name) {
    var input = form.querySelector('input[name="' + name + '"]:checked');
    return input ? input.value : '';
  }

  function updateConditionalFields() {
    var naturalidade = selected('Naturalidade');
    var origem = selected('Origem');
    var states = {
      brasileiro: naturalidade !== '' && naturalidade !== 'Estrangeiro',
      estrangeiro: naturalidade === 'Estrangeiro',
      servidor: origem === 'Servidor'
    };

    form.querySelectorAll('[data-ppgmaf-show]').forEach(function (group) {
      var visible = Boolean(states[group.getAttribute('data-ppgmaf-show')]);
      group.hidden = !visible;
      group.querySelectorAll('input, select').forEach(function (field) {
        field.disabled = !visible;
        if (field.hasAttribute('data-ppgmaf-file') || field.name !== 'ComplementoResidencia') {
          field.required = visible;
        }
      });
    });
  }

  form.addEventListener('change', function (event) {
    if (event.target.name === 'Naturalidade' || event.target.name === 'Origem') updateConditionalFields();
  });
  form.querySelectorAll('[data-ppgmaf-mask]').forEach(function (field) {
    field.addEventListener('input', function () {
      var digits = field.value.replace(/\D/g, '').slice(0, field.dataset.ppgmafMask === 'cpf' ? 11 : field.dataset.ppgmafMask === 'cep' ? 8 : 11);
      if (field.dataset.ppgmafMask === 'cpf') {
        field.value = digits.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
      } else if (field.dataset.ppgmafMask === 'cep') {
        field.value = digits.replace(/(\d{5})(\d)/, '$1-$2');
      } else {
        field.value = digits.length > 10 ? digits.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3') : digits.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
      }
    });
  });
  updateConditionalFields();
})();
