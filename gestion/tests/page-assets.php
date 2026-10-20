<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
// Comprueba que cada página reciba su inicializador y no cargue bibliotecas ajenas.
$root = dirname(__DIR__);
$_SESSION = ['csrf' => 'test'];
foreach (
    [
        '/inventario' => null,
        '/usuarios' => null,
        '/calendario' => 'calendar-page',
        '/' => 'dashboard-page',
        '/tienda' => 'store-page',
    ]
    as $route => $expected
) {
    $_SERVER['REQUEST_URI'] = $route;
    $view = new Core\View($root, ['environment' => 'development', 'name' => 'Prueba']);
    $view->setUser(['name' => 'Prueba', 'username' => 'prueba', 'permissions' => ['*']]);
    ob_start();
    $view->render('layouts/main.html.twig');
    $html = ob_get_clean();
    foreach (['calendar-page', 'dashboard-page', 'store-page'] as $page) {
        if (str_contains($html, '/assets/js/' . $page . '.js?') !== ($page === $expected)) {
            throw new RuntimeException('Recursos incorrectos para ' . $route);
        }
    }
    if (!str_contains($html, '/assets/js/app.js?v=')) {
        throw new RuntimeException('Falta el marco común');
    }
}
$bytes = filesize($root . '/public/assets/js/app.js');
if ($bytes > 20000) {
    throw new RuntimeException('El marco volvió a incorporar bibliotecas pesadas');
}
echo "OK: inventario y usuarios ligeros; calendario, gráficos y POS separados. Marco: $bytes bytes.\n";
