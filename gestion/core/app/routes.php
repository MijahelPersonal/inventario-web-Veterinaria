<?php
declare(strict_types=1);

use App\Controller\HomeController;
use App\Controller\AuthController;
use FastRoute\RouteCollector;

return static function (RouteCollector $routes): void {
    $admin = \App\Controller\AdministrationController::class;
    $routes->addRoute('GET', '/configuracion', [$admin, 'settings', 'settings.manage']);
    $routes->addRoute('POST', '/configuracion', [$admin, 'saveSettings', 'settings.manage']);
    $routes->addRoute('GET', '/usuarios', [$admin, 'users', 'users.manage']);
    $routes->addRoute('POST', '/usuarios', [$admin, 'saveUser', 'users.manage']);
    $routes->addRoute('GET', '/auditoria', [$admin, 'audit', 'audit.read']);
    $inventory = \App\Controller\InventoryController::class;
    $routes->addRoute('GET', '/inventario', [$inventory, 'index', 'inventory.read']);
    $routes->addRoute('POST', '/inventario/movimientos', [$inventory, 'save', 'inventory.write']);
    $agenda = \App\Controller\AppointmentController::class;
    $routes->addRoute('GET', '/citas', [$agenda, 'index', 'appointments.read']);
    $routes->addRoute('GET', '/citas/eventos', [$agenda, 'events', 'appointments.read']);
    $routes->addRoute('POST', '/citas', [$agenda, 'create', 'appointments.write']);
    $routes->addRoute('POST', '/citas/{id:\d+}/estado', [$agenda, 'status', 'appointments.write']);
    $routes->addRoute('POST', '/citas/horarios', [$agenda, 'hours', 'appointments.schedule']);
    $routes->addRoute('GET', '/', [HomeController::class, 'index']);
    $routes->addRoute('GET', '/login', [AuthController::class, 'form']);
    $routes->addRoute('POST', '/login', [AuthController::class, 'login']);
    $routes->addRoute('POST', '/logout', [AuthController::class, 'logout']);
    $slugs = implode('|', array_keys(\App\Service\CatalogService::definitions()));
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
