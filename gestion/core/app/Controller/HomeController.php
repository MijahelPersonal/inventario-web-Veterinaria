<?php
declare(strict_types=1);

namespace App\Controller;

use Core\Controller;

final class HomeController extends Controller
{
    public function index(array $parameters = []): void
    {
        $this->context->view->render('home/index.html.twig');
    }
}
