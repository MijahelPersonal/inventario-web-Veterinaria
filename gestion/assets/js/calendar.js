import { Calendar } from '@fullcalendar/core';
import esLocale from '@fullcalendar/core/locales/es';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';

// El backend entrega los eventos; mover el calendario nunca modifica una cita.
export function mountCalendar() {
    const element = document.getElementById('calendar');
    if (!element) return;
    const calendar = new Calendar(element, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin],
        locale: esLocale,
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
        },
        height: 'auto',
        nowIndicator: true,
        allDaySlot: false,
        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        events: { url: '/citas/eventos', extraParams: { vet: element.dataset.vet } },
        eventSourceFailure() {
            const message = document.createElement('p');
            message.className = 'alert alert-warning';
            message.textContent =
                'No se pudo cargar la agenda. Comprueba tu sesión y vuelve a cargar la página.';
            element.replaceChildren(message);
        },
    });
    calendar.render();
}
