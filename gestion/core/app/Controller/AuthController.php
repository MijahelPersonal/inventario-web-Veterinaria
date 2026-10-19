<?php
declare(strict_types=1);
namespace App\Controller;
use Core\Controller;
use Core\Session;
use DomainException;
final class AuthController extends Controller
{
    public function form(array $parameters = []): void
    {
        $this->context->view->render('auth/login.html.twig');
    }
    public function login(array $parameters = []): void
    {
        try {
            $this->context
                ->auth()
                ->login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''));
        } catch (DomainException $error) {
            Session::flash($error->getMessage(), 'danger');
            $this->redirect('/login');
        }
        $this->redirect('/');
    }
    public function logout(array $parameters = []): void
    {
        $this->context->auth()->logout();
        $this->redirect('/login');
    }
}
