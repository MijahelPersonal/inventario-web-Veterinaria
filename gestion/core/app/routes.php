<?php
declare(strict_types=1);

use App\controller\HomeController;
use FastRoute\RouteCollector;

return static function (RouteCollector $routes): void {
    $routes->addRoute('GET', '/', [HomeController::class, 'index']);
};
