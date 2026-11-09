(function () {
  'use strict';

  function ready(callback) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', callback);
    else callback();
  }

  ready(function () {
    var form = document.getElementById('fafar-auditorium-form');
    if (!form) return;

    var config = window.fafarAuditoriumConfig || {};
    var status = document.getElementById('fafar-auditorium-status');
    var groups = document.getElementById('fafar-auditorium-occurrences');
    var addButton = document.getElementById('fafar-auditorium-add');
    var removeButton = document.getElementById('fafar-auditorium-remove');
    var submitButton = form.querySelector('.fafar-submit');
    var timer = null;
    var busy = false;

    function say(message, isError) {
      status.textContent = message;
      status.hidden = !message;
      status.setAttribute('aria-live', isError ? 'assertive' : 'polite');
      status.classList.toggle('is-error', Boolean(isError));
    }

    function readOccurrences() {
      return Array.prototype.map.call(groups.querySelectorAll('[data-occurrence]'), function (group) {
        return {
          date: group.querySelector('[data-date]').value,
          start_time: group.querySelector('[data-start]').value,
          end_time: group.querySelector('[data-end]').value
        };
      });
    }

    function readSubmission() {
      var resources = {};
      ['use_musical_instruments', 'use_fafar_notebook', 'use_own_notebook', 'use_internet_access'].forEach(function (name) {
        var selected = form.querySelector('input[name="' + name + '"]:checked');
        resources[name] = selected ? selected.value : '';
      });
      return {
        applicant_name: form.elements.applicant_name.value,
        applicant_email: form.elements.applicant_email.value,
        applicant_phone: form.elements.applicant_phone.value,
        description: form.elements.description.value,
        expected_audience: form.elements.expected_audience.value,
        resources: resources,
        occurrences: readOccurrences()
      };
    }

    function request(action, data) {
      var body = new FormData();
      body.append('action', action);
      body.append('nonce', config.nonce || '');
      body.append('submission', JSON.stringify(data));
      return fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
        .then(function (response) {
          return response.json().then(function (json) {
            if (!response.ok || !json.success) {
              var error = new Error(json && json.data && json.data.message ? json.data.message : 'Não foi possível concluir a operação.');
              error.data = json && json.data ? json.data : {};
              throw error;
            }
            return json.data;
          });
        });
    }

    function displayConflicts(conflicts) {
      var all = groups.querySelectorAll('[data-occurrence]');
      Array.prototype.forEach.call(all, function (group) {
        group.querySelector('[data-occurrence-status]').textContent = '';
        group.removeAttribute('data-unavailable');
      });
      (conflicts || []).forEach(function (conflict) {
        var group = all[parseInt(conflict.index, 10)];
        if (!group) return;
        var details = conflict.occupied || {};
        group.setAttribute('data-unavailable', 'true');
        group.querySelector('[data-occurrence-status]').textContent = conflict.message || (details.start_time && details.end_time ? 'Indisponível: já existe uma reserva entre ' + details.start_time + ' e ' + details.end_time + '.' : 'Este horário está indisponível. Escolha outro intervalo.');
      });
    }

    function checkAvailability(announce) {
      var occurrences = readOccurrences();
      var complete = occurrences.length && occurrences.every(function (item) { return item.date && item.start_time && item.end_time; });
      if (!complete) return Promise.resolve(null);
      return request('fafar_auditorium_check', { occurrences: occurrences }).then(function (result) {
        displayConflicts(result.conflicts || []);
        if (announce) say(result.available ? 'Todas as datas e horários estão disponíveis neste momento.' : 'Há horário indisponível. Confira os conjuntos indicados.', !result.available);
        return result;
      }).catch(function (error) {
        if (announce) say(error.message, true);
        return null;
      });
    }

    function scheduleCheck() {
      window.clearTimeout(timer);
      timer = window.setTimeout(function () { checkAvailability(false); }, 500);
    }

    function updateButtons() {
      var count = groups.querySelectorAll('[data-occurrence]').length;
      addButton.disabled = count >= 31;
      removeButton.hidden = count <= 1;
    }

    addButton.addEventListener('click', function () {
      if (groups.querySelectorAll('[data-occurrence]').length >= 31) return;
      var clone = groups.querySelector('[data-occurrence]').cloneNode(true);
      clone.querySelector('[data-date]').value = '';
      clone.querySelector('[data-start]').value = '18:00';
      clone.querySelector('[data-end]').value = '22:00';
      clone.querySelector('[data-occurrence-status]').textContent = '';
      clone.removeAttribute('data-unavailable');
      groups.appendChild(clone);
      updateButtons();
    });

    removeButton.addEventListener('click', function () {
      var all = groups.querySelectorAll('[data-occurrence]');
      if (all.length > 1) groups.removeChild(all[all.length - 1]);
      updateButtons();
      scheduleCheck();
    });

    groups.addEventListener('change', scheduleCheck);
    groups.addEventListener('input', scheduleCheck);
    updateButtons();

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (busy) return;
      if (!form.reportValidity()) return;
      busy = true;
      submitButton.disabled = true;
      say('Verificando todas as datas e enviando a solicitação…', false);
      var payload = readSubmission();
      request('fafar_auditorium_check', { occurrences: payload.occurrences }).then(function (result) {
        displayConflicts(result.conflicts || []);
      }).catch(function (error) {
        say(error.message, true);
      }).then(function () {
        // A submissão no servidor também consulta disponibilidade e salva o desfecho local.
        return request('fafar_auditorium_submit', payload);
      }).then(function (result) {
        say(result.message || 'Solicitação enviada com sucesso.', false);
        form.reset();
        var all = groups.querySelectorAll('[data-occurrence]');
        while (all.length > 1) { groups.removeChild(all[all.length - 1]); all = groups.querySelectorAll('[data-occurrence]'); }
        groups.querySelector('[data-start]').value = '18:00';
        groups.querySelector('[data-end]').value = '22:00';
        displayConflicts([]);
        updateButtons();
      }).catch(function (error) {
        if (error.data && error.data.conflicts) displayConflicts(error.data.conflicts);
        say(error.message, true);
      }).finally(function () {
        busy = false;
        submitButton.disabled = false;
      });
    });
  });
}());
