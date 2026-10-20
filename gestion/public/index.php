<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$config = require $root . '/config/bootstrap.php';

try {
    Core\Session::start();
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header(
        "Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; script-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'",
    );
    header('Cache-Control: no-store');
    $view = new Core\View($root, $config);
    $view->setUser(null);
    $context = new Core\Context($view, $config);
    $dispatcher = FastRoute\simpleDispatcher(require $root . '/core/app/routes.php');
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $result = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', rawurldecode($uri));

    switch ($result[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            http_response_code(404);
            $view->render('errors/error.html.twig', [
                'code' => 404,
                'message' => 'Página no encontrada.',
            ]);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            http_response_code(405);
            header('Allow: ' . implode(', ', $result[1]));
            $view->render('errors/error.html.twig', [
                'code' => 405,
                'message' => 'Método no permitido.',
            ]);
            break;
        case FastRoute\Dispatcher::FOUND:
            [$class, $method] = $result[1];
            $user = null;
            $publicWeb = $class === App\Controller\PublicWebController::class;
            if (
                !$publicWeb &&
                ($class !== App\Controller\AuthController::class || $method === 'logout')
            ) {
                $user = $context->auth()->current();
                if (!$user) {
                    header('Location: /login', true, 303);
                    exit();
                }
                $permission = $result[1][2] ?? null;
                if (
                    $permission &&
                    !in_array('*', $user['permissions'], true) &&
                    !in_array($permission, $user['permissions'], true)
                ) {
                    http_response_code(403);
                    $view->render('errors/error.html.twig', [
                        'code' => 403,
                        'message' => 'No tienes permiso para realizar esta operación.',
                    ]);
                    break;
                }
            }
            $view->setUser($user);
            if ($user) {
                $view->settings((new App\Model\Administration($context->db()))->settings());
            }
            if (
                !$publicWeb &&
                in_array(
                    $_SERVER['REQUEST_METHOD'] ?? 'GET',
                    ['POST', 'PUT', 'PATCH', 'DELETE'],
                    true,
                ) &&
                (!is_string($_POST['csrf'] ?? null) ||
                    !hash_equals($_SESSION['csrf'], $_POST['csrf']))
            ) {
                http_response_code(403);
                $view->render('errors/error.html.twig', [
                    'code' => 403,
                    'message' => 'Formulario vencido. Recarga la página.',
                ]);
                break;
            }
            (new $class($context))->$method($result[2]);
            break;
    }
} catch (DomainException $error) {
    http_response_code(422);
    $view->render('errors/error.html.twig', ['code' => 422, 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log((string) $error, 3, $root . '/storage/logs/app.log');
    http_response_code(500);
    echo 'No se pudo procesar la solicitud.';
}
