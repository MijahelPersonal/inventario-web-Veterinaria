<?php
declare(strict_types=1);
namespace App\Controller;
use App\Model\Catalog;
use App\Model\Inventory;
use App\Service\InventoryService;
use Core\Controller;
use Core\Session;

// Muestra existencias y recibe formularios de movimientos; el servicio decide su validez.
final class InventoryController extends Controller
{
    public function index(array $parameters = []): void
    {
        $model = new Inventory($this->context->db());
        $this->context->view->render('inventory/index.html.twig', [
            'products' => $model->overview(),
            'lots' => $model->lots(),
            'movements' => $model->movements(),
            'suppliers' => (new Catalog($this->context->db()))->choices('suppliers'),
            'incoming_key' => bin2hex(random_bytes(32)),
            'outgoing_key' => bin2hex(random_bytes(32)),
        ]);
    }
    public function save(array $parameters = []): void
    {
        (new InventoryService($this->context->db()))->movement($_POST, (int) $_SESSION['user_id']);
        Session::flash('Movimiento registrado con su responsable.');
        $this->redirect('/inventario');
    }
}
