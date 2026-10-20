# Historial de STRUCH VET

## Perfiles de personal simplificados

- Dos roles de acceso: Administrador y Cajero; un solo rol por cuenta.
- Las cuentas nuevas proponen Cajero y se conserva la protección del último administrador activo.
- Las cuentas de roles retirados se conservan y pasan al perfil Cajero sin permisos administrativos.

## Carga ligera por pantalla

- Marco JavaScript reducido de 446160 a 3404 bytes; calendario, gráficos y POS se descargan solo donde se usan.
- Caché de plantillas compiladas con detección de cambios en desarrollo; existencias y cobros siguen consultándose en cada petición.
- Retirada una consulta de proveedores que Inventario ya no utilizaba.
- Recursos versionados para evitar mezclar archivos antiguos y nuevos al actualizar.

## Navegación y ventana de escritorio

- Título de Electron fijo en STRUCH VET, sin reemplazarlo por cada módulo.
- Ventana inicial mostrada cuando termina de prepararse, con fondo de la gestión.
- Retirada la transición entre documentos que capturaba pantallas incompletas.
- Inicialización crítica antes de pintar la página para evitar el calendario vacío durante la carga.
- Iconos de menú, marca y pie incrustados como SVG, independientes de la carga de fuentes.
- Fuentes locales precargadas para reducir cambios visuales entre pantallas.

## Corrección del desplazamiento lateral

- Menú ampliado a 288 px, sin desbordamiento horizontal al interactuar.
- Resaltado mediante color, sin desplazar las filas fuera de su contenedor.
- Posición del menú conservada entre pantallas y opción activa siempre visible.
- Cabecera y usuario permanecen fijos mientras se desplazan las opciones.

## 0.2.0 — Simplificación y escritorio

- Dashboard, menú simplificado, corazón amarillo e iconos con color.
- Eliminada la ruta visual superior y los accesos a auditoría y catálogos auxiliares.
- Diez colores, tema claro u oscuro y pie de MijxhelDev al fondo de cada página.
- Venta, cobro y stock atómicos en el POS; saldos pendientes separados.
- Actualizador Electron con avisos, notas de versión y descarga e instalación controladas.
- Empaquetado NSIS preparado; requiere publicar y probar las releases antes de distribuir actualizaciones reales.

## Compatibilidad del editor

- Formato PHP 8.2 compatible con el analizador PHP IntelliSense instalado en VS Code.
- Conservados paréntesis al invocar métodos de objetos recién creados.
- Verificación de sintaxis con PHP y con el analizador que originaba los avisos.

## Legibilidad y barra lateral

- Plus Jakarta Sans local, la misma familia de la referencia.
- Menú de 264 px, opciones de 16 px e iconos Bootstrap consistentes.
- Textos de tablas, formularios y descripciones más grandes; desplazamiento discreto.

## Diseño de gestión veterinaria

- Menú por secciones, citas y calendario separados, directorios de mascotas y veterinarios.
- Tablas con responsable, especialidad, contacto, estados y acciones reales.
- Cobros con tarjetas de saldo y formulario específico de registro de pago.
- Eliminada la pantalla independiente «Mi caja»; efectivo integrado en ventas y cobros.

## Fichas e historial de atención

- Fichas de mascotas y veterinarios con citas relacionadas.
- Alergias, antecedentes y microchip en mascotas; notas clínicas con permiso específico.
- Reprogramación con comprobación de cruces, horarios y cargos.
- Constancia PDF de atención separada del ticket de pago.

## Dashboard y reportes

- Inicio con indicadores reales, gráficos de especies y estados, próximas citas y alertas.
- Ventas, cobros, efectivo y margen de productos diferenciados.
- Reportes de citas, ventas, cobros, caja e inventario con filtros, CSV seguro y PDF.

## Venta en tienda y estilo administrativo

- Menú azul oscuro, acentos azules y fondo claro según la referencia visual del usuario.
- POS con buscador, categorías, tarjetas con imagen y disponibilidad, carrito y resumen lateral.
- Conservación del carrito entre filtros; confirmación mediante el servicio existente de ventas.
- Carga validada de imágenes privadas para productos; sin productos ni fotos de demostración.

## Ventas, cobros y caja

- Ventas con productos y servicios, precios históricos y stock transaccional por lotes.
- Cargos únicos por cita; pagos parciales con distintos métodos sin superar el saldo.
- Reversiones auditadas sin borrar cobros y ticket interno PDF.
- Apertura, ingresos, egresos y cierre por usuario con diferencia de efectivo.

## Administración y configuración

- Gestión de personal con roles, usuario, contraseña y desactivación.
- Protección del último administrador activo y de la cuenta propia.
- Preferencias de nombre, contacto, moneda y color; consulta de auditoría.
- Documento de correspondencias con los módulos de la referencia VetMedik.

## Administración del acceso local

- Restablecimiento de contraseñas por consola con hash y registro de auditoría.
- Las contraseñas no se incluyen en los argumentos ni en archivos versionados.

## Inicio de sesión por usuario

- El acceso usa nombre de usuario único y contraseña; correo opcional de contacto.
- La cuenta inicial utiliza `admin` y conserva su contraseña.
- Migración conserva usuarios existentes con identificadores únicos.

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
