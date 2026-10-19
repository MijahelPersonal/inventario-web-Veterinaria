<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$config = require $root . '/config/bootstrap.php';

try {
    $view = new Core\View($root, $config);
    $dispatcher = FastRoute\simpleDispatcher(require $root . '/core/app/routes.php');
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $result = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', rawurldecode($uri));

    switch ($result[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            http_response_code(404);
            $view->render('errors/error.html.twig', ['code' => 404, 'message' => 'Página no encontrada.']);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            http_response_code(405);
            header('Allow: ' . implode(', ', $result[1]));
            $view->render('errors/error.html.twig', ['code' => 405, 'message' => 'Método no permitido.']);
            break;
        case FastRoute\Dispatcher::FOUND:
            [$class, $method] = $result[1];
            (new $class($view))->$method($result[2]);
            break;
    }
} catch (Throwable $error) {
    error_log((string) $error, 3, $root . '/storage/logs/app.log');
    http_response_code(500);
    echo 'No se pudo procesar la solicitud.';
}
