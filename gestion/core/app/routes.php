<?php
declare(strict_types=1);

use App\Controller\HomeController;
use App\Controller\AuthController;
use FastRoute\RouteCollector;

return static function (RouteCollector $routes): void {
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
