# Especialidad: Pruebas funcionales

Responsable coordinador: Calidad.

## Alcance

Pruebas y recorridos de las áreas asignadas; no alterar producto salvo encargo explícito. El coordinador debe asignar archivos concretos y un objetivo antes de editar. Este playbook puede aplicarse en el agente principal o en un subagente disponible; no crea procesos por sí solo.

## Revisión y calidad

Definir entradas, precondiciones y resultado observable para altas, edición, permisos, stock, pedidos y errores. Usar claves únicas y limpiar sólo registros propios. Priorizar evidencia reproducible y la menor modificación que resuelva el problema, manteniendo interfaces y datos existentes.

## Comprobaciones

Ejecutar la suite que corresponda y documentar cada reproducción con plataforma, versión y resultado. Informar por separado lo ejecutado, lo fallido y lo pendiente.

## Límites y escalamiento

No considerar una captura o respuesta 200 prueba suficiente de persistencia. Escalar una regresión de datos o un entorno sin aislamiento. Respetar AGENTS.md, la solicitud del usuario y los permisos heredados. Entregar hallazgos con archivo y pasos de reproducción; no invadir archivos que otro agente esté editando.
