# StruchVet Web · Tienda pública

Página web de la veterinaria para que los clientes exploren productos, marcas y ofertas, preparen su carrito y envíen solicitudes de pedidos o citas. El personal administra el catálogo y atiende esas solicitudes desde **Gestión**.

**[Descargas e instalación de StruchVet Desktop](https://github.com/MijahelPersonal/inventario-web-Veterinaria#descargas)** · **[Guía completa del proyecto](https://github.com/MijahelPersonal/inventario-web-Veterinaria)**

## Qué incluye

- Inicio, categorías de perros/gatos y alimentos, marcas, ofertas y búsqueda con filtros.
- Ficha de producto, presentación, precio, fotografía y disponibilidad.
- Carrito y checkout con importes comprobados por el servidor.
- Solicitudes de cita y contacto recibidas en Gestión.
- Páginas informativas y diseño adaptable.

Los pedidos son **solicitudes pendientes de confirmación**: enviar el formulario no cobra dinero, reserva ni descuenta existencias. El personal coordina disponibilidad, entrega y pago antes de registrar la venta. Una solicitud de cita tampoco ocupa automáticamente la agenda. No se envían mensajes de correo o WhatsApp automáticamente.

## Dirección para visitar la web

Todavía no se ha publicado un dominio de producción. En desarrollo se abre la dirección indicada por Astro, normalmente **http://127.0.0.1:4321/**; si ese puerto está ocupado puede indicar otro, como `4322`. Estas direcciones sólo corresponden al equipo donde se ejecuta la web.

Para que otras personas puedan visitarla por Internet, hace falta desplegarla en un servidor con dominio y HTTPS; se explica en [Producción](#producción).

## Relación con Gestión y Desktop

| Componente | Función |
| --- | --- |
| `gestion/` | Backend PHP y administración: productos, inventario, ventas y atención veterinaria. |
| `web/` | Frontend Astro/TypeScript que consulta la API de Gestión desde el servidor. |
| `electron/` | Aplicación instalable que abre Gestión con PHP incluido o se conecta a Gestión HTTPS. |

Los productos visibles deben estar activos y tener **Publicar en la tienda web** habilitado. Se muestran precios y existencias del backend, descontando reservas y lotes vencidos. Las imágenes se sirven desde Gestión. Un catálogo vacío se muestra vacío; la web no inventa existencias.

Los 100 productos de demostración cargados en el entorno local pertenecen a esa base de datos: **no se distribuyen como datos de los nuevos instaladores ni se incluyen en Git**. Su documentación está en [CATALOGO-DEMO100.md](docs/CATALOGO-DEMO100.md).

La web conserva sus propias dependencias y servidor. Para conectarla, utiliza un backend PHP de Gestión desplegado; el servidor privado y efímero de Desktop está reservado a su propio cliente.

## Requisitos para desarrollar

- Node.js 22 y npm.
- PHP 8.3–8.5, Composer, PDO MySQL, BCMath y mbstring; las extensiones adicionales se detallan en la guía de Gestión/Desktop.
- MariaDB/MySQL accesible y configuración privada de Gestión preparada.

Los visitantes de una web ya publicada sólo necesitan su navegador.

## Instalación local

### 1. Obtener el proyecto

Gestión, Desktop, la web y sus guías están en la rama principal `main`:

```bash
git clone --branch main --single-branch https://github.com/MijahelPersonal/inventario-web-Veterinaria.git struchvet
cd struchvet
```

### 2. Preparar el backend

```bash
cd gestion
composer install
npm ci
npm run assets
```

Copia `config/local.example.php` a `config/local.php`. Completa usuario, contraseña y cualquier otro dato de conexión necesario para la base creada por el administrador. Guarda un respaldo antes de aplicar migraciones sobre datos existentes.

```bash
php scripts/migrate.php
```

En una instalación nueva, si aún no existe una cuenta de acceso:

```bash
php scripts/create-admin.php
```

Después inicia el backend:

```bash
npm run serve
```

Queda disponible en **http://127.0.0.1:8080**. El comando de migraciones aplica las pendientes, incluidas las de conexión web y ofertas; no se limita a una migración concreta.

### 3. Preparar Astro en otra terminal

Desde la raíz del proyecto:

```bash
cd web
npm ci
node scripts/setup-local.mjs
npm run dev
```

Abre la dirección que muestre la terminal. El script comparte una clave privada entre los dos servidores y conserva configuraciones existentes. `web/.env` y `gestion/config/web.local.php` están excluidos de Git; no adjuntarlos a issues ni commits.

### 4. Publicar productos y probar

1. Inicia sesión en Gestión.
2. Registra o edita un producto en **Productos**, configura marca/categoría/precio y activa su publicación web.
3. Añade su fotografía y registra existencias en **Inventario**.
4. Abre la tienda, busca el producto y añádelo al carrito.
5. Envía una solicitud de pedido y comprueba su recepción en **Solicitudes web** de Gestión.

Haz estas pruebas sobre una base separada si no quieres cambiar los datos de operación. Los precios de oferta y sus fechas se administran desde Gestión.

## Estructura

```text
web/
├── src/
│   ├── pages/          # Inicio, categorías, ficha, carrito, checkout y formularios
│   │   └── api/        # Intermediarios privados para catálogo, imágenes y solicitudes
│   ├── layouts/        # Estructura compartida de páginas
│   ├── components/     # Navegación, tarjetas, iconos y componentes de catálogo
│   ├── lib/            # Tipos, categorías y conexión con Gestión
│   ├── scripts/        # Carrito, formularios, navegación y comportamiento
│   ├── styles/         # Diseño general y adaptable
│   └── assets/         # Originales gráficos y su procedencia
├── public/images/      # Imágenes públicas de la web
├── scripts/            # Preparación local
├── docs/               # Procedencia de recursos y catálogo de prueba
├── .env.example        # Ejemplo sin credenciales
└── package.json        # Dependencias y comandos
```

## Producción

1. Despliega Gestión con `gestion/public/` como raíz pública y configura su base privada.
2. Prepara el dominio HTTPS de la tienda y un servicio Node persistente.
3. Configura las variables de `web/.env` en el servidor:

| Variable | Uso |
| --- | --- |
| `SITE_URL` | Dominio HTTPS público de la tienda. |
| `BACKEND_URL` | Dirección del backend PHP accesible desde el servidor Astro. |
| `BACKEND_API_KEY` | Clave privada que debe coincidir con la de Gestión. No usar prefijos `PUBLIC_`. |

4. Desde `web/`, compila y arranca:

```bash
npm ci
npm run build
npm start
```

El comando `start` lee `.env`. El proceso Node debe mantenerse activo detrás de un proxy HTTPS. Configura los puertos del servidor mediante el entorno correspondiente y limita la exposición del backend. `npm run dev` se utiliza para desarrollo.

Antes de publicar, completa los canales reales de contacto y revisa privacidad y condiciones comerciales. Las fotografías generadas por IA son ilustrativas y no representan personal ni instalaciones reales. Los pedidos requieren confirmación; no se anuncia una pasarela de pago operativa.

## Pruebas y documentación

```bash
npm run build
npm run format:check
```

La compilación valida tipos y produce el servidor. Las pruebas PHP y de integración están en `gestion/tests/`; deben utilizar su base separada. Las comprobaciones visuales y límites de cada sesión se registran en [RECONSTRUCCION.md](RECONSTRUCCION.md): compilar no acredita por sí solo una revisión visual completa de todos los dispositivos.

- [Estado de reconstrucción comercial](RECONSTRUCCION.md).
- [Arquitectura elegida](ARQUITECTURA.md).
- [Catálogo de demostración local](docs/CATALOGO-DEMO100.md).
- [Integración de Desktop](https://github.com/MijahelPersonal/inventario-web-Veterinaria/blob/main/docs/DESKTOP.md).
