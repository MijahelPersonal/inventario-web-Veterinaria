# StruchVet · estado de la reconstrucción comercial

Actualizado: 30 de octubre de 2026. Se continúa el trabajo iniciado el 29; no se reinició el proyecto ni se restauraron archivos anteriores.

## Implementado

- Astro y TypeScript conservados junto con PHP/PDO y MariaDB. Sin otro motor ecommerce, analítica, chat, CRM ni integraciones multimedia.
- Marca original: el SVG de corazón con pulso de `gestion/public/assets/icons/heart-pulse-fill.svg` se reutiliza en `Brand.astro` y en el favicon. Se mantiene la identidad azul y blanca.
- Encabezado comercial, buscador amplio y navegación con subcategorías. Menú móvil, cierre con Escape y clic exterior; desplegables mediante controles nativos `details` y teclado.
- Contenedor de hasta 1480 px, campañas panorámicas originales y cuadrículas de cuatro productos en escritorio. Navegación y buscador de 17 px; botones de 16–18 px y nombres de producto de 16–17 px.
- Inicio orientado a catálogo, ofertas vigentes, alimentos por mascota, farmacia, accesorios y marcas. Las secciones de producto muestran datos reales del backend; no se llenó la tienda real con artículos ficticios.
- Filtros de texto, categoría, marca, mascota, sección y rango de precio; orden y paginación. Las páginas fuera de rango se ajustan a un valor válido.
- Carrito persistente compatible con la clave anterior. Actualiza precios, imágenes y disponibilidad contra el catálogo; bloquea cantidades insuficientes. Los controles tienen un mínimo de 44 px y se redistribuyen en pantallas estrechas.
- Checkout separado: solicitud pendiente con referencia, validación de origen, precio calculado en el servidor e idempotencia. No hay cobro ni reserva automática de inventario.

## Panel y descuentos

La migración aditiva `gestion/database/migrations/014_web_commerce.sql` incorpora sección web, mascota destinataria, precio de oferta y fechas opcionales. Conserva productos e historial. `WebPricing.php` aplica una misma regla al catálogo y al pedido, con fechas de Lima e inicio y fin inclusivos. Las ofertas inferiores al 1 % muestran «Oferta», evitando el rótulo engañoso «-0 %».

Desde gestión se puede crear y editar productos, configurar categoría y marca, publicar u ocultar, cargar JPG/PNG/WebP, manejar existencias en Inventario y consultar solicitudes web. Retirar un producto lo desactiva sin borrar el historial. Las operaciones de producto, categorías, marcas e imágenes requieren `products.manage`; el administrador con permiso `*` conserva acceso.

## Imágenes

Los tres banners nuevos están en `public/images/banner-nutricion.jpg`, `banner-gatos.jpg` y `banner-cuidado.jpg`; sus originales PNG se conservan en `src/assets/`. Se reutilizan las imágenes originales anteriores de alimentación, accesorios, farmacia, mascotas y consulta. Los banners son editoriales y no anuncian descuentos inexistentes. Los prompts y procedencia figuran en `src/assets/PROMPTS-RECONSTRUCCION.md` y `src/assets/PROMPTS-COMERCIO.md`.

## Validación completada

### Sesión anterior, antes de la interrupción

- SuperPet se abrió y se inspeccionó visualmente.
- Se midieron vistas de 1366, 1440 y 1920 px: navegación y buscador de 17 px y sin desbordamiento horizontal en esas mediciones.
- Se detectaron y corrigieron el recorte del banner y la superposición del botón sobre la mascota en móvil. El menú se adapta hasta 1080 px y el banner móvil pasa a 540 px con imagen inferior separada del texto.
- Formularios administrativos reales sobre `struch_vet_test`: alta, edición, subida de JPG, retiro, categorías, marcas y pedidos. Un usuario sin permiso recibió HTTP 403.
- En navegador se probaron filtros de marca/precio, ficha, oferta y cantidades del carrito: dos unidades a S/ 30.00, subtotal S/ 60.00.

### Sesión de continuación del 30 de octubre

- Se montó el disco existente «Nuevo vol» para recuperar el mismo checkout.
- Astro check y build: 42 archivos, cero errores, advertencias o hints.
- `php tests/ecommerce.php`, `tests/web.php`, `tests/catalogs.php` y `tests/templates.php`: correctos. Incluyen vigencia, ofertas inválidas, precio del servidor, stock, doble envío, protección de rutas administrativas y compilación de 28 plantillas.
- Recorrido HTTP de un pedido con oferta de S/ 99.99: dos unidades, total guardado **S/ 199.98**. El servidor ignoró el importe manipulado del cliente, conservó 10 unidades de stock, reutilizó la referencia al reenviar y rechazó un origen distinto con HTTP 403.
- 17 rutas públicas, 10 imágenes referenciadas y el estado 404 de un producto inexistente comprobados por HTTP.
- Seis pantallas administrativas comprobadas por HTTP tras iniciar sesión: productos, alta, categorías, marcas, solicitudes e inventario.
- Se retiraron el producto y pedido temporales de esta sesión, además de 13 productos y dos usuarios de QA que quedaron en la base de pruebas durante la interrupción. No se insertaron productos ni compras ficticias en `struch_vet`.
- Dos servidores de desarrollo alcanzaron el límite de observadores de archivos de Fedora. Se detuvieron y se verificó la versión compilada, sin modificar límites globales del sistema.

## Verificación visual pendiente

La herramienta de control del navegador no está disponible en esta sesión. Por ello **no se tomaron nuevas capturas ni se certifica una nueva revisión visual** de las últimas correcciones de móvil/tablet. El contenido actual de SuperPet se pudo consultar mediante lectura web, que no sustituye a una inspección visual. Cuando vuelva la herramienta, quedan por verificar visualmente: 320/390 px, tablet 768/1024 px y capturas finales en 1366/1440/1920 px. No se usa la compilación como sustituto de esta comprobación.

## Para usar la tienda

- Web local: `http://127.0.0.1:4321/`.
- Gestión: `http://127.0.0.1:8080/`.
- Para ejecutar la versión compilada: desde `web`, `HOST=127.0.0.1 PORT=4321 npm start` después de `npm run build`. Gestión y MariaDB deben estar activos.
- El catálogo contiene **100 productos de demostración publicados**, con 11 marcas, 25 ofertas y existencias simuladas compartidas con gestión. La carga y sus verificaciones se documentan en `docs/CATALOGO-DEMO100.md`. Los artículos, precios y existencias reales se administran en Gestión → Productos e Inventario.

## Pendientes funcionales ajenos a credenciales actuales

No hay pasarela de pago, cálculo automático de envío ni registro de cuentas públicas. El flujo actual recibe pedidos pendientes y coordina entrega/pago con el personal. No se simulan cobros ni ventas confirmadas. No se muestran «más vendidos» porque no hay datos de ventas web suficientes.

## Copias de seguridad

`backups/web-antes-reconstruccion-20261026.tar.gz` y `backups/antes-ecommerce-20261029.tar.gz` conservan las versiones previas, sin `.env` ni credenciales locales. Los archivos de configuración privados se mantienen en su ubicación original.
