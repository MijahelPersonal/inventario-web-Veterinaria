<?php
declare(strict_types=1);
namespace App\Controller;
use Core\Controller;

// Expone únicamente catálogo publicable y recepción de solicitudes entre servidores.
final class PublicWebController extends Controller
{
    private function json(array $value, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
    public function catalog(array $parameters = []): void
    {
        $db = $this->context->db();
        $products = $db
            ->query(
                "SELECT p.id,p.code,p.name,p.description,p.presentation,p.price,COALESCE(c.name,'Esenciales') category,COALESCE(b.name,'') brand,IF(p.image_path IS NULL,NULL,CONCAT('/api/web/productos/',p.id,'/imagen')) image,COALESCE(SUM(CASE WHEN l.expires_at IS NULL OR l.expires_at>=CURRENT_DATE THEN l.quantity-l.reserved_quantity ELSE 0 END),0) available FROM products p LEFT JOIN stock_lots l ON l.product_id=p.id LEFT JOIN product_categories c ON c.id=p.category_id LEFT JOIN brands b ON b.id=p.brand_id WHERE p.active=1 AND p.publish_online=1 GROUP BY p.id ORDER BY p.name LIMIT 500",
            )
            ->fetchAll();
        $services = $db
            ->query(
                "SELECT id,name,description,price,duration_minutes FROM services WHERE active=1 AND name NOT LIKE '%(prueba)%' ORDER BY name",
            )
            ->fetchAll();
        $vets = $db
            ->query(
                "SELECT id,name FROM veterinarians WHERE active=1 AND name NOT LIKE '%(prueba)%' ORDER BY name",
            )
            ->fetchAll();
        $this->json(['products' => $products, 'services' => $services, 'veterinarians' => $vets]);
    }
    public function image(array $parameters): void
    {
        $query = $this->context
            ->db()
            ->prepare(
                'SELECT image_path FROM products WHERE id=? AND active=1 AND publish_online=1',
            );
        $query->execute([(int) $parameters['id']]);
        $file = $query->fetchColumn();
        if (!$file || !preg_match('/^[a-f0-9]{40}\.(jpg|png|webp)$/D', $file)) {
            http_response_code(404);
            return;
        }
        $path = dirname(__DIR__, 3) . '/storage/uploads/products/' . $file;
        if (!is_file($path)) {
            http_response_code(404);
            return;
        }
        header('Content-Type: ' . (new \finfo(FILEINFO_MIME_TYPE))->file($path));
        header('Cache-Control: public,max-age=3600');
        readfile($path);
    }
    public function submit(array $parameters = []): void
    {
        $file = dirname(__DIR__, 3) . '/config/web.local.php';
        $config = is_file($file) ? require $file : [];
        $key = (string) ($config['key'] ?? '');
        $provided = (string) ($_SERVER['HTTP_X_STRUKVET_KEY'] ?? '');
        if (strlen($key) < 32 || !hash_equals($key, $provided)) {
            $this->json(['error' => 'Canal web no autorizado.'], 403);
            return;
        }
        $body = file_get_contents('php://input', false, null, 0, 20001);
        if (strlen($body) > 20000) {
            $this->json(['error' => 'Solicitud demasiado extensa.'], 413);
            return;
        }
        try {
            $input = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($input)) {
                throw new \DomainException('Formulario inválido.');
            }
            $reference = (new \App\Service\WebRequestService($this->context->db()))->submit(
                $input,
                (string) ($_SERVER['HTTP_X_CLIENT_HASH'] ?? ''),
            );
            $this->json(['reference' => $reference], 201);
        } catch (\DomainException | \JsonException $error) {
            $this->json(
                [
                    'error' =>
                        $error instanceof \JsonException
                            ? 'Formulario inválido.'
                            : $error->getMessage(),
                ],
                422,
            );
        }
    }
}
