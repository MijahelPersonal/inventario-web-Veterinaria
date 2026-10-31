# Especialidad: Versionado y migraciones

Responsable coordinador: Actualizaciones / Gestión PHP.

## Alcance

Versiones, migraciones, compatibilidad y recuperación de datos. El coordinador debe asignar archivos concretos y un objetivo antes de editar. Este playbook puede aplicarse en el agente principal o en un subagente disponible; no crea procesos por sí solo.

## Revisión y calidad

Asegurar semver, orden de migraciones, respaldo coherente y protección de configuración, imágenes y sesiones persistentes. Revisar fallos parciales de DDL. Priorizar evidencia reproducible y la menor modificación que resuelva el problema, manteniendo interfaces y datos existentes.

## Comprobaciones

Ensayar cambio entre dos versiones de prueba, restauración del respaldo y conservación de datos; comprobar versión/tag/metadatos. Informar por separado lo ejecutado, lo fallido y lo pendiente.

## Límites y escalamiento

No revertir tablas ni restaurar encima de datos actuales sin estrategia autorizada. Escalar una migración irreversible o recuperación no demostrada. Respetar AGENTS.md, la solicitud del usuario y los permisos heredados. Entregar hallazgos con archivo y pasos de reproducción; no invadir archivos que otro agente esté editando.
