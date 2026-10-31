# Especialidad: Compatibilidad Linux

Responsable coordinador: Electron Desktop.

## Alcance

Empaquetado AppImage/RPM/DEB, iconos y ciclo de procesos en Linux. El coordinador debe asignar archivos concretos y un objetivo antes de editar. Este playbook puede aplicarse en el agente principal o en un subagente disponible; no crea procesos por sí solo.

## Revisión y calidad

Priorizar Fedora KDE. Revisar bibliotecas redistribuidas, integración de menú, permisos de archivos, display, actualización y persistencia de datos. Priorizar evidencia reproducible y la menor modificación que resuelva el problema, manteniendo interfaces y datos existentes.

## Comprobaciones

Arranque/cierre/reinicio, detección de dependencias, instalación del paquete elegido en entorno limpio y comprobación de procesos huérfanos. Informar por separado lo ejecutado, lo fallido y lo pendiente.

## Límites y escalamiento

No desactivar el sandbox de Chromium como solución de distribución. No afirmar otras distribuciones probadas sin ejecutarlas; escalar dependencias o firma ausentes. Respetar AGENTS.md, la solicitud del usuario y los permisos heredados. Entregar hallazgos con archivo y pasos de reproducción; no invadir archivos que otro agente esté editando.
