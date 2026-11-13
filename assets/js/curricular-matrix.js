(() => {
  const form = document.querySelector('.curriculum-filters');
  if (!form) return;
  const search = document.querySelector('#curriculum-search');
  const course = document.querySelector('#curriculum-course');
  const period = document.querySelector('#curriculum-period');
  const sets = [...document.querySelectorAll('.curriculum-set')];
  const status = document.querySelector('.curriculum-status');
  const empty = document.querySelector('.curriculum-empty');
  const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  function update() {
    let count = 0;
    const terms = normalize(search.value).split(/\s+/).filter(Boolean);
    sets.forEach(set => {
      const active = !course || set.dataset.course === course.value;
      set.hidden = !active;
      set.querySelectorAll('.curriculum-period').forEach(section => {
        let visible = 0;
        section.querySelectorAll('.curriculum-row').forEach(row => {
          const matches = active && (!period.value || period.value === section.dataset.period) && terms.every(term => normalize(row.textContent).includes(term));
          row.hidden = !matches;
          if (matches) { visible++; count++; }
        });
        section.hidden = !visible;
      });
    });
    status.textContent = `${count} ${count === 1 ? 'atividade encontrada' : 'atividades encontradas'}.`;
    empty.hidden = count > 0;
  }
  const panel = document.querySelector('.curriculum-dependencies');
  const picker = document.querySelector('#curriculum-dependency');
  const graph = document.querySelector('.curriculum-graph');
  const codes = value => value.match(/[A-Z]{2,}\d+/g) || [];
  let activities = [];
  function graphColumn(title, items, middle = false) {
    const column = document.createElement('div');
    column.className = 'curriculum-graph-column' + (middle ? ' is-current' : '');
    const heading = document.createElement('h3'); heading.textContent = title; column.append(heading);
    if (!items.length) { const p = document.createElement('p'); p.textContent = 'Nenhuma relação indicada'; column.append(p); }
    items.forEach(item => {
      const box = document.createElement('div'); box.className = 'curriculum-node';
      const strong = document.createElement('strong'); strong.textContent = item.name;
      const small = document.createElement('span'); small.textContent = item.code + (item.period ? ` · ${item.period}º período` : ' · fora da lista por período');
      box.append(strong, small); column.append(box);
    });
    return column;
  }
  function drawGraph() {
    const current = activities.find(item => item.code === picker.value);
    if (!current) return;
    const required = codes(current.requisites).map(code => activities.find(item => item.code === code) || {code, name: code});
    const next = activities.filter(item => codes(item.requisites).includes(current.code));
    graph.replaceChildren(graphColumn('Pré-requisitos', required), graphColumn('Disciplina escolhida', [current], true), graphColumn('Abre caminho para', next));
  }
  function setupGraph() {
    const active = sets.find(set => !course || set.dataset.course === course.value);
    activities = [...active.querySelectorAll('.curriculum-row')].map(row => ({code: row.dataset.code, name: row.dataset.name, requisites: row.dataset.requisites, period: row.closest('.curriculum-period').dataset.period}));
    const previous = picker.value;
    picker.replaceChildren(...activities.map(item => { const option = document.createElement('option'); option.value = item.code; option.textContent = `${item.period}º período · ${item.name} (${item.code})`; return option; }));
    if (activities.some(item => item.code === previous)) picker.value = previous;
    panel.hidden = !activities.length;
    drawGraph();
  }
  picker.addEventListener('change', drawGraph);
  if (course) course.addEventListener('change', setupGraph);
  form.addEventListener('reset', () => setTimeout(setupGraph, 0));
  setupGraph();
  form.addEventListener('input', update);
  form.addEventListener('change', update);
  form.addEventListener('reset', () => setTimeout(update, 0));
  update();
})();
