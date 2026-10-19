'use strict';
import { mountCalendar } from './calendar.js';
mountCalendar();
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
