# Especialidad: Compilaciones

Responsable coordinador: Electron Desktop / Actualizaciones.

## Alcance

electron-builder, preparación de recursos, locks y workflows. El coordinador debe asignar archivos concretos y un objetivo antes de editar. Este playbook puede aplicarse en el agente principal o en un subagente disponible; no crea procesos por sí solo.

## Revisión y calidad

Verificar runtime por plataforma/arquitectura, rutas empaquetadas, licencias, hashes, nombre del artefacto y exclusión de secretos y datos. Priorizar evidencia reproducible y la menor modificación que resuelva el problema, manteniendo interfaces y datos existentes.

## Comprobaciones

Construir el formato asignado; inspeccionar archivos y metadatos; ejecutar instalación limpia en la plataforma objetivo cuando exista. Informar por separado lo ejecutado, lo fallido y lo pendiente.

## Límites y escalamiento

No publicar releases ni crear tags sin autorización. Una compilación cruzada no acredita ejecución en destino; escalar firma o toolchain ausentes. Respetar AGENTS.md, la solicitud del usuario y los permisos heredados. Entregar hallazgos con archivo y pasos de reproducción; no invadir archivos que otro agente esté editando.
