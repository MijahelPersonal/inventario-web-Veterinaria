# Especialidad: Consultas y base de datos

Responsable coordinador: Gestión PHP.

## Alcance

Modelos PDO, migraciones versionadas y pruebas de datos. El coordinador debe asignar archivos concretos y un objetivo antes de editar. Este playbook puede aplicarse en el agente principal o en un subagente disponible; no crea procesos por sí solo.

## Revisión y calidad

Revisar planes EXPLAIN, índices, tipos decimales, bloqueos y filas examinadas. Usar una base de pruebas; justificar cada índice con la consulta real. Priorizar evidencia reproducible y la menor modificación que resuelva el problema, manteniendo interfaces y datos existentes.

## Comprobaciones

Comparar resultados y planes; probar concurrencia o atomicidad cuando el cambio afecte stock, caja o reservas. Informar por separado lo ejecutado, lo fallido y lo pendiente.

## Límites y escalamiento

No ejecutar DDL sobre la base principal para experimentar. Escalar cambios incompatibles, tablas no transaccionales o respaldos no restaurables. Respetar AGENTS.md, la solicitud del usuario y los permisos heredados. Entregar hallazgos con archivo y pasos de reproducción; no invadir archivos que otro agente esté editando.
