import { notify, currency } from './ui';
import type { Product } from '../lib/types';
interface CartItem {
  id: number;
  name: string;
  price: number;
  image: string | null;
  quantity: number;
  available: number;
}
const storageKey = 'strukvet-cart-v1';
function readCart(): CartItem[] {
  try {
    const value = JSON.parse(localStorage.getItem(storageKey) || '[]');
    return Array.isArray(value)
      ? value
          .filter(
            (item: CartItem) =>
              Number.isSafeInteger(item.id) &&
              item.id > 0 &&
              typeof item.name === 'string' &&
              Number.isFinite(item.price) &&
              item.price >= 0 &&
              Number.isSafeInteger(item.quantity) &&
              item.quantity > 0 &&
              item.quantity <= 999 &&
              Number.isFinite(item.available),
          )
          .slice(0, 50)
      : [];
  } catch {
    return [];
  }
}
let cart = readCart();
function saveCart() {
  try {
    localStorage.setItem(storageKey, JSON.stringify(cart));
  } catch {
    notify('No se pudo guardar el carrito en este navegador.');
  }
  renderCart();
}
function renderCart() {
  document
    .querySelectorAll('[data-cart-count]')
    .forEach(
      (node) => (node.textContent = String(cart.reduce((sum, item) => sum + item.quantity, 0))),
    );
  const lines = document.getElementById('cart-lines');
  if (!lines) return;
  document.getElementById('cart-empty')!.hidden = cart.length > 0;
  document.getElementById('cart-content')!.hidden = cart.length === 0;
  lines.replaceChildren();
  let totalCents = 0;
  for (const item of cart) {
    totalCents += Math.round(item.price * 100) * item.quantity;
    const row = document.createElement('article');
    row.className = 'cart-row';
    const photo = document.createElement('div');
    photo.className = 'cart-item-image';
    if (item.image) {
      const img = document.createElement('img');
      img.src = '/api/productos/' + item.id;
      img.alt = item.name;
      photo.append(img);
    } else photo.textContent = 'SV';
    const copy = document.createElement('div');
    copy.className = 'cart-item-copy';
    const title = document.createElement('a');
    title.href = '/productos/' + item.id;
    title.textContent = item.name;
    const price = document.createElement('span');
    price.textContent = currency(item.price);
    copy.append(title, price);
    const controls = document.createElement('div');
    controls.className = 'quantity-control';
    const minus = document.createElement('button');
    minus.type = 'button';
    minus.textContent = '−';
    minus.setAttribute('aria-label', 'Reducir cantidad de ' + item.name);
    minus.addEventListener('click', () => {
      item.quantity--;
      if (item.quantity === 0) cart = cart.filter((entry) => entry.id !== item.id);
      saveCart();
    });
    const count = document.createElement('span');
    count.textContent = String(item.quantity);
    const plus = document.createElement('button');
    plus.type = 'button';
    plus.textContent = '+';
    plus.setAttribute('aria-label', 'Aumentar cantidad de ' + item.name);
    plus.addEventListener('click', () => {
      if (item.quantity >= Math.min(item.available, 999)) {
        notify('Llegaste a la cantidad disponible.');
        return;
      }
      item.quantity++;
      saveCart();
    });
    controls.append(minus, count, plus);
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'remove-item';
    remove.textContent = 'Quitar';
    remove.setAttribute('aria-label', 'Quitar ' + item.name);
    remove.addEventListener('click', () => {
      cart = cart.filter((entry) => entry.id !== item.id);
      saveCart();
    });
    row.append(photo, copy, controls, remove);
    lines.append(row);
  }
  document.getElementById('cart-total')!.textContent = currency(totalCents / 100);
}

// Envía solo identificadores y cantidades; el servidor calcula el importe real.
export function getOrderItems() {
  return cart.map((item) => ({ id: item.id, quantity: String(item.quantity) }));
}
export function clearSubmittedCart() {
  cart = [];
  try {
    localStorage.removeItem(storageKey);
  } catch {}
  document.querySelectorAll('[data-cart-count]').forEach((node) => (node.textContent = '0'));
  document.getElementById('cart-lines')?.replaceChildren();
  document.getElementById('cart-total')!.textContent = currency(0);
}
export function mountCart() {
  document.querySelectorAll<HTMLButtonElement>('.add-to-cart').forEach((button) =>
    button.addEventListener('click', () => {
      try {
        const product: Product = JSON.parse(button.dataset.product || '{}');
        const available = Math.floor(Number(product.available));
        if (available < 1) return;
        const old = cart.find((item) => item.id === product.id);
        if (old) {
          old.available = available;
          old.price = Number(product.price);
          if (old.quantity >= Math.min(available, 999)) {
            notify('Llegaste a la cantidad disponible.');
            return;
          }
          old.quantity++;
        } else {
          if (cart.length >= 50) {
            notify('Tu carrito admite hasta 50 productos.');
            return;
          }
          cart.push({
            id: product.id,
            name: product.name,
            price: Number(product.price),
            image: product.image,
            quantity: 1,
            available,
          });
        }
        saveCart();
        notify(product.name + ' se agregó a tu carrito.');
      } catch {
        notify('No pudimos agregar este producto. Recarga la página.');
      }
    }),
  );
  document.getElementById('clear-cart')?.addEventListener('click', () => {
    cart = [];
    saveCart();
  });

  renderCart();
}
