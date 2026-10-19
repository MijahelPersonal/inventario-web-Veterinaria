'use strict';
import { mountCalendar } from './calendar.js';
import { mountPointOfSale } from './pos.js';
mountCalendar();
mountPointOfSale();
// El botón del encabezado contrae el menú sin cambiar las pantallas de la gestión.
document.getElementById('sidebar-toggle')?.addEventListener('click', (event) => {
    const collapsed = document.body.classList.toggle('sidebar-collapsed');
    event.currentTarget.setAttribute('aria-expanded', String(!collapsed));
});
// Conserva el tema elegido únicamente en este navegador o escritorio.
try {
    document.documentElement.dataset.coreuiTheme = localStorage.getItem('struch-theme') || 'light';
} catch {}
document.getElementById('theme-toggle')?.addEventListener('click', () => {
    const theme = document.documentElement.dataset.coreuiTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.coreuiTheme = theme;
    try {
        localStorage.setItem('struch-theme', theme);
    } catch {}
});
