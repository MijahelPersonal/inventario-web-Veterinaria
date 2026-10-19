# STRUCH VET — Gestión

Base MVC con PHP, PDO, Twig, FastRoute, CoreUI y Electron. Los módulos de negocio todavía no están implementados.

## Desarrollo

Desde esta carpeta:

1. `composer install` y `npm ci` para recuperar dependencias en otra instalación.
2. `npm run assets` prepara CoreUI y los recursos propios dentro de public/assets.
3. `npm run serve` inicia PHP en http://127.0.0.1:8080 (solo desarrollo).
4. En otra terminal, `npm start` abre Electron. Electron no inicia PHP ni MySQL.

Si usas Apache de Laragon, configura la raíz del sitio en esta carpeta **public**, nunca en la raíz del proyecto. El .htaccess requiere mod_rewrite y permisos de configuración adecuados. Nginx requiere configuración equivalente propia.

## Organización

- core/app/controller: entradas HTTP.
- core/app/service: reglas de negocio y transacciones.
- core/app/model: acceso PDO y modelos.
- core/framework: infraestructura compartida.
- templates: plantillas Twig privadas.
- assets: fuentes; ejecutar npm run assets después de editarlas.
- public: única raíz accesible por HTTP.
- database/migrations y seeds: futuros cambios de esquema y datos iniciales.
- storage: caché, logs y archivos privados. Debe ser escribible por PHP.
- electron: proceso principal de escritorio; sin preload hasta necesitar funciones nativas.
- tests: futuras pruebas de reglas críticas.

## Configuración

Copiar config/local.example.php a config/local.php y completar las credenciales cuando se prepare la base. No usar root. Esta configuración no se publica ni se incorpora a Electron.

La pantalla inicial no abre conexiones ni crea tablas. No hay autenticación todavía: no exponer esta base como sistema de producción.

Electron usa STRUCH_VET_URL si está definida; por defecto abre el servidor local de desarrollo. Backend remoto: HTTPS obligatorio. El entorno PHP/MySQL de distribución se decidirá por separado.
