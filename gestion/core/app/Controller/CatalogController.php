<?php
declare(strict_types=1);
namespace App\Controller;
use App\Model\Catalog;
use App\Service\CatalogService;
use Core\Controller;
use Core\Session;
use DomainException;
// Recibe formularios de catálogo y delega validación y persistencia al servicio.
final class CatalogController extends Controller
{
    public function index(array $parameters): void
    {
        $slug = $parameters['slug'];
        $definition = CatalogService::definition($slug);
        $search = mb_substr((string) ($_GET['q'] ?? ''), 0, 160);
        $page = max(1, min(100000, (int) ($_GET['page'] ?? 1)));
        $result = (new Catalog($this->context->db()))->search($definition, $search, $page);
        $this->context->view->render(
            in_array($slug, ['mascotas', 'veterinarios'], true)
                ? 'catalog/directory.html.twig'
                : 'catalog/index.html.twig',
            compact('slug', 'definition', 'search', 'page') + $result,
        );
    }
    public function form(array $parameters): void
    {
        $slug = $parameters['slug'];
        $definition = CatalogService::definition($slug);
        $model = new Catalog($this->context->db());
        $record = isset($parameters['id'])
            ? $model->find($definition['table'], (int) $parameters['id'])
            : ['active' => 1];
        if (!$record) {
            throw new DomainException('El registro no existe.');
        }
        $choices = [];
        foreach ($definition['fields'] as $key => $field) {
            if ($field['type'] === 'select') {
                $choices[$key] = $model->choices($field['table']);
            }
        }
        $this->context->view->render(
            'catalog/form.html.twig',
            compact('slug', 'definition', 'record', 'choices'),
        );
    }
    public function save(array $parameters): void
    {
        (new CatalogService($this->context->db()))->save(
            $parameters['slug'],
            $_POST,
            isset($parameters['id']) ? (int) $parameters['id'] : null,
            (int) $_SESSION['user_id'],
        );
        Session::flash('Registro guardado.');
        $this->redirect('/catalogos/' . $parameters['slug']);
    }
    public function retireProduct(array $parameters): void
    {
        $db = $this->context->db();
        $id = (int) $parameters['id'];
        if (!(new Catalog($db))->find('products', $id)) { throw new DomainException('El producto no existe.'); }
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE products SET active=0,publish_online=0 WHERE id=?')->execute([$id]);
            (new \App\Service\AuditService($db))->record((int) $_SESSION['user_id'], 'unpublish', 'products', $id);
            $db->commit();
        } catch (\Throwable $error) { if ($db->inTransaction()) $db->rollBack(); throw $error; }
        Session::flash('Producto retirado de la tienda. Su inventario e historial se conservan.');
        $this->redirect('/catalogos/productos');
    }
}
