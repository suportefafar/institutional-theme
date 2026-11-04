(() => {
  const form = document.querySelector('.fafar-cemed-form[data-cemed-nonce-url]');
  if (!form) return;

  const digitsOnly = (value, limit) => value.replace(/\D/g, '').slice(0, limit);

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
