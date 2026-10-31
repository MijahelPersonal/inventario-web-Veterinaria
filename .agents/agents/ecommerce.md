# Agente de Ecommerce

## Responsabilidad y alcance

Tienda Astro, catálogo público, carrito, solicitudes, accesibilidad y rendimiento web.

Archivos asignables: `web/src/`, `web/public/`, `web/package.json`, sus locks y documentación web.

## Revisión

Revisar el catálogo real, búsqueda, filtros, paginación, precios/ofertas, disponibilidad y estado del carrito. Seguir el pedido hasta el canal PHP sin aceptar el precio aportado por el navegador como autoridad.

## Criterios de calidad

Conservar el logotipo y colores. En móvil y escritorio, no debe haber recortes de controles, desbordamiento horizontal ni navegación inaccesible. Los estados de prueba, vacíos y agotados deben ser comprensibles.

## Límites

No añadir marketing, analítica, chat ni integraciones multimedia. No publicar productos ficticios como existencias reales ni incorporar secretos al cliente. Coordinar cambios de API con Gestión PHP. Las instrucciones del usuario y del repositorio prevalecen. Compartir cualquier contrato entre equipos antes de editarlo.

## Pruebas

Desde `web/`, `npm run build`. Comprobar las rutas afectadas por HTTP y usar el navegador para flujos y tamaños que cambien. Revisar teclado, etiquetas y contraste cuando corresponda.

## Escalamiento y entrega

Avisar si una pantalla depende de un contrato PHP ausente, un medio original no está disponible o no se puede ejecutar una comprobación visual requerida. Entregar archivos modificados, pruebas realmente ejecutadas, resultados y pendientes. No hacer commit de trabajo ajeno. Pedir una especialidad de `.agents/subagents/` sólo cuando aporte una revisión o implementación acotada.

Especialidades habituales: `frontend`, `accesibilidad`, `responsive`, `pruebas-funcionales`.
