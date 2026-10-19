<?php
declare(strict_types=1);

namespace App\controller;

use Core\View;

final class HomeController
{
    public function __construct(private View $view) {}

    public function index(array $parameters = []): void
    {
        $this->view->render('home/index.html.twig');
    }
}
