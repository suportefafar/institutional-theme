(() => {
  const input = document.querySelector('#student-procedures-search');
  if (!input) return;
  const items = [...document.querySelectorAll('.student-procedures-item')];
  const status = document.querySelector('.student-procedures-status');
  const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR').trim();
  const filter = () => {
    const terms = normalize(input.value).split(/\s+/).filter(Boolean);
    let count = 0;
    items.forEach(item => {
      const searchable = normalize(`${item.textContent} ${item.dataset.keywords || ''}`);
      item.hidden = !terms.every(term => searchable.includes(term));
      if (!item.hidden) count++;
    });
    status.textContent = count ? `${count} ${count === 1 ? 'resultado encontrado' : 'resultados encontrados'}.` : 'Nenhum resultado encontrado.';
  };
  input.addEventListener('input', filter);
  filter();
})();
