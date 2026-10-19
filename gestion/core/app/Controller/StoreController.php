<?php
declare(strict_types=1);
namespace App\Controller;
use App\Model\Catalog;
use App\Model\Finance;
use App\Model\Store;
use App\Service\FinanceService;
use App\Service\ProductImageService;
use Core\Controller;
use Core\Session;

// Presenta el POS visual y confirma usando el mismo servicio transaccional de ventas.
final class StoreController extends Controller
{
    public function index(array $parameters = []): void
    {
        $search = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 160);
        $category = max(0, (int) ($_GET['categoria'] ?? 0));
        $page = max(1, min(100000, (int) ($_GET['page'] ?? 1)));
        $result = new Store($this->context->db())->products($search, $category ?: null, $page);
        $catalog = new Catalog($this->context->db());
        $confirmed = $_SESSION['pos_confirmed'] ?? null;
        unset($_SESSION['pos_confirmed']);
        $this->context->view->render('store/index.html.twig', [
            ...$result,
            'search' => $search,
            'category' => $category,
            'page' => $page,
            'confirmed' => $confirmed,
            'categories' => $catalog->choices('product_categories'),
            'clients' => $catalog->choices('clients'),
            'request_key' => bin2hex(random_bytes(32)),
        ]);
    }
    public function checkout(array $parameters = []): void
    {
        $id = new FinanceService($this->context->db())->sale($_POST, (int) $_SESSION['user_id']);
        $_SESSION['pos_confirmed'] = $id;
        Session::flash(
            'Venta #' . $id . ' registrada. Continúa en ventas y cobros para aplicar el pago.',
        );
        $this->redirect('/tienda');
    }
    public function upload(array $parameters): void
    {
        new ProductImageService($this->context->db())->save(
            (int) $parameters['id'],
            $_FILES['image'] ?? [],
            (int) $_SESSION['user_id'],
        );
        Session::flash('Imagen del producto guardada.');
        $this->redirect('/catalogos/productos/' . $parameters['id'] . '/editar');
    }
    public function image(array $parameters): void
    {
        $filename = new Store($this->context->db())->image((int) $parameters['id']);
        if (!$filename || !preg_match('/^[a-f0-9]{40}\.(jpg|png|webp)$/D', $filename)) {
            http_response_code(404);
            return;
        }
        $file = dirname(__DIR__, 3) . '/storage/uploads/products/' . $filename;
        if (!is_file($file)) {
            http_response_code(404);
            return;
        }
        header('Content-Type: ' . new \finfo(FILEINFO_MIME_TYPE)->file($file));
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }
}
