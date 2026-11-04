/* One accessible filtering behavior for maps, syllabuses and public directories. */
(() => {
  document.querySelectorAll('form[data-table-filter]').forEach(form => {
    const id = form.dataset.tableFilter;
    const body = document.getElementById(id);
    if (!body) return;
    const rows = Array.from(body.querySelectorAll('[data-filter-row]'));
    const fields = Array.from(form.querySelectorAll('[data-filter-column]'));
    const empty = body.querySelector('[data-filter-empty]');
    const status = document.querySelector(`[data-filter-status="${id}"]`);
    const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR').trim();
    const filter = () => {
      let count = 0;
      rows.forEach(row => {
        const matches = fields.every(field => {
          const query = normalize(field.value);
          const text = normalize(field.dataset.filterColumn === '*' ? row.textContent : row.cells[Number(field.dataset.filterColumn)]?.textContent || '');
          return !query || (field.hasAttribute('data-filter-exact') ? text === query : text.includes(query));
        });
        row.hidden = !matches;
        if (matches) count++;
      });
      if (empty) empty.hidden = count > 0;
      if (status) status.textContent = `${count} ${count === 1 ? 'resultado' : 'resultados'}`;
    };
    form.addEventListener('submit', event => { event.preventDefault(); filter(); });
    form.addEventListener('input', filter);
    form.addEventListener('change', filter);
    filter();
  });
})();
