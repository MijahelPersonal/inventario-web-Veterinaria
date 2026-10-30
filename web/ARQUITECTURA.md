# Arquitectura ecommerce elegida

Se conserva **Astro + TypeScript para la tienda, PHP/PDO para gestión y MariaDB para datos**. Es una decisión basada en el inventario, los roles, las imágenes y las solicitudes ya operativos; evita mantener dos fuentes de precios y existencias.

| Alternativa evaluada | Papel | Decisión para este proyecto |
|---|---|---|
| Salesforce Commerce Cloud | Plataforma comercial de comercio y servicios asociados | Requiere contratación/configuración; no se instaló ni se simuló una licencia. |
| Medusa | Backend modular de comercio con storefront independiente | Supondría migrar o sincronizar inventario, precios y administración que ya existen. No se añadió. |
| Saleor | Plataforma de comercio mediante API GraphQL | Cambiaría el contrato y el motor del backend actual. No se añadió. |
| Vendure | Backend de comercio basado en TypeScript/NestJS | Añadiría otro servicio y panel que duplicaría gestión. No se añadió. |
| Next.js | Framework posible para la interfaz | No resuelve por sí solo la fuente de stock y precios. Astro ya sirve la tienda y sus rutas de servidor; no se migró solo por cambiar de framework. |

No se instalaron jQuery, Slick ni plataformas competidoras. Se reutilizan las dependencias declaradas compatibles y los controles nativos del navegador.

Fuentes oficiales consultadas durante la evaluación:

- [Salesforce Commerce: ediciones y contratación](https://www.salesforce.com/commerce/pricing/)
- [Arquitectura de Medusa](https://docs.medusajs.com/learn/introduction/architecture)
- [Storefronts y Next.js en Medusa](https://docs.medusajs.com/learn/storefront-development)
- [API de comercio de Saleor](https://docs.saleor.io/)
- [Arquitectura de Vendure](https://docs.vendure.io/current/framework/developer-guide/overview)

La base actual puede recibir un proveedor de pago en el futuro con credenciales válidas y una implementación específica. La interfaz no anuncia pagos ya operativos.
