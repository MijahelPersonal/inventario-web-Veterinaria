# StruchVet · Gestión veterinaria y tienda web

**Administra tu veterinaria desde Linux o Windows y muestra tu catálogo en una página web.** StruchVet reúne inventario, ventas, caja, clientes, mascotas y citas en Gestión; la web presenta productos, ofertas, carrito y solicitudes de atención.

[Descargar instaladores](#descargas) · [Instalar en mi equipo](#instalar) · [Configurar la conexión](#conexion) · [Conocer Gestión y Web](#aplicaciones) · [Instalar la web](#web) · [Estructura](#estructura) · [Desarrollo](#desarrollo)

<a id="descargas"></a>
## 1. Descargar StruchVet Desktop

**Versión disponible: 0.3.0 de prueba · 64 bits.** Elige tu sistema:

| Mi sistema | Descargar paquete | Archivo que debo abrir después de descomprimir el ZIP |
| --- | --- | --- |
| **Windows · x64** | **[Descargar para Windows](https://github.com/MijahelPersonal/inventario-web-Veterinaria/actions/runs/41134095276/artifacts/13014921620)** | `StruchVet-Desktop-0.3.0-win-x64.exe` |
| **Fedora KDE / otras distribuciones RPM** | **[Descargar para Linux](https://github.com/MijahelPersonal/inventario-web-Veterinaria/actions/runs/41134095276/artifacts/13014567010)** | `StruchVet-Desktop-0.3.0-linux-x86_64.rpm` |
| **Ubuntu / Debian** | **[Descargar para Linux](https://github.com/MijahelPersonal/inventario-web-Veterinaria/actions/runs/41134095276/artifacts/13014567010)** | `StruchVet-Desktop-0.3.0-linux-amd64.deb` |
| **Linux portable** | **[Descargar para Linux](https://github.com/MijahelPersonal/inventario-web-Veterinaria/actions/runs/41134095276/artifacts/13014567010)** | `StruchVet-Desktop-0.3.0-linux-x86_64.AppImage` |

**Cómo descargar:** abre el enlace de tu sistema, inicia sesión en GitHub si lo solicita, guarda el ZIP y descomprímelo. El ZIP de Linux contiene los tres formatos: utiliza el correspondiente a tu distribución. Para usar el programa no necesitas clonar el repositorio ni compilarlo.

Estos enlaces corresponden a [una compilación aprobada de GitHub Actions](https://github.com/MijahelPersonal/inventario-web-Veterinaria/actions/runs/41134095276). Son artefactos temporales disponibles hasta el **13 de noviembre de 2026**. Aún no hay una versión estable publicada; las futuras descargas permanentes se encontrarán en [Releases](https://github.com/MijahelPersonal/inventario-web-Veterinaria/releases). Los paquetes incluyen metadatos de actualización y un manifiesto del runtime; no incluyen datos de clientes.

| Estado de esta versión | Verificación |
| --- | --- |
| AppImage Linux | Construido, abierto en Fedora KDE y probado con Gestión; actualización local 0.3.0 → 0.3.1 comprobada. |
| RPM y DEB | Construidos localmente y en CI; falta instalar y actualizar mediante los gestores nativos en máquinas limpias. |
| Windows EXE / NSIS | Compilado en Windows 2022 mediante CI; falta ejecutar su instalación gráfica y validar firma y actualización en Windows. El paquete de prueba no está firmado. |
| macOS | Pendiente de runtime PHP, firma y pruebas; no se ofrece un instalador macOS. |

<a id="instalar"></a>
## 2. Instalar en mi equipo

### Windows

1. Descarga y descomprime el **paquete Windows** de la tabla anterior.
2. Ejecuta `StruchVet-Desktop-0.3.0-win-x64.exe`.
3. Sigue el asistente y elige la carpeta de instalación.
4. Abre **StruchVet Desktop** desde el menú Inicio o el acceso directo.
5. Completa la [conexión inicial](#conexion).

El instalador está configurado para crear accesos directos, permitir desinstalación y conservar los datos del perfil. La prueba de instalación Windows permanece pendiente según la tabla de estado.

### Fedora KDE

Descarga y descomprime el paquete Linux. Desde la carpeta donde quedó el RPM:

```bash
sudo dnf install ./StruchVet-Desktop-0.3.0-linux-x86_64.rpm
```

Después abre **StruchVet Desktop** desde el menú de aplicaciones y completa la [conexión inicial](#conexion).

### Ubuntu / Debian

Desde la carpeta donde quedó el DEB:

```bash
sudo apt install ./StruchVet-Desktop-0.3.0-linux-amd64.deb
```

Abre **StruchVet Desktop** desde el menú de aplicaciones y completa la [conexión inicial](#conexion).

### Linux portable con AppImage

Puedes probarlo sin instalar un RPM o DEB:

```bash
chmod +x StruchVet-Desktop-0.3.0-linux-x86_64.AppImage
./StruchVet-Desktop-0.3.0-linux-x86_64.AppImage
```

Para crear su acceso en el menú, dentro del programa selecciona **StruchVet → Instalar AppImage en este usuario**. Se copiará a `~/Applications/StruchVet/`; cierra la ventana y vuelve a abrirlo desde el menú. Si tu distribución solicita FUSE y no puede arrancarlo, la alternativa comprobada es:

```bash
APPIMAGE_EXTRACT_AND_RUN=1 ./StruchVet-Desktop-0.3.0-linux-x86_64.AppImage
```

<a id="conexion"></a>
## 3. Primera conexión: qué necesito para entrar

**El instalador incluye PHP y las dependencias de Gestión. El usuario no necesita Node.js, Composer ni Apache.** Para acceder a los datos de una veterinaria necesita una de estas dos opciones, facilitada por su administrador:

| Opción del asistente | Datos necesarios | Dónde quedan los datos |
| --- | --- | --- |
| **PHP incluido + MySQL/MariaDB** | Servidor, puerto, base existente, usuario SQL y contraseña SQL; CA si corresponde a una conexión TLS. | La base permanece en su servidor SQL. Configuración, imágenes y respaldos del cliente quedan en el perfil de este equipo. |
| **Servidor de Gestión HTTPS** | Dirección HTTPS del servidor de Gestión y una cuenta de acceso a Gestión. | La base y los archivos permanecen centralizados en ese servidor. Si está en Internet, necesitas Internet para trabajar. |

En el modo PHP incluido, la base debe estar creada y accesible. El instalador no incorpora ni configura un servidor MySQL/MariaDB. Para varios puestos que comparten imágenes, utiliza preferentemente el servidor de Gestión HTTPS.

Pulsa **Guardar y conectar**. Si hay migraciones pendientes, el asistente solicitará confirmación y respaldará las tablas existentes antes de aplicarlas. Una base nueva solicita crear el primer administrador; no se distribuye una contraseña predeterminada. Después inicia sesión con tu cuenta de Gestión.

**La contraseña de conexión SQL y la contraseña de tu cuenta de Gestión son accesos distintos.** En una instalación de desarrollo existente, el administrador encontrará la configuración SQL en `gestion/config/local.php`; ese archivo es privado y no está en GitHub. Las imágenes existentes no se importan automáticamente al perfil de escritorio: deben copiarse conservando sus nombres o servirse desde el backend HTTPS existente.

<a id="aplicaciones"></a>
## 4. ¿Qué hace cada parte del proyecto?

### Gestión veterinaria · uso del personal

Gestión es el sistema interno de la veterinaria. Puede abrirse dentro de Desktop o desplegarse en un servidor para acceso por navegador.

- **Productos e inventario:** marcas, categorías, precios, ofertas, publicación web, imágenes, lotes, vencimientos, entradas y salidas.
- **Ventas y caja:** venta en tienda, cobros, comprobantes y movimientos relacionados con existencias.
- **Clientes y mascotas:** propietarios, expedientes y antecedentes.
- **Atención veterinaria:** veterinarios, servicios, citas, agenda, calendario y registros clínicos.
- **Administración:** usuarios, permisos, preferencias, reportes y solicitudes recibidas desde la web.

[Ver el código de Gestión](https://github.com/MijahelPersonal/inventario-web-Veterinaria/tree/main/gestion) · [Guía técnica de Desktop](https://github.com/MijahelPersonal/inventario-web-Veterinaria/blob/main/docs/DESKTOP.md)

### StruchVet Web · uso de los clientes

La web es el escaparate de la veterinaria: catálogo, categorías para perros y gatos, marcas, ofertas, fichas de producto, carrito, checkout y formularios de atención. Consulta los productos, precios y existencias publicados desde el backend de Gestión.

Un pedido web se registra como **solicitud pendiente** para que el personal confirme disponibilidad, entrega y pago. No hay pasarela de pago integrada. Las solicitudes de cita requieren confirmación del personal antes de ocupar un horario de agenda.

**Dirección pública:** todavía no se ha publicado un dominio de producción. Para probarla localmente se utiliza `http://127.0.0.1:4321/`; si Astro indica otro puerto libre, utiliza el que muestre la terminal. Esa dirección sólo corresponde al equipo donde se está ejecutando.

**[Abrir código de la página web](https://github.com/MijahelPersonal/inventario-web-Veterinaria/tree/main/web)** · **[Descargar código del proyecto en ZIP](https://github.com/MijahelPersonal/inventario-web-Veterinaria/archive/refs/heads/main.zip)** · [Guía de la web](https://github.com/MijahelPersonal/inventario-web-Veterinaria/blob/main/web/README.md)

La web se aloja en un servidor y los clientes la visitan desde su navegador. Tiene sus propias dependencias y despliegue; el instalador Desktop abre Gestión.

<details>
<summary><strong>Ver capturas reales de StruchVet Desktop</strong></summary>

Primera ejecución de Desktop 0.3.0 e inventario en Fedora KDE. Los registros que aparecen son datos temporales de pruebas.

![Asistente de conexión](https://raw.githubusercontent.com/MijahelPersonal/inventario-web-Veterinaria/refs/heads/main/docs/screenshots/desktop-inicio.png)

![Inventario dentro de Desktop](https://raw.githubusercontent.com/MijahelPersonal/inventario-web-Veterinaria/refs/heads/main/docs/screenshots/desktop-inventario.png)

</details>

<a id="web"></a>
## 5. Instalar y ejecutar la página web

Esta sección es para quien va a preparar el servidor o desarrollar el proyecto. Los clientes de la tienda sólo necesitan su navegador.

**Requisitos:** Node.js 22, PHP 8.3–8.5 con PDO MySQL, BCMath y mbstring, Composer y una base MariaDB/MySQL configurada. El código completo está en la rama principal `main`.

### Obtener el código

```bash
git clone --branch main --single-branch https://github.com/MijahelPersonal/inventario-web-Veterinaria.git struchvet
cd struchvet
```

También puedes usar el ZIP enlazado en la sección de la web.

### Preparar Gestión y el backend

Desde la carpeta del proyecto:

```bash
cd gestion
composer install
npm ci
npm run assets
```

Copia `config/local.example.php` a `config/local.php` y completa la conexión SQL con un usuario dedicado. El administrador debe crear la base y revisar cualquier migración antes de aplicarla; respalda una base existente. No publiques este archivo ni uses una cuenta SQL de administrador para la aplicación.

```bash
php scripts/migrate.php
php scripts/create-admin.php
npm run serve
```

**Si ya tienes una cuenta de Gestión, omite `php scripts/create-admin.php`.** Ese comando solicita datos por terminal y crea un administrador para una instalación nueva; no es necesario para abrir una instalación existente. El backend de desarrollo queda en **http://127.0.0.1:8080**.

### Preparar la web en otra terminal

Desde la carpeta raíz del proyecto:

```bash
cd web
npm ci
node scripts/setup-local.mjs
npm run dev
```

Abre la dirección que muestre Astro, normalmente **http://127.0.0.1:4321**. El script prepara la clave privada compartida entre servidores y conserva la configuración existente. Para que aparezcan productos, publícalos desde **Gestión → Productos** y registra sus existencias en **Inventario**.

### Publicar la web en un servidor

Configura `SITE_URL` con el dominio HTTPS público, `BACKEND_URL` con el backend de Gestión y `BACKEND_API_KEY` con su clave privada compartida. Mantén las credenciales fuera del repositorio. Desde `web/`:

```bash
npm run build
npm start
```

El proceso Node debe permanecer activo detrás de un proxy HTTPS y tener acceso al backend PHP. El dominio público debe configurarlo el responsable del despliegue; este repositorio aún no anuncia uno. Más detalles en [la guía web](https://github.com/MijahelPersonal/inventario-web-Veterinaria/blob/main/web/README.md).

<a id="estructura"></a>
## 6. Estructura del proyecto

Estructura del código completo disponible en [la rama principal](https://github.com/MijahelPersonal/inventario-web-Veterinaria/tree/main):

```text
StruchVet/
├── gestion/                  # Backend PHP y sistema del personal
│   ├── core/                 # Controladores, servicios, modelos y framework
│   ├── database/migrations/   # Cambios versionados de la base
│   ├── public/               # Única raíz pública del backend
│   ├── templates/            # Pantallas Twig
│   ├── scripts/              # Preparación, migraciones y servicio Desktop
│   └── tests/                # Pruebas con base separada
├── web/                      # Tienda pública Astro / TypeScript
│   ├── src/pages/            # Catálogo, carrito, checkout y formularios
│   ├── src/components/       # Componentes compartidos
│   ├── src/lib/              # Conexión privada al backend y tipos
│   └── public/images/        # Recursos públicos de la tienda
├── electron/                 # Aplicación Desktop independiente
│   ├── src/main/             # Ventanas, PHP, respaldos y actualizador
│   ├── src/preload/          # Comunicación limitada con la interfaz
│   ├── src/renderer/         # Asistente y controles de Desktop
│   ├── assets/icons/         # Icono original: PNG, ICO e ICNS
│   ├── resources/            # Runtime y Gestión preparados al compilar
│   ├── scripts/              # Preparación y verificación del paquete
│   └── electron-builder.yml  # Instaladores y proveedor de actualizaciones
├── .agents/                  # Responsabilidades, especialidades y skill de release
├── .codex/agents/            # Cinco roles registrados para Codex
├── .github/workflows/        # Pruebas, builds y borradores por tag
├── docs/                     # Guías, capturas y evidencia de validación
├── AGENTS.md                 # Reglas compartidas de desarrollo
└── README.md                 # Descargas y puesta en marcha
```

Las dependencias, instaladores generados, credenciales, bases, respaldos e imágenes privadas de clientes quedan excluidos de Git.

<a id="desarrollo"></a>
## 7. Desarrollar y compilar Desktop

Después de preparar Composer y los assets de `gestion/`, desde la raíz:

```bash
cd electron
npm ci
npm run prepare:resources
npm run verify:resources
npm test
npm start
```

Para generar paquetes:

```bash
# En Linux:
npm run build:linux

# En Windows:
npm run build:win
```

Las salidas se guardan en `electron/dist/`. El compilador necesita PHP y herramientas de construcción; los usuarios que descargan el instalador reciben el PHP incluido. Los requisitos específicos de Fedora, extensiones y bibliotecas están en [la guía del runtime](https://github.com/MijahelPersonal/inventario-web-Veterinaria/blob/main/electron/resources/README.md).

## 8. Actualizaciones y protección de datos

En Desktop abre **StruchVet → Comprobar actualizaciones**. El cliente consulta versiones reales de GitHub Releases y presenta versión instalada/disponible, notas y progreso. Descargar e instalar requieren tu acción. Las configuraciones y archivos del perfil se conservan; en modo PHP incluido se detiene el servicio y se crea un respaldo antes de instalar. Los archivos de Gestión PHP se distribuyen junto con Desktop.

Las pruebas han comprobado una actualización local AppImage **0.3.0 → 0.3.1**, rechazo de descarga corrupta y conservación del perfil. La firma, la instalación/actualización Windows, la instalación nativa RPM/DEB y la recuperación completa del esquema requieren más validación antes de una versión estable.

GitHub Actions verifica PHP/MariaDB y compila Linux y Windows. Un tag de versión coincidente prepara un **borrador de release**, no publica automáticamente una estable por cada commit. Actualmente no se ha publicado ninguna release estable. Consulta [el informe de pruebas](https://github.com/MijahelPersonal/inventario-web-Veterinaria/blob/main/docs/VALIDACION-DESKTOP.md) y [el protocolo de lanzamiento](https://github.com/MijahelPersonal/inventario-web-Veterinaria/blob/main/.agents/skills/desktop-release/SKILL.md).

## 9. Tecnologías, agentes y documentación

| Parte | Tecnologías |
| --- | --- |
| Gestión | PHP, PDO, MariaDB/MySQL, Twig, FastRoute y CoreUI |
| Web | Astro, TypeScript y CSS |
| Desktop | Electron, electron-builder y electron-updater |
| Automatización | GitHub Actions; cinco roles Codex y doce especialidades documentadas |

Los agentes son instrucciones para desarrollar y revisar el repositorio; no son procesos de IA que se instalan en el programa. Consulta [AGENTS.md](https://github.com/MijahelPersonal/inventario-web-Veterinaria/blob/main/AGENTS.md) y [la guía de agentes](https://github.com/MijahelPersonal/inventario-web-Veterinaria/blob/main/docs/AGENTES.md).

## 10. Reportar un problema

Abre un [issue en GitHub](https://github.com/MijahelPersonal/inventario-web-Veterinaria/issues) con versión, sistema operativo, pasos y resultado esperado. Oculta contraseñas y datos personales en las capturas; no adjuntes bases, respaldos ni archivos de configuración privados.

Autor: **MijahelPersonal**. La aplicación mantiene su uso reservado; sus bibliotecas y el símbolo original conservan sus respectivas licencias.
