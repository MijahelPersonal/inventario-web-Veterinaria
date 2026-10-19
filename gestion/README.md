# STRUCH VET — Sistema de gestión veterinaria

Gestión MVC con PHP, PDO, Twig, FastRoute, CoreUI y Electron. Desarrollo incremental inspirado en los flujos de VetMedik; no se copia su código ni sus recursos.

## Desarrollo

Desde esta carpeta:

1. `composer install` y `npm ci` para recuperar dependencias en otra instalación.
2. `npm run assets` prepara CoreUI y los recursos propios dentro de public/assets.
3. `npm run serve` inicia PHP en http://127.0.0.1:8080 (solo desarrollo).
4. En otra terminal, `npm start` abre Electron. Electron no inicia PHP ni MySQL.

Si usas Apache de Laragon, configura la raíz del sitio en esta carpeta **public**, nunca en la raíz del proyecto. El .htaccess requiere mod_rewrite y permisos de configuración adecuados. Nginx requiere configuración equivalente propia.

## Organización

- core/app/Controller: entradas HTTP; recibe formularios y devuelve vistas o redirecciones.
- core/app/Service: validación, reglas de negocio y transacciones.
- core/app/Model: consultas PDO; no lee formularios ni genera HTML.
- core/framework: infraestructura compartida.
- templates: plantillas Twig privadas.
- assets: fuentes; ejecutar npm run assets después de editarlas.
- public: única raíz accesible por HTTP.
- database/migrations y seeds: futuros cambios de esquema y datos iniciales.
- storage: caché, logs y archivos privados. Debe ser escribible por PHP.
- electron: lanzador de escritorio, preload limitado y actualizador con descarga e instalación controladas.
- tests: pruebas ejecutables contra struch_vet_test, separada de los datos de operación.

## Configuración

Copiar config/local.example.php a config/local.php y completar las credenciales cuando se prepare la base. No usar root. Esta configuración no se publica ni se incorpora a Electron.

Ejecutar `php scripts/migrate.php` para aplicar cambios pendientes. Para una instalación local NUEVA de Laragon sin contraseña root puede utilizarse `php scripts/setup-local.php`: se detiene si existen las bases o configuración y no las reemplaza. Crea usuario exclusivo y guarda el acceso inicial en `storage/private/acceso-inicial.txt`, excluido de Git.

## Cómo estudiar el código

Ejemplo de catálogo: `routes.php` dirige la solicitud a `CatalogController`; este llama a `CatalogService` para validar y a `Catalog` para consultar; Twig muestra los resultados. `config/catalogs.php` describe campos permitidos para evitar repetir formularios y consultas. Los nombres de tablas nunca vienen del navegador.

`npm run format` aplica formato consistente. Los comentarios de una línea describen responsabilidades y decisiones importantes.

Pruebas: `php tests/access.php`, `php tests/catalogs.php` y `php tests/templates.php`. Las dos primeras requieren MySQL y configuración local de pruebas. No usan ni limpian datos de producción.

Consulte `CHANGELOG.md` y el historial Git para avances por módulo. El sistema está en desarrollo y requiere validación operativa antes de producción.

Electron usa STRUCH_VET_URL si está definida; por defecto abre el servidor local de desarrollo. Backend remoto: HTTPS obligatorio. El entorno PHP/MySQL de distribución se decidirá por separado.

## Apariencia y versión 0.2.0

El menú mantiene Dashboard, tienda, productos, inventario, clientes, ventas, agenda, calendario, mascotas, veterinarios, servicios, reportes y administración. Se retiraron los accesos a especialidades, categorías, marcas, proveedores y auditoría; los registros históricos se conservan. Ya no se registran los inicios y cierres de sesión en auditoría. Los movimientos de existencias y comprobantes conservan su responsable.

Configuración permite diez colores y tema claro u oscuro. El pie compartido muestra MijxhelDev y la versión; permanece al fondo en páginas cortas y después del contenido en páginas largas.

El POS confirma venta, stock y cobro juntos. Solicita método de pago y, si hace falta comenzar un turno, fondo inicial explícito. Ventas y cobros permite consultar comprobantes y cobrar saldos pendientes de productos o servicios sin duplicar el pago del POS.

Para empaquetado y distribución consulta [Actualizaciones](docs/ACTUALIZACIONES.md). `npm run test:desktop` comprueba los estados del actualizador; `php tests/finance.php` incluye reenvíos del checkout y rollback completo ante stock insuficiente.
