# Especialidad: Compatibilidad Windows

Responsable coordinador: Electron Desktop.

## Alcance

NSIS, runtime PHP Windows, rutas, accesos directos y cierre de procesos. El coordinador debe asignar archivos concretos y un objetivo antes de editar. Este playbook puede aplicarse en el agente principal o en un subagente disponible; no crea procesos por sí solo.

## Revisión y calidad

Revisar espacios y Unicode en rutas, instalación por usuario, iconos ICO, librerías runtime, desinstalación y conservación de datos. Priorizar evidencia reproducible y la menor modificación que resuelva el problema, manteniendo interfaces y datos existentes.

## Comprobaciones

Construir NSIS en infraestructura compatible y probar instalación limpia, arranque, reinicio, desinstalación y actualización en Windows real o VM. Informar por separado lo ejecutado, lo fallido y lo pendiente.

## Límites y escalamiento

No dar por probada la instalación desde un artefacto generado en Linux. Escalar certificados, permisos o entorno Windows ausentes. Respetar AGENTS.md, la solicitud del usuario y los permisos heredados. Entregar hallazgos con archivo y pasos de reproducción; no invadir archivos que otro agente esté editando.
