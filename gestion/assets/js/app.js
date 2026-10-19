'use strict';
// La misma familia de la referencia se sirve localmente, sin solicitudes a Google Fonts.
import '@fontsource/plus-jakarta-sans/latin-400.css';
import '@fontsource/plus-jakarta-sans/latin-500.css';
import '@fontsource/plus-jakarta-sans/latin-600.css';
import '@fontsource/plus-jakarta-sans/latin-700.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import { mountCalendar } from './calendar.js';
import { mountPointOfSale } from './pos.js';
import { mountDashboard } from './dashboard.js';
import { mountUpdates } from './updates.js';
mountUpdates();
mountCalendar();
mountPointOfSale();
mountDashboard();
// Completa el saldo sugerido; el backend comprueba nuevamente el importe permitido.
const paymentSelect = document.querySelector('[data-payment-sale]');
if (paymentSelect) {
    const updateAmount = () => {
        const amount = document.getElementById('amount');
        amount.value = paymentSelect.selectedOptions[0]?.dataset.balance || '';
    };
    paymentSelect.addEventListener('change', updateAmount);
    updateAmount();
}
// Los accesos a nuevos registros abren el formulario sin abandonar la pantalla.
document.querySelectorAll('[data-open-details]').forEach((link) => {
    link.addEventListener('click', () => {
        const details = document.getElementById(link.dataset.openDetails);
        if (details) details.open = true;
    });
});
// El botón del encabezado contrae el menú sin cambiar las pantallas de la gestión.
document.getElementById('sidebar-toggle')?.addEventListener('click', (event) => {
    const collapsed = document.body.classList.toggle('sidebar-collapsed');
    event.currentTarget.setAttribute('aria-expanded', String(!collapsed));
});
// Previsualiza la apariencia; Guardar preferencias la conserva en el servidor.
document.querySelectorAll('[name="theme"]').forEach((input) => {
    input.addEventListener('change', () => {
        document.documentElement.dataset.coreuiTheme = input.value;
    });
});
document.querySelectorAll('[name="primary_color"]').forEach((input) => {
    input.addEventListener('change', () => document.body.style.setProperty('--brand', input.value));
});
