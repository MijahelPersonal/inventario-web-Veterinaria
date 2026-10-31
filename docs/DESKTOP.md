# Integración PHP y Electron

## Decisiones

Electron vive en la raíz, fuera de Gestión y Web. Se trasladó el lanzador anterior de `gestion/electron/`; esa carpeta y sus dependencias de escritorio se retiraron de Gestión. Se conserva el identificador `dev.mijxhel.struchvet` y se usa StruchVet Desktop 0.3.0 como versión de trabajo.

MariaDB/MySQL conserva sus tablas, restricciones, transacciones y auditoría. No se convierte la base ni se incluye un servidor SQL embebido: una veterinaria necesita un servidor provisionado o un servidor de Gestión HTTPS. En varios puestos se recomienda HTTPS, porque también centraliza las imágenes y el almacenamiento. Un SQL compartido con PHP en cada escritorio comparte filas, pero no sincroniza archivos entre equipos. SQL remoto requiere TLS verificado mediante CA o una red privada/VPN; no publicar el puerto MySQL en Internet. La pantalla acepta ruta absoluta al CA, trasladada a PDO con verificación del certificado. HTTPS utiliza la validación de certificados de Chromium, sin excepciones para certificados inválidos.

## Inicio

`src/main/index.cjs` abre el asistente empaquetado mediante el protocolo interno `struch-app://desktop`. La configuración del perfil selecciona HTTPS remoto o PHP incluido. El modo local:

1. Comprueba integridad SHA256 de recursos empaquetados; un manifiesto no sustituye firma de código.
2. Ejecuta `scripts/desktop.php status` con PHP incluido; detecta conexión, migraciones pendientes, esquema más nuevo y recuperación incompleta.
3. Solicita confirmación antes de aplicar migraciones y crear el primer administrador. Ninguna petición web aplica migraciones automáticamente.
4. Reserva un puerto efímero de loopback, reintenta conflictos y levanta PHP con el router de escritorio.
5. Comprueba `/__desktop/health`, abre Gestión y controla salida/fallo del proceso. Al salir detiene el hijo antes de cerrar Electron.

PHP CLI contiene un servidor HTTP incorporado que se usa exclusivamente como servicio privado de loopback; no es un servidor público de producción. No requiere el proceso de desarrollo del repositorio, Apache, rutas del equipo compilador ni dependencias instaladas por el usuario. El runtime y Composer se incluyen en `resources/`, fuera de ASAR. Las extensiones requeridas son bcmath, ctype, dom, fileinfo, iconv, mbstring, openssl, PDO/pdo_mysql, session, tokenizer y XML; se incluyen gd/zip cuando están disponibles. Linux incorpora cargador ELF y bibliotecas transitivas; Windows incorpora PHP/DLL/VC app-local. La base del compilador afecta compatibilidad: probar en cada distribución objetivo. Véase [runtime](../electron/resources/README.md).

## Datos y configuración

`app.getPath('userData')/data/` contiene:

- `desktop.json`: configuración privada, con permiso 0600 en Unix y permisos del perfil de usuario en Windows. La contraseña SQL no se imprime ni se devuelve al renderer; se requiere reingresarla al guardar. Este archivo no está cifrado: proteger el perfil, sus respaldos y el disco. No guarda la contraseña del administrador de Gestión.
- `storage/uploads/products`: imágenes.
- `storage/cache`, `sessions`, `logs`, `private`: datos de ejecución.
- `backups`: SQL, configuración e imágenes, y copia anterior del AppImage antes de actualizar.

Gestión en servidor mantiene sus rutas normales de `gestion/storage` y `config/local.php`. `Core\Paths` activa la ubicación externa únicamente con las variables internas de escritorio. No se distribuyen `.env`, `local.php`, `web.local.php`, bases, cargas, logs ni respaldos del desarrollador.

Conectar este perfil a una base ya existente no importa automáticamente las imágenes antiguas: un administrador debe copiar las imágenes de productos al almacenamiento del perfil, conservando nombres, o utilizar el servidor HTTPS que ya las sirve. No copiar cachés ni sesiones. Esto evita alterar o mover silenciosamente los archivos del servidor actual.

## Seguridad

- `nodeIntegration:false`, `contextIsolation:true`, `sandbox:true`, `webSecurity:true`.
- Preload con canales finitos; IPC restringido a ventana de control propia y frame principal. Las páginas PHP/HTTPS no pueden leer configuración ni instalar actualizaciones mediante IPC.
- Navegación limitada al origen de Gestión y al protocolo interno. Ventanas emergentes, webviews y permisos se rechazan.
- El protocolo interno sirve sólo cuatro archivos conocidos con CSP estricta. Los textos de notas de actualización se presentan como texto.
- Puerto enlazado a `127.0.0.1`. Cada ejecución genera un token aleatorio de 256 bits, inyectado desde el proceso principal sólo al servicio propio. Router valida token, Host exacto, dirección local y rutas estáticas. Se conserva CSRF, autenticación y roles PHP. La cookie del escritorio es distinta de la de la web.
- Operaciones de configuración, preparación, respaldo e instalación serializadas. Se evita cerrar a mitad de una operación.

