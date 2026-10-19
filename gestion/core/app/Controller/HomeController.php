<?php
declare(strict_types=1);

namespace App\Controller;

use Core\Controller;

final class HomeController extends Controller
{
    public function index(array $parameters = []): void
    {
        $permissions = $this->context->auth()->current()['permissions'];
        $financial =
            in_array('*', $permissions, true) || in_array('sales.read', $permissions, true);
        $this->context->view->render('home/index.html.twig', [
            'dashboard' => new \App\Model\Report($this->context->db())->dashboard($financial),
            'financial' => $financial,
        ]);
    }
}
