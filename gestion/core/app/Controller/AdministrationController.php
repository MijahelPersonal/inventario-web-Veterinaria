<?php
declare(strict_types=1);
namespace App\Controller;
use App\Model\Administration;
use App\Service\AdministrationService;
use Core\Controller;
use Core\Session;
use DomainException;

// Expone preferencias y usuarios únicamente al personal autorizado.
final class AdministrationController extends Controller
{
    public function webRequests(array $parameters = []): void
    {
        $rows = $this->context
            ->db()
            ->query('SELECT * FROM web_requests ORDER BY id DESC LIMIT 100')
            ->fetchAll();
        foreach ($rows as &$row) {
            $row['data'] = json_decode($row['payload'], true, 32, JSON_THROW_ON_ERROR);
        }
        $this->context->view->render('administration/web-requests.html.twig', [
            'requests' => $rows,
        ]);
    }
    public function webRequestStatus(array $parameters): void
    {
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['new', 'contacted', 'closed'], true)) {
            throw new DomainException('Estado inválido.');
        }
        $this->context
            ->db()
            ->prepare('UPDATE web_requests SET status=? WHERE id=?')
            ->execute([$status, (int) $parameters['id']]);
        Session::flash('Estado de la solicitud actualizado.');
        $this->redirect('/solicitudes-web');
    }
    public function settings(array $parameters = []): void
    {
        $this->context->view->render('administration/settings.html.twig', [
            'settings' => (new Administration($this->context->db()))->settings(),
        ]);
    }
    public function saveSettings(array $parameters = []): void
    {
        (new AdministrationService($this->context->db()))->settings(
            $_POST,
            (int) $_SESSION['user_id'],
        );
        Session::flash('Preferencias guardadas.');
        $this->redirect('/configuracion');
    }
    public function users(array $parameters = []): void
    {
        $model = new Administration($this->context->db());
        $record = isset($_GET['edit'])
            ? $model->findUser((int) $_GET['edit'])
            : ['active' => 1, 'roles' => []];
        if (!$record) {
            throw new DomainException('El usuario no existe.');
        }
        $this->context->view->render('administration/users.html.twig', [
            'users' => $model->users(),
            'roles' => $model->roles(),
            'record' => $record,
        ]);
    }
    public function saveUser(array $parameters = []): void
    {
        (new AdministrationService($this->context->db()))->user(
            $_POST,
            empty($_POST['id']) ? null : (int) $_POST['id'],
            (int) $_SESSION['user_id'],
        );
        Session::flash('Usuario guardado.');
        $this->redirect('/usuarios');
    }
}
