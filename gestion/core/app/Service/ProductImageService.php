<?php
declare(strict_types=1);
namespace App\Service;
use App\Model\Catalog;
use App\Model\Store;
use DomainException;
use PDO;

// Valida imágenes reales y las guarda fuera de la raíz pública con un nombre aleatorio.
final class ProductImageService
{
    public function __construct(private PDO $db) {}
    public function save(int $product, array $file, int $user): void
    {
        if (!$product || !(new Catalog($this->db))->find('products', $product)) {
            throw new DomainException('El producto no existe.');
        }
        if (
            ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
            !is_uploaded_file($file['tmp_name'] ?? '')
        ) {
            throw new DomainException('Selecciona una imagen válida.');
        }
        if (($file['size'] ?? 0) > 4 * 1024 * 1024) {
            throw new DomainException('La imagen no puede superar 4 MB.');
        }
        $type = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $size = @getimagesize($file['tmp_name']);
        if (!isset($extensions[$type]) || !$size || $size[0] > 6000 || $size[1] > 6000) {
            throw new DomainException('Usa JPG, PNG o WebP de hasta 6000 × 6000 píxeles.');
        }
        $directory = dirname(__DIR__, 3) . '/storage/uploads/products';
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $filename = bin2hex(random_bytes(20)) . '.' . $extensions[$type];
        $target = $directory . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new \RuntimeException('No se pudo guardar la imagen.');
        }
        $this->db->beginTransaction();
        try {
            (new Store($this->db))->setImage($product, $filename);
            (new AuditService($this->db))->record($user, 'image', 'products', $product);
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            unlink($target);
            throw $error;
        }
    }
}
