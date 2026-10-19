<?php
declare(strict_types=1);
namespace App\Controller;
use App\Model\Administration;
use App\Model\Catalog;
use App\Model\Finance;
use App\Service\DocumentService;
use App\Service\FinanceService;
use Core\Controller;
use Core\Session;
use DomainException;

// Separa la pantalla de ventas de las operaciones de cobro y cierre de caja.
final class FinanceController extends Controller
{
    public function index(array $parameters = []): void
    {
        $model = new Finance($this->context->db());
        $catalog = new Catalog($this->context->db());
        $sale = empty($_GET['venta']) ? null : $model->sale((int) $_GET['venta']);
        $session = $model->session((int) $_SESSION['user_id']);
        $this->context->view->render('finance/index.html.twig', [
            'sales' => $model->sales(),
            'summary' => $model->summary(),
            'pending_sales' => $model->pendingSales(),
            'sale' => $sale,
            'items' => $sale ? $model->items((int) $sale['id']) : [],
            'paid' => $sale ? $model->paid((int) $sale['id']) : '0.00',
            'payments' => $model->payments($sale ? (int) $sale['id'] : null),
            'pending' => $model->pendingAppointments(),
            'clients' => $catalog->choices('clients'),
            'products' => $catalog->choices('products'),
            'services' => $catalog->choices('services'),
            'sale_key' => bin2hex(random_bytes(32)),
            'payment_key' => bin2hex(random_bytes(32)),
            'session' => $session,
            'expected' => $session ? $model->expected($session) : '0.00',
            'sessions' => $model->sessions((int) $_SESSION['user_id']),
            'entries' => $session ? $model->entries((int) $session['id']) : [],
            'request_key' => bin2hex(random_bytes(32)),
        ]);
    }
    public function paymentForm(array $parameters = []): void
    {
        $model = new Finance($this->context->db());
        $this->context->view->render('finance/payment-form.html.twig', [
            'pending_sales' => $model->pendingSales(),
            'selected' => (int) ($_GET['venta'] ?? 0),
            'session' => $model->session((int) $_SESSION['user_id']),
            'request_key' => bin2hex(random_bytes(32)),
        ]);
    }
    public function void(array $parameters): void
    {
        (new FinanceService($this->context->db()))->voidSale(
            (int) $parameters['id'],
            (string) ($_POST['reason'] ?? ''),
            (int) $_SESSION['user_id'],
        );
        Session::flash('Venta anulada y existencias devueltas a sus lotes.');
        $this->redirect('/ventas?venta=' . (int) $parameters['id']);
    }
    public function sale(array $parameters = []): void
    {
        $id = (new FinanceService($this->context->db()))->sale($_POST, (int) $_SESSION['user_id']);
        Session::flash('Venta registrada. El cobro se registra por separado.');
        $this->redirect('/ventas?venta=' . $id);
    }
    public function pay(array $parameters = []): void
    {
        (new FinanceService($this->context->db()))->payment($_POST, (int) $_SESSION['user_id']);
        Session::flash('Cobro aplicado al saldo.');
        $this->redirect('/ventas?venta=' . (int) $_POST['sale_id']);
    }
    public function reverse(array $parameters): void
    {
        (new FinanceService($this->context->db()))->reverse(
            (int) $parameters['id'],
            (string) ($_POST['reason'] ?? ''),
            (int) $_SESSION['user_id'],
        );
        Session::flash('Cobro revertido sin eliminar el original.');
        $this->redirect('/ventas');
    }
    public function cashOperation(array $parameters): void
    {
        (new FinanceService($this->context->db()))->cash(
            $parameters['action'],
            $_POST,
            (int) $_SESSION['user_id'],
        );
        Session::flash('Operación de caja registrada.');
        $this->redirect('/ventas#control-efectivo');
    }
    public function receipt(array $parameters): void
    {
        $model = new Finance($this->context->db());
        $sale = $model->sale((int) $parameters['id']);
        if (!$sale) {
            throw new DomainException('La venta no existe.');
        }
        $data = [
            'sale' => $sale,
            'items' => $model->items((int) $sale['id']),
            'payments' => $model->payments((int) $sale['id']),
            'paid' => $model->paid((int) $sale['id']),
            'clinic' => (new Administration($this->context->db()))->settings(),
        ];
        $html = $this->context->view->html('documents/receipt.html.twig', $data);
        (new DocumentService())->pdf($html, 'ticket-interno-' . $sale['id'] . '.pdf');
    }
}
