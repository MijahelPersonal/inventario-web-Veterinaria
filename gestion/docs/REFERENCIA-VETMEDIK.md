# Referencia de estudio: VetMedik

Se revisó la copia local `C:/laragon/www/vetmedik-main`, atribuida en su README a Evilnapsis. El proyecto original permanece intacto. No se importan su configuración, cuentas de demostración ni dependencias vendorizadas.

## Correspondencias

| VetMedik | STRUCH VET |
|---|---|
| PacientController / pacients | Catálogo de mascotas y ficha con responsable separado |
| MedicController / medics | Catálogo de veterinarios, especialidad y disponibilidad |
| CategoryController | Especialidades separadas de categorías de productos |
| ReservationController | AppointmentController y AppointmentService |
| PaymentRecordController | Cobros con saldo calculado y anulaciones auditadas |
| ReportController | Reportes por periodo, CSV y PDF |
| UserController | Administración del personal con roles y usuario de acceso |
| SettingController | Preferencias validadas de la veterinaria |
| HomeController | Resumen con indicadores y gráficos |

La adaptación conserva los flujos de aprendizaje con los componentes de STRUCH VET. Los archivos de la referencia no se pegan encima: sus namespaces, tablas, rutas y relaciones son distintos y reemplazarlos rompería la gestión existente. Las operaciones de borrado con historial se sustituyen por desactivación, cancelación o reversión.

## Recorrido MVC

`public/index.php` comprueba sesión, permiso y CSRF → `routes.php` elige controlador → el controlador llama al servicio → el servicio valida y coordina la transacción → el modelo ejecuta PDO → Twig presenta el resultado.

Los servicios no dependen de Twig ni Electron: una futura API podrá invocarlos sin duplicar reglas ni dar acceso directo a MySQL a la web.
