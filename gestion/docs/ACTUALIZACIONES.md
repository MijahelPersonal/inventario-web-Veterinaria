# Versiones y actualizaciones

El sistema muestra la versión de `package.json` y el crédito de MijxhelDev en el pie compartido. La versión del backend y la del escritorio pueden diferir: Configuración muestra ambas cuando se abre desde Electron.

## Escritorio

`npm run desktop:build` crea un instalador NSIS para Windows x64 en `dist`, sin publicar una versión. Solo incluye el lanzador Electron y sus dependencias; excluye PHP, configuración local, credenciales y bases de datos.

El instalador debe apuntar al backend PHP mediante `STRUCH_VET_URL`. En desarrollo usa `http://127.0.0.1:8080`; un backend remoto requiere HTTPS. El servidor PHP y MySQL se administran por separado.

La aplicación instalada busca versiones al abrirse. Avisa de la nueva versión y sus mejoras; el usuario elige descargar y luego reiniciar e instalar. No instala silenciosamente al cerrar. En desarrollo informa de que se necesita un instalador.

El proveedor configurado es GitHub Releases del repositorio MijahelPersonal/inventario-web-Veterinaria. Un commit o push no distribuye una actualización: hay que incrementar la versión, construir y probar el instalador, y publicar el ejecutable, su blockmap y latest.yml en una release. No se ha publicado ninguna release con este cambio. Para distribuir comercialmente debe configurarse la firma de código y probar una actualización entre dos instaladores reales. Si el repositorio es privado, se necesitará un canal accesible por los usuarios; nunca incluir un token de GitHub en el instalador.

Documentación del proveedor: https://www.electron.build/v26/docs/features/auto-update/

## Backend y datos

El backend PHP se despliega por separado, con copia previa de la base de datos y una ventana de mantenimiento cuando cambie el esquema. Las migraciones nuevas se ejecutan con `php scripts/migrate.php`; las ya aplicadas no se editan. Una actualización de Electron no ejecuta migraciones ni modifica MySQL.

Si no hay conexión al canal de actualizaciones, se informa del fallo y se permite reintentar. Esto no implica funcionamiento del backend sin conexión: depende de dónde se aloje.
