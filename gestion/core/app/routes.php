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
};
