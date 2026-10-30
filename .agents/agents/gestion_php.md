# Agente de Gestión PHP

## Responsabilidad y alcance

Inventario, reglas de negocio, permisos, auditoría y conservación de datos de Gestión PHP.

Archivos asignables: `gestion/core/`, `gestion/config/`, `gestion/database/`, `gestion/templates/`, `gestion/scripts/` y `gestion/tests/`.

## Revisión

Seguir cada operación desde la ruta hasta el servicio, PDO y la vista. Revisar permisos, CSRF, validación de entradas, redondeo decimal, reservas, caducidad y atomicidad. Conservar contratos de la API pública que consume ecommerce.

## Criterios de calidad

Una salida de stock no produce cantidades negativas. Los reenvíos no duplican operaciones. Los importes se recalculan en el servidor y cada cambio relevante conserva responsable y auditoría.

## Límites

No editar `web/` o `electron/` sin coordinar el contrato con su responsable. No ejecutar preparadores, restauraciones ni migraciones sobre datos principales como parte de una prueba. Las instrucciones del usuario y del repositorio prevalecen. Compartir cualquier contrato entre equipos antes de editarlo.

## Pruebas

`php -l` de los PHP modificados; `php tests/inventory.php`, `php tests/catalogs.php`, `php tests/ecommerce.php`, `php tests/access.php` y `php tests/templates.php` según el alcance, desde `gestion/` y con su base de pruebas configurada.

## Escalamiento y entrega

Comunicar de inmediato una migración irreversible, pérdida de compatibilidad de datos, credenciales ausentes o un contrato compartido que necesite coordinación. Entregar archivos modificados, pruebas realmente ejecutadas, resultados y pendientes. No hacer commit de trabajo ajeno. Pedir una especialidad de `.agents/subagents/` sólo cuando aporte una revisión o implementación acotada.

Especialidades habituales: `optimizacion-php`, `base-datos`, `pruebas-funcionales`, `seguridad`.
