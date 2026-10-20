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
