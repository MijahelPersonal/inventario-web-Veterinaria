# Validación StruchVet Desktop

Fecha: 30 de octubre de 2026. Versión distribuible de trabajo: **0.3.0**. Plataforma local: Fedora KDE x86_64, kernel `7.2.13-200.fc44.x86_64`, PHP incluido 8.5.12 y Electron 44.9.0. La versión **0.3.1** se construyó solamente como fixture local de actualización; no se modificó la versión fuente ni se creó un tag/release.

## Artefactos reales

Generados en `electron/dist/`, excluidos de Git por su tamaño y carácter de salida de compilación:

| Archivo | Bytes | Comprobación |
| --- | ---: | --- |
| `StruchVet-Desktop-0.3.0-linux-x86_64.AppImage` | 145092924 | Construido y ejecutado |
| `StruchVet-Desktop-0.3.0-linux-x86_64.rpm` | 102608417 | Construido; contenido/encabezados inspeccionados |
| `StruchVet-Desktop-0.3.0-linux-amd64.deb` | 115170532 | Construido; contenido/controles inspeccionados |

`latest-linux.yml` contiene las tres rutas, tamaños y SHA512, comprobados contra los archivos finales. `SHA256SUMS` y `runtime-manifest-linux.json` conservan las comprobaciones. Se verificaron los **864 hashes** de recursos del paquete y que todos los archivos `electron/src/` coinciden byte por byte con los contenidos de su ASAR final. Estos paquetes locales no llevan firma de distribución; no son una publicación estable.

## Comprobaciones ejecutadas

- `npm --prefix electron test`: **7 pruebas aprobadas**, sin omisiones. Incluyen configuración, permisos de archivo, rechazo de recursos alterados, instalación AppImage por usuario, PHP real incluido, renderizado Twig del login, validación de token/Host, cierre y reinicio del servicio, y estados del actualizador.
- `php gestion/tests/desktop.php`: almacenamiento/configuración externos; respaldo SQL de fixture sin modificar la base; migraciones explícitas/idempotentes y bloqueo de recuperación; HTTP privado, recursos y sesiones. Se ejecuta contra `struch_vet_test`.
- `php gestion/tests/desktop-restore.php`: dos restauraciones reales de una tabla InnoDB aislada, usando el SQL intacto de DesktopMaintenance. Se conservan esquema, filas/hash, Unicode, comillas, bytes binarios, NULL, decimales y timestamps, con SQL normal y `NO_BACKSLASH_ESCAPES`. Sólo se crean/renombran/eliminan tablas propias con marcador único; las tablas originales quedan intactas. Este ensayo no sustituye la restauración completa con claves foráneas.
- PHP: `tests/inventory.php`, `ecommerce.php`, `access.php`, `templates.php` aprobados en la base de pruebas; otras comprobaciones de tienda/web también pasaron durante la integración. Las 28 plantillas compilan.
- PDF real generado con PHP incluido (7.399 bytes), usando el almacenamiento externo de caché.
- Base principal comprobada sin cambios: 100 productos de demostración y 2.950 unidades. La base de pruebas conservó 13 unidades después de actualizar.
- Web: `npm --prefix web run build`, **42 archivos sin errores ni advertencias**. La tienda conserva su proyecto y configuración independientes.
- Conexión HTTPS inválida: la aplicación vuelve al asistente con un mensaje comprensible para corregir la dirección, sin desactivar la validación de certificados.
- `gui-smoke.cjs`: ejecutado con Electron real cargando el main/preload/renderer del ASAR empaquetado y sus recursos externos. Perfil limpio muestra el asistente; perfil configurado conecta la base de pruebas, inicia sesión y abre Inventario. Verifica sandbox, aislamiento, ausencia de `require` en renderer y rechazo de IPC privilegiado desde Gestión. Capturas reales en `docs/screenshots/`.
- Formularios reales de inventario dentro de Electron: entrada de 2 unidades y salida por consumo de 1 sobre el producto temporal; saldo de **12 → 14 → 13**. No se usó la base principal.
- Perfil limpio en **X11 y Wayland** de Fedora KDE: asistente renderizado y capturado. El entorno emitió avisos gráficos Mesa/Vulkan, pero las comprobaciones de ventana/renderizado pasaron sin desactivar sandbox.
- Instalación AppImage por usuario en directorios temporales: copia ejecutable, PNG y `.desktop` generados y verificados; se ejecutó el archivo instalado con perfil XDG limpio mediante extracción de AppImage, sin PHP/Node/Composer del usuario final. Se cerró por límite de tiempo de la prueba nativa; el cierre normal y la terminación del PHP se comprobaron por separado en las suites y en el harness gráfico.
- DEB extraído en una carpeta limpia: el PHP incluido ejecuta 8.5.12 sin usar PHP del host. Esto no acredita instalación mediante `dpkg` en otra distribución.

## Actualización real local

`tests/real-update.cjs` utiliza **AppImageUpdater real**, no un doble del actualizador, un servidor HTTP de loopback efímero y dos AppImages construidos de 0.3.0 y 0.3.1. El feed de prueba no se incorpora al código de producción.

