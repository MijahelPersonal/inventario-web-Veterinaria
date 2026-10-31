# Especialidad: Optimización frontend

Responsable coordinador: Ecommerce.

## Alcance

`web/src/components/`, estilos, scripts y carga de imágenes. El coordinador debe asignar archivos concretos y un objetivo antes de editar. Este playbook puede aplicarse en el agente principal o en un subagente disponible; no crea procesos por sí solo.

## Revisión y calidad

Medir primero tamaño, tiempos y recorrido afectado. Eliminar trabajo duplicado conservando diseño y comportamiento; dimensionar imágenes y revisar carga diferida. Priorizar evidencia reproducible y la menor modificación que resuelva el problema, manteniendo interfaces y datos existentes.

## Comprobaciones

`npm run build` desde web; comparar el flujo afectado y las mediciones antes/después. Informar por separado lo ejecutado, lo fallido y lo pendiente.

## Límites y escalamiento

No sustituir el framework ni añadir bibliotecas por una optimización local. Escalar cambios del contrato PHP o de identidad gráfica. Respetar AGENTS.md, la solicitud del usuario y los permisos heredados. Entregar hallazgos con archivo y pasos de reproducción; no invadir archivos que otro agente esté editando.
