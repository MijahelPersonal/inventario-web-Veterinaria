import { mountCarousels } from './carousel';
import { mountCart } from './cart';
import { mountForms } from './forms';

// Inicializa las interacciones una sola vez al cargar cada página.
document.getElementById('menu-toggle')?.addEventListener('click', (event) => {
  const button = event.currentTarget as HTMLButtonElement;
  const open = document.getElementById('main-nav')!.classList.toggle('open');
  button.setAttribute('aria-expanded', String(open));
  button.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
});

mountCart();
mountForms();

mountCarousels();

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    document.getElementById('main-nav')?.classList.remove('open');
    const button = document.getElementById('menu-toggle');
    button?.setAttribute('aria-expanded', 'false');
    button?.setAttribute('aria-label', 'Abrir menú');
  }
});

// Desplegables con teclado, clic y puntero; Escape y clic exterior los cierran.
const menus = Array.from(document.querySelectorAll<HTMLDetailsElement>('.nav-dropdown'));
menus.forEach((menu) => {
  menu.addEventListener('toggle', () => {
    if (menu.open)
      menus.forEach((other) => {
        if (other !== menu) other.open = false;
      });
  });
  const category = menu.closest('.nav-category');
  category?.addEventListener('mouseenter', () => {
    if (matchMedia('(hover: hover)').matches) menu.open = true;
  });
  category?.addEventListener('mouseleave', () => {
    if (matchMedia('(hover: hover)').matches && !menu.contains(document.activeElement))
      menu.open = false;
  });
});
document.addEventListener('click', (event) => {
  if (event.target instanceof Node)
    menus.forEach((menu) => {
      if (!menu.closest('.nav-category')?.contains(event.target as Node)) menu.open = false;
    });
});
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') menus.forEach((menu) => (menu.open = false));
});