## Respaldos y migraciones

Para respaldo manual se confirma el cierre temporal de Gestión. PHP se detiene antes del snapshot SQL y la copia de configuración/imágenes, y se reabre después. Para actualización no se reabre. El snapshot SQL utiliza una transacción consistente InnoDB, incluye esquema, filas, zona horaria y sql_mode, escribe `.partial` y sólo publica el archivo al terminar. Rechaza vistas/tablas no InnoDB; en esos casos se necesita respaldo supervisado del servidor. Otros escritores externos y cambios DDL deben suspenderse durante mantenimiento; este cliente no puede detener otras aplicaciones conectadas.

Las migraciones usan `schema_migrations` y un bloqueo de MariaDB. Al fallar un DDL queda un diario privado, se bloquean reintentos y se solicita recuperación. Restaurar se hace en una base nueva, con revisión profesional antes de sustituir la conexión: no hay un botón que sobrescriba tablas. Un cliente anterior se bloquea si detecta migraciones de una versión posterior. La creación del primer administrador está validada y no reemplaza usuarios existentes.

## Actualizaciones y publicaciones

`electron-updater` consulta exclusivamente la configuración GitHub empaquetada. No se cambia el feed de producción por variables del usuario. Descarga e instalación requieren acción explícita; se muestran versión instalada/disponible, notas, progreso y errores. Una versión ausente no se presenta como disponible. Desarrollo no consulta actualizaciones automáticamente.

RPM y NSIS generan metadatos del constructor; macOS requiere DMG+ZIP y firma. SHA512 de los metadatos verifica la descarga. HTTPS/GitHub, firmas y la procedencia del workflow son la cadena de confianza; un hash en la misma descarga no constituye una firma. `win.signtoolOptions.publisherName` debe coincidir exactamente con el sujeto del certificado real (valor inicial del proyecto: MijahelPersonal). La verificación de firma de actualización de Windows está activada. Los artefactos locales no están firmados y no acreditan la actualización segura en Windows.

El workflow construye en Ubuntu 24.04 y Windows 2022, ejecuta las comprobaciones, conserva instaladores y metadatos, y permite crear un **borrador de release** únicamente con un tag `vX.Y.Z` igual a `electron/package.json`. No publica estable por commit. El borrador añade checksums y attestations de GitHub. Revisar los resultados, firma, notas y pruebas antes de hacer público el release. No se ha creado ningún tag ni release con esta implementación.

Secretos de firma Windows: `WIN_CSC_LINK`, `WIN_CSC_KEY_PASSWORD`; revisar también publisherName. `GITHUB_TOKEN` sólo tiene escritura en el job del borrador. macOS necesitará certificados, Apple ID/App Store Connect, notarización y runtime revisado; no se declara soportado hasta validarlo. Fedora se distribuye únicamente como RPM y se actualiza instalando el nuevo paquete con DNF. No se distribuyen AppImage ni DEB. La actualización integrada de RPM todavía requiere validación real. La instalación RPM sigue pendiente de validación en una máquina limpia.

## Desarrollo y construcción

Desde `electron`: `npm ci`, `npm run prepare:resources`, `npm run verify:resources`, `npm test`, `npm start`. Antes preparar Composer y los assets de Gestión. `npm run build:linux` genera únicamente el RPM de Fedora; `build:win` usa NSIS en Windows. No usar un runtime Linux para empaquetar Windows ni macOS.

En Fedora el helper de FPM puede necesitar `libxcrypt-compat`; el problema y la alternativa temporal están en la guía del runtime. No desactivar la sandbox de Chromium para solucionar errores del entorno. Un fallo de firma, conexión, respaldo o migración debe resolverse antes de publicar; no ocultarlo con estados ficticios.

## Fuentes técnicas

[Seguridad de Electron](https://www.electronjs.org/docs/latest/tutorial/security), [actualizaciones de electron-builder v26](https://www.electron.build/v26/docs/features/auto-update/), [documentación PHP](https://www.php.net/manual/en/features.commandline.webserver.php) y [reglas de agentes Codex](AGENTES.md). Las comprobaciones reales y sus límites se registran en [VALIDACION-DESKTOP.md](VALIDACION-DESKTOP.md).
