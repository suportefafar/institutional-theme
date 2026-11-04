/* Progressive enhancement: all menu links remain available without JavaScript. */
(() => {
  const placeLanguages = () => {
    const switcher = document.querySelector('.gt_switcher_wrapper');
    if (switcher) document.querySelector('.institution-inner')?.append(switcher);
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', placeLanguages, {once: true});
  else placeLanguages();
  const section = document.querySelector('.section-navigation');
  const mobile = window.matchMedia('(max-width: 700px)');
  const adaptSection = () => { if (section) section.open = !mobile.matches; };
  adaptSection();
  mobile.addEventListener('change', adaptSection);
  const toggle = document.querySelector('.nav-toggle');
  const menu = document.getElementById('primary-menu');
  if (toggle && menu) {
    toggle.hidden = false;
    toggle.closest('nav').classList.add('is-enhanced');
    toggle.addEventListener('click', () => {
      const expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!expanded));
      menu.classList.toggle('is-open', !expanded);
    });
  }
  document.querySelectorAll('.main-navigation .menu-item-has-children, .section-navigation .menu-item-has-children').forEach((item, index) => {
    const submenu = Array.from(item.children).find(child => child.tagName === 'UL');
    const link = Array.from(item.children).find(child => child.tagName === 'A');
    if (!submenu || !link) return;
    const button = document.createElement('button');
    const isSection = !!item.closest('.section-navigation');
    const open = isSection && item.classList.contains('current-menu-ancestor');
    submenu.id = `fafar-submenu-${index}`;
    submenu.hidden = !open;
    button.type = 'button';
    button.className = 'submenu-toggle';
    button.setAttribute('aria-controls', submenu.id);
    button.setAttribute('aria-expanded', String(open));
    const label = document.createElement('span');
    label.textContent = link.textContent.trim();
    button.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
    button.prepend(label);
    link.replaceWith(button);
    button.addEventListener('click', () => {
      const next = button.getAttribute('aria-expanded') !== 'true';
      if (next && !isSection) {
        Array.from(item.parentElement.children).forEach(sibling => {
          if (sibling === item) return;
          const siblingButton = Array.from(sibling.children).find(child => child.classList.contains('submenu-toggle'));
          if (siblingButton) {
            siblingButton.setAttribute('aria-expanded', 'false');
            document.getElementById(siblingButton.getAttribute('aria-controls')).hidden = true;
          }
        });
      }
      button.setAttribute('aria-expanded', String(next));
      submenu.hidden = !next;
    });
  });
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    const activeButton = event.target.closest('.submenu-toggle[aria-expanded="true"]');
    if (activeButton) {
      activeButton.setAttribute('aria-expanded', 'false');
      document.getElementById(activeButton.getAttribute('aria-controls')).hidden = true;
      return;
    }
    const submenu = event.target.closest('.sub-menu');
    if (submenu) {
      submenu.hidden = true;
      const button = submenu.previousElementSibling;
      button?.setAttribute('aria-expanded', 'false');
      button?.focus();
    } else if (menu?.classList.contains('is-open')) {
      menu.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.focus();
    }
  });
  document.addEventListener('click', event => {
    document.querySelectorAll('.main-navigation .submenu-toggle[aria-expanded="true"]').forEach(button => {
      if (!button.parentElement.contains(event.target)) {
        button.setAttribute('aria-expanded', 'false');
        document.getElementById(button.getAttribute('aria-controls')).hidden = true;
      }
    });
  });
})();
