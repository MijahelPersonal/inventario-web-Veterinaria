# StrukVet · Web pública

Aplicación independiente con Astro, TypeScript y CSS propio. PHP/MySQL permanecen en `../gestion`; Astro consulta su API desde el servidor, sin exponer credenciales al navegador.

## Desarrollo local

1. Iniciar MySQL y el servidor PHP de la gestión en `http://127.0.0.1:8080`.
2. Desde `gestion`, ejecutar `php scripts/migrate.php` para incorporar la migración 013.
3. Desde `web`, ejecutar `npm ci` y `node scripts/setup-local.mjs`.
4. Ejecutar `npm run dev` y abrir `http://127.0.0.1:4321`.

El instalador local conserva las configuraciones existentes. `.env` y `gestion/config/web.local.php` contienen la misma clave privada y no se suben a Git. Cada aplicación conserva sus propias dependencias.

## Organización del código

- `src/layouts`: cabecera, navegación y pie compartidos.
- `src/pages`: inicio, tienda, detalle, carrito, servicios, citas, contacto y privacidad.
- `src/pages/api`: intermediarios del servidor para solicitudes e imágenes.
- `src/components`: iconos, tarjetas y campos reutilizables.
- `src/lib`: tipos, conexión privada al backend y contenido de servicios.
- `src/scripts/cart.ts`: estado y controles del carrito.
- `src/scripts/forms.ts`: envío y respuesta de formularios.
- `src/scripts/site.ts` y `ui.ts`: inicialización, menú y avisos.
- `src/styles`: estilos generales y de páginas interiores, con reglas adaptables.
- `src/assets`: originales PNG generados por IA.
- `public/images`: versiones WebP optimizadas para servir en la web.

`npm run format` ordena el código; `npm run format:check` comprueba el formato. `npm run build` valida los tipos y compila el servidor.

## Conexión con la gestión

La tienda muestra productos activos con «Publicar en futura tienda» habilitado. Usa precios y existencias reales, descontando reservas y lotes vencidos. Si no hay productos publicados, muestra un estado vacío; no inventa existencias. Las imágenes de productos proceden de la gestión.

El carrito permite buscar productos, consultar detalles y enviar un **pedido pendiente de confirmación**. El servidor vuelve a comprobar disponibilidad y calcula los importes. Enviar una solicitud no cobra dinero ni reserva o descuenta stock. El personal confirma disponibilidad, entrega y pago antes de registrar la venta.

Las solicitudes de citas y contacto también se guardan en MySQL y aparecen en **Solicitudes web**. Solicitar una cita no ocupa automáticamente la agenda: el personal coordina el horario y registra la cita confirmada. No se envían correos ni WhatsApp automáticamente.

Los formularios comprueban origen, consentimiento, campos y límites de frecuencia; las solicitudes repetidas conservan su referencia. Las fotografías de IA son ilustrativas; no representan personal ni instalaciones reales. Antes de publicar, completar los canales reales de contacto y revisar la política de privacidad.

## Producción

Definir `SITE_URL` con el dominio HTTPS público y `BACKEND_URL` con la dirección privada del servidor PHP. Compartir `BACKEND_API_KEY` mediante configuración privada. Ejecutar `npm run build` y `npm start`; este último lee `.env`. El servidor Node debe permanecer activo detrás de un proxy HTTPS. La web no se incorpora a Electron.

## Validación realizada

- Compilación Astro y comprobación de tipos.
- Pruebas PHP de acceso, inventario, plantillas y recepción web.
- Navegación de escritorio, búsqueda, detalle, carrito y ajuste de cantidades.
- Envío de cita a la gestión y pedido de dos unidades por S/ 30.00 en la base de pruebas.
- Comprobación de precio calculado en servidor, idempotencia, límites y stock sin modificar.

El diseño incluye reglas para móvil; la verificación visual realizada corresponde a escritorio.