1. Detecta 0.3.1 desde metadatos de prueba reales.
2. El servidor altera un byte de la primera descarga: falla la verificación SHA512 y no se permite instalar.
3. Descarga otra vez el paquete íntegro y llega a `downloaded`.
4. Ejecuta la instalación de la biblioteca sobre una **copia desechable** del AppImage 0.3.0; el archivo resultante coincide exactamente con el SHA512 de 0.3.1.
5. Comprueba que configuración e imágenes de prueba conservan sus hashes y que el archivo del perfil permanece.
6. Extrae el AppImage realmente sustituido y ejecuta de nuevo el harness gráfico sobre su contenido: informa versión **0.3.1**, reutiliza la configuración ya conservada, inicia sesión y vuelve a Inventario.
7. Se creó además un respaldo SQL real con el PHP incluido, comprobando que contenía el producto temporal. La actualización no ejecutó migraciones ni cambió filas por sí misma.

El harness adapta los metadatos de la aplicación al fixture y llama la ruta real de instalación de la biblioteca sin cerrar el propio proceso de comprobación. Esto verifica descarga/corrupción/reemplazo/reinicio por separado; **no certifica todavía la cadena completa de GitHub público, la confirmación de la interfaz y la instalación de una release firmada**. Las pruebas con EventEmitter de la suite rápida verifican estados, no sustituyen este ensayo.

## GitHub Actions verificado

[Ejecución 41132992374](https://github.com/MijahelPersonal/inventario-web-Veterinaria/actions/runs/41132992374), sobre el commit de código `7f37218`, finalizó con éxito en sus tres jobs: **PHP/MariaDB**, **build Ubuntu 24.04** y **build Windows 2022**. En ambos sistemas se preparó y ejecutó el PHP incluido y pasaron las pruebas Node antes de compilar. Se conservaron los artefactos `struchvet-desktop-Linux` y `struchvet-desktop-Windows`; el de Windows contiene NSIS `.exe`, blockmap, `latest.yml` y manifiesto de runtime. El job de release se omitió correctamente porque no se creó ningún tag.

Los artefactos de CI tienen retención de 14 días. No se confundió la compilación Windows con una instalación ejecutada en Windows.

## Pendientes y límites

- Ejecutar el NSIS ya construido en una máquina Windows limpia, probar instalación/desinstalación, firma real y actualización de una versión firmada a otra. La compilación Windows de CI pasó; la instalación gráfica NSIS no se ejecutó en este equipo Linux.
- Instalar RPM/DEB con los gestores nativos en máquinas limpias y verificar sus actualizadores. Aquí se construyeron y revisaron sus contenidos, pero no se modificó el sistema operativo del usuario para instalarlos.
- Probar otras distribuciones, arquitecturas y equipos. Un build Fedora no acredita compatibilidad universal; CI construye una variante Linux desde Ubuntu 24.04.
- Runtime PHP macOS relocatable, firma, notarización y pruebas DMG/ZIP. La preparación rechaza esa plataforma hasta completar el runtime.
- Restauración SQL completa en una **segunda base**: no ejecutada porque la cuenta de pruebas no tiene permiso `CREATE DATABASE`. Se comprobó el contenido/escritura atómica y se ejecutó el roundtrip real de una tabla aislada sin reescribir su SQL; no se afirma recuperación completa del esquema con claves foráneas.
- Ensayar una release pública de prueba y su cadena de firma/procedencia antes de autorizar una estable. No se publicó ningún tag ni release en este trabajo.
- Revisar certificados y derechos de redistribución de runtime VC/bibliotecas antes de publicar una estable. No hay servidor MariaDB embebido; la conexión SQL/HTTPS debe estar provisionada.

Los registros de harness y fixtures están en directorios temporales privados, fuera de Git. Las capturas sólo muestran un usuario y producto de QA. Los datos y configuración de la base principal no se empaquetaron.

## Repetir los ensayos gráficos

Después de construir, ejecutar con el Electron de desarrollo de la misma versión, apuntando al directorio `linux-unpacked` de la aplicación real:

```bash
cd electron
STRUCH_QA_OUTPUT=/tmp/struchvet-qa-clean \
STRUCH_QA_INSTALLED="/ruta/absoluta/al/paquete/linux-unpacked" \
./node_modules/electron/dist/electron tests/gui-smoke.cjs --ozone-platform=x11
```

Para perfil configurado se usan `STRUCH_QA_CONFIG` y `STRUCH_QA_LOGIN` con JSON privados de una base `_test`. `STRUCH_QA_INVENTORY=1` ejecuta las dos operaciones sobre el producto temporal llamado «Producto de verificación escritorio», que debe comenzar con 12 unidades. No ejecutar ese ensayo sobre datos de operación. La prueba de actualización requiere en `/tmp/struchvet-update-qa` dos AppImages, `feed/latest-linux.yml` de 0.3.1, una copia desechable 0.3.0 llamada `StruchVet-test.AppImage` y datos privados de fixture; `STRUCH_UPDATE_FIXTURE` señala esa carpeta. No usar una aplicación instalada del usuario como destino de prueba.
