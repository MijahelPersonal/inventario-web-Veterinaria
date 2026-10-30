# Catálogo de demostración: 100 productos

Lote: `DEMO100-20261030`. Cargado en la base principal `struch_vet` mediante los servicios de catálogos e inventario de gestión. La web consulta esa misma base a través del catálogo público. No se sustituyeron productos anteriores.

## Contenido

- 100 productos publicados, con imágenes locales: 52 Canbo, 3 Ricocan y 45 de Nestlé/Purina.
- 11 marcas: Canbo, Ricocan, Purina One, Cat Chow, Dog Chow, Fancy Feast, Pro Plan, Friskies, Felix, Dogui y Dentalife.
- 25 ofertas de prueba sin fecha final.
- 95 productos con existencias: 90 con stock normal y 5 con 3 unidades. Otros 5 están agotados.
- Total: 2.950 unidades simuladas; 100 entradas iniciales y 10 ajustes para los estados de stock bajo y agotado.

Los precios, costos y existencias son simulados. Las descripciones y los motivos de movimientos lo indican. Los códigos permiten identificar el lote en gestión; las tarjetas y las fichas de la web indican que son productos de prueba. No se añadieron códigos de barras ficticios ni fechas de vencimiento inventadas.

## Fuentes y fotografías

Se verificaron nombres y presentaciones en los [catálogos Canbo para perros](https://www.canbo.pe/perros/) y [gatos](https://www.canbo.pe/gatos/), la [tienda oficial Nestlé](https://tiendanestle.pe/collections/mascotas) y el [catálogo Ricocan de La Curacao](https://www.lacuracao.pe/mascotas/alimentos.html?brand%5B0%5D=Ricocan). Las fotografías son de esos catálogos públicos. Una fotografía de fórmula puede ilustrar distintos tamaños de esa misma fórmula: se advierte en las descripciones. No se generaron empaques ficticios.

Los archivos privados de carga están en `gestion/storage/private/catalogo-demo100/`:

- `productos.json`: datos, presentación, precio simulado y enlaces originales de cada producto e imagen.
- `imagenes/`: 78 imágenes distintas, convertidas a JPEG con fondo blanco y lado máximo de 1.000 píxeles.
- `cargar.php`: carga por código y movimientos con claves de idempotencia. Repetirla no duplica datos. No usarla para restaurar existencias después de ventas o ajustes posteriores.
- `resultado.json`: identificadores reales de productos y movimientos.
- `verificacion.json`: resultado de la comprobación del catálogo compartido.

Estos archivos no están expuestos como recursos públicos ni incluidos en Git. Para retirar el lote cuando terminen las pruebas, buscar `DEMO100-20261030` en Productos y retirar su publicación/desactivar los registros; conservar el historial de movimientos. No borrar productos usados en ventas.

## Verificación

Compilación Astro: 42 archivos sin errores ni advertencias. Pruebas PHP de inventario, comercio y plantillas aprobadas en la base separada de pruebas. Comprobación HTTP en la base principal: 100 productos y sus precios/ofertas/existencias, 100 imágenes, 5 páginas de 20 tarjetas sin duplicados, filtros por marca, secciones perros/gatos/ofertas, fichas disponibles/agotadas, carrito y checkout. Inicio de sesión administrativo e inventario: los 100 códigos presentes. Repetición de carga: sin duplicados.

No se confirmó ninguna venta ni cobro real durante estas comprobaciones. La comprobación de carrito/checkout fue de respuesta de las páginas; no se realizó una nueva interacción visual con el navegador.
