(() => {
  const form = document.querySelector('.fafar-cemed-form[data-cemed-nonce-url]');
  if (!form) return;

  const digitsOnly = (value, limit) => value.replace(/\D/g, '').slice(0, limit);

  const professionWrap = form.querySelector('[data-cemed-profissao-wrap]');
  const otherProfessionWrap = form.querySelector('[data-cemed-profissao-outro-wrap]');
  const otherProfessionInput = form.querySelector('[name="profissao_saude_outro"]');
  const patientSection = form.querySelector('[data-cemed-patient-section]');
  const patientAge = form.querySelector('[name="idade_paciente"]');
  const patientSex = form.querySelector('[name="sexo_paciente"]');
  const patientGender = form.querySelector('[name="genero_paciente"]');
  const diseaseOther = form.querySelector('[name="doencas_preexistentes_outro"]');
  const specialOther = form.querySelector('[name="condicoes_especiais_outro"]');
  const toggleOtherField = (name, wrapperSelector, input) => {
    const selected = [...form.querySelectorAll(`[name="${name}"]:checked`)].some((choice) => choice.value === 'Outro');
    const visible = patientSection && !patientSection.hidden && selected;
    const wrapper = form.querySelector(wrapperSelector);
    if (wrapper) wrapper.hidden = !visible;
    if (input) {
      input.required = Boolean(visible);
      if (!visible) input.value = '';
    }
  };
  const updatePatientFields = () => {
    const isSpecificPatient = form.querySelector('[name="paciente_especifico"]:checked')?.value === 'Sim';
    if (patientSection) patientSection.hidden = !isSpecificPatient;
    for (const field of [patientAge, patientSex, patientGender]) {
      if (field) field.required = isSpecificPatient;
    }
    for (const name of ['doencas_preexistentes[]', 'condicoes_especiais[]']) {
      const checkboxes = [...form.querySelectorAll(`[name="${name}"]`)];
      for (const checkbox of checkboxes) checkbox.required = false;
      if (checkboxes[0]) {
        const hasSelection = checkboxes.some((checkbox) => checkbox.checked);
        checkboxes[0].setCustomValidity(isSpecificPatient && !hasSelection ? 'Selecione pelo menos uma opção.' : '');
      }
    }
    if (!isSpecificPatient) {
      for (const checkbox of form.querySelectorAll('[name="doencas_preexistentes[]"], [name="condicoes_especiais[]"]')) checkbox.checked = false;
      if (patientAge) patientAge.value = '';
      if (patientSex) patientSex.value = '';
      if (patientGender) patientGender.value = '';
    }
    toggleOtherField('doencas_preexistentes[]', '[data-cemed-diseases-other-wrap]', diseaseOther);
    toggleOtherField('condicoes_especiais[]', '[data-cemed-special-other-wrap]', specialOther);
  };
  form.addEventListener('change', (event) => {
    if (event.target.matches('[name="paciente_especifico"]')) {
      updatePatientFields();
      return;
    }
    if (event.target.matches('[name="doencas_preexistentes[]"], [name="condicoes_especiais[]"]')) {
      const checkbox = event.target;
      const group = checkbox.name;
      const choices = [...form.querySelectorAll(`[name="${group}"]`)];
      const none = choices.find((choice) => choice.value === 'Nenhuma');
      if (checkbox.value === 'Nenhuma' && checkbox.checked) {
        for (const choice of choices) if (choice !== checkbox) choice.checked = false;
      } else if (checkbox.checked && none) {
        none.checked = false;
      }
      updatePatientFields();
    }
  });
  updatePatientFields();
  const updateProfessionFields = () => {
    const isHealthProfessional = form.querySelector('[name="profissional_saude"]:checked')?.value === 'Sim';
    if (!isHealthProfessional) {
      for (const input of form.querySelectorAll('[name="profissao_saude"]')) input.checked = false;
    }
    const profession = form.querySelector('[name="profissao_saude"]:checked')?.value || '';
    if (professionWrap) professionWrap.hidden = !isHealthProfessional;
    if (otherProfessionWrap) otherProfessionWrap.hidden = !isHealthProfessional || profession !== 'Outro';
    for (const input of form.querySelectorAll('[name="profissao_saude"]')) {
      input.required = isHealthProfessional;
    }
    if (otherProfessionInput) {
      const isOther = isHealthProfessional && profession === 'Outro';
      otherProfessionInput.required = isOther;
      if (!isOther) otherProfessionInput.value = '';
    }
  };
  form.addEventListener('change', (event) => {
    if (event.target.matches('[name="profissional_saude"], [name="profissao_saude"]')) updateProfessionFields();
  });
  updateProfessionFields();

  const formatCpf = (value) => {
    const digits = digitsOnly(value, 11);
    let result = digits.slice(0, 3);
    if (digits.length > 3) result += '.' + digits.slice(3, 6);
    if (digits.length > 6) result += '.' + digits.slice(6, 9);
    if (digits.length > 9) result += '-' + digits.slice(9, 11);
    return result;
  };

  const formatPhone = (value) => {
    const digits = digitsOnly(value, 11);
    if (!digits) return '';
    if (digits.length === 1) return '(' + digits;
    const prefix = '(' + digits.slice(0, 2) + ') ';
    const number = digits.slice(2);
    const firstPartLength = digits.length === 11 ? 5 : 4;
    if (number.length <= firstPartLength) return prefix + number;
    return prefix + number.slice(0, firstPartLength) + '-' + number.slice(firstPartLength);
  };

  const masks = { cpf: formatCpf, telefone: formatPhone };
  for (const input of form.querySelectorAll('[data-cemed-mask]')) {
    const format = masks[input.dataset.cemedMask];
    if (!format) continue;

    const applyMask = () => {
      if (input.isComposing) return;
      const before = input.value;
      const selection = input.selectionStart ?? before.length;
      const atEnd = selection === before.length;
      const digitsBeforeCursor = before.slice(0, selection).replace(/\D/g, '').length;
      const formatted = format(before);
      if (formatted === before) return;
      input.value = formatted;
      if (document.activeElement !== input) return;
      if (atEnd) {
        input.setSelectionRange(formatted.length, formatted.length);
        return;
      }
      let seen = 0;
      let position = 0;
      for (let index = 0; index < formatted.length; index += 1) {
        if (/\d/.test(formatted[index])) seen += 1;
        if (seen === digitsBeforeCursor) {
          position = index + 1;
          break;
        }
      }
      input.setSelectionRange(position, position);
    };

    input.addEventListener('input', applyMask);
    input.addEventListener('compositionstart', () => { input.isComposing = true; });
    input.addEventListener('compositionend', () => { input.isComposing = false; applyMask(); });
    input.value = format(input.value);
  }

  const nonceField = form.querySelector('[name="fafar_cemed_nonce"]');
  const errorMessage = form.querySelector('[data-cemed-submit-error]');
  let refreshing = false;

  form.addEventListener('submit', async (event) => {
    if (form.dataset.cemedNonceFresh === '1' || !nonceField) return;
    event.preventDefault();
    if (refreshing) return;
    refreshing = true;
    form.setAttribute('aria-busy', 'true');
    if (errorMessage) errorMessage.hidden = true;

    try {
      const response = await fetch(form.dataset.cemedNonceUrl, {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { Accept: 'application/json' },
      });
      const result = await response.json();
      if (!response.ok || !result.success || !result.data?.nonce) {
        throw new Error('nonce refresh failed');
      }
      nonceField.value = result.data.nonce;
      form.dataset.cemedNonceFresh = '1';
      if (event.submitter) form.requestSubmit(event.submitter);
      else form.requestSubmit();
    } catch {
      if (errorMessage) {
        errorMessage.textContent = 'Não foi possível preparar o envio. Tente novamente; seus dados continuam no formulário.';
        errorMessage.hidden = false;
      }
    } finally {
      refreshing = false;
      form.removeAttribute('aria-busy');
    }
  });
})();
