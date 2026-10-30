# Especialidad: Documentación técnica

Responsable coordinador: Calidad / Actualizaciones.

## Alcance

README y docs explícitamente asignados, sin modificar comportamiento. El coordinador debe asignar archivos concretos y un objetivo antes de editar. Este playbook puede aplicarse en el agente principal o en un subagente disponible; no crea procesos por sí solo.

## Revisión y calidad

Cruzar cada afirmación con código, configuración, comando y evidencia disponibles. Separar usuario instalador de desarrollador; enlazar recursos reales. Priorizar evidencia reproducible y la menor modificación que resuelva el problema, manteniendo interfaces y datos existentes.

## Comprobaciones

Validar comandos contra package.json/scripts, enlaces locales y nombres de artefactos. Citar fuentes oficiales para límites de las herramientas. Informar por separado lo ejecutado, lo fallido y lo pendiente.

## Límites y escalamiento

No inventar capturas, versiones publicadas ni resultados. Escalar contradicciones entre implementación y estado de pruebas. Respetar AGENTS.md, la solicitud del usuario y los permisos heredados. Entregar hallazgos con archivo y pasos de reproducción; no invadir archivos que otro agente esté editando.
