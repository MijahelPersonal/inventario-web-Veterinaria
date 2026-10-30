# Instrucciones de StruchVet

## Proyecto y datos

- `gestion/`: PHP, PDO/MariaDB, Twig y reglas de negocio. `web/`: Astro/TypeScript y ecommerce. `electron/`: aplicación de escritorio independiente. Mantener esta separación.
- Antes de editar, revisar Git, el recorrido de ejecución y las instrucciones existentes. Conservar cambios de otros colaboradores y el logotipo original del corazón azul.
- Las instrucciones del usuario y los permisos de la sesión prevalecen. No añadir aprobaciones que no correspondan a la acción.
- No publicar contraseñas, tokens, archivos `.env`, configuraciones locales, bases de datos, imágenes subidas por clientes ni respaldos. No copiar datos privados al instalador.
- Usar migraciones versionadas para cambios de esquema. Ninguna prueba debe limpiar ni migrar la base principal. Las pruebas de integración PHP se ejecutan contra la base separada terminada en `_test`.
- Mantener cálculos monetarios en el servidor, transacciones, reservas, auditoría, permisos y CSRF. No sustituir MariaDB sin una estrategia acordada de conservación de datos.

## Comprobaciones según el cambio

Desde `gestion/`: `php tests/inventory.php`, `php tests/ecommerce.php`, `php tests/access.php` y `php tests/templates.php` según el comportamiento afectado. Revisar los requisitos de conexión de pruebas antes de ejecutarlos. Para un archivo PHP, `php -l ruta/al/archivo.php`.

Desde `web/`: `npm run build`. Si cambia la interfaz, comprobar el flujo y tamaños afectados con herramientas de navegador disponibles. Una compilación correcta no acredita una revisión visual.

Desde `electron/`: `npm ci`, `npm run prepare:resources`, `npm start` para desarrollo; `npm test` para pruebas. Compilar con `npm run build:linux`, `npm run build:win` o `npm run build:mac` en infraestructura compatible; consultar `package.json` y la documentación del runtime para sus requisitos. Ejecutar un instalador en la plataforma correspondiente antes de declarar superada la instalación. Las pruebas con dobles no acreditan una actualización instalada.

## Colaboración

Los cinco roles registrados están en `.codex/agents/`; las guías detalladas están en `.agents/agents/` y las especialidades en `.agents/subagents/`. Antes de delegar, asignar un objetivo acotado, archivos exclusivos y evidencia esperada. Delegar únicamente cuando lo autorice la petición o las instrucciones aplicables. No editar simultáneamente el mismo archivo.

La configuración no inicia agentes en segundo plano. Si el cliente no permite subagentes, ejecutar el mismo trabajo secuencialmente y declarar esa limitación. No fijar modelos ni permisos distintos de los heredados sin una razón y autorización aplicables.

## Entregas

Distinguir verificación ejecutada de verificación pendiente; indicar comandos, plataforma y artefactos reales. Crear commits coherentes y efectuar push cuando esté autorizado. No publicar una release estable por cada commit: seguir `.agents/skills/desktop-release/SKILL.md`, los workflows reales y la autorización de lanzamiento.
