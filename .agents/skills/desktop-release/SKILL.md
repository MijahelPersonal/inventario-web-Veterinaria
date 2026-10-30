---
name: desktop-release
description: Preparar y verificar una versión de StruchVet Desktop, sus instaladores, metadatos y recuperación, antes de publicar mediante el workflow autorizado.
---

# Lanzamiento de StruchVet Desktop

Aplicar cuando se solicite preparar, verificar o publicar una versión de Desktop. Leer primero AGENTS.md, `electron/package.json`, `electron/electron-builder.yml`, el workflow de lanzamiento real y la documentación de distribución existente.

1. Confirmar la versión semántica, rama y commit candidatos. Enumerar cambios, compatibilidad de datos y plataformas que realmente se podrán ejecutar. No crear una release estable como consecuencia automática de un commit.
2. Revisar el contenido del paquete: aplicación PHP, vendor, runtime y recursos necesarios; excluir configuraciones locales, secretos, bases, respaldos y cargas privadas. Comprobar origen, hashes y licencias del runtime.
3. Ejecutar las pruebas relacionadas con inventario, permisos, transporte local, procesos y actualización. Usar la base de pruebas separada, no la principal. Ejecutar los comandos reales de package.json y guardar el resultado con plataforma y versión.
4. Construir instaladores y metadatos mediante el workflow o scripts mantenidos. Comprobar que versión, nombres, arquitectura, hashes y proveedor correspondan al mismo commit. No inventar artefactos no generados.
5. En una instalación de prueba, respaldar datos, instalar, abrir Gestión y probar cierre/reinicio. Ensayar actualización desde una versión anterior de prueba y conservar registros, imágenes y configuración. Probar descarga interrumpida e integridad fallida. Si falta infraestructura, dejar la verificación expresamente pendiente.
6. Preparar notas de versión y matriz de pruebas. Indicar certificados, permisos, tokens de CI o plataformas ausentes. Ningún secreto debe escribirse en notas ni en el repositorio.
7. Publicar sólo dentro de la autorización de lanzamiento del usuario y de los requisitos reales del workflow. Si sólo se pidió preparar la versión, dejar el commit y los artefactos revisables sin publicar. Después de publicar, verificar el release y los archivos remotos antes de afirmar que está disponible.

## Entrega

Informar commit/tag si existen, artefactos con rutas o enlaces reales, comprobaciones ejecutadas, preservación/recuperación verificada y pendientes. Distinguir claramente pruebas unitarias de una actualización instalada. Los formatos sin soporte de actualización automática deben explicar su alternativa real.
