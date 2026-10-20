# Prueba funcional veterinaria — 19 de octubre de 2026

## Datos disponibles en la instalación local

Todos los nombres y registros de esta demostración son ficticios. Los responsables y profesionales están identificados como prueba. Las colegiaturas DEMO no son registros profesionales reales.

- Cinco mascotas y responsables: Rocky, Spunky, Molly, Luna y Max.
- Tres veterinarios: Camila Rojas, Gabriel Torres y Valeria Paredes.
- Disponibilidad de demostración: lunes a sábado, de 09:00 a 18:00.
- Cinco servicios: consulta general, control de seguimiento, vacunación, desparasitación y revisión de piel. Sus precios y duraciones son ejemplos editables.

| Mascota | Servicio | Fecha | Estado |
| --- | --- | --- | --- |
| Rocky | Consulta general | 20/10/2026 09:00 | Confirmada |
| Spunky | Control de seguimiento | 20/10/2026 10:00 | Solicitada |
| Molly | Vacunación | 20/10/2026 11:00 | Confirmada |
| Luna | Desparasitación | 17/10/2026 11:00 | Atendida, simulación histórica |
| Max | Revisión de piel | 17/10/2026 16:00 | Cancelada, simulación histórica |

Las dos citas pasadas se cargaron exclusivamente como ejemplos históricos, sin atención clínica real ni comprobantes financieros. El formulario normal conserva la validación de horarios futuros.

## Comprobaciones realizadas

1. Alta de una veterinaria y un servicio desde sus formularios HTTP: guardados y visibles en el listado.
2. Registro de Rocky desde Citas / Agenda y de Spunky desde Calendario: ambas consultas aparecen en la misma agenda.
3. Confirmación de Rocky desde el selector de estado: conservada al recargar.
4. Intento de reservar a Spunky a las 09:15 con la misma veterinaria que atiende a Rocky de 09:00 a 09:30: rechazado con el mensaje «El veterinario ya tiene una cita en ese horario», sin crear una cita adicional.
5. Citas pendientes: Rocky, Spunky y Molly. Historial pasado: Luna y Max.
6. Calendario mensual: cinco eventos. Semana y agenda de la semana del 19 al 25: las tres consultas futuras. Día 20: sus tres horarios correctos.
7. Apertura del evento de Rocky: muestra responsable, veterinaria, servicio, horario y precio histórico correctos.
8. Pruebas automatizadas de agenda y fichas ejecutadas sobre la base separada de pruebas: doble envío, horarios, cancelación, límites, concurrencia entre procesos, reprogramación y datos históricos, correctas.

Se corrigió la fecha inicial del calendario: al consultar el mes actual, las vistas Semana y Día comienzan desde hoy; para otro mes se conserva su primer día.

La carga de ejemplos no cambió los conteos de productos, movimientos de stock, ventas o pagos. No se generaron cargos ni cobros. Los ejemplos quedan disponibles en la base local para explorarlos; Git conserva este informe y la corrección, no una copia de la base de datos.
