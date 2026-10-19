<?php
declare(strict_types=1);

use App\Controller\HomeController;
use App\Controller\AuthController;
use FastRoute\RouteCollector;

return static function (RouteCollector $routes): void {
    $clinical = \App\Controller\ClinicalController::class;
    $routes->addRoute('GET', '/fichas/{kind:mascotas|veterinarios}/{id:\d+}', [
        $clinical,
        'profile',
        'catalogs.read',
    ]);
    $routes->addRoute('GET', '/citas/{id:\d+}', [$clinical, 'appointment', 'appointments.read']);
    $routes->addRoute('GET', '/citas/{id:\d+}/pdf', [$clinical, 'pdf', 'clinical.read']);
    $routes->addRoute('POST', '/citas/{id:\d+}/notas', [$clinical, 'notes', 'clinical.write']);
    $routes->addRoute('POST', '/citas/{id:\d+}/reprogramar', [
        $clinical,
        'reschedule',
        'appointments.write',
    ]);
    $reports = \App\Controller\ReportController::class;
    $routes->addRoute('GET', '/reportes', [$reports, 'index', 'reports.read']);
    $routes->addRoute('GET', '/reportes/csv', [$reports, 'csv', 'reports.read']);
    $routes->addRoute('GET', '/reportes/pdf', [$reports, 'pdf', 'reports.read']);
    $store = \App\Controller\StoreController::class;
    $routes->addRoute('GET', '/tienda', [$store, 'index', 'sales.write']);
    $routes->addRoute('POST', '/tienda', [$store, 'checkout', 'sales.write']);
    $routes->addRoute('POST', '/productos/{id:\d+}/imagen', [$store, 'upload', 'catalogs.write']);
    $routes->addRoute('GET', '/media/productos/{id:\d+}', [$store, 'image', 'catalogs.read']);
    $finance = \App\Controller\FinanceController::class;
    $routes->addRoute('GET', '/cobros/nuevo', [$finance, 'paymentForm', 'payments.write']);
    $routes->addRoute('POST', '/ventas/{id:\d+}/anular', [$finance, 'void', 'sales.void']);
    $routes->addRoute('GET', '/ventas', [$finance, 'index', 'sales.read']);
    $routes->addRoute('POST', '/ventas', [$finance, 'sale', 'sales.write']);
    $routes->addRoute('GET', '/ventas/{id:\d+}/ticket', [$finance, 'receipt', 'sales.read']);
    $routes->addRoute('POST', '/cobros', [$finance, 'pay', 'payments.write']);
    $routes->addRoute('POST', '/cobros/{id:\d+}/revertir', [
        $finance,
        'reverse',
        'payments.reverse',
    ]);
    $routes->addRoute('POST', '/ventas/efectivo/{action:open|close|entry}', [
        $finance,
        'cashOperation',
        'cash.write',
    ]);
    $admin = \App\Controller\AdministrationController::class;
    $routes->addRoute('GET', '/configuracion', [$admin, 'settings', 'settings.manage']);
    $routes->addRoute('POST', '/configuracion', [$admin, 'saveSettings', 'settings.manage']);
    $routes->addRoute('GET', '/usuarios', [$admin, 'users', 'users.manage']);
    $routes->addRoute('POST', '/usuarios', [$admin, 'saveUser', 'users.manage']);
    $inventory = \App\Controller\InventoryController::class;
    $routes->addRoute('GET', '/inventario', [$inventory, 'index', 'inventory.read']);
    $routes->addRoute('POST', '/inventario/movimientos', [$inventory, 'save', 'inventory.write']);
    $agenda = \App\Controller\AppointmentController::class;
    $routes->addRoute('GET', '/calendario', [$agenda, 'calendar', 'appointments.read']);
    $routes->addRoute('GET', '/citas', [$agenda, 'index', 'appointments.read']);
    $routes->addRoute('GET', '/citas/eventos', [$agenda, 'events', 'appointments.read']);
    $routes->addRoute('POST', '/citas', [$agenda, 'create', 'appointments.write']);
    $routes->addRoute('POST', '/citas/{id:\d+}/estado', [$agenda, 'status', 'appointments.write']);
    $routes->addRoute('POST', '/citas/horarios', [$agenda, 'hours', 'appointments.schedule']);
    $routes->addRoute('GET', '/', [HomeController::class, 'index']);
    $routes->addRoute('GET', '/login', [AuthController::class, 'form']);
    $routes->addRoute('POST', '/login', [AuthController::class, 'login']);
    $routes->addRoute('POST', '/logout', [AuthController::class, 'logout']);
    $slugs = 'productos|clientes|mascotas|veterinarios|servicios';
    $base = '/catalogos/{slug:' . $slugs . '}';
    $routes->addRoute('GET', $base, [
        \App\Controller\CatalogController::class,
        'index',
        'catalogs.read',
    ]);
    $routes->addRoute('GET', $base . '/nuevo', [
        \App\Controller\CatalogController::class,
        'form',
        'catalogs.write',
    ]);
    $routes->addRoute('GET', $base . '/{id:\d+}/editar', [
        \App\Controller\CatalogController::class,
        'form',
        'catalogs.write',
    ]);
    $routes->addRoute('POST', $base, [
        \App\Controller\CatalogController::class,
        'save',
        'catalogs.write',
    ]);
    $routes->addRoute('POST', $base . '/{id:\d+}', [
        \App\Controller\CatalogController::class,
        'save',
        'catalogs.write',
    ]);
};
