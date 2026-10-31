# Recursos generados para StruchVet Desktop

`npm run prepare:resources` prepara `runtime/`, `gestion/` y `manifest.json`. Son salidas locales excluidas de Git. `npm run verify:resources` comprueba todos los hashes y rechaza la configuración privada del desarrollador. El empaquetado debe incluir estos recursos fuera de ASAR mediante `extraResources`.

El equipo de compilación necesita PHP 8.3–8.5, extensiones requeridas y las dependencias Composer ya instaladas. El usuario final recibe ese PHP y esas dependencias dentro del instalador: no instala Node.js, Composer, Apache ni PHP.

En Linux el script copia PHP, sus extensiones compartidas, el cargador ELF y bibliotecas transitivas. El lanzador usa rutas relativas a su propia ubicación. El resultado debe probarse en las distribuciones objetivo; compilar en una distribución no certifica todas las demás. La compilación de CI usa Ubuntu 24.04 como base. La compilación local Fedora conserva su propio runtime.

En Windows se incorpora la distribución PHP instalada en el runner, sus DLL y extensiones, y el runtime VC disponible para despliegue local. Revisar los derechos de redistribución de Microsoft, las licencias de bibliotecas y la firma antes de una versión estable. Windows 10/11 proporciona Universal CRT. No hay descarga de código al iniciar la aplicación.

La base MariaDB/MySQL, contraseñas, archivos subidos, respaldos y sesiones no forman parte de estos recursos. El manifiesto permite detectar corrupción de archivos; la procedencia de una publicación depende además de los artefactos del workflow, las firmas y la cuenta de GitHub, no de un hash autocontenido.

macOS requiere un runtime PHP relocatable revisado, infraestructura macOS y certificados de firma/notarización. La preparación rechaza macOS hasta implementar y validar esa ruta; el icono ICNS ya está disponible.

## Compilación local en Fedora

La herramienta FPM descargada por electron-builder incluye Ruby, pero puede necesitar `libcrypt.so.1` en Fedora reciente. El equipo de compilación puede instalarla con `sudo dnf install libxcrypt-compat`. Esta dependencia pertenece a la herramienta de construcción; el usuario final no la necesita para ejecutar StruchVet.

En la comprobación del 30 de octubre de 2026 se evitó cambiar el sistema: `dnf download` guardó el RPM Fedora en `/tmp/struchvet-build-deps/rpms`, se verificó con `rpmkeys --checksig`, se extrajo con `rpm2cpio` y `cpio`, y se limitó `LD_LIBRARY_PATH=/tmp/struchvet-build-deps/root/usr/lib64` al proceso de compilación. La carpeta temporal no forma parte del instalador.

Si una aplicación de prueba tiene abierto `dist/linux-unpacked`, cerrar esa aplicación antes de regenerar el paquete. Puede usarse una salida aislada, por ejemplo `--config.directories.output=/tmp/struchvet-rpm-build`, sin tocar los recursos o datos del usuario.
