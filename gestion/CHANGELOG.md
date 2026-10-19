# Historial de STRUCH VET

## Inventario por lotes

- Recepción de compras por producto con proveedor, referencia, lote, costo y vencimiento.
- Saldos iniciales, ajustes negativos, consumo y pérdidas con motivo obligatorio.
- Movimientos transaccionales, protección contra cantidades negativas y doble envío.
- Existencias físicas separadas de disponibilidad vigente y cantidades reservadas.
- Salidas de venta preparadas por vencimiento más próximo y costo histórico.

## Agenda y disponibilidad

- Calendario local con mes, semana, día y lista; filtros por veterinario.
- Citas con duración y precio histórico del servicio, mascota, responsable y origen.
- Validación transaccional de cruces y estados; horarios semanales por veterinario.
- Registro repetido del mismo formulario no duplica citas.

## Catálogos y navegación

- Clientes, mascotas, veterinarios, especialidades y servicios con relaciones explícitas.
- Productos, categorías, marcas y proveedores con búsqueda, edición y desactivación.
- Validación en servicios, consultas PDO y vistas Twig reutilizables.
- Navegación administrativa y selección de tema claro/oscuro.

## Acceso y permisos

- Login del personal con hash, límite de intentos y vencimiento por inactividad.
- Roles comprobados en el servidor; formularios y logout protegidos por CSRF.
- Auditoría y creación del administrador por CLI sin contraseñas de ejemplo.
- Migración inicial versionada y configuración privada.

Avances publicados mediante commits independientes con prefijo `inicio:`.
