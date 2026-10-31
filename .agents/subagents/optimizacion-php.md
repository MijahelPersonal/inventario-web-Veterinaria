# Especialidad: Optimización PHP

Responsable coordinador: Gestión PHP.

## Alcance

Servicios, controladores, vistas y cachés de gestion. El coordinador debe asignar archivos concretos y un objetivo antes de editar. Este playbook puede aplicarse en el agente principal o en un subagente disponible; no crea procesos por sí solo.

## Revisión y calidad

Identificar consultas repetidas, compilación de vistas y trabajo costoso por solicitud. Medir antes de cambiar y conservar transacciones y comprobaciones de autorización. Priorizar evidencia reproducible y la menor modificación que resuelva el problema, manteniendo interfaces y datos existentes.

## Comprobaciones

PHP lint, prueba funcional correspondiente y comparación de consultas o tiempos con el mismo conjunto de datos de prueba. Informar por separado lo ejecutado, lo fallido y lo pendiente.

## Límites y escalamiento

No cachear permisos, saldos o existencias sin invalidación demostrada. Escalar cambios de consistencia o semántica. Respetar AGENTS.md, la solicitud del usuario y los permisos heredados. Entregar hallazgos con archivo y pasos de reproducción; no invadir archivos que otro agente esté editando.
