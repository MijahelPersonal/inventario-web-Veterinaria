# Agente de Electron Desktop

## Responsabilidad y alcance

Aplicación Electron, servicio PHP privado, rutas empaquetadas, procesos y compatibilidad de escritorio.

Archivos asignables: `electron/src/`, `electron/scripts/`, `electron/resources/`, `electron/assets/` y configuración de empaquetado.

## Revisión

Seguir el ciclo arranque, servicio PHP, disponibilidad, ventana y cierre. Inspeccionar aislamiento, preload, validación IPC, navegación, archivos persistentes, puertos y autenticación del servicio local. Probar rutas desde recursos instalados, no sólo desde el checkout.

## Criterios de calidad

El instalador incluye dependencias de ejecución y recursos públicos necesarios. Los errores explican la recuperación sin mostrar secretos. El cierre elimina los procesos propios; los datos del usuario sobreviven a reinstalación y actualización.

## Límites

No habilitar Node en renderers ni desactivar aislamiento para resolver fallos. No empaquetar configuraciones o bases privadas. No modificar reglas de negocio PHP sin coordinar Gestión PHP. Mantener `web/` independiente. Las instrucciones del usuario y del repositorio prevalecen. Compartir cualquier contrato entre equipos antes de editarlo.

## Pruebas

Desde `electron/`, `npm test`; consultar scripts reales para construir por plataforma. Probar arranque, servicio privado, puerto ocupado, ausencia del runtime, cierre/reinicio e instalación limpia con el artefacto construido.

## Escalamiento y entrega

Informar bibliotecas o runtimes no redistribuibles, falta de infraestructura por plataforma, firma ausente y cualquier operación que pueda sobrescribir datos. Entregar archivos modificados, pruebas realmente ejecutadas, resultados y pendientes. No hacer commit de trabajo ajeno. Pedir una especialidad de `.agents/subagents/` sólo cuando aporte una revisión o implementación acotada.

Especialidades habituales: `compilaciones`, `linux`, `windows`, `seguridad`.
