# Especialidad: Seguridad

Responsable coordinador: Calidad.

## Alcance

Autenticación PHP, permisos, API, preload, IPC, red local y distribución. El coordinador debe asignar archivos concretos y un objetivo antes de editar. Este playbook puede aplicarse en el agente principal o en un subagente disponible; no crea procesos por sí solo.

## Revisión y calidad

Trazar entradas no confiables y límites de privilegio. Revisar CSRF, SQL parametrizado, cargas de archivos, secretos, ventanas y procedencia de paquetes. Priorizar evidencia reproducible y la menor modificación que resuelva el problema, manteniendo interfaces y datos existentes.

## Comprobaciones

Pruebas negativas de origen, autorización y manipulación; validación de secretos en artefactos y pruebas de integridad según el cambio. Informar por separado lo ejecutado, lo fallido y lo pendiente.

## Límites y escalamiento

No publicar datos sensibles ni debilitar aislamiento para pasar pruebas. Escalar pérdida de datos, ejecución de código o permisos excedidos antes de recomendar publicación. Respetar AGENTS.md, la solicitud del usuario y los permisos heredados. Entregar hallazgos con archivo y pasos de reproducción; no invadir archivos que otro agente esté editando.
